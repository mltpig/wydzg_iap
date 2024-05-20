<?php
namespace App\Api\Controller\Player;
use App\Api\Table\ConfigParam;
use EasySwoole\EasySwoole\Core;
use App\Api\Controller\BaseController;
use App\Api\Service\Channel\WeixinService;

class ModifyNickname extends BaseController
{

    public function index()
    {

        $cost = ConfigParam::getInstance()->getFmtParam('RENAME_COST');

        $hasNum = $this->player->getGoods($cost['gid']);
        $result = '改名券数量不足';
        if( $hasNum >= $cost['num'])
        {
            $reCount = $this->player->getArg(COUNTER_RENAME);
            $limit   = ConfigParam::getInstance()->getFmtParam('RENAME_DAILY_TIMES');

            $result = '已达今日次数限制';
            if($limit > $reCount)
            {
                $isOk = true;
                $dev  = Core::getInstance()->runMode() === 'dev';
                if(!$dev) $isOk = WeixinService::getInstance()->msgSecCheck($this->param['uid'],$this->param['newName']);

                $result = '昵称涉及敏感词，请更换';
                if($isOk)
                {
                    $this->player->setArg(COUNTER_RENAME,1,'add');

                    $reward = [ [ 'type' => GOODS_TYPE_1,'gid' => $cost['gid'],'num' => -$cost['num'] ] ];
                    $this->player->goodsBridge($reward,'改名',$reCount );

                    $this->player->setData('user','nickname',$this->param['newName']);
    
                    $result = [ 
                        'user'    => $this->player->getUserInfo(),
                        'counter' => $this->player->getArg(COUNTER_RENAME),
                        'remain'  => $this->player->getGoods($cost['gid']),
                    ];
                }
            }
        }


        $this->sendMsg( $result);
    }

}