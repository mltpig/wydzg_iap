<?php
namespace App\Api\Table;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigParadiseReward as Model;

class ConfigParadiseReward
{
    use CoroutineSingleTon;

    protected $tableName = 'config_paradise_reward';

    public function create():void
    {

        $columns = [
            'level'      => [ 'type'=> Table::TYPE_INT ,'size'=> 50 ],
            'weight'     => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'time_param' => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'reward'     => [ 'type'=> Table::TYPE_STRING ,'size'=> 500 ],
        ];

        TableManager::getInstance()->add( $this->tableName , $columns , 100 );

    }

    public function initTable():void
    {
        $table       = TableManager::getInstance()->get($this->tableName);
        $tableConfig = Model::create()->all();
        foreach ($tableConfig as $key => $value) 
        {
            $reward = getFmtGoods( explode('=',$value['reward']) );
            $reward['type'] = ConfigGoods::getInstance()->getOne($reward['gid'])['type'];
            $table->set($value['id'],[
                'level'      => $value['level'],
                'weight'     => $value['weight'],
                'time_param' => $value['time_param'],
                'reward'     => $value['reward'] ? json_encode($reward) : '[]',
            ]);

        }

    }


    public function getOne(int $level):array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();
        
        $data = $table->get($level);
        return $data ? [
                'level'      => $data['level'],
                'weight'     => $data['weight'],
                'time_param' => $data['time_param'],
                'reward'     => json_decode( $data['reward'],true ),
        ] : [];

    }
    
    public function getReward(int $level ):int
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();
        $list = [];
        foreach ($table as $id => $value) 
        {
            if($value['level'] != $level) continue;
            $list[$id] = $value['weight'];
        }

        return randTable($list);
    }
}
