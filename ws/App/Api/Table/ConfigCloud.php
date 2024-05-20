<?php
namespace App\Api\Table;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigCloud as Model;

class ConfigCloud
{
    use CoroutineSingleTon;

    protected $tableName = 'config_cloud';

    public function create():void
    {
        $columns = [
            'id'            => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'name'          => [ 'type'=> Table::TYPE_STRING ,'size'=> 100 ],
            'cost'          => [ 'type'=> Table::TYPE_STRING ,'size'=> 100 ],
            'prim_attack'   => [ 'type'=> Table::TYPE_STRING ,'size'=> 100 ],
            'resource_type' => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'type'          => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
        ];

        TableManager::getInstance()->add( $this->tableName , $columns , 5000 );

    }

    public function initTable():void
    {
        $table = TableManager::getInstance()->get($this->tableName);

        $tableConfig = Model::create()->all();
        foreach ($tableConfig as $key => $value) 
        {
            $cost = [];
            if($value['cost'])
            {
                $cost = getFmtGoods(explode('=',$value['cost']));
                $cost['type'] = ConfigGoods::getInstance()->getOne($cost['gid'])['type'];
            }

            $table->set($value['id'],[
                'name'           => $value['name'],
                'cost'           => json_encode($cost),
                'prim_attack'    => $value['prim_attack'],
                'resource_type'  => $value['resource_type'],
                'type'           => $value['type'],
            ]);
        }

    }

    public function getOne(int $id):array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();
        
        $data = $table->get($id);

        return $data ? [
            'name'          => $data['name'],
            'cost'          => json_decode($data['cost'],true),
            'type'          => $data['type'],
            'prim_attack'   => $data['prim_attack'],
            'resource_type' => $data['resource_type'],
        ] : [];

    }

    public function getIntCloud():int
    {
        // $table = TableManager::getInstance()->get($this->tableName);
        // if(!$table->count()) $this->initTable();

        $cloud = 10001;

        // foreach ($table as $key => $value) 
        // {
        //     $cost = json_decode($value['cost'],true);
        //     if($cost) continue;
        //     $cloud = $key;
        // }

        return $cloud;

    }

}
