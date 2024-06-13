#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we start an HTTP/1 server to demonstrate some advanced usages, where we have:
 *     * multiple worker processes started to handle HTTP requests and sync/async tasks.
 *     * Two cron jobs. One runs every 61 seconds, and the other runs every 63 seconds.
 *     * HTTP endpoints to deploy sync/async tasks.
 *
 * You can run following curl commands to see different outputs:
 *   docker compose exec -t client bash -c "curl -i http://server:9502"
 *   docker compose exec -t client bash -c "curl -i http://server:9502?type=task"
 *   docker compose exec -t client bash -c "curl -i http://server:9502?type=taskwait"
 *   docker compose exec -t client bash -c "curl -i http://server:9502?type=taskWaitMulti"
 *   docker compose exec -t client bash -c "curl -i http://server:9502?type=taskCo"
 */

use Swoole\Constant;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Swoole\Database\PDOConfig;
use Swoole\Database\PDOPool;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Xel\DB\QueryBuilder\QueryDML;

require __DIR__."/../vendor/autoload.php";

$server = new Server('0.0.0.0', 9502);
$server->set(
    [
        Constant::OPTION_WORKER_NUM      => 2,
        Constant::OPTION_TASK_WORKER_NUM => swoole_cpu_num(),
    ]
);

$server->on('start', function (Server $server) {});
$server->on('workerStart', function (Server $server, int $workerId) {
    $db2 = new PDOPool((new PDOConfig())
        ->withDriver('mysql')
        ->withCharset('utf8mb4')
        ->withHost('localhost')
        ->withUsername('root')
        ->withPassword('Todokana1ko!')
        ->withDbname('absensi')
        ->withOptions([
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]),
        70);
//    // Store connection pool in coroutine context
    $queryBuilder = new QueryDML($db2, true);
    $server->setting = [
        'QueryDML' => $queryBuilder,
        'db2' => $db2
    ];
});

$server->on(
    'request',
    function (Request $request, Response $response) use ($server) {
        $type = $request->get['type'] ?? '';
        switch ($type) {
            case 'task':



                $server->task((object) ['type' => 'projects']);
                $response->end($type . PHP_EOL);
                break;
            case 'taskwait':
                // To deploy an asynchronous task, and wait until it finishes.
                $server->taskwait(['type' => 'taskwait']);
                $response->end($type . PHP_EOL);
                break;
            case 'taskWaitMulti':
                // To deploy multiple asynchronous tasks, and wait until they finish. (legacy implementation)
                $server->taskWaitMulti(['taskWaitMulti #0', 'taskWaitMulti #1', 'taskWaitMulti #2']);
                $response->end($type . PHP_EOL);
                break;
            case 'taskCo':
                // To deploy multiple asynchronous tasks, and wait until they finish.
                $result = $server->taskCo(
                    [
                        'taskCo #0',
                        ['type' => 'taskCo #1'],
                        (object) ['type' => 'taskCo #2'],
                    ]
                );
                $response->end(print_r($result, true));
                break;
            default:
                // To deploy an asynchronous task, and process the response through a callback function.
                $server->task('taskCallback', -1, function (Server $server, int $taskId, $data) use ($response) {
                    $response->end($data . PHP_EOL);
                });
                break;
        }
    }
);
$server->on(
    'task',
    function (Server $server, int $taskId, int $reactorId, $data) {
        echo 'Task received with incoming data (serialized already): ', serialize($data), PHP_EOL;
        return $data;
    }
);
$server->on(
    'finish',
    function (Server $server, int $taskId, $data) {
        echo 'Task returned with data (serialized already): ', serialize($data), PHP_EOL;

        return $data;
    }
);

$server->start();