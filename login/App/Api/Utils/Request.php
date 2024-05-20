<?php

namespace App\Api\Utils;
use EasySwoole\HttpClient\HttpClient;
use EasySwoole\Component\CoroutineSingleTon;

class Request
{

    use CoroutineSingleTon;

    public function http(string $url,string $type,array $param,array $headers = [],array $cookie = [] ,int $ms = 0):array
    {

        if($ms > 0 ) \Swoole\Coroutine::sleep($ms/1000);

        $client = new HttpClient($url);
        $client->setTimeout(10);
        $client->setConnectTimeout(10);

        if($cookie)  $client->addCookies($cookie);
        if($headers) $client->setHeaders($headers,false,false);

        switch ($type) 
        {
            case 'get':
                $response = $client->setQuery($param)->get();
                break;
            case 'post':
                $client->setContentTypeJson();
                $response = $client->postJson(json_encode($param,JSON_UNESCAPED_UNICODE));
            break;
        }
        
        return [$response->json(true) , $response->getBody() ];

    }

    
}