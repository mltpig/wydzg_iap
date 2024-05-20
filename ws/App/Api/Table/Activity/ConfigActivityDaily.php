<?php
namespace App\Api\Table\Activity;
use Swoole\Table;
use App\Api\Table\ConfigGoods;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\Activity\ConfigActivityDaily as Model;

class ConfigActivityDaily
{
    use CoroutineSingleTon;

    protected $tableName = 'config_activity_daily';

    public function create():void
    {
        $columns = [ 
            'daily_type'    => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'name'          => [ 'type'=> Table::TYPE_STRING ,'size'=> 128 ],
            'price'         => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'recharge_id'   => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'data'          => [ 'type'=> Table::TYPE_STRING ,'size'=>  80000],
        ];
        
        TableManager::getInstance()->add( $this->tableName , $columns ,  1024);
    }

    public function initTable():void
    {
        $table = TableManager::getInstance()->get($this->tableName);

        $tableConfig = Model::create()->all();
        
        foreach ($tableConfig as $key => $config) 
        {
            $list = [];
            $data = json_decode($config['data'],true);
            foreach ($data as  $detail)
            {
                $cost         = [];
                $cost         = getFmtGoods(explode('=',$detail['cost']));
                $cost['type'] = ConfigGoods::getInstance()->getOne($cost['gid'])['type'];

                $freeReward         = [];
                $freeReward         = getFmtGoods(explode('=',$detail['freeReward']));
                $freeReward['type'] = ConfigGoods::getInstance()->getOne($freeReward['gid'])['type'];

                $paidReward         = [];
                $paidReward         = getFmtGoods(explode('=',$detail['paidReward']));
                $paidReward['type'] = ConfigGoods::getInstance()->getOne($paidReward['gid'])['type'];

                $list[] = [
                    'id'                => $detail['id'],
                    'cost'              => $cost,
                    'freeReward'        => $freeReward,
                    'paidReward'        => $paidReward,
                ];
            }

            $table->set($config['group'],[ 
                'daily_type'        => $config['daily_type'],
                'name'              => $config['name'],
                'price'             => $config['price'],
                'recharge_id'       => $config['recharge_id'],
                'data'              => json_encode($list),
            ] );
        }
    }

    public function getAll():array
    {
        $table = TableManager::getInstance()->get($this->tableName);

        if(!$table->count()) $this->initTable();

        $list  = [];
        foreach ($table as $group => $config) 
        {
            $list[$group] = [ 
                'daily_type'        => $config['daily_type'],
                'name'              => $config['name'],
                'price'             => $config['price'],
                'recharge_id'       => $config['recharge_id'],
                'data'              => json_decode($config['data'],true),
            ];
        }

        return $list;
    }

    public function getOne(int $group):array
    {
        $table = TableManager::getInstance()->get($this->tableName);

        if(!$table->count()) $this->initTable();
        
        $data = $table->get($group);

        return $data ?  [ 
            'daily_type'    => $data['daily_type'],
            'name'          => $data['name'],
            'price'         => $data['price'],
            'recharge_id'   => $data['recharge_id'],
            'data'          => json_decode($data['data'],true),
        ]: [];
    }

}
