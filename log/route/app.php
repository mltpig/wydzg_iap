<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Route;

//clickhouse 数据 入库
Route::resource('log', 'Log')->only(['index']);
Route::resource('log_ysjdftz', 'YsjdftzLog')->only(['index']);

Route::rule('query','Query/index','POST');

// Route::miss(function() {
//     return json(['code' => 200 , 'data' => [] ]);
// });
