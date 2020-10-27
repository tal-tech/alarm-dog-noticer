<?php

declare(strict_types=1);

namespace Dog\Noticer\Channel\DingGroup\MsgType;

use Dog\Noticer\Channel\DingGroup\MsgType;

class Link extends MsgType
{
    /**
     * @var string
     */
    protected $type = 'link';

    /**
     * Link constructor.
     *
     * @param string $title
     * @param string $text
     * @param string $messageUrl
     * @param string $picUrl
     */
    public function __construct($title, $text, $messageUrl, $picUrl = '')
    {
        $this->data = [
            'title' => $title,
            'text' => $text,
            'messageUrl' => $messageUrl,
            'picUrl' => $picUrl,
        ];
    }

    /**
     * @return array
     */
    public function getParam()
    {
        $data = $this->toArray();

        return [
            'msgtype' => $this->getType(),
            $this->getType() => $data,
        ];
    }
}
