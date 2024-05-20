<?php
namespace App\Api\Service;
use App\Api\Table\ConfigTask;
use App\Api\Table\ConfigRole;
use App\Api\Utils\Consts;
use EasySwoole\Component\CoroutineSingleTon;

class TaskService
{
    use CoroutineSingleTon;


    //Admin:
    //1=主线
    //2=关卡章节奖励
    //3=妖盟任务
    //4=
    //5=
    //6=境界任务
    //7=
    //8=和神通相关的任务
    //9=妖盟成就任务

    public function getInitTask():array
    {
        $initConfig = ConfigTask::getInstance()->getInitData();
        $list = [];
        foreach ($initConfig as $taskid => $value) 
        {
            if(!in_array($value['type'],[1,2,6])) continue;
            //state val
            $list[$taskid] = [0, 0 ];
        }
        return $list;
    }

    public function setVal(PlayerService $playerSer,int $completeType=null,int $number,string $action):void
    {
        $task    = $playerSer->getData('task');
        $configs = ConfigTask::getInstance()->getAll(array_keys($task),$completeType);
        if(!$configs ) return ;

        $list = [];
        //2=锤炼N次
        //28=参与比武N次（斗法挑战）
        //29=推演阵法N次
        //31=千里走单骑通关第N关
        //34=累计吸引精怪N次
        //36=累计召唤灵兽N次
        //39=异兽入侵挑战N次
        //41=仙友赠礼N次
        //45=分解装备获得N个灵石
        //51=冒险挑战N次
        //53=灵兽上阵N次
        //54=灵兽升级N次
        //57=升级精怪N次
        //67=福地累计采集N次
        //69=合成精怪N次
        //71=分解N件装备
        //76=完成仙友游历N次
        //77=进行N次挑战妖王
        //1001=活动期间累计登录N天
        //1002=在坊市累计购买N个道具
        //1003=活动期间累计观看N个视频

        $incrVal = [2,28,29,31,34,36,39,41,45,51,53,54,57,67,69,71,76,77,1001,1002,1003];
        foreach ($configs as $taskid => $config) 
        {
            //达成任务不再判断
            if($task[$taskid][1]) continue;

            if(in_array($config['complete_type'],$incrVal) && !is_null($completeType) ) $playerSer->setTask($taskid,0,$number,$action);

            $val = $playerSer->getData('task',$taskid);

            list($num,$state) = $this->getTaskState($playerSer,$val[0],$config);

            $playerSer->setTask($taskid,0,$num,'set');

            $list[$taskid] = [ 
                'taskid'         => $taskid , 
                'type'           => $config['type'] ,
                'target'         => $config['complete_params'],
                'jump_id'        => $config['jump_id'],
                'val'            => $num,
                'state'          => $val[1],
                'reward'         => $config['rewards'],
                'complete_type'  => $config['complete_type'],
            ];

            if(!$state) continue;
            $playerSer->setTask($taskid,1,1,'set');
            
            $list[$taskid]['state'] = 1;
        }

        //不主动推列表
        if($list) $playerSer->pushi([ 'code' => SUCCESS, 'method' => 'task_update', 'data' => array_values($list)  ]);
    }

    // 3=通关关卡N
    // 21=角色等级达到N级
    // 27=挑战妖王中击败id为N的妖王
    // 30=累计座驾升级N次
    // 44=穿戴N件装备
    // 46=仙树N级
    // 52=达到境界N
    // 70=穿戴X件品质≥N的装备
    // 1004=活动期间id为X的道具累计N个（活动重置时删除该道具）消耗道具请自定义Arg计数累计
    // 以上数据动态取值
    public function getTaskState(PlayerService $playerSer,int $taskVal,array $config):array
    {
        $target0 = $config['complete_params'][0];
        $target1 = $config['complete_params'][1];
        
        $number  = 0;
        switch ($config['complete_type']) 
        {
            case 3://通关关卡N
                $number = $playerSer->getData('chapter') - 1;
                break;
            case 21://角色等级达到N级
                $number = $playerSer->getData('role','lv');
                break;
            case 24://拥有N个福地鼠宝
                $number = count( $playerSer->getData('paradise','worker')['list'] );
                break;
            case 26://斗法胜利N次
                $number = $playerSer->getArg(Consts::DOUFA_WIN_COUNT,1);
                break;
            case 27://挑战妖王中击败id为N的妖王
                $number = $playerSer->getData('challenge');
                break;
            case 30://累计座驾升级N次
                $number = $playerSer->getArg(COUNTER_CLOUD_UP);
                break;
            case 31://千里走单骑通关10-10
                $number = $playerSer->getArg(Consts::TOWER_HIGH_RECORD);
                break;
            case 44://穿戴N件装备
                $number = count(array_column($playerSer->getData('equip'),'equipid'));
                break;
            case 46://仙树N级
                $number = $playerSer->getData('tree','lv');
                break;
            case 52://达到境界N
                $lv = $playerSer->getData('role','lv');
                $number = ConfigRole::getInstance()->getOne($lv)['type'];
                break;
            case 70://穿戴X件品质≥N的装备
                $number = EquipService::getInstance()->getEquipQualityCount($playerSer->getData('equip'),$target1);
                break;
            case 1004://活动期间id为X的道具累计N个（活动重置时删除该道具）消耗道具请自定义Arg计数累计
                //  $number = $playerSer->getGoods($target1);
                $number = $playerSer->getArg($target1);
                break;
            default:
                $number = $taskVal;
                break;
        }

        return [ $number, $number >=  $target0 ];
    }

    public function getShowTask(array $task):array
    {
        $list = [];
        $config = ConfigTask::getInstance()->getAll(array_keys($task),null);
        foreach ($config as $taskid => $value) 
        {
            $list[ $value['type'] ][] = [
                'taskid'        => $taskid,
                'type'          => $value['type'],
                'target'        => $value['complete_params'],
                'jump_id'       => $value['jump_id'],
                'val'           => $task[$taskid][0],
                'state'         => $task[$taskid][1],
                'reward'        => $value['rewards'],
                'complete_type' => $value['complete_type'],
            ];
        }
        return $list;
    }

    public function getAdminTask(array $task,int $type ):int
    {
        $config = ConfigTask::getInstance()->getAll(array_keys($task),null);
        foreach ($config as $taskid => $value) 
        {
            if($value['type'] == $type ) return $taskid;
        }

        return 0 ;
    }

    public function getTasksByType(array $task,int $type ):array
    {
        $list   = [];
        $config = ConfigTask::getInstance()->getAll(array_keys($task),null);
        foreach ($config as $taskid => $value) 
        {
            if($value['type'] !== $type ) continue;
            $list[] = $taskid;
        }

        return $list;
    }

}
