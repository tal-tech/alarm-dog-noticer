<?php

declare(strict_types=1);

namespace Dog\Noticer\Channel\YachGroup\MsgType;

use Dog\Noticer\Channel\YachGroup\MsgType;

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
     * @param string $image 图片url
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
