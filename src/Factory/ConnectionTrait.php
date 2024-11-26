<?php

declare(strict_types = 1);

namespace Moln\SwooleDoctrinePool\Factory;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Swoole\ConnectionPool;

trait ConnectionTrait
{

    public function __construct(private readonly string $keyName = 'default')
    {
    }

    public static function __callStatic(string $name, array $arguments): ConnectionPool
    {
        return (new self($name))($arguments[0]);
    }


    public function getConfig(ContainerInterface $container): array
    {
        $config = $container->get('config');
        $poolConfig = $config['doctrine']['pool'][$this->keyName];

        $loggerName = $poolConfig['logger'] ?? null;
        if (! $loggerName && $container->has(LoggerInterface::class)) {
            $logger = $container->get(LoggerInterface::class);
        } elseif ($loggerName) {
            $logger = $container->get($loggerName);
        } else {
            $logger = null;
        }
        $poolConfig['logger'] = $logger;

        return $poolConfig;
    }
}
