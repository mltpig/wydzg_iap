<?php
namespace App\Api\Table;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigSystemInfo as Model;

class ConfigSystemInfo
{
    use CoroutineSingleTon;

    protected $tableName = 'config_system_info';

    public function create():void
    {
        $columns = [
            'name'                  => [ 'type'=> Table::TYPE_STRING ,'size'=> 64 ],
            'desc'                  => [ 'type'=> Table::TYPE_STRING ,'size'=> 128 ],
            'icon'                  => [ 'type'=> Table::TYPE_STRING ,'size'=> 64 ],
            'sort'                  => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'is_show'               => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'condition_type'        => [ 'type'=> Table::TYPE_STRING ,'size'=> 64 ],
            'value'                 => [ 'type'=> Table::TYPE_STRING ,'size'=> 64 ],
            'reward'                => [ 'type'=> Table::TYPE_STRING ,'size'=> 128 ],
            'notice'                => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'collect'               => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'destination'           => [ 'type'=> Table::TYPE_STRING ,'size'=> 64 ],
            'is_show_tow'           => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
        ];

        TableManager::getInstance()->add( $this->tableName , $columns , 5000 );

    }

    public function initTable():void
    {
        $table = TableManager::getInstance()->get($this->tableName);

        $tableConfig = Model::create()->all();
        foreach ($tableConfig as $key => $value) 
        {
            $table->set($value['id'],[
                'name'               => $value['name'],
                'desc'               => $value['desc'],
                'icon'               => $value['icon'],
                'sort'               => $value['sort'],
                'is_show'            => $value['is_show'],
                'condition_type'     => $value['condition_type'],
                'value'              => $value['value'],
                'reward'             => $value['reward'],
                'notice'             => $value['notice'],
                'collect'            => $value['collect'],
                'destination'        => $value['destination'],
                'is_show_tow'        => $value['is_show_tow'],
            ]);
        }

    }

    public function getAll():array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();

        $list  = [];
        foreach ($table as $id => $config)
        {
            $list[ $id ] = [
                'name'                  => $config['name'],
                'desc'                  => $config['desc'],
                'icon'                  => $config['icon'],
                'sort'                  => $config['sort'],
                'is_show'               => $config['is_show'],
                'condition_type'        => $config['condition_type'],
                'value'                 => $config['value'],
                'reward'                => $config['reward'],
                'notice'                => $config['notice'],
                'collect'               => $config['collect'],
                'destination'           => $config['destination'],
                'is_show_tow'           => $config['is_show_tow'],
            ];
        }
        return $list;
    }

    public function getOne(int $id):array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();
        
        $data = $table->get($id);

        return $data ? [
            'name'              => $data['name'],
            'desc'              => $data['desc'],
            'icon'              => $data['icon'],
            'sort'              => $data['sort'],
            'is_show'           => $data['is_show'],
            'condition_type'    => $data['condition_type'],
            'value'             => $data['value'],
            'reward'            => $data['reward'],
            'notice'            => $data['notice'],
            'collect'           => $data['collect'],
            'destination'       => $data['destination'],
            'is_show_tow'       => $data['is_show_tow'],
        ] : [];

    }

}
