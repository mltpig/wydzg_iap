<?php
namespace App\Api\Controller\Player;
use App\Api\Service\TreeService;
use App\Api\Service\RedPointService;
use App\Api\Service\TaskService;
use App\Api\Service\ActivityService;
use App\Api\Controller\BaseController;

class Ping extends BaseController
{

    public function index()
    {
        $this->sendMsg( [
            'tree'         => TreeService::getInstance()->getShowTree($this->player),
            'goods'        => $this->player->getGoodsInfo(),
            'arg'          => $this->player->getArgInfo(),
            'task'  	   => TaskService::getInstance()->getShowTask(  $this->player->getData('task') ),
            'daily_reward' => ActivityService::getInstance()->getDailyRewardFmt($this->player),
            'redPoint'     => RedPointService::getInstance()->getRedPoints($this->player),
        ] );
    }

}