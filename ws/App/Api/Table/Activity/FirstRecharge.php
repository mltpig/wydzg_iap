<?php
namespace App\Api\Table\Activity;
use Swoole\Table;
use App\Api\Table\ConfigGoods;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\Activity\FirstRecharge as Model;

class FirstRecharge
{
    use CoroutineSingleTon;

    protected $tableName = 'activity_first_recharge';

    public function create():void
    {
        $columns = [ 
            'id'              => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'day'             => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'reward'          => [ 'type'=> Table::TYPE_STRING ,'size'=> 500 ],
            'complete_type'   => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ], 
            'complete_params' => [ 'type'=> Table::TYPE_STRING ,'size'=> 100 ], 
        ];
        TableManager::getInstance()->add( $this->tableName , $columns , 10 );
    }

    public function initTable():void
    {
        $table = TableManager::getInstance()->get($this->tableName);

        $tableConfig = Model::create()->all();
        
        foreach ($tableConfig as $key => $value) 
        {
            $rewards  = [];
            $list = explode('|',$value['value']);
            foreach ($list as  $val) 
            {
                $reward = getFmtGoods(explode('=',$val));
                $reward['type'] = ConfigGoods::getInstance()->getOne($reward['gid'])['type'];
                $rewards[] = $reward;
            }
            $completeParams =  $value['complete_params'] ? explode('|',$value['complete_params']) : [];
            $table->set( $value['id'] , [ 
                'id' => $value['id'],
                'day' => $value['day'],
                'reward' => json_encode($rewards),
                'complete_type' => $value['complete_type'],
                'complete_params' => json_encode($completeParams),
            ] );
        }

    }

    public function getOne(int $id):array
    {
        $table = TableManager::getInstance()->get($this->tableName);

        if(!$table->count()) $this->initTable();

        $data = $table->get($id);

        return $data ? [
            'id'              => $data['id'],
            'day'             => $data['day'],
            'reward'          => json_decode($data['reward'],true),
            'complete_type'   => $data['complete_type'],
            'complete_params' => json_decode($data['complete_params'],true),
        ] : [];

    }

    public function getAll():array
    {
        $table = TableManager::getInstance()->get($this->tableName);

        if(!$table->count()) $this->initTable();
        $list  = [];
        foreach ($table as $id => $config) 
        {

            $list[] = [ 
                'id'              => $config['id'],
                'day'             => $config['day'],
                'reward'          => json_decode($config['reward'],true),
                'complete_type'   => $config['complete_type'],
                'complete_params' => json_decode($config['complete_params'],true),
            ];
        }
        return $list;

    }

}
