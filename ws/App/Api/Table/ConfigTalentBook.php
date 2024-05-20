<?php

namespace App\Api\Table;

use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigTalentBook as Model;

class ConfigTalentBook
{
    use CoroutineSingleTon;

    protected $tableName = 'config_talent_book';

    public function create(): void
    {
        $columns = [
            'id' => ['type' => Table::TYPE_INT, 'size' => 20],
            'weight' => ['type' => Table::TYPE_STRING, 'size' => 100],
        ];

        TableManager::getInstance()->add($this->tableName, $columns, 40000);
    }

    public function initTable(): void
    {
        $table = TableManager::getInstance()->get($this->tableName);

        $tableConfig = Model::create()->all();
        foreach ($tableConfig as $value) {
            $weights = explode('|', $value['weight']);
            $table->set($value['id'], [
                'weight' => json_encode($weights),
            ]);
        }

    }

    public function getOne(int $level): array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if (!$table->count()) $this->initTable();

        $data = $table->get($level);

        return $data ? [
            'weight' => json_decode($data['weight'], true),
        ] : [];

    }

}
