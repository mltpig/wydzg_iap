<?php
namespace App\Api\Table;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigSkillRandom as Model;

class ConfigSkillRandom
{
    use CoroutineSingleTon;

    protected $tableName = 'config_skill_random';

    public function create():void
    {

        $columns = [
            'id'          => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'skill_id'    => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'weight'      => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'pool_type'   => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
        ];
        
        TableManager::getInstance()->add( $this->tableName , $columns , 500 );

    }

    public function initTable():void
    {
        $table = TableManager::getInstance()->get($this->tableName);

        $tableConfig = Model::create()->all();
        foreach ($tableConfig as $value) 
        {

            $table->set($value['id'],[
                'id'          => $value['id'],
                'skill_id'    => $value['skill_id'],
                'weight'      => $value['weight'],
                'pool_type'   => $value['pool_type'],
            ]);
        }

    }

    public function getAllWeight(int $type,array $filter):array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();

        $skills = [];
        $weight = [];
        foreach ($table as $id => $config) 
        {
            if($config['pool_type'] != $type || array_key_exists($config['skill_id'],$filter)) continue;
            $skills[ $id ] = $config['skill_id'];
            $weight[ $id ] = $config['weight'];
        }

        return [ $skills , $weight ];

    }

}
