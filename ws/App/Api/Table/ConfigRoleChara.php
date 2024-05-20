<?php
namespace App\Api\Table;
use Swoole\Table;
use EasySwoole\Component\TableManager;
use EasySwoole\Component\CoroutineSingleTon;
use App\Api\Model\ConfigRoleChara as Model;

class ConfigRoleChara
{
    use CoroutineSingleTon;

    protected $tableName     = 'config_role_chara';
    protected $tableNameInit = 'config_role_chara_init';
    protected $tableActivity = 'config_role_chara_activity';

    public function create():void
    {
        $columns = [
            'id'                => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'create'            => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'unlock'            => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'get_type'          => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],

            // 'name'              => [ 'type'=> Table::TYPE_STRING ,'size'=> 500 ],
            // 'desc'              => [ 'type'=> Table::TYPE_STRING ,'size'=> 500 ],
            // 'icon'              => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            // 'type'              => [ 'type'=> Table::TYPE_STRING ,'size'=> 500 ],
            // 'title_type'        => [ 'type'=> Table::TYPE_STRING ,'size'=> 500 ],
            // 'get_type'          => [ 'type'=> Table::TYPE_STRING ,'size'=> 500 ],
            // 'skil'              => [ 'type'=> Table::TYPE_STRING ,'size'=> 500 ],
            // 'body_height'       => [ 'type'=> Table::TYPE_STRING ,'size'=> 500 ],
             'belong'            => [ 'type'=> Table::TYPE_STRING ,'size'=> 500 ],
            // 'quality'           => [ 'type'=> Table::TYPE_STRING ,'size'=> 500 ],
            // 'params'            => [ 'type'=> Table::TYPE_STRING ,'size'=> 500 ],
            // 'attack_type'       => [ 'type'=> Table::TYPE_STRING ,'size'=> 500 ],
            // 'attack_excursion'  => [ 'type'=> Table::TYPE_STRING ,'size'=> 500 ],
            // 'tree_romote'       => [ 'type'=> Table::TYPE_STRING ,'size'=> 500 ],
            // 'tree_excursion'    => [ 'type'=> Table::TYPE_STRING ,'size'=> 500 ],
            // 'formation_excursion'  => [ 'type'=> Table::TYPE_STRING ,'size'=> 500 ],
        ];

        TableManager::getInstance()->add( $this->tableName , $columns , 100 );

        $columns = [ 'id' => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ] ,
                    'belong'   => [ 'type'=> Table::TYPE_STRING ,'size'=> 500 ]
        ];
        TableManager::getInstance()->add( $this->tableNameInit , $columns , 1 );

        $columns = [ 
            'id'              => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'get_type'        => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'cost_id'         => [ 'type'=> Table::TYPE_STRING ,'size'=> 50 ],
            'skill'           => [ 'type'=> Table::TYPE_INT ,'size'=> 8 ],
            'belong'          => [ 'type'=> Table::TYPE_STRING ,'size'=> 500 ],
        ];
        TableManager::getInstance()->add( $this->tableActivity , $columns , 10 );

    }

    public function initTable():void
    {
        $table         = TableManager::getInstance()->get($this->tableName);
        $tableInit     = TableManager::getInstance()->get($this->tableNameInit);
        $tableActivity = TableManager::getInstance()->get($this->tableActivity);

        $tableConfig = Model::create()->all();
        
        foreach ($tableConfig as $key => $value) 
        {
            
            if($value['get_type'] == 2)
            {
                $cost = [];
                if($value['cost_id'])
                {
                    list($lock,$step) = explode('|',$value['cost_id']);
                    list($gid,$num) = explode('=',$lock);
                    $cost = ['gid' => $gid,'num' => $num ,'step' => $step ];
                }

                $tableActivity->set($value['id'],[
                    'id'              => $value['id'],
                    'get_type'        => $value['get_type'],
                    'cost_id'         => json_encode($cost),
                    'skill'           => $value['skill'],
                ]);
            }else{
                
                $table->set($value['unlock'],[ 'id'     => $value['id'] ]);
                if(!$value['create']) continue;
                $tableInit->set('init',[ 'id'     => $value['id'],'belong'     => $value['belong'] ]);

            }

        }
    }


    public function getOne(int $type):array
    {
        $table = TableManager::getInstance()->get($this->tableName);
        if(!$table->count()) $this->initTable();
        
        $data = $table->get($type);

        return $data ? $data : [];

    }

    public function getIntHead():array
    {
        $table = TableManager::getInstance()->get($this->tableNameInit);
        if(!$table->count()) $this->initTable();

        $data = $table->get('init');

        return $data ;
    }

    public function getActivityAll():array
    {
        $table = TableManager::getInstance()->get($this->tableActivity);
        if(!$table->count()) $this->initTable();
        
        $list  = [];
        foreach ($table as $id => $config) 
        {
            $list[ $id ] = [
                'id'              => $config['id'],
                'get_type'        => $config['get_type'],
                'cost_id'         => json_decode($config['cost_id'],true),
                'skill'           => $config['skill'],
            ];
        }

        return $list;

    }

    public function getActivityOne(int $id):array
    {
        $table = TableManager::getInstance()->get($this->tableActivity);
        if(!$table->count()) $this->initTable();
        
        $data = $table->get($id);

        return $data ? [
            'id'              => $data['id'],
            'get_type'        => $data['get_type'],
            'cost_id'         => json_decode($data['cost_id'],true),
            'skill'           => $data['skill'],
        ] : [];

    }
}
