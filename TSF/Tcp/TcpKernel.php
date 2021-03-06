<?php
/**
 * Created by PhpStorm.
 * User: LQB
 * Date: 2/28/17
 * Time: 11:27 AM
 */
namespace TSF\Tcp;

use TSF\Contract\Kernel\Tcp;
use TSF\Core\Log;
use TSF\Facade\App;
use TSF\Facade\Config;
use TSF\Tcp\TcpResponse;
use TSF\Facade\Tcp\Route;
use TSF\Tcp\TcpRequest;
use App\Framework\Facade\Connection;
use TSF\Core\Server;

class TcpKernel extends Tcp
{
    public function onWorkerStart($server, $workerId)
    {
        $route = new \TSF\Tcp\TcpRoute();
        \TSF\Core\Log::init(Config::facade()->get('app.log'));
        App::facade()->globalSingleton('TSF\Tcp\Route', $route);
        App::facade()->singleton("App\\Framework\\Facade\\Connection","App\\Framework\\Facade\\Connection");
        Route::facade()->loadConfig();
        Connection::setProtocol(Server::SERVER_TYPE_HTTP);

//        swoole_timer_tick(10000, function () use ($server) {
//            $conn_list = $server->connection_list(0, 10);
//            if($conn_list===false or count($conn_list) === 0)
//            {
//                echo "connection_list empty\n";
//            } else {
//                foreach($conn_list as $fd)
//                {
//                    $fdinfo = $server->connection_info($fd);
//                    //$server->send($fd, "broadcast");
//                    if ($fdinfo['server_port'] == 9501) {
//                        $server->push($fd, "broadcast");
//                    } else {
//                        $server->send($fd, "broadcast");
//                    }
//                }
//            }
//        });
    }

    public function onClose($server, $fd, $fromId)
    {
        // TODO: Implement onClose() method.
        echo "fd {$fd} 断开连接\n";
    }

    public function onManagerStart($server)
    {
        echo "TCP Service is started\n";
        // var_dump(get_included_files());
    }

    public function onReceive($server, $fd, $fromId, $data)
    {
        // TODO: Implement onMessage() method.
        $request = new TcpRequest($fd,$fromId,$data);
        $response = new TcpResponse($server, $fd);
        App::facade()->singleton('TSF\\Tcp\\TcpRequest', $request);
        App::facade()->singleton('TSF\\Tcp\\TcpResponse', $response);

        try {
            Route::facade()->dispatch($request);
        } catch (\Exception $e) {
            App::facade()->make('TSF\\Tcp\\TcpExceptionHandler')->render($request, $e);
        }
        $response->send();
        App::facade()->clearCurrentSingleton();
        echo $data;
    }

    public function onWorkerStop($server, $workerId)
    {
        parent::onWorkerStop($server, $workerId); // TODO: Change the autogenerated stub
        echo "worker Stop -" . $workerId . PHP_EOL;
    }

    public function onManagerStop($server)
    {
        parent::onManagerStop($server); // TODO: Change the autogenerated stub
        echo "manager stop";
    }

    public function onWorkerError($server, $workerId, $workerPid, $exitCode, $signal)
    {
        parent::onWorkerError($server, $workerId, $workerPid, $exitCode, $signal); // TODO: Change the autogenerated stub
        echo "WorkerError" . $workerId . PHP_EOL;
        Log::warning(" tcpManager_check_exit_status: worker#$workerId abnormal exit, status=$exitCode, signal=$signal");
    }

    public function onConnect($server, $fd, $fromId)
    {

        // var_dump($server->getLastError());
    }
}