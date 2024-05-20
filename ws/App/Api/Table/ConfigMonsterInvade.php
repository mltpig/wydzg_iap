<?php
namespace App\Api\Table;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigMonsterInvade as Model;

class ConfigMonsterInvade
{
    use CoroutineSingleTon;

    protected $tableName = 'config_monster_invade';

    public function create():void
    {
        $columns = [
            'id'               => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'attack_base'      => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'hp_base'          => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'def_base'         => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'speed_base'       => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            're_stun'          => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            're_critical_hit'  => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            're_double_attack' => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            're_dodge'         => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            're_attack_back'   => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            're_life_steal'    => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
        ];
        
        TableManager::getInstance()->add( $this->tableName , $columns , 200 );

    }

    public function initTable():void
    {
        $table = TableManager::getInstance()->get($this->tableName);

        $tableConfig = Model::create()->all();
        foreach ($tableConfig as $key => $value) 
        {
            $table->set($value['id'],$value->toArray());
        }

    }

    public function getAll():array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();
        
        $list  = [];
        foreach ($table as $id => $config) 
        {
            $list[ $id ] = $config;
        }

        return $list;
    }

}
