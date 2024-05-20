<?php
namespace App\Api\Table;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigPets as Model;

class ConfigPets
{
    use CoroutineSingleTon;

    protected $tableName = 'config_pets';

    public function create():void
    {
        $columns = [
            'quality'               => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'type'                  => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'weight'                => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'active_skill'          => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'active_skill_upgrade'  => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'gvg_active_skill'      => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'passive_skill'         => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'hp_basic'              => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'attack_basic'          => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'defense_basic'         => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'hp_add'                => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'attack_add'            => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'defense_add'           => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'hp_star_add'           => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'attack_star_add'       => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'defense_star_add'      => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'create_cost'           => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'back_reward'           => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'level_cost'            => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'level_limit'           => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'star_limit'            => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'combine_id'            => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'icon'                  => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
        ];

        TableManager::getInstance()->add( $this->tableName , $columns , 50 );

    }

    public function initTable():void
    {
        $table = TableManager::getInstance()->get($this->tableName);

        $tableConfig = Model::create()->all();
        foreach ($tableConfig as $key => $value) 
        {

            $createCost = getFmtGoods(explode('=',$value['create_cost']));
            $createCost['type'] = ConfigGoods::getInstance()->getOne($createCost['gid'])['type'];
            $backReward = getFmtGoods(explode('=',$value['back_reward']));
            $backReward['type'] = ConfigGoods::getInstance()->getOne($backReward['gid'])['type'];
            $levelCost  = getFmtGoods(explode('=',$value['level_cost']));
            $levelCost['type'] = ConfigGoods::getInstance()->getOne($levelCost['gid'])['type'];

            $table->set($value['id'],[
                'quality'               => $value['quality'],
                'type'                  => $value['type'],
                'weight'                => $value['weight'],
                'active_skill'          => $value['active_skill'],
                'active_skill_upgrade'  => $value['active_skill_upgrade'],
                'gvg_active_skill'      => $value['gvg_active_skill'],
                'passive_skill'         => $value['passive_skill'],
                'hp_basic'              => $value['hp_basic'],
                'attack_basic'          => $value['attack_basic'],
                'defense_basic'         => $value['defense_basic'],
                'hp_add'                => $value['hp_add'],
                'attack_add'            => $value['attack_add'],
                'defense_add'           => $value['defense_add'],
                'hp_star_add'           => $value['hp_star_add'],
                'attack_star_add'       => $value['attack_star_add'],
                'defense_star_add'      => $value['defense_star_add'],
                'level_limit'           => $value['level_limit'],
                'star_limit'            => $value['star_limit'],
                'combine_id'            => $value['combine_id'],
                'create_cost'           => json_encode($createCost),
                'back_reward'           => json_encode($backReward),
                'level_cost'            => json_encode($levelCost),
                'icon'                  => $value['icon'],
            ]);
        }

    }

    public function getOne(int $level):array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();
        
        $data = $table->get($level);

        return $data ? [
            'quality'               => $data['quality'],
            'type'                  => $data['type'],
            'weight'                => $data['weight'],
            'active_skill'          => $data['active_skill'],
            'active_skill_upgrade'  => $data['active_skill_upgrade'],
            'gvg_active_skill'      => $data['gvg_active_skill'],
            'passive_skill'         => $data['passive_skill'],
            'hp_basic'              => $data['hp_basic'],
            'attack_basic'          => $data['attack_basic'],
            'defense_basic'         => $data['defense_basic'],
            'hp_add'                => $data['hp_add'],
            'attack_add'            => $data['attack_add'],
            'defense_add'           => $data['defense_add'],
            'hp_star_add'           => $data['hp_star_add'],
            'attack_star_add'       => $data['attack_star_add'],
            'defense_star_add'      => $data['defense_star_add'],
            'level_limit'           => $data['level_limit'],
            'star_limit'            => $data['star_limit'],
            'combine_id'            => $data['combine_id'],
            'create_cost'           => json_decode($data['create_cost'],true),
            'back_reward'           => json_decode($data['back_reward'],true),
            'level_cost'            => json_decode($data['level_cost'],true),
            'icon'                  => $data['icon'],
        ] : [];

    }

    public function getAllWeight():array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();

        $list  = [];
        foreach ($table as $petid => $value) 
        {
            if(!$value['weight']) continue;
            
            $list[ $petid ] = $value['weight'];
        }

        return $list;
    }

    public function getQualityWeight(int $quality):array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();

        $list  = [];
        foreach ($table as $petid => $value) 
        {
            if($value['quality'] != $quality) continue;
            if(!$value['weight']) continue;
            
            $list[ $petid ] = $value['weight'];
        }

        return $list;
    }

    public function getAllCombineId():array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();

        $list  = [];
        foreach ($table as $petid => $value) 
        {
            if(!$value['combine_id']) continue;
            
            $list[ $value['combine_id'] ][$petid] = 1;
        }

        return $list;
    }

}
