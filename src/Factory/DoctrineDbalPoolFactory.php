<?php

declare(strict_types = 1);

namespace Moln\SwooleDoctrinePool\Factory;

use Doctrine\DBAL\Connection;
use Moln\SwooleDoctrinePool\DoctrineDbalPool;
use Psr\Container\ContainerInterface;

class DoctrineDbalPoolFactory
{
    use ConnectionTrait;

    public function __invoke(ContainerInterface $container): DoctrineDbalPool
    {
        $poolConfig = $this->getConfig($container);

        $connectionName = $poolConfig['connection'] ?? Connection::class;
        /** @var Connection $conn */
        $conn = $container->get($connectionName);
        $conn->close();
        unset($poolConfig['connection']);

        return new DoctrineDbalPool(
            fn() => clone $conn,
            ...$poolConfig,
        );
    }
}
