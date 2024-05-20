<?php
namespace App\Api\Table;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigComradeChallenge as Model;

class ConfigComradeChallenge
{
    use CoroutineSingleTon;

    protected $tableName   = 'config_comrade_challenge';

    public function create():void
    {
        
        $columns = [
            'id'              => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'background'      => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'name'            => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'unlock_level'    => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'unlock_like'     => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'chara_id'        => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'monster_id'      => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'monster_level'   => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'rewards'         => [ 'type'=> Table::TYPE_STRING ,'size'=> 200 ]
        ];

        TableManager::getInstance()->add( $this->tableName , $columns , 1000 );

    }

    public function initTable():void
    {
        $table   = TableManager::getInstance()->get($this->tableName);

        $tableConfig = Model::create()->all();
        foreach ($tableConfig as $key => $value) 
        {
            $rewards = [];
            $rewardList = explode('|',$value['rewards']);
            foreach ($rewardList as $item) 
            {
                $detail = getFmtGoods(explode('=',$item));
                $detail['type'] = ConfigGoods::getInstance()->getOne($detail['gid'])['type'];

                $rewards[] = $detail;
            }

            $table->set($value['id'], [
                'id'           => $value['id'],
                'background'   => $value['background'],
                'name'         => $value['name'],
                'unlock_level' => $value['unlock_level'],
                'unlock_like'  => $value['unlock_like'],
                'chara_id'     => $value['chara_id'],
                'monster_id'   => $value['monster_id'],
                'monster_level'=> $value['monster_level'],
                'rewards'      => json_encode($rewards),
            ]);
        }
        
    }

    public function getOne(int $id):array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();

        $data = $table->get($id);

        return $data ? [
            'id'            => $data['id'],
            'background'    => $data['background'],
            'name'          => $data['name'],
            'unlock_level'  => $data['unlock_level'],
            'unlock_like'   => $data['unlock_like'],
            'chara_id'      => $data['chara_id'],
            'monster_id'    => $data['monster_id'],
            'monster_level' => $data['monster_level'],
            'rewards'       => json_decode($data['rewards'],true),
        ] : [];
    }

    public function getBattleId(int $charaid,int $index):array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();
        
        $i = 1;
        foreach ($table as $id => $config) 
        {
            if($config['chara_id'] != $charaid ) continue;
            if( $index == $i ) return $config;
            
            $i++;
        }
        
    }
}