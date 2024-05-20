<?php
namespace App\Api\Controller;
use App\Api\Validate\Check;
use EasySwoole\Http\AbstractInterface\Controller;

class BaseController extends Controller
{
    public $param;

    protected function onRequest(?string $action): ?bool
    {

        $tag   = CHANNEL.'/';
        $uri = ltrim($this->request()->getServerParams()['request_uri'],'/');
        strpos($uri , $tag) !== 0 ? : $uri = substr($uri,strlen($tag));

        if(!$body = $this->request()->getBody()->__toString()) return $this->rJson('body不可为空',true);
        $request = json_decode($body,true);

        if(!is_array($request)) return $this->rJson('body格式错误',true);

        $result  = Check::getInstance()->getValidateData($uri,$request);

        if(array_key_exists('err_code',$result)) return $this->rJson($result['msg'],true);

        $this->param = $result;
        
        return true;
    }

    protected function rJson($result,bool $force = false):bool
    {

        if (!$this->response()->isEndResponse()) 
        {
            $data =  !is_array($result) ? [ "code"=> ERROR , "msg" => $result ]: [ "code"=> SUCCESS , "data" => $result ];
            
            $this->response()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
            $this->response()->withStatus(200);

            return $force === false ? true : false;
        } else {
            return false;
        }
    }
}