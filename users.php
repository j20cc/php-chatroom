<?php
require_once __DIR__ . '/functions.php';
/*
  用户列表
*/
//实例化redis
$redis = new Redis();
//连接
$redis->connect('127.0.0.1', 6379);

dump($redis->hgetall('user1'));