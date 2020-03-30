<?php
namespace app\common\validate;
use think\Validate;
class Bigshop extends Validate
{
    protected $rule =   [
        'name'  => 'require|length:3,200|unique:bigshop|chsAlphaNum',
        'bigshoplogo' => 'require',
        'content'=>'require',
        'type' => 'require|in:0,1',
        'province' =>'require|chs',
        'city'=>'require|chs',
        'area'=>'require|chs',
        'street'=>'require|chs',
        'address'=>'require|chsAlphaNum',
        'longitude'=>'require|float',
        'latitude'=>'require|float',
        'phone'=>'require|number',
        'do_business_time'=>'require',
        'pastdue'=>'require|dateFormat:Y-m-d',
        'headimg'=>'require',
    ];
    protected $message  =   [
        'name.require'      => '商圈名称不能为空',
        'name.length'       => '商圈名称在3到200个字符之间',
        'name.unique'       => '商圈名称已存在',
        'name.chsAlphaNum'  => '商圈名称只能是汉字、字母和数字',
        'bigshoplogo.require' => '商圈logo不能为空',
        'content.require'   => '详情介绍不能为空',
        'type.require'      => '商圈类型不能为空',
        'type.in'           => '商圈类型不在有效范围内',
        'province.require'  => '省不能为空',
        'province.chs'      => '省只能用汉字',
        'city.require'      => '市不能为空',
        'city.chs'          => '省只能用汉字',
        'area.require'      => '区不能为空',
        'area.chs'          => '区只能用汉字',
        'street.require'    => '街道不能为空',
        'street.chs'        => '街道只能用汉字',
        'address.require'   => '地址不能为空',
        'address.chsAlphaNum' => '地址只能是汉字字母或数字',
        'longitude.require' => '经度不能为空',
        'longitude.float'   => '经度无效',
        'latitude.require'  => '维度不能为空',
        'latitude.float'    => '维度无效',
        'phone.require'       => '电话不能为空',
        'phone.number'        => '电话无效',
        'do_business_time.require'=> '营业时间不能为空',
        'pastdue.require'   => '服务到期时间不能为空',
        'pastdue.dateFormat'=> '服务到期时间无效',
        'headimg.require'   => '相册不能为空',
    ];
}