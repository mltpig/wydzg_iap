<?php

namespace App\Api\Service\Pay\Dev;

use App\Api\Service\PlayerService;
use EasySwoole\Component\CoroutineSingleTon;
use EasySwoole\Utility\SnowFlake;

class VoucherService
{
    use CoroutineSingleTon;

    public function __construct(PlayerService $player)
    {
        $this->player = $player;
    }

    //获取剩余数量
    public function getBalance(): int
    {
        //105047
        return $this->player->getGoods(105047);
    }

    public function pay(int $amount): array
    {
        $hasNum = $this->player->getGoods(105047);
        $num = $hasNum - $amount;
        $this->player->goodsBridge([['type' => GOODS_TYPE_1, 'gid' => 105047, 'num' => $num]], '支付测试22', $hasNum);
        return ['balance' => $num, 'bill_no' => strval(SnowFlake::make(rand(0, 31), rand(0, 127)))];
    }

    public function present(int $amount): int
    {
        $hasNum = $this->player->getGoods(105047);
        $num = $hasNum + $amount;
        $this->player->goodsBridge([['type' => GOODS_TYPE_1, 'gid' => 105047, 'num' => $num]], '支付测试11', $hasNum);
        return $num;
    }

    public function queryOrder(string $tradeNo): array
    {
        return array();

    }

}