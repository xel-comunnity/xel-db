<?php

use Swoole\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Xel\DB\QueryBuilder\Exception\QueryBuilderException;
use Xel\DB\QueryBuilder\QueryBuilderExecutor;
use Xel\DB\XgenConnector;
use Xel\DB\QueryBuilder\QueryBuilder;
require __DIR__."/../vendor/autoload.php";


$server = new Server('0.0.0.0', 9501, SWOOLE_BASE);
$server->set([
    'worker_num' => swoole_cpu_num(),
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

$server->on('workerStart', function (Server $server) {
    $db = (new XgenConnector([
        'dsn' => 'mysql:host=localhost;dbname=absensi',
        'username' => 'root',
        'password' => 'Todokana1ko!',
        'options' =>[
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    ],
        30,
        20
    ));

    $queryBuilderExecutor = new QueryBuilderExecutor($db);
    $queryBuilder = new Xel\DB\QueryBuilder\QueryBuilder($queryBuilderExecutor);

    $server->setting = [
        'QueryBuilder' => $queryBuilder,
        'XgenQueryAdapterInterface' => $db
    ];
});

$server->on('request', function (Request $request, Response $response) use ($server) {

    /** @var QueryBuilder $queryBuilder */
    $queryBuilder = $server->setting['QueryBuilder'];
    try {

                        $users = $queryBuilder
                            ->select(['id', 'name'])
                            ->from('data')
                            ->get();


        //                $users = $queryBuilder
        //                    ->insert('data', [
        //                        'name' => 'yogi'
        //                    ])->run();


        //        $users = $queryBuilder
        //            ->update('users', [
        //                'fullname' => 'xela',
        //                'email' => 'xel1@gmail.com',
        //                'password' => 'obasan',
        //                'date_of_birth' => "2012-08-06",
        //                'gender' => 'laki-laki',
        //                'contact' => '082228240098',
        //                'religion' => 'Kristen',
        //                'role_id' => 2
        //            ])->where('id','=', 4)
        //            ->run();


        //                $users = $queryBuilder
        //                    ->delete('data')
        //                    ->where('name','=', 'yogi')
        //                    ->run();
        //

        $response->header('Content-Type', 'application/json');
        $response->end(json_encode($users));

    } catch (QueryBuilderException $e) {
        $response->header('Content-Type', 'application/json');
        $response->status($e->getHttpCode(), $e->getHttpMessage());
        $response->end(json_encode("Error : ". $e->getMessage()));
    }

});

$server->start();
