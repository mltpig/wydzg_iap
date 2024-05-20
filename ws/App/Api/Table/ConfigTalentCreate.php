<?php

namespace App\Api\Table;

use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigTalentCreate as Model;

class ConfigTalentCreate
{
    use CoroutineSingleTon;

    protected $tableName = 'config_talent_create';

    public function create(): void
    {
        $columns = [
            'id' => ['type' => Table::TYPE_INT, 'size' => 8],
            'talent_level' => ['type' => Table::TYPE_STRING, 'size' => 100],
            'exp' => ['type' => Table::TYPE_INT, 'size' => 8],
        ];

        TableManager::getInstance()->add($this->tableName, $columns, 200);

    }

    public function initTable(): void
    {
        $table = TableManager::getInstance()->get($this->tableName);

        $tableConfig = Model::create()->all();
        foreach ($tableConfig as $value) {
            $talentLevel = explode('|', $value['talent_level']);
            $talentLevels = array();
            foreach ($talentLevel as $val2) {
                $talentLevels[] = explode(';', $val2);
            }
            $table->set($value['id'], [
                'talent_level' => json_encode($talentLevels),
                'exp' => $value['exp'],
            ]);
        }

    }

    public function getOne(int $level): array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if (!$table->count()) $this->initTable();

        $data = $table->get($level);

        return $data ? [
            'talent_level' => json_decode($data['talent_level'], true),
            'exp' => $data['exp'],
        ] : [];

    }

}
