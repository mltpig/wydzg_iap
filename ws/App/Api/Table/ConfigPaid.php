<?php
namespace App\Api\Table;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigPaid as Model;

class ConfigPaid
{
    use CoroutineSingleTon;

    protected $tableName = 'config_paid';

    public function create():void
    {
        $columns = [
            'id'            => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'price'         => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'repeat_reward' => [ 'type'=> Table::TYPE_STRING ,'size'=> 200 ],
        ];

        TableManager::getInstance()->add( $this->tableName , $columns , 200 );
    }

    public function initTable():void
    {
        $table = TableManager::getInstance()->get($this->tableName);

        $tableConfig = Model::create()->all();
        foreach ($tableConfig as $key => $value) 
        {

            $reward = getFmtGoods(explode('=',$value['repeat_reward']));
            $reward['type'] = ConfigGoods::getInstance()->getOne($reward['gid']);

            $table->set($value['id'],[
                'id'            => $value['id'],
                'price'         => $value['price'],
                'repeat_reward' => json_encode($reward),
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
                'id'            => $config['id'],
                'price'         => $config['price'],
                'repeat_reward' => json_decode($config['repeat_reward'],true),
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
            'id'            => $data['id'],
            'price'         => $data['price'],
            'repeat_reward' => json_decode($data['repeat_reward'],true),
        ] : [];

    }

}
