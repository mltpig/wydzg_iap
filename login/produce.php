<?php
return [
    'SERVER_NAME' => "EasySwoole",
    'MAIN_SERVER' => [
        'LISTEN_ADDRESS' => '0.0.0.0',
        'PORT' => 8061,
        'SERVER_TYPE' => EASYSWOOLE_WEB_SERVER, // 可选为 EASYSWOOLE_SERVER  EASYSWOOLE_WEB_SERVER EASYSWOOLE_WEB_SOCKET_SERVER
        'SOCK_TYPE' => SWOOLE_TCP,
        'RUN_MODEL' => SWOOLE_PROCESS,
        'SETTING' => [
            'worker_num' => 1,
            'reload_async' => true,
            'max_wait_time'=>3,
        ],
        'TASK'=>[
            'workerNum'=>1,
            'maxRunningNum'=>128,
            'timeout'=>15
        ]
    ],
    'TEMP_DIR' => null,
    'LOG_DIR' => null,
    'REDIS' => [
        'host'              => '127.0.0.1',
        'port'              => '7004',
        'auth'              => 'MCz7SS6TetjdzRx43f4LRdtSNPsaAFFYk2nRe7XLy8eecAE4xfBNPMyCizxZPAYPktn85r',
        'timeout'           => 3.0, // Redis 操作超时时间
        'reconnectTimes'    => 3, // Redis 自动重连次数
        'db'                => 0, // Redis 库
        'serialize'         => \EasySwoole\Redis\Config\RedisConfig::SERIALIZE_NONE, // 序列化类型，默认不序列化
        'packageMaxLength'  => 1024 * 1024 * 2, // 允许操作的最大数据
        'intervalCheckTime' => 15 * 1000, // 设置 连接池定时器执行频率
        'maxIdleTime'       => 10, // 设置 连接池对象最大闲置时间 (秒)
        'maxObjectNum'      => 5, // 设置 连接池最大数量
        'minObjectNum'      => 2, // 设置 连接池最小数量
        'getObjectTimeout'  => 3.0, // 设置 获取连接池的超时时间
        'loadAverageTime'   => 0.001, // 设置 负载阈值
    ],
];



