Swoole doctrine pool
======================

Installation
--------------


```shell
composer require moln/swoole-doctrine-pool
```

Usage
--------


```php
use Moln\SwooleDoctrinePool\DoctrineDbalPool;

class DemoController {
   public function __construct(private DoctrineDbalPool $pool) {}
   
   public function handle() {
        $result = [];
        $dbPool = $this->pool;
        for ($i = 0; $i < 10; $i++) {
            go(function () use ($dbPool, &$result) {
                $conn = $dbPool->get();
                $result[] = $conn->executeQuery('SELECT now() as "now", sleep(1)')->fetchOne();
                $dbPool->put($conn);
            });
        }
        
        return $result;
   }
}
```