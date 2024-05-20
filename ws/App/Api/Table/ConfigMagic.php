<?php
namespace App\Api\Table;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigMagic as Model;

class ConfigMagic
{
    use CoroutineSingleTon;

    protected $tableName = 'config_magic';

    public function create():void
    {
        $columns = [
            'type'                  => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'quality'               => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'icon'                  => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'effect_id'             => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'item_id'               => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'stone_num'             => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'stone_unlock'          => [ 'type'=> Table::TYPE_STRING ,'size'=> 100 ],
            'stone_type'            => [ 'type'=> Table::TYPE_STRING ,'size'=> 100 ],
            'combine_id'            => [ 'type'=> Table::TYPE_STRING ,'size'=> 100 ],
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
                'type'                  => $value['type'],
                'quality'               => $value['quality'],
                'icon'                  => $value['icon'],
                'effect_id'             => $value['effect_id'],
                'item_id'               => $value['item_id'],
                'stone_num'             => $value['stone_num'],
                'stone_unlock'          => $value['stone_unlock'],
                'stone_type'            => $value['stone_type'],
                'combine_id'            => $value['combine_id'],
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
                'type'                  => $config['type'],
                'quality'               => $config['quality'],
                'icon'                  => $config['icon'],
                'effect_id'             => $config['effect_id'],
                'item_id'               => $config['item_id'],
                'stone_num'             => $config['stone_num'],
                'stone_unlock'          => $config['stone_unlock'],
                'stone_type'            => $config['stone_type'],
                'combine_id'            => $config['combine_id'],
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
            'type'                  => $data['type'],
            'quality'               => $data['quality'],
            'icon'                  => $data['icon'],
            'effect_id'             => $data['effect_id'],
            'item_id'               => $data['item_id'],
            'stone_num'             => $data['stone_num'],
            'stone_unlock'          => $data['stone_unlock'],
            'stone_type'            => $data['stone_type'],
            'combine_id'            => $data['combine_id'],
        ] : [];
    }

    public function getQuality(int $quality)
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();

        $list  = [];
        foreach ($table as $id => $config)
        {
            if($config['quality'] != $quality) continue;

            $list[ $id ] = [
                'type'                  => $config['type'],
                'quality'               => $config['quality'],
                'icon'                  => $config['icon'],
                'effect_id'             => $config['effect_id'],
                'item_id'               => $config['item_id'],
                'stone_num'             => $config['stone_num'],
                'stone_unlock'          => $config['stone_unlock'],
                'stone_type'            => $config['stone_type'],
                'combine_id'            => $config['combine_id'],
            ];
        }
        return $list;
    }

    public function getAllCombineId():array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();

        $list  = [];
        foreach ($table as $id => $value) 
        {
            if(!$value['combine_id']) continue;
            
            $list[ $value['combine_id'] ][$id] = 1;
        }

        return $list;
    }

    public function getItemId()
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();

        $list  = [];
        foreach ($table as $id => $config)
        {
            $list[ $config['item_id'] ] = [
                'id'                    => $id,
                'type'                  => $config['type'],
                'quality'               => $config['quality'],
                'icon'                  => $config['icon'],
                'effect_id'             => $config['effect_id'],
                'item_id'               => $config['item_id'],
                'stone_num'             => $config['stone_num'],
                'stone_unlock'          => $config['stone_unlock'],
                'stone_type'            => $config['stone_type'],
                'combine_id'            => $config['combine_id'],
            ];
        }
        return $list;
    }
}
