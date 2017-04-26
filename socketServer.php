<?php 
/*改成异步以后echo已经没用了。输出不了*/
$server = new swoole_websocket_server("0.0.0.0", 9502,SWOOLE_BASE);
//设置相关属性
$server->set(array(
    'ractor_num'    =>    1,    //主进程中线程数量
    'worker_num'    =>    4,    //工作进程数量
    'daemonize'        =>    true,  //是否守护进程
    'log_file'        =>    '/root/swoole.log',    //日志存储路径
    'dispatch_mode' =>     2,     //1平均分配，2按FD取摸固定分配，3抢占式分配，默认为取模(dispatch=2)'
    'task_worker_num'=> 2,
));
$server->on('open', function (swoole_websocket_server $server, $request) {
    $c = count($server->connections);
    $data = json_encode(['data'=>'welcome!','count'=>$c]);
    $server->push($request->fd, $data);
});
$server->on('task', function ($server, $worker_id, $task_id, $data) {
	 $server->finish($data);
 });
$server->on('finish', function ($server, $task_id, $data) {
  //echo 'messager ID :'.$frame->fd;
$c = count($server->connections);
$data = json_encode(['data'=>$data,'count'=>$c]);
 foreach($server->connections as $fd) { 
    $server->push($fd, $data); 
    } 
});
$server->on('message', function (swoole_websocket_server $server, $frame) {
    //echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";
    //$frame->fd 是客户端id，$frame->data是客户端发送的数据
	$server->task($frame->data);
});

$server->on('close', function ($server, $fd) {
     echo "client {$fd} closed\n";
});
$server->start();
?>
