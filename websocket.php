<?php
$serv = new swoole_websocket_server("0.0.0.0", 9501);

$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

$serv->on('open', function ($serv, $request) use ($redis) {
	//获取用户列表
});

$serv->on('message', function ($serv, $request) use ($redis) {
	//收到取名消息,分配头像,加入用户列表
	$data = json_decode($request->data, true);
	$users = json_decode($redis->get('user_list'), true); //获得原来的列表
	if ($data['type'] == 1) {
		$users[$request->fd]['name'] = $data['name'];
		$users[$request->fd]['avatar'] = rand(1, 10);

		//新的用户列表，群发json
		$redis->set('user_list', json_encode($users));
		foreach ($serv->connections as $fd) {
			$msg['type'] = 1; //用户列表
			$msg['msg'] = '新用户上线了';
			$msg['list'] = json_decode($redis->get('user_list'), true);
			$serv->push($fd, json_encode($msg));
		}
	}
	//收到聊天消息,群发
	if ($data['type'] == 2) {
		foreach ($serv->connections as $fd) {
			$msg['type'] = 2; //群聊内容
			$msg['name'] = $users[$request->fd]['name'];
			$msg['avatar'] = $users[$request->fd]['avatar'];
			$msg['msg'] = $data['msg'];
			$msg['time'] = date('H:i', time());
			$serv->push($fd, json_encode($msg));
		}
	}
});

$serv->on('close', function ($serv, $fd) use ($redis) {
	//从用户列表中删除
	$users = json_decode($redis->get('user_list'), true);
	unset($users[$fd]);
	foreach ($users as $key => $value) {
		$msg['type'] = 1; //用户列表
		$msg['list'] = $users;
		$msg['msg'] = '有用户退出了';
		$serv->push($key, json_encode($msg));
	}
	$redis->set('user_list', json_encode($users));
	var_dump(json_decode($redis->get('user_list'), true));
});

$serv->start();