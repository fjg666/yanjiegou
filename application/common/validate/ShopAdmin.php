<?php
namespace app\common\validate;
use think\Validate;
class ShopAdmin extends Validate
{
     protected $rule =   [
        'username'  => 'require|length:6,20|unique:bigshopAdmin|alphaNum',
        'pwd' => 'require|alphaNum|length:6,20',
    ];
    protected $message  =   [
        'username.require'      => '管理员名称不能为空',
        'username.length'       => '管理员名称在6到20个字符之间',
        'username.unique'       => '管理员名称已存在',
        'username.alpha'  => '管理员名称只能是字母数字',
        'pwd.require'       => '管理员密码不能为空',
        'pwd.alphaNum'      => '管理员密码只能用数字和密码',
        'pwd.length'       => '密码在6到20个字符之间',
    ];
}