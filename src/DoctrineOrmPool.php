<?php

declare(strict_types = 1);

namespace Moln\SwooleDoctrinePool;

use Doctrine\DBAL\Exception\ConnectionLost;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Swoole\ConnectionPool;

/**
 */
class DoctrineOrmPool extends ConnectionPool
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

    public function get(float $timeout = -1): EntityManagerInterface
    {
        /** @var EntityManagerInterface $em */
        $em = parent::get($timeout);

        $key = spl_object_hash($em);
        // 30秒内不重复检测
        if (($this->time[$key] ?? 0) > time() - $this->retryPingConnection) {
            return $em;
        }
        $this->time[$key] = 0;
        $keys = array_flip(array_keys($this->time));
        $index = $keys[$key];

        while (true) {
            $conn = $em->getConnection();
            try {
                $this->logger->info("[DoctrineOrmPool][$index] Ping");
                $conn->executeQuery($conn->getDatabasePlatform()->getDummySelectSQL())->free();
                $this->time[$key] = time();
                return $em;
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
     * @param EntityManagerInterface $connection
     * @inheritdoc
     */
    public function put($connection): void
    {
        $key = spl_object_hash($connection);
        $keys = array_flip(array_keys($this->time));
        $index = $keys[$key];
        $connection->clear();
        $this->logger->debug("[DoctrineOrmPool][$index] release.");
        parent::put($connection);
    }
}
