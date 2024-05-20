<?php
namespace App\Api\Table;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigComradeVisit as Model;

class ConfigComradeVisit
{
    use CoroutineSingleTon;

    protected $tableName   = 'config_comrade_visit';

    public function create():void
    {
        $columns = [
            'id'                => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'name'              => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'weight'            => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'normal_reward'     => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'location'          => [ 'type'=> Table::TYPE_INT ,'size'=> 50 ],
            'chara'             => [ 'type'=> Table::TYPE_INT ,'size'=> 50 ],
            'like_num'          => [ 'type'=> Table::TYPE_INT ,'size'=> 50 ],
            'system_id'         => [ 'type'=> Table::TYPE_INT ,'size'=> 50 ]
        ];

        TableManager::getInstance()->add( $this->tableName , $columns , 100 );

    }

    public function initTable():void
    {
        $table   = TableManager::getInstance()->get($this->tableName);

        $tableConfig = Model::create()->all();
        foreach ($tableConfig as $key => $value) 
        {
            $reward = [];
            if($value['normal_reward'])
            {
                $reward = getFmtGoods(explode('=',$value['normal_reward']));
                $reward['type'] = ConfigGoods::getInstance()->getOne($reward['gid'])['type'];
            }

            $table->set($value['id'], [
                'id'             => $value['id'],
                'weight'         => $value['weight'],
                'location'       => $value['location'],
                'chara'          => $value['chara'],
                'like_num'       => $value['like_num'],
                'normal_reward'  => json_encode($reward),
            ]);
        }
        
    }

    public function getOne(int $id):array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();

        $data = $table->get($id);

        return $data ? [
            'id'             => $data['id'],
            'weight'         => $data['weight'],
            'location'       => $data['location'],
            'chara'          => $data['chara'],
            'like_num'       => $data['like_num'],
            'normal_reward'  => json_decode($data['normal_reward'],true),
        ] : [];
    }

    public function getRandVisit(array $comrades):array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();

        $map  = $list  = [];
        foreach ($table as $id => $config) 
        {
            if($config['chara'] && !in_array($config['chara'],$comrades)) continue;

            $list[ $id ] = [
                'id'             => $config['id'],
                'weight'         => $config['weight'],
                'location'       => $config['location'],
                'chara'          => $config['chara'],
                'like_num'       => $config['like_num'],
                'normal_reward'  => json_decode($config['normal_reward'],true),
            ];

            $map[$id] = $config['weight'];
        }

        return [ $map , $list];
    }

}