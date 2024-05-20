<?php
namespace App\Api\Service;
use App\Api\Table\ConfigRole;
use App\Api\Table\ConfigRoleChara;
use App\Api\Utils\Consts;
use EasySwoole\Component\CoroutineSingleTon;

class RoleService
{
    use CoroutineSingleTon;

    public function checkLv(PlayerService $playerSer):void
    {
        $role = $playerSer->getData('role');
        $now  = ConfigRole::getInstance()->getOne($role['lv']);
        $befor = $role['lv'] > 1 ? ConfigRole::getInstance()->getOne($role['lv']-1) : ['demonic_max' => 0 ];
        //升级经验是否足够
        $demonicMax =  $now['demonic_max'] - $befor['demonic_max'];
        if($role['exp'] < $demonicMax ) return ;

        if(!$next  = ConfigRole::getInstance()->getOne($role['lv']+1)) return;
        //下一等级是否跨大境界
        if($now['type'] != $next['type']) return ;
        //直接升级
        $playerSer->setRole('lv',1,'add');
        $playerSer->setRole('exp',$demonicMax,'sub');

        $newLv = $playerSer->getData('role','lv');
        TaskService::getInstance()->setVal($playerSer,21,$newLv,'set');
    }

    public function checkExp(PlayerService $playerSer):void
    {
        $role = $playerSer->getData('role');
        $now  = ConfigRole::getInstance()->getOne($role['lv']);
        $befor = $role['lv'] > 1 ? ConfigRole::getInstance()->getOne($role['lv']-1) : ['demonic_max' => 0 ];
        //升级经验是否足够
        $demonicMax =  $now['demonic_max'] - $befor['demonic_max'];
        if($role['exp'] < $demonicMax ) return ;
        if(!$next  = ConfigRole::getInstance()->getOne($role['lv']+1)) return;
        //下一等级是否跨大境界
        if($now['type'] != $next['type']) return ;
        //直接升级
        $playerSer->setRole('lv',1,'add');
        $playerSer->setRole('exp',$demonicMax,'sub');
        $newLv = $playerSer->getData('role','lv');
        TaskService::getInstance()->setVal($playerSer,21,$newLv,'set');
        $this->checkExp($playerSer);

    }

    public function dailyReset(PlayerService $playerSer):void
    {
        //每日改名次数
        $playerSer->setArg(COUNTER_RENAME,1,'unset');

    }
    
    public function checkHead(PlayerService $playerSer,int $type):void
    {
        $unlockHead = ConfigRoleChara::getInstance()->getOne($type);
        if(!$unlockHead) return ;
        $playerSer->setHead(1,0,$unlockHead['id'],'push');
        $playerSer->setChara(1,$unlockHead['id']);

        $chara =  $playerSer->getData('user','chara');
        
        if($chara['type'] != 2) $playerSer->setData('user','chara',['type' => 1,'value' => strval($unlockHead['id']) ] );
        
        // $playerSer->setData('user','head', ['type' => 1,'value' => strval($unlockHead['id']) ] );

        $chara = $this->getCharaFmt($playerSer);

        $playerSer->pushi([ 'code' => SUCCESS, 'method' => 'user_update', 'data' => [
            'user'  => $playerSer->getUserInfo(),
            'chara' => $chara,
            'head'  => $playerSer->getData('head'),
        ]  ]);
    }


    public function getCharaFmt(PlayerService $playerSer):array
    {

        $chara  = $playerSer->getData('chara');
        $belong = $playerSer->getArg(Consts::CHARA_BELONG);

        $list = [];

        foreach ($chara as $type => $charaType)
        {
            switch ($type) 
            {
                case 1:
                    foreach ($charaType as $charaid) 
                    {
                        $list[] = ['id' => strval($charaid)  ,'lv' => 1,'type' => $type,'belong' => $belong];
                    }
                    break;
                case 2:
                    foreach ($charaType as $charaid => $lv) 
                    {
                        $config = ConfigRoleChara::getInstance()->getOne($charaid);

                        $list[] = ['id' => strval($charaid) ,'lv' => $lv,'type' => $type,'belong' => $config['belong']];
                    }
                    break;
            }
        }

        return $list;
    }

    public function getPromotionRedPointInfo(PlayerService $playerSer):array
    {
        $list    = $reach   = [];
        $task    = $playerSer->getData('task');
        $taskIds = TaskService::getInstance()->getTasksByType( $task,6 );
        foreach ($taskIds as $taskid) 
        {
            if(!$task[$taskid][1] ) continue;
            if($task[$taskid][1] == 1) $list[] = $taskid;
            if($task[$taskid][1] == 2) $reach[] = $taskid;
        }

        $role  = $playerSer->getData('role');
        $now   = ConfigRole::getInstance()->getOne($role['lv']);
        $befor = $role['lv'] > 1 ? ConfigRole::getInstance()->getOne($role['lv']-1) : ['demonic_max' => 0 ];
        //升级经验是否足够
        $demonicMax =  $now['demonic_max'] - $befor['demonic_max'];

        return [ $list , count($taskIds) == count($reach) && $role['exp'] >= $demonicMax ];
    }

    public function getRoleAttrAdd(&$attr,int $level):void
    {
        if($level <=  0)  return ;
        $role  = ConfigRole::getInstance()->getOne($level);

        $attr['attack']  = add($attr['attack'],$role['attack_base']);
        $attr['hp']      = add($attr['hp'],$role['hp_base']);
        $attr['defence'] = add($attr['defence'],$role['def_base']);
        $attr['speed']   = add($attr['speed'],$role['speed_base']);

    }
}
