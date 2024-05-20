<?php
namespace app\controller;
use app\lib\Redis\RedisManager;
use app\lib\ClickHouse\Client;

class YsjdftzLog extends BaseController
{
    public function index()
    {
        $client = Client::getInstance()->getClient();
        $redis  = RedisManager::getRedisClient();
        $tableField  = array(
            "log_prop_ysjdftz"         => ['uid', 'name', 'scene','desc','type','number', 'node', 'create_time'],
        );

        $keys = $redis->keys('log_prop');

        foreach ($keys as $key => $tableName) 
        {
            $insertList = [];
            $fieldList = $this->getFieldList($tableName,$tableField);

            if(!$fieldList) continue;
            while ($logStr = $redis->lpop($tableName)) 
            {
                $log  = json_decode($logStr,true);
                $data = [];
                foreach ($fieldList as $key => $filedName) 
                {
                    if(!array_key_exists($filedName,$log)) continue;
                    $data[] = $log[$filedName];
                }

                if($data && count($fieldList) == count($data)) $insertList[] = $data;

                if(count($insertList) > 50000 )
                {
                    $client->insert($tableName,$insertList,$fieldList);
                    $insertList = [];
                } 

            }

            if($insertList) $client->insert($tableName,$insertList,$fieldList);
        }

    }

    public function getFieldList($logKey,$tableField)
    {
        $list = array();
        foreach($tableField as $tableName => $fieldList)
        {
            if(strpos($logKey,$tableName) !== 0) continue; 
            $list = $fieldList;
        }
        return $list;
    }
}
