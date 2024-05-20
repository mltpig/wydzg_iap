<?php
namespace App\Api\Controller\Activity\signIn;

use App\Api\Controller\BaseController;
use App\Api\Table\Activity\SignIn as ActivitySignIn;

class Get extends BaseController
{

    public function index()
    { 


        
        $config  = ActivitySignIn::getInstance()->getAll();
        
        $result  = [];
        list($day,$idState)  = $this->player->getTmp('sign_in');

        foreach ($config as $value) 
        {
            // 0未达成  1 已达成 2已领取
            $state = $day >= $value['day_num'] ? 1 : 0;
            if($idState >= $value['id']) $state = 2;

            $result[] = [
                'id'      => $value['id'],
                'state'   => $state,
                'day_num' => $value['day_num'],
                'reward'  => $value['reward'],
            ];
        }

        $this->sendMsg([ 'list' => $result ,'day' => $day ]  );
    }

}