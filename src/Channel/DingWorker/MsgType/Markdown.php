<?php

declare(strict_types=1);

namespace Dog\Noticer\Channel\DingWorker\MsgType;

use Dog\Noticer\Channel\DingWorker\MsgType;

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
     */
    public function __construct($title, $text)
    {
        $this->data = [
            'title' => $title,
            'text' => $text,
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
            'content' => base64_encode(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)),
        ];
    }
}
