<?php
$server = new swoole_websocket_server("0.0.0.0", 9501);
$server->set(array(
    'worker_num' => 4,
    'daemonize' => true,
    'backlog' => 128,
));
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

$server->on('open', function (swoole_websocket_server $server, $request) use ($redis) {
    // 返回用户列表
    foreach ($server->connections as $fd) {
        $connected_fds = $redis->lrange('chat:users_list', 0, -1);
        $users = [];
        foreach ($connected_fds as $key) {
            $users[] = $redis->hgetall('chat:users_info:' . $key);
        }
        $ret['type'] = 'users_list';
        $ret['data'] = $users;
        $ret['time'] = date('Y-m-d H:i:s');
        $server->push($fd, json_encode($ret));
    }
    echo "server: handshake success with fd{$request->fd}\n";
});

$server->on('message', function (swoole_websocket_server $server, $frame) use ($redis) {
    $data = json_decode($frame->data, true);
    $route = $data['route'];
    $params = $data['params'];
    $now = date('Y-m-d H:i:s');
    // 取名
    if ($route == 'makeName') {
        // fd存入用户列表
        $redis->lpush('chat:users_list', $frame->fd);
        $key = 'chat:users_info:' . $frame->fd;
        $info = ['fd' => $frame->fd, 'name' => $params['name'], 'avatar_id' => mt_rand(1, 10), 'time' => $now];
        $redis->hMSet($key, $info);
        // 返回用户信息
        foreach ($server->connections as $fd) {
            if ($fd !== $frame->fd) {
                $ret['type'] = 'new_user';
                $ret['data'] = $redis->hgetall($key);
                $ret['time'] = $now;
                $server->push($fd, json_encode($ret));
            }
        }
        // 返回给当前用户
        $ret['type'] = 'myself';
        $ret['data'] = $info;
        $ret['time'] = $now;
        $server->push($frame->fd, json_encode($ret));
    }
    // 聊天
    if ($route == 'chat') {
        $params['time'] = date('H:i');
        $ret['type'] = 'chat';
        $ret['data'] = $params;
        foreach ($server->connections as $fd) {
            $server->push($fd, json_encode($ret));
        }
    }
});

$server->on('close', function ($server, $fd) use ($redis) {
    $redis->lrem('chat:users_list', $fd, 0);
    $redis->delete('chat:users_info:' . $fd);
    echo "client {$fd} closed\n";
});

$server->start();
