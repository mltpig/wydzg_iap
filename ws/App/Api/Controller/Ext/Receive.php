<?php
namespace App\Api\Controller\Ext;
use App\Api\Table\ConfigTask;
use EasySwoole\EasySwoole\Core;
use App\Api\Service\TaskService;
use App\Api\Service\EquipService;
use App\Api\Controller\BaseController;

//抽卡
class Receive extends BaseController
{

    public function index()
    {
        
        $result = [];
        if(Core::getInstance()->runMode() === 'dev')
        {
            $taskid    = $this->param['taskid'];
            $adminTask = TaskService::getInstance()->getAdminTask( $this->player->getData('task'),1 );
            $result = '不可小于等于当前任务';
            if($taskid > $adminTask)
            {
                //删除当前主线任务
                $config = ConfigTask::getInstance()->getOne($taskid);
                
                $result = '只能为主线任务';
                if($config['type'] == 1)
                {
                    $diff = $taskid - $adminTask;
                    $adminconfig = ConfigTask::getInstance()->getOne($adminTask);
                    for ($i=0; $i <= $diff; $i++) 
                    { 
    
                        $this->player->setArg(COUNTER_TASK,1,'add');
                        $this->player->setTask($adminTask,0,0,'unset');

                        if($adminconfig['next_id'])
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
                        
    
                        $adminTask   = $config['next_id'];
                        $adminconfig = ConfigTask::getInstance()->getOne($config['next_id']);
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
        }

        
        $this->sendMsg( $result );
    }

}