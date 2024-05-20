<?php
namespace App\Api\Table;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigEquipAttach as Model;

class ConfigEquipAttach
{
    use CoroutineSingleTon;

    protected $tableName   = 'config_equip_attach';

    public function create():void
    {
        $columns = [
            'quality1'   => [ 'type'=> Table::TYPE_STRING ,'size'=> 2000 ],
            'quality2'   => [ 'type'=> Table::TYPE_STRING ,'size'=> 2000 ],
            'quality3'   => [ 'type'=> Table::TYPE_STRING ,'size'=> 2000 ],
            'quality4'   => [ 'type'=> Table::TYPE_STRING ,'size'=> 2000 ],
            'quality5'   => [ 'type'=> Table::TYPE_STRING ,'size'=> 2000 ],
            'quality6'   => [ 'type'=> Table::TYPE_STRING ,'size'=> 2000 ],
            'quality7'   => [ 'type'=> Table::TYPE_STRING ,'size'=> 2000 ],
            'quality8'   => [ 'type'=> Table::TYPE_STRING ,'size'=> 2000 ],
            'quality9'   => [ 'type'=> Table::TYPE_STRING ,'size'=> 2000 ],
            'quality10'  => [ 'type'=> Table::TYPE_STRING ,'size'=> 2000 ],
            'quality11'  => [ 'type'=> Table::TYPE_STRING ,'size'=> 2000 ],
            'quality12'  => [ 'type'=> Table::TYPE_STRING ,'size'=> 2000 ],
            'quality13'  => [ 'type'=> Table::TYPE_STRING ,'size'=> 2000 ],
            'quality14'  => [ 'type'=> Table::TYPE_STRING ,'size'=> 2000 ],
            'quality15'  => [ 'type'=> Table::TYPE_STRING ,'size'=> 2000 ],
            'quality16'  => [ 'type'=> Table::TYPE_STRING ,'size'=> 2000 ],
            'quality17'  => [ 'type'=> Table::TYPE_STRING ,'size'=> 2000 ],
            'quality18'  => [ 'type'=> Table::TYPE_STRING ,'size'=> 2000 ],
            'quality19'  => [ 'type'=> Table::TYPE_STRING ,'size'=> 2000 ],
            'quality20'  => [ 'type'=> Table::TYPE_STRING ,'size'=> 2000 ],
            'quality21'  => [ 'type'=> Table::TYPE_STRING ,'size'=> 2000 ],
            'quality22'  => [ 'type'=> Table::TYPE_STRING ,'size'=> 2000 ],
            'quality23'  => [ 'type'=> Table::TYPE_STRING ,'size'=> 2000 ],
            'quality24'  => [ 'type'=> Table::TYPE_STRING ,'size'=> 2000 ],
            'quality25'  => [ 'type'=> Table::TYPE_STRING ,'size'=> 2000 ],
            'quality26'  => [ 'type'=> Table::TYPE_STRING ,'size'=> 2000 ],
            'quality27'  => [ 'type'=> Table::TYPE_STRING ,'size'=> 2000 ],
            'quality28'  => [ 'type'=> Table::TYPE_STRING ,'size'=> 2000 ],
            'quality29'  => [ 'type'=> Table::TYPE_STRING ,'size'=> 2000 ],
            'quality30'  => [ 'type'=> Table::TYPE_STRING ,'size'=> 2000 ],
            'quality31'  => [ 'type'=> Table::TYPE_STRING ,'size'=> 2000 ],
            'quality32'  => [ 'type'=> Table::TYPE_STRING ,'size'=> 2000 ],
            'quality33'  => [ 'type'=> Table::TYPE_STRING ,'size'=> 2000 ],
            'quality34'  => [ 'type'=> Table::TYPE_STRING ,'size'=> 2000 ],
        ];

        TableManager::getInstance()->add( $this->tableName , $columns , 5000 );

    }

    public function initTable():void
    {
        $table   = TableManager::getInstance()->get($this->tableName);

        $tableConfig = Model::create()->all();
        $list = [];
        foreach ($tableConfig as $key => $value) 
        {
            $sec_attribute_ran        = explode('|',$value['sec_attribute_ran']);
            $arrt  =  explode('-',$sec_attribute_ran[0]);
            $sec_attribute_ran_num    = $arrt[0];
            $sec_attribute_ran[0]     = $arrt[1];
            $secAttributeRan = [];
            foreach ($sec_attribute_ran as $j => $num) {
                if(!$num) continue;
                $secAttributeRan[$j+1] = $num * 1000;
            }

            $sec_def_attribute_ran    = explode('|',$value['sec_def_attribute_ran']);
            $arrt  =  explode('-',$sec_def_attribute_ran[0]);
            $sec_def_attribute_ran_num = $arrt[0];
            $sec_def_attribute_ran[0]  = $arrt[1];
            $secDefAttributeRan  = [];
            foreach ($secDefAttributeRan as $i => $num) {
                if(!$num) continue;
                $secAttributeRan[$i+1] = $num * 1000;
            }

            $list[ 'pos'.$value['type'] ][ 'quality'.$value['quality'] ] = json_encode([
                'id'                          => $value['id'],
                'name'                        => $value['name'],
                'icon'                        => $value['icon'],
                'prim_attack'                 => explode('|',$value['prim_attack']),
                'prim_hp'                     => explode('|',$value['prim_hp']),
                'prim_defence'                => explode('|',$value['prim_defence']),
                'speed'                       => explode('|',$value['speed']),
                'sec_attribute_ran_num'       => $sec_attribute_ran_num,
                'sec_attribute_ran'           => $secAttributeRan,
                'stun'                        => explode('|',$value['stun']),
                'critical_hit'                => explode('|',$value['critical_hit']),
                'double_attack'               => explode('|',$value['double_attack']),
                'dodge'                       => explode('|',$value['dodge']),
                'attack_back'                 => explode('|',$value['attack_back']),
                'life_steal'                  => explode('|',$value['life_steal']),
                'sec_def_attribute_ran_num'   => $sec_def_attribute_ran_num,
                'sec_def_attribute_ran'       => $secAttributeRan,
                're_stun'                     => explode('|',$value['re_stun']),
                're_critical_hit'             => explode('|',$value['re_critical_hit']),
                're_double_attack'            => explode('|',$value['re_double_attack']),
                're_dodge'                    => explode('|',$value['re_dodge']),
                're_attack_back'              => explode('|',$value['re_attack_back']),
                're_life_steal'               => explode('|',$value['re_life_steal']),
            ]);
        }
        
        foreach ($list as $pos => $data) 
        {
            $table->set($pos,$data);
        }

    }

    public function getOne(int $pos,int $quality):array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();

        $data = $table->get('pos'.$pos,'quality'.$quality);

        return $data ? json_decode($data,true) : [];
    }

}
