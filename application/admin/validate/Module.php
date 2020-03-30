<?php
/**
 * 管理员验证
 */
namespace app\admin\validate;
use think\Validate;
class Module extends Validate{
    //验证规则
	protected $rule =   [
		'moduleid|模型编号'			=> 'require|number|min:1|unique:module',
		'modulename|模型名称'		=> 'require|chs',
		'moduleapp|模型控制器名'	=> 'require|alpha',
		'uploadcatalog|上传目录'	=> 'require|alpha',
    ];
}