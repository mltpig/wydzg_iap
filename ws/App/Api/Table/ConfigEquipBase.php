<?php
namespace App\Api\Table;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigEquipBase as Model;

class ConfigEquipBase
{
    use CoroutineSingleTon;

    protected $tableNameByLevel   = 'config_equip_base_by_level';
    
    public function create():void
    {
        $columns = [
            'id'          => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'level'       => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'attack'      => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'hp'          => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'defence'     => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'speed'       => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'special'     => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'special_def' => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'reward'      => [ 'type'=> Table::TYPE_STRING ,'size'=> 100 ],
        ];

        TableManager::getInstance()->add( $this->tableNameByLevel , $columns , 5000 );
    }

    public function initTable():void
    {
        $tableByLevel   = TableManager::getInstance()->get($this->tableNameByLevel);

        $tableConfig = Model::create()->all();
        foreach ($tableConfig as $value) 
        {

            $reward = getFmtGoods(explode('=',$value['reward']));
            $reward['type'] = ConfigGoods::getInstance()->getOne($reward['gid'])['type'];

            $tableByLevel->set($value['level'],[
                'id'            => $value['id'],
                'level'         => $value['level'],
                'attack'        => $value['attack'],
                'hp'            => $value['hp'],
                'defence'       => $value['defence'],
                'speed'         => $value['speed'],
                'special'       => $value['special'],
                'special_def'   => $value['special_def'],
                'reward'        => json_encode([ $reward ]),
            ]);
        }

    }


    public function getOneByLevel(int $level):array
    {
        $table = TableManager::getInstance()->get($this->tableNameByLevel);
        if(!$table->count()) $this->initTable();
        
        $data = $table->get($level);

        return $data ? [
            'id'            => $data['id'],
            'level'         => $data['level'],
            'attack'        => $data['attack'],
            'hp'            => $data['hp'],
            'defence'       => $data['defence'],
            'speed'         => $data['speed'],
            'special'       => $data['special'],
            'special_def'   => $data['special_def'],
            'reward'        => json_decode($data['reward'],true),
        ] : [];
    }

}
