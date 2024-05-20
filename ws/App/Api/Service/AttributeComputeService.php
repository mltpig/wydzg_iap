<?php
/**
 * 属性计算
 */

namespace App\Api\Service;

use App\Api\Utils\Consts;
use EasySwoole\Component\CoroutineSingleTon;

class AttributeComputeService
{
    use CoroutineSingleTon;

    /**
     * 计算基础属性
     * @param $attr
     * @param array $ratio
     * @return void
     */
    public function computeBaseAttr(&$attr, array $ratio): void
    {
        $attr['hp'] = mul($attr['hp'], ($ratio['ratio_hp'] + 1000) / 1000);
        $attr['speed'] = mul($attr['speed'], ($ratio['ratio_speed'] + 1000) / 1000);
        $attr['attack'] = mul($attr['attack'], ($ratio['ratio_attack'] + 1000) / 1000);
        $attr['defence'] = mul($attr['defence'], ($ratio['ratio_defence'] + 1000) / 1000);

        unset($ratio['ratio_attack'], $ratio['ratio_defence'], $ratio['ratio_hp'], $ratio['ratio_speed']);

        //1025=强化战斗抗性 1027=强化战斗属性
        //加成  击晕    暴击    连击    闪避    反击    吸血
        $battleAttr = Consts::SECOND_ATTRIBUTE;
        foreach ($battleAttr as $battleName) {
            $attr[$battleName] = add($attr[$battleName], $ratio['ratio_battle']);
        }
        //抗性 击晕    暴击    连击    闪避    反击    吸血
        $battleReAttr = Consts::SECOND_DEF_ATTRIBUTE;
        foreach ($battleReAttr as $reName) {
            $attr[$reName] = add($attr[$reName], $ratio['ratio_re']);
        }

        //强化战斗抗性    强化战斗属性
        unset($ratio['ratio_battle'], $ratio['ratio_re']);

        foreach ($ratio as $attrName => $attrVal) {
            if (!array_key_exists($attrName, $attr)) continue;
            $attr[$attrName] = add($attr[$attrName], $attrVal);
        }

    }


    /**
     * 计算暴击
     * @param int $hurt 普攻伤害
     * @param int $selfFortify 强化暴伤
     * @return void
     */
    public function computeCriticalHit(int &$hurt, int $selfFortify): void
    {
        //普攻暴击：（攻击-被防御）*（100%+增伤-被减伤）*（200%+强化暴伤-被弱化暴伤）
        $hurt = mul($hurt, (2000 + $selfFortify) / 1000);
        if ($hurt <= 0) {
            $hurt = 1;
        }
    }

    /**
     * 普通攻击
     * @param int $selfAttack
     * @param int $enemyDefence
     * @param int $selfFinalHurt
     * @return int|string|null
     */
    public function computeHit(int $selfAttack, int $enemyDefence, int $selfFinalHurt): int
    {
        //（攻击-被防御）*（100%+增伤-被减伤）
        $hurt = div(($selfAttack - $enemyDefence) * (1000 + $selfFinalHurt), 1000);
        if ($hurt <= 0) {
            $hurt = 1;
        }
        return $hurt;
    }

    public function computeMagicHit(int $selfAttack, int $enemyDefence, int $selfFinalHurt, int $selfFortifyMagic, int $magicItem): int
    {
        //道法：（攻击*道法倍率-被防御）*（100%+增伤-被减伤）*（100%+强化道伤-被弱化道伤）
        $hurt = div(sub($selfAttack * $magicItem / 1000, $enemyDefence) * (1000 + $selfFinalHurt) *
            (1000 + $selfFortifyMagic), 1000 * 1000);
        if ($hurt <= 0) {
            $hurt = 1;
        }
        return $hurt;
    }


    /**
     * 计算灵兽攻击
     * @param array $self
     * @return int
     */
    public function computePetHit(array $self): int
    {
        //灵兽攻击：攻击*灵兽技能倍率*（100%+增伤-被减伤）*（100%+强化灵兽-被弱化灵兽）
//        $petSkillValue = $self['attack']  * (1000 + $self['final_hurt']-$enemyFinalSubHurt)/1000
//        * (1000 + $self['fortify_pet'] - $enemyWeakenPet) /1000;
        $hurt = div($self['attack'] * (1000 + $self['final_hurt'])
            * (1000 + $self['fortify_pet']), 1000 * 1000);
        if ($hurt <= 0) {
            $hurt = 1;
        }
        return $hurt;
    }


    /**
     * 灵兽的治疗类技能
     * @param int $value
     * @param int $selfFortifyCure 强疗
     * @param int $selfFortifyPet 强灵
     * @return void
     */
    public function computePetCure(int &$value, int $selfFortifyCure, int $selfFortifyPet)
    {
        //恢复量*（100%+强疗-被弱疗）*（100%+强灵-被弱灵）
//        $value = $value * (1000 + $selfFortifyCure - $enemyWeakenCure) / 1000
//            * (1000 + $selfFortifyPet - $enemyWeakenPet) / 1000;
        $value = div($value * (1000 + $selfFortifyCure) * (1000 + $selfFortifyPet), 1000 * 1000);
        if ($value <= 0) {
            $value = 1;
        }
    }


    public function computeSkillCure(int &$value, int $selfFortifyCure)
    {
        //恢复量*（100%+强疗-被弱疗）
//        $value = $value * (1000 + $selfFortifyCure - $enemyWeakenCure) / 1000
//            * (1000 + $selfFortifyPet - $enemyWeakenPet) / 1000;
        $value = div($value * (1000 + $selfFortifyCure), 1000);
        if ($value <= 0) {
            $value = 1;
        }
    }


    /**
     * 吸血
     * @param int $hurt 伤害
     * @param int $selfFortifyCure 强疗
     * @param int $selfLifeSteal 吸血
     * @return int
     */
    public function computeLifeSteal(int $hurt, int $selfFortifyCure, int $selfLifeSteal): int
    {
        //造成的伤害*（100%+强疗-被弱疗）*（吸血-抗吸血）
        $value = div($hurt * (1000 + $selfFortifyCure) * $selfLifeSteal, 1000 * 1000);
        if ($value <= 0) {
            $value = 1;
        }
        return $value;
    }


    //提前汇算属性中间值
    public function getBattleAttr($selfAttr, $enemyAttr): array
    {
        $data = array();
        foreach (Consts::SECOND_ATTRIBUTE as $value) {
            //我方：击晕 =（我方击晕 - 敌方无视战斗属性）-（敌方抗击晕 -我方无视战斗抗性）
            //$enemyAttr['ignore_arr'] $selfAttr['ignore_arr_re']
            $data[$value] = sub($selfAttr[$value] - $enemyAttr['ignore_arr'],
                $enemyAttr['re_' . $value] - $selfAttr['ignore_arr_re']);
            if ($data[$value] < 0) {
                $data[$value] = 0;
            }
        }


        $data['attack'] = $selfAttr['attack'];
        $data['defence'] = $selfAttr['defence'];
        $data['speed'] = $selfAttr['speed'];

        $data['final_hurt'] = $selfAttr['final_hurt'] - $enemyAttr['final_sub_hurt'];
        $data['final_sub_hurt']= $selfAttr['final_sub_hurt'];

        $data['fortify_cure'] = $selfAttr['fortify_cure'] - $enemyAttr['weaken_cure'];
        $data['fortify_pet'] = $selfAttr['fortify_pet'] - $enemyAttr['weaken_pet'];
        $data['fortify_critical_hit'] = $selfAttr['fortify_critical_hit'] - $enemyAttr['weaken_critical_hit'];
        $data['fortify_magic'] = $selfAttr['fortify_magic'] - $enemyAttr['weaken_magic'];
        $data['magic_double_attack'] = $selfAttr['magic_double_attack'] - $enemyAttr['re_magic_double_attack'];

        foreach (['final_hurt', 'fortify_cure', 'fortify_cure', 'fortify_critical_hit', 'fortify_magic'] as $name) {
            if ($data[$name] + 1000 < 0) {
                $data[$name] = -1000;
            }
        }

        return $data;
    }


    /**
     * 添加属性，测试使用
     * @param $arr
     * @param $test
     * @return void
     */
    public function getTestAttribute(&$arr, $test)
    {
        if ($test) {
            foreach ($test as $name => $value) {
                $arr[$name] = add($arr[$name], $value);
            }
        }
    }

    /**
     * 计算技能攻击（非灵兽）
     * @param array $self
     * @return int
     */
    public function computeSkillHit(array $self): int
    {
        //灵兽攻击：攻击*灵兽技能倍率*（100%+增伤-被减伤）
//        $petSkillValue = $self['attack']  * (1000 + $self['final_hurt']-$enemyFinalSubHurt)/1000
//        * (1000 + $self['fortify_pet'] - $enemyWeakenPet) /1000;
        $hurt = div($self['attack'] * (1000 + $self['final_hurt']), 1000);
        if ($hurt <= 0) {
            $hurt = 1;
        }
        return $hurt;
    }

    //限制血量减少
    public function limitHpSub($enemyDetail, &$hurt, &$log)
    {
        if (isset($enemyDetail['buff_skill_special'][134301])) {
            $maxHurt = $enemyDetail['buff_skill_special'][134301]['val'] * $enemyDetail['hp_max'] / 1000;
            $maxHurt = (int)$maxHurt;
            if ($hurt > $maxHurt) {
                $hurt = $maxHurt;
                $log['shanghaiData']['_val'] = $hurt;
            }
        }
    }


    //ext伤害扣除
    public function limitHpExtSub($selfDetail, $enemyDetail, &$log)
    {

        if (isset($selfDetail['buff_skill_special'][134301]) && count($log['extEnemyShanghaiData']) > 0) {
            $maxHurt = $selfDetail['buff_skill_special'][134301]['val'] * $selfDetail['hp_max'] / 1000;
            $maxHurt = (int)$maxHurt;
            foreach ($log['extEnemyShanghaiData'] as $key => $item) {
                if ($item['_val'] > $maxHurt) {
                    $log['extEnemyShanghaiData'][$key]['_val'] = $maxHurt;
                    $log['hurt']['enemy'] = $log['hurt']['enemy'] - $item['_val'] + $maxHurt;
                }
            }
        }

        if (isset($enemyDetail['buff_skill_special'][134301]) && count($log['extShanghaiData']) > 0) {
            $maxHurt = $enemyDetail['buff_skill_special'][134301]['val'] * $enemyDetail['hp_max'] / 1000;
            $maxHurt = (int)$maxHurt;
            foreach ($log['extShanghaiData'] as $key => $item) {
                if ($item['_val'] > $maxHurt) {
                    $log['extShanghaiData'][$key]['_val'] = $maxHurt;
                    $log['hurt']['self'] = $log['hurt']['self'] - $item['_val'] + $maxHurt;
                }
            }
        }

    }


}