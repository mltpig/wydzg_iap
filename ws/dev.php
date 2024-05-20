<?php
return [
    'SERVER_NAME' => "异世界巅峰挑战",
    'MAIN_SERVER' => [
        'LISTEN_ADDRESS' => '0.0.0.0',
        'PORT' => 8025,
        'SERVER_TYPE' => EASYSWOOLE_WEB_SOCKET_SERVER, // 可选为 EASYSWOOLE_SERVER  EASYSWOOLE_WEB_SERVER EASYSWOOLE_WEB_SOCKET_SERVER
        'SOCK_TYPE' => SWOOLE_TCP,
        'RUN_MODEL' => SWOOLE_PROCESS,
        'SETTING' => [
            'worker_num' => 8,
            'reload_async' => true,
            'max_wait_time'=>3,
            'heartbeat_check_interval'=> 60,
            'heartbeat_idle_time'=> 60,
        ],
        'TASK'=>[
            'workerNum'=>4,
            'maxRunningNum'=>128,
            'timeout'=>15
        ]
    ],
    'TEMP_DIR' => null,
    'LOG_DIR' => null,
    'MYSQL' => [
        'host'              => '127.0.0.1',
        'port'              => '3306',
        'user'              => 'wydzg',
        'timeout'           => '5',
        'charset'           => 'utf8mb4',
        'password'          => 'NKibJRPzYSWWhdRH',
        'database'          => 'wydzg',
        'intervalCheckTime' => 15 * 1000, // 设置 连接池定时器执行频率
        'maxIdleTime'       => 10, // 设置 连接池对象最大闲置时间 (秒)
        'maxObjectNum'      => 20, // 设置 连接池最大数量
        'minObjectNum'      => 5, // 设置 连接池最小数量
        'getObjectTimeout'  => 5.0, // 设置 获取连接池的超时时间
        'loadAverageTime'   => 0.001, // 设置 负载阈值
    ],
    'REDIS' => [
        'host'              => '127.0.0.1',
        'port'              => '7004',
        'auth'              => '',
        'timeout'           => 3.0, // Redis 操作超时时间
        'reconnectTimes'    => 3, // Redis 自动重连次数
        'db'                => 0, // Redis 库
        'serialize'         => \EasySwoole\Redis\Config\RedisConfig::SERIALIZE_NONE, // 序列化类型，默认不序列化
        'packageMaxLength'  => 1024 * 1024 * 2, // 允许操作的最大数据
        'intervalCheckTime' => 15 * 1000, // 设置 连接池定时器执行频率
        'maxIdleTime'       => 10, // 设置 连接池对象最大闲置时间 (秒)
        'maxObjectNum'      => 20, // 设置 连接池最大数量
        'minObjectNum'      => 5, // 设置 连接池最小数量
        'getObjectTimeout'  => 3.0, // 设置 获取连接池的超时时间
        'loadAverageTime'   => 0.001, // 设置 负载阈值
    ],
    'REDIS_LOG' => [
        'host'              => '1.13.6.99',
        'port'              => '8527',
        'auth'              => '7rBHWK6nHX8NEh27Jf7BCsCJHHbAFKrhMBC72edTK8mZtac8xaMdX3Tr5X5pyxa4rHTXBB3AhnSb82p4x5AM4ZfLRYS6pARAEsmHX2M8P5kbsPRrHT468H8anHLkMWT37t6bctPD68wSrhEC',
        'timeout'           => 3.0, // Redis 操作超时时间
        'reconnectTimes'    => 3, // Redis 自动重连次数
        'db'                => 0, // Redis 库
        'serialize'         => \EasySwoole\Redis\Config\RedisConfig::SERIALIZE_NONE, // 序列化类型，默认不序列化
        'packageMaxLength'  => 1024 * 1024 * 2, // 允许操作的最大数据
        'intervalCheckTime' => 15 * 1000, // 设置 连接池定时器执行频率
        'maxIdleTime'       => 10, // 设置 连接池对象最大闲置时间 (秒)
        'maxObjectNum'      => 20, // 设置 连接池最大数量
        'minObjectNum'      => 5, // 设置 连接池最小数量
        'getObjectTimeout'  => 3.0, // 设置 获取连接池的超时时间
        'loadAverageTime'   => 0.001, // 设置 负载阈值
    ],
];



