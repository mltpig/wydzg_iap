<?php 
namespace App\Api;

use EasySwoole\Socket\AbstractInterface\ParserInterface;
use EasySwoole\Socket\Client\WebSocket;
use EasySwoole\Socket\Bean\Caller;
use EasySwoole\Socket\Bean\Response;
use App\Api\Validate\ParamCheck;
use App\Api\Table\ApiStatus;

/**
 * Class WebSocketParser
 *
 * 此类是自定义的 websocket 消息解析器
 * 此处使用的设计是使用 json string 作为消息格式
 * 当客户端消息到达服务端时，会调用 decode 方法进行消息解析
 * 会将 websocket 消息 转成具体的 Class -> Action 调用 并且将参数注入
 *
 * @package App\Api\WebSocket
 */
class WebSocketParser implements ParserInterface
{
    /**
     * decode
     * @param  string         $raw    客户端原始消息
     * @param  WebSocket      $client WebSocket Client 对象
     * @return Caller         Socket  调用对象
     */
    public function decode($raw, $client) : ? Caller
    {
        // 解析 客户端原始消息
        $caller =  new Caller();

        $result  = ParamCheck::getInstance()->Validate($raw);
        $class   = $result['code'] ? '\\App\Api\\Controller\\Error' : $result['class'];
        $data    = $result['code'] ? [ 'msg' => $result['msg'] ] : $result['data'];
        $caller->setControllerClass($class);
        $caller->setAction('index');
        $caller->setArgs($data);

        // $method =  $result['code'] ? 'error' : $data['method'];
        // ApiStatus::getInstance()->incr( $method );
        return $caller;
    }
    /**
     * encode
     * @param  Response     $response Socket Response 对象
     * @param  WebSocket    $client   WebSocket Client 对象
     * @return string             发送给客户端的消息
     */
    public function encode(Response $response, $client) : ? string
    {
        /**
         * 这里返回响应给客户端的信息
         * 这里应当只做统一的encode操作 具体的状态等应当由 Controller处理
         */
        return $response->getMessage();
    }
}