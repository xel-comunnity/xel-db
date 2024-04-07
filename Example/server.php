<?php


use Swoole\Constant;
use Swoole\Coroutine;
use Swoole\Database\PDOConfig;
use Swoole\Database\PDOPool;
use Swoole\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Xel\DB\QueryBuilder\Exception\QueryBuilderException;
use Xel\DB\QueryBuilder\QueryDML;

require __DIR__."/../vendor/autoload.php";
$server = new Server('0.0.0.0', 9501, SWOOLE_PROCESS);
$server->set([
    'worker_num' =>35,
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
$server->on('workerStart', function (Server $server){
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

$server->on('request', function (Request $request, Response $response) use ($server) {

    /** @var QueryDML $queryBuilder */
    $queryBuilder = $server->setting['QueryDML'];
//    /** @var PDOPool $queryBuilder */
//    $queryBuilder = $server->setting['db2'];



    try {

//        $conn = $queryBuilder->get();
//        $stmt = $conn->query('SELECT * FROM projects');
//        $stmt->execute();
//        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
//        $queryBuilder->put($conn);
        $result = $queryBuilder->select()->from('projects')->get();
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

//        $result = $queryBuilder->select(["users.id","users.name", "role.role"])->from('users')
//            ->leftJoin('user_role', 'users.id = user_role.user_id')
//            ->leftJoin('role', ' user_role.role_id = role.id ')->get();

        $response->header('Content-Type', 'application/json');
        $response->end(json_encode($result));
    } catch (QueryBuilderException $e) {
        $response->header('Content-Type', 'application/json');
        $response->status($e->getHttpCode(), $e->getHttpMessage());
        $response->end(json_encode("Error : ". $e->getMessage()));
    }
});

$server->start();
