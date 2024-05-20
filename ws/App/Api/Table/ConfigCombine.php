<?php
namespace App\Api\Table;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigCombine as Model;

class ConfigCombine
{
    use CoroutineSingleTon;

    protected $tableName = 'config_combine';

    public function create():void
    {
        $columns = [
            'id'               => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'desc'             => [ 'type'=> Table::TYPE_STRING ,'size'=> 100 ],
            'comb_skill_id'    => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'skill_id'         => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'type'             => [ 'type'=> Table::TYPE_INT ,'size'=> 100 ],
            'sort'             => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
        ];

        TableManager::getInstance()->add( $this->tableName , $columns , 100 );

    }

    public function initTable():void
    {
        $table = TableManager::getInstance()->get($this->tableName);

        $tableConfig = Model::create()->all();
        foreach ($tableConfig as $value) 
        {

            $table->set($value['id'],[
                'desc'            => $value['desc'],
                'comb_skill_id'   => $value['comb_skill_id'],
                'skill_id'        => $value['skill_id'],
                'type'            => $value['type'],
                'sort'            => $value['sort'],
            ]);
        }

    }

    public function getOne(int $level):array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();
        
        $data = $table->get($level);

        return $data ? [
            'desc'            => $data['desc'],
            'comb_skill_id'   => $data['comb_skill_id'],
            'skill_id'        => $data['skill_id'],
            'type'            => $data['type'],
            'sort'            => $data['sort'],
        ] : [];

    }

    public function getTypeAll(int $type):array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();
        
        $list  = [];
        foreach ($table as $combineid => $value) 
        {
            if($type != $value['type']) continue;
            $list[] = $combineid;
        }
        
        return $list;
    }

    public function getTypeSkill(int $type):array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();
        
        $list  = [];
        foreach ($table as $combineid => $value) 
        {
            if($type != $value['type']) continue;
            $list[$combineid] = $value['comb_skill_id'];
        }
        
        return $list;
    }
}
