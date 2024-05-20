<?php
namespace App\Api\Table;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigMonsters as Model;

class ConfigMonsters
{
    use CoroutineSingleTon;

    protected $tableName   = 'config_monsters';

    public function create():void
    {
        $columns = [
            'id'                    => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'attackEmp'             => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'hpEmp'                 => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'defEmp'                => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'speedEmp'              => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'stun'                  => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'criticalHit'           => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'doubleAttack'          => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'dodge'                 => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'attackBack'            => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'lifeSteal'             => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'reStun'                => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'reCriticalHit'         => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'reDoubleAttack'        => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'reDodge'               => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'reAttackBack'          => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'reLifeSteal'           => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'cloud'                 => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'pet'                   => [ 'type'=> Table::TYPE_INT ,'size'=> 10 ],
            'spirit'                => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
        ];

        
        TableManager::getInstance()->add( $this->tableName , $columns , 5000 );

    }

    public function initTable():void
    {
        $table   = TableManager::getInstance()->get($this->tableName);

        $tableConfig = Model::create()->all();
        foreach ($tableConfig as $key => $value) 
        {
            $spirit = explode('|', $value['spirit']);

            $table->set($value['id'], [
                'id'                    => $value['id'],
                'attackEmp'             => $value['attackEmp'],
                'hpEmp'                 => $value['hpEmp'],
                'defEmp'                => $value['defEmp'],
                'speedEmp'              => $value['speedEmp'],
                'stun'                  => $value['stun'],
                'criticalHit'           => $value['criticalHit'],
                'doubleAttack'          => $value['doubleAttack'],
                'dodge'                 => $value['dodge'],
                'attackBack'            => $value['attackBack'],
                'lifeSteal'             => $value['lifeSteal'],
                'reStun'                => $value['reStun'],
                'reCriticalHit'         => $value['reCriticalHit'],
                'reDoubleAttack'        => $value['reDoubleAttack'],
                'reDodge'               => $value['reDodge'],
                'reAttackBack'          => $value['reAttackBack'],
                'reLifeSteal'           => $value['reLifeSteal'],
                'cloud'                 => $value['cloud'],
                'pet'                   => $value['pet'],
                'spirit'                => json_encode($spirit),
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
            'attackEmp'             => $data['attackEmp'],
            'hpEmp'                 => $data['hpEmp'],
            'defEmp'                => $data['defEmp'],
            'speedEmp'              => $data['speedEmp'],
            'stun'                  => $data['stun'],
            'criticalHit'           => $data['criticalHit'],
            'doubleAttack'          => $data['doubleAttack'],
            'dodge'                 => $data['dodge'],
            'attackBack'            => $data['attackBack'],
            'lifeSteal'             => $data['lifeSteal'],
            'reStun'                => $data['reStun'],
            'reCriticalHit'         => $data['reCriticalHit'],
            'reDoubleAttack'        => $data['reDoubleAttack'],
            'reDodge'               => $data['reDodge'],
            'reAttackBack'          => $data['reAttackBack'],
            'reLifeSteal'           => $data['reLifeSteal'],
            'cloud'                 => $data['cloud'],
            'pet'                   => $data['pet'],
            'spirit'                => json_decode($data['spirit'],true),
        ] : [];
    }

}
