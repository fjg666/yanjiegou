<?php
return [
    //通用配置
    'check'	=> [
		'1'		=> ['status'=>0,'statusname'=>'待审核','color'=>'orange'],
		'3'		=> ['status'=>1,'statusname'=>'未通过','color'=>'red'],
		'2'		=> ['status'=>2,'statusname'=>'审核通过','color'=>'green'],
	],
	'je'	=> [
		'0'		=> ['status'=>0,'statusname'=>'待审核','color'=>'orange'],
		'1'		=> ['status'=>1,'statusname'=>'未通过','color'=>'red'],
		'2'		=> ['status'=>2,'statusname'=>'审核通过','color'=>'green'],
	],
	'status'	=> [
		'0'		=> ['status'=>0,'statusname'=>'未启用','color'=>'red'],
		'1'		=> ['status'=>9,'statusname'=>'已启用','color'=>'green'],
	],
	'gender'  =>  [
        '0'  =>  ['status'=>0,'statusname'=>'女','color'=>'#FF5722'],
        '1'  =>  ['status'=>1,'statusname'=>'男','color'=>'#009688'],
    ],

	'sys_name'  => '沿街购管理系统',
    //商超相关配置
	'bigshop_type'=>[
		'1'		=> ['status'=>0,'statusname'=>'商超','color'=>'red'],
		'2'		=> ['status'=>1,'statusname'=>'步行街','color'=>'red'],
	],
	'bigshop_status'=>[
		'1'		=> ['status'=>1,'statusname'=>'待入驻','color'=>'red'],
		'2'		=> ['status'=>2,'statusname'=>'入驻中','color'=>'red'],
		'3'		=> ['status'=>3,'statusname'=>'暂停中','color'=>'red'],
		'4'		=> ['status'=>4,'statusname'=>'即将到期','color'=>'red'],
	],
	//订单相关配置
	'pay_type'=>[
	    '1'		=> ['status'=>1,'statusname'=>'支付宝','color'=>'red'],
		'2'		=> ['status'=>2,'statusname'=>'微信','color'=>'red'],
		'3'		=> ['status'=>3,'statusname'=>'银联付款','color'=>'red'],
		'4'		=> ['status'=>4,'statusname'=>'货到付款','color'=>'red'],
		'5'		=> ['status'=>5,'statusname'=>'余额付款','color'=>'red'],
		'6'	    => ['status'=>6,'statusname'=>'后台付款','color'=>'red'],
	],
	'delivery_type'=>[
	    '1'		=> ['status'=>1,'statusname'=>'普通快递','color'=>'red'],
		'2'		=> ['status'=>2,'statusname'=>'同城配送','color'=>'red'],
		'3'		=> ['status'=>3,'statusname'=>'自提','color'=>'red'],
		'4'		=> ['status'=>4,'statusname'=>'平台配送','color'=>'red'],
	],
	'express_company'=>[
	    'shentong'		=> ['status'=>'shentong','statusname'=>'申通','color'=>'red'],
		'zhongtong'		=> ['status'=>'zhongtong','statusname'=>'中通','color'=>'red'],
		'yuantong'		=> ['status'=>'yuantong','statusname'=>'圆通','color'=>'red'],
		'shunfeng'		=> ['status'=>'shunfeng','statusname'=>'顺丰','color'=>'red'],
		'youzhengguonei'		=> ['status'=>'youzhengguonei','statusname'=>'邮政','color'=>'red'],
		'yunda'		=> ['status'=>'yunda','statusname'=>'韵达','color'=>'red'],
		'zhaijisong'		=> ['status'=>'zhaijisong','statusname'=>'宅急送','color'=>'red'],
		'tiantian'		=> ['status'=>'tiantian','statusname'=>'天天','color'=>'red'],
		'huitongkuaidi'		=> ['status'=>'huitongkuaidi','statusname'=>'百世汇通','color'=>'red'],
		'debangwuliu'		=> ['status'=>'debangwuliu','statusname'=>'德邦','color'=>'red'],
		'baishiwuliu'		=> ['status'=>'baishiwuliu','statusname'=>'百世物流','color'=>'red'],
		'youshuwuliu'		=> ['status'=>'youshuwuliu','statusname'=>'优速','color'=>'red'],
		'jd'		=> ['status'=>'jd','statusname'=>'京东','color'=>'red'],
		'ems'		=> ['status'=>'ems','statusname'=>'EMS','color'=>'red'],
	],
	'order_status'=>[
	    '1'		=> ['status'=>1,'statusname'=>'待付款','color'=>'red'], 
		'2'		=> ['status'=>2,'statusname'=>'待发货','color'=>'red'], 
		'3'		=> ['status'=>3,'statusname'=>'已发货','color'=>'red'],  //待收货
		'4'		=> ['status'=>4,'statusname'=>'待评价','color'=>'red'],  
		'5'		=> ['status'=>5,'statusname'=>'已完成','color'=>'red'],
		'6'	    => ['status'=>6,'statusname'=>'已关闭','color'=>'red'],
		'7'	    => ['status'=>7,'statusname'=>'售后','color'=>'red'],
	],
	//商品相关配置
	'goods_status'=>[
	    '0'		=> ['status'=>0,'statusname'=>'下架','color'=>'red'],
		'1'		=> ['status'=>1,'statusname'=>'上架','color'=>'green'],
	],
	'goods_check_status'=>[
	    '-1'		=> ['status'=>-1,'statusname'=>'违规','color'=>'red'],
		'0'		=> ['status'=>1,'statusname'=>'未审核','color'=>'orange'],
		'1'		=> ['status'=>9,'statusname'=>'已审核','color'=>'green'],
	],
	'goods_type' =>[
	    '1'		=> ['status'=>1,'statusname'=>'商圈商家','color'=>'red'],
		'2'		=> ['status'=>2,'statusname'=>'个体户','color'=>'green'],
	],
	//文章相关配置
	'article_status'	=> [
		'-1'		=> ['status'=>-1,'statusname'=>'违规','color'=>'red'],
		'0'		=> ['status'=>1,'statusname'=>'未审核','color'=>'red'],
		'1'		=> ['status'=>9,'statusname'=>'已审核','color'=>'green'],
	],
	//会员卡状态
    'userscard_status'	=> [
		'0'		=> ['status'=>0,'statusname'=>'停发','color'=>'red'],
		'1'		=> ['status'=>1,'statusname'=>'发放','color'=>'green'],
	],

	//商家资金流动类型
    'shop_fund_log_type'	=> [
		'0'		=> ['status'=>0,'statusname'=>'收入','color'=>'red'],
		'1'		=> ['status'=>1,'statusname'=>'提现','color'=>'green'],
	],

	//商家资金流动类型
    'shop_is_lock'	=> [
		'1'		=> ['status'=>0,'statusname'=>'禁用','color'=>'red'],
		'0'		=> ['status'=>1,'statusname'=>'启用','color'=>'green'],
	],
	
	//优惠券有效期
    'coupon_is_expire'	=> [
		'0'		=> ['status'=>0,'statusname'=>'有效','color'=>'green'],
		'1'		=> ['status'=>1,'statusname'=>'无效','color'=>'red'],
	],

	//优惠券类型
    'coupon_type'	=> [
		'1'		=> ['status'=>1,'statusname'=>'平台','color'=>'red'],
		'2'		=> ['status'=>2,'statusname'=>'商家','color'=>'green'],
	],

	'shop_type' => [
		'1'		=> ['status'=>1,'statusname'=>'商圈商家','color'=>'red'],
		'2'		=> ['status'=>2,'statusname'=>'个体商户','color'=>'green'],
	],
];