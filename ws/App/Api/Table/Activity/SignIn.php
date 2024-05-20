<?php
namespace App\Api\Table\Activity;
use Swoole\Table;
use App\Api\Table\ConfigGoods;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\Activity\SignIn as Model;

class SignIn
{
    use CoroutineSingleTon;

    protected $tableName = 'activity_sign_in';

    public function create():void
    {
        $columns = [ 
            'id'      => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'day_num' => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'reward'  => [ 'type'=> Table::TYPE_STRING ,'size'=> 500 ]
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
                'id'      => $value['id'],
                'day_num' => $value['day_num'],
                'reward'  => json_encode($rewards),
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

            $list[] = [ 
                'id'       => $config['id'],
                'day_num'  => $config['day_num'],
                'reward'   => json_decode($config['reward'],true)
            ];
        }

        return $list;

    }

    public function getOne(string $name):array
    {
        $table = TableManager::getInstance()->get($this->tableName);

        if(!$table->count()) $this->initTable();
        
        $data = $table->get($name);
        return $data ?  json_decode($data['reward'],true): [];

    }

}
