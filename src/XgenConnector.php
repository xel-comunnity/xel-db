<?php
declare(strict_types=1);
namespace Xel\DB;
use Exception;
use PDO;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;

class XgenConnector
{
    private Channel $channel;
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
    ){}

    /**
     * @throws Exception
     */
    public function initializeConnections(): void
    {
        if ($this->poolMode){
            $this->channel = new Channel($this->pool);
            for ($i = 0; $i < $this->pool; $i++) {
                Coroutine::create(function (){
                    $conn = PDOInstance::create($this->config);
                    $this->channel->push($conn);
                });
            }
        }else{
            $conn = PDOInstance::create($this->config);
            $conn->setAttribute(PDO::ATTR_PERSISTENT, true);
            $this->persistence = $conn;
        }
//        var_dump($this->channel->stats());
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
            $conn = PDOInstance::create($this->config);
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
            $conn = PDOInstance::create($this->config);
        }
        $this->channel->push($conn);
    }
}