<?php
/**
 * Created by xiaoxing.
 * Date: 2019/10/15
 * Time: 11:05
 */

namespace app\lib\ClickHouse;

use think\facade\Config;
use app\lib\Component\Singleton;
use ClickHouseDB\Client as ClickHouseClient;
class Client
{
    use Singleton;

    private $dbClient;
    private $config;

    private function  __construct()
    {
        $this->config   = Config::get('clickhouse');
        $this->dbClient = $this->connectDb();
    }

    private function connectDb()
    {
        $db = new ClickHouseClient($this->config);
        $db->database($this->config['database']);
        $db->setTimeout(25);       // 10 seconds
        $db->setConnectTimeOut(5); // 5 seconds
        return $db;
    }

    public function getClient()
    {
        return $this->dbClient;
    }
}