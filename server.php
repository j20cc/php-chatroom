<?php

class WebsocketTest {
    public $server;
    public $redis;

    public function __construct() {
        $this->server = new swoole_websocket_server("0.0.0.0", 9501);
        $this->redis = new redis();
        
        $this->server->on('open', function (swoole_websocket_server $server, $request) {
            echo "server: handshake success with fd{$request->fd}\n";
        });
        $this->server->on('message', function (swoole_websocket_server $server, $frame) {
            echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";
            $server->push($frame->fd, "this is server");
        });
        $this->server->on('close', function ($ser, $fd) {
            echo "client {$fd} closed\n";
        });
        $this->server->start();
    }
}
new WebsocketTest();