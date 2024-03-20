<?php
declare(strict_types=1);
namespace Xel\DB;
use Exception;
use PDO;
use PDOException;
use Swoole\Atomic;
use Swoole\Coroutine\Channel;
/**
 * @phpstan-ignore-next-line
 */
use function Swoole\Coroutine\go;
class XgenConnector
{
    private Channel $channel;
    private Atomic $currentConnections;

    /**
     * @param array<string|int, mixed> $config
     * @param int $pool
     * @param int $maxConnections
     */
    public function __construct
    (
        private readonly array $config,
        private readonly int $pool,
        private readonly int $maxConnections
    ){
        /**
         * @phpstan-ignore-next-line
         */
        go(function ()  {
            $this->channel = new Channel($this->pool);
            $this->currentConnections = new Atomic();
            $this->initializeConnections();
        });
    }

    private function initializeConnections(): void
    {
        for ($i = 0; $i < $this->pool; $i++) {
            $conn = new PDO(
                $this->config['dsn'],
                $this->config['username'],
                $this->config['password'],
                $this->config['options']
            );

            $this->channel->push($conn);
        }
    }

    /**
     * @throws Exception
     */
    public function getConnection():PDO|false
    {
        $conn = $this->channel->pop();
        if ($conn === false) {
            // No connections available in the pool
            if ($this->currentConnections->get()  > $this->maxConnections) {
                throw new Exception("Cannot create more connections: ");
            }

            // ? Create new connections up to the maximum allowed per worker
            $this->initializeConnections();
            $conn = $this->channel->pop();

        }

        // Validate the connection
        if (!$this->validateConnection($conn)) {
            $conn = null;
            try {
                $conn = new PDO(
                    $this->config['dsn'],
                    $this->config['username'],
                    $this->config['password'],
                    $this->config['options']
                );
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                throw new Exception("Failed to create database connection: " , $e->getCode());
            }
        }
        if ($conn !== null) {
            $this->currentConnections->add();
        }

        return $conn;
    }

    public function releaseConnection(PDO $conn): void
    {
        $this->channel->push($conn);
        $this->currentConnections->sub();
    }

    /**
     * @throws Exception
     */
    private function validateConnection(PDO $conn): bool
    {
        try {
            /**
             * @phpstan-ignore-next-line
             */
            $conn->query("SELECT 1")->fetchColumn();
            return true;
        } catch (PDOException $e) {
            // XgenQueryAdapterInterface is invalid, log the error or handle it as per your application's requirements
            throw new Exception("Failed to validate database connection: " , $e->getCode());
        }
    }
}