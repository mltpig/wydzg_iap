<?php
namespace App\Api\Table;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigGoodsBox as Model;

class ConfigGoodsBox
{
    use CoroutineSingleTon;

    protected $tableName = 'config_goods_box';

    public function create():void
    {
        $columns = [
            'box_id'       => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'item_id'      => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'min_num'      => [ 'type'=> Table::TYPE_INT ,'size'=> 4 ],
            'max_num'      => [ 'type'=> Table::TYPE_INT ,'size'=> 4 ],
            'random_type'  => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'probability'  => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'is_notice'    => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
        ];
    
        TableManager::getInstance()->add( $this->tableName , $columns , 5000 );

    }

    public function initTable():void
    {
        $table = TableManager::getInstance()->get($this->tableName);

        $tableConfig = Model::create()->all();
        foreach ($tableConfig as $key => $value) 
        {
            $table->set($value['id'],[
                'box_id'      => $value['box_id'],
                'item_id'     => $value['item_id'],
                'min_num'     => $value['min_num'],
                'max_num'     => $value['max_num'],
                'random_type' => $value['random_type'],
                'probability' => $value['probability'],
                'is_notice'   => $value['is_notice'],
            ]);
        }

    }

    public function getAll(int $boxId):array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();

        $map  = $pool  = [];
        foreach ($table as $id => $config) 
        {
            if($config['box_id'] != $boxId) continue;
            $map[ $id ] = [
                'item_id'     => $config['item_id'],
                'min_num'     => $config['min_num'],
                'max_num'     => $config['max_num'],
                'random_type' => $config['random_type'],
                'probability' => $config['probability'],
                'is_notice'   => $config['is_notice'],
            ];

            $pool[ $id ] = $config['probability'];
        }

        return array( $map , $pool);
    }

}
