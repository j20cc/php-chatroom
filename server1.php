<?php
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

$fd = $_GET['fd'];
// $redis->lpush('chat:users_list', $fd);
// $redis->lrem('chat:users', $fd, 0);
var_dump($redis->lrange('chat:users_list', 0, -1));
exit(123);
echo "<pre>";
var_dump($redis->hgetall('chat:users_info:' . $fd));
echo "</pre>";
