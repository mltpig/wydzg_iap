<?php
namespace App\Api\Controller\Role;
use App\Api\Table\ConfigRole;
use App\Api\Service\TaskService;
use App\Api\Service\RoleService;
use App\Api\Table\ConfigTask;
use App\Api\Controller\BaseController;


//抽卡
class Battle  extends BaseController
{

    public function index()
    {

        $role  = $this->player->getData('role');
        $now   = ConfigRole::getInstance()->getOne($role['lv']);
        $befor = $role['lv'] > 1 ? ConfigRole::getInstance()->getOne($role['lv']-1) : 0;
        $demonicMax =  $role['lv'] == 1 ? $now['demonic_max'] - 0 : $now['demonic_max'] - $befor['demonic_max'];

        $result = '修为不足，无法渡劫';
        if($role['exp'] >= $demonicMax )
        {
            $next = ConfigRole::getInstance()->getOne($role['lv']+1);

            $result = '已达此界上限，不可再做突破，恐有天倾之危';
            if($next)
            {
                $result = '非跨境界，无需渡劫';
                if($now['type'] != $next['type'])
                {
                    $task = TaskService::getInstance()->getShowTask($this->player->getData('task'));
    
                    if(array_key_exists(6,$task))
                    {
                        //直接升级
                        $sum   = array_sum( array_column($task[6],'state'));
                        $count = count($task[6]);
    
                        $result = '请先领取任务奖励';
                        if($sum / $count == 2 )
                        {
                            $this->player->setRole('lv',1,'add');
                            $this->player->setRole('exp',$demonicMax,'sub');
    
                            foreach ($task[6] as $value) 
                            {
                                $this->player->setTask($value['taskid'],0,0,'unset');
                            }
    
                            $newTask    = ConfigTask::getInstance()->getTaskByChapterId($next['type']);
                            foreach ($newTask as $newId) 
                            {
                                $this->player->setTask($newId,0,0,'set');
                                $this->player->setTask($newId,1,0,'set');
                            }

                            // $oldLv = $this->player->getData('role','lv');
                            RoleService::getInstance()->checkExp($this->player);
                            // $newLv = $this->player->getData('role','lv');
                            // if($oldLv != $newLv) TaskService::getInstance()->setVal($this->player,21,$newLv,'set');
                            // TaskService::getInstance()->setVal($this->player,52,1,'add');

                            TaskService::getInstance()->setVal($this->player,null,1,'add');

                            $result = [
                                'role'  => $this->player->getData('role'),
                                'task'  => TaskService::getInstance()->getShowTask($this->player->getData('task')),
                            ];

                            RoleService::getInstance()->checkHead($this->player,$next['type']);
                        }
                        
                    }else{
                        $this->player->setRole('lv',1,'add');
                        $this->player->setRole('exp',$demonicMax,'sub');

                        $oldLv = $this->player->getData('role','lv');
                        RoleService::getInstance()->checkExp($this->player);
                        $newLv = $this->player->getData('role','lv');
                        if($oldLv != $newLv) TaskService::getInstance()->setVal($this->player,21,$newLv,'set');
                        TaskService::getInstance()->setVal($this->player,52,1,'add');
                        
                        $result = [
                            'role'  => $this->player->getData('role'),
                            'task'  => $this->player->getShowTask($this->player->getData('task')),
                        ];
                        RoleService::getInstance()->checkHead($this->player,$next['type']);
                    }
    
    
                }
            }
        }

        $this->sendMsg( $result );
    }

}