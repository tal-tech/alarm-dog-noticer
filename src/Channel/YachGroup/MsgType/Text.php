<?php

declare(strict_types=1);

namespace Dog\Noticer\Channel\YachGroup\MsgType;

use Dog\Noticer\Channel\YachGroup\MsgType;

class Text extends MsgType
{
    /**
     * @var string
     */
    protected $type = 'text';

    /**
     * Text constructor.
     *
     * @param string $content
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
            'msgtype' => $this->getType(),
            $this->getType() => $data,
        ];
    }
}
