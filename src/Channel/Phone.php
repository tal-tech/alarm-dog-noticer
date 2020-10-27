<?php

declare(strict_types=1);

namespace Dog\Noticer\Channel;

use Dog\Noticer\Contract\AbstractHttpChannel;
use Dog\Noticer\Exception\NoticeException;
use Psr\Container\ContainerInterface;

class Phone extends AbstractHttpChannel
{
    /**
     * 错误码定义.
     */
    // 未发生错误
    const ERR_NONE = 0;

    // 状态码非200
    const ERR_NOT_200 = 3001;

    // errcode字段未设置
    const ERR_NOT_KEY_ERRCODE = 3002;

    // 达到频率限制
    const ERR_REACH_LIMIT = 3003;

    // 其他错误
    const ERR_OTHER = 3004;

    /**
     * 通知渠道名称.
     *
     * @var string
     */
    protected $name = 'phone';

    /**
     * 通知的URL.
     *
     * @var string
     */
    protected $uriNotice;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->uriNotice = $this->config['base_uri'] . 'Accounts/' . $this->config['sid'] . '/Calls/LandingCalls/';
    }

    /**
     * 发送电话通知.
     *
     * @param string $content
     * @param string $receiver 手机号，一次只能一个
     * @param array $options 选项请参考文档中参数：https://www.yuntongxun.com/doc/rest/tongzhi/3_5_1_1.html
     * @throws NoticeException
     * @return array ['statusCode' => '000000', 'LandingCall' => ['dateCreated' => '2020-03-11 23:50:01',
     *               'callSid' => '200311235001233400010537004dc5f0', 'orderId' => 'CM1053720200311235001256753']]
     */
    public function send($content, $receiver, $options = [])
    {
        $query = [
            'sig' => $this->sign(),
        ];
        $data = array_merge($this->config['options'] ?: [], [
            'appId' => $this->config['appid'],
            'to' => $receiver,
            'mediaTxt' => $content,
        ], $options);

        $resp = $this->guzzle->post($this->uriNotice, [
            'query' => $query,
            'json' => $data,
            'headers' => [
                'Authorization' => $this->authorization(),
                'Accept' => 'application/json',
            ],
        ]);

        $body = (string) $resp->getBody()->getContents();
        // 状态码判断
        if ($resp->getStatusCode() !== 200) {
            throw new NoticeException('notice by phone failed, status code is not 200', static::ERR_NOT_200, [
                'status_code' => $resp->getStatusCode(),
                'body' => $body,
            ]);
        }

        // 数据格式判断
        $json = $body ? json_decode($body, true) : $body;
        if (! $json || ! isset($json['statusCode'])) {
            throw new NoticeException('notice by phone failed, not key errcode', static::ERR_NOT_KEY_ERRCODE, [
                'body' => $body,
            ]);
        }

        // 判断错误码
        if ($json['statusCode'] != '000000') {
            throw new NoticeException('notice by phone failed, error: ' . $json['statusMsg'], static::ERR_OTHER, [
                'json' => $json,
            ]);
        }

        return $json;
    }

    /**
     * 生成签名.
     *
     * @return string
     */
    protected function sign()
    {
        return md5($this->config['sid'] . $this->config['token'] . date('YmdHis'));
    }

    /**
     * 生成Authorization认证header.
     *
     * @return string
     */
    private function authorization()
    {
        return base64_encode($this->config['sid'] . ':' . date('YmdHis'));
    }
}
