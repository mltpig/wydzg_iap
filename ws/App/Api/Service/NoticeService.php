<?php
namespace App\Api\Service;

use App\Api\Utils\Keys;
use EasySwoole\Redis\Redis;
use EasySwoole\Pool\Manager as PoolManager;
use EasySwoole\Component\CoroutineSingleTon;

class NoticeService
{
    use CoroutineSingleTon;

    public function getList():array
    {
        $noticeKey = Keys::getInstance()->getNoticeKey();
        $notice = PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use($noticeKey) {
            return $redis->get($noticeKey);
        });

        if(!$notice) return [];
        $detail = json_decode($notice,true);

        $time = time();

        if($time < $detail['start_time'] || $time > $detail['end_time']) return [];

        return [ 'title'    => $detail['title'],'content'    => $detail['content'] ];

    }


}
