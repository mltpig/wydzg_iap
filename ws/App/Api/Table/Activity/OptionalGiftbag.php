<?php
namespace App\Api\Table\Activity;
use Swoole\Table;
use App\Api\Table\ConfigGoods;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\Activity\OptionalGiftbag as Model;

class OptionalGiftbag
{
    use CoroutineSingleTon;

    protected $tableName = 'activity_optional_giftbag';

    public function create():void
    {
        $columns = [ 
            'giftbag_type'  => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'quality'       => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'vip_unlock'    => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'choice_num'    => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'limit_num'     => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'price'         => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'recharge_id'   => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'name'          => [ 'type'=> Table::TYPE_STRING ,'size'=> 100 ],
            'basics_reward' => [ 'type'=> Table::TYPE_STRING ,'size'=> 100 ],
            'data'          => [ 'type'=> Table::TYPE_STRING ,'size'=> 5000 ],
        ];
        
        TableManager::getInstance()->add( $this->tableName , $columns , 10 );
    }

    public function initTable():void
    {
        $table = TableManager::getInstance()->get($this->tableName);

        $tableConfig = Model::create()->all();
        
        foreach ($tableConfig as $key => $config) 
        {
            
            $basics = getFmtGoods(explode('=',$config['basics_reward']));
            $basics['type'] = ConfigGoods::getInstance()->getOne($basics['gid'])['type'];

            $rewards  = [];
            $data = json_decode($config['data'],true);
            foreach ($data as  $detail) 
            {
                $goods = getFmtGoods(explode('=',$detail['reward']));
                $goods['type'] = ConfigGoods::getInstance()->getOne($goods['gid'])['type'];

                $rewards[$detail['type']][$detail['id']] = $goods;
            }
            
            $table->set( $config['group'] , [ 
                'giftbag_type'  => $config['giftbag_type'],
                'quality'       => $config['quality'],
                'vip_unlock'    => $config['vip_unlock'],
                'choice_num'    => $config['choice_num'],
                'limit_num'     => $config['limit_num'],
                'name'          => $config['name'],
                'price'         => $config['price'],
                'recharge_id'   => $config['recharge_id'],
                'basics_reward' => json_encode($basics),
                'data'          => json_encode($rewards)
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
                'giftbag_type'  => $config['giftbag_type'],
                'quality'       => $config['quality'],
                'vip_unlock'    => $config['vip_unlock'],
                'limit_num'     => $config['limit_num'],
                'choice_num'    => $config['choice_num'],
                'name'          => $config['name'],
                'price'         => $config['price'],
                'recharge_id'   => $config['recharge_id'],
                'basics_reward' => json_decode($config['basics_reward'],true),
                'data'          => json_decode($config['data'],true)
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
            'giftbag_type'  => $data['giftbag_type'],
            'quality'       => $data['quality'],
            'vip_unlock'    => $data['vip_unlock'],
            'limit_num'     => $data['limit_num'],
            'choice_num'    => $data['choice_num'],
            'name'          => $data['name'],
            'price'         => $data['price'],
            'recharge_id'   => $data['recharge_id'],
            'basics_reward' => json_decode($data['basics_reward'],true),
            'data'          => json_decode($data['data'],true)
        ]: [];

    }

}
