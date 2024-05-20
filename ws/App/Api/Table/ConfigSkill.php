<?php
namespace App\Api\Table;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigSkill as Model;

class ConfigSkill
{
    use CoroutineSingleTon;

    protected $tableName = 'config_skill';

    public function create():void
    {
        $columns = [
            'id'               => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'name'             => [ 'type'=> Table::TYPE_STRING ,'size'=> 100 ],
            'icon'             => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'star'             => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'type'             => [ 'type'=> Table::TYPE_STRING ,'size'=> 100 ],
            'params'           => [ 'type'=> Table::TYPE_STRING ,'size'=> 100 ],
            'upgradeParams'    => [ 'type'=> Table::TYPE_STRING ,'size'=> 500 ],
            'maxLevel'         => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'upgradeItem'      => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'upgradeType'      => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'sort'             => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'textStyle'        => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
        ];
        
        TableManager::getInstance()->add( $this->tableName , $columns , 5000 );

    }

    public function initTable():void
    {
        $table = TableManager::getInstance()->get($this->tableName);

        $tableConfig = Model::create()->all();
        foreach ($tableConfig as $value) 
        {
            $paramList   = $upgradeParams = [];
            $param= explode(';',$value['params']);
            foreach ($param as $val1) 
            {
                $paramList[] = explode('|',$val1);
            }

            $upgradeParamsList = explode(';',$value['upgradeParams']);
            foreach ($upgradeParamsList as $val2)
            {
                $upgradeParams[] = explode('|',$val2);
            }

            $table->set($value['id'],[
                'id'              => $value['id'],
                'name'            => $value['name'],
                'icon'            => $value['icon'],
                'star'            => $value['star'],
                'type'            => json_encode(explode('|',$value['type'])),
                'params'          => json_encode($paramList),
                'upgradeParams'   => json_encode($upgradeParams),
                'maxLevel'        => $value['maxLevel'],
                'upgradeItem'     => $value['upgradeItem'],
                'upgradeType'     => $value['upgradeType'],
                'sort'            => $value['sort'],
                'textStyle'       => $value['textStyle'],
            ]);
        }

    }

    public function getOne(int $level):array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();
        
        $data = $table->get($level);

        return $data ? [
            'id'              => $data['id'],
            'name'            => $data['name'],
            'icon'            => $data['icon'],
            'star'            => $data['star'],
            'type'            => json_decode($data['type'],true),
            'params'          => json_decode($data['params'],true),
            'upgradeParams'   => json_decode($data['upgradeParams'],true),
            'maxLevel'        => $data['maxLevel'],
            'upgradeItem'     => $data['upgradeItem'],
            'upgradeType'     => $data['upgradeType'],
            'sort'            => $data['sort'],
            'textStyle'       => $data['textStyle'],
        ] : [];

    }

}
