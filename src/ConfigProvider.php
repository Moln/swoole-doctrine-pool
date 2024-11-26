<?php

declare(strict_types = 1);

namespace Moln\SwooleDoctrinePool;

use Moln\SwooleDoctrinePool\Factory\DoctrineDbalPoolFactory;
use Moln\SwooleDoctrinePool\Factory\DoctrineOrmPoolFactory;

class ConfigProvider
{

    public function __invoke(): array
    {
        return [
            'dependencies' => [
                'factories' => [
                    'doctrine.connection-pool.default' => [DoctrineDbalPoolFactory::class, 'default'],
                    'doctrine.orm-pool.default' => [DoctrineOrmPoolFactory::class, 'default'],
                ],
                'aliases' => [
                    DoctrineDbalPool::class => 'doctrine.connection-pool.default',
                    DoctrineOrmPool::class => 'doctrine.orm-pool.default',
                ]
            ]
        ];
    }
}
