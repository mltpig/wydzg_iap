<?php

defined("CHANNEL") or define("CHANNEL", "wydzg_yzy_iap" );
defined("CHANNEL_PAY") or define("CHANNEL_PAY", "weixin" );
defined("TABLE_UID_FD") or define("TABLE_UID_FD", "table_uid_fd" );
defined("TABLE_FD_UID") or define("TABLE_FD_UID", "table_fd_uid" );
defined("TABLE_UID_LOCK") or define("TABLE_UID_LOCK", "table_uid_lock" );

defined('SUCCESS') or define('SUCCESS',0);
defined('ERROR') or define('ERROR',1);
defined('RELOGIN') or define('RELOGIN',2);
defined('REMOTE_LOGIN') or define('REMOTE_LOGIN',3);
defined('MANY_REQUEST') or define('MANY_REQUEST',4);

defined('USER_SET') or define('USER_SET','user_set');

defined('RANK_DOUFA') or define('RANK_DOUFA','doufa');
defined('RANK_RUQIN') or define('RANK_RUQIN','ruqin');
defined('RANK_TOWER') or define('RANK_TOWER','tower');
defined('RANK_SECRET') or define('RANK_SECRET','secret');
defined('RANK_SECRET_TOWER') or define('RANK_SECRET_TOWER','secret_tower:');

defined('CONFIG_ROBOT') or define('CONFIG_ROBOT','config_robot');
defined('CONFIG_MONSTER_INVADE') or define('CONFIG_MONSTER_INVADE','config_monster_invade');

     
defined('EXP') or define('EXP',10);
defined('XIANYU') or define('XIANYU',100000);
defined('LINGSHI') or define('LINGSHI',100003);
defined('XIANTAO') or define('XIANTAO',100004);
defined('GENGJIN') or define('GENGJIN',100005);
defined('HUNYUANSHI') or define('HUNYUANSHI',100006);
defined('PUSHI') or define('PUSHI',100007);
defined('HUFU') or define('HUFU',100009);
defined('QIUXIANLING') or define('QIUXIANLING',100010);
defined('RENAME') or define('RENAME',100012);
defined('QINGPU') or define('QINGPU',100016);
defined('TAOHUAZHI') or define('TAOHUAZHI',100023);
defined('JINGSHUIPING') or define('JINGSHUIPING',100025);
defined('TIAOZHANQUAN') or define('TIAOZHANQUAN',100026);
defined('LIULIZHU') or define('LIULIZHU',100029);

defined('GOODS_TYPE_1') or define('GOODS_TYPE_1',1);//普通道具
defined('GOODS_TYPE_2') or define('GOODS_TYPE_2',2);//好感度道具（贤士）
defined('GOODS_TYPE_3') or define('GOODS_TYPE_3',3);//副将
defined('GOODS_TYPE_5') or define('GOODS_TYPE_5',5);//红颜碎片
defined('GOODS_TYPE_6') or define('GOODS_TYPE_6',6);//随机宝箱
defined('GOODS_TYPE_7') or define('GOODS_TYPE_7',7);//自选宝箱
defined('GOODS_TYPE_8') or define('GOODS_TYPE_8',8);//战技
defined('GOODS_TYPE_14') or define('GOODS_TYPE_14',14);//附魂外观兑换道具
defined('GOODS_TYPE_19') or define('GOODS_TYPE_19',19);//自选法宝碎片
defined('GOODS_TYPE_23') or define('GOODS_TYPE_23',23);//刻印
defined('GOODS_TYPE_100') or define('GOODS_TYPE_100',100);//特殊装备

//控制参数
defined('COUNTER_EQUIP') or define('COUNTER_EQUIP',101);
defined('COUNTER_RENAME') or define('COUNTER_RENAME',RENAME);
defined('COUNTER_CODE') or define('COUNTER_CODE',102);
defined('COUNTER_CLOUD_UP') or define('COUNTER_CLOUD_UP',103);
defined('COUNTER_TASK') or define('COUNTER_TASK',104);
defined('COUNTER_LOGIN') or define('COUNTER_LOGIN',105);
defined('COUNTER_NEW') or define('COUNTER_NEW',106);
//是否首冲
defined('COUNTER_FR') or define('COUNTER_FR',107);
//广告光看次数
defined('COUNTER_AD') or define('COUNTER_AD',108);
//每日福利领取次数
defined('COUNTER_DAILY_REWARD') or define('COUNTER_DAILY_REWARD',109);
//每日福利领取间隔限制
defined('COUNTER_DAILY_REWARD_CD') or define('COUNTER_DAILY_REWARD_CD',110);
//玩家上次登录周数
defined('COUNTER_WEEK') or define('COUNTER_WEEK',111);
//妖王挑战当天挑战次数
defined('CHALLENGE') or define('CHALLENGE',112);
//异兽入侵当天挑战次数
defined('INVADE') or define('INVADE',113);
//福地每日广告刷新次数
defined('PARADISE_AD_REFRES_GOODS') or define('PARADISE_AD_REFRES_GOODS',114);
//福地所有物品上次刷新时间
defined('PARADISE_AUTO_REFRESH_TIME') or define('PARADISE_AUTO_REFRESH_TIME',115);
//一日秒时长
defined('DAY_LENGHT') or define('DAY_LENGHT',86400);
//周秒时长
defined('WEEK_LENGHT') or define('WEEK_LENGHT',604800);
//开服时间 2023-12-20
defined('OPEN_DATE') or define('OPEN_DATE',1703001600);

function randTable(array $data):int
{
    $range = $id  =0;
    $rand = rand(1,array_sum($data));
    foreach ($data as $key => $number) 
    {
        $range += $number;
        if($rand <= $range)
        {
            $id = $key;
            break;
        }
    }
    return $id;
}

//加
function add(string $num1,string $num2,$len=0)
{
    return bcadd(strval($num1),strval($num2),$len);
}

//减
function sub($num1,$num2,int $len=0)
{
    return bcsub(strval($num1),strval($num2),$len);
}

//乘
function mul($num1,$num2,int $len=0)
{
    return bcmul(strval($num1),strval($num2),$len);
}

//除
function div($num1,$num2,int $len=0)
{
    return bcdiv(strval($num1),strval($num2),$len);
}

function getMsectime()
{ 
    list($msec, $sec) = explode(' ', microtime());
    $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    return $msectime;
}

function getWeekNum(int $time):int
{ 
    //230400 1970-01-04 1-4日为 星期4-星期五
   return ceil( ($time-316800) / WEEK_LENGHT ) + 1;
}

function getFmtGoods(array $goods):array
{ 
   return ['gid' => $goods[0] , 'num' => intval($goods[1]) ];
}

//保持位数即可
function numToStr($num)
{
    if (stripos($num,'e') === false) return $num;
    $num = trim(preg_replace('/[=\'"+]/','',$num,1),'"');
    list($string,$len) = explode('e',$num);
    return bcmul($string,bcpow('10',$len));
}

function encrypt(string $data,string $key,string $iv):string
{
    return base64_encode(openssl_encrypt($data, 'AES-128-CBC', $key, 1, $iv));
}

function decrypt(string $data,string $key,string $iv):string
{
    return openssl_decrypt(base64_decode($data), 'AES-128-CBC', $key, 1, $iv);
}

function bubbleSort(array $list,string $field) 
{
    $len = count($list);
    for ($i=0; $i<$len-1; $i++) {
        for ($j=$len-1; $j>$i; $j--) {
            if ($list[$j][$field] < $list[$j-1][$field]) {
                // 交换位置
                $temp = $list[$j];
                $list[$j] = $list[$j-1];
                $list[$j-1] = $temp;
            }
        }
    }
    
    return $list;
}

