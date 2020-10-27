<?php

declare(strict_types=1);

namespace Dog\Noticer\Exception;

use Exception;
use Throwable;

class NoticeException extends Exception
{
    /**
     * 上下文信息.
     *
     * @var array
     */
    protected $context = [];

    public function __construct(string $message, int $code = 0, array $context = [], ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * 获取上下文信息.
     *
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }
}
