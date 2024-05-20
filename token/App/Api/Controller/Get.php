<?php
namespace App\Api\Controller;
use App\Api\Utils\Consts;
use App\Api\Service\WeixinService;
use App\Api\Controller\BaseController;

class Get extends BaseController
{

    public function index()
    {
        $tag  = decrypt($this->param['tag'],Consts::AES_KEY,Consts::AES_IV);
        $code = decrypt($this->param['code'],Consts::AES_KEY,Consts::AES_IV);

        $result = [];

        if($tag === 'dividendr3WSzC7ZxJ' && $code === 'bA6FjyenSbPBsfAaT5x5')
        {
            try {

                $token  = WeixinService::getInstance()->getAppToken();
                
                $result = [
                    'token' => encrypt($token,Consts::AES_KEY,Consts::AES_IV) 
                ];
                
            } catch (\Throwable $th) {
    
                $result = $th->getMessage();
            }
        }

        return $this->rJson( $result );

    }
}