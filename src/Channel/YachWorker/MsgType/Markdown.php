<?php

declare(strict_types=1);

namespace Dog\Noticer\Channel\YachWorker\MsgType;

use Dog\Noticer\Channel\YachWorker\MsgType;

class Markdown extends MsgType
{
    /**
     * @var string
     */
    protected $type = 'markdown';

    /**
     * Markdown constructor.
     *
     * @param string $title
     * @param string $text
     * @param string $image 图片URL
     */
    public function __construct($title, $text, $image = '')
    {
        $this->data = [
            'title' => $title,
            'text' => $text,
        ];
        if ($image) {
            $this->data['image'] = $image;
        }
    }

    /**
     * @return string
     */
    public function getParam()
    {
        $data = $this->toArray();

        $message = [
            'msgtype' => $this->getType(),
            $this->getType() => $data,
        ];

        return base64_encode(json_encode($message));
    }
}
