<?php
namespace App\Api\Service;
use App\Api\Utils\Consts;
use App\Api\Table\ConfigTree;
use App\Api\Table\ConfigParam;
use EasySwoole\Component\CoroutineSingleTon;

class TreeService
{
    use CoroutineSingleTon;

    public function checkTree(PlayerService $playerSer,int $time):void
    {
        $tree = $playerSer->getData('tree');

        if(!$tree['state'] || $tree['timestamp'] > $time ) return ;

        $playerSer->setData('tree','lv',$tree['lv']+1);
        $playerSer->setData('tree','state',0);
        $playerSer->setData('tree','timestamp',0);

        TaskService::getInstance()->setVal($playerSer,46,$tree['lv']+1,'set');
    }

    public function getShowTree(PlayerService $playerSer):array
    {
        $tree = $playerSer->getData('tree');
        
        return [
            'lv'            => $tree['lv'],
            'state'         => $tree['state'],
            'remain_time'   => $tree['state'] ? $tree['timestamp'] - time() : 0,
            'speed_cd'      => $this->getAdSpeedUpCd($playerSer),
        ];
    }

    public function getRandReward(int $treeLv):array
    {
        $number = ConfigParam::getInstance()->getFmtParam('EQUIPMENTCREATE_DROP_EMPTY_WEIGHT');
        list($weight,$list) = ConfigTree::getInstance()->getRewardWeight($treeLv);
        $weight['-1'] = $number;
        $gid = randTable($weight);
        
        return $gid > 0 ? [ $list[$gid] ] : [];
    }

    public function getAdSpeedUpCd(PlayerService $playerSer)
    {
        $timeSpeed      = $playerSer->getArg(Consts::TREE_SPEED_UP_CD_TIME);
        $timeElapsed    = $timeSpeed - time();
        if(empty($timeSpeed))
        {
            return 0;
        }else{
            return $timeElapsed;
        }
    }
}
