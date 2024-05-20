<?php
namespace App\Api\Service;

use App\Api\Table\ConfigParam;
use App\Api\Table\ConfigPets;
use EasySwoole\Component\CoroutineSingleTon;
use EasySwoole\EasySwoole\ServerManager;
use App\Api\Utils\Consts;
use EasySwoole\Utility\SnowFlake;
use App\Api\Service\Module\PetService;
use App\Api\Service\Module\SpiritService;
use App\Api\Service\Module\TowerService;
use App\Api\Service\Module\EquipmentService;
use App\Api\Service\Module\DemonTrailService;
use App\Api\Service\Module\LogService;
use App\Api\Service\Module\SecretTowerService;
use App\Api\Service\Module\MagicService;
use App\Api\Service\Module\TicketService;
use App\Api\Service\Module\FundService;
use App\Api\Service\Module\MonthlyCardService;
use App\Api\Service\Module\LifetimeCardService;
use App\Api\Service\Module\XianYuanService;
use App\Api\Service\Module\ShangGuService;
use App\Api\Service\Module\OpenCelebraService;

class PlayerService extends BaseService
{
    use CoroutineSingleTon;

    public function check():void
    {
        $time     = time();
        $lastTime = strtotime($this->getData('last_time'));
		
        $this->chechDefault();
        $this->dailyReset($time,$lastTime);
		//防止老用户多加1
		if(!$this->getTmp('sign_in'))$this->setTmp('sign_in',[1,0]);

        ActivityService::getInstance()->check($this,$time);
        TreeService::getInstance()->checkTree($this,$time);
        ParadisService::getInstance()->checkParadis($this,$time,$lastTime);
        ComradeService::getInstance()->check($this,$time);
		MonthlyCardService::getInstance()->check($this,$time);//月卡
		LifetimeCardService::getInstance()->check($this,$time);//终身卡
		XianYuanService::getInstance()->check($this,$time);//仙缘
		OpenCelebraService::getInstance()->check($this,$time);//开服庆典

        $this->setData('last_time',null,date('Y-m-d H:i:s'));
    }

	public function gcCheck():void
	{
		ComradeService::getInstance()->gcCheck($this);
	}

	public function chechDefault():void
	{
		//七日签到
        ParadisService::getInstance()->initParadise($this);
        ComradeService::getInstance()->initComrade($this);
        PetService::getInstance()->initPet($this);
		SpiritService::getInstance()->initSpirit($this);

        TacticalService::getInstance()->initTactical($this); //初始化阵法

		TowerService::getInstance()->initTower($this);
		EquipmentService::getInstance()->initEquipment($this);
		DemonTrailService::getInstance()->initDemonTrail($this);
		SecretTowerService::getInstance()->initSecretTower($this);
		MagicService::getInstance()->initMagic($this);
		FundService::getInstance()->initFund($this);
		XianYuanService::getInstance()->initXanYuan($this);//仙缘
		ShangGuService::getInstance()->initXanYuan($this);//商贾

        //初始化副将图像 iap都是新用户
        //PetService::getInstance()->initPetHead($this);

	}
	
    public function dailyReset(int $time,int $lastTime):void
    {
		//日变 周、月一定变
		if(date('Y-m-d',$time) === date('Y-m-d',$lastTime)) return;


		RoleService::getInstance()->dailyReset($this);
		ActivityService::getInstance()->dailyReset($this);
		ShopService::getInstance()->dailyReset($this,$time,$lastTime);
		ChallengeService::getInstance()->dailyReset($this);
		MonsterInvadeService::getInstance()->dailyReset($this);
		ParadisService::getInstance()->dailyReset($this);
		ComradeService::getInstance()->dailyReset($this);
		PetService::getInstance()->dailyReset($this);
		SpiritService::getInstance()->dailyReset($this);
		TowerService::getInstance()->dailyReset($this);
		SecretTowerService::getInstance()->dailyReset($this);
		MagicService::getInstance()->dailyReset($this);
		FundService::getInstance()->dailyReset($this);
		XianYuanService::getInstance()->dailyReset($this);
		ShangGuService::getInstance()->dailyReset($this);
		OpenCelebraService::getInstance()->dailyReset($this);

		MonthlyCardService::getInstance()->dailyReset($this);//月卡
		LifetimeCardService::getInstance()->dailyReset($this);//终身卡


		//周结算
        $week = getWeekNum( $time );
		if($week !== $this->getArg(COUNTER_WEEK) )
		{
			//斗法周数只能自行统计，以防机器人删除，隔年登录获取报错
			$this->setArg(COUNTER_WEEK,$week,'reset');
			$this->setData('doufa','enemy',[]);
			//分数从排行榜取，不再从自己数据取
			// $this->setData('doufa','score',1000);
		}

    }

    public function getGoods(int $id):int
    {
		return array_key_exists($id,$this->goods) ? $this->goods[$id] : 0;
    }

    private function setGoods(int $id,int $number):int
    {
		array_key_exists($id,$this->goods) ?  '' : $this->goods[$id] = 0;

		$this->goods[$id] += $number;

		return $this->goods[$id];
    }

    public function getArg(int $id):int
    {
		return array_key_exists($id,$this->arg) ? $this->arg[$id] : 0;
    }

    public function setArg(int $id,int $number,string $action):void
    {
		array_key_exists($id,$this->arg) ?  '' : $this->arg[$id] = 0;
		switch ($action) 
		{
			case 'add':
				$this->arg[$id] += $number;
			break;
			case 'reset':
				$this->arg[$id] = $number;
			break;
			case 'unset':
				unset($this->arg[$id]);
			break;
		}

    }

    public function getTmp(string $field)
    {
		return array_key_exists($field,$this->tmp) ? $this->tmp[$field] : null;
    }

    public function setTmp(string $field, $data):void
    {
		$this->tmp[$field] = $data;
    }

    public function getGoodsInfo():array
    {
		return [
			XIANYU 	 	 => $this->getGoods(XIANYU),//灵石
			LINGSHI 	 => $this->getGoods(LINGSHI),//灵石
			XIANTAO 	 => $this->getGoods(XIANTAO),//仙桃
			JINGSHUIPING => $this->getGoods(JINGSHUIPING),//净水瓶
			GENGJIN 	 => $this->getGoods(GENGJIN),//庚金
			RENAME 	 	 => $this->getGoods(RENAME),//改名卡
			HUNYUANSHI   => $this->getGoods(HUNYUANSHI),//混元石
			TIAOZHANQUAN => $this->getGoods(TIAOZHANQUAN),//挑战券
			LIULIZHU 	 => $this->getGoods(LIULIZHU),//挑战券
			QINGPU 	 => $this->getGoods(QINGPU),//挑战券
			TAOHUAZHI 	 => $this->getGoods(TAOHUAZHI),//挑战券
			QIUXIANLING 	 => $this->getGoods(QIUXIANLING),//挑战券
			HUFU 	 => $this->getGoods(HUFU),//挑战券
			PUSHI 	 => $this->getGoods(PUSHI),//璞石
			100044 	 => $this->getGoods(100044),//挑战券
			100047 	 => $this->getGoods(100047),//挑战券
			100070 	 => $this->getGoods(100070),//太清残卷
			100017 	 => $this->getGoods(100017),//太清残卷
			100018 	 => $this->getGoods(100018),//太清残卷
			100011 	 => $this->getGoods(100011),//太清残卷
			150016 	 => $this->getGoods(150016),//太清残卷
			150003 	 => $this->getGoods(150003),//太清残卷
			141001 	 => $this->getGoods(141001),//太清残卷
			100008 	 => $this->getGoods(100008),//阵法晶魄
			133002 	 => $this->getGoods(133002),//碎片-鲍三娘
			133003 	 => $this->getGoods(133003),//碎片-郭皇后
			133004 	 => $this->getGoods(133004),//碎片-辛宪英
			133005 	 => $this->getGoods(133005),//碎片-曹婴
			133006 	 => $this->getGoods(133006),//碎片-孙茹
			133007 	 => $this->getGoods(133007),//碎片-张春华
			133008 	 => $this->getGoods(133008),//碎片-曹节
			133009 	 => $this->getGoods(133009),//碎片-邹氏
			134001 	 => $this->getGoods(134001),//碎片-赵襄
			134002 	 => $this->getGoods(134002),//碎片-关银屏
			134003 	 => $this->getGoods(134003),//碎片-马云騄
			134004 	 => $this->getGoods(134004),//碎片-张星彩
			134005 	 => $this->getGoods(134005),//碎片-董白
			134006 	 => $this->getGoods(134006),//碎片-卑弥呼
			134007 	 => $this->getGoods(134007),//碎片-孙鲁育
			134008 	 => $this->getGoods(134008),//碎片-王元姬
			134009 	 => $this->getGoods(134009),//碎片-冯方女
			134010 	 => $this->getGoods(134010),//碎片-张琪瑛
			134011 	 => $this->getGoods(134011),//碎片-诸葛果
			135001 	 => $this->getGoods(135001),//碎片-小乔
			135002 	 => $this->getGoods(135002),//碎片-蔡文姬
			135003 	 => $this->getGoods(135003),//碎片-大乔
			135004 	 => $this->getGoods(135004),//碎片-樊氏
			135005 	 => $this->getGoods(135005),//碎片-甄姬
			135006 	 => $this->getGoods(135004),//碎片-步练师
			135007 	 => $this->getGoods(135005),//碎片-灵雎
			135008 	 => $this->getGoods(135008),//碎片-孙尚香
			135009 	 => $this->getGoods(135009),//碎片-吕玲绮
			135010 	 => $this->getGoods(135010),//碎片-黄月英
			135011 	 => $this->getGoods(135011),//碎片-孙寒华
            100038 	 =>	$this->getGoods(100038),//悟性
			100048   => $this->getGoods(100048),
			171100   => $this->getGoods(171100), //回天术
			171101   => $this->getGoods(171101), //飞矢
			171102   => $this->getGoods(171102), //落石
			171200	 => $this->getGoods(171200), //阵前吼
			171201   => $this->getGoods(171201), //连弩激射
			171202	 => $this->getGoods(171202), //雷击
			171203   => $this->getGoods(171203), //飞沙走石
			171300   => $this->getGoods(171300), //趁火打劫
			171301   => $this->getGoods(171301), //麻沸散
			171302   => $this->getGoods(171302), //渴血
			171303   => $this->getGoods(171303), //炎墙燃烧
			171304   => $this->getGoods(171304), //补给
			171400   => $this->getGoods(171400), //生生不息
			171401	 => $this->getGoods(171401), //冰岚刃舞
			171402	 => $this->getGoods(171402), //火烧连营
			171403	 => $this->getGoods(171403), //神鬼乱舞
			171404   => $this->getGoods(171404), //无双
			171405   => $this->getGoods(171405), //齐头并进
			171406	 => $this->getGoods(171406), //气凌三军
			172100	 => $this->getGoods(172100), //震慑
			172101   => $this->getGoods(172101), //断刃
			172102	 => $this->getGoods(172102), //闷棍
			172200   => $this->getGoods(172200), //割裂
			172201 	 => $this->getGoods(172201), //刺轮攻
			172202   => $this->getGoods(172202), //借刀
			172203	 => $this->getGoods(172203), //净化
			172300   => $this->getGoods(172300), //战意
			172301   => $this->getGoods(172301), //拒陆马
			172302   => $this->getGoods(172302), //火焰箭
			172303   => $this->getGoods(172303), //精准打击
			172304   => $this->getGoods(172304), //后发制人
			172400   => $this->getGoods(172400), //一鼓作气
			172401   => $this->getGoods(172401), //虎啸龙咆
			172402   => $this->getGoods(172402), //致命一击
			172403   => $this->getGoods(172403), //乱刀狂舞
			172404   => $this->getGoods(172404), //大地狂啸
			172405   => $this->getGoods(172405), //八卦奇阵
			172406   => $this->getGoods(172406), //以命相搏
			173100   => $this->getGoods(173100), //泉涌
			173101   => $this->getGoods(173101), //默契
			173102   => $this->getGoods(173102), //夜袭
			173200   => $this->getGoods(173200), //命疗
			173201   => $this->getGoods(173201), //袭扰
			173202   => $this->getGoods(173202), //双重打击
			173203   => $this->getGoods(173203), //斗志削弱
			173300   => $this->getGoods(173300), //破胆
			173301   => $this->getGoods(173301), //避害
			173302   => $this->getGoods(173302), //冰柱
			173303	 => $this->getGoods(173303), //火牛阵
			173304   => $this->getGoods(173304), //耀武扬威
			173400   => $this->getGoods(173400), //起死回生
			173401	 => $this->getGoods(173401), //断生机
			173402   => $this->getGoods(173402), //铁骑突击
			173403   => $this->getGoods(173403), //同心协力
			173404   => $this->getGoods(173404), //鼓舞
			173405   => $this->getGoods(173405), //后伏军阵
			173406   => $this->getGoods(173406), //威名赫赫
			174100   => $this->getGoods(174100), //自愈
			174101   => $this->getGoods(174101), //坚盾
			174102   => $this->getGoods(174102), //轻装
			174200   => $this->getGoods(174200), //暴怒
			174201   => $this->getGoods(174201), //陷阱
			174202   => $this->getGoods(174202), //急疗
			174203   => $this->getGoods(174203), //陷阵之志
			174300   => $this->getGoods(174300), //鹰眼
			174301   => $this->getGoods(174301), //坚韧
			174302   => $this->getGoods(174302), //振奋
			174303   => $this->getGoods(174303), //藤甲
			174304   => $this->getGoods(174304), //怒气爆发
			174400   => $this->getGoods(174400), //背水一战
			174401   => $this->getGoods(174401), //荆棘铠甲
			174402   => $this->getGoods(174402), //刚烈不屈
			174403   => $this->getGoods(174403), //闪避姿态
			174404   => $this->getGoods(174404), //斗志昂扬
			174405   => $this->getGoods(174405), //偷天换日
			174406   => $this->getGoods(174406), //烈焰焚身
			260001   => $this->getGoods(260001), //1级军形刻印
			260002   => $this->getGoods(260002), //2级军形刻印
			260003   => $this->getGoods(260003), //3级军形刻印
			260004   => $this->getGoods(260004), //4级军形刻印
			260005   => $this->getGoods(260005), //5级军形刻印
			260006   => $this->getGoods(260006), //6级军形刻印
			260007   => $this->getGoods(260007), //7级军形刻印
			260008   => $this->getGoods(260008), //8级军形刻印
			260009   => $this->getGoods(260009), //1级军争刻印
			260010   => $this->getGoods(260010), //2级军争刻印
			260011   => $this->getGoods(260011), //3级军争刻印
			260012   => $this->getGoods(260012), //4级军争刻印
			260013   => $this->getGoods(260013), //5级军争刻印
			260014   => $this->getGoods(260014), //6级军争刻印
			260015   => $this->getGoods(260015), //7级军争刻印
			260016   => $this->getGoods(260016), //8级军争刻印
			260017   => $this->getGoods(260017), //1级兵势刻印
			260018   => $this->getGoods(260018), //2级兵势刻印
			260019   => $this->getGoods(260019), //3级兵势刻印
			260020   => $this->getGoods(260020), //4级兵势刻印
			260021   => $this->getGoods(260021), //5级兵势刻印
			260022   => $this->getGoods(260022), //6级兵势刻印
			260023   => $this->getGoods(260023), //7级兵势刻印
			260024   => $this->getGoods(260024), //8级兵势刻印
			260025   => $this->getGoods(260025), //1级火攻刻印
			260026   => $this->getGoods(260026), //2级火攻刻印
			260027   => $this->getGoods(260027), //3级火攻刻印
			260028   => $this->getGoods(260028), //4级火攻刻印
			260029   => $this->getGoods(260029), //5级火攻刻印
			260030   => $this->getGoods(260030), //6级火攻刻印
			260031   => $this->getGoods(260031), //7级火攻刻印
			260032   => $this->getGoods(260032), //8级火攻刻印
			260033   => $this->getGoods(260033), //1级始计刻印
			260034   => $this->getGoods(260034), //2级始计刻印
			260035   => $this->getGoods(260035), //3级始计刻印
			260036   => $this->getGoods(260036), //4级始计刻印
			260037   => $this->getGoods(260037), //5级始计刻印
			260038   => $this->getGoods(260038), //6级始计刻印
			260039   => $this->getGoods(260039), //7级始计刻印
			260040   => $this->getGoods(260040), //8级始计刻印
			260041   => $this->getGoods(260041), //1级作战刻印
			260042   => $this->getGoods(260042), //2级作战刻印
			260043   => $this->getGoods(260043), //3级作战刻印
			260044   => $this->getGoods(260044), //4级作战刻印
			260045   => $this->getGoods(260045), //5级作战刻印
			260046   => $this->getGoods(260046), //6级作战刻印
			260047   => $this->getGoods(260047), //7级作战刻印
			260048   => $this->getGoods(260048), //8级作战刻印
			260049   => $this->getGoods(260049), //1级九地刻印
			260050   => $this->getGoods(260050), //2级九地刻印
			260051   => $this->getGoods(260051), //3级九地刻印
			260052   => $this->getGoods(260052), //4级九地刻印
			260053   => $this->getGoods(260053), //5级九地刻印
			260054   => $this->getGoods(260054), //6级九地刻印
			260055   => $this->getGoods(260055), //7级九地刻印
			260056   => $this->getGoods(260056), //8级九地刻印
			260057   => $this->getGoods(260057), //1级谋攻刻印
			260058   => $this->getGoods(260058), //2级谋攻刻印
			260059   => $this->getGoods(260059), //3级谋攻刻印
			260060   => $this->getGoods(260060), //4级谋攻刻印
			260061   => $this->getGoods(260061), //5级谋攻刻印
			260062   => $this->getGoods(260062), //6级谋攻刻印
			260063   => $this->getGoods(260063), //7级谋攻刻印
			260064   => $this->getGoods(260064), //8级谋攻刻印
			260065   => $this->getGoods(260065), //1级行军刻印
			260066   => $this->getGoods(260066), //2级行军刻印
			260067   => $this->getGoods(260067), //3级行军刻印
			260068   => $this->getGoods(260068), //4级行军刻印
			260069   => $this->getGoods(260069), //5级行军刻印
			260070   => $this->getGoods(260070), //6级行军刻印
			260071   => $this->getGoods(260071), //7级行军刻印
			260072   => $this->getGoods(260072), //8级行军刻印
			260073   => $this->getGoods(260073), //1级虚实刻印
			260074   => $this->getGoods(260074), //2级虚实刻印
			260075   => $this->getGoods(260075), //3级虚实刻印
			260076   => $this->getGoods(260076), //4级虚实刻印
			260077   => $this->getGoods(260077), //5级虚实刻印
			260078   => $this->getGoods(260078), //6级虚实刻印
			260079   => $this->getGoods(260079), //7级虚实刻印
			260080   => $this->getGoods(260080), //8级虚实刻印
			260081   => $this->getGoods(260081), //1级九变刻印
			260082   => $this->getGoods(260082), //2级九变刻印
			260083   => $this->getGoods(260083), //3级九变刻印
			260084   => $this->getGoods(260084), //4级九变刻印
			260085   => $this->getGoods(260085), //5级九变刻印
			260086   => $this->getGoods(260086), //6级九变刻印
			260087   => $this->getGoods(260087), //7级九变刻印
			260088   => $this->getGoods(260088), //8级九变刻印
			260089   => $this->getGoods(260089), //1级用间刻印
			260090   => $this->getGoods(260090), //2级用间刻印
			260091   => $this->getGoods(260091), //3级用间刻印
			260092   => $this->getGoods(260092), //4级用间刻印
			260093   => $this->getGoods(260093), //5级用间刻印
			260094   => $this->getGoods(260094), //6级用间刻印
			260095   => $this->getGoods(260095), //7级用间刻印
			260096   => $this->getGoods(260096), //8级用间刻印
		];
    }
	 
    public function goodsBridge(array $goods,string $scene,string $desc=''):void
    {
		//GOODS_TYPE_1	普通道具 GOODS_TYPE_2	好感度道具（贤士） GOODS_TYPE_3	 副将 
		//GOODS_TYPE_5	红颜碎片 GOODS_TYPE_6	随机宝箱 		  GOODS_TYPE_7	自选宝箱 
		//GOODS_TYPE_8	战技     GOODS_TYPE_14	附魂外观兑换道具  GOODS_TYPE_19	自选法宝碎片 
		//GOODS_TYPE_23	刻印     GOODS_TYPE_100	特殊装备
		foreach ($goods as $item) 
		{
			switch ($item['type']) 
			{
				case GOODS_TYPE_1:
				case GOODS_TYPE_2:
				case GOODS_TYPE_5:
				case GOODS_TYPE_6:
				case GOODS_TYPE_7:
				case GOODS_TYPE_8:
				case GOODS_TYPE_14:
				case GOODS_TYPE_19:
				case GOODS_TYPE_23:
				case 101:
					$this->setGoods($item['gid'],$item['num']);
					break;
				case GOODS_TYPE_100:
					$newTmp = EquipService::getInstance()->getGuideExtract(0,$item['gid']);
					$newTmp['index'] = strval(SnowFlake::make(1,1));
					$this->setEquipTmp($newTmp['index'],$newTmp,'add');
					break;
                case GOODS_TYPE_3:
                    $config = ConfigPets::getInstance()->getOne( $item['gid'] );
					$petInfo = PetService::getInstance()->getBagPetInitFmtData( intval($item['gid']),intval($config['passive_skill']));
                    $bagId = PetService::getInstance()->checkFreeBag( $this->getData('pet','bag') );
                    $this->setPet('bag',$bagId,$petInfo,'multiSet');
                    $this->setPet('map', $item['gid'],1,'multiSet');
                    //解锁副将头像
                    $headInfo = $this->getData('head');
                    if (!isset($headInfo[5]) || !in_array($config['icon'], $headInfo[5])) {
                        $this->setHead(5, 0, $config['icon'], 'push');
                    }
                    break;
				default:

					break;
			}

			$log = [
				'uid'  	=> $this->getData('roleid'),
				'node' 	=> $this->getData('site'),
				'goods' => $item['gid'],
				'num'   => $item['num'],
				'scene' => $scene,
				'desc' 	=> $desc,
				'time' 	=> time(),
			];

			LogService::getInstance()->push($log);
		}
    }

    public function passChapter():void
    {
		$this->chapter++;
    }

    public function setRole(string $field,int $number,string $action):void
    {
		switch ($action) 
		{
			case 'add':
				$this->role[$field] += $number;
			break;
			case 'sub':
				$this->role[$field] -= $number;
			break;
			case 'set':
				$this->role[$field] = $number;
			break;
		}
    }

    public function setTask(int $taskid,int $field,int $number,string $action):void
    {
		switch ($action) 
		{
			case 'add':
				$this->task[$taskid][$field] += $number;
			break;
			case 'set':
				$this->task[$taskid][$field] = $number;
			break;
			case 'unset':
				unset($this->task[$taskid]);
				break;
		}
    }

    public function setEquipTmp(int $index,array $data,string $action):void
    {
		switch ($action) 
		{
			case 'add':
				$this->equip_tmp[$index] = $data;
			break;
			case 'unset':
				unset($this->equip_tmp[$index]);
				break;
		}
    }

    public function setCloud(string $field,int $number,string $action):void
    {
		switch ($action) 
		{
			case 'add':
				$this->cloud[$field] += $number;
			break;
			case 'set':
				$this->cloud[$field] = $number;
			break;
			case 'push':
				$this->cloud[$field][] = $number;
			break;
		}
    }

    public function setHead(int $type,int $index,string $value,string $action):void
    {
		array_key_exists($type,$this->head) ? '' : $this->head[$type] = [];
		switch ($action) 
		{
			case 'set':
				$this->head[$type][$index] = strval($value);
			break;
			case 'push':
				$this->head[$type][] = strval($value);
			break;
		}
    }
	
	//设计开始遗漏等级，活动模型单独设计
    public function setChara(int $type,string $value):void
    {
		array_key_exists($type,$this->chara) ? '' : $this->chara[$type] = [];
		$this->chara[$type][] = strval($value);
    }

    public function setActivityChara(int $type,string $charaid,int $lv ,string $action):void
    {
		switch ($action) {
			case 'set':
				array_key_exists($type,$this->chara) ? '' : $this->chara[$type] = [];
				$this->chara[$type][$charaid] = $lv;
				break;
			case 'add':
				$this->chara[$type][$charaid] += $lv;
				break;
		}
    }

	public function getLoginData(bool $isNew):array
	{
		$this->check();

		
		if(!$mainCost = $this->getArg(Consts::MAIN_Kill_COST))
		{
			$mainCost = $this->getArg(COUNTER_EQUIP) >= 15 ? mt_rand(3,5) : 3 ;
			$this->setArg(Consts::MAIN_Kill_COST,$mainCost,'reset');
		}
		
		$this->saveData();

		$comrade = $this->getData('comrade');
		list($_sum , $attrSum) = ComradeService::getInstance()->getComradeAttrSum($comrade);
		
		$config = ConfigParam::getInstance()->getLimitConfig();
		$config['kill_cost'] = $mainCost;

		$ticket = 0;
		try {
			$ticket =  TicketService::getInstance($this)->getBalance();
		} catch (\Throwable $th) {
			//throw $th;
		}

		return [
			'task'  	=> TaskService::getInstance()->getShowTask( $this->getData('task') ),
			'tree'  	=> TreeService::getInstance()->getShowTree( $this ),
			'equip' 	=> EquipService::getInstance()->getEquipFmtData($this->getData('equip')),
			'equip_tmp' => EquipService::getInstance()->getEquipFmtData(array_values($this->getData('equip_tmp'))),
			'role' 		=> $this->getData('role'),
			'ext' 		=> $this->getData('ext'),
			'chapterid' => intval($this->getData('chapter')),
			'goods'		=> $this->getGoodsInfo(),
			'user'		=> $this->getUserInfo($isNew),
			'arg'		=> $this->getArgInfo(),
			'config'	=> $config,
			'cloud' 	=> $this->getData('cloud'),
			'notice' 	=> NoticeService::getInstance()->getList(),
			'head' 		=> $this->getData('head'),
			'chara' 	=> RoleService::getInstance()->getCharaFmt($this),
			'activity' 	=> [
				'dailyReward'   => ConfigParam::getInstance()->getFmtParam('AD_REWARD'),
				'firstRecharge' => ActivityService::getInstance()->getFirstRechargeConfig($this),
				'signIn' => ActivityService::getInstance()->getSignInState($this),
				'newYear' => [
					'begin' => strtotime(Consts::ACTIVITY_NEW_YEAR_BEGIN),
					'end' 	=> strtotime(Consts::ACTIVITY_NEW_YEAR_END),
				],
			],
			'daily_reward' 	=> ActivityService::getInstance()->getDailyRewardFmt($this),
			'comrade' => [
				'attr_sum'     => $attrSum,
				'param'     => [
					Consts::RENSHENTANG      => $this->getArg(Consts::RENSHENTANG),
					Consts::COMRADE_ENERGY   => $this->getArg(Consts::COMRADE_ENERGY),
					Consts::COMRADE_AD_COUNT => $this->getArg(Consts::COMRADE_AD_COUNT),
				],
			],
			'redPoint' => RedPointService::getInstance()->getRedPoints($this),
			'pet' 	   => PetService::getInstance()->getPetFmtData( $this ),
			'spirit'   => SpiritService::getInstance()->getSpiritFmtData( $this,$this->getArg( Consts::SPIRIT_AD_TAG )),
            'tactical' => $this->getData('tactical'),
			'tower'	   => TowerService::getInstance()->getTowerFmtData( $this),
			'equipment'=> EquipmentService::getInstance()->getEquipmentFmtData( $this),
			'demon_trail'	=> DemonTrailService::getInstance()->getDemonTrailFmtData( $this),
			'secret_tower'	=> SecretTowerService::getInstance()->getSecretTowerFmtData( $this),
			'magic'			=> MagicService::getInstance()->getMagicFmtData( $this),
			'monthly_card'	=> MonthlyCardService::getInstance()->getMonthlyCardFmtData( $this),
			'lifetime_card'	=> LifetimeCardService::getInstance()->getLifetimeCardFmtData( $this),
			'ticket'   		=> $ticket,
		];
	}

    public function getUserInfo(bool $isNew = false):array
    {	
		$user = $this->getData('user');
		$user['uid']   		   = $this->getData('roleid');
		$user['isNew'] 		   = $isNew;
		$user['time']  		   = time();
		$user['open']  		   = ceil((time() - OPEN_DATE ) / DAY_LENGHT);
		$user['chara_belong']  = $this->getArg(Consts::CHARA_BELONG) ? $this->getArg(Consts::CHARA_BELONG) : -1  ;

        if(isset($user['chara']['belong']) && $user['chara']['belong'] != 0 ){
            $user['chara']['belong'] = $user['chara_belong'];
        }
		return $user;
    }

    public function getArgInfo():array
    {
		return [
			COUNTER_RENAME 	=> $this->getArg(COUNTER_RENAME),
			COUNTER_TASK 	=> $this->getArg(COUNTER_TASK),
			COUNTER_FR 		=> $this->getArg(COUNTER_FR) ? 1 : 0,
		];
    }

	public function pushi(array $data ):void
	{
		ServerManager::getInstance()->getSwooleServer()->push($this->fd,json_encode($data,JSON_UNESCAPED_UNICODE|JSON_FORCE_OBJECT));
	}

	public function colse():void
	{
		ServerManager::getInstance()->getSwooleServer()->close($this->fd);
	}

    public function setParadise($module, $field, $id,$value,string $action):void
    {
		switch ($field) 
		{
			case 'energy':
				$this->paradise[$module][$field] = $value;
			break;
			case 'time':
			case 'reward':
				$this->paradise[$field] = $value;
			break;
			case 'pos':
				$this->paradise[$module][$id] = $value;
			break;
			default:
				$this->paradise[$module][$field][$id] = $value;
			break;
		}
    }

    public function setComrade(int $id,$field, $value,$action):void
    {
		if(is_null($field))
		{
			$this->comrade[$id] = $value;
		}else{
			switch ($action) 
			{
				case 'add':
					$this->comrade[$id][$field] += $value;
				break;
				case 'set':
					$this->comrade[$id][$field] = $value;
				break;
				case 'push':
					$this->comrade[$id][$field] = $value;
				break;
			}
		}
    }

    public function setPet(string $field,int $id, $value,string $action):void
    {
		switch ($action) 
		{
			case 'set':
				$this->pet[$field] = $value;
			break;
			case 'multiSet':
				$this->pet[$field][$id] = $value;
			break;
			case 'push':
				$this->pet[$field][] = $value;
			break;
			case 'flushall':
				$this->pet = $value;
			break;
		}
		
    }

    public function setSpirit(string $field,int $id, $value,string $action):void
    {
		switch ($action) 
		{
			case 'set':
				$this->spirit[$field] = $value;
			break;
			case 'multiSet':
				$this->spirit[$field][$id] = $value;
			break;
			case 'push':
				$this->spirit[$field][] = $value;
			break;
			case 'flushall':
				$this->spirit = $value;
			break;
		}
    }

    public function setTactical(string $field,int $id, $value,string $action):void
    {
        switch ($action)
        {
            case 'set':
                $this->tactical[$field] = $value;
                break;
            case 'multiSet':
                $this->tactical[$field][$id] = $value;
                break;
            case 'push':
                $this->tactical[$field][] = $value;
                break;
            case 'flushall':
                $this->tactical = $value;
                break;
        }
    }

	public function setTower(string $field,int $id, $value,string $action):void
	{
		switch ($action) 
		{
			case 'set':
				$this->tower[$field] = $value;
			break;
			case 'multiSet':
				$this->tower[$field][$id] = $value;
			break;
			case 'push':
				$this->tower[$field][] = $value;
			break;
			case 'flushall':
				$this->tower = $value;
			break;
		}
	}

	public function setEquipment(string $field,int $id, $value,string $action):void
	{
		switch ($action) 
		{
			case 'set':
				$this->equipment[$field] = $value;
			break;
			case 'multiSet':
				$this->equipment[$field][$id] = $value;
			break;
			case 'push':
				$this->equipment[$field][] = $value;
			break;
			case 'flushall':
				$this->equipment = $value;
			break;
		}
	}

	public function setDemonTrail(string $field,int $id, $value,string $action):void
	{
		switch ($action) 
		{
			case 'set':
				$this->demon_trail[$field] = $value;
			break;
			case 'multiSet':
				$this->demon_trail[$field][$id] = $value;
			break;
			case 'push':
				$this->demon_trail[$field][] = $value;
			break;
			case 'flushall':
				$this->demon_trail = $value;
			break;
		}
	}
	public function setSecretTower(string $field,int $id, $value,string $action):void
	{
		switch ($action) 
		{
			case 'set':
				$this->secret_tower[$field] = $value;
			break;
			case 'multiSet':
				$this->secret_tower[$field][$id] = $value;
			break;
			case 'push':
				$this->secret_tower[$field][] = $value;
			break;
			case 'flushall':
				$this->secret_tower = $value;
			break;
		}
	}

	public function setMagic(string $field,int $id, $value,string $action):void
	{
		switch ($action) 
		{
			case 'set':
				$this->magic[$field] = $value;
			break;
			case 'multiSet':
				$this->magic[$field][$id] = $value;
			break;
			case 'push':
				$this->magic[$field][] = $value;
			break;
			case 'flushall':
				$this->magic = $value;
			break;
		}
	}

	public function setFund(string $field,int $id, $value,string $action):void
	{
		switch ($action) 
		{
			case 'set':
				$this->fund[$field] = $value;
			break;
			case 'multiSet':
				$this->fund[$field][$id] = $value;
			break;
			case 'push':
				$this->fund[$field][] = $value;
			break;
			case 'flushall':
				$this->fund = $value;
			break;
		}
	}

	public function setXianYuan(string $field,int $id, $value,string $action):void
	{
		switch ($action) 
		{
			case 'set':
				$this->xianyuan[$field] = $value;
			break;
			case 'multiSet':
				$this->xianyuan[$field][$id] = $value;
			break;
			case 'push':
				$this->xianyuan[$field][] = $value;
			break;
			case 'flushall':
				$this->xianyuan = $value;
			break;
		}
	}

	public function setShangGu(string $field,int $id, $value,string $action):void
	{
		switch ($action) 
		{
			case 'set':
				$this->shanggu[$field] = $value;
			break;
			case 'multiSet':
				$this->shanggu[$field][$id] = $value;
			break;
			case 'push':
				$this->shanggu[$field][] = $value;
			break;
			case 'flushall':
				$this->shanggu = $value;
			break;
		}
	}

	public function setOpenCelebra(string $field,int $id, $value,string $action):void
	{
		switch ($action) 
		{
			case 'set':
				$this->open_celebra[$field] = $value;
			break;
			case 'multiSet':
				$this->open_celebra[$field][$id] = $value;
			break;
			case 'push':
				$this->open_celebra[$field][] = $value;
			break;
			case 'flushall':
				$this->open_celebra = $value;
			break;
		}
	}
}
