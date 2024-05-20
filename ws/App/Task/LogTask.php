<?php 
namespace App\Task;

use EasySwoole\Task\AbstractInterface\TaskInterface;
use App\Api\Utils\Keys;
use EasySwoole\EasySwoole\Logger;
use EasySwoole\Log\LoggerInterface;
use EasySwoole\Redis\Redis;
use EasySwoole\Pool\Manager as PoolManager;
use App\Api\Table\ConfigGoods;

class LogTask implements TaskInterface
{
    protected $data;

    public function __construct($data)
    {
        // 保存投递过来的数据
        $this->data = $data;
    }

    public function run(int $taskId, int $workerIndex)
    {
        $cahceKey = Keys::getInstance()->getLogGoodsKey('ysjdftz');

        PoolManager::getInstance()->get('redis_log')->invoke(function (Redis $redis) use($cahceKey){
            foreach ($this->data as $key => $detail) 
            {
                try {
                    $log = [
                        'uid'           => $detail['uid'],
                        'name'          => ConfigGoods::getInstance()->getOne($detail['goods'])['name'],
                        'scene'         => $detail['scene'],
                        'desc'          => $detail['desc'],
                        'type'          => $detail['num'] > 0 ? 1 :2 ,
                        'number'        => abs($detail['num']),
                        'node'          => $detail['node'],
                        'create_time'   => date('Y-m-d H:i:s',$detail['time']),  
                    ];
                    $redis->lPush($cahceKey,json_encode($log));
                } catch (\Throwable $th) {
                    var_dump($detail['scene'].' '.$detail['desc']);
                }
            }
        });
    }

    public function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        Logger::getInstance()->log($throwable->getMessage(),LoggerInterface::LOG_LEVEL_ERROR,'LogTask');

    }
}