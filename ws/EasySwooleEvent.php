<?php
namespace EasySwoole\EasySwoole;

use EasySwoole\EasySwoole\Swoole\EventRegister;
use EasySwoole\EasySwoole\AbstractInterface\Event;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use EasySwoole\Socket\Dispatcher;

use EasySwoole\EasySwoole\Config as GlobalConfig;

use EasySwoole\Pool\Config as PoolConfig;
use EasySwoole\Pool\Manager as PoolManager;
use EasySwoole\Redis\Config\RedisConfig;

use EasySwoole\ORM\DbManager;
use EasySwoole\ORM\Db\Config as DbConfig;
use EasySwoole\ORM\Db\Connection;

use App\Api\WebSocketParser;
use App\Api\WebSocketEvent;

use App\Api\Table\Table;

use App\Crontab\PlayerCacheToMysql;
use App\Crontab\DoufaDaily;
use App\Crontab\MonsterInvade;

use App\Process\LogProp;
use EasySwoole\Component\Process\Config as ProcessConfig;
use EasySwoole\Component\Process\Manager as ProcessManager;

class EasySwooleEvent implements Event
{

    public static function initialize()
    {
        date_default_timezone_set('Asia/Shanghai');

        $config      = new PoolConfig();
        $redisConfig = new RedisConfig(GlobalConfig::getInstance()->getConf('REDIS'));
        PoolManager::getInstance()->register(new \App\Pool\RedisPool($config,$redisConfig),'redis');

        $config      = new PoolConfig();
        $redisConfig = new RedisConfig(GlobalConfig::getInstance()->getConf('REDIS_LOG'));
        PoolManager::getInstance()->register(new \App\Pool\RedisPool($config,$redisConfig),'redis_log');
        //mysql
        $config = new DbConfig(GlobalConfig::getInstance()->getConf("MYSQL"));
        DbManager::getInstance()->addConnection(new Connection($config));
    }

    public static function mainServerCreate(EventRegister $register)
    {
        Table::getInstance()->create();
        
        // 创建一个 Dispatcher 配置
        $conf = new \EasySwoole\Socket\Config();
        // 设置 Dispatcher 为 WebSocket 模式
        $conf->setType(\EasySwoole\Socket\Config::WEB_SOCKET);
        // 设置解析器对象
        $conf->setParser(new WebSocketParser());
        // 创建 Dispatcher 对象 并注入 config 对象
        $dispatch = new Dispatcher($conf);
        // 给server 注册相关事件 在 WebSocket 模式下  on message 事件必须注册 并且交给 Dispatcher 对象处理
        $register->set(EventRegister::onMessage, function (\swoole_websocket_server $server, \swoole_websocket_frame $frame) use ($dispatch) {
            $dispatch->dispatch($server, $frame->data, $frame);
        });        
        //自定义事件
        $register->set(EventRegister::onOpen, [WebSocketEvent::class,'onOpen']);         
        $register->set(EventRegister::onRequest, [WebSocketEvent::class,'onRequest']);         
        $register->set(EventRegister::onClose, [WebSocketEvent::class,'onClose']);
        // $register->set(EventRegister::onPipeMessage, [WebSocketEvent::class,'onPipeMessage']);
        
        // 配置定时任务
        $crontabConfig = new \EasySwoole\Crontab\Config();
        // 1.设置执行定时任务的 socket 服务的 socket 文件存放的位置，默认值为 当前文件所在目录
        // 这里设置为框架的 Temp 目录
        $crontabConfig->setTempDir(EASYSWOOLE_TEMP_DIR);
        // 2.设置执行定时任务的 socket 服务的名称，默认值为 'EasySwoole'
        $crontabConfig->setServerName('EasySwoole');
        // 3.设置用来执行定时任务的 worker 进程数，默认值为 3
        $crontabConfig->setWorkerNum(3);
        // 4.设置定时任务执行出现异常的异常捕获回调
        $crontabConfig->setOnException(function (\Throwable $throwable) {
            // 定时任务执行发生异常时触发（如果未在定时任务类的 onException 中进行捕获异常则会触发此异常回调）
        });
        // 创建定时任务实例
        $crontab = \EasySwoole\EasySwoole\Crontab\Crontab::getInstance($crontabConfig);
        // 注册定时任务
        $crontab->register(new PlayerCacheToMysql());
        $crontab->register(new DoufaDaily());
        $crontab->register(new MonsterInvade());

        $register->add($register::onWorkerStart, function (\Swoole\Server $server,int $workerId){
            if($workerId !== 0) return;
            \EasySwoole\Component\Timer::getInstance()->after(1 * 1000, function () {
                Table::getInstance()->reset();
            });
        });

        $processConfig = new ProcessConfig([
            'processName'     => 'Test', // 设置 自定义进程名称
            'processGroup'    => 'Custom', // 设置 自定义进程组名称
            'enableCoroutine' => true, // 设置 自定义进程自动开启协程
        ]);
        ProcessManager::getInstance()->addProcess(new LogProp($processConfig));
    }

    public static function onRequest(Request $request, Response $response): bool
    {
        $serverInfo = $request->getServerParams();
        if ($serverInfo['path_info'] == '/favicon.ico' || $serverInfo['request_uri'] == '/favicon.ico') 
        {
            $response->withStatus(404);
            return false;
        }
        return true;
    }

    public static function afterRequest(Request $request, Response $response): void
    {
        // TODO: Implement afterAction() method.
    }
}