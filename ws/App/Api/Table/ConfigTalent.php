<?php

namespace App\Api\Table;

use EasySwoole\EasySwoole\Logger;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigTalent as Model;

class ConfigTalent
{
    use CoroutineSingleTon;

    protected $tableName = 'config_talent';

    protected $normalFieldList = ['id', 'icon', 'quality', 'type', 'weight', 'name', 'desc'];//正常字段数组，不需要额外处理

    public function create(): void
    {
        $columns = [
            'id' => ['type' => Table::TYPE_INT, 'size' => 8],
            'icon' => ['type' => Table::TYPE_STRING, 'size' => 50],
            'quality' => ['type' => Table::TYPE_INT, 'size' => 8],//品质
            'level' => ['type' => Table::TYPE_STRING, 'size' => 50],
            'type' => ['type' => Table::TYPE_INT, 'size' => 8],
            'fir_attribute_ran' => ['type' => Table::TYPE_STRING, 'size' => 128],
            'weight' => ['type' => Table::TYPE_INT, 'size' => 8],//权重
            'name' => ['type' => Table::TYPE_STRING, 'size' => 50],//名字

            // ","primAttack":"	","primHP":"	","primDefence":"	","primSpeed":" 基础属性0-3
            'prim_attack' => ['type' => Table::TYPE_STRING, 'size' => 50],
            'prim_hp' => ['type' => Table::TYPE_STRING, 'size' => 50],
            'prim_defence' => ['type' => Table::TYPE_STRING, 'size' => 50],
            'prim_speed' => ['type' => Table::TYPE_STRING, 'size' => 50],
            //","stun":"	","criticalHit":"	","doubleAttack":"	","dodge":"	","attackBack":,"lifeSteal":"第二词条4-9
            'stun' => ['type' => Table::TYPE_STRING, 'size' => 50],
            'critical_hit' => ['type' => Table::TYPE_STRING, 'size' => 50],
            'double_attack' => ['type' => Table::TYPE_STRING, 'size' => 50],
            'dodge' => ['type' => Table::TYPE_STRING, 'size' => 50],
            'attack_back' => ['type' => Table::TYPE_STRING, 'size' => 50],
            'life_steal' => ['type' => Table::TYPE_STRING, 'size' => 50],

            //"reStun":","reCriticalHit":"","reDoubleAttack":","reDodge":","reAttackBack"reLifeSteal":"第二抗性词条 10-15
            're_stun' => ['type' => Table::TYPE_STRING, 'size' => 50],
            're_critical_hit' => ['type' => Table::TYPE_STRING, 'size' => 50],
            're_double_attack' => ['type' => Table::TYPE_STRING, 'size' => 50],
            're_dodge' => ['type' => Table::TYPE_STRING, 'size' => 50],
            're_attack_back' => ['type' => Table::TYPE_STRING, 'size' => 50],
            're_life_steal' => ['type' => Table::TYPE_STRING, 'size' => 50],

            //","addDamage":"","minusDamage":"","addCritical":","minusCritical":",//暂时不使用
            //"addHeal":","minusHeal":","addPet":","minusPet":"
        ];

        TableManager::getInstance()->add($this->tableName, $columns, 200);

    }

    public function initTable(): void
    {
        $table = TableManager::getInstance()->get($this->tableName);

        $tableConfig = Model::create()->all();
        foreach ($tableConfig as $value) {
            //处理非正常存储的字段
            $value = $value->toArray();

            foreach ($value as $key => $info) {
                if (!in_array($key, $this->normalFieldList)) {
                    $data[$key] = json_encode(explode('|', $value[$key]));
                } else {
                    $data[$key] = $value[$key];
                }
            }
            $id = $value['id'];
            unset($data['id'], $data['desc']);
            $table->set($id, $data);
        }

    }

    public function getOne(int $id): array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if (!$table->count()) $this->initTable();

        $data = $table->get($id);
        //处理非正常存储的字段
        foreach ($data as $key => $info) {
            if (!in_array($key, $this->normalFieldList)) {
                $data[$key] = json_decode($data[$key], true);
            }
        }

        return $data ? $data : [];

    }

    //通过品质和等级,抽取合适的阵眼
    public function lotteryEyeToQualityAndLv(int $quality, int $lv)
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if (!$table->count()) $this->initTable();

        $randList = array();
        foreach ($table as $id => $value) {
            //判断品质
            if ($quality != $value['quality']) {
                continue;
            }
            $value['level'] = json_decode($value['level'], true);
            //判断等级
            if ($lv >= $value['level'][0] && $lv <= $value['level'][1] && $value['weight'] > 0) {
                $randList[$id] = $value['weight'];
            }
        }
        //抽取阵眼
        $id = randTable($randList);

        if(!$id){
            Logger::getInstance()->waring('抽取配置：'.json_encode($randList));
            Logger::getInstance()->waring('阵眼id'.$id);
        }

        $data = $this->getOne($id);
        if(!$data){
            Logger::getInstance()->waring('阵眼id'.$id);
            Logger::getInstance()->waring('阵眼数据'.json_encode($data));
        }
        $data['id'] = $id;
        return $data;
    }

}
