<?php
namespace App\Api\Table;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigParadiseLevel as Model;

class ConfigParadiseLevel
{
    use CoroutineSingleTon;

    protected $tableName = 'config_paradise_level';

    public function create():void
    {
        $columns = [
            'cost'       => [ 'type'=> Table::TYPE_STRING ,'size'=> 100 ],
            'weight'     => [ 'type'=> Table::TYPE_STRING ,'size'=> 100 ],
        ];

        TableManager::getInstance()->add( $this->tableName , $columns , 100 );

    }

    public function initTable():void
    {
        $table       = TableManager::getInstance()->get($this->tableName);
        $tableConfig = Model::create()->all();
        foreach ($tableConfig as $key => $value) 
        {
            $cost = [];
            if($value['cost'])
            {
                $cost = getFmtGoods( explode('=',$value['cost']) );
                $cost['type'] = ConfigGoods::getInstance()->getOne($cost['gid'])['type'];
            }

            $table->set($value['id'],[
                'cost'       => json_encode($cost),
                'weight'     => json_encode(explode('|',$value['weight'])),
            ]);
        }

    }


    public function getOne(int $level):array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();
        
        $data = $table->get($level);

        return $data ? [
                'cost'       => json_decode( $data['cost'],true ),
                'weight'     => json_decode( $data['weight'],true ),
        ] : [];

    }

    public function getRewardLevel(int $level = 1 ):int
    {
        $config = $this->getOne($level);
        return randTable($config['weight']) + 1;
    }
    
}
