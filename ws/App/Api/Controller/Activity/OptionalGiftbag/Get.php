<?php
namespace App\Api\Controller\Activity\OptionalGiftbag;

use App\Api\Controller\BaseController;
use App\Api\Table\Activity\OptionalGiftbag as ActivityOptionalGiftbag;


class Get extends BaseController
{

    public function index()
    { 

        $config  = ActivityOptionalGiftbag::getInstance()->getAll();
        $result  = [];

        $ogb = $this->player->getTmp('ogb');
        foreach ($config as $key => $value) 
        {
            $value['group']   = $key;
            $value['remain']  = $this->player->getArg($key);
            $value['receive'] = !is_null($ogb) && array_key_exists($key,$ogb) ? $ogb[$key]: [];

            $result[] = $value;
        }

        $this->sendMsg( $result );
    }

}