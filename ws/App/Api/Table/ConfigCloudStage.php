<?php
namespace App\Api\Table;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigCloudStage as Model;

class ConfigCloudStage
{
    use CoroutineSingleTon;

    protected $tableName = 'config_cloud_stage';
    protected $tableNameLevel = 'config_cloud_stage_level';

    public function create():void
    {

        $columns = [
            'attack'         => [ 'type'=> Table::TYPE_STRING ,'size'=> 100 ],
            'hp'             => [ 'type'=> Table::TYPE_STRING ,'size'=> 100 ],
            'defence'        => [ 'type'=> Table::TYPE_STRING ,'size'=> 100 ],
            'basic_resist'   => [ 'type'=> Table::TYPE_STRING ,'size'=> 100 ],
            'towards_resist' => [ 'type'=> Table::TYPE_STRING ,'size'=> 100 ],
            'cost'           => [ 'type'=> Table::TYPE_STRING ,'size'=> 100 ],
            'advance_cost'   => [ 'type'=> Table::TYPE_STRING ,'size'=> 100 ],
        ];
        
        TableManager::getInstance()->add( $this->tableName , $columns , 35000 );
        $columns = [
            'attack'         => [ 'type'=> Table::TYPE_STRING ,'size'=> 100 ],
            'hp'             => [ 'type'=> Table::TYPE_STRING ,'size'=> 100 ],
            'defence'        => [ 'type'=> Table::TYPE_STRING ,'size'=> 100 ],
            'basic_resist'   => [ 'type'=> Table::TYPE_STRING ,'size'=> 100 ],
            'towards_resist' => [ 'type'=> Table::TYPE_STRING ,'size'=> 100 ],
        ];
        TableManager::getInstance()->add( $this->tableNameLevel , $columns , 35000 );
    }

    public function initTable():void
    {
        $table = TableManager::getInstance()->get($this->tableName);
        $tableLevel = TableManager::getInstance()->get($this->tableNameLevel);

        $tableConfig = Model::create()->all();
        foreach ($tableConfig as $key => $value) 
        {
            $cost = [];
            if($value['lift_cost'])
            {
                $cost = getFmtGoods(explode('=',$value['lift_cost']));
                $cost['num']  = $value['up_exp'];
                $cost['type'] = ConfigGoods::getInstance()->getOne($cost['gid'])['type'];
            }

            $advanceCost = [];
            if($value['advance_cost'])
            {
                $advanceCost = getFmtGoods(explode('=',$value['advance_cost']));
                $advanceCost['type'] = ConfigGoods::getInstance()->getOne($advanceCost['gid'])['type'];
            }

            $index = 'key_'.$value['stage'].'_'.$value['level'];
            
            $table->set($index,[
                'attack'         => $value['attack'],
                'hp'             => $value['hp'],
                'defence'        => $value['defence'],
                'basic_resist'   => $value['basic_resist'],
                'towards_resist' => $value['towards_resist'],
                'cost'           => json_encode($cost),
                'advance_cost'   => json_encode($advanceCost),
            ]);

            $tableLevel->set('key_'.$value['id'],[
                'attack'         => $value['attack'],
                'hp'             => $value['hp'],
                'defence'        => $value['defence'],
                'basic_resist'   => $value['basic_resist'],
                'towards_resist' => $value['towards_resist']
            ]);
        }

    }

    public function getOne(int $stage,int $level):array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();

        $index = 'key_'.$stage.'_'.$level;
        $data = $table->get($index);
        return $data ? [
            'attack'         => $data['attack'],
            'hp'             => $data['hp'],
            'defence'        => $data['defence'],
            'basic_resist'   => $data['basic_resist'],
            'towards_resist' => $data['towards_resist'],
            'cost'           => json_decode($data['cost'],true),
            'advance_cost'   => json_decode($data['advance_cost'],true),
        ] : [];

    }

    public function getOneById(int $id):array
    {
        $table = TableManager::getInstance()->get($this->tableNameLevel);
        if(!$table->count()) $this->initTable();

        $data = $table->get( 'key_'.$id );
        return $data ? [
            'attack'         => $data['attack'],
            'hp'             => $data['hp'],
            'defence'        => $data['defence'],
            'basic_resist'   => $data['basic_resist'],
            'towards_resist' => $data['towards_resist'],
        ] : [];

    }

}
