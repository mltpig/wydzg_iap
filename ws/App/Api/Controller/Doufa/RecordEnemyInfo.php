<?php
namespace App\Api\Controller\Doufa;
use App\Api\Service\DoufaService;
use App\Api\Controller\BaseController;

class RecordEnemyInfo extends BaseController
{

    public function index()
    {

        $rid = $this->param['rid'];
        $result = '无效的rid';
        if($info = DoufaService::getInstance()->getRecordEnemy($this->param['uid'],$this->param['site'],$rid))
        {

            $enemyInfo =  DoufaService::getInstance()->getEnemyPlayerInfo( $info['playerid'],$this->param['site']);
            unset($enemyInfo['playerid']);
            $enemyInfo['rid'] = $rid;
            $result = [
                'enemyInfo' => $enemyInfo,
            ];
            
        }
        
        $this->sendMsg($result);
    }

}