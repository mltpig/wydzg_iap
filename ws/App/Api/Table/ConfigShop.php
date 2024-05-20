<?php
namespace App\Api\Table;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigShop as Model;

class ConfigShop
{
    use CoroutineSingleTon;

    protected $tableName = 'config_shop';

    public function create():void
    {
        $columns = [
            'id'              => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'name'            => [ 'type'=> Table::TYPE_STRING ,'size'=> 500 ],
            'shop_type'       => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'category'        => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'reward'          => [ 'type'=> Table::TYPE_STRING ,'size'=> 500 ],
            'price'           => [ 'type'=> Table::TYPE_STRING ,'size'=> 500 ],
            'recharge_id'     => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'old_price'       => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'price_add'       => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'is_double'       => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'buy_limit'       => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'buy_limit_type'  => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
        ];

        TableManager::getInstance()->add( $this->tableName , $columns , 200 );
    }

    public function initTable():void
    {
        $table = TableManager::getInstance()->get($this->tableName);

        $tableConfig = Model::create()->all();
        foreach ($tableConfig as $key => $value) 
        {
            if($value['category'])
            {
                $price = ['gid' => null,'type' => null ,'num' => $value['price'] ];
            }else{
                //免费
                if(!$value['price'])
                {
                    $price = ['gid' => null,'type' => null ,'num' => $value['price'] ];
                }else{
                    $price = getFmtGoods(explode('=',$value['price']));
                    $price['type'] = ConfigGoods::getInstance()->getOne($price['gid'])['type'];
                }
            }
            
            $rewards = [];
            $list = explode('|',$value['reward']);
            foreach ($list as $detail) 
            {
                $config = getFmtGoods(explode('=',$detail));
                $config['type'] = ConfigGoods::getInstance()->getOne($config['gid'])['type'];
                $rewards[] = $config;
            }

        
            $table->set($value['id'],[
                'id'              => $value['id'],
                'name'            => $value['name'],
                'shop_type'       => $value['shop_type'],
                'category'        => $value['category'],
                'recharge_id'     => $value['recharge_id'],
                'old_price'       => $value['old_price'],
                'price_add'       => $value['price_add'],
                'is_double'       => $value['is_double'],
                'buy_limit'       => $value['buy_limit'],
                'buy_limit_type'  => $value['buy_limit_type'],
                'price'           => json_encode($price),
                'reward'          => json_encode($rewards),
            ]);
        }

    } 
    

    public function getAll(int $shopType):array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();
        
        $list  = [];
        foreach ($table as $id => $config) 
        {
            if($shopType != 0)
            {
                if($config['shop_type'] != $shopType ) continue;
            }
            $list[ $id ] = [
                'id'              => $config['id'],
                'name'            => $config['name'],
                'shop_type'       => $config['shop_type'],
                'category'        => $config['category'],
                'recharge_id'     => $config['recharge_id'],
                'old_price'       => $config['old_price'],
                'price_add'       => $config['price_add'],
                'is_double'       => $config['is_double'],
                'buy_limit'       => $config['buy_limit'],
                'buy_limit_type'  => $config['buy_limit_type'],
                'price'           => json_decode($config['price'],true),
                'reward'          => json_decode($config['reward'],true),
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
            'id'              => $data['id'],
            'name'            => $data['name'],
            'shop_type'       => $data['shop_type'],
            'category'        => $data['category'],
            'recharge_id'     => $data['recharge_id'],
            'old_price'       => $data['old_price'],
            'price_add'       => $data['price_add'],
            'is_double'       => $data['is_double'],
            'buy_limit'       => $data['buy_limit'],
            'buy_limit_type'  => $data['buy_limit_type'],
            'reward'          => json_decode($data['reward'],true),
            'price'           => json_decode($data['price'],true),
        ] : [];

    }

}
