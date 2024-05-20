<?php
namespace App\Api\Table\Activity;
use Swoole\Table;
use App\Api\Table\ConfigGoods;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\Activity\ConfigFund as Model;

class ConfigFund
{
    use CoroutineSingleTon;

    protected $tableName = 'config_fund';

    public function create():void
    {
        $columns = [ 
            'fund_type'     => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
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
                $freeReward         = [];
                $freeReward         = getFmtGoods(explode('=',$detail['freeReward']));
                $freeReward['type'] = ConfigGoods::getInstance()->getOne($freeReward['gid'])['type'];

                $paidReward         = [];
                $paid        = explode('|',$detail['paidReward']);
                foreach ($paid as  $val) 
                {
                    $paids             = getFmtGoods(explode('=',$val));
                    $paids['type']     = ConfigGoods::getInstance()->getOne($paids['gid'])['type'];
                    $paidReward[]      = $paids;
                }

                $list[] = [
                    'id'                => $detail['id'],
                    'completeType'      => $detail['completeType'],
                    'completeParams'    => explode('|',$detail['completeParams']),
                    'freeReward'        => $freeReward,
                    'paidReward'        => $paidReward,
                ];
            }

            $table->set($config['group'],[ 
                'fund_type'     => $config['fund_type'],
                'name'          => $config['name'],
                'price'         => $config['price'],
                'recharge_id'   => $config['recharge_id'],
                'data'          => json_encode($list),
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
                'fund_type'     => $config['fund_type'],
                'name'          => $config['name'],
                'price'         => $config['price'],
                'recharge_id'   => $config['recharge_id'],
                'data'          => json_decode($config['data'],true),
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
            'fund_type'     => $data['fund_type'],
            'name'          => $data['name'],
            'price'         => $data['price'],
            'recharge_id'   => $data['recharge_id'],
            'data'          => json_decode($data['data'],true),
        ]: [];
    }

}
