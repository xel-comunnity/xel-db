<?php
declare(strict_types=1);
namespace Xel\DB;
use Exception;
use PDO;
use PDOException;
use Swoole\Coroutine\Channel;
use Swoole\Lock;
use function Swoole\Coroutine\go;
class XgenConnector
{
    private ?Channel $channel = null;
    private ?Lock $lock = null;

    /**
     * @param array<string|int, mixed> $config
     * @param bool $poolMode
     * @param int $pool
     */
    public function __construct
    (
        private readonly array $config,
        private readonly bool $poolMode,
        private readonly int $pool = 1,
    ){}

    public function initializationResource(int $pool = 10): void
    {
        go(function () use ($pool){
            $this->channel = new Channel($pool);
            $this->lock = new Lock(SWOOLE_SPINLOCK);
        });
    }

    /**
     * @throws Exception
     */
    public function initializeConnections(): void
    {
        if ($this->poolMode){
            try {
                for ($i = 0; $i < $this->pool; $i++) {
                    $conn = $this->createConnections();
                    $this->channel->push($conn);
                }
            } catch (PDOException $e) {
                throw new Exception($e->getMessage());
            }
        }else{
            $this->createConnections();
        }

    }

    /**
     * @throws Exception
     */
    private function createConnections(): PDO
    {
        if ($this->poolMode){
            try {
                return new PDO(
                    $this->config['dsn'],
                    $this->config['username'],
                    $this->config['password'],
                    $this->config['options']
                );
            } catch (PDOException $e){
                throw new Exception("Failed to create database connection: " . $e->getMessage(), $e->getCode(), $e);
            }
        }else{
            try {
                $pdo =  new PDO(
                    $this->config['dsn'],
                    $this->config['username'],
                    $this->config['password'],
                    $this->config['options']
                );

                $pdo->setAttribute(PDO::ATTR_PERSISTENT, true);

                return $pdo;

            } catch (PDOException $e){
                throw new Exception("Failed to create database connection: " . $e->getMessage(), $e->getCode(), $e);
            }
        }

    }

    /**
     * @throws Exception
     */
    public function getPersistentConnection(): PDO
    {
        return $this->createConnections();
    }
    /**
     * @throws Exception
     */
    public function getPoolConnection():PDO|false
    {
        while (true) {
            if ($this->lock->trylock()){
                try {
                    $conn = $this->channel->pop();
                    if ($conn === false) {
                        if (!$this->channel->isFull()) {
                            return $this->createConnections();
                        }
                    } elseif ($this->validateConnection($conn)) {
                        return $conn;
                    }
                } finally {
                    $this->lock->unlock();
                }
            }else{
                // Maximum connections reached, wait and try again
                return false;
            }

        }
    }

    /**
     * @throws Exception
     */
    public function releasePoolConnection(PDO $conn): void
    {
        if ($this->validateConnection($conn)){
            if ($this->lock->trylock()){
                try {
                    $this->channel->push($conn);
                }
                finally {
                    $this->lock->unlock();
                }
            }
        }
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