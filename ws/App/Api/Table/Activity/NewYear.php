<?php
namespace App\Api\Table\Activity;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\Activity\NewYear as Model;
use App\Api\Table\ConfigGoods;

class NewYear
{
    use CoroutineSingleTon;

    protected $tableName = 'activity_new_year';

    public function create():void
    {
        $columns = [ 
            'id'        => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'task_need' => [ 'type'=> Table::TYPE_STRING ,'size'=> 500 ],
            'value'     => [ 'type'=> Table::TYPE_STRING ,'size'=> 500 ]
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

            
            $table->set( $value['id'] , [ 
                'id'        => $value['id'],
                'task_need' => json_encode(explode('|',$value['task_need'])),
                'value'     => json_encode($rewards),
            ] );
        }

    }

    public function getAll():array
    {
        $table = TableManager::getInstance()->get($this->tableName);

        if(!$table->count()) $this->initTable();

        $list  = [];
        foreach ($table as $id => $config) 
        {

            $list[ $config['id'] ] = [ 
                'id'        => $config['id'],
                'task_need' => json_decode($config['task_need'],true),
                'reward'    => json_decode($config['value'],true),
            ];
        }

        return $list;

    }

    public function getOne(string $name):array
    {
        $table = TableManager::getInstance()->get($this->tableName);

        if(!$table->count()) $this->initTable();
        
        $data = $table->get($name);
        return $data ? [
            'id'        => $data['id'],
            'task_need' => json_decode($data['task_need'],true),
            'reward'    => json_decode($data['value'],true),
        ] : [];

    }

}
