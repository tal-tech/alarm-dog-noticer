<?php

declare(strict_types=1);

namespace Dog\Noticer\Channel\DingGroup\MsgType;

use Dog\Noticer\Channel\DingGroup\MsgType;

class FeedCard extends MsgType
{
    /**
     * @var string
     */
    protected $type = 'feedCard';

    /**
     * FeedCard constructor.
     */
    public function __construct()
    {
    }

    /**
     * 设置FeedCard的链接.
     *
     * @param string $title
     * @param string $messageURL
     * @param string $picURL
     * @param bool $append
     */
    public function link($title, $messageURL, $picURL, $append = true)
    {
        $link = [
            'title' => $title,
            'messageURL' => $messageURL,
            'picURL' => $picURL,
        ];

        if ($append) {
            $this->data['links'][] = $link;
        } else {
            $this->data['links'] = [$link];
        }
    }

    /**
     * @return array
     */
    public function getParam()
    {
        $data = $this->toArray();

        if (empty($data['links'])) {
            throw new \InvalidArgumentException('请设置FeedCard的Link');
        }

        return [
            'msgtype' => $this->getType(),
            $this->getType() => $data,
        ];
    }
}
