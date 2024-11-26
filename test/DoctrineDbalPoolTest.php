<?php

declare(strict_types = 1);

namespace MolnTest\SwooleDoctrinePool;

use Moln\SwooleDoctrinePool\DoctrineDbalPool;
use PHPUnit\Framework\TestCase;
use Swoole\Event;
use Swoole\Runtime;

class DoctrineDbalPoolTest extends TestCase
{
    use InitContainerTrait;

    public function testGetPut(): void
    {
        $container = $this->getContainer();
        /** @var DoctrineDbalPool $pool */
        $pool = $container->get(DoctrineDbalPool::class);
        Runtime::enableCoroutine();
        $result = [];
        for ($i = 0; $i < 10; $i++) {
            go(function () use ($pool, &$result): void {
                $conn = $pool->get();
                $result[] = $conn->executeQuery('SELECT now() as "now", sleep(1)')->fetchOne();
                $pool->put($conn);
            });
        }

        Event::wait();
        self::assertCount(10, $result);
        $result = array_unique($result);
        self::assertCount(1, $result);
    }
}
