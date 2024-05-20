<?php
namespace App\Api\Controller\Doufa;
use App\Api\Service\DoufaService;
use App\Api\Controller\BaseController;
//回收
class Enemys extends BaseController
{

    public function index()
    {
        
        $enemy  = $this->player->getData('doufa','enemy');
        
        if(!$enemy)
        {
            $enemy = DoufaService::getInstance()->getEnemysUid($this->param['uid'],$this->param['site'],5);
            $this->player->setData('doufa','enemy',$enemy);
        }

        $this->sendMsg( [
            'enemys' => DoufaService::getInstance()->getEnemyList($enemy,$this->param['site']),
        ]);
    }

}