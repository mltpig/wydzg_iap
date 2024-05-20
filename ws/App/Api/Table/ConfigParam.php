<?php
namespace App\Api\Table;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigParam as Model;

class ConfigParam
{
    use CoroutineSingleTon;

    protected $tableName = 'config_param';

    public function create():void
    {
        $columns = [ 'value'  => [ 'type'=> Table::TYPE_STRING ,'size'=> 2000 ] ];

        TableManager::getInstance()->add( $this->tableName , $columns , 500 );

    }

    public function initTable():void
    {
        $table = TableManager::getInstance()->get($this->tableName);

        $tableConfig = Model::create()->all();

        foreach ($tableConfig as $config) 
        {
            $table->set($config['param'],[ 'value' => $config['value'] ]);
        }

    }

    public function getOne(string $field):string
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();
        $data = $table->get($field);

        return $data ? $data['value']  : '';

    }

    public function getFmtParam(string $field)
    {
        $param = null;
        $value = $this->getOne($field);
        switch ($field) 
        {
            case 'DREAM_UPGRADE_SPEEDUP_ITEM_COST'://使用净水瓶花费
            case 'RENAME_COST'://改名花费
            case 'PVP_REFRESH_COST'://斗法刷新花费
            case 'PVP_CHALLENGE_COST'://斗法挑战花费
            case 'DREAM_UPGRADE_SPEEDUP_AD_SKIP'://可以通过50仙玉跳过广告获得奖励
            case 'HOMELAND_PAY_REFRESH_COST'://福地刷新消耗
            case 'PET_REFRESH_COST'://宠物刷新的花费
            case 'SPIRIT_PULL_COST'://精怪抽取消耗
            case 'TALENT_READ_COST'://阵法参悟 物品id=数量
            case 'TALENT_PULL_COST'://阵法抽取消耗 物品id=数量
            case 'MAGIC_PULL_COST'://神通单抽花费
            case 'MAGIC_RESET_COST'://神通重置花费
            case 'ZHENGJI_GIFTBAG_FREE_REWARD'://政绩-礼包每日免费奖励
            case 'ZHENGJI_GIFTBAG_PAID_REWARD'://政绩-礼包购买奖励
            case 'BODYCHANGE_ITEM_ID'://修改模型属性消耗
                list($id,$number) = explode('=',$value);
                $param = ['gid' => $id,'num' => $number];
                break;
            case 'EQUIPMENT_SPECIAL_DROP_LIST'://前十五个装备固定输出
            case 'PVP_ROBOT_LEVEL'://斗法机器人等级区间
            case 'WILDBOSS_REPEAT_COST_PARAM'://妖王挑战快速战斗消耗
            case 'INVADE_MONSTER_ID'://异兽入侵怪物id
            case 'INVADE_CHALLENGE_TIME'://异兽入侵挑战最大次数
            case 'HOMELAND_ENERGY_DIVIDE'://工人状态划分标准
            case 'HOMELAND_ENERGY_SPEED'://工人状态对应附加时长百分比
            case 'HOMELAND_SINGLE_BOX_MOUSE_LIMIT_CONFIG'://福地物品等级对应采取人数
            case 'HOMELAND_AUTO_REFRESH_TIME'://福地物品刷新时间点
            case 'DESTINY_ENERGY_ITEM_PARAM'://使用精气丹获得一点体力
            case 'DESTINY_LEVEL_UP'://贤士升级配置
            case 'HOMELAND_ENERGY_COPE_SPEED'://使用精气丹获得一点体力
            case 'PET_LEVELUP_COST_PARAM'://宠物升级使用灵兽果的数量
            case 'SPIRIT_BOX_UNLOCK'://精怪内容解锁
            case 'SPIRIT_PULL_ENSURE_NUM'://精怪抽取保底
            case 'SPIRIT_FIRST_PULL_ENSURE_QUALITY'://玩家第一次抽取数量的稀有度
            case 'SPIRIT_LEVEL_COST_NUM'://精怪升级所需的灵魂数量
            case 'TALENT_SPECIAL_TYPE'://阵法特殊类型
            case 'TALENT_SPECIAL_EFFECT'://阵法特殊影响,技能
            case 'PET_ACTIVE_SKILL_UPGRADE'://副将技能升级参数
            case 'MAGIC_PULL_WEIGHT'://神通抽奖权重
            case 'MAGIC_UPGRADE_PARAM'://神通升阶参数
            case 'MAGIC_COMBINE_LEVEL'://神通图鉴进阶
            case 'MAGIC_BATTLE_PARAM'://神通妖气使用配置参数
                $param = explode('|',$value);
                break;
            case 'DREAM_UPGRADE_SPEEDUP_ITEM_TIME'://使用净水瓶能减少300秒（1个）
            case 'EQUIPMENTCREATE_DROP_EMPTY_WEIGHT'://仙树额外掉落参数（为空的权重）
            case 'RENAME_DAILY_TIMES'://改名卡每日限制次数
            case 'AD_REWARD_CD'://每日福利领取限制周期
            case 'AD_REWARD_DAILY_MAX_NUM'://每日福利限制次数
            case 'PVP_ROBOT_COUNT'://斗法机器人数量
            case 'PVP_CHALLENGE_COST_LIMIT'://砍树产出挑战券最大数量
            case 'PVP_SCORE_CHANGE_RATE'://游戏分数改变率
            case 'WILDBOSS_REPEAT_LIMIT'://妖王挑战最大次数
            case 'INVADE_FIGHT_REWARD'://异兽入侵挑战奖励
            case 'DREAM_UPGRADE_SPEEDUP_AD_TIME'://看广告可以减少1800秒时间
            case 'HOMELAND_FREE_REFRESH_TIME'://福地广告刷新物品观看限制次数
            case 'HOMELAND_AUTO_REFRESH_TIME_PER'://福地失效物品自动刷新时间时长
            case 'HOMELAND_BASIC_WORKER_NUM'://福地工人初始数量
            case 'DESTINY_ENERGY_FREE_REFRESH_TIME'://仙友体力每日广告恢复的次数
            case 'DESTINY_ENERGY_TIME'://体力恢复时间
            case 'HOMELAND_TARGET_REFRESH_TIME'://家园刷新的冷却时间
            case 'PET_BAG_SIZE'://宠物背包的大小
            case 'PET_FREE_REFRESH_TIME'://宠物一天能免费刷新的次数
            case 'PET_BACK_PARAM'://宠物返还的参数
            case 'SPIRIT_FIRST_PULL_ENSURE_NUM'://玩家第一次抽取数量
            case 'SPIRIT_AD_LIMIT'://一天看广告获取精怪的次数
            case 'PVP_INITIAL_SCORE'://最初默认积分1000
            case 'TOWER_RESET_PARAM'://镇妖塔重置参数
            case 'SECRETTOWER_FREE_TIME_LIMIT'://六道秘境挑战次数
            case 'SECRETTOWER_AD_TIME_LIMIT'://六道秘境视频次数  
            case 'PET_DRAW_PROTECT_PARAM'://宠物掉落保护
            case 'PET_DRAW_SPECIFIC_PROTECT_PARAM'://宠物保底次数
            case 'SPIRIT_LEVEL_LIMIT'://精怪等级上限
            case 'MAGIC_PULL_FREE_TIME'://神通免费次数
            case 'MAGIC_AD_LIMIT'://神通广告次数
            case 'MAGIC_PULL_PROTECT_TIME'://神通保底数
            case 'MAGIC_PULL_PROTECT_QUALITY'://神通保底稀有度
            case 'MAGIC_REPEAT_PARAM'://神通重复的转换道具数
            case 'MAGIC_LEVEL_LIMIT'://神通等级上限
            case 'DISCOUNT'://充值折扣
            case 'SHANGGUTOUZI_RESET_TIME'://商贾投资活动重置周期（秒）
            case 'ZHENGJI_FUND_RESET_TIME'://政绩-基金重置周期（秒）
            case 'ZHENGJI_TASK_RESET_TIME'://政绩-任务重置周期（秒）
            case 'ZHENGJI_DAILY_RESET_TIME'://政绩-签到重置周期（秒）
            case 'ZHENGJI_GIFTBAG_RESET_ITEM'://政绩-重置道具物品
            case 'ZHENGJI_GIFTBAG_RESET_TIME'://政绩-礼包重置周期（秒）
            case 'ZHENGJI_GIFTBAG_PAID_REWARD_LIMIT'://政绩-礼包购买奖励需求次数
            case 'DREAM_UPGRADE_SPEEDUP_AD_COLD_TIME'://树加速升级CD（秒）
                $param = $value;
                break;
            case 'BORN_REWARD_LIST'://初始仙桃
            case 'AD_REWARD'://每日福利
            case 'PVP_CHALLENGE_REWARD'://斗法胜利奖励
            case 'NEW_PLAYER_MAIL_REWARD'://初始玩家奖励（邮件）
            case 'WX_ADD_DESKTOP_REWARD': // 微信-添加桌面
            case 'WX_ADD_FAVOURITE_REWARD': // 微信-添加收藏
            case 'GOOD_REPUTATION_REWARD': // 微信-五星好评
                $list = explode('|',$value);
                $param = [];
                foreach ($list as $key => $item) 
                {
                    list($id,$number) = explode('=',$item);
                    $param[$id] = ['type' => GOODS_TYPE_1,'gid' => $id,'num' => intval($number) ];
                }
                break;
            case 'HOMELAND_WORKER_COST'://福地工人雇佣费用
            case 'PET_BAG_ADD_COST'://增加宠物背包容量的花费
            case 'PET_RESET_COST'://宠物重置属性花费
                $list = explode(';',$value);
                $param = [];
                foreach ($list as $key => $item) 
                {
                    list($id,$number) = explode('=',$item);
                    $param[] = ['gid' => $id,'num' => $number];
                }
                break;
            case 'PVP_SCORE_CHANGE_PARAM'://斗法扣分计算区间
                $list = explode(';',$value);
                $param = [];
                foreach ($list as $key => $item) 
                {
                    list($num1,$num2) = explode('|',$item);
                    $param[] = [$num1,$num2];
                }
                break;
            case 'SPIRIT_PULL_WEIGHT'://精怪抽取权重
                list($a, $b) = explode(';', $value);

                $a_arr = explode('|', $a);
                $b_arr = explode('|', $b);

                $a_arr = array_map('intval', $a_arr);
                $b_arr = array_map('intval', $b_arr);
                
                $param = [$a_arr,$b_arr];
                break;
            case 'SPIRIT_PULL_ENSURE_QUALITY'://精怪抽取保底稀有度
                $first = explode(";", $value);
                $param = array_map(function ($item) {
                    return explode("|", $item);
                }, $first);
                break;
            case 'TOWER_MOPPINGUP_LIMIT'://镇妖塔奖励相关
                $list = explode(';',$value);
                $param = [];
                foreach ($list as $key => $item) 
                {
                    list($num1,$num2) = explode('|',$item);
                    $param[$num1] = $num2;
                }
                break;
        }

        return $param;
    }

    public function getIniGoodss():array
    {
        $config = $this->getFmtParam('BORN_REWARD_LIST');
        $goodsInit = [];
        foreach ($config as $key => $value) 
        {
            $goodsInit[$value['gid']] = $value['num'];
        }
        return $goodsInit;
    }

    public function getLimitConfig():array
    {
        return [
            'base' => [
                JINGSHUIPING    => intval($this->getFmtParam('DREAM_UPGRADE_SPEEDUP_ITEM_TIME')), 
                COUNTER_RENAME  => intval($this->getFmtParam('RENAME_DAILY_TIMES')),
            ],
        ];
    }
}
