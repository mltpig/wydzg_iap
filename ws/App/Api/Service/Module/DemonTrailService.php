<?php
namespace App\Api\Service\Module;

use App\Api\Service\BattleService;
use App\Api\Service\PlayerService;
use App\Api\Utils\Consts;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigSystemInfo;
use EasySwoole\Component\CoroutineSingleTon;

class DemonTrailService
{
    use CoroutineSingleTon;

    public function initDemonTrail(PlayerService $playerSer):void
    {
        //解锁初始化
        if($demon_trail = $playerSer->getData('demon_trail')) return ;

        $demon_trail = [];

        $playerSer->setDemonTrail('',0,$demon_trail,'flushall');
    }

    public function dailyReset(PlayerService $playerSer):void
    {

    }

    public function getDemonTrailConfig():array
    {
        $config = ConfigSystemInfo::getInstance()->getAll();

        $list = [];
        foreach($config as $k => $v)
        {
            //1001 - 1019 妖途
            if($v['sort'] >= 1001 && $v['is_show_tow'] == 1)
            {
                $condition_type = explode("|",$v['condition_type']);
                $value          = explode("|",$v['value']);
                list($gid,$num) = explode("=",$v['reward']);

                $list[$k] = [
                    'is_show'           => $v['is_show'],
                    'condition_type'    => $condition_type,
                    'value'             => $value,
                    'reward'            => ['gid' => $gid, 'num' => $num],
                ];
            }
        }
        return $list;
    }

    public function getDemonTrailFmtData(PlayerService $playerSer):array
    {
        $demon_trail = $playerSer->getData('demon_trail');// 0:未满足; 1:可领取; 2:已领取

        $config = $this->getDemonTrailConfig();

        $demonTrailData = [];
        foreach($config as $id => $data)
        {
            if(array_key_exists($id,$demon_trail))
            {
                $demonTrailData[$id] = 2;
            }else{
                $demonTrailData[$id] = 0;

                // 1:任务; 2:等级; 3:时间
                switch ($data['condition_type']) {
                    case [1]:

                        $where = $data['value'][0];
                        if($playerSer->getArg(COUNTER_TASK) >= $where) $demonTrailData[$id] = 1;
                        break;
                    case [2]:

                        $where = $data['value'][0];
                        $lv    = $playerSer->getData('role','lv');
                        if($lv >= $where) $demonTrailData[$id] = 1;
                        break;
                    case [2,3]:
        
                        //暂不处理时间
                        $where = $data['value'][0];
                        $lv    = $playerSer->getData('role','lv');
                        if($lv >= $where) $demonTrailData[$id] = 1;
                        break;
                    default:
                        break;
                }
            }
        }

        return $demonTrailData;
    }

    public function getDemonTrailRedPointInfo(PlayerService $playerSer)
    {
        $demon_trail = $this->getDemonTrailFmtData($playerSer);
        $where = false;
        foreach($demon_trail as $k => $v){
            if($v == 1){
                $where = true;
            }
        }
        return $where;
    }
}
