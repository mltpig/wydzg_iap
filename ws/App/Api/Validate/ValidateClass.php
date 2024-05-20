<?php
namespace App\Api\Validate;
use EasySwoole\Component\CoroutineSingleTon;

class ValidateClass
{
    use CoroutineSingleTon;

    private $classs = array(
        "ping"           => "\\App\\Api\\Validate\\Player\\Ping",
        "modifyNickname" => "\\App\\Api\\Validate\\Player\\ModifyNickname",
        "modifyHead"       => "\\App\\Api\\Validate\\Player\\ModifyAvatar",
        "synChannelHead" => "\\App\\Api\\Validate\\Player\\SynChannelAvatar",
        "touch"         => "\\App\\Api\\Validate\\Player\\Touch",
        "initCharaBelong"   => "\\App\\Api\\Validate\\Player\\InitBelong",
        "editCharaBelong"   => "\\App\\Api\\Validate\\Player\\EditBelong",

        "upgradeTree"   => "\\App\\Api\\Validate\\Tree\\Upgrade",
        "speedUpTree"   => "\\App\\Api\\Validate\\Tree\\SpeedUp",
        "adSpeedUpTree" => "\\App\\Api\\Validate\\Tree\\AdSpeedUp",

        "chopTree"      => "\\App\\Api\\Validate\\Equip\\Get",
        "recoveryEquip" => "\\App\\Api\\Validate\\Equip\\Recovery",
        "applyEquip"    => "\\App\\Api\\Validate\\Equip\\Apply",
        
        
        "battleChapter"  => "\\App\\Api\\Validate\\Chapter\\Battle",
        
        "roleBattle"     => "\\App\\Api\\Validate\\Role\\Battle",
        "roleProfile"    => "\\App\\Api\\Validate\\Role\\Profile",
        
        "receiveTaskReward"     => "\\App\\Api\\Validate\\Task\\Receive",
        "receiveTaskAllReward"  => "\\App\\Api\\Validate\\Task\\ReceiveAll",
        
        "setExt"             => "\\App\\Api\\Validate\\Ext\\Set",

        "devAdd"             => "\\App\\Api\\Validate\\Ext\\Add",
        "testEmail"          => "\\App\\Api\\Validate\\Ext\\TestEmail",
        "jumpTask"         => "\\App\\Api\\Validate\\Ext\\Receive",
        "jumpCustoms"         => "\\App\\Api\\Validate\\Ext\\Pass",
        "addAttribute"        =>  "\\App\\Api\\Validate\\Ext\\AddAttribute",
        "mdsAdd"            =>  "\\App\\Api\\Validate\\Ext\\MdsAdd",

        "applyCloud"       => "\\App\\Api\\Validate\\Cloud\\Apply",
        "unlockCloud"      => "\\App\\Api\\Validate\\Cloud\\Unlock",
        "upgradeCloud"     => "\\App\\Api\\Validate\\Cloud\\Upgrade",

        "receiveGiftCode"  => "\\App\\Api\\Validate\\GiftCode\\Receive",

        "getNotice"         => "\\App\\Api\\Validate\\Notice\\Get",

        "getEmail"          => "\\App\\Api\\Validate\\Email\\Get",
        "readEmail"         => "\\App\\Api\\Validate\\Email\\Read",
        "receiveEmail"      => "\\App\\Api\\Validate\\Email\\Receive",
        "receiveAllEmail"   => "\\App\\Api\\Validate\\Email\\ReceiveAll",
        "deleteEmail"       => "\\App\\Api\\Validate\\Email\\Delete",
        "deleteAllEmail"    => "\\App\\Api\\Validate\\Email\\DeleteAll",

        "getFirstRechargeReward"        => "\\App\\Api\\Validate\\Activity\\FirstRecharge\\Get",
        "buyFirstRechargeReward"        => "\\App\\Api\\Validate\\Activity\\FirstRecharge\\Buy",
        "receiveFirstRechargeReward"    => "\\App\\Api\\Validate\\Activity\\FirstRecharge\\Receive",
        "receiveDailyReward"            => "\\App\\Api\\Validate\\Activity\\DailyReward\\Receive",
        "getOptionalGiftbagReward"      => "\\App\\Api\\Validate\\Activity\\OptionalGiftbag\\Get",
        "receiveOptionalGiftbagReward"  => "\\App\\Api\\Validate\\Activity\\OptionalGiftbag\\Receive",

        "getShops"          => "\\App\\Api\\Validate\\Shop\\Get",
        "buyGoods"          => "\\App\\Api\\Validate\\Shop\\Buy",

        "getDoufaRank"       => "\\App\\Api\\Validate\\Doufa\\Get",
        "getDoufaEnemys"     => "\\App\\Api\\Validate\\Doufa\\Enemys",
        "refreshDoufaEnemys" => "\\App\\Api\\Validate\\Doufa\\Refresh",
        "doufaBattle"        => "\\App\\Api\\Validate\\Doufa\\Battle",
        "getDoufaRecord"     => "\\App\\Api\\Validate\\Doufa\\Record",
        "doufaRecordBattle"  => "\\App\\Api\\Validate\\Doufa\\RecordBattle",

        "getBag"      => "\\App\\Api\\Validate\\Bag\\Get",
        "bagOpenBox"  => "\\App\\Api\\Validate\\Bag\\OpenBox",
        "bagOpenChooseBox"    => "\\App\\Api\\Validate\\Bag\\OpenChooseBox",

        "getChallengeBossInfo"  => "\\App\\Api\\Validate\\Challenge\\Get",
        "challengeBossBattle"  => "\\App\\Api\\Validate\\Challenge\\Battle",
        "challengeBossQuickBattle"  => "\\App\\Api\\Validate\\Challenge\\Quick",

        "getMonsterInvadeInfo"  => "\\App\\Api\\Validate\\MonsterInvade\\Get",
        "monsterInvadeBattle"   => "\\App\\Api\\Validate\\MonsterInvade\\Battle",
        "getMonsterInvadeRank"  => "\\App\\Api\\Validate\\MonsterInvade\\Rank",

        "getSignInReward"      => "\\App\\Api\\Validate\\Activity\\SignIn\\Get",
        "receiveSignInReward"  => "\\App\\Api\\Validate\\Activity\\SignIn\\Receive",

        "getParadiseInfo"       => "\\App\\Api\\Validate\\Paradise\\Get",
        "paradiseCollectGoods"  => "\\App\\Api\\Validate\\Paradise\\CollectGoods",
        "paradiseRefreshGoods"  => "\\App\\Api\\Validate\\Paradise\\RefreshGoods",
        "paradiseAdRefreshGoods"  => "\\App\\Api\\Validate\\Paradise\\AdRefreshGoods",
        "paradiseCollectGoodsRevokeById" => "\\App\\Api\\Validate\\Paradise\\CollectGoodsRevokeById",
        "paradiseCollectGoodsRevokeByWorker" => "\\App\\Api\\Validate\\Paradise\\CollectGoodsRevokeByWorker",
        "paradiseCollectRecord" => "\\App\\Api\\Validate\\Paradise\\CollectRecord",
        "paradiseWorkerAdd" => "\\App\\Api\\Validate\\Paradise\\WorkerAdd",

        "getComradeInfo"    => "\\App\\Api\\Validate\\Dongfu\\Comrade\\Get",
        "unlockComrade"    => "\\App\\Api\\Validate\\Dongfu\\Comrade\\Unlock",
        "upgradeComrade"    => "\\App\\Api\\Validate\\Dongfu\\Comrade\\Upgrade",
        "visitComrade"      => "\\App\\Api\\Validate\\Dongfu\\Comrade\\Visit",
        "comradeEnergyAdd"  => "\\App\\Api\\Validate\\Dongfu\\Comrade\\EnergyAdd",
        "comradeBattle"  => "\\App\\Api\\Validate\\Dongfu\\Comrade\\Battle",

        //渠道活动
        "getWxCircleOfFriendsReward"     => "\\App\\Api\\Validate\\Activity\\Channel\\Get",
        "receiveWxCircleOfFriendsReward" => "\\App\\Api\\Validate\\Activity\\Channel\\Receive",

        "receiveWxCollectReward"         => "\\App\\Api\\Validate\\Activity\\Channel\\ReceiveCollect",
        "receiveWxDesktopReward"         => "\\App\\Api\\Validate\\Activity\\Channel\\ReceiveDesktop",
        "receiveWxReviewReward"          => "\\App\\Api\\Validate\\Activity\\Channel\\ReceiveReview",
        "receiveWxGetReview"             => "\\App\\Api\\Validate\\Activity\\Channel\\GetReceiveReview",

        "getZjJumpRewardConfig"          => "\\App\\Api\\Validate\\Activity\\Zijie\\Get",
        "receiveZjJumpRewardConf"        => "\\App\\Api\\Validate\\Activity\\Zijie\\Receive",
        //登录奖励
        "getLoginRewardConfig"           => "\\App\\Api\\Validate\\Activity\\Login\\Get",
        "receiveLoginRewardConfig"       => "\\App\\Api\\Validate\\Activity\\Login\\Receive",
        //模型
        "modifyChara"    => "\\App\\Api\\Validate\\Chara\\Modify",
        "unlockChara"    => "\\App\\Api\\Validate\\Chara\\Unlock",
        "upgradeChara"    => "\\App\\Api\\Validate\\Chara\\Upgrade",
        //新春奖励
        "getNewYearRewardConfig"     => "\\App\\Api\\Validate\\Activity\\NewYear\\Get",
        "receiveNewYearReward"       => "\\App\\Api\\Validate\\Activity\\NewYear\\Receive",

        "recordVideo"       => "\\App\\Api\\Validate\\Ext\\Video",

        "getPet"           => "\\App\\Api\\Validate\\Pet\\Get",
        "petRefresh"       => "\\App\\Api\\Validate\\Pet\\Refresh",
        "petBuy"           => "\\App\\Api\\Validate\\Pet\\Buy",
        "petApply"         => "\\App\\Api\\Validate\\Pet\\Apply",
        "petUpLv"          => "\\App\\Api\\Validate\\Pet\\UpLv",
        "petLock"          => "\\App\\Api\\Validate\\Pet\\Lock",
        "petRelease"       => "\\App\\Api\\Validate\\Pet\\Release",
        "petReset"         => "\\App\\Api\\Validate\\Pet\\Reset",
        "petUnlockBox"     => "\\App\\Api\\Validate\\Pet\\UnlockBox",
        "petUpStar"        => "\\App\\Api\\Validate\\Pet\\UpStar",
        "petWish"          => "\\App\\Api\\Validate\\Pet\\Wish",

        "getSpirit"        => "\\App\\Api\\Validate\\Spirit\\Get",
        "getDraw"          => "\\App\\Api\\Validate\\Spirit\\Draw",
        "spiritUnlock"     => "\\App\\Api\\Validate\\Spirit\\Unlock",
        "spiritUpLv"       => "\\App\\Api\\Validate\\Spirit\\UpLv",
        "spiritApplySquad" => "\\App\\Api\\Validate\\Spirit\\ApplySquad",
        "spiritApply"      => "\\App\\Api\\Validate\\Spirit\\Apply",
        "spiritReject"     => "\\App\\Api\\Validate\\Spirit\\Reject",
        "spiritCut"        => "\\App\\Api\\Validate\\Spirit\\Cut",
        "spiritUnlockBox"  => "\\App\\Api\\Validate\\Spirit\\UnlockBox",
        "spiritFetterUnlock"  => "\\App\\Api\\Validate\\Spirit\\FetterUnlock",
        "spiritFetterUpLv"    => "\\App\\Api\\Validate\\Spirit\\FetterUpLv",

        //阵法
        "understandingTactical"    => "\\App\\Api\\Validate\\Tactical\\Understanding",//参悟阵法
        "deduceTactical"           => "\\App\\Api\\Validate\\Tactical\\Deduce",       //推演阵法
        "applyTactical"            => "\\App\\Api\\Validate\\Tactical\\Apply",        //应用
        "recoveryTactical"         => "\\App\\Api\\Validate\\Tactical\\Recovery",     //回收

        "getTower"        => "\\App\\Api\\Validate\\Tower\\Get",
        "towerBattle"     => "\\App\\Api\\Validate\\Tower\\Battle",
        "towerGetBuff"    => "\\App\\Api\\Validate\\Tower\\GetBuff",
        "towerDelBuff"    => "\\App\\Api\\Validate\\Tower\\DelBuff",
        "towerSetBuff"    => "\\App\\Api\\Validate\\Tower\\SetBuff",
        "towerPreinst"    => "\\App\\Api\\Validate\\Tower\\Preinst",
        "towerMoppingup"  => "\\App\\Api\\Validate\\Tower\\Moppingup",
        "towerRank"       => "\\App\\Api\\Validate\\Tower\\Rank",

        "getEquipment"        => "\\App\\Api\\Validate\\Equipment\\Get",
        "equipmentUpLv"       => "\\App\\Api\\Validate\\Equipment\\UpLv",
        "equipmentUpStage"    => "\\App\\Api\\Validate\\Equipment\\UpStage",
        "equipmentUnlock"     => "\\App\\Api\\Validate\\Equipment\\Unlock",

        "getDemonTrail"       => "\\App\\Api\\Validate\\DemonTrail\\Get",
        "demonTrailReceive"   => "\\App\\Api\\Validate\\DemonTrail\\Receive",

        "getSecretTower"        => "\\App\\Api\\Validate\\SecretTower\\Get",
        "secretTowerBattle"     => "\\App\\Api\\Validate\\SecretTower\\Battle",
        "secretTowerRank"       => "\\App\\Api\\Validate\\SecretTower\\Rank",
        "secretTowerReceive"            => "\\App\\Api\\Validate\\SecretTower\\Receive",
        "secretTowerAchievement"        => "\\App\\Api\\Validate\\SecretTower\\Draw",
        "secretTowerAchievementRank"    => "\\App\\Api\\Validate\\SecretTower\\AchievementRank",

        "getMagic"          => "\\App\\Api\\Validate\\Magic\\Get",
        "magicDraw"         => "\\App\\Api\\Validate\\Magic\\Draw",
        "magicApply"        => "\\App\\Api\\Validate\\Magic\\Apply",
        "magicUpLv"         => "\\App\\Api\\Validate\\Magic\\UpLv",
        "magicUpStage"      => "\\App\\Api\\Validate\\Magic\\UpStage",
        "magicReset"        => "\\App\\Api\\Validate\\Magic\\Reset",
        "magicIhUnlock"     => "\\App\\Api\\Validate\\Magic\\IhUnlock",
        "magicIhUpLv"       => "\\App\\Api\\Validate\\Magic\\IhUpLv",
        "stoneApply"        => "\\App\\Api\\Validate\\Magic\\StoneApply",
        "stoneRemove"       => "\\App\\Api\\Validate\\Magic\\StoneRemove",
        "stoneConflate"     => "\\App\\Api\\Validate\\Magic\\StoneConflate",

        "getFund"           => "\\App\\Api\\Validate\\Fund\\Get",
        "fundReceive"       => "\\App\\Api\\Validate\\Fund\\Receive",
        "fundBuy"           => "\\App\\Api\\Validate\\Fund\\Buy",

        "getMonthlyCard"    => "\\App\\Api\\Validate\\MonthlyCard\\Get",
        "devBuyMonthlyCard" => "\\App\\Api\\Validate\\MonthlyCard\\Buy",
        "applyMonthlyCard"  => "\\App\\Api\\Validate\\MonthlyCard\\Apply",

        "getLifetimeCard"    => "\\App\\Api\\Validate\\LifetimeCard\\Get",
        "devBuyLifetimeCard" => "\\App\\Api\\Validate\\LifetimeCard\\Buy",
        "applyLifetimeCard"  => "\\App\\Api\\Validate\\LifetimeCard\\Apply",

        "getXianYuan"        => "\\App\\Api\\Validate\\Activity\\XianYuan\\Get",
        "xianYuanClaimTask"  => "\\App\\Api\\Validate\\Activity\\XianYuan\\ClaimTask",
        "xianYuanReceive"    => "\\App\\Api\\Validate\\Activity\\XianYuan\\Receive",
        "xianYuanSignIn"     => "\\App\\Api\\Validate\\Activity\\XianYuan\\SignIn",
        "xianYuanFreeReward" => "\\App\\Api\\Validate\\Activity\\XianYuan\\FreeReward",
        "xianYuanTaskReward" => "\\App\\Api\\Validate\\Activity\\XianYuan\\TaskReward",
        "xianYuanBuyFund"    => "\\App\\Api\\Validate\\Activity\\XianYuan\\BuyFund",
        "xianYuanBuySignIn"  => "\\App\\Api\\Validate\\Activity\\XianYuan\\BuySignIn",
        "xianYuanBuyGift"    => "\\App\\Api\\Validate\\Activity\\XianYuan\\BuyGift",

        "getShangGu"        => "\\App\\Api\\Validate\\Activity\\ShangGu\\Get",
        "shangGuSignIn"     => "\\App\\Api\\Validate\\Activity\\ShangGu\\SignIn",
        "shangGuBuySignIn"  => "\\App\\Api\\Validate\\Activity\\ShangGu\\BuySignIn",

        "getOverFlowGift"   => "\\App\\Api\\Validate\\Activity\\OverFlowGift\\Get",
        "overFlowBuy"       => "\\App\\Api\\Validate\\Activity\\OverFlowGift\\Buy",

        "getYuanBao"        => "\\App\\Api\\Validate\\YuanBao\\Get",
        "buyYuanBao"        => "\\App\\Api\\Validate\\YuanBao\\Buy",

        "createPayOrder"    => "\\App\\Api\\Validate\\Pay\\Wx\\Order",
        "queryPayOrder"     => "\\App\\Api\\Validate\\Pay\\Wx\\Query",
    );

    public function getPath(string $method):string
    {
        return array_key_exists($method,$this->classs)? $this->classs[$method] :'';
    }

    public function getMethods():array
    {
        return array_keys( $this->classs );
    }
}
