<?php
/*
namespace app\websocket\controller;*/

class WebSocketServer
{
    protected $serv = null;       //Swoole\Server对象
    protected $host = '0.0.0.0'; //监听对应外网的IP 0.0.0.0监听所有ip
    protected $port = 9502;      //监听端口号

    public function __construct()
    {
        //创建websocket服务器对象，监听0.0.0.0:9604端口
        $this->serv = new \Swoole\Websocket\Server($this->host, $this->port);

        //设置参数
        //如果业务代码是全异步 IO 的，worker_num设置为 CPU 核数的 1-4 倍最合理
        //如果业务代码为同步 IO，worker_num需要根据请求响应时间和系统负载来调整，例如：100-500
        //假设每个进程占用 40M 内存，100 个进程就需要占用 4G 内存
        $this->serv->set(array(
            'worker_num' => 4,         //设置启动的worker进程数。【默认值：CPU 核数】
            'max_request' => 10000,    //设置每个worker进程的最大任务数。【默认值：0 即不会退出进程】
            'daemonize' => 0,          //开启守护进程化【默认值：0】
        ));

        //监听WebSocket连接打开事件
        $this->serv->on('open', function ($serv, $req) {
            echo "connection open: {$req->fd}" . PHP_EOL;
        });

        //监听WebSocket消息事件
        //客户端向服务器端发送信息时，服务器端触发 onMessage 事件回调
        //服务器端可以调用 $server->push() 向某个客户端（使用 $fd 标识符）发送消息，长度最大不得超过 2M
        $this->serv->on('message', function ($serv, $frame) {
            echo "Message: {$frame->data}". PHP_EOL;
            $this->serv->push($frame->fd, "server: {$frame->data}");
        });

        //监听WebSocket连接关闭事件
        $this->serv->on('close', function ($serv, $fd) {
            echo "connection close: {$fd}". PHP_EOL;
        });

        //启动服务
        $this->serv->start();
    }
}

$webSocketServer = new WebSocketServer();
