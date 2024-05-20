<?php

namespace App\Api\Utils;

class Consts
{
    //物品
    const RENSHENTANG = 100011;//人参汤
    const QINPU       = 100016;//琴谱
    const DUKANGJIU   = 100017;//杜康酒
    const WANSHOUTAO  = 100018;//万寿桃

    //Arg 标志参数
    const COMRADE_ENERGY   = 116;//贤士探访体力
    const COMRADE_AD_COUNT = 117;//贤士体力广告恢复次数上限
    const COMRADE_ENERGY_TIME = 118;//探访体力缺失开始时间
    const DOUFA_WIN_COUNT = 119;//斗法累计胜利次数

    const ACTIVITY_CHANNEL_TASK_1 = 120;//朋友圈 首次加入朋友圈
    const ACTIVITY_CHANNEL_TASK_2 = 121;//朋友圈 当日点赞
    const ACTIVITY_CHANNEL_TASK_3 = 122;//朋友圈 当日评论

    const HOMELAND_TARGET_REFRESH_TIME = 123;//家园刷新的冷却时间

    const ACTIVITY_CHANNEL_TASK_4 = 124;//字节侧边栏

    const AES_KEY = '4jsnvOUINGhwwg5o';//
    const AES_IV  = 'AgICbPWjRXh8dX9k';//

    const ACTIVITY_TAG_5 = 125;//登录奖励1
    const ACTIVITY_TAG_6 = 126;//登录奖励2

    const ACTIVITY_NEW_YEAR_BEGIN = '2024-02-09';//新年活动开启时间
    const ACTIVITY_NEW_YEAR_END   = '2024-02-17';//新年活动结束时间
    const ACTIVITY_NEW_YEAR_TAG   = [ 1 => 127,2 => 128,3 => 129,4 => 130,5 => 131,6 => 132,7 => 133 ];

    const PET_AD_TAG   = 134;

    const SPIRIT_AD_TAG   = 135;
    const SPIRIT_DRAW_COUNT = 136; // 红颜累计抽取次数

    const BASIC_ATTRIBUTE = ['prim_attack', 'prim_hp', 'prim_defence', 'prim_speed'];//基础属性
    const SECOND_ATTRIBUTE = ['stun', 'critical_hit', 'double_attack', 'dodge', 'attack_back', 'life_steal'];//第二词条
    const SECOND_DEF_ATTRIBUTE = ['re_stun', 're_critical_hit', 're_double_attack', 're_dodge', 're_attack_back',
        're_life_steal'];//第二抗性词条
    const SPECIAL_ATTRIBUTE = [ 'final_hurt','final_sub_hurt','fortify_critical_hit','weaken_critical_hit',//0-3
        'fortify_cure' , 'weaken_cure','fortify_pet' , 'weaken_pet',//4-7
        //格挡 ,抗格挡  破甲 抗破甲 //8-11
        'gd','kgd','PJ','KPJ',//todo:: 这个后续需要重命名，目前用来占位置
        //强化战技伤害 弱化战技伤害
        'fortify_magic','weaken_magic','ignore_arr','ignore_arr_re',//12-15
    ];//特殊属性


    const TOWER_HIGH_RECORD = 137; //镇妖塔最高记录

    /**
     * buff 类型列表
     * 最终减伤 ：不属于buff
     */
    const BUFF_TYPE_LIST = [
        'critical_hit' => 0, /**0:加暴击 */
        're_critical_hit' => 1, /**1:加暴击抗性 */
        'critical_hit_sub' => 2, /**2:减暴击 */
        're_critical_hit_sub' => 3, /**3:减暴击抗性 */
        'fortify_critical_hit' => 4, /**4:加爆伤 */
        'weaken_critical_hit' => 5, /**5:减少爆伤 */
        'defence' => 6, /**6:加防御 */
        'defence_sub' => 7, /**7:减少防御 */
        'attack_back' => 8, /**8:加反击 */
        're_attack_back' => 9, /**9:加反击抗性 */
        'attack_back_sub' => 10, /**10:减反击 */
        're_attack_back_sub' => 11, /**11:减反击抗性 */
        'attack' => 12, /**12:加攻击 */
        'attack_sub' => 13, /**13:减少攻击 */
        'stun' => 14, /**14:加击晕 */
        're_stun' => 15, /**15:加击晕抗性 */
        'stun_sub' => 16, /**16:减击晕 */
        're_stun_sub' => 17, /**17:减击晕抗性 */
        'double_attack' => 18, /**18:加连击 */
        're_double_attack' => 19, /**19:加连击抗性 */
        'double_attack_sub' => 20, /**20:减连击 */
        're_double_attack_sub' => 21, /**21:减连击抗性 */
        'fortify_pet' => 22, /**22:强化副将 */
        'weaken_pet' => 23, /**23:弱化副将 */
        'dodge' => 24, /**24:加闪避 */
        're_dodge' => 25, /**25:加闪避抗性 */
        'dodge_sub' => 26, /**26:减闪避 */
        're_dodge_sub' => 27, /**27:减闪避抗性 */
        'speed' => 28, /**28:加速度 */
        'speed_sub' => 29, /**29:减速度 */
        'life_steal' => 30, /**30:加吸血 */
        're_life_steal' => 31, /**31:加吸血抗性 */
        'life_steal_sub' => 32, /**32:减吸血 */
        're_life_steal_sub' => 33, /**33:减吸血抗性 */
        'fortify_cure' => 34, /**34:加治疗 ，恢复效果，强化治疗*/
        'weaken_cure' => 35,/**35:减治疗 */
        'final_hurt' => 36, /**36:最终增伤*/
        'final_sub_hurt' => 37,/**37:最终减伤 */
    ];

    /**
     * 战斗用户状态
     */
    const BATTLE_BUFF_STATUS = [
        'freeze'  => 0,//冰冻
        'burn'    => 1,//燃烧
        'bleed'   => 2,//流血
        'immunity'=> 3,//免疫（免疫燃烧和冰冻）
        //'cure'    => 4,//治疗
        'vulnerability' => 4,//易伤
    ];


    const ACTIVITY_CHANNEL_TASK_5 = 138;// 微信-添加桌面
    const ACTIVITY_CHANNEL_TASK_6 = 139;// 微信-添加收藏

    const SECRET_TOWER_COUNT = 140; // 六道秘境每日次数
    const SECRET_TOWER_AD_TAG = 141; // 六道秘境视频次数
    const STUN = 3;

    const PET_REFRESH_COUNT = 142; // 灵宠累计抽取次数
    const PET_MG_COUNT = 143; // 灵宠心愿次数

    const MAGIC_AD_TAG   = 144; // 神通视频次数
    const MAGIC_REFRESH_COUNT   = 145; // 神通累计抽取次数(保底清空)
    const MAGIC_FREE_COUNT   = 146; // 神通免费次数

    const EQUIP_FALLING_TIME = 147;
    const MAIN_Kill_COST     = 148; // 主界面怪物击杀消耗

    const UPDATE_PET_HEAD     = 149; // 是否更新副将头像
    // 150 - 180 保留为首冲状态使用
    const ACTIVITY_FIRST_RECHARGE_TAG  = 150; // 首冲第一天
    // 150 - 180 保留为首冲状态使用

    const MONTHLY_CARD_TIME   = 181; // 月卡过期时间
    const MONTHLY_CARD_STATE  = 182; // 月卡奖励状态

    const LIFETTIME_CARD_TIME  = 183; // 终身卡开通时间
    const LIFETIME_CARD_STATE  = 184; // 终身卡奖励状态

    const XIANYUAN_GIFT_SCHEDULE = 185; // 仙缘礼包购买次数
    const XIANYUAN_GIFT_FREE_REWARD= 186; // 仙缘礼包免费领取奖励
    const XIANYUAN_GIFT_REWARD = 187; // 仙缘礼包领取奖励
    // 105046 仙缘累计积分

    // 188 - 198 保留基金购买状态
    const ACTIVITY_FUND_GROUP1 = 188; // 基金group:1
    const ACTIVITY_FUND_GROUP2 = 189; // 基金group:2
    const ACTIVITY_FUND_GROUP3 = 190; // 基金group:3
    const ACTIVITY_FUND_GROUP4 = 191; // 基金group:4
    // 188 - 198 保留基金购买状态

    const XIANYUAN_FUND_GROUP9 = 199; // 基金group:9 (仙缘)
    const XIANYUAN_SIGNIN_GIFT = 200; // 仙缘签到礼包

    const SHANGGU_SIGNIN_GIFT = 201; // 聚宝盆签到礼包

    const CHARA_BELONG = 202; // 模型类型

    const TREE_SPEED_UP_CD_TIME = 203; // 树(军旗)等级加速冷却

    // 105048 仙缘累计积分
}