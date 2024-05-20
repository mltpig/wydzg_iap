<?php 
namespace App\Api;
use EasySwoole\Component\TableManager;
use App\Api\Service\Node\NodeService;
use App\Api\Service\Node\ServerService;
use App\Api\Service\PlayerService;
use App\Api\Service\Pay\PaySuccessCallBackService;
use App\Api\Service\Pay\Wx\MessageService as WxMessageService;
use App\Api\Table\Table;
use App\Api\Table\ApiStatus;
use EasySwoole\EasySwoole\ServerManager;

class WebSocketEvent
{
    public static function onRequest(\swoole_http_request $request, \swoole_http_response $response)
    {   

        $pathInfo = $request->server['path_info'];
        if ($pathInfo == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico') 
        {
            $response->end();
            return;
        }

        $reMsg = '200';

        $get = $request->get ? $request->get : [];

        switch ($pathInfo) 
        {
            case '/'.CHANNEL.'/clearTable':
                Table::getInstance()->reset();
                break;
            case '/'.CHANNEL.'/openServer':
                $reMsg = NodeService::getInstance()->openNewServer($get);
                break;
            case '/'.CHANNEL.'/exitGame':
                $reMsg = ServerService::getInstance()->exitGame($get);
                break;
            case '/'.CHANNEL.'/payNotify':
                $method = $request->server['request_method'];
                if($method === 'GET')
                {
                    $reMsg = PaySuccessCallBackService::getInstance()->check($get);
                }elseif($method === 'POST'){
                    $reMsg = PaySuccessCallBackService::getInstance()->payCallBack( $request );
                }
                break;
            case '/'.CHANNEL.'/message':
                $method = $request->server['request_method'];
                if($method === 'GET')
                {
                    $reMsg = WxMessageService::getInstance()->firstCheck($get);
                }elseif($method === 'POST'){
                    $reMsg = WxMessageService::getInstance()->run( $request );
                }
                break;
        }

        $response->write($reMsg);
    }

    public static function onOpen(\swoole_websocket_server $server, \swoole_http_request $request)
    {   
        //统计wss 连接数
        $get = $request->get;
        $fd   = $request->fd;

        if(!array_key_exists('code',$get) || !$get['code'] || !NodeService::getInstance()->isLogin($get['code'])) return $server->close($fd);
        if(!array_key_exists('scene',$get) || !$get['scene'] || strlen($get['scene']) != 4 ) return $server->close($fd);
        if(!array_key_exists('node',$get) || !NodeService::getInstance()->existsNode($get['node']) ) return $server->close($fd);
        
        $site = $get['node'];
        $uid  = $get['code'];

        $old = TableManager::getInstance()->get(TABLE_UID_FD)->get($uid);
        if($old)
        {
            $remoteLogin = $old['scene'] === $get['scene'] ? ['code' => 5 ,'msg' => '网络不稳定'] : ['code' => REMOTE_LOGIN,'msg' => '该账号已在其他设备登录'];
            ServerManager::getInstance()->getSwooleServer()->push($old['fd'],json_encode($remoteLogin,JSON_UNESCAPED_UNICODE|JSON_FORCE_OBJECT));
            ServerManager::getInstance()->getSwooleServer()->close($old['fd']);
            \Swoole\Coroutine::sleep(0.02);
        }

        $playerSer = new PlayerService($uid,$site,null);
        $isExists  = is_null($playerSer->getData('last_time'));
        if( $isExists ) $playerSer->signup();

        $server->push($request->fd,json_encode(['code'=> SUCCESS,'method'=>'login','data'=> $playerSer->getLoginData($isExists)],JSON_UNESCAPED_UNICODE|JSON_FORCE_OBJECT));
        
        NodeService::getInstance()->setLastLoginNode($uid,$site);

        //放在最后面，等待colse回调执行完毕
        TableManager::getInstance()->get(TABLE_FD_UID)->set($fd,[ 'uid' => $uid ,'site' => $site]);
        TableManager::getInstance()->get(TABLE_UID_FD)->set($uid,[ 'fd' => $fd,'scene' => $get['scene'] ]);

    }

    public static function onClose(\swoole_server $server, int $fd, int $reactorId)
    {
        if(!$server->isEstablished($fd)) return;
        $playerInfo = TableManager::getInstance()->get(TABLE_FD_UID)->get($fd);
        if(!$playerInfo) return;
        TableManager::getInstance()->get(TABLE_FD_UID)->del($fd);
        TableManager::getInstance()->get(TABLE_UID_FD)->del($playerInfo['uid']);
    }

    // public static function onPipeMessage(Swoole\Server $server, int $src_worker_id, mixed $message)
    // {
        
    // }

}