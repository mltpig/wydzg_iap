<?php
namespace App\Api\Controller\Player;
use App\Api\Controller\BaseController;

class Touch extends BaseController
{

    public function index()
    {
        $this->sendMsg( [] );
    }

}