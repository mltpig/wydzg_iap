<?php

namespace App\Api\Service;

use App\Api\Service\Module\MagicSkillService;
use App\Api\Table\ConfigSkill;
use App\Api\Utils\Consts;
use EasySwoole\Component\CoroutineSingleTon;

class SkillService
{
    use CoroutineSingleTon;


    const SKILL_LIST = [
        '1' => [],//副将技能
        '2' => [
            '0' => [40001, 40002, 40003, 40004, 40005, 40006, 40008, 40010, 40011, 40012, 40024, 40025, 40029, 40031,
                40034, 40037, 40041, 40045,],//0代表首回合启动
            '1' => [40007, 40030,], //每回合开始时
            '2' => [40009], //第$b回合开始
            '3' => [40013], //每次触发连击
            '4' => [40014,], //暴击后
            '5' => [40015], //击晕敌人时
            '6' => [40016, 40023], //反击时
            '7' => [40017, 40044], //副将释放战技时
            '8' => [40027, 40032, 40035], //攻击时,生命值首次低于%b时  被攻击
            '9' => [40019, 40020, 40033, 40036, 40040], //攻击时
            '10' => [40021, 40039, 40042,], //每次闪避
            '11' => [40022, 40038], //每次被攻击
            '12' => [40026], //首次被击倒,复活
            '13' => [40028], //攻击时,对生命值低于%b的敌人
            '14' => [40043], //攻击时,对生命值高于%b的敌人
            '15' => [40018], //攻击时,生命值低于%c时  被攻击
            '16' => [], '17' => [], '18' => [], '19' => [], '20' => [], '21' => [], '22' => [], '23' => [], '24' => []
        ],//精怪技能
        '3' => [
            '0' => [50001,],//0代表首回合启动
            '1' => [50006], //每回合开始时
            '2' => [50003], //第$b回合开始
            '3' => [], //每次触发连击
            '4' => [50005], //暴击后
            '5' => [], //击晕敌人时
            '6' => [50002], //反击时
            '7' => [], //副将释放战技时
            '8' => [],  //攻击时,生命值首次低于%b时
            '9' => [], //攻击时
            '10' => [50004], //每次闪避
            '11' => [], //每次被攻击
            '12' => [], //首次被击倒,复活
            '13' => [], //对生命值低于%b的敌人
            '14' => [], //对生命值高于%b的敌人
            '15' => [],//攻击时,生命值低于%c时
            '16' => [], '17' => [], '18' => [], '19' => [], '20' => [], '21' => [], '22' => [], '23' => [], '24' => []
        ],//阵法技能
        '4' => [
            '0' => [133100, 133101, 134101, 134102, 134300, 134303, 134405,],//0代表首回合启动
            '1' => [134100, 134404], //每回合开始时
            '2' => [], //第$b回合开始
            '3' => [132400], //每次触发连击
            '4' => [132403], //暴击后
            '5' => [], //击晕敌人时
            '6' => [134302, 134402], //反击时
            '7' => [133102, 133201, 133202, 133300, 133303, 133401, 133406, 133301, 133304, 133402], //副将释放战技时
            '8' => [134400, 134301, 134203], //攻击时,生命值首次低于%b时
            '9' => [132102, 132201, 132202, 132303, 132401, 132402, 132405, 132300, 134406, 132406], //攻击时
            '10' => [134201, 134403], //每次闪避
            '11' => [134200, 134304, 134401, 134202], //每次被攻击
            '12' => [133400], //首次被击倒,复活
            '13' => [], //对生命值低于%b的敌人
            '14' => [], //对生命值高于%b的敌人
            '15' => [],//攻击时,生命值低于%c时

            '16' => [132100, 132101, 132200, 132302],//攻击时，对敌人造成伤害时
            '17' => [132203],//攻击时，处于负面状态时
            '18' => [132304],//反击命中后
            '19' => [132404],//释放兵法时
            '20' => [133203],//副将攻击处于负面状态的敌人时
            '21' => [133403, 133404, 133405],//副将每次攻击后
            '22' => [133302, 133200],//每过$b回合，在副将下一次释放战技后，有%a概率
            '23' => [131100, 131101, 131102, 131200, 131201, 131202, 131203, 131300, 131301, 131302, 131303, 131304,
                131400, 131401, 131402, 131403, 131404, 131405, 131406],//道法主动技能释放
            '24' => [132301],//敌人闪避时
        ],//道法技能
    ];

    const PET_NO_ATTASK_SKILL_LIST = [30006, 30011, 30012, 30020, 30021, 30024, 30025, 30029];//非副将攻击技能

    public function petAttackSkill(&$selfAttr, &$enemyAttr, &$selfDetail, &$enemyDetail, $tmp, array $petSkill, $id, $selfSkill): array
    {
        $tmp['type'] = 1;
        $tmp['id'] = $id;

        if (isset($selfDetail['buff_skill']['fortify_pet'][134304])) {
            unset($selfDetail['buff_skill']['fortify_pet'][134304]);
        }

        //灵兽攻击不可规避，不会造成眩晕连击，防守方不可反击
        //属性可能存在buff影响，每次重新计算
        //$selfBattleAttr = AttributeComputeService::getInstance()->getBattleAttr($selfAttr, $enemyAttr);
        //计算灵兽攻击伤害数值
        $hurt = AttributeComputeService::getInstance()->computePetHit($selfAttr);
        $value = div($hurt * $petSkill['a'], 1000);

        //神通技能加成133101
        if (isset($selfDetail['buff_skill_pet']['harm'])) {
            $value = div(($selfDetail['buff_skill_pet']['harm']['val'] + 1000) * $value, 1000);
        }

        if ($value < 1) {
            $value = 1;
        }
        $isSubHp = true;//是否有扣血
        switch ($petSkill['id']) {
            //30001  每隔$b个回合释放1次战技，攻击造成角色攻击力%a伤害，并提升角色下回合%c攻击。
            //30004  每隔$b个回合释放1次战技，攻击造成角色攻击力%a伤害，并提升角色下回合%c攻击。
            case '30001':
            case '30004':
                //["type":1,"heiheval存在的回合数":3,"ceng":1层数]
                $tmp['mybuff'][] = ['id' => $petSkill['id'], "type" => Consts::BUFF_TYPE_LIST['attack'], "heiheval" => 2, "ceng" => 0];
                $tmp['shanghaiData'] = ['type' => [], '_val' => $value];
                $selfDetail['buff_pet']['ratio_attack'] = ['round' => $tmp['round'], 'limit' => 2, 'count' => 1, 'val' => $petSkill['c']];
                break;
            //30002  每隔$b个回合释放1次战技，攻击造成角色攻击力%a伤害，并提升角色下回合%c防御。
            case '30002':
                $tmp['mybuff'][] = ['id' => $petSkill['id'], "type" => Consts::BUFF_TYPE_LIST['defence'], "heiheval" => 2, "ceng" => 0];
                $tmp['shanghaiData'] = ['type' => [], '_val' => $value];
                $selfDetail['buff_pet']['ratio_defence'] = ['round' => $tmp['round'], 'limit' => 2, 'count' => 1, 'val' => $petSkill['c']];
                break;
            //30003  每隔$b个回合释放1次战技，攻击造成角色攻击力%a伤害，并击晕敌人$c回合。
            case '30003':
                $tmp['shanghaiData'] = ['type' => [], '_val' => $value];
                $item = $petSkill['c'] + 1;
                if (isset($enemyDetail['debuff']['stun'])) {
                    if ($enemyDetail['debuff']['stun'] > $item) {
                        $item = $enemyDetail['debuff']['stun'];
                    }
                }
                $enemyDetail['debuff']['stun'] = $item;
                $tmp['shanghaiData']['type'][] = Consts::STUN;
                $specialBuff = ['type' => Consts::STUN, 'num' => $item];
                $tmp['shanghaiData']['specialBuff'][] = $specialBuff;
                SkillService::getInstance()->triggerSkill($tmp, $selfSkill, $selfAttr, $enemyAttr, $selfDetail, $enemyDetail, 5);
                break;
            //30005  每隔$b个回合释放1次战技，攻击造成角色攻击力%a伤害，并降低敌人下回合%c攻击。
            case '30005':
                $tmp['mybuff'][] = ['id' => $petSkill['id'], "type" => Consts::BUFF_TYPE_LIST['attack_sub'], "heiheval" => 2, "ceng" => 0];
                $tmp['shanghaiData'] = ['type' => [], '_val' => $value];
                $selfDetail['debuff_pet']['ratio_attack'] = ['round' => $tmp['round'], 'limit' => 2, 'count' => 1, 'val' => -$petSkill['c']];
                break;
            //30006  每隔$b个回合释放1次战技，恢复角色攻击力%a生命值，并提升角色下回合%c防御。
            case '30006':
                $value = div($selfAttr['attack'] * $petSkill['a'], 1000);
                if (isset($selfDetail['buff_skill_pet']['cure'])) {
                    $value = div(($selfDetail['buff_skill_pet']['cure']['val'] + 1000) * $value, 1000);
                }
                $tmp['mybuff'][] = ['id' => $petSkill['id'], "type" => Consts::BUFF_TYPE_LIST['defence'], "heiheval" => 2, "ceng" => 0];
                AttributeComputeService::getInstance()->computePetCure($value, $selfAttr['fortify_cure'], $selfAttr['fortify_pet']);
                BattleService::getInstance()->getLifeStealNumber($value, $selfDetail);

                $tmp['buffdata'] = ['type' => 1, '_val' => $value];

                $selfDetail['buff_pet']['ratio_defence'] = ['round' => $tmp['round'], 'limit' => 2, 'count' => 1, 'val' => $petSkill['c']];
                $isSubHp = false;
                break;
            //30007  每隔$b个回合释放1次战技，攻击造成角色攻击力%a伤害，并降低敌人下回合%c速度。
            case '30007':
                $tmp['mybuff'][] = ['id' => $petSkill['id'], "type" => Consts::BUFF_TYPE_LIST['speed_sub'], "heiheval" => 2, "ceng" => 0];
                $tmp['shanghaiData'] = ['type' => [], '_val' => $value];
                $selfDetail['debuff_pet']['ratio_speed'] = ['round' => $tmp['round'], 'limit' => 2, 'count' => 1, 'val' => -$petSkill['c']];
                break;
            //30008  每隔$b个回合释放1次战技，攻击造成角色攻击力%a伤害，并提升角色下回合%c暴击。
            case '30008':
                $tmp['mybuff'][] = ['id' => $petSkill['id'], "type" => Consts::BUFF_TYPE_LIST['critical_hit'], "heiheval" => 2, "ceng" => 0];
                $tmp['shanghaiData'] = ['type' => [], '_val' => $value];
                $selfDetail['buff_pet']['ratio_critical_hit'] = ['round' => $tmp['round'], 'limit' => 2, 'count' => 1, 'val' => $petSkill['c']];
                break;
            //30009  每隔$b个回合释放1次战技，攻击造成角色攻击力%a伤害，并提升角色下回合%c连击。
            case '30009':
                $tmp['mybuff'][] = ['id' => $petSkill['id'], "type" => Consts::BUFF_TYPE_LIST['double_attack'], "heiheval" => 2, "ceng" => 0];
                $tmp['shanghaiData'] = ['type' => [], '_val' => $value];
                $selfDetail['buff_pet']['ratio_double_attack'] = ['round' => $tmp['round'], 'limit' => 2, 'count' => 1, 'val' => $petSkill['c']];
                break;
            //30010  每隔$b个回合释放1次战技，攻击造成角色攻击力%a伤害，并提升角色下回合%c击晕。
            case '30010':
                $tmp['mybuff'][] = ['id' => $petSkill['id'], "type" => Consts::BUFF_TYPE_LIST['stun'], "heiheval" => 2, "ceng" => 0];
                $tmp['shanghaiData'] = ['type' => [], '_val' => $value];
                $selfDetail['buff_pet']['stun'] = ['round' => $tmp['round'], 'limit' => 2, 'count' => 1, 'val' => $petSkill['c']];
                break;
            //30011  每隔$b个回合释放1次战技，恢复角色攻击力%a生命值，并提升角色下回合%c闪避。
            case '30011':
                $value = div($selfAttr['attack'] * $petSkill['a'], 1000);
                if (isset($selfDetail['buff_skill_pet']['cure'])) {
                    $value = div(($selfDetail['buff_skill_pet']['cure']['val'] + 1000) * $value, 1000);
                }
                $tmp['mybuff'][] = ['id' => $petSkill['id'], "type" => Consts::BUFF_TYPE_LIST['dodge'], "heiheval" => 2, "ceng" => 0];
                AttributeComputeService::getInstance()->computePetCure($value, $selfAttr['fortify_cure'], $selfAttr['fortify_pet']);
                BattleService::getInstance()->getLifeStealNumber($value, $selfDetail);

                $tmp['buffdata'] = ['type' => 1, '_val' => $value];

                $selfDetail['buff_pet']['dodge'] = ['round' => $tmp['round'], 'limit' => 2, 'count' => 1, 'val' => $petSkill['c']];
                $isSubHp = false;
                break;
            //30012  每隔$b个回合释放1次战技，恢复角色攻击力%a生命值，并提升角色下回合%c反击。
            case '30012':
                $value = div($selfAttr['attack'] * $petSkill['a'], 1000);
                if (isset($selfDetail['buff_skill_pet']['cure'])) {
                    $value = div(($selfDetail['buff_skill_pet']['cure']['val'] + 1000) * $value, 1000);
                }
                $tmp['mybuff'][] = ['id' => $petSkill['id'], "type" => Consts::BUFF_TYPE_LIST['attack_back'], "heiheval" => 2, "ceng" => 0];
                AttributeComputeService::getInstance()->computePetCure($value, $selfAttr['fortify_cure'], $selfAttr['fortify_pet']);
                BattleService::getInstance()->getLifeStealNumber($value, $selfDetail);

                $tmp['buffdata'] = ['type' => 1, '_val' => $value];

                $selfDetail['buff_pet']['attack_back'] = ['round' => $tmp['round'], 'limit' => 2, 'count' => 1, 'val' => $petSkill['c']];
                $isSubHp = false;
                break;
            //30013  每隔$b个回合释放1次战技，攻击造成角色攻击力%a伤害。
            //30023  每隔$b个回合释放1次战技，攻击造成角色攻击力%a伤害。
            case '30013':
            case '30023':
                $tmp['shanghaiData'] = ['type' => [], '_val' => $value];
                break;
            //30014  每隔$b个回合释放1次战技，攻击造成角色攻击力%a伤害，并提升角色下回合%c反击和闪避。
            case '30014':
                $tmp['mybuff'][] = ['id' => $petSkill['id'], "type" => Consts::BUFF_TYPE_LIST['dodge'], "heiheval" => 2, "ceng" => 0];
                $tmp['mybuff'][] = ['id' => $petSkill['id'], "type" => Consts::BUFF_TYPE_LIST['attack_back'], "heiheval" => 2, "ceng" => 0];
                $tmp['shanghaiData'] = ['type' => [], '_val' => $value];
                $selfDetail['buff_pet']['dodge'] = ['round' => $tmp['round'], 'limit' => 2, 'count' => 1, 'val' => $petSkill['c']];
                $selfDetail['buff_pet']['attack_back'] = ['round' => $tmp['round'], 'limit' => 2, 'count' => 1, 'val' => $petSkill['c']];
                break;
            //30015  每隔$b个回合释放1次战技，攻击造成角色攻击力%a伤害，并提升角色下回合%d连击，同时降低敌人下回合%c连击。
            case '30015':
                $tmp['mybuff'][] = ['id' => $petSkill['id'], "type" => Consts::BUFF_TYPE_LIST['double_attack'], "heiheval" => 2, "ceng" => 0];
                $tmp['mybuff'][] = ['id' => $petSkill['id'], "type" => Consts::BUFF_TYPE_LIST['double_attack_sub'], "heiheval" => 2, "ceng" => 0];
                $tmp['shanghaiData'] = ['type' => [], '_val' => $value];
                $selfDetail['buff_pet']['double_attack'] = ['round' => $tmp['round'], 'limit' => 2, 'count' => 1, 'val' => $petSkill['d']];
                $enemyDetail['debuff_pet']['double_attack'] = ['round' => $tmp['round'], 'limit' => 2, 'count' => 1, 'val' => -$petSkill['c']];
                break;
            //30016  每隔$b个回合释放1次战技，攻击造成角色攻击力%a伤害，并降低敌人下回合%c防御。
            case '30016':
                $tmp['mybuff'][] = ['id' => $petSkill['id'], "type" => Consts::BUFF_TYPE_LIST['defence_sub'], "heiheval" => 2, "ceng" => 0];
                $tmp['shanghaiData'] = ['type' => [], '_val' => $value];
                $enemyDetail['debuff_pet']['ratio_defence'] = ['round' => $tmp['round'], 'limit' => 2, 'count' => 1, 'val' => -$petSkill['c']];
                break;
            //30017  每隔$b个回合释放1次战技，攻击造成角色攻击力%a伤害，并提升角色%c暴击，最多叠加$d次，直至战斗结束。
            case '30017':
                $tmp['shanghaiData'] = ['type' => [], '_val' => $value];
                if (isset($selfDetail['buff_pet']['sum_critical_hit'])) {
                    $selfDetail['buff_pet']['sum_critical_hit']['count']++;
                    if ($selfDetail['buff_pet']['sum_critical_hit']['count'] > $petSkill['d']) {
                        $selfDetail['buff_pet']['sum_critical_hit']['count'] = $petSkill['d'];
                    }
                } else {
                    $selfDetail['buff_pet']['sum_critical_hit'] = ['round' => $tmp['round'] - 1, 'limit' => 999, 'count' => 1, 'val' => $petSkill['c']];
                }
                $tmp['mybuff'][] = ['id' => $petSkill['id'], "type" => Consts::BUFF_TYPE_LIST['critical_hit'], "heiheval" => 999, "ceng" => $selfDetail['buff_pet']['sum_critical_hit']['count']];
                break;
            //30018  每隔$b个回合释放1次战技，攻击造成角色攻击力%a伤害，并提升角色%c反击和连击，最多叠加$d次，直至战斗结束。
            case '30018':
                $tmp['shanghaiData'] = ['type' => [], '_val' => $value];
                if (isset($selfDetail['buff_pet']['sum_attack_back'])) {
                    $selfDetail['buff_pet']['sum_attack_back']['count']++;
                    if ($selfDetail['buff_pet']['sum_attack_back']['count'] > $petSkill['d']) $selfDetail['buff_pet']['sum_attack_back']['count'] = $petSkill['d'];
                    $selfDetail['buff_pet']['sum_double_attack']['count']++;
                    if ($selfDetail['buff_pet']['sum_double_attack']['count'] > $petSkill['d']) $selfDetail['buff_pet']['sum_double_attack']['count'] = $petSkill['d'];
                } else {
                    $selfDetail['buff_pet']['sum_attack_back'] = ['round' => $tmp['round'] - 1, 'limit' => 999, 'count' => 1, 'val' => $petSkill['c']];
                    $selfDetail['buff_pet']['sum_double_attack'] = ['round' => $tmp['round'] - 1, 'limit' => 999, 'count' => 1, 'val' => $petSkill['c']];
                }
                //反击
                $tmp['mybuff'][] = ['id' => $petSkill['id'], "type" => Consts::BUFF_TYPE_LIST['attack_back'], "heiheval" => 999, "ceng" => $selfDetail['buff_pet']['sum_attack_back']['count']];
                //连击
                $tmp['mybuff'][] = ['id' => $petSkill['id'], "type" => Consts::BUFF_TYPE_LIST['double_attack'], "heiheval" => 999, "ceng" => $selfDetail['buff_pet']['sum_double_attack']['count']];
                break;
            //30019  每隔$b个回合释放1次战技，攻击造成角色攻击力%a伤害；若敌人处于眩晕状态则造成%c伤害，并延长$d回合眩晕。
            case '30019':
                $tmp['shanghaiData'] = ['type' => [], '_val' => $value];
                //判断是否处于晕眩状态
                if (isset($enemyDetail['debuff']['stun'])) {
                    $value = $petSkill['d'];
                    $enemyDetail['debuff']['stun'] = $enemyDetail['debuff']['stun'] + $value;
                    $specialBuff = ['type' => Consts::STUN, 'num' => $enemyDetail['debuff']['stun']];
                    $tmp['shanghaiData']['type'][] = Consts::STUN;
                    $tmp['shanghaiData']['specialBuff'][] = $specialBuff;

                    $value = mul($hurt, ($petSkill['c'] / 1000));
                    if (isset($selfDetail['buff_skill_pet']['harm'])) {
                        $value = div(($selfDetail['buff_skill_pet']['harm']['val'] + 1000) * $value, 1000);
                    }
                    if ($value < 1) {
                        $value = 1;
                    }
                    $tmp['shanghaiData']['_val'] = $value;
                }
                break;
            //30020  每隔$b个回合释放1次战技，恢复角色攻击力%a生命值，并提升角色%c恢复效果，最多叠加$d次，直至战斗结束。
            case '30020':
                $value = div($selfAttr['attack'] * $petSkill['a'], 1000);
                if (isset($selfDetail['buff_skill_pet']['cure'])) {
                    $value = div(($selfDetail['buff_skill_pet']['cure']['val'] + 1000) * $value, 1000);
                }
                AttributeComputeService::getInstance()->computePetCure($value, $selfAttr['fortify_cure'], $selfAttr['fortify_pet']);
                BattleService::getInstance()->getLifeStealNumber($value, $selfDetail);

                $tmp['buffdata'] = ['type' => 1, '_val' => $value];


                if (isset($selfDetail['buff_pet']['sum_fortify_cure'])) {
                    $selfDetail['buff_pet']['sum_fortify_cure']['count']++;
                    if ($selfDetail['buff_pet']['sum_fortify_cure']['count'] > $petSkill['d']) $selfDetail['buff_pet']['sum_fortify_cure']['count'] = $petSkill['d'];
                } else {
                    $selfDetail['buff_pet']['sum_fortify_cure'] = ['round' => $tmp['round'] - 1, 'limit' => 999, 'count' => 1, 'val' => $petSkill['c']];
                }
                $tmp['mybuff'][] = ['id' => $petSkill['id'], "type" => Consts::BUFF_TYPE_LIST['fortify_cure'], "heiheval" => 999, "ceng" => $selfDetail['buff_pet']['sum_fortify_cure']['count']];
                $isSubHp = false;
                break;
            //30021  每隔$b个回合释放1次战技，恢复角色攻击力%a生命值，并提升角色下回合%c攻击。
            case '30021':
                $value = div($selfAttr['attack'] * $petSkill['a'], 1000);
                if (isset($selfDetail['buff_skill_pet']['cure'])) {
                    $value = div(($selfDetail['buff_skill_pet']['cure']['val'] + 1000) * $value, 1000);
                }
                AttributeComputeService::getInstance()->computePetCure($value, $selfAttr['fortify_cure'], $selfAttr['fortify_pet']);
                BattleService::getInstance()->getLifeStealNumber($value, $selfDetail);

                $tmp['buffdata'] = ['type' => 1, '_val' => $value];


                $selfDetail['buff_pet']['ratio_attack'] = ['round' => $tmp['round'], 'limit' => 2, 'count' => 1, 'val' => $petSkill['c']];
                $tmp['mybuff'][] = ['id' => $petSkill['id'], "type" => Consts::BUFF_TYPE_LIST['attack'], "heiheval" => 2, "ceng" => 0];
                $isSubHp = false;
                break;
            //30022  每隔$b个回合释放1次战技，攻击造成角色攻击力%a伤害，并降低敌人下回合%c防御。
            case '30022':
                $tmp['mybuff'][] = ['id' => $petSkill['id'], "type" => Consts::BUFF_TYPE_LIST['defence_sub'], "heiheval" => 2, "ceng" => 0];
                $tmp['shanghaiData'] = ['id' => 0, 'type' => [], '_val' => $value];
                $enemyDetail['debuff_pet']['ratio_defence'] = ['round' => $tmp['round'], 'limit' => 2, 'count' => 1, 'val' => -$petSkill['c']];
                break;
            //30024  每隔$b个回合释放1次战技，恢复角色攻击力%a生命值，并提升角色下回合%c防御和速度。
            case '30024':
                $tmp['mybuff'][] = ['id' => $petSkill['id'], "type" => Consts::BUFF_TYPE_LIST['defence'], "heiheval" => 2, "ceng" => 0];
                $tmp['mybuff'][] = ['id' => $petSkill['id'], "type" => Consts::BUFF_TYPE_LIST['speed'], "heiheval" => 2, "ceng" => 0];
                $value = div($selfAttr['attack'] * $petSkill['a'], 1000);
                if (isset($selfDetail['buff_skill_pet']['cure'])) {
                    $value = div(($selfDetail['buff_skill_pet']['cure']['val'] + 1000) * $value, 1000);
                }
                AttributeComputeService::getInstance()->computePetCure($value, $selfAttr['fortify_cure'], $selfAttr['fortify_pet']);
                BattleService::getInstance()->getLifeStealNumber($value, $selfDetail);

                $tmp['buffdata'] = ['type' => 1, '_val' => $value];


                $selfDetail['buff_pet']['ratio_defence'] = ['round' => $tmp['round'], 'limit' => 2, 'count' => 1, 'val' => $petSkill['c']];
                $selfDetail['buff_pet']['ratio_speed'] = ['round' => $tmp['round'], 'limit' => 2, 'count' => 1, 'val' => $petSkill['c']];
                $isSubHp = false;
                break;
            //30025  每隔$b个回合释放1次战技，恢复角色攻击力%a生命值，并提升角色下回合%c吸血。
            case '30025':
                $value = div($selfAttr['attack'] * $petSkill['a'], 1000);
                if (isset($selfDetail['buff_skill_pet']['cure'])) {
                    $value = div(($selfDetail['buff_skill_pet']['cure']['val'] + 1000) * $value, 1000);
                }
                $tmp['mybuff'][] = ['id' => $petSkill['id'], "type" => Consts::BUFF_TYPE_LIST['life_steal'], "heiheval" => 2, "ceng" => 0];
                AttributeComputeService::getInstance()->computePetCure($value, $selfAttr['fortify_cure'], $selfAttr['fortify_pet']);
                BattleService::getInstance()->getLifeStealNumber($value, $selfDetail);

                $tmp['buffdata'] = ['type' => 1, '_val' => $value];


                $selfDetail['buff_pet']['life_steal'] = ['round' => $tmp['round'], 'limit' => 2, 'count' => 1, 'val' => $petSkill['c']];
                $isSubHp = false;
                break;
            //30026  每隔$b个回合释放1次战技，攻击造成角色攻击力%a伤害，并提升角色下回合%c攻击和防御。
            case '30026':
                $tmp['mybuff'][] = ['id' => $petSkill['id'], "type" => Consts::BUFF_TYPE_LIST['attack'], "heiheval" => 2, "ceng" => 0];
                $tmp['mybuff'][] = ['id' => $petSkill['id'], "type" => Consts::BUFF_TYPE_LIST['defence'], "heiheval" => 2, "ceng" => 0];
                $tmp['shanghaiData'] = ['type' => [], '_val' => $value];
                $selfDetail['buff_pet']['ratio_attack'] = ['round' => $tmp['round'], 'limit' => 2, 'count' => 1, 'val' => $petSkill['c']];
                $selfDetail['buff_pet']['ratio_defence'] = ['round' => $tmp['round'], 'limit' => 2, 'count' => 1, 'val' => $petSkill['c']];
                break;
            //30027  每隔$b个回合释放1次战技，攻击造成角色攻击力%a伤害，若敌人处于眩晕状态则为角色恢复造成伤害%c的生命值。
            case '30027':
                //判断是否处于晕眩状态
                if (isset($enemyDetail['debuff']['stun'])) {
                    $tmp['shanghaiData'] = ['type' => [], '_val' => $value];
                    $value = mul($hurt, $petSkill['c'] / 1000);
                    if (isset($selfDetail['buff_skill_pet']['cure'])) {
                        $value = div(($selfDetail['buff_skill_pet']['cure']['val'] + 1000) * $value, 1000);
                    }
                    if ($value < 1) {
                        $value = 1;
                    }
                    AttributeComputeService::getInstance()->computePetCure($value, $selfAttr['fortify_cure'], $selfAttr['fortify_pet']);
                    BattleService::getInstance()->getLifeStealNumber($value, $selfDetail);

                    $tmp['buffdata'] = ['type' => 1, '_val' => $value];

                } else {
                    $tmp['shanghaiData'] = ['type' => [], '_val' => $value];
                }
                break;
            //30028  每隔$b个回合释放1次战技，攻击造成角色攻击力%a伤害，并降低敌人下回合%c恢复效果。 就是提高我自己的强化治疗
            case '30028':
                $tmp['mybuff'][] = ['id' => $petSkill['id'], "type" => Consts::BUFF_TYPE_LIST['weaken_cure'], "heiheval" => 2, "ceng" => 0];
                $tmp['shanghaiData'] = ['type' => [], '_val' => $value];
                $selfDetail['buff_pet']['weaken_cure'] = ['round' => $tmp['round'], 'limit' => 2, 'count' => 1, 'val' => $petSkill['c']];
                break;
            //30029  每隔$b个回合释放1次战技，恢复角色攻击力%a生命值，并提升角色下回合%c最终减伤。
            case '30029':
                $value = div($selfAttr['attack'] * $petSkill['a'], 1000);
                if (isset($selfDetail['buff_skill_pet']['cure'])) {
                    $value = div(($selfDetail['buff_skill_pet']['cure']['val'] + 1000) * $value, 1000);
                }
                AttributeComputeService::getInstance()->computePetCure($value, $selfAttr['fortify_cure'], $selfAttr['fortify_pet']);
                BattleService::getInstance()->getLifeStealNumber($value, $selfDetail);

                $tmp['buffdata'] = ['type' => 1, '_val' => $value];


                $selfDetail['buff_pet']['final_sub_hurt'] = ['round' => $tmp['round'], 'limit' => 2, 'count' => 1, 'val' => $petSkill['c']];
                $isSubHp = false;
                break;
            //30030  每隔$b个回合释放1次战技，攻击造成角色攻击力%a伤害，并提升角色%c吸血和闪避，最多叠加$d次，直至战斗结束。
            case '30030':
                $tmp['shanghaiData'] = ['type' => [], '_val' => $value];
                $sumKey = 'sum_dodge';
                $sumKey2 = 'sum_life_steal';
                if (isset($selfDetail['buff_pet'][$sumKey])) {
                    $selfDetail['buff_pet'][$sumKey]['count']++;
                    if ($selfDetail['buff_pet'][$sumKey]['count'] > $petSkill['d']) {
                        $selfDetail['buff_pet'][$sumKey]['count'] = $petSkill['d'];
                    }
                    $selfDetail['buff_pet'][$sumKey2]['count']++;
                    if ($selfDetail['buff_pet'][$sumKey2]['count'] > $petSkill['d']) {
                        $selfDetail['buff_pet'][$sumKey2]['count'] = $petSkill['d'];
                    }
                } else {
                    $selfDetail['buff_pet'][$sumKey] = ['round' => $tmp['round'] - 1, 'limit' => 999, 'count' => 1, 'val' => $petSkill['c']];
                    $selfDetail['buff_pet'][$sumKey2] = ['round' => $tmp['round'] - 1, 'limit' => 999, 'count' => 1, 'val' => $petSkill['c']];
                }
                $tmp['mybuff'][] = ['id' => $petSkill['id'], "type" => Consts::BUFF_TYPE_LIST['dodge'], "heiheval" => 999, "ceng" => $selfDetail['buff_pet'][$sumKey]['count']];
                $tmp['mybuff'][] = ['id' => $petSkill['id'], "type" => Consts::BUFF_TYPE_LIST['life_steal'], "heiheval" => 999, "ceng" => $selfDetail['buff_pet'][$sumKey2]['count']];
                break;
            //30031  每隔$b个回合释放1次战技，攻击造成角色攻击力%a伤害，并提升角色%c战技伤害，最多叠加$d次，直至战斗结束。
            case '30031':
                $tmp['shanghaiData'] = ['type' => [], '_val' => $value];
                //fortify_magic
                if (isset($selfDetail['buff_pet']['fortify_magic'])) {
                    $selfDetail['buff_pet']['fortify_magic']['count']++;
                    if ($selfDetail['buff_pet']['fortify_magic']['count'] > $petSkill['d']) {
                        $selfDetail['buff_pet']['fortify_magic']['count'] = $petSkill['d'];
                    }
                } else {
                    $selfDetail['buff_pet']['fortify_magic'] = ['round' => $tmp['round'] - 1, 'limit' => 999, 'count' => 1, 'val' => $petSkill['c']];
                }
                $tmp['mybuff'][] = ['id' => $petSkill['id'], "type" => Consts::BUFF_TYPE_LIST['attack'], "heiheval" => 999, "ceng" => $selfDetail['buff_pet']['fortify_magic']['count']];

                break;
        }
        if (!$isSubHp) {
            $value = 0;
        }

        return [$value, $tmp];

    }

    /**
     * @param array $log
     * @param array $selfSkill
     * @param array $selfAttr
     * @param array $enemyAttr
     * @param array $selfDetail
     * @param int $skillUseType
     * @param $roundConditions
     * @param $isTriggerPet
     * @return void
     */
    public function triggerSkill(array &$log, array $selfSkill, array &$selfAttr, array &$enemyAttr, array &$selfDetail, &$enemyDetail, int $skillUseType, $roundConditions = false, &$isTriggerPet = false)
    {
        //判断冰冻状态,封闭精怪技能，只能触发神通-副将技能
        if (isset($selfDetail['debuff']['freeze'])) {
            $selfSkill['spirit'] = [];
            $selfSkill['magic'] = isset($selfSkill['magic']['pet']) && $selfSkill['magic']['pet'] ? [$selfSkill['magic']['pet']] : [];
        } else {
            unset($selfSkill['magic']['pet']);
        }


        //1,判断是否存在触发的技能
        //2,判断技能属于谁触发（精怪，阵法，道法）
        //   $selfSkill = ['tactical' => $selfTactical,'spirit'=>$selfSpirit];
        $skillList = $this->getSkillList($selfSkill, $skillUseType);


        if (!$skillList) {
            return;
        }

        //处理40026和，133400 优先复活精怪
        if ($skillUseType == 12 && isset($skillList[40026]) && isset($skillList[133400])) {
            unset($skillList[133400]);
        }

        //处理40017和40044，那个伤害高处理哪一个
        if ($skillUseType == 7 && isset($skillList[40017]) && isset($skillList[40044])) {
            $skillConf = ConfigSkill::getInstance()->getOne(40017);
            $value40017 = $skillConf['params'][0][0] + ($skillList[40017]['lv'] - 1) * $skillConf['upgradeParams'][0][0];
            $skillConf = ConfigSkill::getInstance()->getOne(40044);
            $value40044 = $skillConf['params'][0][0] + ($skillList[40044]['lv'] - 1) * $skillConf['upgradeParams'][0][0];

            if ($value40017 > $value40044) {
                unset($skillList[40044]);
            } else {
                unset($skillList[40017]);
            }
        }

        //处理特殊技能
        //首回合开始时，立刻对敌人造成攻击力%a伤害，可触发击晕。
        if (isset($skillList[40024])) {
            $stun = sub($selfAttr['stun'] - $enemyAttr['ignore_arr'],
                $enemyAttr['re_stun'] - $selfAttr['ignore_arr_re']);
            if ($stun < 0) {
                $stun = 0;
            }

            if ($stun >= rand(1, 1000)) {
                //判断是否触发击晕
                BattleService::getInstance()->triggerStun($log, $enemyAttr, $selfSkill, $selfAttr,
                    $selfDetail, $enemyDetail);
            }
        }


        //3,获取技能数据,触发技能,写入日志数据
        $this->handleSkillList($skillList, $selfSkill, $skillUseType, $log, $selfAttr, $selfDetail, $enemyAttr, $enemyDetail, $roundConditions, $isTriggerPet);
    }

    //攻击触发
    public function attackTriggerSkill(&$log, array $selfSkill, array &$selfAttr, array &$enemyAttr, &$selfDetail, &$enemyDetail)
    {
        //攻击时,触发技能
        $this->triggerSkill($log, $selfSkill, $selfAttr, $enemyAttr, $selfDetail, $enemyDetail, 9);
        if (MagicSkillService::getInstance()->isNegativeStatus($enemyDetail)) {
            $this->triggerSkill($log, $selfSkill, $selfAttr, $enemyAttr, $selfDetail, $enemyDetail, 16);//攻击时，处于负面状态时
        }
        if (isset($log['shanghaiData']['val']) && $log['shanghaiData']['val'] > 0) {
            $this->triggerSkill($log, $selfSkill, $selfAttr, $enemyAttr, $selfDetail, $enemyDetail, 17);//攻击时，对敌人造成伤害时
        }

        foreach ([13, 14] as $skillUseType) {
            $this->hpTriggerSkill($log, $selfSkill, $selfAttr, $enemyAttr, $skillUseType, $selfDetail, $enemyDetail);
        }
    }


    //被攻击触发技能
    public function attackedTriggerSkill(&$log, array $enemySkill, array &$selfAttr, array &$enemyAttr, &$selfDetail, &$enemyDetail)
    {
        $log['isFirst'] = false;
        foreach ([8, 15] as $skillUseType) {
            $this->hpTriggerSkill($log, $enemySkill, $enemyAttr, $selfAttr, $skillUseType, $enemyDetail, $selfDetail);
        }
        unset($log['isFirst']);
    }

    public function hpTriggerSkill(&$log, array $selfSkill, array &$selfAttr, array &$enemyAttr, int $skillUseType, &$selfDetail, &$enemyDetail)
    {
        //判断冰冻状态,封闭精怪技能，只能触发神通-副将技能
        if (isset($selfDetail['debuff']['freeze'])) {
            $selfSkill['spirit'] = [];
            $selfSkill['magic'] = $selfSkill['magic']['pet'] ? [$selfSkill['magic']['pet']] : [];
        } else {
            unset($selfSkill['magic']['pet']);
        }

        //1,判断是否存在触发的技能
        //2,判断技能属于谁触发（精怪，阵法，道法）
        //   $selfSkill = ['tactical' => $selfTactical,'spirit'=>$selfSpirit];
        $skillList = $this->getSkillList($selfSkill, $skillUseType);

        //3,获取技能数据,触发技能,写入日志数据
        foreach ($skillList as $id => $value) {
            //处理攻击，但是敌人没血的时候触发精怪技能,加伤害、扣血
            if (in_array($id, [40028, 40043, 50002]) && $enemyDetail['hp'] <= 0) {
                continue;
            }

            //获取技能参数
            $skillConf = ConfigSkill::getInstance()->getOne($id);
            $skillParam = [
                'a' => $skillConf['params'][0][0] + ($value['lv'] - 1) * $skillConf['upgradeParams'][0][0],
                'b' => $skillConf['params'][0][1] + ($value['lv'] - 1) * $skillConf['upgradeParams'][0][1],
                'c' => $skillConf['params'][0][2] + ($value['lv'] - 1) * $skillConf['upgradeParams'][0][2],
                'd' => $skillConf['params'][0][3] + ($value['lv'] - 1) * $skillConf['upgradeParams'][0][3],
            ];
            //处理判断hp
            switch ($skillUseType) {
                case 13:
                    //对生命值低于%b的敌人
                    if ($enemyDetail['hp'] > div($skillParam['b'] * $enemyDetail['hp_max'], 1000)) {
                        unset($skillList['id']);
                        continue 2;
                    }
                    break;
                case 14:
                    //对生命值高于%b的敌人
                    if ($enemyDetail['hp'] < div($skillParam['b'] * $enemyDetail['hp_max'], 1000)) {
                        unset($skillList['id']);
                        continue 2;
                    }
                    break;
                case 15://被攻击
                    //生命值低于%c时
                    if ($selfDetail['hp'] > div($skillParam['c'] * $selfDetail['hp_max'], 1000)) {
                        unset($skillList['id']);
                        continue 2;
                    }
                    break;
                case 8://生命值首次低于%b被攻击
                    if (!$selfDetail) continue 2;//如果異獸入侵我方已經結束
                    //生命值低于%b时
                    if (!isset($selfDetail['skill_one'])) $selfDetail['skill_one'] = array();
                    if (in_array($id, $selfDetail['skill_one']) || $selfDetail['hp'] >
                        div($skillParam['b'] * $selfDetail['hp_max'], 1000)) {
                        unset($skillList['id']);
                        continue 2;
                    }
                    $selfDetail['skill_one'][] = $id;//写入首次
                    break;
                default:
                    continue 2;
            }

            //处理概率触发技能
            //40019-%b，40023-%a,40033-%b
            if (in_array($id, [40019, 40033, 132300]) && $skillParam['b'] >= rand(1, 1000)) {
                continue;
            }
            if (in_array($id, [40023]) && $skillParam['a'] >= rand(1, 1000)) {
                continue;
            }


            //4,触发技能
            //5,写入日志数据-
            $spiritNameList = ['spirit', 'enemySpirit'];
            $tacticalNameList = ['tactical', 'enemyTactical'];
            $magicNameList = ['magic', 'enemyMagic'];
            if (!isset($log['isFirst']) || $log['isFirst']) {
                $index = 0;
            } else {
                $index = 1;
            }

            if ($value['type'] == 2) {
                $log[$spiritNameList[$index]][] = $value['id'];
            } elseif ($value['type'] == 3) {
                $log[$tacticalNameList[$index]][] = $value['id'];
            } elseif ($value['type'] == 4) {
                $log[$magicNameList[$index]][] = $value['id'];
            }

            //4,触发技能
            $this->useSkill($id, $log, $selfAttr, $enemyAttr, $skillParam, $selfDetail, $enemyDetail, $selfSkill);

        }
    }


    //处理技能列表
    public function handleSkillList($skillList, $selfSkill, $skillUseType, array &$log, &$selfAttr, &$selfDetail, &$enemyAttr, &$enemyDetail, $roundConditions = false, &$isTriggerPet = false)
    {
        foreach ($skillList as $id => $value) {

            //处理击晕，必须处于燃烧状态与击晕状态才会触发
            if (in_array($id, [134406]) && (!isset($enemyDetail['debuff']['stun']) || !isset($enemyDetail['buff_magic']['burn']) || count($enemyDetail['buff_magic']['burn']) <= 0)) {
                continue;
            }


            //处理攻击，但是敌人没血的时候触发精怪技能,加伤害、扣血
            if (in_array($id, [40028, 40043, 50002]) && $enemyDetail['hp'] <= 0) {
                continue;
            }

            if (in_array($id, [133301, 133304, 133402, 133200])) {//副将首次释放战技时
                if (!isset($selfDetail['skill_one'])) $selfDetail['skill_one'] = array(); //处理只会首次触发的技能
                if (in_array($id, $selfDetail['skill_one'])) {
                    continue;
                }
                $selfDetail['skill_one'][] = $id;//写入首次

            }

            //获取技能参数
            $skillConf = ConfigSkill::getInstance()->getOne($id);
            $skillParam = [
                'a' => $skillConf['params'][0][0] + ($value['lv'] - 1) * $skillConf['upgradeParams'][0][0],
                'b' => $skillConf['params'][0][1] + ($value['lv'] - 1) * $skillConf['upgradeParams'][0][1],
                'c' => $skillConf['params'][0][2] + ($value['lv'] - 1) * $skillConf['upgradeParams'][0][2],
                'd' => $skillConf['params'][0][3] + ($value['lv'] - 1) * $skillConf['upgradeParams'][0][3],
            ];

            //处理是否需要判断回合数
            if ($roundConditions && $log['round'] != $skillParam[$roundConditions]) {
                continue;
            }

            //处理概率触发技能
            //40019-%b，40023-%a,40033-%b
            if (in_array($id, [40019, 40033, 132300, 132302, 133303, 134202]) && $skillParam['b'] < rand(1, 1000)) {
                continue;
            }
            if (in_array($id, [134201]) && $skillParam['a'] < rand(1, 1000)) {
                continue;
            }


            if (in_array($id, [132402])) {
                $hurt = isset($log['shanghaiData']['_val']) ? $log['shanghaiData']['_val'] : 0;
                if ($hurt <= 0 || ($skillParam['b'] + $skillParam['d']) < rand(1, 1000)) {
                    continue;
                }
            }


            if (in_array($id, [40023])) {
                if ($skillParam['a'] > rand(1, 1000)) {
                    $isTriggerPet = $id;
                }
                continue;
            }

            //处理攻击，但是敌人没血的时候触发精怪技能,加伤害、扣血
            if (in_array($id, [40028, 40043, 50002]) && $enemyDetail['hp'] <= 0) {
                continue;
            }


            //4,触发技能
            //5,写入日志数据-
            $spiritNameList = ['spirit', 'enemySpirit'];
            $tacticalNameList = ['tactical', 'enemyTactical'];
            $magicNameList = ['magic', 'enemyMagic'];
            if (!isset($log['isFirst']) || $log['isFirst']) {
                $index = 0;
            } else {
                $index = 1;
            }

            if ($skillUseType == 12) {
                if ($value['type'] == 3) {
                    $log['revice'] = ['type' => 0, 'id' => $value['id']];
                } elseif ($value['type'] == 4) {
                    $log['revice'] = ['type' => 1, 'id' => $value['id']];
                }
            } else {
                if ($value['type'] == 2) {
                    $log[$spiritNameList[$index]][] = $value['id'];
                } elseif ($value['type'] == 3) {
                    $log[$tacticalNameList[$index]][] = $value['id'];
                } elseif ($value['type'] == 4) {
                    $log[$magicNameList[$index]][] = $value['id'];
                }
            }


            $this->useSkill($id, $log, $selfAttr, $enemyAttr, $skillParam, $selfDetail, $enemyDetail, $selfSkill);
        }
    }


    /**
     * 获取技能列表
     * @param $selfSkill
     * @param $skillUseType
     * @return array
     */
    public function getSkillList($selfSkill, $skillUseType)
    {

        $skillTypeList = [3 => $selfSkill['tactical'], 2 => $selfSkill['spirit'], 4 => $selfSkill['magic']];
        $skillList = array();
        foreach ($skillTypeList as $type => $data) {
            $index = 0;
            foreach ($data as $id => $lv) {
                if (in_array($id, self::SKILL_LIST[$type][$skillUseType])) {
                    $skillList[$id] = ['type' => $type, 'lv' => $lv, 'id' => $index];
                }
                $index++;
            }
        }
        return $skillList;
    }


    /**
     * 新增buff
     * @param int $rount 持续回合数
     * @param string $type buff类型
     * @param int $skillId 技能id
     * @param int $val 新增值
     * @param int $total 累计层数
     * @param $log
     * @param $selfAttr
     * @return void
     */
    public function addBuff(int $round, string $type, int $skillId, int $val, &$log, &$selfDetail, $buffName, int $total = 0)
    {
        //如果是回合开始时触发+1回合
        $list = [];
        foreach (self::SKILL_LIST as $key => $item) {
            if ($key > 1) {
                $list = array_merge($list, $item[0], $item[1], $item[2]);
            }
        }
        if (in_array($skillId, $list)) {
            $round = $round + 1;
        }

        $log[$buffName][] = ['id' => $skillId, "type" => Consts::BUFF_TYPE_LIST[$type], "heiheval" => $round,
            "ceng" => $total];
        //处理buff ;count层数
        if ($total == 0) {
            $total = 1;
        }

        //处理基础值，加成 关键词 提升基础值,40035,40014,40038,40042
        if (in_array($skillId, [40035, 40014, 40038, 40042, 131101, 131203, 131100, 133102, 133403, 134200, 132300, 134203]) && in_array($type, ['speed', 'attack', 'hp', 'defence'])) {
            $type = 'ratio_' . $type;
        }
        if (in_array($skillId, [134302])) {
            $count = 0;
            $skillIdCopy = $skillId;
            $skillId = $skillIdCopy . '_' . $count;
            while (isset($selfDetail['buff_skill'][$type][$skillId])) {
                $count++;
                $skillId = $skillIdCopy . '_' . $count;
            }
        }

        if ($skillId == 132405) {
            $skillId = $skillId . '_' . $log['round'];
        }
        $selfDetail['buff_skill'][$type][$skillId] = ['limit' => $round, 'count' => $total, 'val' => $val, 'round' => $log['round']];
    }


    /**
     * 使用技能
     * @param $id
     * @param $log
     * @param $selfAttr
     * @param $skillParam
     * @return void
     */
    public function useSkill($id, &$log, &$selfAttr, &$enemyAttr, $skillParam, &$selfDetail, &$enemyDetail, $selfSkill)
    {
        $id = (int)$id;
        //$value = div($selfAttr['attack'] * $skillParam['a'], 1000);
        $value = AttributeComputeService::getInstance()->computeSkillHit($selfAttr);
        if ($value < 1) {
            $value = 1;
        }

        //额外伤害 额外加血
//        'extShanghaiData' => [], 'extBuffdata' => [],
//            'extEnemyShanghaiData' => [], 'extEnemyBuffdata' => [],

        if (!isset($log['isFirst']) || $log['isFirst']) {
            $nameList = [
                'mybuff' => 'mybuff',
                'enemybuff' => 'enemybuff',
                'shanghaiData' => 'shanghaiData',
                'enemyShanghaiData' => 'enemyShanghaiData',

                'extShanghaiData' => 'extShanghaiData',//额外伤害
                'extEnemyShanghaiData' => 'extEnemyShanghaiData',

                'extBuffdata' => 'extBuffdata',//额外加血
                'extEnemyBuffdata' => 'extEnemyBuffdata',
                'self' => 'self',
                'enemy' => 'enemy',


            ];
        } else {
            $nameList = [
                'mybuff' => 'enemybuff',
                'enemybuff' => 'mybuff',
                'shanghaiData' => 'enemyShanghaiData',
                'enemyShanghaiData' => 'shanghaiData',

                'extShanghaiData' => 'extEnemyShanghaiData',//额外伤害
                'extEnemyShanghaiData' => 'extShanghaiData',

                'extBuffdata' => 'extEnemyBuffdata',//额外加血
                'extEnemyBuffdata' => 'extBuffdata',
                'self' => 'enemy',
                'enemy' => 'self',
            ];
        }

        //使用技能
        switch ($id) {
            case 50001://首回合获得%a吸血，持续$b回合。
                $this->addBuff($skillParam['b'], 'life_steal', $id, $skillParam['a'], $log, $selfDetail,
                    $nameList['mybuff']);
                break;
            case 50003://第$b回合获得%a连击。
                $this->addBuff(1, 'double_attack', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff']);
                break;
            case 50004://每次闪避时，提升%a强化副将，最多叠加$b次，直至战斗结束。
                $count = 1;
                if (isset($selfDetail['buff_skill']['fortify_pet'][$id])) {
                    $count = $selfDetail['buff_skill']['fortify_pet'][$id]['count'];
                    if ($count < $skillParam['b']) {
                        $count++;
                    }
                }
                $this->addBuff(999, 'fortify_pet', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff'], $count);
                break;
            case 50005://每次暴击后，提升%a强化暴伤，不限叠加次数，直至战斗结束。
                //处理buff
                if (isset($selfAttr['buff_skill']['fortify_critical_hit'][$id])) {
                    $count = $selfAttr['buff_skill']['fortify_critical_hit'][$id]['count'] + 1;
                } else {
                    $count = 1;
                }
                $this->addBuff(999, 'fortify_critical_hit', $id, $skillParam['a'], $log, $selfDetail,
                    $nameList['mybuff'], $count);
                break;
            case 40001://首回合获得%a暴击，直至战斗结束。
                $this->addBuff(999, 'critical_hit', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff']);
                break;
            case 40002://首回合获得%a吸血，直至战斗结束。
            case 134400://背水一战	生命值首次低于%b时，提升%a吸血，直至战斗结束。
                $this->addBuff(999, 'life_steal', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff']);
                break;
            case 40003://首回合获得%a连击，直至战斗结束。
                $this->addBuff(999, 'double_attack', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff']);
                break;
            case 40004://首回合获得%a击晕，直至战斗结束。
            case 50006:
                $this->addBuff(999, 'stun', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff']);
                break;
            case 40005://首回合获得%a反击，直至战斗结束。
                $this->addBuff(999, 'attack_back', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff']);
                break;
            case 40006://首回合获得%a闪避，直至战斗结束。
                $this->addBuff(999, 'dodge', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff']);
                break;
            case 40008://首回合获得%a速度，直至战斗结束。
            case 134102://轻装	首回合获得%a速度，直至战斗结束。
                $this->addBuff(999, 'speed', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff']);
                break;
            case 40009://第$b回合开始获得%a强化治疗，直至战斗结束。
                $this->addBuff(999, 'fortify_cure', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff']);
                break;
            case 40010://首回合获得%a攻击，直至战斗结束。
                $this->addBuff(999, 'attack', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff']);
                break;
            case 40011://首回合提升%a最大生命上限，直至战斗结束。
                $selfDetail['hp_max'] = mul($selfDetail['hp_max'], (1000 + $skillParam['a']) / 1000);
                $selfDetail['hp'] = $selfDetail['hp_max'];
                //加治疗图标
                $log[$nameList['mybuff']][] = ['id' => $id, "type" => Consts::BUFF_TYPE_LIST['fortify_cure'],
                    "heiheval" => 999, "ceng" => 0];
                break;
            case 40012://首回合获得%a防御，直至战斗结束。
                $this->addBuff(999, 'defence', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff']);
                break;
            case 40013://每次触发连击，造成一次攻击力%a伤害。
            case 40015://击晕敌人时，额外造成一次攻击力%a伤害。
            case 40017://副将释放战技时，会额外造成一次攻击力%a伤害。
            case 40030://每回合开始时，立刻对敌人造成攻击力%a伤害。
            case 40039://每次闪避时对敌人造成一次攻击力%a伤害。
            case 40024://首回合开始时，立刻对敌人造成攻击力%a伤害，可触发击晕。
            case 133202://双重打击	副将释放战技后，额外对敌人造成一次角色攻击力%a伤害。
            case 134402://刚烈不屈	反击时，额外造成攻击力%a伤害。
                $value = div($value * $skillParam['a'], 1000);
                if($value < 1){
                    $value = 1;
                }
                $log[$nameList['extShanghaiData']][] = ['type' => [], '_val' => $value];
                $log['hurt'][$nameList['self']] = add($log['hurt'][$nameList['self']], $value);
                break;
            case 40014://暴击后，提升%a攻击，持续$b回合。
            case 132304://后发制人	反击命中后，提升%a攻击，持续$b回合。
                $this->addBuff($skillParam['b'], 'attack', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff']);
                break;
            case 40016://反击时提升%a暴击，持续$b回合。
                $this->addBuff($skillParam['b'], 'critical_hit', $id, $skillParam['a'], $log, $selfDetail,
                    $nameList['mybuff']);
                break;
            case 40018://生命值低于%c时，提升%a闪避和%b强化治疗，直至战斗结束。
                $this->addBuff(999, 'dodge', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff']);
                $this->addBuff(999, 'fortify_cure', $id, $skillParam['b'], $log, $selfDetail, $nameList['mybuff']);
                break;
            case 40007://每回合开始时，恢复攻击力%a生命值。
            case 40019://攻击时有%b概率额外恢复攻击力%a生命值。
                $value = div($selfAttr['attack'] * $skillParam['a'], 1000);
                AttributeComputeService::getInstance()->computeSkillCure($value, $selfAttr['fortify_cure']);
                BattleService::getInstance()->getLifeStealNumber($value, $selfDetail);
                if ($value > 0) {
                    $log[$nameList['extBuffdata']][] = ['type' => 2, '_val' => $value];
                }
                break;
            case 40020://每次攻击后提击升%a暴，最多叠加$b次，直至战斗结束。
                if (isset($selfDetail['buff_skill']['critical_hit'][$id])) {
                    $total = $selfDetail['buff_skill']['critical_hit'][$id]['count'] + 1;
                    if ($total > $skillParam['b']) {
                        $total = $skillParam['b'];
                    }

                } else {
                    $total = 1;
                }
                $this->addBuff(999, 'critical_hit', $id, $skillParam['a'], $log, $selfDetail,
                    $nameList['mybuff'], $total);
                break;
            case 40021://每次闪避时恢复%a已损生命值。
            case 132203://净化	处于负面状态时，被攻击有%b概率恢复%a已损生命值。
            case 134100://自愈	每回合开始时恢复%a已损生命值。
            case 133200://命疗	第$b回合开始，下次副将释放战技时对主人进行一次治疗，恢复%a已损失生命值。
                $value = div(($selfDetail['hp_max'] - $selfDetail['hp']) * $skillParam['a'], 1000);
                AttributeComputeService::getInstance()->computeSkillCure($value, $selfAttr['fortify_cure']);
                BattleService::getInstance()->getLifeStealNumber($value, $selfDetail);
                if ($value > 0) {
                    $log[$nameList['extBuffdata']][] = ['type' => 2, '_val' => $value];
                }
                break;
            case 40022://每次被攻击后提升%a反击，最多叠加$b次，直至战斗结束。
                if (isset($selfDetail['buff_skill']['attack_back'][$id])) {
                    $total = $selfDetail['buff_skill']['attack_back'][$id]['count'] + 1;
                    if ($total > $skillParam['b']) {
                        $total = $skillParam['b'];
                    }
                } else {
                    $total = 1;
                }
                $this->addBuff(999, 'attack_back', $id, $skillParam['a'], $log, $selfDetail,
                    $nameList['mybuff'], $total);
                break;
            case 40023://反击时副将有%a概率释放技能协助。
                break;
            case 40025://首回合获得%a反击和连击，直至战斗结束。
                $this->addBuff(999, 'attack_back', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff']);
                $this->addBuff(999, 'double_attack', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff']);
                break;
            case 40026://首次被击倒后立即复活，并恢复%a最大生命值（红颜和战技的复活只能生效一个）。
            case 133400://起死回生	角色首次被击倒后，副将会复活角色并恢复其%a最大生命值（红颜和战法的复活只能生效一个）。
                $addHp = div($skillParam['a'] * $selfDetail['hp_max'], 1000);
                AttributeComputeService::getInstance()->computeSkillCure($addHp, $selfAttr['fortify_cure']);
                $selfDetail['hp'] = $addHp;
                if ($selfDetail['hp'] > $selfDetail['hp_max']) {
                    $selfDetail['hp'] = $selfDetail['hp_max'];
                }
                $log[$nameList['extBuffdata']][] = ['type' => 2, '_val' => $selfDetail['hp']];
                break;
            case 40027://生命值首次低于%b时，提升%a最终减伤，直至战斗结束。
                $this->addBuff(999, 'final_sub_hurt', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff']);
                break;
            case 40028://对生命值低于%b的敌人，伤害提升%a。（该伤害只能使用在，道法和人物上）
            case 40043://对生命值高于%b的敌人，伤害提升%a。
            case 50002://反击时提升%a伤害。
            case 133304://耀武扬威	副将第一次释放的战技额外提升%a伤害，治疗效果不受影响。
                if (isset($log[$nameList['shanghaiData']]['_val']) && $log[$nameList['shanghaiData']]['_val'] > 0) {
                    $value = div($log[$nameList['shanghaiData']]['_val'] * $skillParam['a'], 1000);
                    if($value < 1){
                        $value = 1;
                    }
                    $log['hurt']['self'] = add($log['hurt']['self'], $value);
                    $log[$nameList['shanghaiData']]['_val'] = add($value, $log[$nameList['shanghaiData']]['_val']);
                }
                break;
            case 40029://首回合获得等同自身防御%a攻击力，直至战斗结束。
                $addAttack = div($selfAttr['defence'] * $skillParam['a'], 1000);
                $this->addBuff(999, 'attack', $id, $addAttack, $log, $selfDetail, $nameList['mybuff']);
                break;
            case 40031://首回合获得%a暴击伤害提升，直至战斗结束。
                $this->addBuff(999, 'fortify_critical_hit', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff']);
                break;
            case 40032://生命值首次低于%b时，立即恢复%a最大生命值。
                $value = div($selfDetail['hp_max'] * $skillParam['a'], 1000);
                AttributeComputeService::getInstance()->computeSkillCure($value, $selfAttr['fortify_cure']);
                BattleService::getInstance()->getLifeStealNumber($value, $selfDetail);
                if ($value > 0) {
                    $log[$nameList['extBuffdata']][] = ['type' => 2, '_val' => $value];
                }
                break;
            case 40033://攻击时有%b概率提升%a吸血，最多叠加$c次，直至战斗结束。
                if (isset($selfDetail['buff_skill']['life_steal'][$id])) {
                    $total = $selfDetail['buff_skill']['life_steal'][$id]['count'] + 1;
                    if ($total > $skillParam['c']) {
                        $total = $skillParam['c'];
                    }
                } else {
                    $total = 1;
                }
                $this->addBuff(999, 'life_steal', $id, $skillParam['a'], $log, $selfDetail,
                    $nameList['mybuff'], $total);
                break;
            case 40034://首回合获得%a强化副将，直至战斗结束。
                $this->addBuff(999, 'fortify_pet', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff']);
                break;
            case 40035://生命值首次低于%b时，提升%a攻击，持续$c回合。
            case 132300://战意	每次攻击时有%b概率触发，提升%a攻击，持续$c回合。
                $this->addBuff($skillParam['c'], 'attack', $id, $skillParam['a'], $log, $selfDetail,
                    $nameList['mybuff']);
                break;
            case 40036://每次攻击后提升%a强化副将，最多叠加$b次，直至战斗结束。
                if (isset($selfDetail['buff_skill']['fortify_pet'][$id])) {
                    $total = $selfDetail['buff_skill']['fortify_pet'][$id]['count'] + 1;
                    if ($total > $skillParam['b']) {
                        $total = $skillParam['b'];
                    }
                } else {
                    $total = 1;
                }
                $this->addBuff(999, 'fortify_pet', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff'], $total);
                break;
            case 40037://首回合获得%a最终减伤，直至战斗结束。
            case 134101://坚盾	首回合获得%a最终减伤，直至战斗结束。
                $this->addBuff(999, 'final_sub_hurt', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff']);
                break;
            case 40038://每次被攻击后提升%a闪避，最多叠加$b次，直至战斗结束。
                if (isset($selfDetail['buff_skill']['ratio_dodge'][$id])) {
                    $total = $selfDetail['buff_skill']['ratio_dodge'][$id]['count'] + 1;
                    if ($total > $skillParam['b']) {
                        $total = $skillParam['b'];
                    }
                } else {
                    $total = 1;
                }
                $this->addBuff(999, 'dodge', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff'], $total);
                break;
            case 40040://每次攻击会额外造成一次敏捷%a伤害。
                $value = div($selfAttr['speed'] * $skillParam['a'], 1000);
                if($value < 1){
                    $value = 1;
                }
                $log[$nameList['extShanghaiData']][] = ['type' => [], '_val' => $value];
                $log['hurt']['self'] = add($log['hurt']['self'], $value);
                break;
            case 40041://首回合获得%a闪避和吸血，直至战斗结束。
                $this->addBuff(999, 'dodge', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff']);
                $this->addBuff(999, 'life_steal', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff']);
                break;
            case 40042://每次闪避时，提升%a攻击和%b强化治疗，最多叠加$c次，直至战斗结束。
                if (isset($selfDetail['buff_skill']['ratio_attack'][$id])) {
                    $total = $selfDetail['buff_skill']['ratio_attack'][$id]['count'] + 1;
                    if ($total > $skillParam['c']) {
                        $total = $skillParam['c'];
                    }
                } else {
                    $total = 1;
                }
                $this->addBuff(999, 'attack', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff'], $total);
                $this->addBuff(999, 'fortify_cure', $id, $skillParam['b'], $log, $selfDetail,
                    $nameList['mybuff'], $total);
                break;
            case 40044://副将释放战技时，会额外造成一次攻击力%a伤害，并提升%b击晕，最多叠加$c次，直至战斗结束。
                $value = div($value * $skillParam['a'], 1000);
                if($value < 1){
                    $value = 1;
                }
                $log[$nameList['extShanghaiData']][] = ['type' => [], '_val' => $value];
                $log['hurt'][$nameList['self']] = add($log['hurt'][$nameList['self']], $value);
                if (isset($selfDetail['buff_skill']['stun'][$id])) {
                    $total = $selfDetail['buff_skill']['stun'][$id]['count'] + 1;
                    if ($total > $skillParam['c']) {
                        $total = $skillParam['c'];
                    }
                } else {
                    $total = 1;
                }
                $this->addBuff(999, 'stun', $id, $skillParam['b'], $log, $selfDetail, $nameList['mybuff'], $total);
                break;
            case 40045://首回合获得%a抗暴击、抗击晕、抗连击，直至战斗结束。
                $this->addBuff(999, 're_critical_hit', $id, $skillParam['a'], $log, $selfDetail,
                    $nameList['mybuff']);
                $this->addBuff(999, 're_stun', $id, $skillParam['a'], $log, $selfDetail,
                    $nameList['mybuff']);
                $this->addBuff(999, 're_double_attack', $id, $skillParam['a'], $log, $selfDetail,
                    $nameList['mybuff']);
                break;
            case 132100://震慑	对敌人造成伤害时，降低其%a防御，持续$b回合。
                $log[$nameList['mybuff']][] = ['id' => $id, "type" => Consts::BUFF_TYPE_LIST['defence_sub'], "heiheval" => $skillParam['b'], "ceng" => 0];
                $enemyDetail['buff_skill']['ratio_defence'][$id] = ['limit' => $skillParam['b'], 'count' => 1, 'val' => -$skillParam['a']];
                break;
            case 132101://断刃	对敌人造成伤害时，降低其%a攻击，持续$b回合。
                $log[$nameList['mybuff']][] = ['id' => $id, "type" => Consts::BUFF_TYPE_LIST['attack_sub'], "heiheval" => $skillParam['b'], "ceng" => 0];
                $enemyDetail['buff_skill']['ratio_attack'][$id] = ['limit' => $skillParam['b'], 'count' => 1, 'val' => -$skillParam['a']];
                break;
            case 132102://闷棍	攻击时，提升%a击晕，最多叠加$b次，直至战斗结束。
                if (isset($selfDetail['buff_skill']['stun'][$id])) {
                    $total = $selfDetail['buff_skill']['stun'][$id]['count'] + 1;
                    if ($total > $skillParam['b']) {
                        $total = $skillParam['b'];
                    }
                } else {
                    $total = 1;
                }
                $this->addBuff(999, 'stun', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff'], $total);
                break;
            case 132200://割裂	对敌人造成伤害时施加流血效果，每回合造成敌人最大生命值%a伤害，最高不超过自身%b攻击，直至战斗结束。（流血伤害无视最终增伤和最终减伤效果）
                MagicSkillService::getInstance()->addMagicStatus('bleed', $id, 999, $skillParam['a'], $enemyDetail, $selfDetail, $log, $skillParam['b']);
                break;
            case 132201://刺轮攻	每次攻击附加敌人当前生命值%a伤害，最高不超过自身%b攻击。
                $value = div($enemyDetail['hp'] * $skillParam['a'] * (1000 - $enemyAttr['final_sub_hurt']), 1000 * 1000);
                $myValue = div($selfAttr['attack'] * $skillParam['b'], 1000);
                if ($value > $myValue) {
                    $value = $myValue;
                }
                if($value < 1){
                    $value = 1;
                }
                $log[$nameList['extShanghaiData']][] = ['type' => [], '_val' => $value];
                $log['hurt']['self'] = add($log['hurt']['self'], $value);
                break;
            case 132202://借刀	攻击时，偷取敌人%a攻击，最高不超过自身%c攻击，最多叠加$b次，直至战斗结束。
                if (isset($selfDetail['buff_skill']['attack'][$id])) {
                    $total = $selfDetail['buff_skill']['attack'][$id]['count'] + 1;
                    if ($total > $skillParam['b']) {
                        $total = $skillParam['b'];
                    }
                } else {
                    $total = 1;
                }
                $value = div($enemyAttr['attack'] * $skillParam['a'], 1000);
                $myValue = div($selfAttr['attack'] * $skillParam['c'], 1000);
                if ($value > $myValue) {
                    $value = $myValue;
                }
                if($value < 1){
                    $value = 1;
                }
                $this->addBuff(999, 'attack', $id, $value, $log, $selfDetail, $nameList['mybuff'], $total);
                $log[$nameList['mybuff']][] = ['id' => $id, "type" => Consts::BUFF_TYPE_LIST['attack_sub'], "heiheval" => 999, "ceng" => $total];
                $enemyDetail['buff_skill']['attack'][$id] = ['limit' => 999, 'count' => $total, 'val' => -$value, 'round' => $log['round']];
                break;
            case 132301://拒陆马	对敌人造成伤害时，若敌人成功闪避，则立即对敌人造成其最大生命值%a伤害，最高不超过自身%b攻击。
                $value = div($enemyDetail['hp_max'] * $skillParam['a'] * (1000 - $enemyAttr['final_sub_hurt']), 1000 * 1000);
                $myValue = div($selfAttr['attack'] * $skillParam['b'], 1000);
                if ($value > $myValue) {
                    $value = $myValue;
                }
                if($value < 1){
                    $value = 1;
                }
                $log[$nameList['extShanghaiData']][] = ['type' => [], '_val' => $value];
                $log['hurt'][$nameList['self']] = add($log['hurt'][$nameList['self']], $value);
                break;
            case 132302://火焰箭	对敌人造成伤害时，有%b概率使其燃烧，每回合造成攻击力%a伤害，持续$c回合。（燃烧伤害无视最终增伤和最终减伤效果）
            case 133303://火牛阵	副将释放战技后，有%b概率使敌人燃烧，每回合造成角色攻击力%a伤害，持续$c回合。（燃烧伤害无视最终增伤和最终减伤效果）
                MagicSkillService::getInstance()->addMagicStatus('burn', $id, $skillParam['c'], $skillParam['a'], $enemyDetail, $selfDetail, $log);
                break;
            case 132303://精准打击	攻击时，对敌人施加易伤状态，产生暴击后，附加额外%a伤害，并移除该状态。
                MagicSkillService::getInstance()->addMagicStatus('vulnerability', $id, 999, $skillParam['a'], $enemyDetail, $selfDetail, $log);
                break;
            case 132400://一鼓作气	触发连击时，恢复本次伤害%a生命值，并提升$b点士气。
                $hurt = isset($log[$nameList['shanghaiData']]['_val']) ? $log[$nameList['shanghaiData']]['_val'] : 0;
                if ($hurt > 0) {
                    $value = div($hurt * $skillParam['a'], 1000);
                    AttributeComputeService::getInstance()->computeSkillCure($value, $selfAttr['fortify_cure']);
                    BattleService::getInstance()->getLifeStealNumber($value, $selfDetail);
                    if ($value > 0) {
                        $log[$nameList['extBuffdata']][] = ['type' => 2, '_val' => $value];
                    }
                }
                MagicSkillService::getInstance()->addStamina($selfDetail, $skillParam['b']);
                break;
            case 132401://虎啸龙咆	攻击时，减少敌人$a点士气。
                MagicSkillService::getInstance()->subStamina($enemyDetail, $skillParam['a']);
                break;
            case 132402://致命一击	对敌人造成伤害时，有%b概率造成%a伤害，有%d概率造成%c伤害。 %b+%d最大低于100%
                $hurt = isset($log['shanghaiData']['_val']) ? $log['shanghaiData']['_val'] : 0;
                if ($skillParam['b'] >= rand(1, $skillParam['b'] + $skillParam['d'])) {
                    $log['shanghaiData']['_val'] = div($hurt * $skillParam['a'], 1000);
                } else {
                    $log['shanghaiData']['_val'] = div($hurt * $skillParam['c'], 1000);
                }
                $value = $log['shanghaiData']['_val'] - $hurt;
                if($value < 1){
                    $value = 1;
                }
                $log['hurt']['self'] = add($log['hurt']['self'], $value);
                break;
            case 132403://乱刀狂舞	攻击时若触发暴击，则立即恢复攻击力%a生命值，并提升%b强化暴伤，持续$c回合。
                $value = div($selfAttr['attack'] * $skillParam['a'], 1000);
                AttributeComputeService::getInstance()->computeSkillCure($value, $selfAttr['fortify_cure']);
                BattleService::getInstance()->getLifeStealNumber($value, $selfDetail);
                if ($value > 0) {
                    $log[$nameList['extBuffdata']][] = ['type' => 2, '_val' => $value];
                }
                $this->addBuff($skillParam['c'], 'fortify_critical_hit', $id, $skillParam['b'], $log, $selfDetail, $nameList['mybuff']);
                break;
            case 132404://大地狂啸	释放兵法时，提升%a伤害，并有%b概率击晕敌人$c回合。
                $hurt = isset($log['shanghaiData']['_val']) ? $log['shanghaiData']['_val'] : 0;
                $log['shanghaiData']['_val'] = div($hurt * ($skillParam['a'] + 1000), 1000);
                $value = $log['shanghaiData']['_val'] - $hurt;
                $log['hurt']['self'] = add($log['hurt']['self'], $value);

                if ($skillParam['b'] >= rand(1, 1000)) {
                    $item = $skillParam['c'];
                    if (isset($enemyDetail['debuff']['stun'])) {
                        if ($enemyDetail['debuff']['stun'] > $item) {
                            $item = $enemyDetail['debuff']['stun'];
                        }
                    }
                    $enemyDetail['debuff']['stun'] = $item;

                    if (isset($log[$nameList['shanghaiData']]['type'])) {
                        $log[$nameList['shanghaiData']]['type'][] = BattleService::STUN;
                    } else {
                        $log[$nameList['shanghaiData']] = ['type' => BattleService::STUN, '_val' => $value];
                    }

                    $specialBuff = ['type' => Consts::STUN, 'num' => $item];
                    $log[$nameList['shanghaiData']] ['specialBuff'][] = $specialBuff;
                    SkillService::getInstance()->triggerSkill($log, $selfSkill, $selfAttr, $enemyAttr, $selfDetail, $enemyDetail, 5);
                }
                break;
            case 132405://八卦奇阵	每次攻击敌人时，提升%a强化副将，可无限叠加，持续到下回合结束。
                $idCopy = $id;
                $id = $id . '_' . $log['round'];
                if (isset($selfDetail['buff_skill']['fortify_pet'][$id])) {
                    $total = $selfDetail['buff_skill']['fortify_pet'][$id]['count'] + 1;
                } else {
                    $total = 1;
                }
                $this->addBuff(2, 'fortify_pet', $idCopy, $skillParam['a'], $log, $selfDetail, $nameList['mybuff'], $total);
                break;
            case 132406://以命相搏	提升%c吸血，攻击时消耗%a当前生命值，扣除敌人等额生命值，最高不超过自身%b攻击。
                $this->addBuff(999, 'life_steal', $id, $skillParam['c'], $log, $selfDetail, $nameList['mybuff']);
                $value = div($skillParam['a'] * $selfDetail['hp'], 1000);
                $myValue = div($selfAttr['attack'] * $skillParam['b'], 1000);
                if ($value > $myValue) {
                    $value = $myValue;
                }
                if($value < 1){
                    $value = 1;
                }
                $log['extShanghaiData'][] = ['type' => [], '_val' => $value];
                $log['extEnemyShanghaiData'][] = ['type' => [], '_val' => $value];
                $log['hurt']['self'] = add($log['hurt']['self'], $value);
                $log['hurt']['enemy'] = add($log['hurt']['enemy'], $value);
                break;
            case 133100://泉涌	使上阵副将的治疗效果提升%a。
                $selfDetail['buff_skill_pet']['cure'] = ['count' => 1, 'val' => $skillParam['a']];
                break;
            case 133101://默契	使上阵副将造成的伤害提升%a。
                $selfDetail['buff_skill_pet']['harm'] = ['count' => 1, 'val' => $skillParam['a']];
                break;
            case 133102://夜袭	副将释放战技后，提升角色%a速度，可无限叠加，直至战斗结束。
                if (isset($selfDetail['buff_skill']['ratio_speed'][$id])) {
                    $total = $selfDetail['buff_skill']['ratio_speed'][$id]['count'] + 1;
                } else {
                    $total = 1;
                }
                $this->addBuff(999, 'speed', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff'], $total);
                break;
            case 133201://袭扰	副将释放战技后，提升角色%a抗连击，持续$b回合。
                $this->addBuff($skillParam['b'], 're_double_attack', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff']);
                break;
            case 133203://斗志削弱	副将攻击处于负面状态的敌人时，降低其%a最终减伤，持续$b回合。
                $log[$nameList['mybuff']][] = ['id' => $id, "type" => Consts::BUFF_TYPE_LIST['defence_sub'], "heiheval" => $skillParam['b'], "ceng" => 0];
                $enemyDetail['buff_skill']['final_sub_hurt'][$id] = ['limit' => $skillParam['b'], 'count' => 1, 'val' => -$skillParam['a']];
                break;
            case 133300://破胆	副将释放战技后，降低敌人%a抗暴击和抗吸血，持续$b回合。
                $log[$nameList['mybuff']][] = ['id' => $id, "type" => Consts::BUFF_TYPE_LIST['re_critical_hit_sub'], "heiheval" => $skillParam['b'], "ceng" => 0];
                $enemyDetail['buff_skill']['re_critical_hit'][$id] = ['limit' => $skillParam['b'], 'count' => 1, 'val' => -$skillParam['a']];
                $log[$nameList['mybuff']][] = ['id' => $id, "type" => Consts::BUFF_TYPE_LIST['re_life_steal_sub'], "heiheval" => $skillParam['b'], "ceng" => 0];
                $enemyDetail['buff_skill']['re_life_steal'][$id] = ['limit' => $skillParam['b'], 'count' => 1, 'val' => -$skillParam['a']];
                break;
            case 133301://避害	副将首次释放战技时，恢复角色%a最大生命值，并免疫燃烧和冰冻，持续$b回合。
                $value = div($selfDetail['hp_max'] * $skillParam['a'], 1000);
                AttributeComputeService::getInstance()->computeSkillCure($value, $selfAttr['fortify_cure']);
                BattleService::getInstance()->getLifeStealNumber($value, $selfDetail);
                if ($value > 0) {
                    $log[$nameList['extBuffdata']][] = ['type' => 2, '_val' => $value];
                }
                MagicSkillService::getInstance()->addMagicStatus('immunity', $id, $skillParam['b'], 0, $enemyDetail, $selfDetail, $log);
                break;
            case 133302://冰柱	每过$b回合，在副将下一次释放战技后，有%a概率使敌人冰冻至下回合。
                MagicSkillService::getInstance()->addMagicStatus('freeze', $id, 1, 0, $enemyDetail, $selfDetail, $log);
                break;
            case 133401://断生机	副将释放战技时，使敌人收到的治疗效果降低%a，持续$b回合。
                //加自己的弱化治疗  weaken_cure
                $this->addBuff($skillParam['b'], 'weaken_cure', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff']);
                break;
            case 133402://铁骑突击	副将首次释放战技后，角色提升%a连击和抗连击，同时移除对手等量属性，直至战斗结束。
                $this->addBuff(999, 'double_attack', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff']);
                $this->addBuff(999, 're_double_attack', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff']);

                $log[$nameList['mybuff']][] = ['id' => $id, "type" => Consts::BUFF_TYPE_LIST['double_attack_sub'], "heiheval" => 999, "ceng" => 0];
                $enemyDetail['buff_skill']['double_attack'][$id] = ['limit' => 999, 'count' => 1, 'val' => -$skillParam['a']];
                $log[$nameList['mybuff']][] = ['id' => $id, "type" => Consts::BUFF_TYPE_LIST['re_double_attack_sub'], "heiheval" => 999, "ceng" => 0];
                $enemyDetail['buff_skill']['re_double_attack'][$id] = ['limit' => 999, 'count' => 1, 'val' => -$skillParam['a']];
                break;
            case 133403://同心协力	副将每次攻击后，为角色恢复%a最大生命值，并提升其%b攻击，持续$c回合。
                $value = div($selfDetail['hp_max'] * $skillParam['a'], 1000);
                AttributeComputeService::getInstance()->computeSkillCure($value, $selfAttr['fortify_cure']);
                BattleService::getInstance()->getLifeStealNumber($value, $selfDetail);
                if ($value > 0) {
                    $log[$nameList['extBuffdata']][] = ['type' => 2, '_val' => $value];
                }
                $this->addBuff($skillParam['c'], 'attack', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff']);
                break;
            case 133404://鼓舞	副将释放战技后，提升角色$a点士气。
            case 134404://斗志昂扬	每回合开始时，提升$a点士气。
                MagicSkillService::getInstance()->addStamina($selfDetail, $skillParam['a']);
                break;
            case 133405://后伏军阵	副将每次攻击后，降低敌人%a所有战斗属性，持续$b回合
                //ignore_arr
                // 'stun' => '0', 'critical_hit' => '0', 'double_attack' => '0', 'dodge' => '0', 'attack_back' => '0', 'life_steal' => '0',
                $log[$nameList['mybuff']][] = ['id' => $id, "type" => Consts::BUFF_TYPE_LIST['stun_sub'], "heiheval" => $skillParam['b'], "ceng" => 0];
                $log[$nameList['mybuff']][] = ['id' => $id, "type" => Consts::BUFF_TYPE_LIST['critical_hit_sub'], "heiheval" => $skillParam['b'], "ceng" => 0];
                $log[$nameList['mybuff']][] = ['id' => $id, "type" => Consts::BUFF_TYPE_LIST['double_attack_sub'], "heiheval" => $skillParam['b'], "ceng" => 0];
                $log[$nameList['mybuff']][] = ['id' => $id, "type" => Consts::BUFF_TYPE_LIST['dodge_sub'], "heiheval" => $skillParam['b'], "ceng" => 0];
                $log[$nameList['mybuff']][] = ['id' => $id, "type" => Consts::BUFF_TYPE_LIST['attack_back_sub'], "heiheval" => $skillParam['b'], "ceng" => 0];
                $log[$nameList['mybuff']][] = ['id' => $id, "type" => Consts::BUFF_TYPE_LIST['life_steal_sub'], "heiheval" => $skillParam['b'], "ceng" => 0];
                $selfDetail['buff_skill']['ignore_arr'][$id] = ['limit' => $skillParam['b'], 'count' => 1, 'val' => $skillParam['a']];
                break;
            case 133406://威名赫赫	副将释放战技后，提升角色%a吸血和%b强化治疗，持续$c回合。
                $this->addBuff($skillParam['c'], 'life_steal', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff']);
                $this->addBuff($skillParam['c'], 'fortify_cure', $id, $skillParam['b'], $log, $selfDetail, $nameList['mybuff']);
                break;
            case 134200://暴怒	每次受到攻击后，提升自身%a攻击，最多叠加$b次，直至战斗结束。
                if (isset($selfDetail['buff_skill']['ratio_attack'][$id])) {
                    $total = $selfDetail['buff_skill']['ratio_attack'][$id]['count'] + 1;
                    if ($total > $skillParam['b']) {
                        $total = $skillParam['b'];
                    }
                } else {
                    $total = 1;
                }
                $this->addBuff(999, 'attack', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff'], $total);
                break;
            case 134203://陷阵之志	生命值低于%b时，提升%a攻击。
                $this->addBuff(999, 'attack', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff']);
                break;
            case 134201://陷阱	闪避时，有%a概率击晕敌人$b回合。
                $item = $skillParam['b'] + 1;
                if (isset($enemyDetail['debuff']['stun'])) {
                    if ($enemyDetail['debuff']['stun'] > $item) {
                        $item = $enemyDetail['debuff']['stun'];
                    }
                }
                $enemyDetail['debuff']['stun'] = $item;

                if (isset($log[$nameList['shanghaiData']]['type'])) {
                    $log[$nameList['shanghaiData']]['type'][] = BattleService::STUN;
                } else {
                    $log[$nameList['shanghaiData']] = ['type' => BattleService::STUN, '_val' => $value];
                }

                $specialBuff = ['type' => Consts::STUN, 'num' => $item];
                $log[$nameList['shanghaiData']] ['specialBuff'][] = $specialBuff;
                SkillService::getInstance()->triggerSkill($log, $selfSkill, $selfAttr, $enemyAttr, $selfDetail, $enemyDetail, 5);
                break;
            case 134202://急疗	每次受到伤害，有%b概率恢复所受伤害%a生命值。
                $hurt = isset($log[$nameList['enemyShanghaiData']]['_val']) ? $log[$nameList['enemyShanghaiData']]['_val'] : 0;
                if ($hurt > 0) {
                    $value = div($hurt * $skillParam['a'], 1000);
                    AttributeComputeService::getInstance()->computeSkillCure($value, $selfAttr['fortify_cure']);
                    BattleService::getInstance()->getLifeStealNumber($value, $selfDetail);
                    if ($value > 0) {
                        $log[$nameList['extBuffdata']][] = ['type' => 2, '_val' => $value];
                    }
                }
                break;
            case 134300://鹰眼	首回合获得%a暴击率和%b强化暴伤，持续$c回合。
                $this->addBuff($skillParam['c'], 'critical_hit', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff']);
                $this->addBuff($skillParam['c'], 'fortify_critical_hit', $id, $skillParam['b'], $log, $selfDetail, $nameList['mybuff']);
                break;
            case 134301://坚韧	生命值低于%b时，每次受到的伤害不会高于%a自身最大生命值，持续$c回合。
                //todo
                $selfDetail['buff_skill_special'][$id] = ['limit' => $skillParam['c'], 'val' => $skillParam['a']];
                break;
            case 134302://振奋	反击后提升%a强化副将，持续$b回合。
                $this->addBuff($skillParam['b'], 'fortify_pet', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff']);
                break;
            case 134303://藤甲	战斗开始时，受到的所有伤害降低%a，持续$b回合。
                $this->addBuff($skillParam['b'], 'final_sub_hurt', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff']);
                break;
            case 134304://怒气爆发	每次受到攻击后，提升%a强化副将，最多叠加$b次，持续到下次副将行动。
                if (isset($selfDetail['buff_skill']['fortify_pet'][$id])) {
                    $total = $selfDetail['buff_skill']['fortify_pet'][$id]['count'] + 1;
                    if ($total > $skillParam['b']) {
                        $total = $skillParam['b'];
                    }
                } else {
                    $total = 1;
                }
                $this->addBuff('999', 'fortify_pet', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff'], $total);
                break;
            case 134401://荆棘铠甲	每次受到攻击时，向敌人反弹%a伤害。
                $hurt = isset($log[$nameList['enemyShanghaiData']]['_val']) ? $log[$nameList['enemyShanghaiData']]['_val'] : 0;
                if ($hurt > 0) {
                    $value = div($hurt * $skillParam['a'], 1000);
                    if($value < 1){
                        $value = 1;
                    }
                    $log[$nameList['extShanghaiData']][] = ['type' => [], '_val' => $value];
                    $log['hurt'][$nameList['self']] = add($log['hurt'][$nameList['self']], $value);
                }
                break;
            case 134403://闪避姿态	闪避后，提升%a暴击，持续$b回合。
                if (isset($selfDetail['buff_skill']['critical_hit'][$id])) {
                    $total = $selfDetail['buff_skill']['critical_hit'][$id]['count'] + 1;
                    if ($total > $skillParam['b']) {
                        $total = $skillParam['b'];
                    }
                } else {
                    $total = 1;
                }
                $this->addBuff($skillParam['b'], 'critical_hit', $id, $skillParam['a'], $log, $selfDetail, $nameList['mybuff'], $total);
                break;
            case 134405://偷天换日	战斗开始时，偷取敌人%a速度和%b攻击，偷取攻击最高不超过自身%d攻击，持续$c回合。
                $value = div($enemyAttr['attack'] * $skillParam['b'], 1000);
                $myValue = div($selfAttr['attack'] * $skillParam['d'], 1000);
                if ($value > $myValue) {
                    $value = $myValue;
                }
                $this->addBuff($skillParam['c'], 'attack', $id, $value, $log, $selfDetail, $nameList['mybuff']);
                $log[$nameList['mybuff']][] = ['id' => $id, "type" => Consts::BUFF_TYPE_LIST['attack_sub'], "heiheval" => $skillParam['c'] + 1, "ceng" => 0];
                $enemyDetail['buff_skill']['attack'][$id] = ['limit' => $skillParam['c'] + 1, 'count' => 0, 'val' => -$value, 'round' => $log['round']];

                $value = div($enemyAttr['speed'] * $skillParam['a'], 1000);
                $this->addBuff($skillParam['c'], 'speed', $id, $value, $log, $selfDetail, $nameList['mybuff']);
                $log[$nameList['mybuff']][] = ['id' => $id, "type" => Consts::BUFF_TYPE_LIST['speed_sub'], "heiheval" => $skillParam['c'] + 1, "ceng" => 0];
                $enemyDetail['buff_skill']['speed'][$id] = ['limit' => $skillParam['c'] + 1, 'count' => 0, 'val' => -$value, 'round' => $log['round']];
                break;
            case 134406://烈焰焚身	击晕敌人或者攻击处于眩晕状态的敌人时，若敌人处于燃烧状态，则立即结算一次，且燃烧伤害提升%a。（燃烧伤害无视最终增伤和最终减伤效果）
                //1,触发效果
                $value = 0;
                $limit = 0;
                $skillId = 0;
                foreach ($enemyDetail['buff_magic']['burn'] as $id => $data) {
                    if ($data['value'] > $value) {
                        $value = $data['value'];
                    }
                    if ($data['limit'] > $limit) {
                        $limit = $data['limit'];
                        $skillId = $id;
                    }
                    //2，扣除回合
                    $enemyDetail['buff_magic']['burn'][$id]['limit']--;
                    if ($enemyDetail['buff_magic']['burn'][$id]['limit'] == 0) {
                        unset($enemyDetail['buff_magic']['burn'][$id]);
                    }
                }
                //预防出现特殊情况
                if ($value <= 0) {
                    break;
                }
                //3，修改燃烧伤害值-
                $value = div($value * (1000 + $skillParam['a']), 1000);
                //1,触发效果
                MagicSkillService::getInstance()->triggerBurnStatus($log, $value, $enemyAttr, $nameList);
                if ($limit - 1 > 0) {
                    //刷新客户端状态
                    $log['status'][] = ['id' => $skillId, 'type' => Consts::BATTLE_BUFF_STATUS['burn'],
                        'round' => $limit - 1];
                }
                break;
            default:
                break;
        }
    }


    //触发技能类型22 //每过$b回合，在副将下一次释放战技后，有%a概率触发
    public function isTriggerSkillType22($selfSkill, $round, $selfDetail)
    {
        //判断冰冻状态,封闭精怪技能，只能触发神通-副将技能
        if (isset($selfDetail['debuff']['freeze'])) {
            $selfSkill['spirit'] = [];
            $selfSkill['magic'] = $selfSkill['magic']['pet'] ? [$selfSkill['magic']['pet']] : [];
        } else {
            unset($selfSkill['magic']['pet']);
        }

        $skillList = $this->getSkillList($selfSkill, 22);
        $resSkillList = [];
        if (!$skillList) {
            return $resSkillList;
        }

        foreach ($skillList as $id => $value) {

            //获取技能参数
            $skillConf = ConfigSkill::getInstance()->getOne($id);
            $skillParam = [
                'a' => $skillConf['params'][0][0] + ($value['lv'] - 1) * $skillConf['upgradeParams'][0][0],
                'b' => $skillConf['params'][0][1] + ($value['lv'] - 1) * $skillConf['upgradeParams'][0][1],
                'c' => $skillConf['params'][0][2] + ($value['lv'] - 1) * $skillConf['upgradeParams'][0][2],
                'd' => $skillConf['params'][0][3] + ($value['lv'] - 1) * $skillConf['upgradeParams'][0][3],
            ];

            if ($round % ($skillParam['b'] + 1) == 0 && $skillParam['a'] > rand(1, 1000)) {
                $resSkillList[$id] = ['lv' => $value['lv'], 'type' => $value['type']];
            }
        }

        return $resSkillList;

    }

    /**
     * 格式化技能列表可以在TriggerSkill方法使用
     * @param $skillList
     * @return array|array[]
     */
    public function formatSkillList($skillList): array
    {
        $list = ['tactical' => [], 'spirit' => [], 'magic' => [],];
        $skillTypeList = [3 => 'tactical', 2 => 'spirit', 4 => 'magic'];
        foreach ($skillList as $id => $value) {
            $list[$skillTypeList[$value['type']]][$id] = $value['lv'];
        }

        return $list;

    }


}