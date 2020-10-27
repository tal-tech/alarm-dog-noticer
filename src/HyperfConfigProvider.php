<?php

declare(strict_types=1);

namespace Dog\Noticer;

use Dog\Noticer\Contract\ConfigInterface;
use Hyperf\Contract\ConfigInterface as HyperfConfigInterface;

class HyperfConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                ConfigInterface::class => HyperfConfigInterface::class,
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config of noticer.',
                    'source' => __DIR__ . '/../config/noticer.php',
                    'destination' => BASE_PATH . '/config/autoload/noticer.php',
                ],
            ],
        ];
    }
}
