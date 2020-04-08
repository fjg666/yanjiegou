<?php
namespace app\api\controller;
use think\Db;
use think\Request;
use think\Config;
class Order extends Base
{
    ////确认订单第一步，还没有生成订单
    public function index()
    {
        $user_id = input('post.user_id');
        if(null===$user_id){
            $this->json_error('请传过来用户编号');
        }
        $cart_id = input('post.cart_id');
        if(null===$cart_id){
            $this->json_error('请传过来购物车编号');
        }

        $carts = Db::name('shopcart')->alias('c')
            ->join('goods g','g.id=c.goods_id','LEFT')
            ->whereIn('c.id',$cart_id)
            ->where(['user_id'=>$user_id])
            ->field('c.*,g.shopid,g.headimg,g.title,g.price')
            ->select();
        $shop_ids = array_unique(array_column($carts,'shopid'));
        $shop_ids = implode(',',$shop_ids);
        //获取当前用户领取的商家优惠券
        // $coupons = Db::name('couponlog')->alias('clog')
        //     ->join('coupon c','c.id=clog.coupon_id')
        //     ->where(['clog.user_id'=>$user_id,'clog.is_use'=>0,'c.type_id'=>2,'c.is_expire'=>0])
        //     ->field('clog.id as clogid,clog.user_id,clog.is_use,clog.receive_time,c.*')
        //     ->order('clog.id desc')
        //     ->select();
        // $time = time();
        // foreach($coupons as $cck=>$ccv){
        //     if($time>$ccv['end_time']){
        //         //说明过期了
        //         Db::name('coupon')->where(['id'=>$ccv['id']])->update(['is_expire'=>1]);
        //     }
        // }
    
        $shops = Db::name('shop')->where('id','in',$shop_ids)->field('id,name,shoplogo,province,city,area')->select();
        $data = [];
        $order_goods = [];
        $mycoupons = [];
        //商家是否允许平台使用券
        $is_public = 0;
        foreach($shops as $k=>$v){
            $shops[$k]['shoplogo'] = $this->domain().$v['shoplogo'];
            $cart_ids = [];
            $totalnum = 0;
            $totalprice = 0;
            foreach($carts as $ck=>$cv){
                if($v['id']==$cv['shopid']){
                    array_push($cart_ids,$cv['id']);
                    $data['id'] = $cv['id'];
                    $data['goods_id'] = $cv['goods_id'];
                    $data['num'] = $cv['num'];
                    $totalnum+=$cv['num'];
                    $data['goods_attr'] = json_decode($cv['goods_attr'],true);
                    $data['goods_sku'] = $cv['sku_id'];
                    /*---chen*/
                    $data['goods_attr_val'] = '';
                    if (!empty($data['goods_attr'])) {
                        foreach ($data['goods_attr'] as $ks=>$vs){
                            $SttrName=Db::name('GoodsSttr')->where('id',$ks)->value('key');
                            $SttrValName=Db::name('GoodsSttrval')->where('id',$vs)->value('sttr_value');
                            $data['goods_attr_val'] .=  $SttrName.':'.$SttrValName.' ';
                        }
                    }
                    /*---chen*/
                    $data['title'] = $cv['title'];
                    $data['price'] = ($cv['sku_id']==0)?$cv['price']:(Db::name('GoodsSttrxsku')->where('id',$cv['sku_id'])->value('money'));
                    // $totalprice+=($cv['num']*$cv['price']);
                    $totalprice+=($cv['num']*$data['price']);
                    $headimgs = explode(',',$cv['headimg']);
                    $data['headimg'] = $this->domain().$headimgs[0];
                    $shops[$k]['goods'][] = $data;
                }
            }
            $shops[$k]['cart_id'] = implode(',',$cart_ids);
            $shops[$k]['remark_member'] = "";
            $shops[$k]['totalnum'] = $totalnum;
            $shops[$k]['totalprice'] = $totalprice;
/*---chen*/
// 可用——商家优惠券
            $shop_coupon = Db::name('couponlog')
                    ->alias('clog')
                    ->join('coupon c','c.id = clog.coupon_id')
                    ->field('clog.id as clogid,clog.user_id,clog.receive_time,c.*')
                    ->where('clog.user_id', $user_id)
                    ->where('clog.is_use', 0)
                    ->where('c.type_id', 2)
                    ->where('c.is_expire',0)
                    ->where('c.shop_id',$v['id'])
                    ->select();
// 该商店花费总金额
            $all_money = empty($data)?0:array_sum(array_column($shops,'totalprice'));
            $can_coupon = [];
            $shops[$k]['coupon_clogid'] = '';
            $shops[$k]['coupon_price'] = '';
            $shops[$k]['coupon_name'] = '';
            $shops[$k]['is_public'] = 1;
            if ($shop_coupon) {
                foreach ($shop_coupon as $scKey => $scValue) {
                    if(time() > $scValue['end_time']){
                        // 标记过期
                        Db::name('coupon')->where(['id'=>$scValue['id']])->update(['is_expire'=>1]);
                        unset($shop_couponp[$scKey]);
                    }
                    // 计算用哪一张优惠卷
                    if ($scValue['min_price'] <= $all_money) {
                        $can_coupon[] = [
                            'clogid'    =>  $scValue['clogid'],
                            'sub_price' =>  $scValue['sub_price'],
                            'name'  =>  $scValue['name'],
                            'is_public' =>  $scValue['is_public'],
                        ];
                    }
                }
    // 使用优惠券的id 还有减去金额 --加入这个shop
                if (!empty($can_coupon)) {
                  	array_multisort(array_column($can_coupon,'sub_price'),SORT_DESC,$can_coupon);
                    $shops[$k]['coupon_clogid'] = $can_coupon[0]['clogid'];
                    $shops[$k]['coupon_price'] = $can_coupon[0]['sub_price'];
                    $shops[$k]['coupon_name'] = $can_coupon[0]['name'];
                    $shops[$k]['is_public'] = $can_coupon[0]['is_public'];
                    if ($is_public == 0) {
                        $is_public = $can_coupon[0]['is_public'];
                    }
                }
            }
            // foreach($coupons as $kk=>$vv){
            //     if($v['id']==$vv['shop_id']){
            //         if($vv['min_price']<$shops[$k]['totalprice']){
            //             $mycoupons['clogid'] = $vv['clogid'];
            //             $mycoupons['user_id'] = $vv['user_id'];
            //             $mycoupons['is_use'] = $vv['is_use'];
            //             $mycoupons['receive_time'] = date('Y-m-d H:i:s',$vv['receive_time']);
            //             $mycoupons['id'] = $vv['id'];
            //             $mycoupons['type_id'] = $vv['type_id'];
            //             $mycoupons['name'] = $vv['name'];
            //             $mycoupons['min_price'] = $vv['min_price'];
            //             $mycoupons['sub_price'] = $vv['sub_price'];
            //             $mycoupons['begin_time'] = date('Y-m-d H:i:s',$vv['begin_time']);
            //             $mycoupons['end_time'] = date('Y-m-d H:i:s',$vv['end_time']);
            //             $mycoupons['add_time'] = date('Y-m-d H:i:s',$vv['add_time']);
            //             $mycoupons['total_count'] = $vv['total_count'];
            //             $mycoupons['sort'] = $vv['sort'];
            //             $mycoupons['is_expire'] = $vv['is_expire'];
            //             $mycoupons['shop_id'] = $vv['shop_id'];
            //             $mycoupons['shop_name'] = $vv['shop_name'];
            //             $mycoupons['special'] = $vv['special'];
            //             $shops[$k]['coupons'][] = $mycoupons;
            //         }
            //     }
            // }
/*---chen*/
        }
// 平台优惠券计算：
        $coupon_one = [];
        $coupon_arr = [];
        $ping_coupon = Db::name('couponlog')
                    ->alias('clog')
                    ->join('coupon c','c.id = clog.coupon_id')
                    ->field('clog.id as clogid,clog.user_id,clog.receive_time,c.*')
                    ->where('clog.user_id', $user_id)
                    ->where('clog.is_use', 0)
                    ->where('c.type_id', 1)
                    ->where('c.is_expire',0)
                    ->select();
        // 有1 说明有商家接受平台券
        
        if ($is_public == 1) {
            if ($ping_coupon) {
                $shopall_money = empty($shops)?0:array_sum(array_column($shops,'totalprice'));
                foreach ($ping_coupon as $pcKey => $pcValue) {
                    if(time() > $pcValue['end_time']){
                        // 标记过期
                        Db::name('coupon')->where(['id'=>$pcValue['id']])->update(['is_expire'=>1]);
                        unset($ping_coupon[$pcKey]);
                    }
                    // 计算用哪一张优惠卷
                    if ($pcValue['min_price'] <= $shopall_money) {
                        $coupon_arr[] = [
                            'clogid'    =>  $pcValue['clogid'],
                            'sub_price' =>  $pcValue['sub_price'],
                            'name'      =>  $pcValue['name'],
                            'min_price' =>  $pcValue['min_price']
                        ];
                    }
                }
            }
            // 使用优惠券的id 还有减去金额 --加入这个shop
            if (!empty($coupon_arr)) {
                array_multisort(array_column($coupon_arr,'sub_price'),SORT_DESC,$coupon_arr);
                $coupon_one['clogid'] = $coupon_arr[0]['clogid'];
                $coupon_one['price'] = $coupon_arr[0]['sub_price'];
                $coupon_one['name'] = $coupon_arr[0]['name'];
                $coupon_one['min_price'] = $coupon_arr[0]['min_price'];
            }
        }
        
        //查看当前用户是否有默认的收货地址
        $recvaddr = Db::name('recvaddr')->where(['user_id'=>$user_id,'is_delete'=>0])->field('id,consignee,phone,province,city,area,address')->find();
        if(null===$recvaddr){
            $myinfo['shop'] = $shops;
            $this->json_success($myinfo,'您还没有设置收货地址',-1);
            die;
        }

       if (!empty($shops) && count($shops) == 1) {
            $shopaddr = Db::name('shop')->where(['id'=>$shops[0]['id']])->field('id,province,city,area,address,phone')->find();
        }else{
            $shopaddr = [];
        }
        $info = [
            'recvaddr'=>$recvaddr,
            'shop'=>$shops,
            'shopaddr'=>$shopaddr,
            'ping_coupon'=>$coupon_one,
            'origin_id' => $this->randCode(),
        ];
        $this->json_success($info);
    }
// public function index()
//     {
//         $user_id = input('post.user_id');
//         if(null===$user_id){
//             $this->json_error('请传过来用户编号');
//         }
//         $cart_id = input('post.cart_id');
//         if(null===$cart_id){
//             $this->json_error('请传过来购物车编号');
//         }
//         $carts = Db::name('shopcart')->alias('c')
//             ->join('goods g','g.id=c.goods_id','LEFT')
//             ->whereIn('c.id',$cart_id)
//             ->where(['user_id'=>$user_id])
//             ->field('c.*,g.shopid,g.headimg,g.title,g.price')
//             ->select();
//         $shop_ids = array_unique(array_column($carts,'shopid'));
//         $shop_ids = implode(',',$shop_ids);
//         //获取当前用户领取的商家优惠券
//         $coupons = Db::name('couponlog')->alias('clog')
//             ->join('coupon c','c.id=clog.coupon_id')
//             ->where(['clog.user_id'=>$user_id,'clog.is_use'=>0,'c.type_id'=>2,'c.is_expire'=>0])
//             ->field('clog.id as clogid,clog.user_id,clog.is_use,clog.receive_time,c.*')
//             ->order('clog.id desc')
//             ->select();
//         $time = time();
//         foreach($coupons as $cck=>$ccv){
//             if($time>$ccv['end_time']){
//                 //说明过期了
//                 Db::name('coupon')->where(['id'=>$ccv['id']])->update(['is_expire'=>1]);
//             }
//         }
//         $shops = Db::name('shop')->where('id','in',$shop_ids)->field('id,name,shoplogo')->select();
//         $data = [];
//         $order_goods = [];
//         $mycoupons = [];
//         foreach($shops as $k=>$v){
//             $shops[$k]['shoplogo'] = $this->domain().$v['shoplogo'];
//             $cart_ids = [];
//             $totalnum = 0;
//             $totalprice = 0;
//             foreach($carts as $ck=>$cv){
//                 if($v['id']==$cv['shopid']){
//                     array_push($cart_ids,$cv['id']);
//                     $data['id'] = $cv['id'];
//                     $data['goods_id'] = $cv['goods_id'];
//                     $data['num'] = $cv['num'];
//                     $totalnum+=$cv['num'];
//                     $data['goods_attr'] = json_decode($cv['goods_attr'],true);
//                     $data['goods_sku'] = $cv['sku_id'];
//                     /*---chen*/
//                     $data['goods_attr_val'] = '';
//                     if (!empty($data['goods_attr'])) {
//                         foreach ($data['goods_attr'] as $ks=>$vs){
//                             $SttrName=Db::name('GoodsSttr')->where('id',$ks)->value('key');
//                             $SttrValName=Db::name('GoodsSttrval')->where('id',$vs)->value('sttr_value');
//                             $data['goods_attr_val'] .=  $SttrName.':'.$SttrValName.' ';
//                         }
//                     }
//                     /*---chen*/
//                     $data['title'] = $cv['title'];
//                     $data['price'] = ($cv['sku_id']==0)?$cv['price']:(Db::name('GoodsSttrxsku')->where('id',$cv['sku_id'])->value('money'));
//                     // $totalprice+=($cv['num']*$cv['price']);
//                     $totalprice+=($cv['num']*$data['price']);
//                     $headimgs = explode(',',$cv['headimg']);
//                     $data['headimg'] = $this->domain().$headimgs[0];
//                     $shops[$k]['goods'][] = $data;
//                 }
//             }
//             $shops[$k]['cart_id'] = implode(',',$cart_ids);
//             $shops[$k]['remark_member'] = "";
//             $shops[$k]['totalnum'] = $totalnum;
//             $shops[$k]['totalprice'] = $totalprice;
//             foreach($coupons as $kk=>$vv){
//                 if($v['id']==$vv['shop_id']){
//                     if($vv['min_price']<$shops[$k]['totalprice']){
//                         $mycoupons['clogid'] = $vv['clogid'];
//                         $mycoupons['user_id'] = $vv['user_id'];
//                         $mycoupons['is_use'] = $vv['is_use'];
//                         $mycoupons['receive_time'] = date('Y-m-d H:i:s',$vv['receive_time']);
//                         $mycoupons['id'] = $vv['id'];
//                         $mycoupons['type_id'] = $vv['type_id'];
//                         $mycoupons['name'] = $vv['name'];
//                         $mycoupons['min_price'] = $vv['min_price'];
//                         $mycoupons['sub_price'] = $vv['sub_price'];
//                         $mycoupons['begin_time'] = date('Y-m-d H:i:s',$vv['begin_time']);
//                         $mycoupons['end_time'] = date('Y-m-d H:i:s',$vv['end_time']);
//                         $mycoupons['add_time'] = date('Y-m-d H:i:s',$vv['add_time']);
//                         $mycoupons['total_count'] = $vv['total_count'];
//                         $mycoupons['sort'] = $vv['sort'];
//                         $mycoupons['is_expire'] = $vv['is_expire'];
//                         $mycoupons['shop_id'] = $vv['shop_id'];
//                         $mycoupons['shop_name'] = $vv['shop_name'];
//                         $mycoupons['special'] = $vv['special'];
//                         $shops[$k]['coupons'][] = $mycoupons;
//                     }
//                 }
//             }
//         }
//         //查看当前用户是否有默认的收货地址
//         $recvaddr = Db::name('recvaddr')->where(['user_id'=>$user_id,'is_delete'=>0])->field('consignee,phone,province,city,area,address')->find();
//         if(null===$recvaddr){
//             $myinfo['shop'] = $shops;
//             $this->json_success($myinfo,'您还没有设置收货地址',-1);
//             die;
//         }
//         $info = [
//             'recvaddr'=>$recvaddr,
//             'shop'=>$shops
//         ];
//         $this->json_success($info);
//     }
    //立即购买
    public function ordernow()
    {
        $user_id = input('post.user_id');
        if(null===$user_id){
            $this->json_error('请传过来用户编号');
        }
        //pay_type支付方式   1支付宝  2微信  3银联
        $pay_type = input('post.pay_type');
        if(null===$pay_type){
            $this->json_error('请传过来支付方式');
        }
        $ptype = [1,2,3];
        if(!in_array($pay_type,$ptype)){
            $this->json_error('支付方式有问题');
        }
        //查看当前用户是否有默认的收货地址
        $recvaddr = Db::name('recvaddr')->where(['user_id'=>$user_id,'is_default'=>1,'is_delete'=>0])->field('consignee,phone,province,city,area,address')->find();
        if(null===$recvaddr){
            $this->json_error('您还没有设置收货地址');
            die;
        }
        $goods_id = input('post.goods_id');
        if(null===$goods_id){
            $this->json_error('请传过来商品编号');
        }
        $num = input('post.num');
        if(null===$num){
            $this->json_error('请传过来购买数量');
        }
        if($num<=0){
            $this->json_error('数量不合法');
            die;
        }
        //查看库存是否够
        $goods = Db::name('goods')->where('id','=',$goods_id)->find();
        $total = $goods['total'];
        if($num>$total){
            $this->json_error('库存不足');
            die;
        }
        $goods_attr = input('post.goods_attr');
        if(null===$goods_attr){
            $this->json_error('请传过来商品属性');
        }
        if(!is_json($goods_attr)){
            $this->json_error('商品属性格式不对');
            die;
        }
        if($goods==null){
            $this->json_error('非法操作');
            die;
        }
        $shop_id = $goods['shopid'];
        $price = $goods['price'];
        //订单号
        $ordersn = makeordersn();
        $totalmoney = $num*$price;
        $order['shop_id'] = $shop_id;
        $order['order_sn'] = $ordersn;
        $order['user_id'] = $user_id;
        $order['money'] = $totalmoney;
        $order['oldmoney'] = $totalmoney;
        $order['total_num'] = $num;
        $order['status'] = 1;  //订单状态 1.待付款   2.待发货    3.已发货   4.待评价 5.已完成  6.已关闭 7售后
        $order['add_time'] = time();
        $order['getusername'] = $recvaddr['consignee'];
        $order['mobile'] = $recvaddr['phone'];
        $order['address'] = $recvaddr['address'];
        $order['province'] = $recvaddr['province'];
        $order['city'] = $recvaddr['city'];
        $order['area'] = $recvaddr['area'];
        $order['pay_type'] = $pay_type;
        $order_goods['order_sn'] = $ordersn;
        $order_goods['goodsid'] = $goods_id;
        $order_goods['price'] = $price;
        $order_goods['num'] = $num;
        $order_goods['specification'] = $goods_attr;
        $order_goods['addtime'] = time();
        //开启事务
        Db::startTrans();
        $order_id = Db::name('order')->insertGetId($order);
        $order_goods = Db::name('order_goods')->insertGetId($order_goods);
        $myorders = Db::name('order')->where(['id'=>$order_id,'user_id'=>$user_id])->field('id,order_sn')->select();
        $order_sns = json_encode(array_column($myorders,'order_sn'));
        $myorder_ids  = implode(',',array_column($myorders,'id'));
        $order_trades = [
            'out_trade_no'=>makeordersn(),
            'order_sns'=>$order_sns,
            'order_ids'=>$myorder_ids,
            'total_amount'=>$totalmoney
        ];
        $trade_id = Db::name('order_trade')->insertGetId($order_trades);
        //判断条件
        if($order_id && $trade_id && $order_goods){
            //如果全部成功,提交事务
            Db::commit();
            //--0拍下减库存   1付款减库存   2永不减库存
            Db::name('goods')->where(['id'=>$goods_id,'totalcnf'=>0])->setDec('total',$num);
            $orders =Db::name('order')->order('id','desc')
                ->where(['user_id'=>$user_id,'status'=>1])  //订单状态 1.待付款   2.待发货    3.已发货   4.待评价 5.已完成  6.已关闭 7售后
                ->whereIn('id',$order_id)
                ->select();
            //订单编号，去查找订单商品
            $order_sn = array_column($orders,'order_sn');
            $orders_goods = Db::name('order_goods')->alias('og')
                ->join('goods g','g.id=og.goodsid')
                ->join('shop s','s.id=g.shopid','left')
                ->field('og.price as ogprice,og.num,og.specification,og.order_sn as ogorder_sn,g.id as gid,g.title as gtitle,g.headimg,s.id as sid,s.name as sname,s.shoplogo')
                ->whereIn('og.order_sn',$order_sn)
                ->select();
            foreach($orders_goods as $gk=>$gv){
                foreach($orders as $kkk=>$vvv){
                    if($gv['ogorder_sn']==$vvv['order_sn']){
                        $orders_goods[$gk]['status'] = $vvv['status'];
                        $orders_goods[$gk]['oid'] = $vvv['id'];
                    }
                }
            }
            $shop_ids = array_column($orders,'shop_id');
            $shop_ids = implode(',',$shop_ids);
            $shops = Db::name('shop')->where('id','in',$shop_ids)->field('id,name,shoplogo')->select();
            $data2 = [];
            foreach($shops as $k=>$v){
                $shops[$k]['shoplogo'] = $this->domain().$v['shoplogo'];
                foreach($orders as $kkk=>$vvv){
                    if($v['id']==$vvv['shop_id']){
                        $shops[$k]['order_sn'] = $vvv['order_sn'];
                        $shops[$k]['status'] = $vvv['status'];
                        $shops[$k]['pay_type'] = $vvv['pay_type'];
                    }
                }
                foreach($orders_goods as $ok=>$ov){
                    if($v['id']==$ov['sid']){
                        $data2['gitle'] = $ov['gtitle'];
                        $data2['status'] = $ov['status'];
                        $data2['oid'] = $ov['oid'];
                        $data2['gid'] = $ov['gid'];
                        $data2['ogprice'] = $ov['ogprice'];
                        $data2['num'] = $ov['num'];
                        $data2['specification'] = json_decode($ov['specification'],true);
                        $headimgs = explode(',',$ov['headimg']);
                        $data2['headimg'] = $this->domain().$headimgs[0];
                        $shops[$k]['goods'][] = $data2;
                    }
                }
            }
            $info = [
                'recvaddr'=>$recvaddr,
                'shop'=>$shops
            ];
            $trandeinfo = Db::name('order_trade')->where('id',$trade_id)->field('out_trade_no,total_amount')->find();
            $info['out_trade_no'] = $trandeinfo['out_trade_no'];
            $info['total_amount'] = $trandeinfo['total_amount'];
            $this->json_success($info,'生成订单成功');
        }else{
            //如果失败,回滚事务
            Db::rollback();
            $this->json_error('生成订单失败');
        }
    }
    //提交订单
    /**
     *
     *[user_id] => 17
     *[pay_type] => 1
     *[myshop] => [{"shop_id":5,"cart_id":"245","remark_member":"","send_type":"1","coupon_id":"11"}]
     *
     */
     public function ordersub(){
     	        // print_r(input());exit;

     	$post = input('post.');
     	
        $user_id = input('post.user_id');
        $myshop = input('post.myshop');
        $pay_type = input('post.pay_type');
        $coupon_id = input('post.coupon_id');
        $address_id = input('post.address_id');
        $qtjs_money = input('post.money');
        $takes_time = input('post.takes_time'); // 到店自取时间
        $takes_mobile = input('post.takes_mobile'); // 自取手机号
        
        $myshop = $this->checkoutSubParam($user_id, $myshop, $pay_type, $coupon_id); //参数检测，顺带[]myshop
		
        //$recvaddr = $this->infoDefaultAddr($user_id); //默认收货地址
        $recvaddr = $this->infoDefaultAddr($address_id,$user_id); //查询用户收货地址

        $coupons = $this->infoAllCoupon($myshop, $coupon_id); //所有优惠券
		
        $carts = $this->infoAllCart($myshop, $user_id); //所有商品
		
        // 拼装 myshop消息，优惠券
        $shop_ids = array_column($myshop,'shop_id');
        $shops = Db::name('shop')->whereIn('id',$shop_ids)->field('id,name,shoplogo')->select();
        
        foreach ($shops as $key => $value) 
        {
            foreach ($myshop as $k => $v) 
            {
                if ($myshop[$k]['shop_id'] == $shops[$key]['id']) 
                {
                    $shops[$key]['cart_id'] = $myshop[$k]['cart_id'];
                    $shops[$key]['remark_member'] = $myshop[$k]['remark_member'];
                    $shops[$key]['send_type'] = $myshop[$k]['send_type'];
                    $shops[$key]['freight'] = $myshop[$k]['freight'];
                    $shops[$key]['coupon_id'] = $myshop[$k]['coupon_id'];
                    if($v['send_type'] == 1){
                    	$shops[$key]['price_token'] = $myshop[$k]['price_token'];
                		$shops[$key]['order_price'] = $myshop[$k]['order_price'];
                    	$shops[$key]['balance_paymoney'] = $myshop[$k]['freight'];
                    	
                    }
                    
                }
            }
            $shops[$key]['coupon_price'] = 0; // 商家优惠券金额
            $ping_coupon = 0; // 平台优惠券
            foreach ($coupons as $kc => $vc) 
            {
                if ($shops[$key]['coupon_id'] == $coupons[$kc]['couponlog_id']) 
                {
                    $shops[$key]['coupon_price'] = $coupons[$kc]['sub_price'];
                }
                if ($coupons[$kc]['type_id'] == 1) 
                {
                    $ping_coupon = $coupons[$kc]['sub_price'];
                }
            }
        }
        
        
        /*流水信息*/
        $out_trade_no = makeordersn();
        $info = $this->infoOrderAndGoods($shops, $user_id, $recvaddr, $carts, $pay_type, $takes_time, $takes_mobile, $out_trade_no); //order + order_goods
		


        Db::startTrans(); //开启事务
        try {
        //order
            $order = Db::name('order')->insertAll($info['order']); //order表添加
            if (!$order) {
                throw new \Exception("订单添加错误");
            }
            //获取order表添加id[];
            $order_id = Db::name('order')->getLastInsID();
            $order_ids = [];
            for ($i=0; $i<$order; $i++) {
                $order_ids[] = (int)$order_id++;
            }
        // order_goods
            $order_goods = Db::name('order_goods')->insertAll($info['order_goods']); //order_goods表添加
            if (!$order_goods) {
                throw new \Exception("订单商品添加错误");
            }
            $myorders = Db::name('order')->whereIn('id',$order_ids)->where(['user_id'=>$user_id])->field('id,order_sn')->select();
            $order_sns = json_encode(array_column($myorders,'order_sn'));
            $myorder_ids  = implode(',',array_column($myorders,'id'));
            if($info['total_amount']<=0){
                throw new \Exception("金额不合法");
            }
            $order_trades = [
                'out_trade_no'=>$out_trade_no,
                'order_sns'=>$order_sns,
                'order_ids'=>$myorder_ids,
                'total_amount'=>$info['total_amount'] - $info['coupon_amount'] - $ping_coupon
            ];
            if($order_trades['total_amount'] != $qtjs_money){
                throw new \Exception("金额错误");
            }
        //order_trade
            $trade_id = Db::name('order_trade')->insertGetId($order_trades);
            if (!$trade_id) {
                throw new \Exception("交易添加错误");
            }
            // 成功,提交事务
            Db::commit();
            $this->endFuncOrder($user_id, $coupons, $myshop, $carts);
            
            
            
            
            
            
            $data = [
                'recvaddr'=>$recvaddr,
                'shop'=>$shops
            ];
            $trandeinfo = Db::name('order_trade')->where('id',$trade_id)->field('out_trade_no,total_amount')->find();
            $data['out_trade_no'] = $trandeinfo['out_trade_no'];
            $data['total_amount'] = $trandeinfo['total_amount'];
            $this->json_success($data,'生成订单成功');
        } catch (\Exception $e) {
            // echo $e->getMessage();
            //如果失败,回滚事务
            Db::rollback();
            $this->json_error($e->getMessage());
        }

        



// 修改为用过，等最后
        
    }
    /*--------提交订单-------*/
    public function checkoutSubParam($user_id, $myshop, $pay_type, $coupon_id)
    {
        if (empty($user_id) || empty($myshop) || empty($pay_type)) {
            $this->json_error('参数错误');
        }
        $ptype = [1,2];
        if (!in_array($pay_type, $ptype) || !is_json($myshop)) {
            $this->json_error('格式错误');
        }
        $myshop = json_decode($myshop,true);
        $mykey = ['shop_id','cart_id','remark_member','send_type','freight','coupon_id'];
        foreach ($myshop as $key => $value) {
            foreach ($mykey as $v) {
                if (!array_key_exists($v,$value)) {
                    $this->json_error('缺少参数');
                }
            }
        }

        return $myshop;
    }
    public function infoDefaultAddr($address_id,$user_id)
    {
        $re_where = [
            'id' => $address_id,
            'user_id'=>$user_id,
            //'is_default'=>1,
            'is_delete'=>0
        ];
        $recvaddr = Db::name('recvaddr')->where($re_where)->field('consignee,phone,province,city,area,address')->find();
        if(!$recvaddr){
            $this->json_error('您还没有设置收货地址');
        }
        return $recvaddr;
    }
    public function infoAllCoupon($myshop, $coupon_id)
    {
        $where = array_column($myshop,'coupon_id');
        if (!empty($coupon_id)) {
            $where[] = $coupon_id;
        }
        // $cwhere=Db::name('couponlog')->whereIn('id',$where)->column('coupon_id');
        // $coupons = Db::name('coupon')->whereIn('id',$cwhere)->select();
        $coupons=Db::name('couponlog')->alias('l')
                ->field('c.*,l.id as couponlog_id')
                ->join('shy_coupon c','c.id = l.coupon_id')
                ->whereIn('l.id',$where)
                ->select();
        $is_expire = array_column($coupons,'is_expire');
        if (in_array('1',$is_expire)) {
            $this->json_error('优惠券错误');
        }
        return $coupons;
    }
    public function infoAllCart($myshop, $user_id)
    {
        $cart_id = implode(',',array_column($myshop,'cart_id'));
        $carts = Db::name('shopcart')->alias('c')
            ->join('goods g','g.id=c.goods_id','LEFT')
            ->whereIn('c.id',$cart_id)
            ->where(['c.user_id'=>$user_id])
            ->field('c.*,g.shopid,g.headimg,g.title,g.price')
            ->select();
        foreach ($carts as $key => $value) {
            if ($value['sku_id'] != 0) {
                $sku=Db::name('GoodsSttrxsku')->where('id',$value['sku_id'])->find();
                $carts[$key]['price'] = $sku['money'];
                if($value['num'] > $sku['number']){
                    $this->json_error('库存不足');
                }
            }else{
                $goods = Db::name('goods')->where('id', $value['goods_id'])->find();
                if($value['num'] > $goods['total']){
                    $this->json_error('库存不足');
                }
            }




        }
        return $carts;
    }
    public function infoOrderAndGoods($shops, $user_id, $recvaddr, $carts, $pay_type, $takes_time, $takes_mobile, $out_trade_no)
    {
        $order = []; //订单表
        $order_goods = []; //订单商品表
        $total_amount = 0; //订单原价
        $coupon_amount = 0; //商家优惠券总金额
        $you_money = 0;// 邮费
        // halt($shops);
        
        foreach($shops as $k=>$v)
        {
        	
            $ordersn = makeordersn();
            $total = 0; //每个店家价格
            $total_num = 0; //每个店家数量
            
            
            foreach ($carts as $key => $value) 
            {
                //购物车中商品，所有该商家的商品做处理
                if($v['id']==$value['shopid'])
                {  
                    $total = $value['price']*$value['num']+$total;
                    $total_num = $value['num']+$total_num;
                //---order_goods
                    $specification = ''; //组装sku信息值
                    if ($value['sku_id'] != 0) 
                    {
                        $goods_attr = json_decode($value['goods_attr'],true);
                        foreach ($goods_attr as $ks=>$vs)
                        {
                            $SttrName=Db::name('GoodsSttr')->where('id',$ks)->value('key');
                            $SttrValName=Db::name('GoodsSttrval')->where('id',$vs)->value('sttr_value');
                            $specification .=  $SttrName.':'.$SttrValName.' ';
                        }
                    }
                    $order_goods[] = [
                        'order_sn'  =>  $ordersn,
                        'goodsid'   =>  $value['goods_id'],
                        'price'     =>  $value['price'],
                        'num'       =>  $value['num'],
                        'specification'=>$specification,
                        'addtime'   =>  time(),
                        'sku_id'    =>  $value['sku_id'],
                    ];
                //---order_goods End
                }
            }
        //---order
	        if($v['send_type'] != 2){
	        	$takes_time = '';
	            $takes_mobile = '';
	        }
            if($v['send_type'] == 1){
            	$order[] = [
	                'shop_id'   =>  $v['id'],
	                'out_trade_no'=>$out_trade_no,
	                'order_sn'  =>  $ordersn, //订单号
	                'user_id'   =>  $user_id,
	                'money'     =>  ($total - $v['coupon_price'] + $v['freight']), // 金额-优惠卷+运费
	                'oldmoney'  =>  $total, // 原价
	                'coupon_id'=> $v['coupon_id'], //优惠券价格
	                'couponprice'=> $v['coupon_price'], //优惠券价格
	                'total_num' =>  $total_num, //总数量
	                'send_type' =>  $v['send_type'], //配送类型   
	                'takes_time'=>	$takes_time,
	                'takes_mobile'=>$takes_mobile,
	                
	                'freight'   =>  $v['freight'],   //邮费
	                'remark_member'=>$v['remark_member'],
	
	                'pay_type'  =>  $pay_type,
	                'status'    =>  1, //订单状态 1.待付款   2.待发货    3.已发货   4.待评价 5.已完成  6.已关闭 7售后
	                'add_time'  =>  time(),
	                'getusername'=> $recvaddr['consignee'], //收货人
	                'mobile'    =>  $recvaddr['phone'],
	                'address'   =>  $recvaddr['address'],
	                'province'  =>  $recvaddr['province'],
	                'city'  => $recvaddr['city'],
	                'area'  => $recvaddr['area'],
	                'price_token' => $v['price_token'],
	                'order_price' => $v['order_price'],
	                'balance_paymoney' => $v['freight'],
	            ];
            }else{
            	$order[] = [
	                'shop_id'   =>  $v['id'],
	                'out_trade_no'=>$out_trade_no,
	                'order_sn'  =>  $ordersn, //订单号
	                'user_id'   =>  $user_id,
	                'money'     =>  ($total - $v['coupon_price'] + $v['freight']), // 金额-优惠卷+运费
	                'oldmoney'  =>  $total, // 原价
	                'coupon_id'=> $v['coupon_id'], //优惠券价格
	                'couponprice'=> $v['coupon_price'], //优惠券价格
	                'total_num' =>  $total_num, //总数量
	                'send_type' =>  $v['send_type'], //配送类型   
	                'takes_time'=>	$takes_time,
	                'takes_mobile'=>$takes_mobile,
	                
	                'freight'   =>  $v['freight'],   //邮费
	                'remark_member'=>$v['remark_member'],
	
	                'pay_type'  =>  $pay_type,
	                'status'    =>  1, //订单状态 1.待付款   2.待发货    3.已发货   4.待评价 5.已完成  6.已关闭 7售后
	                'add_time'  =>  time(),
	                'getusername'=> $recvaddr['consignee'], //收货人
	                'mobile'    =>  $recvaddr['phone'],
	                'address'   =>  $recvaddr['address'],
	                'province'  =>  $recvaddr['province'],
	                'city'  => $recvaddr['city'],
	                'area'  => $recvaddr['area'],
	               
	            ];
            }
            
            
        //---order End
            $total_amount = $total_amount + $total;
            $coupon_amount = $coupon_amount + $v['coupon_price'];
            
            $you_money = $you_money + $v['freight'];

        }
        
        $data['order'] = $order;
        $data['order_goods'] = $order_goods;
        $data['total_amount'] = $total_amount + $you_money; //加运费
        $data['coupon_amount'] = $coupon_amount;
        return $data;
    }
    public function endFuncOrder($user_id, $coupons, $myshop, $carts)
    {
        // 订单成功。优惠券消耗
        $cid = array_column($coupons,'id');
        Db::name('couponlog')->whereIn('coupon_id',$cid)->where(['user_id'=>$user_id])->update(['is_use'=>1,'use_time'=>time()]);

        //--0拍下减库存   1付款减库存   2永不减库存
        foreach($carts as $mk=>$mv){
            if ($mv['sku_id'] != 0) 
            {
                $good = Db::name('goods')->where(['id'=>$mv['goods_id']])->find();
                if ($good['totalcnf'] == 0) 
                {
                    Db::name('GoodsSttrxsku')->where('id',$mv['sku_id'])->setDec('number',$mv['num']);
                }
            }else
            {
                Db::name('goods')->where(['id'=>$mv['goods_id'],'totalcnf'=>0])->setDec('total',$mv['num']);
            }
            
        }

        //删除购物车shopcart
        $cart_id = array_column($myshop,'cart_id');
        $cart_id = implode(',',$cart_id);
        Db::name('shopcart')->where('user_id','=',$user_id)->whereIn("id",$cart_id)->delete();
    }

    /*---------------------*/
     
     
     
     
     
     
    public function ordersub2()
    {
       
        $user_id = input('post.user_id');
        if(null===$user_id){
            $this->json_error('请传过来用户编号');
        }
        
        $myshop = input('post.myshop');
        if(!is_json($myshop)){
            $this->json_error('格式不对');
            die;
        }
        $myshop = json_decode($myshop,true);
        if(null===$myshop){
            $this->json_error('请传过来店铺编号和购物车id信息和备注信息');
        }
        $cart_id = array_column($myshop,'cart_id');
        $cart_id = implode(',',$cart_id);
        //pay_type支付方式   1支付宝  2微信  3银联
        $pay_type = input('post.pay_type');
        if(null===$pay_type){
            $this->json_error('请传过来支付方式');
        }
        $ptype = [1,2,3];
        if(!in_array($pay_type,$ptype)){
            $this->json_error('支付方式有问题');
        }
        $carts = Db::name('shopcart')->alias('c')
            ->join('goods g','g.id=c.goods_id','LEFT')
            ->whereIn('c.id',$cart_id)
            ->where(['user_id'=>$user_id])
            ->field('c.*,g.shopid,g.headimg,g.title,g.price')
            ->select();
        //查看当前用户是否有默认的收货地址
        $recvaddr = Db::name('recvaddr')->where(['user_id'=>$user_id,'is_default'=>1,'is_delete'=>0])->field('consignee,phone,province,city,area,address')->find();
        if(null===$recvaddr){
            $this->json_error('您还没有设置收货地址');
            die;
        }
        $shop_ids = array_column($myshop,'shop_id');
        //有多少个商家就生成多少个订单
        //$shop_ids = array_unique(array_column($carts,'shopid'));
        $shop_ids = implode(',',$shop_ids);
        $shops = Db::name('shop')->where('id','in',$shop_ids)->field('id,name,shoplogo')->select();
        //优惠券
        $coupon_id = array_column($myshop,'coupon_id');
        $coupons = Db::name('coupon')->whereIn('id',$coupon_id)->where(['is_expire'=>0])->select();
        $cid = array_column($coupons,'id');
        Db::name('couponlog')->whereIn('coupon_id',$cid)->where(['user_id'=>$user_id])->update(['is_use'=>1,'use_time'=>time()]);
        $data = [];
        $order_goods = [];
        foreach($shops as $k=>$v){
            $shops[$k]['shoplogo'] = $this->domain().$v['shoplogo'];
            //订单号
            $ordersn = makeordersn();
            $shops[$k]['order_sn'] = $ordersn;
            $total = 0;
            $total_num = 0;
            foreach($carts as $ck=>$cv){
                if($v['id']==$cv['shopid']){
                    $data['id'] = $cv['id'];
                    $data['goods_id'] = $cv['goods_id'];
                    $data['num'] = $cv['num'];
                    $data['goods_attr'] = json_decode($cv['goods_attr'],true);
                    $data['title'] = $cv['title'];
                    $data['price'] = $cv['price'];
                    //统计商品价格
                    $data['tprice'] = $cv['num']*$data['price'];
                    $total+=$data['tprice'];
                    $total_num+=$data['num'];
                    $headimgs = explode(',',$cv['headimg']);
                    $data['headimg'] = $this->domain().$headimgs[0];
                    $shops[$k]['goods'][] = $data;
                    $order_goods[$ck]['order_sn'] = $ordersn;
                    $order_goods[$ck]['goodsid'] = $cv['goods_id'];
                    $order_goods[$ck]['price'] = $cv['price'];
                    $order_goods[$ck]['num'] = $cv['num'];
                    
/*---chen*/
                    $order_goods[$ck]['specification'] = '';
                    if ($cv['sku_id'] != 0) {
                        $goods_attr = json_decode($cv['goods_attr'],true);
                        foreach ($goods_attr as $ks=>$vs){
                            $SttrName=Db::name('GoodsSttr')->where('id',$ks)->value('key');
                            $SttrValName=Db::name('GoodsSttrval')->where('id',$vs)->value('sttr_value');
                            $order_goods[$ck]['specification'] .=  $SttrName.':'.$SttrValName.' ';
                        }
                    }
/*---chen*/
                    // $order_goods[$ck]['specification'] = $cv['goods_attr'];
                    $order_goods[$ck]['addtime'] = time();
                    /*---chen*/
                    $order_goods[$ck]['sku_id'] = $cv['sku_id'];
                    /*---chen*/
                }
            }
            $shops[$k]['totalprice'] = $total;
            $shops[$k]['total_num'] = $total_num;
        }
        $order = [];
        $total_amount = 0;
        foreach($shops as $k=>$v){
            $order[$k]['shop_id'] = $v['id'];
            $order[$k]['order_sn'] = $v['order_sn'];
            $order[$k]['user_id'] = $user_id;
            $order[$k]['money'] = $v['totalprice'];
            $order[$k]['oldmoney'] = $v['totalprice'];
            $order[$k]['total_num'] = $v['total_num'];
            $order[$k]['status'] = 1;  //订单状态 1.待付款   2.待发货    3.已发货   4.待评价 5.已完成  6.已关闭 7售后
            $order[$k]['add_time'] = time();
            $order[$k]['getusername'] = $recvaddr['consignee'];
            $order[$k]['mobile'] = $recvaddr['phone'];
            $order[$k]['address'] = $recvaddr['address'];
            $order[$k]['province'] = $recvaddr['province'];
            $order[$k]['city'] = $recvaddr['city'];
            $order[$k]['area'] = $recvaddr['area'];
            $order[$k]['pay_type'] = $pay_type;
            foreach($myshop as $mmk=>$mmv){
                if($mmv['shop_id']==$v['id']){
                    $order[$k]['remark_member'] = $mmv['remark_member'];
                }
            }
            foreach($coupons as $ck=>$cv){
                if($cv['shop_id']==$v['id']){
                    $order[$k]['couponid'] = $cv['id'];
                    $order[$k]['couponprice'] = $cv['sub_price'];
                }
            }
            $total_amount+=$v['totalprice'];
        }
        //开启事务
        Db::startTrans();
        //数据操作
        $order = Db::name('order')->insertAll($order);
        $order_id = Db::name('order')->getLastInsID();
        $order_ids = [];
        for ($i=0; $i<$order; $i++) {
            $order_ids[] = (int)$order_id++;
        }
        $order_goods = Db::name('order_goods')->insertAll($order_goods);
        $myorders = Db::name('order')->whereIn('id',$order_ids)->where(['user_id'=>$user_id])->field('id,order_sn')->select();
        $order_sns = json_encode(array_column($myorders,'order_sn'));
        $myorder_ids  = implode(',',array_column($myorders,'id'));
       if($total_amount<=0){
           $this->json_error('金额不合法');
           die;
       }
        $order_trades = [
            'out_trade_no'=>makeordersn(),
            'order_sns'=>$order_sns,
            'order_ids'=>$myorder_ids,
            'total_amount'=>$total_amount
        ];
        
        $trade_id = Db::name('order_trade')->insertGetId($order_trades);
        //判断条件
        if($order && $trade_id && $order_ids){
            //如果全部成功,提交事务
            Db::commit();
            //--0拍下减库存   1付款减库存   2永不减库存
            foreach($carts as $mk=>$mv){
                Db::name('goods')->where(['id'=>$mv['goods_id'],'totalcnf'=>0])->setDec('total',$mv['num']);
            }
            //删除购物车shopcart
            Db::name('shopcart')->where('user_id','=',$user_id)->where("id in ($cart_id)")->delete();
            $orders =Db::name('order')->order('id','desc')
                ->where(['user_id'=>$user_id,'status'=>1])  //订单状态 1.待付款   2.待发货    3.已发货   4.待评价 5.已完成  6.已关闭 7售后
                ->whereIn('id',$order_ids)
                ->select();
            //订单编号，去查找订单商品
            $order_sn = array_column($orders,'order_sn');
            $orders_goods = Db::name('order_goods')->alias('og')
                ->join('goods g','g.id=og.goodsid')
                ->join('shop s','s.id=g.shopid','left')
                ->field('og.price as ogprice,og.num,og.specification,og.order_sn as ogorder_sn,g.id as gid,g.title as gtitle,g.headimg,s.id as sid,s.name as sname,s.shoplogo')
                ->whereIn('og.order_sn',$order_sn)
                ->select();
            foreach($orders_goods as $gk=>$gv){
                foreach($orders as $kkk=>$vvv){
                    if($gv['ogorder_sn']==$vvv['order_sn']){
                        $orders_goods[$gk]['status'] = $vvv['status'];
                        $orders_goods[$gk]['oid'] = $vvv['id'];
                    }
                }
            }
            $shop_ids = array_column($orders,'shop_id');
            $shop_ids = implode(',',$shop_ids);
            $shops = Db::name('shop')->where('id','in',$shop_ids)->field('id,name,shoplogo')->select();
            $data2 = [];
            foreach($shops as $k=>$v){
                $shops[$k]['shoplogo'] = $this->domain().$v['shoplogo'];
                foreach($orders as $kkk=>$vvv){
                    if($v['id']==$vvv['shop_id']){
                        $shops[$k]['order_sn'] = $vvv['order_sn'];
                        $shops[$k]['status'] = $vvv['status'];
                    }
                }
                foreach($orders_goods as $ok=>$ov){
                    if($v['id']==$ov['sid']){
                        $data2['gitle'] = $ov['gtitle'];
                        $data2['status'] = $ov['status'];
                        $data2['oid'] = $ov['oid'];
                        $data2['gid'] = $ov['gid'];
                        $data2['ogprice'] = $ov['ogprice'];
                        $data2['num'] = $ov['num'];
                        $data2['specification'] = json_decode($ov['specification'],true);
                        $headimgs = explode(',',$ov['headimg']);
                        $data2['headimg'] = $this->domain().$headimgs[0];
                        $shops[$k]['goods'][] = $data2;
                    }
                }
            }
            $info = [
                'recvaddr'=>$recvaddr,
                'shop'=>$shops
            ];
            $trandeinfo = Db::name('order_trade')->where('id',$trade_id)->field('out_trade_no,total_amount')->find();
            $info['out_trade_no'] = $trandeinfo['out_trade_no'];
            $info['total_amount'] = $trandeinfo['total_amount'];
            $this->json_success($info,'生成订单成功');
        }else{
            //如果失败,回滚事务
            Db::rollback();
            $this->json_error('生成订单失败');
        }
    }
    //订单提交
     /**
     *
     *[user_id] => 17
     *[pay_type] => 1
     *[myshop] => [{"shop_id":5,"cart_id":"245","remark_member":""}]
     *
     */
    public function ordersub3(){
        
        $user_id = input('post.user_id');
        if(null===$user_id){
            $this->json_error('请传过来用户编号');
        }
        
        $myshop = input('post.myshop');
        if(!is_json($myshop)){
            $this->json_error('格式不对');
            die;
        }
        $myshop = json_decode($myshop,true);
        $cart_id = array_column($myshop,'cart_id');
        if(null===$myshop){
            $this->json_error('请传过来店铺编号和购物车id信息和备注信息');
        }       
        //pay_type支付方式   1支付宝  2微信  3银联
        $pay_type = input('post.pay_type');
        if(null===$pay_type){
            $this->json_error('请传过来支付方式');
        }
        $ptype = [1,2,3];
        if(!in_array($pay_type,$ptype)){
            $this->json_error('支付方式有问题');
        }
        //获取用户地址
         //查看当前用户是否有默认的收货地址
         $recvaddr = Db::name('recvaddr')->where(['user_id'=>$user_id,'is_default'=>1,'is_delete'=>0])->field('consignee,phone,province,city,area,address')->find();
         if(null===$recvaddr){
             $this->json_error('您还没有设置收货地址');
             die;
         }
         //循环myshop
         $order_info=array();//订单信息
         $order_goods=array();//订单商品
         $order_sn=array();//订单号
         $order_total_money=0;//订单总金额
         foreach($myshop as $k=>$v){
                $order['order_sn']=makeordersn();//订单号
                $order_sn[]=$order['order_sn'];
                $order['user_id']=$user_id;//用户id
                $order['shop_id']=$v['shop_id'];//商户id
                $order['pay_type']=$pay_type;//支付类型
                $order['add_time']=time();//下单时间
                $order['status']=1;//订单状态 1待支付
                $order['remark_member']=$v['remark_member'];//备注信息
                $order['getusername']=$recvaddr['consignee'];
                $order['mobile']=$recvaddr['phone'];
                $order['address']=$recvaddr['address'];
                $order['province']=$recvaddr['province'];
                $order['city']=$recvaddr['city'];
                $order['area']=$recvaddr['area'];                  
                $order['send_type']=$v['send_type'];//配送类型    
                $order['freight']  =$v['freight']; //邮费     
                //订单商品
                $carts_goods=$this->getCartGoods($v['cart_id'],$user_id); 
                $goods_total_price=0; //商品总价格
                $num =0;         //商品总的数量
                //组装商品
                foreach($carts_goods as $key=>$val){
                       $goods['order_sn']=$order['order_sn'];//订单号
                       $goods['goodsid']=$val['goods_id'];//商品id
                       $goods['price']=$val['price'];//商品价格
                       $goods['num']=$val['num'];//商品数量
                       $goods['addtime']=time();//添加时间
                       $goods['sku_id']=$val['sku_id'];//skuid
                       $specification='';
                       if ($val['sku_id'] != 0) {
                        $goods_attr = json_decode($val['goods_attr'],true);
                        foreach ($goods_attr as $ks=>$vs){
                            $SttrName=Db::name('GoodsSttr')->where('id',$ks)->value('key');
                            $SttrValName=Db::name('GoodsSttrval')->where('id',$vs)->value('sttr_value');
                            $specification .=  $SttrName.':'.$SttrValName.' ';
                            }    
                        $goods['price']=$val['sku_money'];                    
                        }
                        $goods['specification']= $specification;
                        $order_goods[]=$goods;
                        $goods_total_price +=$goods['price']*$val['num'];
                        $num +=$val['num'];
                }
                //组装优惠券
                $coupons_money=0;
                if(!empty($v['coupon_id'])){
                    $coupons=$this->getCoupons($v['coupon_id']);                   
                    if(!empty($coupons)){//不为空的话
                        $coupons_money =$coupons['sub_price'];                   
                    }
                    $order['coupon_id']=$v['coupon_id'];
                    $order['couponprice']=$coupons_money;
                }               
                //优惠券结束
                $totalprice =$goods_total_price -$coupons_money+$v['freight'];//商品总的价钱 商品总价-优惠券+邮费
                // $totalprice =$goods_total_price -$coupons_money;//商品总的价钱 商品总价-优惠券+邮费
                if($totalprice<0){//小于零时候订单为零
                    $totalprice =0;
                }
                $order['money']=$totalprice;
                $order['oldmoney']=$totalprice;
                $order['total_num']=$num;//订单总的数量
                $order_info[]=$order;
                $order_total_money +=$totalprice;//订单总金额
                
         }
         //开启事务
         Db::startTrans();
         $order_result=Db::name('order')->insertAll($order_info);
         $goods_result=Db::name('order_goods')->insertAll($order_goods);
         $order_infos=Db::name('order')->whereIn('order_sn',$order_sn)->select();
         //减库存
         $this->decGoodsNum($order_goods);
         //删除购物车
         $this->delCartGoods($cart_id);
         $myorder_ids  = implode(',',array_column($order_infos,'id'));
         $coupon_ids  = implode(',',array_column($order_infos,'coupon_id'));
         Db::name('couponlog')->whereIn('coupon_id',$coupon_ids)->where(['user_id'=>$user_id])->update(['is_use'=>1,'use_time'=>time()]);
         $order_sns=implode(',',$order_sn);//分割订单号
         $order_trades = [
            'out_trade_no'=>makeordersn(),//支付日志编号
            'order_sns'=>$order_sns,//订单编号
            'order_ids'=>$myorder_ids,//订单id编号
            'total_amount'=>$order_total_money//支付金额
        ];
      
        $trade_id = Db::name('order_trade')->insertGetId($order_trades);
          
        if($trade_id && $order_result && $goods_result){
            Db::commit();//事务提交
            $info['recaddr']=$recvaddr;
            $trandeinfo = Db::name('order_trade')->where('id',$trade_id)->field('out_trade_no,total_amount')->find();
            $info['out_trade_no'] = $trandeinfo['out_trade_no'];
            $info['total_amount'] = $trandeinfo['total_amount'];
            $this->json_success($info,'生成订单成功');
        }else{
            Db::rollback();//事务回滚
            $this->json_error('生成订单失败');
        }         
    }
    //购物车商品
    public function getCartGoods($cart_id,$user_id){   
        $carts = Db::name('shopcart')->alias('c')
                ->join('goods g','g.id=c.goods_id','LEFT')
                ->join('goods_sttrxsku s','s.id =c.sku_id',"LEFT")
                ->whereIn('c.id',$cart_id)
                ->where(['user_id'=>$user_id])
                ->field('c.*,g.shopid,g.headimg,g.title,g.price,s.money sku_money')
                ->select();
        return $carts;
    }
    //获取优惠劵
    public function getCoupons($coupon_id){
        $coupon=Db::name('coupon')->where('id',$coupon_id)->where(['is_expire'=>0])->find();
        return $coupon;
    }
    //减库存
    public function decGoodsNum($order_goods){
        foreach($order_goods as $k=>$v){
            //减总的库存
            Db::name('goods')->where(['id'=>$v['goodsid']])->setDec('total',$v['num']);
            //sku 库存
            if($v['sku_id']!=0){
                Db::name('goods_sttrxsku')
                    ->where(['id'=>$v['sku_id']])
                    ->setDec('number',$v['num']);
                Db::name('goods_sttrxsku')
                    ->where(['id'=>$v['sku_id']])
                    ->setInc('volume',$v['num']);
            }
        }
    }
    //删除购物车
    public function delCartGoods($cart_ids){
        Db::name('shopcart')->whereIn('id',$cart_ids)->delete();
    }
    //申请退款
    public function refund()
    {
        $user_id = input('post.user_id');
        if (null === $user_id) {
            $this->json_error('请传过来用户编号');
        }
        $order_id = input('post.order_id');
        if (null === $order_id) {
            $this->json_error('请传过来订单编号');
        }
        $goods_id = input('post.goods_id');
        if (null === $goods_id) {
            $this->json_error('请传过来商品编号');
        }
        $og_id = input('post.og_id');
        if (null === $og_id) {
            $this->json_error('请传过来订单商品编号');
        }
        //判断当前订单是否存在
        $order = Db::name('order')->where(['id'=>$order_id])->find();
        if(null === $order){
            $this->json_error('非法操作');
            die;
        }
        //操作   otype=1  显示    otype=2  提交
        $otype = input('post.otype') ? input('post.otype') : 1;
        $order_sn = $order['order_sn'];
        $orders_goods = Db::name('order_goods')->alias('og')
            ->join('goods g','g.id=og.goodsid','LEFT')
            ->field('og.price as ogprice,og.num,og.specification,og.order_sn as ogorder_sn,og.id as og_id,g.id as gid,g.title as gtitle,g.headimg')
            ->where(['og.goodsid'=>$goods_id,'og.order_sn'=>$order_sn,'og.id'=>$og_id])
            ->find();
        if($otype==1){
            $headimgs = explode(',',$orders_goods['headimg']);
            $orders_goods['headimg'] = $this->domain().$headimgs[0];
            
            $this->json_success($orders_goods);
            die;
        }else{
            $rid = input('post.rid');
            if (null === $rid) {
                $this->json_error('请传过来退款原因编号');
            }
            $rule = [
                'mobile' => 'require|max:11|regex:/^1[3-8]{1}[0-9]{9}$/',
                //'imgs' => 'require'
            ];
            $msg = [
                'mobile.require' => '手机号码不能为空',
                'mobile.max' => '手机号码不符合长度',
                'mobile.regex' => '手机号码格式不正确',
                'imgs.require' => '上传凭证不能为空'
            ];
            $result = $this->validate(input('post.'), $rule, $msg);
            if (true !== $result) {
                // 验证失败 输出错误信息
                $this->json_error($result);
                die;
            }else {
                $mobile = input('post.')['mobile'];
                $imgs = $result['imgs'];
                $money = $orders_goods['ogprice'];
                //申请过了不让重新申请
                $orderrefund = Db::name('orderrefund')->where(['user_id'=>$user_id,'og_id'=>
                    $og_id])->find();
                if(null!=$orderrefund){
                    $this->json_error('你已经申请过了，请不要重复申请');
                    die;
                }
                $data = [
                    'rid'=>$rid,
                    'order_id'=>$order_id,
                    'order_sn'=>$order_sn,
                    'og_id'=>$og_id,
                    'money'=>$money,
                    'mobile'=>$mobile,
                    'imgs'=>$imgs,
                    'goods_id'=>$goods_id,
                    'user_id'=>$user_id,
                    'add_time'=>time()
                ];
                $remark = input('post.remark');
                if(null!=$remark){
                    $data['remark'] = $remark;
                }
                Db::name('order')->where('id',$order_id)->update(['is_refund'=>1]);
                $id = Db::name('orderrefund')->insertGetId($data);
                if($id){
                    $this->json_success([],'申请成功，请耐心等待结果');
                    die;
                }else{
                    $this->json_error([],'申请失败，请重新申请');
                    die;
                }
            }
        }
    }
    //退款原因
    public function reason()
    {
        $data = Db::name('orderreason')->order('id desc')->select();
        $this->json_success($data);
    }
    //我的申请退款售后列表
    public function aftersale()
    {
        $user_id = input('post.user_id');
        if(null===$user_id){
            $this->json_error('请传过来用户编号');
        }
        //当前的页码
        $p = empty(input('post.p')) ?1:input('post.p');
        //每页显示的数量
        $rows = empty(input('post.rows'))?10:input('post.rows');
        $data = Db::name('orderrefund')->alias('of')
            ->join('goods g','g.id=of.goods_id','LEFT')
            ->join('shop s','s.id=g.shopid','LEFT')
            ->field('of.id,of.status,of.order_sn,of.goods_id,of.add_time,g.id as gid,g.headimg,g.title,s.id as sid,s.name,s.shoplogo')
            ->where(['of.user_id'=>$user_id])
            ->order('of.id','desc')
            ->page($p,$rows)
            ->select();
        $order_sns = array_column($data,'order_sn');
        $order_goods = Db::name('order_goods')->whereIn('order_sn',$order_sns)->select();
        foreach($data as $k=>$v){
            $data[$k]['shoplogo'] = $this->domain().$v['shoplogo'];
            $headimg = explode(',',$v['headimg']);
            $data[$k]['headimg'] = $this->domain().$headimg[0];
            $data[$k]['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
            foreach($order_goods as $kk=>$vv){
                if($vv['goodsid']==$v['goods_id'] && $vv['order_sn']==$v['order_sn']){
                    $data[$k]['price'] = $vv['price'];
                    $data[$k]['num'] = $vv['num'];
                    $data[$k]['specification'] = json_decode($vv['specification']);
                }
            }
        }
        $this->json_success($data);
    }
    //删除订单，假删除
    public function delorder()
    {
        $user_id = input('post.user_id');
        if(null===$user_id){
            $this->json_error('请传过来用户编号');
        }
        $order_id = input('post.order_id');
        if(null===$order_id){
            $this->json_error('请传过来订单编号');
        }
        $order = Db::name('order')->where(['user_id'=>$user_id,'id'=>$order_id])->find();
        if(null==$order){
            $this->json_error('非法操作');
        }
        $res = Db::name('order')->where('id',$order_id)->update(['is_del' => 1]);
        if($res){
            $this->json_success([],'删除订单成功');
        }else{
            $this->json_error('删除订单失败');
        }
    }
    //确认收货
    public function orderok()
    {
        $user_id = input('post.user_id');
        if(null===$user_id){
            $this->json_error('请传过来用户编号');
        }
        $order_id = input('post.order_id');
        if(null===$order_id){
            $this->json_error('请传过来订单编号');
        }
        $order = Db::name('order')->where(['user_id'=>$user_id])->whereIn('id',$order_id)->find();
        if(null==$order){
            $this->json_error('非法操作');
        }
        $res = Db::name('order')->whereIn('id',$order_id)->update(['status' => 4,'affirmtime'=>time(),'overtime'=>time()]);
        if($res){
            $this->json_success([],'确认收货成功');
        }else{
            $this->json_error('确认收货失败');
        }
    }
    //取消订单
    public function cancleorder()
    {
        $user_id = input('post.user_id');
        if(null===$user_id){
            $this->json_error('请传过来用户编号');
        }
        $order_id = input('post.order_id');
        if(null===$order_id){
            $this->json_error('请传过来订单编号');
        }
        $order = Db::name('order')->where(['user_id'=>$user_id,'id'=>$order_id])->find();
        if(null==$order){
            $this->json_error('非法操作');
        }
        //没有付款的订单可以取消
        //status 订单状态 1.待付款   2.待发货    3.已发货   4.待评价 5.已完成  6.已关闭 7.售后 8.取消订单
        if($order['status']==6){
            $this->json_error('您已经取消了订单，请不要重复取消');
            die;
        }
        if($order['status']!=1){
            $this->json_error('不能取消订单，请申请售后');
            die;
        }
        $res = Db::name('order')->where('id',$order_id)->update(['canceltime' => time(),'status'=>6]);
        if($res){
            //库存在加回去
            $order_sn = $order['order_sn'];
            $ordegoods = Db::name('order_goods')->whereIn('order_sn',$order_sn)->select();
            //--0拍下减库存   1付款减库存   2永不减库存
            foreach($ordegoods as $mk=>$mv){
                // Db::name('goods')->where(['id'=>$mv['goodsid'],'totalcnf'=>0])->setInc('total',$mv['num']);
                if ($mv['sku_id'] != 0) {
                    Db::name('GoodsSttrxsku')->where(['id'=>$mv['sku_id']])->setInc('number',$mv['num']);
                }else{
                    Db::name('goods')->where(['id'=>$mv['goodsid'],'totalcnf'=>0])->setInc('total',$mv['num']);
                }
            }
            $this->json_success([],'取消订单成功');
        }else{
            $this->json_error('取消订单失败');
        }
    }
    // // 退款订单
    // public function refund()
    // {
    //     $user_id = input('post.user_id');
    //     if(null===$user_id){
    //         $this->json_error('请传过来用户编号');
    //     }
    // }
     //普通邮费
    public function ptyf(){
        $user_id=input('user_id');//会员id
        $shop_id=input('shop_id');//商户id
        $cart_id=input('cart_id');//购物车id
        $dispatchprice=0;//邮费
        //获取用户的地址
        $address=DB::name('recvaddr')->where(['user_id'=>$user_id,'is_default'=>1])->find();

	if($address == null){
	   $this->json_error('请先设置收获地址！');
	}
        $province=rtrim($address['province'],'市');
        $city=$address['city'];
        $cityid=Db::name('area')->alias('a')
               ->join('area b','a.id=b.parent_id',"LEFT")
               ->where(['a.area_name'=>$province,'b.area_name'=>$city])
               ->value('b.id'); 
        //取出商家地址邮费配置
        $address_yf=Db::name('shop_postage')->where(['shop_id'=>$shop_id])->value('money');
        $address_yf=json_decode($address_yf,true);
        $cityprice=$address_yf[$cityid];
        $goods_list=Db::name('shopcart')->alias("a")
                    ->join("goods b","a.goods_id=b.id","LEFT")
                    ->whereIn('a.id',$cart_id)
                    ->field('b.issendfree,b.dispatchprice,b.ednum,b.edmoney,a.num,b.price')
                    ->select();
        foreach($goods_list as $key=>$val){
            if($val['issendfree']==1){//包邮
                $dispatchprice =0;
                break;
            }else{
                if($val['ednum'] !=0 || $val['edmoney'] !=0){
                    //都不符合条件
                    if(($val['num'] < $val['ednum']) && ($val['num']*$val['price'] <$val['edmoney'])){
                        $dispatchprice =$val['dispatchprice'];
                        $dispatchprice =$cityprice ? $cityprice : $dispatchprice;
                        break;                        
                    }
                    //有符合符合条件
                    if(($val['num'] >= $val['ednum'] && $val['ednum'] !=0) || ($val['num']*$val['price'] >=$val['edmoney'] && $val['edmoney'] !=0)){
                        $dispatchprice =0;
                        break;
                    }
                }
            }
        }
        $data['dispatchprice']=$dispatchprice;
        $data['shop_id']=$shop_id;
        $this->json_success($data);
    }
    
    // 获取验证码
    public function openid()
    {
        $post = input('post.');
        
        $guid = str_replace('-', '', $this->guid());
        $appKey ='c6832bdc4c4f4886b06df9f1043444dc';
       // $appKey = Config::get('uu')['appkey'];
        $date = array(
        	
        	'user_mobile' => 15225618021,
            'user_ip' => request()->ip(),
            // 'user_openid' => '819d065e887e43c291601412c0b36586',
            'nonce_str' => strtolower($guid),
            'timestamp' => time(),
            'appid' => '4a3f49278abc45f9bcbccf142d5ed481',
            //'appid' => Config::get('uu')['appid'],
        	);
       
        ksort($date);
        //halt($appKey);
        $date['sign'] = $this->sign($date, $appKey);
         
       // $date['sign'] = sign($date, $appKey);
        $url = "http://openapi.uupaotui.com/v2_0/binduserapply.ashx";
        
        return $this->request_post($url,$date);
    }
    // 配送费 
    public function delivery()
    {
        $post = input('post.');
        $shop = Db::table('shy_shop')->where('id',$post['shop_id'])->find();
        // dump($shop);
        $form_address = $shop['province'].$shop['city'].$shop['area'].$shop['street'];
        // dump($shop['province'].$shop['city'].$shop['area'].$shop['street'].$shop['address']);
        $appKey ='c6832bdc4c4f4886b06df9f1043444dc';
		$guid = str_replace('-', '', $this->guid());
		$date = array(
				'origin_id' => $post['origin_id'],
				'from_address' => $form_address,
				'from_usernote' => $shop['address'],
				'to_address' => $post['to_address'],
				// 'to_usernote' => '',
				'city_name' => $post['city_name'],
				// 'subscribe_type' => 0,
				// 'county_name' => '',
				// 'subscribe_time' => '',
				// 'coupon_id' => -1,
				'send_type' => 0,
				'to_lat' => 0,
				'to_lng' => 0,
				'from_lat' => 0,
				'from_lng' => 0,
				'nonce_str' => strtolower($guid),
				'timestamp' => time(),
				'openid' => '819d065e887e43c291601412c0b36586',
				'appid' => '4a3f49278abc45f9bcbccf142d5ed481'
			);
			
			ksort($date);
			
	        //halt($appKey);
	        $date['sign'] = $this->sign($date, $appKey);
	        // dump($date);
			$url = "http://openapi.uupaotui.com/v2_0/getorderprice.ashx";
		    $res =  $this->request_post($url,$date);
		    
		    // dump($res);
		    // dump($post['shop_id']);
		    $res = json_decode($res,true);
		    // dump($res);
		    $res['shop_id'] = $post['shop_id'];
		    // $res.shop_id=$post['shop_id'];
		    // $res = explode(',',$res);
		    // dump($res);
		    // $res = implode(',',$res);
		    $res=json_encode($res);
		    $this->json_success($res);
    }
    
    // 生成guid
    function guid(){
	    if (function_exists('com_create_guid')){
	        return com_create_guid();
	    }else{
	        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
	        $charid = strtoupper(md5(uniqid(rand(), true)));
	        $hyphen = chr(45);// "-"
	        $uuid = substr($charid, 0, 8).$hyphen
	                .substr($charid, 8, 4).$hyphen
	                .substr($charid,12, 4).$hyphen
	                .substr($charid,16, 4).$hyphen
	                .substr($charid,20,12);                
	        return $uuid;
	    }
	}
	
	// 发起请求
	function request_post($url = '', $post_data = array()) {
	    if (empty($url) || empty($post_data)) {
	        return false;
	    }
	    
	    $arr = [];
	    foreach ($post_data as $key => $value) {
	      $arr[] = $key.'='.$value;
	    }
	
	    $curlPost = implode('&', $arr);
	
	    $postUrl = $url;
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL,$postUrl);
	    curl_setopt($ch, CURLOPT_HEADER, 0);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_POST, 1);
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
	    $data = curl_exec($ch);
	    curl_close($ch);
	    
	    return $data;
	}
	
	// 生成签名
	function sign($data, $appKey) {
	  $arr = [];
	  foreach ($data as $key => $value) {
	    $arr[] = $key.'='.$value;
	  }
	
	  $arr[] = 'key='.$appKey;
	  $str = strtoupper(implode('&', $arr));
	  //$str = http_build_query($arr, '&');
	  return strtoupper(md5($str));
	}
	
	// 获取订单详情
     public function detailsxq()
     {
        $id = input('post.id');
        // halt($id);
        $list = Db::name('order')->where('id',$id)->find();
        // halt($list);
        // dump($shop['province'].$shop['city'].$shop['area'].$shop['street'].$shop['address']);
        $appKey ='c6832bdc4c4f4886b06df9f1043444dc';
        $guid = str_replace('-', '', $this->guid());
        $date = array(
                'order_code' => $list['ordercode'],
                'origin_id' => $list['origin_id'],
                
                
                
                'nonce_str' => strtolower($guid),
                'timestamp' => time(),
                'openid' => '819d065e887e43c291601412c0b36586',
                'appid' => '4a3f49278abc45f9bcbccf142d5ed481'
            );
            
        ksort($date);
        
        //halt($appKey);
        $date['sign'] = $this->sign($date, $appKey);
        // dump($date);
        $url = "http://openapi.uupaotui.com/v2_0/getorderdetail.ashx";
        $res =  $this->request_post($url,$date);
        $this->json_success($res);
     }
     
     //取消订单
     public function cancel()
     {
     	$id = input('post.');
     	// halt($id);
     	$list = Db::name('order')->where('id',$id['order_id'])->find();
     	$appKey ='c6832bdc4c4f4886b06df9f1043444dc';
        $guid = str_replace('-', '', $this->guid());
        $date = array(
                'order_code' => $list['ordercode'],
                'origin_id' => $list['origin_id'],
                
                'reason' => $id['reason'],
                
                'nonce_str' => strtolower($guid),
                'timestamp' => time(),
                'openid' => '819d065e887e43c291601412c0b36586',
                'appid' => '4a3f49278abc45f9bcbccf142d5ed481'
            );
            
        ksort($date);
        
        //halt($appKey);
        $date['sign'] = $this->sign($date, $appKey);
        // dump($date);
        $url = "http://openapi.uupaotui.com/v2_0/cancelorder.ashx";
        $res =  $this->request_post($url,$date);
        $this->json_success($res);
     }
}
