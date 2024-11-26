<?php

declare(strict_types = 1);

namespace Moln\SwooleDoctrinePool;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\ConnectionLost;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Swoole\ConnectionPool;

/**
 */
class DoctrineDbalPool extends ConnectionPool
{

    private array $time = [];
    private LoggerInterface $logger;

    public function __construct(
        callable $constructor,
        int $size = self::DEFAULT_SIZE,
        ?LoggerInterface $logger = null,
        private int $retryPingConnection = 30,
        private int $retryOnLostConnection = 2,
    ) {
        $this->logger = $logger ?? new NullLogger();
        parent::__construct($constructor, $size);
    }

    public function get(float $timeout = -1): Connection
    {
        /** @var Connection $conn */
        $conn = parent::get($timeout);

        $key = spl_object_hash($conn);
        // 30秒内不重复检测
        if (! isset($this->time[$key])) {
            $this->time[$key] = 0;
        }
        if ($this->time[$key] > time() - $this->retryPingConnection) {
            return $conn;
        }
        $keys = array_flip(array_keys($this->time));
        $index = $keys[$key];

        while (true) {
            try {
                $this->logger->info("[DoctrineDbalPool][$index] Ping");
                $conn->executeQuery($conn->getDatabasePlatform()->getDummySelectSQL());
                $this->time[$key] = time();
                return $conn;
            } catch (ConnectionLost $e) {
                $this->logger->warning(sprintf(
                    "[DoctrineDbalPool][$index] Lost connection, retry after %s secs, error: %s",
                    $this->retryOnLostConnection,
                    $e->getMessage(),
                ));
                $conn->close();
                sleep($this->retryOnLostConnection);
            }
        }
    }

    /**
     * @param Connection $connection
     * @inheritdoc
     */
    public function put($connection): void
    {
        $key = spl_object_hash($connection);
        if (! isset($this->time[$key])) {
            $this->time[$key] = 0;
        }
        $keys = array_flip(array_keys($this->time));
        $index = $keys[$key];
        $this->logger->debug("[DoctrineDbalPool][$index] release.");
        parent::put($connection);
    }
}
