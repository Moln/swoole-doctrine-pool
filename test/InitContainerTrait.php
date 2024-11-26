<?php

declare(strict_types = 1);

namespace MolnTest\SwooleDoctrinePool;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Laminas\ServiceManager\ServiceManager;
use Moln\SwooleDoctrinePool\ConfigProvider;
use Psr\Container\ContainerInterface;

trait InitContainerTrait
{

    public function getContainer(): ContainerInterface
    {
        $container = new ServiceManager((new ConfigProvider())()['dependencies']);
        $container->setService('config', [
            'doctrine' => [
                'pool' => [
                    'connection' => Connection::class,
                    'orm' => EntityManagerInterface::class,
                ],
                'configuration' => [
                    'default' => [
                        'driver' => 'default',
                    ],
                ],
                'connection' => [
                    'default' => [
                        'event_manager' => 'default',
                        'params' => [
                            'url' => 'pdo-mysql://root:root@192.168.4.82/sys',
//                            'url' => 'sqlite:///' . __DIR__ . '/test.db',
                        ],
                    ],
                ],
                'entity_manager' => [
                    'default' => [
                        'connection' => 'default',
                        'configuration' => 'default',
                    ],
                ],
                'driver' => [
                    'attribute' => [
                        'class' => \Doctrine\ORM\Mapping\Driver\AttributeDriver::class,
                        'paths' => [
                            __DIR__ . '/Entity',
                        ],
                    ],
                    'default' => [
                        'class' => \Doctrine\Persistence\Mapping\Driver\MappingDriverChain::class,
                        'default_driver' => 'attribute',
                        'drivers' => [
                        ],
                    ],
                ],
            ]
        ]);

        $container->configure([
            'factories' => [
                'doctrine.entity_manager.default' => new \Roave\PsrContainerDoctrine\EntityManagerFactory("default"),
                'doctrine.connection.default' => new \Roave\PsrContainerDoctrine\ConnectionFactory("default"),
            ],
            'aliases' => [
                EntityManagerInterface::class => 'doctrine.entity_manager.default',
                Connection::class => 'doctrine.connection.default',
            ]
        ]);

        return $container;
    }
}
