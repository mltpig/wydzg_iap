<?php
namespace App\Api\Controller\Activity\NewYear;
use App\Api\Service\ActivityService;
use App\Api\Table\Activity\NewYear;
use App\Api\Controller\BaseController;
use App\Api\Utils\Consts;

class Receive extends BaseController
{

    public function index()
    { 
        $id = $this->param['id'];

        $time   = time();
        $begin  = strtotime(Consts::ACTIVITY_NEW_YEAR_BEGIN);
        $end    = strtotime(Consts::ACTIVITY_NEW_YEAR_END);
        $result = '活动未开启';
        if($time > $begin && $time < $end || $time == $begin)
        {
            $newYearConfig = NewYear::getInstance()->getOne($id);
    
            $result = '该奖励暂未开启';
            if($newYearConfig)
            {
                $result = '该奖励已领取';

                $task   = $this->player->getData('task');
                $bosx   = ActivityService::getInstance()->getNewYearBoxs($this->player,$task);
                $state = array_column($bosx,'state','id')[$id];
                if($state == 1)
                {
                    //根据任务状态来判断宝箱状态
                    foreach ($newYearConfig['task_need'] as $taskid) 
                    {
                        $this->player->setTask($taskid,1,2,'set');
                    }

                    $this->player->setArg(Consts::ACTIVITY_NEW_YEAR_TAG[$id],2,'reset');
                    
                    $this->player->goodsBridge($newYearConfig['reward'],'新年活动',Consts::ACTIVITY_NEW_YEAR_TAG[$id]);

                    $result = [
                        'id'     => $id,
                        'state'  => 2,
                        'reward' => $newYearConfig['reward'],
                    ];
                    
                }
            }
        }

        $this->sendMsg( $result );
    }

}