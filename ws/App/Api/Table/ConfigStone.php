<?php
namespace App\Api\Table;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigStone as Model;

class ConfigStone
{
    use CoroutineSingleTon;

    protected $tableName = 'config_stone';

    public function create():void
    {
        $columns = [
            'name'                  => [ 'type'=> Table::TYPE_STRING ,'size'=> 68 ],
            'desc'                  => [ 'type'=> Table::TYPE_STRING ,'size'=> 128 ],
            'type'                  => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'quality'               => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'icon'                  => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'effect_id'             => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'compound_item_id'      => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'compound_num'          => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
        ];

        TableManager::getInstance()->add( $this->tableName , $columns , 1024 );

    }

    public function initTable():void
    {
        $table = TableManager::getInstance()->get($this->tableName);

        $tableConfig = Model::create()->all();
        foreach ($tableConfig as $key => $value) 
        {
            $table->set($value['id'],[
                'name'                  => $value['name'],
                'desc'                  => $value['desc'],
                'type'                  => $value['type'],
                'quality'               => $value['quality'],
                'icon'                  => $value['icon'],
                'effect_id'             => $value['effect_id'],
                'compound_item_id'      => $value['compound_item_id'],
                'compound_num'          => $value['compound_num'],
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
                'name'                  => $config['name'],
                'desc'                  => $config['desc'],
                'type'                  => $config['type'],
                'quality'               => $config['quality'],
                'icon'                  => $config['icon'],
                'effect_id'             => $config['effect_id'],
                'compound_item_id'      => $config['compound_item_id'],
                'compound_num'          => $config['compound_num'],
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
            'name'                  => $data['name'],
            'desc'                  => $data['desc'],
            'type'                  => $data['type'],
            'quality'               => $data['quality'],
            'icon'                  => $data['icon'],
            'effect_id'             => $data['effect_id'],
            'compound_item_id'      => $data['compound_item_id'],
            'compound_num'          => $data['compound_num'],
        ] : [];
    }

    public function getCompoundItem(array $type):array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();

        $list  = [];
        foreach ($table as $id => $config)
        {
            if(!in_array($config['type'],$type)) continue;
            if(empty($config['compound_item_id'])) continue;

            $list[ $id ] = [
                'compound_item_id'      => $config['compound_item_id'],
                'compound_num'          => $config['compound_num'],
            ];
        }
        return $list;
    }
}
