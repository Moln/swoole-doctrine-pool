<?php

declare(strict_types = 1);

namespace MolnTest\SwooleDoctrinePool;

use Moln\SwooleDoctrinePool\DoctrineOrmPool;
use PHPUnit\Framework\TestCase;
use Swoole\Event;
use Swoole\Runtime;

class DoctrineOrmPoolTest extends TestCase
{
    use InitContainerTrait;

    public function testGetPut(): void
    {
        $container = $this->getContainer();
        /** @var DoctrineOrmPool $pool */
        $pool = $container->get(DoctrineOrmPool::class);
        Runtime::enableCoroutine();
        $result = [];
        for ($i = 0; $i < 10; $i++) {
            go(function () use ($pool, &$result): void {
                $em = $pool->get();
                $result[] = $em->getConnection()->executeQuery('SELECT now() as "now", sleep(1)')->fetchOne();
                $pool->put($em);
            });
        }

        Event::wait();
        self::assertCount(10, $result);
        $result = array_unique($result);
        self::assertCount(1, $result);
    }
}
