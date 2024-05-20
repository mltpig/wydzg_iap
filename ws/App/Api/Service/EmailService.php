<?php
namespace App\Api\Service;
use App\Api\Utils\Keys;
use EasySwoole\Redis\Redis;
use EasySwoole\Pool\Manager as PoolManager;
use EasySwoole\Component\CoroutineSingleTon;

class EmailService
{
    use CoroutineSingleTon;

    public function getEamils(string $uid,int $site,int $type):array
    {
        $emailKey  = Keys::getInstance()->getEmailKey($uid,$site,$type);
        $emailList = PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use($emailKey) {
            return $redis->hGetAll($emailKey);
        });

        $now  = time();
        $list = [];
        foreach ($emailList as $emailid => $value) 
        {
            $detail = json_decode($value,true);
            if( $detail['end_time'] < $now )
            {
                $this->delete($uid,$site,$type,$emailid);
                continue;
            }

            //0 未阅读 1 已阅读 2 已领取
            $list[] = [
                'id'         => strval($emailid),
                'title'      => $detail['title'],
                'content'    => $detail['content'],
                'start_time' => date('Y-m-d',$detail['start_time']),
                'timeout'    => floor( ($detail['end_time'] - $now) / DAY_LENGHT),
                'reward'     => $detail['reward'],
                'from'       => $detail['from'],
                'state'      => $detail['state'],
            ];

        }
        
        return $list;
    }

    public function getAll(string $uid,int $site,int $type):array
    {
        $emailKey = Keys::getInstance()->getEmailKey($uid,$site,$type);
        $emails   = PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use($emailKey) {
            return $redis->hGetAll($emailKey);
        });

        $list = [];
        foreach ($emails as $key => $value) 
        {
            $list[$key] = json_decode($value,true);
        }
        
        return $list;
    }

    public function getOne(string $uid,int $site,int $type,string $emailid):array
    {
        $emailKey = Keys::getInstance()->getEmailKey($uid,$site,$type);
        $value  = PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use($emailKey,$emailid) {
            return $redis->hget($emailKey,$emailid);
        });

        return $value ? json_decode($value,true) : [];
    }

    public function set(string $uid,int $site,int $type,string $emailid,array $email):void
    {
        $emailKey = Keys::getInstance()->getEmailKey($uid,$site,$type);
        $content = json_encode($email);
        PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use($emailKey,$emailid,$content) {
            $redis->hSet($emailKey,$emailid,$content);
        });
    }

    public function delete(string $uid,int $site,int $type,string $emailid):void
    {
        $emailKey = Keys::getInstance()->getEmailKey($uid,$site,$type);
        PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use($emailKey,$emailid) {
            $redis->hDel($emailKey,$emailid);
        });
    }
}
