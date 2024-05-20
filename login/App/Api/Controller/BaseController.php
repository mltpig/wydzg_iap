<?php
namespace App\Api\Controller;
use App\Api\Validate\CheckIn;
use EasySwoole\Http\AbstractInterface\Controller;

class BaseController extends Controller
{
    public $param;
    public $uri;

    protected function onRequest(?string $action): ?bool
    {

        $this->uri = ltrim($this->request()->getServerParams()['request_uri'],'/');

        $request = $this->request()->getQueryParams();

        $result  = CheckIn::getInstance()->getValidateData($this->uri,$request);

        if(array_key_exists('err_code',$result)) return $this->rJson($result['msg'],true);

        $this->param = $result;
        
        return true;
    }

    protected function rJson($result,bool $force = false):bool
    {

        if (!$this->response()->isEndResponse()) 
        {
            $data =  !is_array($result) ? [ "code"=> 400 , "msg" => $result ]: [ "code"=> 200 , "data" => $result ];
            
            $this->response()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
            $this->response()->withStatus(200);

            return $force === false ? true : false;
        } else {
            return false;
        }
    }
}