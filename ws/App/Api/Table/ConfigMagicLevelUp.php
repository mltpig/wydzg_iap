<?php
namespace App\Api\Table;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigMagicLevelUp as Model;

class ConfigMagicLevelUp
{
    use CoroutineSingleTon;

    protected $tableName = 'config_magic_level_up';

    public function create():void
    {
        $columns = [
            'cost'                  => [ 'type'=> Table::TYPE_STRING ,'size'=> 128 ],
            'attack'                => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'hp'                    => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'defence'               => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'unlock'                => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
        ];

        TableManager::getInstance()->add( $this->tableName , $columns , 3000 );

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
                $cost_val = getFmtGoods(explode('=',$val));
                $cost_val['type'] = ConfigEquipSpecial::getInstance()->getOne($cost_val['gid']) ? GOODS_TYPE_2 : GOODS_TYPE_1;
                $cost[] = $cost_val;
            }

            $table->set($value['id'],[
                'cost'      => json_encode($cost),
                'attack'    => $value['attack'],
                'hp'        => $value['hp'],
                'defence'   => $value['defence'],
                'unlock'    => $value['unlock'],
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
                'cost'        => json_decode($config['cost'],true),
                'attack'      => $config['attack'],
                'hp'          => $config['hp'],
                'defence'     => $config['defence'],
                'unlock'      => $config['unlock'],
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
            'cost'        => json_decode($data['cost'],true),
            'attack'      => $data['attack'],
            'hp'          => $data['hp'],
            'defence'     => $data['defence'],
            'unlock'      => $data['unlock'],
        ] : [];

    }

    public function getLvSpend(int $index):array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();

        $list  = [];
        foreach ($table as $id => $config)
        {
            if($id > $index) continue;

            $list[ $id ] = [
                'cost'        => json_decode($config['cost'],true),
                'attack'      => $config['attack'],
                'hp'          => $config['hp'],
                'defence'     => $config['defence'],
                'unlock'      => $config['unlock'],
            ];
        }
        return $list;
    }
}
