<?php
namespace App\Api\Controller\Cloud;
use App\Api\Controller\BaseController;
//抽卡
class Apply extends BaseController
{

    public function index()
    {
        $cloudid  = $this->param['id'];
        $cloud  = $this->player->getData('cloud');
        $result = '未解锁';
        if(in_array($cloudid,$cloud['list']) )
        {

            $this->player->setCloud('apply',$cloudid,'set');

            $result = [ 
                'cloud'  => $this->player->getData('cloud'),
            ];

        }

        $this->sendMsg( $result );
    }
}