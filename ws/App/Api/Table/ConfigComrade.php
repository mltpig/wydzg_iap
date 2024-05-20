<?php
namespace App\Api\Table;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigComrade as Model;

class ConfigComrade
{
    use CoroutineSingleTon;

    protected $tableName   = 'config_comrade';

    public function create():void
    {
        $columns = [
            'id'                => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'name'              => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'quest_id'          => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'cost_id'           => [ 'type'=> Table::TYPE_STRING ,'size'=> 200 ],
            'race'              => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'quality'           => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'character'         => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'battle_talent'     => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'talent'            => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'talent_level_up'   => [ 'type'=> Table::TYPE_STRING ,'size'=> 500 ],
            'talk'              => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'skill_list'        => [ 'type'=> Table::TYPE_STRING ,'size'=> 2000 ],
        ];

        TableManager::getInstance()->add( $this->tableName , $columns , 100 );

    }

    public function initTable():void
    {
        $table   = TableManager::getInstance()->get($this->tableName);

        $tableConfig = Model::create()->all();
        foreach ($tableConfig as $key => $value) 
        {
            $cost = [];
            if($value['cost_id'])
            {
                list($lock,$step) = explode('|',$value['cost_id']);
                list($gid,$num) = explode('=',$lock);
                $cost = ['gid' => $gid,'num' => $num ,'step' => $step ];
            }

            $table->set($value['id'], [
                'id'                => $value['id'],
                'name'              => $value['name'],
                'quest_id'          => $value['quest_id'],
                'cost_id'           => json_encode($cost),
                'race'              => $value['race'],
                'quality'           => $value['quality'],
                'character'         => $value['character'],
                'battle_talent'     => $value['battle_talent'],
                'talent'            => $value['talent'],
                'talent_level_up'   => json_encode($this->getFmtTalentLevelUpData($value['talent_level_up'])),
                'talk'              => $value['talk'],
                'skill_list'        => json_encode(explode('|',$value['skill_list'])),
            ]);
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
                'id'                => $config['id'],
                'name'              => $config['name'],
                'quest_id'          => $config['quest_id'],
                'cost_id'           => json_decode($config['cost_id'],true),
                'race'              => $config['race'],
                'quality'           => $config['quality'],
                'character'         => $config['character'],
                'battle_talent'     => $config['battle_talent'],
                'talent'            => $config['talent'],
                'talent_level_up'   => json_decode($config['talent_level_up'],true),
                'talk'              => $config['talk'],
                'skill_list'        => json_decode($config['skill_list'],true),
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
                'id'                => $data['id'],
                'name'              => $data['name'],
                'quest_id'          => $data['quest_id'],
                'cost_id'           => json_decode($data['cost_id'],true),
                'race'              => $data['race'],
                'quality'           => $data['quality'],
                'character'         => $data['character'],
                'battle_talent'     => $data['battle_talent'],
                'talent'            => $data['talent'],
                'talent_level_up'   => json_decode($data['talent_level_up'],true),
                'talk'              => $data['talk'],
                'skill_list'        => json_decode($data['skill_list'],true),
        ] : [];
    }


    public function getInitId():int
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();
        
        $initId = 0;
        foreach ($table as $id => $config) 
        {
            $cost = json_decode($config['cost_id'],true);
            if(!$initId && !$config['quest_id'] && !$cost ) $initId = $id;
        }

        return $initId;
    }

    public function getFmtTalentLevelUpData(string $string):array
    {
        $range = [];
        $list  = explode('|',$string);
        foreach ($list as $key => $value) 
        {   
            //起始 截止 对应阶
            if(!$key)
            {
                $range[$key] = [ 0, $value - 1];
            }else{
                $range[$key] = [ $list[$key-1],$value - 1 ];
            }
        }

        $range[$key + 1] = [$value,$value];
        return $range;
    }
}