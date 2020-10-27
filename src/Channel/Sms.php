<?php

declare(strict_types=1);

namespace Dog\Noticer\Channel;

use Dog\Noticer\Contract\AbstractHttpChannel;
use Dog\Noticer\Exception\NoticeException;
use GuzzleHttp\Psr7\Response;
use Hyperf\Redis\Redis;
use Psr\Container\ContainerInterface;
use Redis as LocalRedis;

class Sms extends AbstractHttpChannel
{
    /**
     * 错误码定义.
     */
    // 未发生错误
    const ERR_NONE = 0;

    // 状态码非200
    const ERR_NOT_200 = 4001;

    // errcode字段未设置
    const ERR_NOT_KEY_ERRCODE = 4002;

    // 达到频率限制
    const ERR_REACH_LIMIT = 4003;

    // 其他错误
    const ERR_OTHER = 4004;

    /**
     * 通知渠道名称.
     *
     * @var string
     */
    protected $name = 'sms';

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
    protected $baseUri = 'https://yourdomain.com';

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->redis = $container->get(LocalRedis::class);

        if (! empty($this->config['base_uri'])) {
            $this->baseUri = $this->config['base_uri'];
        }
    }

    /**
     * 发送短信通知.
     *
     * @param string $tplId 短信模板ID
     * @param array $tplParams 短信模板参数
     * @param array $receivers 手机号列表 ['131xxxxxxxx', '132xxxxxxxx']
     * @throws NoticeException
     * @return array
     */
    public function send($tplId, $tplParams = [], $receivers = [])
    {
        $msgs = [];
        foreach ($receivers as $receiver) {
            $msgs[] = [
                'phone_code' => 86,
                'phone' => $receiver,
                'sms_param' => $tplParams,
            ];
        }
        $data = [
            'type' => 1,
            'msgs' => json_encode($msgs),
            'tpl_id' => $tplId,
        ];

        $query = [
            'ticket' => $this->getTicket(),
        ];

        $url = sprintf('%s/v1/sms/send', $this->baseUri);
        $resp = $this->guzzle->post($url, [
            'form_params' => $data,
            'query' => $query,
        ]);

        $json = $this->handleResp($resp, 'notice by sms');

        if ($json['errcode']) {
            throw new NoticeException('notice by sms failed, error: ' . $json['errmsg'], static::ERR_OTHER, [
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
        $cacheKey = 'sms.ticket.' . $this->config['appid'];
        if (! ($ticket = $this->redis->get($cacheKey))) {
            $ticket = $this->getTicketFromHttp();
            // 缓存ticket
            $this->redis->set($cacheKey, $ticket, 6000);
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
        $url = sprintf('%s/v1/ticket/get', $this->baseUri);
        $resp = $this->guzzle->get($url, [
            'query' => [
                'appid' => $this->config['appid'],
                'secret' => $this->config['secret'],
            ],
        ]);

        $json = $this->handleResp($resp, 'get sms ticket');
        if ($json['errcode']) {
            throw new NoticeException('get sms ticket failed, error: ' . $json['errmsg'], static::ERR_OTHER, [
                'json' => $json,
            ]);
        }

        return $json['data']['ticket'];
    }

    /**
     * 处理响应.
     *
     * @param string $business
     * @return array
     */
    protected function handleResp(Response $resp, $business = null)
    {
        $body = (string) $resp->getBody()->getContents();
        // 状态码判断
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
                'body' => mb_substr($body, 0, 1000),
            ]);
        }

        return $json;
    }
}
