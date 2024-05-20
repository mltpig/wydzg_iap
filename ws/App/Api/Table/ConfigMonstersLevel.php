<?php
namespace App\Api\Table;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigMonstersLevel as Model;

class ConfigMonstersLevel
{
    use CoroutineSingleTon;

    protected $tableName   = 'config_monsters_level';

    public function create():void
    {
        $columns = [
            'id'                => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'realms'            => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'attackBase'        => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'hpBase'            => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'defBase'           => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'speedBase'         => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'secAttribute'      => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'secDefAttribute'   => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'cloudLevel'        => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'petLevel'          => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'spiritCount'       => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'spiritLevel'       => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
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
                'id'                    => $value['id'],
                'realms'                => $value['realms'],
                'attackBase'            => $value['attackBase'],
                'hpBase'                => $value['hpBase'],
                'defBase'               => $value['defBase'],
                'speedBase'             => $value['speedBase'],
                'secAttribute'          => $value['secAttribute'],
                'secDefAttribute'       => $value['secDefAttribute'],
                'cloudLevel'            => $value['cloudLevel'],
                'petLevel'              => $value['petLevel'],
                'spiritCount'           => $value['spiritCount'],
                'spiritLevel'           => $value['spiritLevel'],
            ]);
        }
        
    }

    public function getOne(int $equipid):array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();

        $data = $table->get($equipid);

        return $data ? [
            'id'                    => $data['id'],
            'realms'                => $data['realms'],
            'attackBase'            => $data['attackBase'],
            'hpBase'                => $data['hpBase'],
            'defBase'               => $data['defBase'],
            'speedBase'             => $data['speedBase'],
            'secAttribute'          => $data['secAttribute'],
            'secDefAttribute'       => $data['secDefAttribute'],
            'cloudLevel'            => $data['cloudLevel'],
            'petLevel'              => $data['petLevel'],
            'spiritCount'           => $data['spiritCount'],
            'spiritLevel'           => $data['spiritLevel'],
        ] : [];
    }

}
