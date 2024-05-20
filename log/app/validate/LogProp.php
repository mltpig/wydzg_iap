<?php
namespace app\validate;
use think\Validate;

class LogProp extends Validate
{
    protected $rule =   [
        'pagenum'       => 'require|integer|egt:0',
        'pagesize'      => 'require|integer|in:10,20,30',
        'site'          => 'require|integer|egt:0',   
        'type'          => 'require|integer|between:0,2', 
        'startTime'     => 'require|dateFormat:Y-m-d H:i:s',
        'endTime'       => 'require|dateFormat:Y-m-d H:i:s',
        'table'         => 'require|in:log_prop,log_prop_ysjdftz',
        
    ];

    protected $message  =   [
        'pagenum.require' => '页码不可为空',
        'pagenum.integer' => '页码必须为整数',
        'pagenum.egt'     => '页码必须大于等于0',

        'pagesize.require' => '展示条数不可为空',
        'pagesize.integer' => '页码必须为整数',
        'pagesize.between' => '展示条数最少为1、最大为30',

        'site.require'      => '无效的SITE',
        'site.integer'      => '无效的SITE',
        'site.egt'          => '无效的SITE',

        'type.require'    => '标识类型不可为空',
        'type.integer'    => '标识类型必须为整数',
        'type.between'    => '标识范围必须在0-2区间',

        'startTime.require'    => '开始时间不可为空',
        'startTime.dateFormat' => '开始时间格式错误',

        'endTime.require'      => '有效截止时间不可为空',
        'endTime.dateFormat'   => '有效截止时间格式错误',

        'table.require'      => '表名必须',
        'table.in'           => '无效表名',

    ];

    protected $scene = [
        'get'   =>  [ 'pagenum','pagesize','type','startTime','endTime','table' ],
    ]; 
    
}
