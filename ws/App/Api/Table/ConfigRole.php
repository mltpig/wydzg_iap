<?php
namespace App\Api\Table;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigRole as Model;

class ConfigRole
{
    use CoroutineSingleTon;

    protected $tableName = 'config_role';

    public function create():void
    {

        $columns = [
            'demonic_max'     => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'type'            => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'attack_base'     => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'hp_base'         => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'def_base'        => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'speed_base'      => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'equipment_level' => [ 'type'=> Table::TYPE_STRING ,'size'=> 500 ],
            'equipment_type'  => [ 'type'=> Table::TYPE_STRING ,'size'=> 500 ],
            'destiny_energy'  => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
        ];

        TableManager::getInstance()->add( $this->tableName , $columns , 5000 );

    }

    public function initTable():void
    {
        $table = TableManager::getInstance()->get($this->tableName);

        $tableConfig = Model::create()->all();
        
        foreach ($tableConfig as $key => $value) 
        {
            $level = $type = [];
            
            $levelConfig = explode('|',$value['equipment_level']);
            foreach ($levelConfig as $lvCon) 
            {
                list($lv,$number) = explode(';',$lvCon);
                $level[$lv] = $number;
            }

            $typeConfig = explode('|',$value['equipment_type']);
            foreach ($typeConfig as $pos => $number) 
            {
                $type[$pos+1] = $number;
            }

            $table->set($value['id'],[
                'demonic_max'     => $value['demonic_max'],
                'type'            => $value['type'],
                'attack_base'     => $value['attack_base'],
                'hp_base'         => $value['hp_base'],
                'def_base'        => $value['def_base'],
                'speed_base'      => $value['speed_base'],
                'equipment_level' => json_encode($level),
                'equipment_type'  => json_encode($type),
                'destiny_energy'  => $value['destiny_energy'],
            ]);
        }
    }

    public function getOne(int $level):array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();
        
        $data = $table->get($level);

        return $data ? [
            'demonic_max'     => $data['demonic_max'],
            'type'            => $data['type'],
            'attack_base'     => $data['attack_base'],
            'hp_base'         => $data['hp_base'],
            'def_base'        => $data['def_base'],
            'speed_base'      => $data['speed_base'],
            'equipment_level' => json_decode($data['equipment_level'],true),
            'equipment_type'  => json_decode($data['equipment_type'],true),
            'destiny_energy'  => $data['destiny_energy'],
        ] : [];

    }

}
