<?php

declare(strict_types=1);

namespace Dog\Noticer\Contract;

use Dog\Noticer\Component\Guzzle;
use GuzzleHttp\Client;
use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;

abstract class AbstractHttpChannel extends AbstractChannel
{
    /**
     * Guzzle客户端.
     *
     * @var Client
     */
    protected $guzzle;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        // 初始化guzzle
        $this->initGuzzle($container->get(ConfigInterface::class));
    }

    /**
     * 初始化Guzzle.
     */
    protected function initGuzzle(ConfigInterface $configer)
    {
        $config = array_merge($configer->get('noticer.guzzle', []), $this->config['guzzle'] ?? []);
        $this->guzzle = Guzzle::create($config);
    }
}
