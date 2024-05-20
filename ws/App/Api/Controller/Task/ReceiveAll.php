<?php
namespace App\Api\Controller\Task;
use App\Api\Table\ConfigTask;
use App\Api\Service\TaskService;
use App\Api\Controller\BaseController;

//抽卡
class ReceiveAll extends BaseController
{

    public function index()
    {
        $taskid = $this->param['taskid'];
        $task   = $this->player->getData('task');
        $result = '无效任务ID';
        if(array_key_exists($taskid,$task))
        {
            $value = $task[$taskid];
            $result = '任务未完成';
            if($value[1])
            {

                $receiveConfig = ConfigTask::getInstance()->getOne($taskid);

                $rewardList = [];
                foreach ($task as $id => $taskState) 
                {
                    if($taskState[1] != 1 ) continue;

                    $config = ConfigTask::getInstance()->getOne($id);
                    if($receiveConfig['type'] != $config['type']) continue;
                    $this->player->goodsBridge($config['rewards'],'任务奖励',$id);

                    switch ($config['type']) 
                    {
                        case 1:
                        case 2:
                            if($config['next_id'])
                            {
                                $this->player->setTask($config['next_id'],0,0,'set');
                                $this->player->setTask($config['next_id'],1,0,'set');
                            }
                            $this->player->setTask($id,0,0,'unset');
                            break;
                        case 6:
                            $this->player->setTask($id,1,2,'set');
                            break;
                    }

                    foreach ($config['rewards'] as $reward) 
                    {
                        array_key_exists($reward['gid'],$rewardList) ? $rewardList[$reward['gid']]['num'] += $reward['num'] : $rewardList[$reward['gid']] = $reward;
                    }
                }

                TaskService::getInstance()->setVal($this->player,null,1,'add');
                
                $result = [
                    'task'    => TaskService::getInstance()->getShowTask( $this->player->getData('task') ),
                    'reward'  => array_values($rewardList),
                    'scene'   => $this->param['scene'],
                ];
            }
        }

        $this->sendMsg( $result );
    }

}