<?php
namespace App\Api\Table;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigGoods as Model;

class ConfigGoods
{
    use CoroutineSingleTon;

    protected $tableName = 'config_goods';

    public function create():void
    {
        $columns = [
            'is_hidden'    => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'display_num'  => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'can_bag_use'  => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'type'         => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'name'         => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'params'       => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
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
                'name'         => $value['name'],
                'is_hidden'    => $value['is_hidden'],
                'type'         => $value['type'],
                'display_num'  => $value['display_num'],
                'can_bag_use'  => $value['can_bag_use'],
                'params'       => json_encode(explode('|',$value['params'])),
            ]);
        }

    }

    public function getOne(int $id):array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();
        
        $data = $table->get($id);

        return $data ? [
            'name'         => $data['name'],
            'is_hidden'    => $data['is_hidden'],
            'type'         => $data['type'],
            'display_num'  => $data['display_num'],
            'can_bag_use'  => $data['can_bag_use'],
            'params'       => json_decode($data['params']),
        ] : [];

    }

}
