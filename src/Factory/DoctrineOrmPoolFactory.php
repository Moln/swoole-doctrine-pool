<?php

declare(strict_types = 1);

namespace Moln\SwooleDoctrinePool\Factory;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Moln\SwooleDoctrinePool\DoctrineOrmPool;
use Psr\Container\ContainerInterface;

class DoctrineOrmPoolFactory
{
    use ConnectionTrait;

    public function __invoke(ContainerInterface $container): DoctrineOrmPool
    {
        $poolConfig = $this->getConfig($container);

        /** @var EntityManagerInterface $em */
        $em = $container->get($poolConfig['entity_manager'] ?? EntityManagerInterface::class);
        $em->clear();
        $em->getConnection()->close();
        unset($poolConfig['entity_manager']);

        return new DoctrineOrmPool(
            fn() => new EntityManager(
                clone $em->getConnection(),
                $em->getConfiguration(),
                $em->getEventManager(),
            ),
            ...$poolConfig
        );
    }
}
