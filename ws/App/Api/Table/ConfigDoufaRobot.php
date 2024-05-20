<?php
namespace App\Api\Table;

use Swoole\Table;
use App\Api\Utils\Keys;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use EasySwoole\Redis\Redis;
use EasySwoole\Pool\Manager as PoolManager;

class ConfigDoufaRobot
{
    use CoroutineSingleTon;

    protected $tableName = 'config_doufa_robot';

    public function create():void
    {
        $columns = [
            'playerid'  => [ 'type'=> Table::TYPE_STRING ,'size'=> 100 ],
            'score'     => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'rolelv'    => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'power'     => [ 'type'=> Table::TYPE_STRING ,'size'=> 100 ],
            'user'      => [ 'type'=> Table::TYPE_STRING ,'size'=> 1000 ],
            'cloud'     => [ 'type'=> Table::TYPE_STRING ,'size'=> 100 ],
            'equip'     => [ 'type'=> Table::TYPE_STRING ,'size'=> 5000 ],
        ];

        TableManager::getInstance()->add( $this->tableName , $columns , 100 );

    }

    public function initTable(int $site , array $config ):void
    {
        $robotKey = Keys::getInstance()->getDoufaRobotKey($site);

        PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use($robotKey,$config) {
            $redis->del($robotKey);
            $redis->hMSet($robotKey,$config);
        });

    }

    public function getAllRobotId(int $site):array
    {
        $robotKey = Keys::getInstance()->getDoufaRobotKey($site);
        $all = PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use($robotKey) {
            return $redis->hKeys($robotKey);
        });
        $list  = [];
        foreach ($all as $id)
        {
            $list[$id] = $id;
        }
        return $list;
    }

    public function getOne(int $site,string $robotid):array
    {

        $robotKey = Keys::getInstance()->getDoufaRobotKey($site);

        $string = PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use($robotKey,$robotid) {
            return $redis->hGet($robotKey,$robotid);
        });

        $data =  $string ? json_decode($string,true) : [];

        return $data ? [
            'playerid' => $data['playerid'],
            'score'    => $data['score'],
            'power'    => $data['power'],
            'rolelv'   => $data['rolelv'],
            'user'     => json_decode($data['user'],true),
            'cloud'    => json_decode($data['cloud'],true),
            'equip'    => json_decode($data['equip'],true),
        ] : [];

    }

    public function decr(int $site,string $robotId,int $incrby ):int
    {
        $robotKey = Keys::getInstance()->getDoufaRobotKey($site);
        return PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use($robotKey,$robotId,$incrby) {
            $string = $redis->hGet($robotKey,$robotId);
            if(!$string) return 0;

            $data  =  json_decode($string,true);
            $data['score'] +=  $incrby;

            $redis->hSet($robotKey,$robotId,json_encode($data));

            return $data['score'];
        });

    }

}
