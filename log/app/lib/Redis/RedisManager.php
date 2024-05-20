<?php
/**
 * Created by xiaoxing.
 * Date: 2019/10/15
 * Time: 11:05
 */

namespace app\lib\Redis;

use think\facade\Config;

/**
 * redis 客户端单例获取
 * Class RedisManager
 * @package lib
 */
class RedisManager
{
    private static $redisClient;
    private static $config;

    private function  __construct()
    {
        self::$config = Config::get('redis');
        self::$redisClient = self::connectRedis();
    }

    private function  __clone(){}

    public static function getRedisClient()
    {
        $redisClass = \Redis::class;

        if ( ! ( self::$redisClient instanceof  $redisClass) )
        {
            new self();
        }

        return self::$redisClient;
    }
    private function connectRedis()
    {

        $redisClient = new \Redis();
        $redisClient->pconnect(self::$config['host'],self::$config['port']);
        if(self::$config['password']) $redisClient->auth(self::$config['password']);
        //$redisClient->setOption(\Redis::OPT_PREFIX,self::$config['prefix']);
        
        return $redisClient;


    }

    public static function closeRedis()
    {
        $redisClass =  \Redis::class;
        if (self::$redisClient instanceof  $redisClass) self::$redisClient->close();
    }

}