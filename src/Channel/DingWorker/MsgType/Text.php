<?php

declare(strict_types=1);

namespace Dog\Noticer\Channel\DingWorker\MsgType;

use Dog\Noticer\Channel\DingWorker\MsgType;

class Text extends MsgType
{
    /**
     * @var string
     */
    protected $type = 'text';

    /**
     * Text constructor.
     *
     * @param string $content 文本内容
     */
    public function __construct($content)
    {
        $this->data = [
            'content' => $content,
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
            'content' => base64_encode($data['content']),
        ];
    }
}
