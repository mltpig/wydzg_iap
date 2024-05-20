<?php
namespace App\Api\Table;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigTower as Model;

class ConfigTower
{
    use CoroutineSingleTon;

    protected $tableName = 'config_tower';

    public function create():void
    {
        $columns = [
            'floor'                 => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'moster_list'           => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'moster_level_list'     => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'buff_limit'            => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'repeat_rewards'        => [ 'type'=> Table::TYPE_STRING ,'size'=> 100 ],
            'rewards'               => [ 'type'=> Table::TYPE_STRING ,'size'=> 100 ],
            'sec_attribute'         => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'sec_def_attribute'     => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
        ];

        TableManager::getInstance()->add( $this->tableName , $columns , 3000 );

    }

    public function initTable():void
    {
        $table = TableManager::getInstance()->get($this->tableName);

        $tableConfig = Model::create()->all();
        foreach ($tableConfig as $key => $value) 
        {

            $repeat  = [];
            $list = explode('|',$value['repeat_rewards']);
            foreach ($list as  $val) 
            {
                $repeat_rewards = getFmtGoods(explode('=',$val));
                $repeat_rewards['type'] = ConfigGoods::getInstance()->getOne($repeat_rewards['gid'])['type'];
                $repeat[] = $repeat_rewards;
            }

            $rewards = getFmtGoods(explode('=',$value['rewards']));
            $rewards['type'] = ConfigGoods::getInstance()->getOne($rewards['gid'])['type'];

            $table->set($value['id'],[
                'floor'              => $value['floor'],
                'moster_list'        => $value['moster_list'],
                'moster_level_list'  => $value['moster_level_list'],
                'buff_limit'         => $value['buff_limit'],
                'repeat_rewards'     => json_encode($repeat),
                'rewards'            => json_encode($rewards),
                'sec_attribute'      => $value['sec_attribute'],
                'sec_def_attribute'  => $value['sec_def_attribute'],
            ]);
        }

    }

    public function getAll():array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();

        $list  = [];
        foreach ($table as $id => $config)
        {
            $list[ $id ] = [
                'floor'                 => $config['floor'],
                'moster_list'           => $config['moster_list'],
                'moster_level_list'     => $config['moster_level_list'],
                'buff_limit'            => $config['buff_limit'],
                'repeat_rewards'        => json_decode($config['repeat_rewards'],true),
                'rewards'               => json_decode($config['rewards'],true),
                'sec_attribute'         => $config['sec_attribute'],
                'sec_def_attribute'     => $config['sec_def_attribute'],
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
            'floor'             => $data['floor'],
            'moster_list'       => $data['moster_list'],
            'moster_level_list' => $data['moster_level_list'],
            'buff_limit'        => $data['buff_limit'],
            'repeat_rewards'    => json_decode($data['repeat_rewards'],true),
            'rewards'           => json_decode($data['rewards'],true),
            'sec_attribute'     => $data['sec_attribute'],
            'sec_def_attribute' => $data['sec_def_attribute'],
        ] : [];

    }

    public function getFloorAll(int $floor):array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();

        $list  = [];
        foreach ($table as $towerid => $value) 
        {
            if($value['floor'] > $floor) continue;
            
            $list[$towerid] = [
                'floor'                 => $value['floor'],
                'moster_list'           => $value['moster_list'],
                'moster_level_list'     => $value['moster_level_list'],
                'buff_limit'            => $value['buff_limit'],
                'repeat_rewards'        => json_decode($value['repeat_rewards'],true),
                'rewards'               => json_decode($value['rewards'],true),
                'sec_attribute'         => $value['sec_attribute'],
                'sec_def_attribute'     => $value['sec_def_attribute'],
            ];
        }

        return $list;
    }

    public function getFloor(int $floor):array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();

        $list  = [];
        foreach ($table as $towerid => $value) 
        {
            if($value['floor'] != $floor) continue;
            
            $list[] = $towerid;
        }

        return $list;
    }

}
