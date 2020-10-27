<?php

declare(strict_types=1);

namespace Dog\Noticer\Channel\DingGroup\MsgType;

use Dog\Noticer\Channel\DingGroup\MsgType;

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
            'msgtype' => $this->getType(),
            $this->getType() => $data,
        ];
    }
}
