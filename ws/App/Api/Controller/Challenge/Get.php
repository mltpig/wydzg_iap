<?php
namespace App\Api\Controller\Challenge;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigChallengeBoss;
use App\Api\Controller\BaseController;

//获取妖王挑战参数 
class Get extends BaseController
{

    public function index()
    {
        $costNum = 0;
        $config  = ConfigChallengeBoss::getInstance()->getOne( $this->player->getData('challenge') );
        if($config)
        {
            $count   = $this->player->getArg(CHALLENGE);
            $cost    = $config['repeat_cost'];
            $config  = ConfigParam::getInstance()->getFmtParam('WILDBOSS_REPEAT_COST_PARAM');
            $costNum = $cost['num'] * ($config[$count]/1000);
        }

        $result = [
            'lv'       => intval($this->player->getData('challenge')),
            'count'    => $this->player->getArg(CHALLENGE),
            'cost_num' => $costNum,
        ];

        $this->sendMsg( $result );

    }

}