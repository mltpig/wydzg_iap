<?php
namespace App\Api\Service;

use EasySwoole\Component\CoroutineSingleTon;

class ChallengeService
{
    use CoroutineSingleTon;

    public function dailyReset(PlayerService $playerSer):void
    {
        //重置每日妖王挑战次数
        $playerSer->setArg(CHALLENGE,1,'unset'); 
    }
}
