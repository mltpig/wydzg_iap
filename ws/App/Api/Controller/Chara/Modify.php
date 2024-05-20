<?php
namespace App\Api\Controller\Chara;
use App\Api\Controller\BaseController;

class Modify extends BaseController
{

    public function index()
    {
        $type  = $this->param['type'];
        $value = $this->param['value'];
        $belong = $this->param['belong'];

        $chara = $this->player->getData('chara');
        $result = '类型未解锁';
        if(array_key_exists($type,$chara))
        {
            if($type == 1)
            {                
                $result = '形象未解锁';
                if(in_array($value,$chara[$type]))
                {
                    $this->player->setData('user','chara',['type' => $type,'value' => $value,'belong'=> $belong]);
                    $result = [ 
                        'user'    => $this->player->getUserInfo(),
                    ];
                }
            }else{
                $result = '形象未解锁';
                if(isset($chara[$type][$value]))
                {
                    $this->player->setData('user','chara',['type' => $type,'value' => $value ,'belong'=> $belong]);
                    $result = [ 
                        'user'    => $this->player->getUserInfo(),
                    ];
                }
            }
        }

        $this->sendMsg( $result);
    }

}