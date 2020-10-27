<?php

declare(strict_types=1);

namespace Dog\Noticer\Channel\DingWorker\MsgType;

use Dog\Noticer\Channel\DingWorker\MsgType;

class Image extends MsgType
{
    /**
     * @var string
     */
    protected $type = 'image';

    /**
     * Image constructor.
     *
     * @param string $mediaUrl
     */
    public function __construct($mediaUrl)
    {
        $this->data = [
            'media_url' => $mediaUrl,
        ];
    }

    /**
     * @return array
     */
    public function getParam()
    {
        $data = $this->toArray();
        $data['type'] = $this->getType();

        return $data;
    }
}
