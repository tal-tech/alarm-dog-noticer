<?php

declare(strict_types=1);

namespace Dog\Noticer\Channel\DingWorker\MsgType;

use Dog\Noticer\Channel\DingWorker\MsgType;

class Link extends MsgType
{
    /**
     * @var string
     */
    protected $type = 'link';

    /**
     * Link constructor.
     *
     * @param string $title 消息标题
     * @param string $text 消息内容
     * @param string $messageUrl 点击消息后跳转的地址
     * @param string $picUrl jpg图片地址
     */
    public function __construct($title, $text, $messageUrl, $picUrl)
    {
        $this->data = [
            'title' => $title,
            'text' => $text,
            'picUrl' => $picUrl,
            'messageUrl' => $messageUrl,
        ];
    }

    /**
     * @return array
     */
    public function getParam()
    {
        $data = $this->toArray();

        return [
            'type' => $this->getType(),
            'content' => base64_encode(json_encode($data)),
        ];
    }
}
