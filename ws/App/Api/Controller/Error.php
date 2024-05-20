<?php
namespace App\Api\Controller;
use EasySwoole\Socket\AbstractInterface\Controller;

class Error extends Controller
{
    public function index()
    {	
        $param = $this->caller()->getArgs();
        $param['code'] = ERROR;
    	$this->response()->setMessage(json_encode($param,272));
    }
}