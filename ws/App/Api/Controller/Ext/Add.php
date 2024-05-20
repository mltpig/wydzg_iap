<?php
namespace App\Api\Controller\Ext;
use EasySwoole\EasySwoole\Core;
use App\Api\Service\RoleService;
use App\Api\Controller\BaseController;
use App\Api\Table\ConfigGoods;

class Add  extends BaseController
{

    public function index()
    {
        $result = '无效的物品ID';

        if(Core::getInstance()->runMode() === 'dev')
        {
            
            $info = ConfigGoods::getInstance()->getOne($this->param['gid']);
            $rewards = [
                ['gid' => $this->param['gid'],'num' => $this->param['num'],'type' => $info['type'] ]
            ];
            if($this->param['gid'] == 110){
                $this->player->setRole('exp',$this->param['num'],'add');
                    
                RoleService::getInstance()->checkLv($this->player);
                RoleService::getInstance()->checkExp($this->player);
            }else{

                $this->player->goodsBridge($rewards,'测试','');
            }
            
            $result = [
                'reward' => $rewards
            ];
        }

        $this->sendMsg($result);
    }

}