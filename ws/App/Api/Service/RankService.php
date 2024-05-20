<?php
namespace App\Api\Service;
use App\Api\Utils\Keys;
use EasySwoole\Redis\Redis;
use EasySwoole\Pool\Manager as PoolManager;
use EasySwoole\Component\CoroutineSingleTon;

class RankService
{
    use CoroutineSingleTon;

	public function updateRankScore(string $name,int $score , string $member,int $site):void
	{
        if(!$score) return ;
        $rankName = Keys::getInstance()->getRankName($name,$site);
		PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use($rankName,$score,$member) {
            $redis->zAdd($rankName,$score,$member);
		});
	}

	public function updateRankScoreByIncr(string $name, string $member,int $site,int $score ):int
	{
        $rankName = Keys::getInstance()->getRankName($name,$site);
		return PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use($rankName,$score,$member) {
            return $redis->zInCrBy($rankName,$score,$member);
		});
	}

    public function getRankInfo(string $name , string $member , int $site , int $len=49):array
    {
        $rankName = Keys::getInstance()->getRankName($name,$site);
        $rankInfo = PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use($rankName,$member,$len) {
            return array(
                "myIndex"   => $redis->zRevRank($rankName,$member),
                "myScore"   => $redis->zScore($rankName,$member),
                "worldData" => $redis->zRevRange($rankName,0,$len,true)
            );
        });

        return array(
            array(
                'index' => is_null($rankInfo['myIndex'])  ? 0 : ++$rankInfo['myIndex'],
                'score' => is_null($rankInfo['myScore']) ? 0 : $rankInfo['myScore'],
            ),
            $this->getWorldRank($rankInfo['worldData']),
        );

    }

    public function getRankScoreByMember(string $name , string $member , int $site):array
    {
        $rankName = Keys::getInstance()->getRankName($name,$site);
        $rankInfo = PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use($rankName,$member) {
            return array(
                "myIndex"   => $redis->zRevRank($rankName,$member),
                "myScore"   => $redis->zScore($rankName,$member),
            );
        });

        return array(
            'index' => is_null($rankInfo['myIndex'])  ? 0 : ++$rankInfo['myIndex'],
            'score' => is_null($rankInfo['myScore']) ? 0 : $rankInfo['myScore'],
        );
    }    
    
    //组装世界等级排行数据
    public function getWorldRank(array $worldData):array
    {
        $worldRank = array();
        $index = 1;
        foreach ($worldData as $playerid => $value) 
        {   
            $worldRank[] = [ 'index' => $index,'playerid' => $playerid,'score' => $value ];
            $index++;
        }
        
        return $worldRank;
    }
    

    public function getRankNeighborInfo(string $name,string $playerid,int $site):array
    {
        $rankName = Keys::getInstance()->getRankName($name,$site);

        $res = PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use($rankName,$playerid) {

            $index = $redis->zRevRank($rankName,$playerid);
            if(is_null($index)) return [];
            $start = $index-10;
            return $redis->zRevRange($rankName,$start > 0 ? $start : 0,$index+10);
        });

        $list = [];
        foreach ($res as $id) 
        {
            if($id === $playerid ) continue;
            $list[$id] = $id;
        }
        
        return $list;
    }

    public function getRankPlayeridByIndex(string $name ,  int $site , int $len=49):array
    {
        $rankName = Keys::getInstance()->getRankName($name,$site);
        return PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use($rankName,$len) {
            return $redis->zRevRange($rankName,0,$len,true);
        });

    }  

    public function delRank(string $name ,int $site):void
    {
        $rankName = Keys::getInstance()->getRankName($name,$site);
        PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use($rankName) {
            return $redis->del($rankName);
        });

    }

    // 六道秘境成就排行榜
    public function getSecretTowerRankInfo(string $name, int $site, int $len=9):array
    {
        $rankName = Keys::getInstance()->getRankName($name,$site);
        $rankInfo = PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use($rankName, $len) {
            return [
                "worldData" => $redis->zRevRange($rankName,0,$len,true)
            ];
        });

        return $this->getSecretTowerRank($rankInfo['worldData']);
    }

    //组装六道秘境成就排行榜
    public function getSecretTowerRank(array $worldData):array
    {

        uasort($worldData, function($a, $b) {
            return $a - $b; // 升序排序 使用uasort()对数组进行排序
        });

        $worldRank = array();
        $index = 1;
        foreach ($worldData as $playerid => $value) 
        {   
            $worldRank[] = [ 'index' => $index,'playerid' => $playerid,'score' => $value ];
            $index++;
        }
        
        return $worldRank;
    }

}
