<?php
//全局bootstrap事件
date_default_timezone_set('Asia/Shanghai');
EasySwoole\Component\Di::getInstance()->set(EasySwoole\EasySwoole\SysConst::HTTP_CONTROLLER_MAX_DEPTH,5);
EasySwoole\Component\Di::getInstance()->set(EasySwoole\EasySwoole\SysConst::HTTP_CONTROLLER_NAMESPACE,'App\\Api\\Controller\\');


defined('SUCCESS') or define('SUCCESS',0);
defined('ERROR') or define('ERROR',1);


function encrypt(string $data,string $key,string $iv):string
{
    return base64_encode(openssl_encrypt($data, 'AES-128-CBC', $key, 1, $iv));
}

function decrypt(string $data,string $key,string $iv):string
{
    return openssl_decrypt(base64_decode($data), 'AES-128-CBC', $key, 1, $iv);
}