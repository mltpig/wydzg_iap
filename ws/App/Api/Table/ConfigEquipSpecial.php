<?php
namespace App\Api\Table;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigEquipSpecial as Model;

class ConfigEquipSpecial
{
    use CoroutineSingleTon;

    protected $tableName   = 'config_equip_special';

    public function create():void
    {
        $columns = [
            'id'                => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'name'              => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'desc'              => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'icon'              => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'quality'           => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'level'             => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'type'              => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'prim_attack'       => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'prim_hp'           => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'prim_defence'      => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'prim_speed'        => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'stun'              => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'critical_hit'      => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'double_attack'     => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'dodge'             => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'attack_back'       => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'life_steal'        => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            're_stun'           => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            're_critical_hit'   => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            're_double_attack'  => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            're_dodge'          => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            're_attack_back'    => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            're_life_steal'     => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
        ];

        TableManager::getInstance()->add( $this->tableName , $columns , 5000 );

    }

    public function initTable():void
    {
        $table   = TableManager::getInstance()->get($this->tableName);

        $tableConfig = Model::create()->all();
        foreach ($tableConfig as $key => $value) 
        {

            $table->set($value['id'], [
                'id'                => $value['id'],
                'name'              => $value['name'],
                'desc'              => $value['desc'],
                'icon'              => $value['icon'],
                'quality'           => $value['quality'],
                'level'             => $value['level'],
                'type'              => $value['type'],
                'prim_attack'       => json_encode(explode('|',$value['prim_attack'])),
                'prim_hp'           => json_encode(explode('|',$value['prim_hp'])),
                'prim_defence'      => json_encode(explode('|',$value['prim_defence'])),
                'prim_speed'        => json_encode(explode('|',$value['prim_speed'])),
                'stun'              => json_encode(explode('|',$value['stun'])),
                'critical_hit'      => json_encode(explode('|',$value['critical_hit'])),
                'double_attack'     => json_encode(explode('|',$value['double_attack'])),
                'dodge'             => json_encode(explode('|',$value['dodge'])),
                'attack_back'       => json_encode(explode('|',$value['attack_back'])),
                'life_steal'        => json_encode(explode('|',$value['life_steal'])),
                're_stun'           => json_encode(explode('|',$value['re_stun'])),
                're_critical_hit'   => json_encode(explode('|',$value['re_critical_hit'])),
                're_double_attack'  => json_encode(explode('|',$value['re_double_attack'])),
                're_dodge'          => json_encode(explode('|',$value['re_dodge'])),
                're_attack_back'    => json_encode(explode('|',$value['re_attack_back'])),
                're_life_steal'     => json_encode(explode('|',$value['re_life_steal'])),
            ]);
        }
        
    }

    public function getOne(int $equipid):array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();

        $data = $table->get($equipid);

        return $data ? [
                'id'                => $data['id'],
                'name'              => $data['name'],
                'desc'              => $data['desc'],
                'icon'              => $data['icon'],
                'quality'           => $data['quality'],
                'level'             => $data['level'],
                'type'              => $data['type'],
                'prim_attack'       => json_decode($data['prim_attack'],true),
                'prim_hp'           => json_decode($data['prim_hp'],true),
                'prim_defence'      => json_decode($data['prim_defence'],true),
                'prim_speed'        => json_decode($data['prim_speed'],true),
                'stun'              => json_decode($data['stun'],true),
                'critical_hit'      => json_decode($data['critical_hit'],true),
                'double_attack'     => json_decode($data['double_attack'],true),
                'dodge'             => json_decode($data['dodge'],true),
                'attack_back'       => json_decode($data['attack_back'],true),
                'life_steal'        => json_decode($data['life_steal'],true),
                're_stun'           => json_decode($data['re_stun'],true),
                're_critical_hit'   => json_decode($data['re_critical_hit'],true),
                're_double_attack'  => json_decode($data['re_double_attack'],true),
                're_dodge'          => json_decode($data['re_dodge'],true),
                're_attack_back'    => json_decode($data['re_attack_back'],true),
                're_life_steal'     => json_decode($data['re_life_steal'],true),
        ] : [];
    }

}
