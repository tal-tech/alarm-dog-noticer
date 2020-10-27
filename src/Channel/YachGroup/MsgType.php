<?php

declare(strict_types=1);

namespace Dog\Noticer\Channel\YachGroup;

abstract class MsgType
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var string
     */
    protected $type;

    /**
     * @return array
     */
    abstract public function getParam();

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }
}
