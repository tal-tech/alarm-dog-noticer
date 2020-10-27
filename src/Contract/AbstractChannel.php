<?php

declare(strict_types=1);

namespace Dog\Noticer\Contract;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Logger\Logger;
use Hyperf\Logger\LoggerFactory;
use Psr\Container\ContainerInterface;

abstract class AbstractChannel
{
    /**
     * 通知渠道名称.
     *
     * @var string
     */
    protected $name = 'default';

    /**
     * 配置信息.
     *
     * @var array
     */
    protected $config = [];

    /**
     * Container容器.
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * 日志logger.
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Last error code.
     *
     * @var int
     */
    protected $lastErrno = 0;

    /**
     * Last error.
     *
     * @var array
     */
    protected $lastError;

    /**
     * DingTalk constructor.
     *
     * @param array $config
     * @param string $namespace
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        $configer = $container->get(ConfigInterface::class);
        $this->config = $configer->get('noticer.channel.' . $this->name);

        // logger
        $this->logger = $container->get(LoggerFactory::class)->get('dog-noticer');
    }

    /**
     * 获取最后错误码
     *
     * @return int
     */
    public function getLastErrno()
    {
        return $this->lastErrno;
    }

    /**
     * 获取最后错误信息.
     *
     * @return null|array
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * 通知渠道名称.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 处理错误信息.
     */
    protected function handleError(int $errno, string $message, array $context = [], bool $saveLog = false)
    {
        // TODO 改用协程上下文，否则有风险
        $this->lastErrno = $errno;
        $this->lastError = [
            'message' => $message,
            'context' => $context,
        ];

        if ($saveLog) {
            $context['errno'] = $errno;
            $this->logger->error($message, $context);
        }
    }
}
