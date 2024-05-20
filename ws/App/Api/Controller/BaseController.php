<?php
namespace App\Api\Controller;

use App\Api\Service\Module\LogService;
use App\Api\Service\PlayerService;
use App\Api\Service\RedPointService;
use EasySwoole\Component\TableManager;
use EasySwoole\EasySwoole\ServerManager;
use EasySwoole\Socket\AbstractInterface\Controller;
use App\Api\Utils\Lock;

class BaseController extends Controller
{
    public $param;
    public $method;
    public $player;

    public function sendMsg($arg,int $code = ERROR)
    { 
        $tag  = is_array($arg) ? 'data' : 'msg' ;
        $code = is_array($arg) ? SUCCESS  : $code;
        
        $this->response()->setMessage(json_encode([ 
                'code' => $code ,
                'method' => $this->method, 
                $tag => $arg 
            ],JSON_UNESCAPED_UNICODE|JSON_FORCE_OBJECT)
        );
    }

    protected function onRequest(?string $actionName):bool
    {	
        parent::onRequest($actionName);

        $fd = $this->caller()->getClient()->getFd();
        $tmp  = $this->param  = $this->caller()->getArgs();
        $this->method = $this->param['method'];

        $playerInfo = TableManager::getInstance()->get(TABLE_FD_UID)->get($fd);
        if(!$playerInfo)
        {   
            $msg = json_encode([ 'code' => RELOGIN ,'method' => $this->method, 'msg' => '请重新登录' ],JSON_UNESCAPED_UNICODE|JSON_FORCE_OBJECT);
            $this->responseImmediately($msg);
            ServerManager::getInstance()->getSwooleServer()->close($fd);
            return false;
        }

        $this->param['uid']  = $playerInfo['uid'];
        $this->param['site'] = $playerInfo['site'];

        if(Lock::exists($playerInfo['uid']))
        {
            
            $msg = json_encode([ 'code' => MANY_REQUEST ,'method' => $this->method, 'msg' => $tmp ],JSON_UNESCAPED_UNICODE|JSON_FORCE_OBJECT);
            $this->responseImmediately($msg);
            return false;
        }

        
        $this->player  = new PlayerService($playerInfo['uid'],$playerInfo['site'],$fd);
        
        if(is_null($this->player->getData('last_time')))
        {
            $msg = json_encode([ 'code' => RELOGIN ,'method' => $this->method, 'msg' => '请重新登录' ],JSON_UNESCAPED_UNICODE|JSON_FORCE_OBJECT);
            $this->responseImmediately($msg);
            ServerManager::getInstance()->getSwooleServer()->close($fd);
            return false;
        }
        
        $this->player->check();

        return true;
    }

    protected function gc()
    {
        
        if($this->player instanceof PlayerService){
            $this->player->gcCheck();
            $this->player->saveData();
            RedPointService::getInstance()->sendPoint($this->player);
        } 
        
        if(array_key_exists('uid',$this->param)) Lock::rem($this->param['uid']);
        
        LogService::getInstance()->save();
        
        parent::gc();
        
    }
}