<?php
namespace App\Api\Table;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigTree as Model;

class ConfigTree
{
    use CoroutineSingleTon;

    protected $tableName = 'config_tree';

    public function create():void
    {
        $columns = [
            'cost'    => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'quality' => [ 'type'=> Table::TYPE_STRING ,'size'=> 500 ],
            'time'    => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'reward'  => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'weight'  => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
        ];

        TableManager::getInstance()->add( $this->tableName , $columns , 100 );

    }

    public function initTable():void
    {
        $table = TableManager::getInstance()->get($this->tableName);

        $tableConfig = Model::create()->all();
        foreach ($tableConfig as $key => $value) 
        {
            $qualitys = explode('|',$value['quality']);
            $quality  = [];
            foreach ($qualitys as $key => $number) 
            {
                $quality[$key + 1] = intval($number);
            }

            $reward =  $value['reward'] ? getFmtGoods( explode('=',$value['reward'])) : [];
            if($reward) $reward['type'] = ConfigGoods::getInstance()->getOne($reward['gid'])['type'];
            
            $cost = getFmtGoods( explode('=',$value['cost']));
            $cost['type'] = ConfigGoods::getInstance()->getOne($cost['gid'])['type'];

            $table->set($value['id'],[
                'cost'    => json_encode($cost),
                'quality' => json_encode($quality),
                'time'    => $value['time'],
                'reward'  => json_encode($reward),
                'weight'  => $value['weight'],
            ]);
        }

    }

    public function getAll():array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();
        
        $list  = [];
        foreach ($table as $level => $config) 
        {

            $list[ $level ] = [
                'cost'    => json_decode( $config['cost'],true ),
                'quality' => json_decode( $config['quality'],true ),
                'time'    => $config['time'],
                'reward'  => json_decode( $config['reward'],true ),
                'weight'  => $config['weight'],
            ];
        }

        return $list;
    }

    public function getOne(int $level):array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();
        
        $data = $table->get($level);
        return $data ? [
                'cost'    => json_decode( $data['cost'],true ),
                'quality' => json_decode( $data['quality'],true ),
                'time'    => $data['time'],
                'reward'  => json_decode( $data['reward'],true ),
                'weight'  => $data['weight'],
        ] : [];

    }
    
    public function getMaxLevel():int
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();
        return $table->count();
    }

    public function getRewardWeight(int $level):array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();

        $weight =  $list   = [];
        foreach ($table as $key => $value) 
        {
            if($key > $level ) continue;

            $reward = json_decode( $value['reward'],true );
            if(!$reward ) continue;

            $list[ $reward['gid'] ]   = $reward;
            $weight[ $reward['gid'] ] = $value['weight'];
        }

        return [$weight,$list];
    
    }
}
