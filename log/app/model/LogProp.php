<?php
namespace app\model;
use app\lib\ClickHouse\Client;

class LogProp 
{

    public function getPageData(array $param):array
    {
        $tableName = $param['table'];

        $client = Client::getInstance()->getClient();
        $time = date('Y-m-d',strtotime('+1day',strtotime($param['endTime'])));
        $sql = "select * from $tableName 
                    where create_time  >= '" .$param['startTime'] . "' 
                        and create_time  < '" . $time . "' ";
                        
        if(array_key_exists('type',$param) && $param['type'] )   $sql.= " and type  = " . $param['type'] . " ";
        if(array_key_exists('uid',$param) && $param['uid'] ) $sql.= " and uid  = '" . $param['uid'] . "'  ";
        if(array_key_exists('scene',$param) && $param['scene'] )   $sql.= " and scene  = '" . $param['scene'] . "'  ";
        if(array_key_exists('goods',$param) && $param['goods'] )   $sql.= " and name  = '" . $param['goods'] . "'  ";

        $start = $param['pagenum'] > 0 ? ($param['pagenum']-1)*$param['pagesize'] : 0;

        $sql .= " order by create_time desc limit ".$start.",".$param['pagesize'].";";

        return  $client->select($sql)->rows();
    }
    
    public function getCount(array $param)
    {
        $tableName = $param['table'];

        $client = Client::getInstance()->getClient();
        $time = date('Y-m-d',strtotime('+1day',strtotime($param['endTime'])));
        $sql = "select count(uid) as total from $tableName 
                    where create_time  >= '" .$param['startTime'] . "' 
                        and create_time  < '" . $time . "' ";
        if(array_key_exists('type',$param) && $param['type'] )   $sql.= " and type  = " . $param['type'] . " ";
        if(array_key_exists('uid',$param) && $param['uid'] ) $sql.= " and uid  = '" . $param['uid'] . "' ";
        if(array_key_exists('scene',$param) && $param['scene'] )   $sql.= " and scene  = '" . $param['scene'] . "' ";
        if(array_key_exists('goods',$param) && $param['goods'] )   $sql.= " and name  = '" . $param['goods'] . "' ;";

        return  $client->select($sql)->rows();
    }
}
