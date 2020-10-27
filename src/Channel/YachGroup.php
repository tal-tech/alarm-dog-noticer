<?php

declare(strict_types=1);

namespace Dog\Noticer\Channel;

use Dog\Noticer\Channel\YachGroup\MsgType;
use Dog\Noticer\Contract\AbstractHttpChannel;
use Dog\Noticer\Exception\NoticeException;
use Hyperf\Redis\Redis;
use Psr\Container\ContainerInterface;

class YachGroup extends AbstractHttpChannel
{
    /**
     * 错误码定义.
     */
    // 未发生错误
    const ERR_NONE = 0;

    // 状态码非200
    const ERR_NOT_200 = 6001;

    // errcode字段未设置
    const ERR_NOT_KEY_ERRCODE = 6002;

    // 达到频率限制
    const ERR_REACH_LIMIT = 6003;

    // 其他错误
    const ERR_OTHER = 6004;

    /**
     * 通知渠道名称.
     *
     * @var string
     */
    protected $name = 'yachgroup';

    /**
     * 消息推送url.
     *
     * @var string
     */
    protected $uriPush = 'https://yourdomain.com';

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        if (! empty($this->config['uri_push'])) {
            $this->uriPush = $this->config['uri_push'];
        }

        $this->config = array_merge([
            'sleep_redis_prefix' => 'dog.yachgroup.sleep.',
            'sleep_num' => 19,
            'sleep_time' => 61,
        ], $this->config);
    }

    /**
     * 发送消息.
     *
     * @param array $robots 多个机器人配置信息 [['webhook' => 'WEBHOOK', 'secret' => 'SECRET'], ...]
     * @return bool 返回false，可以通过 getLastErrno 和 getLastError 获取最后错误信息
     */
    public function send(MsgType $msgType, array $robots)
    {
        $this->lastErrno = static::ERR_NONE;
        $this->lastError = null;

        $data = $msgType->getParam();

        $sendSuccess = false;
        foreach ($robots as $robot) {
            // 判断机器人是否冷却中
            if ($this->isSleep($robot['webhook'])) {
                continue;
            }

            $reqSuccess = $this->sendHttpRequest($robot, $data);
            if ($reqSuccess) {
                $sendSuccess = true;
                break;
            }
        }

        // 所有机器人发送失败，返回错误信息
        if (! $sendSuccess) {
            throw new NoticeException($this->lastError['message'], $this->lastErrno, $this->lastError['context']);
        }

        return true;
    }

    /**
     * 钉钉机器人发送消息签名.
     *
     * @return array
     */
    protected function sign(string $secret)
    {
        $timestamp = time() * 1000;
        $sign = hash_hmac('sha256', "{$timestamp}\n{$secret}", $secret, true);
        $sign = base64_encode($sign);

        return [$sign, $timestamp];
    }

    /**
     * 钉钉机器人是否处于冷却状态
     *
     * @return bool
     */
    protected function isSleep(string $webhook)
    {
        // TODO 使用内存二级缓存加速冷却判断效率

        $key = $this->config['sleep_redis_prefix'] . $webhook;
        $redis = $this->container->get(Redis::class);
        // 为1时，表示是一个周期开始，设置过期时间
        $luaScript = "
            local key = KEYS[1];
            local sleep_time = tonumber(ARGV[1]);
            local num = redis.call('INCR', key);
            if num == 1 then
                redis.call('EXPIRE', key, sleep_time);
            end
            return num;
        ";
        $num = $redis->eval($luaScript, [$key, $this->config['sleep_time']], 1);

        if ($num > $this->config['sleep_num']) {
            // 为了避免意外情况没有给key成功设置ttl导致死锁问题
            if ($redis->ttl($key) == -1) {
                $redis->del($key);
                return false;
            }
            // 达到频率限制
            $this->handleError(static::ERR_REACH_LIMIT, 'notice by yachgroup failed, the robot is sleeping', [
                'webhook' => $webhook,
            ], false);
            return true;
        }
        return false;
    }

    /**
     * 发送HTTP请求
     *
     * @param
     */
    protected function sendHttpRequest(array $robot, array $data)
    {
        // 请求query参数
        $query = [
            'access_token' => $robot['webhook'],
        ];
        if (! empty($robot['secret'])) {
            [$sign, $timestamp] = $this->sign($robot['secret']);
            $query['sign'] = $sign;
            $query['timestamp'] = $timestamp;
        }

        $resp = $this->guzzle->post($this->uriPush, [
            'query' => $query,
            'json' => $data,
        ]);

        // 状态码判断
        if ($resp->getStatusCode() !== 200) {
            $this->handleError(static::ERR_NOT_200, 'notice by yachgroup failed, status is not 200', [
                'robot' => $robot,
                'data' => $data,
                'query' => $query,
                'status_code' => $resp->getStatusCode(),
                'resp' => (string) $resp->getBody()->getContents(),
            ]);

            return false;
        }

        // 数据格式判断
        $body = (string) $resp->getBody()->getContents();
        $json = $body ? json_decode($body, true) : $body;
        if (! $json || ! isset($json['code'])) {
            $this->handleError(static::ERR_NOT_KEY_ERRCODE, 'notice by yachgroup failed, not key code', [
                'robot' => $robot,
                'data' => $data,
                'query' => $query,
                'resp' => $json,
            ]);

            return false;
        }

        // 错误码判断
        if ($json['code'] == 50014) {
            // 达到频率限制暂不处理，仅返回失败
            $this->handleError(static::ERR_REACH_LIMIT, 'notice by yachgroup failed, reach frequency limit', [
                'robot' => $robot,
                'data' => $data,
                'query' => $query,
                'resp' => $json,
            ], false);

            return false;
        }
        // 其他错误
        if ($json['code'] != 200) {
            $this->handleError(static::ERR_OTHER, "notice by yachgroup failed, {$json['msg']}({$json['code']})", [
                'robot' => $robot,
                'data' => $data,
                'query' => $query,
                'json' => $json,
            ]);

            return false;
        }

        return true;
    }
}
