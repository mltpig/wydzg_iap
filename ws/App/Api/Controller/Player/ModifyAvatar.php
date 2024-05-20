<?php
namespace App\Api\Controller\Player;
use App\Api\Controller\BaseController;

class ModifyAvatar extends BaseController
{

    public function index()
    {
        $type  = $this->param['type'];
        $value = $this->param['value'];

        $head = $this->player->getData('head');

        $result = '类型未解锁';
        if(array_key_exists($type,$head))
        {
            $result = '头像未解锁';
            if(in_array($value,$head[$type]))
            {
                $this->player->setData('user','head',['type' => $type,'value' => $value ]);
                
                $result = [ 
                    'user'    => $this->player->getUserInfo(),
                ];
                
            }
        }

        $this->sendMsg( $result );

    }

}