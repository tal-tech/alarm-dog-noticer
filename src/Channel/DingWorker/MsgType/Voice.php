<?php

declare(strict_types=1);

namespace Dog\Noticer\Channel\DingWorker\MsgType;

use Dog\Noticer\Channel\DingWorker\MsgType;

class Voice extends MsgType
{
    /**
     * @var string
     */
    protected $type = 'voice';

    /**
     * Voice constructor.
     *
     * @param string $mediaUrl
     * @param string $duration 正整数，小于60，表示音频时长
     */
    public function __construct($mediaUrl, $duration)
    {
        $this->data = [
            'media_url' => $mediaUrl,
            'duration' => $duration,
        ];
    }

    /**
     * @return array
     */
    public function getParam()
    {
        $data = $this->toArray();
        $mediaUrl = $data['media_url'];
        unset($data['media_url']);

        return [
            'type' => $this->getType(),
            'media_url' => $mediaUrl,
            'content' => base64_encode(json_encode($data)),
        ];
    }
}
