<?php
namespace app\common\validate;
use think\Validate;
class Goods extends Validate
{
    protected $rule =   [
        'title'  => 'require',
        'keywords' => 'require|chsAlphaNum',
        'catid'=>'require',
        'price'=>'require|float',
        'original_price'=>'require|float',
        'cost_price'=>'require|float',
        'headimg'=>'require',
        'content'=>'require',
    ];
    protected $message  =   [
        'title.require'      => '商品名称不能为空',
        'keywords.require'  => '关键字不能为空',
        'keywords.chsAlphaNum'  => '关键字只能是汉字、字母和数字',
        'catid.require'     => '商品分类不能为空',
        'price.require'     => '售价不能为空',
        'price.float'       => '售价无效',
        'original_price.require' => '原价不能为空',
        'original_price.float'   => '原价无效',
        'cost_price.require' => '成本价不能为空',
        'cost_price.float'   => '成本价无效',
        'headimg.require'     => '商品图片不能为空',
        'content.require'     => '商品详情不能为空',
    ];
}