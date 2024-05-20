<?php
namespace App\Api\Controller\Activity\NewYear;
use App\Api\Utils\Consts;
use App\Api\Service\ActivityService;
use App\Api\Controller\BaseController;


//登录奖励
class Get extends BaseController
{

    public function index()
    { 
        $time   = time();
        $begin  = strtotime(Consts::ACTIVITY_NEW_YEAR_BEGIN);
        $end    = strtotime(Consts::ACTIVITY_NEW_YEAR_END);

        $result = '活动未开启';
        if($time > $begin && $time < $end || $time == $begin)
        {
            $task   = $this->player->getData('task');
            $result = [
                'boxs' => ActivityService::getInstance()->getNewYearBoxs($this->player,$task),
                'task' => ActivityService::getInstance()->getNewYearTask($task),
            ] ;
        }

        $this->sendMsg( $result );
    }

}