<?php
namespace App\Api\Service\Node;

use EasySwoole\EasySwoole\ServerManager;
use EasySwoole\Component\CoroutineSingleTon;

class ServerService
{
    use CoroutineSingleTon;
    //account=5xR4KC8mNE7iNY7zKHB6fjGkjn2tCy&pwd=sBT2fmf6FsT4PYnmYBD75KcfcFkCyGfMncYWBAF4&msg=游戏维护中，敬请期待
    public function exitGame(array $param):int
    {
        if(!array_key_exists('account',$param) || !$param['account'] ) return 402;
        if(!array_key_exists('pwd',$param) || !$param['pwd'] ) return 403;
        if(!array_key_exists('msg',$param) || !$param['msg'] ) return 405;

        if($param['account'] !== '5xR4KC8mNE7iNY7zKHB6fjGkjn2tCy' || $param['pwd'] !== 'sBT2fmf6FsT4PYnmYBD75KcfcFkCyGfMncYWBAF4') return 406;

        $notice = json_encode([ 'code' => SUCCESS,'method' => 'exit_game','data' => [ 'msg' => $param['msg'] ] ],JSON_UNESCAPED_UNICODE|JSON_FORCE_OBJECT);

        $wssServer = ServerManager::getInstance()->getSwooleServer();

        foreach ($wssServer->connections as $fd)
        {
            if(!$wssServer->isEstablished($fd)) continue;
            $wssServer->push($fd,$notice);
        }

        //以免网络延迟
        \Swoole\Coroutine::sleep(1);

        foreach ($wssServer->connections as $fd)
        {
            if(!$wssServer->isEstablished($fd)) continue;
            $wssServer->close($fd);
        }
        
        return 200;
    }

}