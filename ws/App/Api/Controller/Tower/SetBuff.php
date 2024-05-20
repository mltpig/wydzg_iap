<?php
namespace App\Api\Controller\Tower;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigTower;
use App\Api\Utils\Consts;
use App\Api\Controller\BaseController;
use App\Api\Service\Module\TowerService;
use App\Api\Service\TaskService;
use App\Api\Table\ConfigSkill;

class SetBuff extends BaseController
{

    public function index()
    {
        $param = $this->param;
        $tower = $this->player->getData('tower');

        if(empty($tower['open'])) // 预设开关 | 关闭
        {
            $result = '暂无加成选择';
            if($tower['bufftemp'])
            {
                $result = '错误加成选择';
                if(array_key_exists($param['temp'],$tower['bufftemp']))
                {
                    if($param['long'])
                    {
                        //存在则有替换的BUFF
                        $buff = TowerService::getInstance()->replaceTowerBuff($tower['buff'],$param['temp'],$param['long']);

                        $this->player->setTower('buff',0,$buff,'set');
                        $this->player->setTower('bufftemp',0,[],'set');

                        $result = [
                            'tower'     => TowerService::getInstance()->getTowerFmtData($this->player),
                        ];
                    }else{
                        $tower_buff = $this->player->getData('tower','buff');
                        //不存在则升级或追加BUFF
                        if(array_key_exists($param['temp'],$tower['buff']))
                        {
                            //升级
                            $old        = $tower_buff[$param['temp']];

                            $skillConfig = ConfigSkill::getInstance()->getOne($param['temp']);
                            if($old < $skillConfig['maxLevel']) $old++;

                            $this->player->setTower('buff',$param['temp'],$old,'multiSet');
                            $this->player->setTower('bufftemp',0,[],'set');

                            $result = [
                                'tower'     => TowerService::getInstance()->getTowerFmtData($this->player),
                            ];
                        }else{
                            //追加
                            $config = ConfigTower::getInstance()->getOne($tower['towerid']);
                            $result = '超出加成上限';
                            if(count($tower['buff']) < $config['buff_limit'])
                            {
                                $this->player->setTower('buff',$param['temp'],1,'multiSet');
                                $this->player->setTower('bufftemp',0,[],'set');

                                $result = [
                                    'tower'     => TowerService::getInstance()->getTowerFmtData($this->player),
                                ];
                            }
                        }
                    }
                }
            }
        }else{
            //预设开关 | 开启
            if($tower['buffnum'] > 0)
            {
                for($i = $tower['buffnum']; $i > 0;)
                {
                    $bufftemp = $this->player->getData('tower','bufftemp');
                    if(empty($bufftemp)){
                        $i--;
                        $buff       = TowerService::getInstance()->getTierWhetherBuff($tower['towerid']);
                        $this->player->setTower('bufftemp',0,$buff,'set');
                        $this->player->setTower('buffnum',0,$i,'set');
                    }
                    
                    TowerService::getInstance()->openPreinst($this->player);
                }
            }else{
                TowerService::getInstance()->openPreinst($this->player);
            }

            $result = [
                'tower'     => TowerService::getInstance()->getTowerFmtData($this->player),
            ];
        }
        $this->sendMsg( $result );
    }

}