<?php
namespace App\Api\Controller\Magic;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigMagic;
use App\Api\Table\ConfigMagicLevelUp;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\MagicService;
use App\Api\Service\TaskService;

class UpLv extends BaseController
{

    public function index()
    {
        $param  = $this->param;
        $magic  = $this->player->getData('magic');

        $result = '未衍化';
        if(array_key_exists($param['id'],$magic['bag']))
        {
            $config     = $magic['bag'][$param['id']];
            $where_level_limit  = MagicService::getInstance()->getMagicLevelLimit($config['lv'], $config['stage']);
            $result = '已达当前最大等级';
            if($where_level_limit)
            {
                $up_consume = ConfigMagicLevelUp::getInstance()->getOne($config['lv']);
                $cost       = $up_consume['cost'];
                $result     = '道具不足';
                if($this->player->getGoods($cost[0]['gid']) >= $cost[0]['num'] && $this->player->getGoods($cost[1]['gid']) >= $cost[1]['num'])
                {
                    // 一键十连
                    if($param['open'])
                    {
                        $remain = [];
                        for($i = 1; $i <= 10; $i++)
                        {
                            $bag = $this->player->getData('magic','bag');
                            $shengtong              = $bag[$param['id']];
                            $shengtong_level_limit  = MagicService::getInstance()->getMagicLevelLimit($shengtong['lv'], $shengtong['stage']);
                            if($shengtong_level_limit)
                            {
                                $consume = ConfigMagicLevelUp::getInstance()->getOne($shengtong['lv']);
                                $st_cost = $consume['cost'];
                                if($this->player->getGoods($st_cost[0]['gid']) >= $st_cost[0]['num'] && $this->player->getGoods($st_cost[1]['gid']) >= $st_cost[1]['num']){

                                    $shengtong['lv']++;
                                    $this->player->setMagic('bag',$param['id'],$shengtong,'multiSet');

                                    $goodsList = [];
                                    foreach($st_cost as $value)
                                    {
                                        $goodsList[] = ['type' => GOODS_TYPE_1, 'gid' => $value['gid'], 'num' => -$value['num']];
                                    }
                                    $this->player->goodsBridge($goodsList,'神通十连升级');
                                }
                            }
                        }
                        $remain[] = ['gid' => $st_cost[0]['gid'], 'num' => $this->player->getGoods($st_cost[0]['gid'])];
                        $remain[] = ['gid' => $st_cost[1]['gid'], 'num' => $this->player->getGoods($st_cost[1]['gid'])];
                        $result = [
                            'magic'     => MagicService::getInstance()->getMagicFmtData($this->player),
                            'remain'    => $remain,
                        ];
                    }else{
                        $remain = [];
                        $config['lv']++;
                        $this->player->setMagic('bag',$param['id'],$config,'multiSet');

                        $goodsList = [];
                        foreach($cost as $value)
                        {
                            $goodsList[] = ['type' => GOODS_TYPE_1, 'gid' => $value['gid'], 'num' => -$value['num']];
                        }
                        $this->player->goodsBridge($goodsList,'神通升级');
                        $remain[] = ['gid' => $cost[0]['gid'], 'num' => $this->player->getGoods($cost[0]['gid'])];
                        $remain[] = ['gid' => $cost[1]['gid'], 'num' => $this->player->getGoods($cost[1]['gid'])];
                        $result = [
                            'magic'     => MagicService::getInstance()->getMagicFmtData($this->player),
                            'remain'    => $remain,
                        ];
                    }
                }
            }
        }

        $this->sendMsg( $result );
    }

}