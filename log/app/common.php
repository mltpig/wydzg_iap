<?php

//清除缓存地址
defined('CLEAR_CACHE') or define('CLEAR_CACHE','http://shufen.shenzhenyuren.com/clear_cache');
defined('CLEAR_CHANNEL_CACHE') or define('CLEAR_CHANNEL_CACHE','http://shufen.shenzhenyuren.com/channel/clearCache');
defined('CLEAR_CACHE_USERNAME') or define('CLEAR_CACHE_USERNAME','rfKMF7CC6YAkypmdbpRCBKbBMjFjjs8wETba');
defined('CLEAR_CACHE_PASSWORD') or define('CLEAR_CACHE_PASSWORD','dPCG3mTN4MnrWR4WPti37eRT8FHMz5KBbH4CjrTRE7FmydeH');

defined('SUCCESS_CODE') or define('SUCCESS_CODE',200);
defined('ERROR_CODE') or define('ERROR_CODE',400);

defined('ROOT') or define('ROOT','szyrgameshufen');
defined('ROOT_WEIGHT') or define('ROOT_WEIGHT',99999999);

defined('ADMIN_KEY') or define('ADMIN_KEY','JMDpWovn2UVynQkC');
defined('ADMIN_IV') or define('ADMIN_IV','Yrmhlmz8rLhw25PZ');

//请同步上报接口配置
defined('PLATFORM') or define('PLATFORM',[
  ['id' => 0,'name' => '全部'],
  ['id' => 1,'name' => '安卓'],
  ['id' => 2,'name' => 'IOS'],
  ['id' => 3,'name' => '其他']
]);

//请同步上报接口配置
defined('SOURCE') or define('SOURCE',[
  ['id' => 0,'name' => '全部'],
  ['id' => 1,'name' => '自然量'],
  ['id' => 2,'name' => '字节'],
  ['id' => 3,'name' => '微信'],
  ['id' => 4,'name' => 'UC'],
  ['id' => 5,'name' => '华为'],
  ['id' => 6,'name' => 'Taptap'],
]);



function fmtJosn(int $code = 400 , array $data = [], string $msg = null):\think\response\Json
{
  return json(['code' => $code , "data" => $data , 'msg' => $msg ]);
}

function encrypt(string $data,string $key,string $iv)
{
  return base64_encode(openssl_encrypt($data, 'AES-128-CBC', $key, 1, $iv));
}

function decrypt(string $data,string $key,string $iv)
{
  return openssl_decrypt(base64_decode($data), 'AES-128-CBC', $key, 1, $iv);
}

function httpRequest(string $url, string $type='get',$data=null,array $header=[],bool $isDev=false)
{   
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3); // 连接超时（秒）
    curl_setopt($ch, CURLOPT_TIMEOUT, 4); // 执行超时（秒）

    if($header) curl_setopt($ch, CURLOPT_HTTPHEADER, $header);


    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); 


    if($type === 'post')
    {    
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }    

    
    $outPut = curl_exec($ch);
    
    if($isDev) var_dump($outPut);
    if($isDev) var_dump(curl_getinfo($ch,CURLINFO_HEADER_OUT));
    
    curl_close($ch);
    
    return $outPut;
}

function getDateFromRange(string $startdate, string $enddate):array{
  $stimestamp = strtotime($startdate);
  $etimestamp = strtotime($enddate);
  $days = ($etimestamp-$stimestamp)/86400+1;
  $date = array();
  for($i=0; $i<$days; $i++)
  {
    $date[] = date('Y-m-d', $stimestamp+(86400*$i));
  }
  return $date;
}

function getWhereSql(array $where){
  $sql = '';
  if(array_key_exists('platformId',$where) && $where['platformId'] > 0 ) $sql.= " and platform  = " . $where['platformId'] . " ";
  if(array_key_exists('nodeId',$where) && $where['nodeId'] > 0 )   $sql.= " and node  = " . $where['nodeId'] . " ";
  if(array_key_exists('sourceId',$where) && $where['sourceId'] > 0 )   $sql.= " and source  = " . $where['sourceId'] . " ";
  return $sql;
}

function getCaseSql(array $caseList,string $unit){
  $sql = 'case ';
  foreach ($caseList as $name => $value) 
  {
    $sql .= "when $unit >= ".$value[0]." and $unit < ".$value[1]." then '".$name."' ";
  }


  return $sql."ELSE '其他'  END AS level";
}

function bubbleSort($arr):array{
  $list = array();
  foreach ($arr as $key => $value) {
    if($value['children']) $value['children'] = bubbleSort($value['children']);
    $list[] = $value;
  }
  $len = count($list);
  for($i = 1; $i < $len; $i++){
   for($j = 0; $j < $len-$i; $j++) {
    if($list[$j]['lv'] > $list[$j+1]['lv']) {
     $tmp = $list[$j+1];
     $list[$j+1] = $list[$j];
     $list[$j] = $tmp;
    }
   }
  }
  return $list;
}