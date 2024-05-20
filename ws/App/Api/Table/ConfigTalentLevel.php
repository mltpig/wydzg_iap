<?php

namespace App\Api\Table;

use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigTalentLevel as Model;

class ConfigTalentLevel
{
    use CoroutineSingleTon;

    protected $tableName = 'config_talent_level';

    public function create(): void
    {
        $columns = [
            'id' => ['type' => Table::TYPE_INT, 'size' => 20],
            'level' => ['type' => Table::TYPE_INT, 'size' => 8],
            'basic' => ['type' => Table::TYPE_STRING, 'size' => 100],
            'second' => ['type' => Table::TYPE_INT, 'size' => 8],
            'second_def' => ['type' => Table::TYPE_INT, 'size' => 8],
            'special' => ['type' => Table::TYPE_STRING, 'size' => 50],
            'basic_count' => ['type' => Table::TYPE_INT, 'size' => 8],
            'reward' => ['type' => Table::TYPE_STRING, 'size' => 50],
        ];

        TableManager::getInstance()->add($this->tableName, $columns, 500);

    }

    public function initTable(): void
    {
        $table = TableManager::getInstance()->get($this->tableName);

        $tableConfig = Model::create()->all();
        foreach ($tableConfig as $value) {
            $reward = explode(';', $value['reward']);
            $rewards = array();
            foreach ($reward as $val2) {
                $rewards[] = explode('=', $val2);
            }

            $table->set($value['level'], [
                'basic' => $value['basic'],
                'second' => $value['second'],
                'second_def' => $value['second_def'],
                'special' => $value['special'],
                'basic_count' => $value['basic_count'],
                'reward' => json_encode($rewards),
            ]);
        }

    }

    public function getOne(int $level): array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if (!$table->count()) $this->initTable();

        $data = $table->get($level);

        return $data ? [
            'basic' => $data['basic'],
            'second' => $data['second'],
            'second_def' => $data['second_def'],
            'special' => $data['special'],
            'basic_count' => $data['basic_count'],
            'reward' => json_decode($data['reward'], true),
        ] : [];

    }

}
