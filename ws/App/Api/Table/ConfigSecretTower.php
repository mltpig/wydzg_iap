<?php
namespace App\Api\Table;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigSecretTower as Model;

class ConfigSecretTower
{
    use CoroutineSingleTon;

    protected $tableName = 'config_secret_tower';

    protected $tableNameReward = 'config_secret_tower_reward';

    public function create():void
    {
        $columns = [
            'floor'                 => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'background'            => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'type'                  => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'moster_list'           => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'moster_level_list'     => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'challenge_reward'      => [ 'type'=> Table::TYPE_STRING ,'size'=> 128 ],
            'big_reward'            => [ 'type'=> Table::TYPE_STRING ,'size'=> 128 ],
            'server_reward'         => [ 'type'=> Table::TYPE_STRING ,'size'=> 128 ],
        ];
        TableManager::getInstance()->add( $this->tableName , $columns , 5000 );

        $columns = [
            'floor'                 => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'background'            => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'type'                  => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'big_reward'            => [ 'type'=> Table::TYPE_STRING ,'size'=> 128 ],
            'server_reward'         => [ 'type'=> Table::TYPE_STRING ,'size'=> 128 ],
        ];
        TableManager::getInstance()->add( $this->tableNameReward , $columns , 5000 );

    }

    public function initTable():void
    {
        $table          = TableManager::getInstance()->get($this->tableName);
        $tableReward    = TableManager::getInstance()->get($this->tableNameReward);

        $tableConfig = Model::create()->all();
        foreach ($tableConfig as $key => $value) 
        {
            $challenge  = [];
            if($value['challenge_reward']){
                $challenges = explode('|',$value['challenge_reward']);
                foreach ($challenges as  $val) 
                {
                    $challenge_reward = getFmtGoods(explode('=',$val));
                    $challenge_reward['type'] = ConfigGoods::getInstance()->getOne($challenge_reward['gid'])['type'];
                    $challenge[] = $challenge_reward;
                }
            }

            $big  = [];
            if($value['big_reward'])
            {
                $bigs = explode('|',$value['big_reward']);
                foreach ($bigs as  $val) 
                {
                    $big_reward = getFmtGoods(explode('=',$val));
                    $big_reward['type'] = ConfigGoods::getInstance()->getOne($big_reward['gid'])['type'];
                    $big[] = $big_reward;
                }
            }

            $server = [];
            if($value['server_reward'])
            {
                $server_reward = getFmtGoods(explode('=',$value['server_reward']));
                $server_reward['type'] = ConfigGoods::getInstance()->getOne($server_reward['gid'])['type'];
                $server[] = $server_reward;
            }

            $table->set($value['id'],[
                'floor'              => $value['floor'],
                'background'         => $value['background'],
                'type'               => $value['type'],
                'moster_list'        => $value['moster_list'],
                'moster_level_list'  => $value['moster_level_list'],
                'challenge_reward'   => json_encode($challenge),
                'big_reward'         => json_encode($big),
                'server_reward'      => json_encode($server),
            ]);

            if(!empty($big) && !empty($server))
            {
                $tableReward->set($value['id'],[
                    'floor'              => $value['floor'],
                    'background'         => $value['background'],
                    'type'               => $value['type'],
                    'big_reward'         => json_encode($big),
                    'server_reward'      => json_encode($server),
                ]);
            }
        }

    }

    public function getAll():array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();

        $list  = [];
        foreach ($table as $id => $config)
        {
            $list[ $id ] = [
                'floor'                 => $config['floor'],
                'background'            => $config['background'],
                'type'                  => $config['type'],
                'moster_list'           => $config['moster_list'],
                'moster_level_list'     => $config['moster_level_list'],
                'challenge_reward'      => json_decode($config['challenge_reward'],true),
                'big_reward'            => json_decode($config['big_reward'],true),
                'server_reward'         => json_decode($config['server_reward'],true),
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
            'floor'             => $data['floor'],
            'background'        => $data['background'],
            'type'              => $data['type'],
            'moster_list'       => $data['moster_list'],
            'moster_level_list' => $data['moster_level_list'],
            'challenge_reward'    => json_decode($data['challenge_reward'],true),
            'big_reward'          => json_decode($data['big_reward'],true),
            'server_reward'       => json_decode($data['server_reward'],true),
        ] : [];

    }

    public function getAllReward():array
    {
        $table = TableManager::getInstance()->get($this->tableNameReward);
        if(!$table->count()) $this->initTable();

        $list  = [];
        foreach ($table as $id => $config)
        {
            $list[ $id ] = [
                'floor'                 => $config['floor'],
                'background'            => $config['background'],
                'type'                  => $config['type'],
                'big_reward'            => json_decode($config['big_reward'],true),
                'server_reward'         => json_decode($config['server_reward'],true),
            ];
        }
        return $list;
    }
}
