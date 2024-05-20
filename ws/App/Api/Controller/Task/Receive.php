<?php
namespace App\Api\Controller\Task;
use App\Api\Table\ConfigTask;
use App\Api\Service\TaskService;
use App\Api\Service\EquipService;
use App\Api\Controller\BaseController;

//抽卡
class Receive extends BaseController
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

                $config = ConfigTask::getInstance()->getOne($taskid);

                $this->player->goodsBridge($config['rewards'],'任务奖励',$taskid);
                
                switch ($config['type']) 
                {
                    case 1:
                        $this->player->setArg(COUNTER_TASK,1,'add');
                    case 2:
                        if($config['next_id'])
                        {
                            $this->player->setTask($config['next_id'],0,0,'set');
                            $this->player->setTask($config['next_id'],1,0,'set');

                            if($config['next_id'] == 100030)
                            {
                                $this->player->setCloud('apply',10001,'set');

                                $this->player->pushi([ 
                                    'code' => SUCCESS, 
                                    'method' => 'cloud_update', 
                                    'data' => [ 'cloud' 	=> $this->player->getData('cloud') ]  
                                ]);
                            } 
                        }
                        $this->player->setTask($taskid,0,0,'unset');
                        break;
                    case 6:
                        $this->player->setTask($taskid,1,2,'set');
                        break;
                }

                TaskService::getInstance()->setVal($this->player,null,1,'add');
                
                $equipTmp = $this->player->getData('equip_tmp');

                $result = [
                    'task'        => TaskService::getInstance()->getShowTask( $this->player->getData('task') ),
                    'reward'      => $config['rewards'],
                    'scene'       => $this->param['scene'],
                    'mainTaskNum' => $this->player->getArg(COUNTER_TASK,1),
                    'equip_tmp'   => EquipService::getInstance()->getEquipFmtData(array_values($equipTmp)),
                ];
            }
        }

        $this->sendMsg( $result );
    }

}