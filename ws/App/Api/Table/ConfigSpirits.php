<?php
namespace App\Api\Table;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigSpirits as Model;

class ConfigSpirits
{
    use CoroutineSingleTon;

    protected $tableName = 'config_spirits';

    public function create():void
    {
        $columns = [
            'quality'               => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'weight'                => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'active_skill'          => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'gvg_active_skill'      => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'passive_skill'         => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'compound_item_id'      => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'compound_num'          => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'combine_id'            => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
        ];

        TableManager::getInstance()->add( $this->tableName , $columns , 200 );

    }

    public function initTable():void
    {
        $table = TableManager::getInstance()->get($this->tableName);

        $tableConfig = Model::create()->all();
        foreach ($tableConfig as $key => $value) 
        {

            $table->set($value['id'],[
                'quality'               => $value['quality'],
                'weight'                => $value['weight'],
                'active_skill'          => $value['active_skill'],
                'gvg_active_skill'      => $value['gvg_active_skill'],
                'passive_skill'         => $value['passive_skill'],
                'compound_item_id'      => $value['compound_item_id'],
                'compound_num'          => $value['compound_num'],
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
                'quality'           => $config['quality'],
                'weight'            => $config['weight'],
                'active_skill'      => $config['active_skill'],
                'gvg_active_skill'  => $config['gvg_active_skill'],
                'passive_skill'     => $config['passive_skill'],
                'compound_item_id'  => $config['compound_item_id'],
                'compound_num'      => $config['compound_num'],
                'combine_id'        => $config['combine_id'],
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
            'quality'               => $data['quality'],
            'weight'                => $data['weight'],
            'active_skill'          => $data['active_skill'],
            'gvg_active_skill'      => $data['gvg_active_skill'],
            'passive_skill'         => $data['passive_skill'],
            'compound_item_id'      => $data['compound_item_id'],
            'compound_num'          => $data['compound_num'],
            'combine_id'            => $data['combine_id'],
        ] : [];

    }

    public function getAllWeight(int $quality):array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();

        $list  = [];
        foreach ($table as $spiritid => $value) 
        {
            if(!$value['weight']) continue;
            if($value['quality'] != $quality) continue;
            
            $list[ $spiritid ] = $value['weight'];
        }

        return $list;
    }

    public function getAllCombineId():array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();

        $list  = [];
        foreach ($table as $spiritid => $value) 
        {
            if(!$value['combine_id']) continue;
            
            $list[ $value['combine_id'] ][$spiritid] = 1;
        }

        return $list;
    }

}
