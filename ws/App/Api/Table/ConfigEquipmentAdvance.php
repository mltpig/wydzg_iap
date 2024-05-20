<?php
namespace App\Api\Table;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigEquipmentAdvance as Model;

class ConfigEquipmentAdvance
{
    use CoroutineSingleTon;

    protected $tableName = 'config_equipment_advance';

    public function create():void
    {
        $columns = [
            'attack'                 => [ 'type'=> Table::TYPE_INT ,'size'=> 10 ],
            'hp'                     => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'defence'                => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'cost'                   => [ 'type'=> Table::TYPE_STRING ,'size'=> 128 ],
        ];

        TableManager::getInstance()->add( $this->tableName , $columns , 20000 );

    }

    public function initTable():void
    {
        $table = TableManager::getInstance()->get($this->tableName);

        $tableConfig = Model::create()->all();
        foreach ($tableConfig as $key => $value) 
        {

            $cost  = [];
            $list = explode('|',$value['cost']);
            foreach ($list as  $val) 
            {
                list($gid, $num) = explode('=',$val);
                $cost[] = ['gid' => $gid, 'num' => $num];
            }

            $table->set($value['id'],[
                'attack'                => $value['attack'],
                'hp'                    => $value['hp'],
                'defence'               => $value['defence'],
                'cost'                  => json_encode($cost),
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
                'attack'                => $config['attack'],
                'hp'                    => $config['hp'],
                'defence'               => $config['defence'],
                'cost'                  => json_decode($config['cost'],true),
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
            'attack'    => $data['attack'],
            'hp'        => $data['hp'],
            'defence'   => $data['defence'],
            'cost'      => json_decode($data['cost'],true),
        ] : [];

    }
}
