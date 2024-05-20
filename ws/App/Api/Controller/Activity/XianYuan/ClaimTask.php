<?php
namespace App\Api\Controller\Activity\XianYuan;
use App\Api\Utils\Consts;
use App\Api\Table\ConfigTask;
use App\Api\Service\Module\XianYuanService;
use App\Api\Controller\BaseController;

class ClaimTask extends BaseController
{

    public function index()
    {
        $task       = $this->player->getData('task');
        $taskInfo   = XianYuanService::getInstance()->getXianYuanTask($task);
        $rewards    = [];
        foreach($taskInfo as $k => $v)
        {
            if($v['state'] != 1) continue;
            $this->player->setTask($v['id'],1,2,'set');

            $taskConfig = ConfigTask::getInstance()->getOne($v['id']);
            $this->player->goodsBridge($taskConfig['rewards'],'仙缘领取任务奖励');

            $this->player->setArg($taskConfig['rewards'][0]['gid'], $taskConfig['rewards'][0]['num'], 'add');

            foreach($taskConfig['rewards'] as $reward)
            {
                $rewards[] = $reward;
            }
        }

        $result = [
            'xianyuan'  => XianYuanService::getInstance()->getXianYuanFmtData($this->player),
            'reward'    => XianYuanService::getInstance()->aggregateAwards($rewards)
        ];

        $this->sendMsg( $result );
    }

}