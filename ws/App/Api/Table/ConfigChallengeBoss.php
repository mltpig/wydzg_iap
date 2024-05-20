<?php
namespace App\Api\Table;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigChallengeBoss as Model;

class ConfigChallengeBoss
{
    use CoroutineSingleTon;

    protected $tableName = 'config_challenge_boss';

    public function create():void
    {
        $columns = [
            'name'               => [ 'type'=> Table::TYPE_STRING ,'size'=> 100 ],
            'background'         => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'unlock_level'       => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'moster_list'        => [ 'type'=> Table::TYPE_STRING ,'size'=> 100 ],
            'moster_level_list'  => [ 'type'=> Table::TYPE_STRING ,'size'=> 100 ],
            'rewards'            => [ 'type'=> Table::TYPE_STRING ,'size'=> 500 ],
            'repeat_rewards'     => [ 'type'=> Table::TYPE_STRING ,'size'=> 500 ],
            'repeat_cost'        => [ 'type'=> Table::TYPE_STRING ,'size'=> 500 ],
        ];

        TableManager::getInstance()->add( $this->tableName , $columns , 100 );

    }

    public function initTable():void
    {
        $table = TableManager::getInstance()->get($this->tableName);

        $tableConfig = Model::create()->all();
        foreach ($tableConfig as $key => $value) 
        {
            $rewards  = [];
            $list = explode('|',$value['rewards']);
            foreach ($list as  $val) 
            {
                $reward = getFmtGoods(explode('=',$val));
                $reward['type'] = ConfigGoods::getInstance()->getOne($reward['gid'])['type'];
                $rewards[] = $reward;
            }

            $repeatRewards = getFmtGoods(explode('=',$value['repeat_rewards']));
            $repeatRewards['type'] = ConfigGoods::getInstance()->getOne($repeatRewards['gid'])['type'];

            $repeatCost = getFmtGoods(explode('=',$value['repeat_cost']));
            $repeatCost['type'] = ConfigGoods::getInstance()->getOne($repeatCost['gid'])['type'];

        
            $table->set($value['id'],[
                'name'              => $value['name'],
                'background'        => $value['background'],
                'unlock_level'      => $value['unlock_level'],
                'moster_list'       => $value['moster_list'],
                'moster_level_list' => $value['moster_level_list'],
                'rewards'           => json_encode($rewards),
                'repeat_rewards'    => json_encode($repeatRewards),
                'repeat_cost'       => json_encode($repeatCost),

            ]);
        }

    }

    public function getOne(int $level):array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();
        
        $data = $table->get($level);

        return $data ? [
            'name'              => $data['name'],
            'background'        => $data['background'],
            'unlock_level'      => $data['unlock_level'],
            'moster_list'       => $data['moster_list'],
            'moster_level_list' => $data['moster_level_list'],
            'rewards'           => json_decode($data['rewards'],true),
            'repeat_rewards'    => json_decode($data['repeat_rewards'],true),
            'repeat_cost'       => json_decode($data['repeat_cost'],true),
        ] : [];

    }

}
