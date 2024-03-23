<?php
declare(strict_types=1);
namespace Xel\DB;
use Exception;
use PDO;
use PDOException;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use function _PHPStan_8c645376c\React\Async\coroutine;

class XgenConnector
{
    private ?Channel $channel = null;
    private ?PDO $persistence = null;
    /**
     * @param array<string|int, mixed> $config
     * @param bool $poolMode
     * @param int $pool
     */
    public function __construct
    (
        private readonly array $config,
        private readonly bool $poolMode = false,
        private readonly int $pool = 1,
    ){
        $this->channel = new Channel($this->pool);
    }

    /**
     * @throws Exception
     */
    public function initializeConnections(): void
    {
        if ($this->poolMode){
            for ($i = 0; $i < $this->pool; $i++) {
                Coroutine::create(function (){
                    $conn = $this->createConnections();
                    $this->channel->push($conn);
                });
            }

        }else{
            $conn = $this->createConnections();
            $this->persistence = $conn;
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
        return $this->persistence;
    }
    /**
     * @throws Exception
     */
    public function getPoolConnection():PDO|false
    {
        $conn = $this->channel->pop(-1);
        if (!$conn){
            $conn = null;
            $conn = $this->createConnections();
            $this->channel->push($conn);
            return $conn;
        }

        return $conn;
    }


    /**
     * @throws Exception
     */
    public function releasePoolConnection(?PDO $conn = null): void
    {
        if (!$conn){
            $conn = $this->createConnections();
        }
        $this->channel->push($conn);
    }
}