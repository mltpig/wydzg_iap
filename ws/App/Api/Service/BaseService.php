<?php
namespace App\Api\Service;

use App\Api\Model\Player;
use App\Api\Utils\Keys;
use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigCloud;
use App\Api\Table\ConfigRoleChara;
use App\Api\Table\ConfigNickname;
use EasySwoole\ORM\DbManager;
use EasySwoole\Redis\Redis;
use EasySwoole\Pool\Manager as PoolManager;
use EasySwoole\Utility\SnowFlake;

class BaseService
{
    protected $openid       = null; //String   ID
    protected $roleid       = null; //String   ID
    protected $site         = null; //Int      区服
    protected $last_time    = null; //String   最后一次存档时间
    protected $create_time  = null; //String   账号创建时间
    public    $playerKey    = null; //String   玩家KEY
    public    $fd           = null; //String   玩家KEY
    protected $role         = null; //Array  角色信息
    protected $goods        = null; //Array  背包物品
    protected $arg          = null; //Array  int   控制参数
    protected $tmp          = null; //Array  字符串 临时保存、控制参数;
    protected $task         = null; //Array  任务
    protected $tree         = null; //Array[lv,state,timestamp]  仙树
    protected $equip        = null; //Array [ 1:[等级，品质，攻击。生命，防御，敏捷]...] 装备
    protected $equip_tmp    = null; //Array 抽卡装备放置区
    protected $chapter      = null; //int 关卡冒险;
    protected $ext          = null; //客户端自保存数据;
    protected $cloud        = null; //座驾
    protected $head         = null; //头像[1:境界头像，2:渠道头像]
    protected $chara        = null; //模型[1: 境界,2: 活动]]
    protected $user         = null; //模型[]
    protected $doufa        = null; //斗法
    protected $challenge    = null; //int 挑战妖王;
    protected $paradise     = null; //Array 福地 交由actor处理，此处只读，不保存
    protected $comrade      = null; //Array [ list id : ['lv' => 1,'step' => 0,'battle' => 0] ] 贤士
    protected $pet          = null; //Array [ list id : ['lv' => 1,'step' => 0,'battle' => 0] ] 武将
    protected $spirit       = null; //Array [ list id : ['lv' => 1,'step' => 0,'battle' => 0] ] 红颜
    protected $tactical     = null; //Array  阵法
    protected $tower        = null; //Array [ id : 1, buffnum : 0, 'bufftemp' => [], 'buff' => [] ] 镇妖塔
    protected $equipment    = null;
    protected $demon_trail  = null;
    protected $secret_tower = null;
    protected $magic = null;
    protected $fund  = null;
    protected $xianyuan  = null;
    protected $shanggu   = null;

    public function __construct(string $openid,int $site,int $fd = null)
    {
        $this->fd       = $fd;
        $this->site     = $site;
        $this->openid   = $openid;

        $this->playerKey = Keys::getInstance()->getPlayerKey($openid,$site);

        $this->getPlayerInfo();
    }

    //获取用户数据
    public function getPlayerInfo(): void
    {
        if ($userData = $this->findUserData()) $this->init($userData);
    }

    //查找用户
    public function findUserData(): array
    {
        $userCache = PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) {
            return $redis->hGetAll($this->playerKey);
        });

        if (!empty($userCache)) return $userCache;

        $userObj = DbManager::getInstance()->invoke(function ($client) {
            return Player::invoke($client)->get(['openid' => $this->openid,'site' => $this->site]);
        });

        if (is_null($userObj)) return array();

        return $this->mysql2Cache($userObj->toArray());
    }

    //用户数据初始化
    private function init(array $userData): void
    {

        foreach ($userData as $name => $val)
        {
            if (!property_exists($this, $name) || in_array($name,['playerKey','fd'])) continue;
            $data = $name != 'ext' ? json_decode($val,true) : $val;
            $this->{$name} = is_array($data) ? $data : $val;
        }

    }

    //注册
    public function signup(): bool
    {
        $goodsInit   = ConfigParam::getInstance()->getIniGoodss();
        $cloudInitId = ConfigCloud::getInstance()->getIntCloud();
        $charaData    = ConfigRoleChara::getInstance()->getIntHead();
        $charaid =  $charaData ? strval($charaData['id']) : "0";
        $belong =  $charaData ? strval($charaData['belong']) : -1;
        $taskInit    = TaskService::getInstance()->getInitTask();
        $tactical    = TacticalService::getInstance()->getInitTactical();
        $user = [
            'head'         => ['type' => 1, 'value' => strval($charaid) ],
            'chara'        => ['type' => 1, 'value' => strval($charaid) ,'belong'=>(int)$belong],
            'nickname'     => ConfigNickname::getInstance()->getNickname(),
        ];
        $userData = array(
            'openid'      => $this->openid,
            'roleid'      => strval(SnowFlake::make(rand(0,31),rand(0,127))),
            'site'        => $this->site,
            'last_time'   => date('Y-m-d H:i:s'),
            'create_time' => date('Y-m-d H:i:s'),
            'role'        => json_encode(['lv' => 1,'exp' => 0 ]),
            'goods'       => json_encode($goodsInit),
            'arg'         => json_encode([ COUNTER_WEEK => getWeekNum( time() )]),
            'task'        => json_encode($taskInit),
            'tree'        => json_encode(['lv' => 1 ,'state' => 0 ,'timestamp' => 0]),
            'equip'       => json_encode([1 => [],2 => [],3 => [],4 => [],5 => [],6 => [],7 => [],8 => [],9 => [],10 => [],11 => [],12=> []]),
            'equip_tmp'   => json_encode([]),
            'head'        => json_encode([ 1 => [ strval($charaid) ] ]),
            'chara'       => json_encode([ 1 => [ strval($charaid) ] ]),
            'user'        => json_encode( $user ),
            'chapter'     => 1,
            'challenge'   => 0,
            'ext'         => '',
            'tmp'         => json_encode([]),
            'cloud'       => json_encode([ 'apply' => -1,'stage' => 1,'lv' => 1,'step' => 0,'list' => [ $cloudInitId ] ]),
            'doufa'       => json_encode([ 'enemy' => [],'score' => 0 ]),
            'paradise'    => json_encode([]),
            'comrade'     => json_encode([]),
            'pet'         => json_encode([]),
            'spirit'      => json_encode([]),
            'tactical'    => json_encode($tactical), //阵法
            'tower'       => json_encode([]),
            'equipment'   => json_encode([]),
            'demon_trail' => json_encode([]),
            'secret_tower'=> json_encode([]),
            'magic'       => json_encode([]),
            'fund'        => json_encode([]),
            'xianyuan'    => json_encode([]),
            'shanggu'     => json_encode([]),
        );

        try {

            $incrId = DbManager::getInstance()->invoke(function ($client) use ($userData) {
                return  Player::invoke($client)->data($userData)->save();
            });

            if (is_null($incrId)) return false;

            $this->init($this->mysql2Cache($userData));
            $this->initEmail();
            return true;
        } catch (\Throwable $th) {
            \EasySwoole\EasySwoole\Logger::getInstance()->info("inster Error " . $th->getMessage());
            return false;
        }
    }

    //mysql数据格式转化为缓存数据格式
    private function mysql2Cache(array $userInfo): array
    {
        $playerData = array();
        foreach ($userInfo as $name => $val)
        {
            if (!property_exists($this, $name)) continue;
            $playerData[$name] = $val;
        }

        PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use ($playerData) {
            $redis->hMSet($this->playerKey, $playerData);
        });

        return $playerData;
    }

    //保存用户数据至Redis  默认全部保存
    public function saveData(array $field = [])
    {
        $newData = array();
        foreach ($this as $name => $value)
        {
            if(!property_exists($this, $name) || is_null($value) || in_array($name,['playerKey','fd'])) continue;

            if($field && !in_array($name,$field)) continue;

            $newData[$name] = is_array($value) ? json_encode($value) : $value;
        }

        $nodeKey = Keys::getInstance()->getNodeKey($this->openid);
        $nodeInfo = [
            $this->site => json_encode([
                'level'     => $this->role['lv'],
                'nickname'  => $this->user['nickname'],
            ])
        ];
        PoolManager::getInstance()->get('redis')->invoke(function (Redis $redis) use ($newData,$nodeKey,$nodeInfo) {
            $redis->hMSet($this->playerKey, $newData);
            //时刻更新用户等级昵称
            $redis->hMSet($nodeKey, $nodeInfo);
            //日活是否有添加
            if($redis->sIsMember(USER_SET,$this->playerKey)) $redis->sAdd(USER_SET, $this->playerKey);
        });
    }

    //获取用户字段数据入口
    public function getData(string $property,string $field = null )
    {
        if (!property_exists($this, $property)) throw new \Exception($property . " 属性不存在");

        if(is_null($field)) return $this->{$property};

        if(!array_key_exists($field,$this->{$property}) ) throw new \Exception($property . " 属性不存在 ".$field.' 键');

        return $this->{$property}[$field];
    }

    //设置用户数据
    public function setData(string $property,string $field = null, $data): void
    {
        if (!property_exists($this, $property) || in_array($property,['playerKey','fd'])) throw new \Exception($property . " 属性不存在");

        if(is_null($field))
        {
            $this->{$property} = $data;
        }else{
            if(!array_key_exists($field,$this->{$property}) ) throw new \Exception($property . " 属性不存在 ".$field.' 键值');
            $this->{$property}[$field] = $data;
        }
   }

   public function initEmail():void
   {

        $email  = [
            'title'      => '强者的诞生',
            'content'    => '话说天下大势，分久必合，合久必分。<br/>时势造英雄，英雄亦适时。历经数载，此番乱世也终于出现了天命之人，将终结乱世，一统天下。<br/>而英雄辈出之际，想乘势而起，又谈何容易！但若天命在身，怎能不争？！<br/>扬名立万、扭转乾坤，扶摇直上九万里。沧海横流，方显英雄本色。在这风云际会之时，你，便是绝对的主角。<br/>去吧，去搅动这乱世风云，去与这时代争辉，去成就你独一无二的荣耀吧~<br/>',
            'start_time' => time(),
            'end_time'   => time()+2592000,
            'reward'     => ConfigParam::getInstance()->getFmtParam('NEW_PLAYER_MAIL_REWARD'),
            'from'       => '水镜先生',
            'state'      => 0,
        ];

        $emailId = strval(SnowFlake::make(rand(0,31),rand(0,127)));
        EmailService::getInstance()->set($this->openid,$this->site,1,$emailId,$email);

   }
}

