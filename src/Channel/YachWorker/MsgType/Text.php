<?php

declare(strict_types=1);

namespace Dog\Noticer\Channel\YachWorker\MsgType;

use Dog\Noticer\Channel\YachWorker\MsgType;

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
