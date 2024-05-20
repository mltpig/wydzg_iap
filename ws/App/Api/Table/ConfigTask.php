<?php
namespace App\Api\Table;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigTask as Model;

class ConfigTask
{
    use CoroutineSingleTon;

    protected $tableName      = 'config_task';
    protected $tableNameInit  = 'config_task_init';
    protected $tableNameType6 = 'config_task_type_6';
    protected $tableNameType101 = 'config_task_type_101';
    protected $tableNameType102 = 'config_task_type_102';
    protected $tableNameType103 = 'config_task_type_103';
    protected $tableNameType104 = 'config_task_type_104';

    public function create():void
    {
        $columns = [
            'id'               => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'type'             => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'little_type'      => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'pid'              => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'next_id'          => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'complete_type'    => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'name'             => [ 'type'=> Table::TYPE_STRING ,'size'=> 300 ],
            'jump_id'          => [ 'type'=> Table::TYPE_STRING ,'size'=> 200 ],
            'complete_params'  => [ 'type'=> Table::TYPE_STRING ,'size'=> 200 ],
            'rewards'          => [ 'type'=> Table::TYPE_STRING ,'size'=> 200 ],
        ];

        TableManager::getInstance()->add( $this->tableName , $columns , 5000 );
        TableManager::getInstance()->add( $this->tableNameType6 , $columns , 5000 );
        TableManager::getInstance()->add( $this->tableNameType101 , $columns , 100 );
        TableManager::getInstance()->add( $this->tableNameType102 , $columns , 100 );
        TableManager::getInstance()->add( $this->tableNameType103 , $columns , 100 );
        TableManager::getInstance()->add( $this->tableNameType104 , $columns , 200 );

        $columns = [ 'id' => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],'type' => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ] ];
        TableManager::getInstance()->add( $this->tableNameInit , $columns , 5000 );


    }

    public function initTable():void
    {
        $table      = TableManager::getInstance()->get($this->tableName);
        $tableInit  = TableManager::getInstance()->get($this->tableNameInit);
        $tableType6 = TableManager::getInstance()->get($this->tableNameType6);
        $tableType101 = TableManager::getInstance()->get($this->tableNameType101);
        $tableType102 = TableManager::getInstance()->get($this->tableNameType102);
        $tableType103 = TableManager::getInstance()->get($this->tableNameType103);
        $tableType104 = TableManager::getInstance()->get($this->tableNameType104);

        $tableConfig = Model::create()->all();
        foreach ($tableConfig as $key => $value)
        {
            $reward = [];
            if($value['rewards'])
            {
                $rewardList = explode('|',$value['rewards']);
                foreach ($rewardList as $detailConfig) 
                {
                    $detail = getFmtGoods(explode('=',$detailConfig));
                    $detail['type'] = ConfigGoods::getInstance()->getOne($detail['gid'])['type'];
                    $reward[] = $detail;
                }
            }
            $taskData = [
                'name'            => $value['name'],
                'type'            => $value['type'],
                'little_type'     => $value['little_type'],
                'pid'             => $value['pid'],
                'next_id'         => $value['next_id'],
                'complete_type'   => $value['complete_type'],
                'jump_id'         => $value['jump_id'],
                'complete_params' => json_encode(explode('|',$value['complete_params'])),
                'rewards'         => json_encode( $reward )
            ];

            $table->set($value['id'],$taskData);

            //境界任务
            if($value['type'] == 6) $tableType6->set($value['id'],$taskData);
            if($value['type'] == 101) $tableType101->set($value['id'],$taskData);
            if($value['type'] == 102) $tableType102->set($value['id'],$taskData);
            if($value['type'] == 103) $tableType103->set($value['id'],$taskData);
            if($value['type'] == 104) $tableType104->set($value['id'],$taskData);

            if($value['pid']) continue;

            $data = [ 'id'=> $value['id'] ,'type' => $value['type'] ];
            switch ($value['type']) 
            {
                case 1:
                case 2:
                    $tableInit->set($value['id'],$data);
                    break;
                case 6:
                    if($value['little_type'] == 1 )$tableInit->set($value['id'],$data);
                    break;
                default:
                    # code...
                    break;
            }
        }
    }

    public function getAll(array $taskids,int $typlittleTypee = null):array
    {
        $table = TableManager::getInstance()->get($this->tableName);

        if(!$table->count()) $this->initTable();
        $list = [];
        foreach ($taskids as  $id) 
        {
            if(!$data = $table->get($id)) continue;
            
            if(!is_null($typlittleTypee) && $data['complete_type'] != $typlittleTypee ) continue;

            $list[$id] = [
                'pid'             => $data['pid'],
                'next_id'         => $data['next_id'],
                'type'            => $data['type'],
                'little_type'     => $data['little_type'],
                'complete_type'   => $data['complete_type'],
                'jump_id'         => $data['jump_id'],
                'complete_params' => json_decode($data['complete_params'],true),
                'rewards'         => json_decode($data['rewards'],true),
            ];
        }

        return $list;
    }

    public function getOne(int $id):array
    {
        $table = TableManager::getInstance()->get($this->tableName);

        if(!$table->count()) $this->initTable();

        $data = $table->get($id);

        return $data ? [
            'type'            => $data['type'],
            'pid'             => $data['pid'],
            'next_id'         => $data['next_id'],
            'complete_type'   => $data['complete_type'],
            'jump_id'         => $data['jump_id'],
            'complete_params' => json_decode($data['complete_params'],true),
            'rewards'         => json_decode($data['rewards'],true),
        ] : [];

    }

    public function getInitData():array
    {
        $table = TableManager::getInstance()->get($this->tableNameInit);
        if(!$table->count()) $this->initTable();
        
        $list = [];
        foreach ($table as $taskid => $value) 
        {
            $list[$taskid] = $value;
        }

        return $list;
    }

    public function getTaskByChapterId(int $roleLv):array
    {
        $table = TableManager::getInstance()->get($this->tableNameType6);
        if(!$table->count()) $this->initTable();
        
        $list = [];
        foreach ($table as $taskid => $value) 
        {
            if($value['little_type'] != $roleLv) continue;
            $list[] = $taskid;
        }

        return $list;
    }

    public function getInitType1Taskid():int
    {
        $table = TableManager::getInstance()->get($this->tableNameInit);
        if(!$table->count()) $this->initTable();
        
        $taskid = 0;
        foreach ($table as $taskid => $value) 
        {
            if($value['type'] != 1 ) continue;
            $taskid = $taskid;
        }

        return $taskid;
    }

    public function getNewYearInitTask():array
    {
        $table = TableManager::getInstance()->get($this->tableNameType101);
        if(!$table->count()) $this->initTable();
        
        $list = [];
        foreach ($table as $taskid => $value) 
        {
            $list[$taskid] = [
                'name'             => $value['name'],
                'complete_type'    => $value['complete_type'],
                'complete_params'  => json_decode($value['complete_params'],true),
            ];
        }
        return $list;
    }

    public function getXianYuanInitTask():array
    {
        $table = TableManager::getInstance()->get($this->tableNameType102);
        if(!$table->count()) $this->initTable();
        
        $list = [];
        foreach ($table as $taskid => $value) 
        {
            $list[$taskid] = [
                'name'             => $value['name'],
                'complete_type'    => $value['complete_type'],
                'complete_params'  => json_decode($value['complete_params'],true),
            ];
        }
        return $list;
    }

    public function getCelebration103InitTask():array
    {
        $table = TableManager::getInstance()->get($this->tableNameType103);
        if(!$table->count()) $this->initTable();
        
        $list = [];
        foreach ($table as $taskid => $value) 
        {
            $list[$taskid] = [
                'name'             => $value['name'],
                'complete_type'    => $value['complete_type'],
                'complete_params'  => json_decode($value['complete_params'],true),
            ];
        }
        return $list;
    }

    public function getCelebration104InitTask():array
    {
        $table = TableManager::getInstance()->get($this->tableNameType104);
        if(!$table->count()) $this->initTable();
        
        $list = [];
        foreach ($table as $taskid => $value) 
        {
            $list[$taskid] = [
                'name'             => $value['name'],
                'complete_type'    => $value['complete_type'],
                'complete_params'  => json_decode($value['complete_params'],true),
            ];
        }
        return $list;
    }
}
