<?php
namespace app\shop\validate;

use think\Validate;

class Users extends Validate
{
    protected $rule =   [
        'username'  => 'require|length:3,25',
    ];
    protected $message  =   [
        'username.require'      => '用户名不能为空',
        'username.length'       => '用户名在3到25个字符之间',
    ];
}