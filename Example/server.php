<?php


use Swoole\Constant;
use Swoole\Coroutine;
use Swoole\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Xel\DB\QueryBuilder\Exception\QueryBuilderException;
use Xel\DB\QueryBuilder\QueryDML;
use Xel\DB\XgenConnector;

require __DIR__."/../vendor/autoload.php";
$server = new Server('0.0.0.0', 9501, SWOOLE_BASE);
$server->set([
    'worker_num' =>swoole_cpu_num(),
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
        'dbname' => 'sample',
        'options' =>[
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    ],
        true, 1
    ));

    $db->initializeConnections();

    $queryBuilder = new QueryDML($db, true);

    $server->setting = [
        'QueryDML' => $queryBuilder,
        'db' => $db
    ];
});

$server->on('request', function (Request $request, Response $response) use ($server) {

    /** @var QueryDML $queryBuilder */
    $queryBuilder = $server->setting['QueryDML'];
    try {
//        $queryBuilder
//            ->insert(
//                "users",
//                [
//                    "name"=> "andi",
//                    "email"=> "andi.ut@gmail.com"
//                ])->run();
//
//        $user_id = $queryBuilder->lastId;
//        if ($user_id !== false) {
//            $queryBuilder->insert(
//                "user_role",
//                [
//                    "user_id"=> $user_id,
//                    "role_id"=> 2
//                ])->run();
//        }

        $result = $queryBuilder->select(["users.id","users.name", "role.role"])->from('users')
            ->leftJoin('user_role', 'users.id = user_role.user_id')
            ->leftJoin('role', ' user_role.role_id = role.id ')->get();

        $response->header('Content-Type', 'application/json');
        $response->end(json_encode($result));
    } catch (QueryBuilderException $e) {
        $response->header('Content-Type', 'application/json');
        $response->status($e->getHttpCode(), $e->getHttpMessage());
        $response->end(json_encode("Error : ". $e->getMessage()));
    }
});

$server->start();
