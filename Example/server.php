<?php


use Swoole\Constant;
use Swoole\Coroutine;
use Swoole\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Xel\DB\QueryBuilder\Exception\QueryBuilderException;
use Xel\DB\QueryBuilder\QueryBuilder;
use Xel\DB\XgenConnector;

require __DIR__."/../vendor/autoload.php";




$server = new Server('0.0.0.0', 9501, SWOOLE_PROCESS);
$server->set([
    'worker_num' => 35,
    'log_file' => '/dev/null',
    'dispatch_mode' => 1,
    'open_tcp_nodelay'      => true,
    'reload_async'          => true,
    'max_wait_time'         => 60,
    'enable_reuse_port'     => true,
    'enable_coroutine'      => true,
    'http_compression'      => true,
    'enable_static_handler' => false,
    'buffer_output_size'    => swoole_cpu_num() * 1024 * 1024,
]);

Coroutine::set([Constant::OPTION_HOOK_FLAGS => SWOOLE_HOOK_TCP]);
$server->on('workerStart', function (Server $server) {
    $db = (new XgenConnector([
        'driver' => 'mysql',
        'host' => 'localhost',
        'charset' => 'utf8mb4',
        'username' => 'root',
        'password' => 'Todokana1ko!',
        'dbname' => 'absensi',
        'options' =>[
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    ],
        true, swoole_cpu_num()
    ));

    $db->initializeConnections();

    $queryBuilder = new QueryBuilder($db, true);

    $server->setting = [
        'QueryBuilder' => $queryBuilder,
        'db' => $db
    ];
});

$server->on('request', function (Request $request, Response $response) use ($server) {

//    /** @var QueryBuilder $queryBuilder */
//    $queryBuilder = $server->setting['QueryBuilder'];
    /** @var XgenConnector $queryBuilder */
    $queryBuilder = $server->setting['db'];

    try {
        $data = $queryBuilder->getPoolConnection();
        $users = $data->query('SELECT id, fullname FROM users');
        $users->execute();
        $result = $users->fetchAll(PDO::FETCH_ASSOC);

        $queryBuilder->releasePoolConnection($data);
//        $result = $queryBuilder
//            ->select(['id', 'fullname'])
//            ->from('users')
//            ->get();

        $response->header('Content-Type', 'application/json');
        $response->end(json_encode($result));
    } catch (QueryBuilderException $e) {
        $response->header('Content-Type', 'application/json');
        $response->status($e->getHttpCode(), $e->getHttpMessage());
        $response->end(json_encode("Error : ". $e->getMessage()));
    }

});

$server->start();
