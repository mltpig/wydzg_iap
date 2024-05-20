<?php
namespace App\Api\Table;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigEquipmentAdvanceUp as Model;

class ConfigEquipmentAdvanceUp
{
    use CoroutineSingleTon;

    protected $tableName = 'config_equipment_advance_up';

    public function create():void
    {
        $columns = [
            'big_cost'               => [ 'type'=> Table::TYPE_STRING ,'size'=> 128 ],
            'equipment_up'           => [ 'type'=> Table::TYPE_STRING ,'size'=> 20 ],
            'special_skill'          => [ 'type'=> Table::TYPE_STRING ,'size'=> 20 ],
            'level_limit'            => [ 'type'=> Table::TYPE_INT ,'size'=> 20 ],
        ];

        TableManager::getInstance()->add( $this->tableName , $columns ,  2048);

    }

    public function initTable():void
    {
        $table = TableManager::getInstance()->get($this->tableName);

        $tableConfig = Model::create()->all();
        foreach ($tableConfig as $key => $value) 
        {

            $cost  = [];
            $list = explode('|',$value['big_cost']);
            foreach ($list as  $val) 
            {
                if(empty($val)) continue;

                list($gid, $num) = explode('=',$val);
                $cost[] = ['gid' => $gid, 'num' => $num];
            }

            $table->set($value['id'],[
                'big_cost'      => json_encode($cost),
                'equipment_up'  => $value['equipment_up'],
                'special_skill' => $value['special_skill'],
                'level_limit'   => $value['level_limit'],
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
                'big_cost'          => json_decode($config['big_cost'],true),
                'equipment_up'      => $config['equipment_up'],
                'special_skill'     => $config['special_skill'],
                'level_limit'       => $config['level_limit'],
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
            'big_cost'          => json_decode($data['big_cost'],true),
            'equipment_up'      => $data['equipment_up'],
            'special_skill'     => $data['special_skill'],
            'level_limit'       => $data['level_limit'],
        ] : [];

    }

    public function getHmAll():array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();

        $list  = [];
        foreach ($table as $id => $config)
        {
            if(empty($config['special_skill'])) continue;

            $list[ $id ] = 0;
        }
        return $list;
    }
}
