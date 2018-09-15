<?php
$server = new swoole_websocket_server("0.0.0.0", 9501);
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

function makeName($data)
{
    var_dump($data['params']);
}

$server->on('open', function (swoole_websocket_server $server, $request) {
    echo "server: handshake success with fd{$request->fd}\n";
});

$server->on('message', function (swoole_websocket_server $server, $frame) {
    $data = json_decode($frame->data, true);
    $route = $data['route'];
    $params = $data['params'];
    var_dump($data);
    if (function_exists($route)) {
        call_user_func($route, compact('server', 'frame', 'params'));
    } else {
        $server->push($frame->fd, "no such route");
    }
});

$server->on('close', function ($server, $fd) {
    echo "client {$fd} closed\n";
});

$server->start();
