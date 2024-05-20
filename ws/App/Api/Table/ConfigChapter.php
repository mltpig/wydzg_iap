<?php
namespace App\Api\Table;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigChapter as Model;

class ConfigChapter
{
    use CoroutineSingleTon;

    protected $tableName = 'config_chapter';

    public function create():void
    {
        $columns = [
            'id'                 => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'background'         => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'chapter'            => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'name'               => [ 'type'=> Table::TYPE_STRING ,'size'=> 100 ],
            'moster_list'        => [ 'type'=> Table::TYPE_STRING ,'size'=> 100 ],
            'moster_level_list'  => [ 'type'=> Table::TYPE_STRING ,'size'=> 100 ],
            'map_coordinates'    => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'exercise_boby_exp'  => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'rewards'            => [ 'type'=> Table::TYPE_STRING ,'size'=> 500 ],
        ];

        TableManager::getInstance()->add( $this->tableName , $columns , 5000 );

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
                $reward['type'] =  ConfigGoods::getInstance()->getOne($reward['gid'])['type'];
                $rewards[] = $reward;
            }
            $table->set($value['id'],[
                'id'                => $value['id'],
                'background'        => $value['background'],
                'chapter'           => $value['chapter'],
                'name'              => $value['name'],
                'moster_list'       => $value['moster_list'],
                'moster_level_list' => $value['moster_level_list'],
                'map_coordinates'   => $value['map_coordinates'],
                'exercise_boby_exp' => $value['exercise_boby_exp'],
                'rewards'           => json_encode($rewards),
            ]);
        }

    }

    public function getOne(int $level):array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();
        
        $data = $table->get($level);

        return $data ? [
            'id'                => $data['id'],
            'background'        => $data['background'],
            'chapter'           => $data['chapter'],
            'name'              => $data['name'],
            'moster_list'       => $data['moster_list'],
            'moster_level_list' => $data['moster_level_list'],
            'map_coordinates'   => $data['map_coordinates'],
            'exercise_boby_exp' => $data['exercise_boby_exp'],
            'rewards'           => json_decode($data['rewards'],true),
        ] : [];

    }

}
