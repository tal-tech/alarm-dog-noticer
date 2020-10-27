<?php

declare(strict_types=1);

namespace Dog\Noticer\Channel;

use Dog\Noticer\Channel\DingWorker\MsgType;
use Dog\Noticer\Contract\AbstractHttpChannel;
use Dog\Noticer\Exception\NoticeException;
use Dog\Noticer\Exception\ReachLimitException;
use GuzzleHttp\Psr7\Response;
use Hyperf\Redis\Redis;
use Psr\Container\ContainerInterface;
use Redis as LocalRedis;

class DingWorker extends AbstractHttpChannel
{
    /**
     * 错误码定义.
     */
    // 未发生错误
    const ERR_NONE = 0;

    // 状态码非200
    const ERR_NOT_200 = 2001;

    // errcode字段未设置
    const ERR_NOT_KEY_ERRCODE = 2002;

    // 达到频率限制
    const ERR_REACH_LIMIT = 2003;

    // 其他错误
    const ERR_OTHER = 2004;

    /**
     * 通知渠道名称.
     *
     * @var string
     */
    protected $name = 'dingworker';

    /**
     * Redis实例.
     *
     * @var Redis
     */
    protected $redis;

    /**
     * 获取ticket的url.
     *
     * @var string
     */
    protected $uriGetTicket = 'https://yourdomain.com';

    /**
     * 发送通知的url.
     *
     * @var string
     */
    protected $uriNotice = 'https://yourdomain.com';

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->redis = $container->get(LocalRedis::class);

        if (! empty($this->config['uri_get_ticket'])) {
            $this->uriGetTicket = $this->config['uri_get_ticket'];
        }
        if (! empty($this->config['uri_notice'])) {
            $this->uriNotice = $this->config['uri_notice'];
        }
    }

    /**
     * 发送消息.
     *
     * @param array $options 指定邮箱发送：['emails' => 'email1@foo.bar|email2@foo.bar']，多个以|符号分隔
     *                       指定工号发送：['workcodes' => '000000|999999']
     * @throws NoticeException
     * @throws ReachLimitException
     * @return bool 失败时会抛出异常
     */
    public function send(MsgType $msgType, array $options = [])
    {
        $data = array_merge($msgType->getParam(), $options);
        $data['ticket'] = $this->getTicket();

        $resp = $this->guzzle->post($this->uriNotice, [
            'form_params' => $data,
        ]);

        $json = $this->handleResp($resp, 'notice by dingworker');

        // 达到频率限制
        if ($json['errcode'] == 426) {
            throw new ReachLimitException(
                'notice by dingworker failed, reach frequency limit',
                static::ERR_REACH_LIMIT,
                [
                    'json' => $json,
                ]
            );
        }
        if ($json['errcode']) {
            throw new NoticeException('notice by dingworker failed, error: ' . $json['errmsg'], static::ERR_OTHER, [
                'json' => $json,
            ]);
        }

        return true;
    }

    /**
     * 获取Ticket.
     *
     * @return string
     */
    protected function getTicket()
    {
        $cacheKey = 'dingworker.ticket.' . $this->config['appid'];
        if (! ($ticket = $this->redis->get($cacheKey))) {
            $ticket = $this->getTicketFromHttp();
            // 缓存ticket
            $this->redis->set($cacheKey, $ticket, 100);
        }

        return $ticket;
    }

    /**
     * 从网络获取Ticket.
     *
     * @return string
     */
    protected function getTicketFromHttp()
    {
        $resp = $this->guzzle->get($this->uriGetTicket, [
            'query' => [
                'appid' => $this->config['appid'],
                'appkey' => $this->config['appkey'],
            ],
        ]);

        $json = $this->handleResp($resp, 'get dingworker ticket');
        if ($json['errcode']) {
            throw new NoticeException('get dingworker ticket failed, error: ' . $json['errmsg'], static::ERR_OTHER, [
                'json' => $json,
            ]);
        }

        return $json['ticket'];
    }

    /**
     * 处理响应.
     *
     * @param string $business
     * @return array
     */
    protected function handleResp(Response $resp, $business = null)
    {
        // 状态码判断
        $body = (string) $resp->getBody()->getContents();
        if ($resp->getStatusCode() !== 200) {
            throw new NoticeException($business . ' failed, status code is not 200', static::ERR_NOT_200, [
                'status_code' => $resp->getStatusCode(),
                'body' => $body,
            ]);
        }

        // 数据格式判断
        $json = $body ? json_decode($body, true) : $body;
        if (! $json || ! isset($json['errcode'])) {
            throw new NoticeException($business . ' failed, not key errcode', static::ERR_NOT_KEY_ERRCODE, [
                'body' => $body,
            ]);
        }

        return $json;
    }
}
