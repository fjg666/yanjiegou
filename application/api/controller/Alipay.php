<?php
namespace app\api\controller;
use Pay\Pay;
use think\config;
use think\Db;
use think\Request;

class Alipay
{
    //支付宝异步通知
    public function notify()
    {
        //支付宝
        $config = config('alipay');
        $pay = new \Pay\Pay($config);
        $data = input('post.');
        
        // $data = json_encode($data);
        if ($pay->driver('alipay')->gateway('wap')->verify($data)) {
            file_put_contents('notify.txt', "收到来自支付宝的异步通知\r\n", FILE_APPEND);
            file_put_contents('notify.txt', "订单单号：{$data['out_trade_no']}\r\n", FILE_APPEND);
            file_put_contents('notify.txt', "订单金额：{$data['total_amount']}\r\n\r\n", FILE_APPEND);
            file_put_contents('notify.txt', "订单状态：{$data['trade_status']}\r\n\r\n", FILE_APPEND);
            file_put_contents('notify.txt', "gmt_create：{$data['gmt_create']}\r\n\r\n", FILE_APPEND);
            file_put_contents('notify.txt', "gmt_payment：{$data['gmt_payment']}\r\n\r\n", FILE_APPEND);
            file_put_contents('notify.txt', "notify_time：{$data['notify_time']}\r\n\r\n", FILE_APPEND);
            file_put_contents('notify.txt', json_encode($data), FILE_APPEND);
            if($data['trade_status']=='TRADE_SUCCESS'){

                //开启事务
                Db::startTrans();
                $out_trade_no = $data['out_trade_no'];

                $order_trade = Db::name('order_trade')->where(['out_trade_no'=>$out_trade_no])->find();

                $order_ids = $order_trade['order_ids'];

                $order_sns = json_decode($order_trade['order_sns'],true);

                $order_goods = Db::name('order_goods')->whereIn('order_sn',$order_sns)->select();
                //--0拍下减库存   1付款减库存   2永不减库存
                foreach($order_goods as $mk=>$mv){
                    // Db::name('goods')->where(['id'=>$mv['goodsid'],'totalcnf'=>1])->setDec('total',$mv['num']);
                    if ($mv['sku_id'] != 0) 
                    {
                        $good = Db::name('goods')->where(['id'=>$mv['goods_id']])->find();
                        if ($good['totalcnf'] == 1) 
                        {
                            Db::name('GoodsSttrxsku')->where(['id'=>$mv['sku_id']])->setDec('number',$mv['num']);
                        }
                    }else
                    {
                        Db::name('goods')->where(['id'=>$mv['goodsid'],'totalcnf'=>1])->setDec('total',$mv['num']);
                    }
                }
                $res1 = Db::name('order_trade')->where(['id'=>$order_trade['id']])->update(['trade_status' => $data['trade_status'],'trade_no'=>$data['trade_no'],'gmt_create'=>$data['gmt_create'],'gmt_payment'=>$data['gmt_payment'],'notify_time'=>$data['notify_time']]);
                //订单状态 1.待付款   2.待发货    3.已发货   4.待评价 5.已完成  6.已关闭 7.售后 8.取消订单
                $res2 = Db::name('order')->whereIn('id',$order_ids)->update(['status'=>2,'paytime'=>time()]);
                if($order_trade && $res1 && $res2){
                    //如果全部成功,提交事务
                    Db::commit();
                }else{
                    //如果失败,回滚事务
                    Db::rollback();
                }
            }
            echo "success";		//请不要修改或删除
        }else{
            file_put_contents('notify.txt', "收到异步通知\r\n", FILE_APPEND);
            //验证失败
            echo "fail";	//请不要修改或删除
        }
    }
    public function returnurl()
    {
        echo "<script type='text/javascript'>location.href='http://wap.yanjiegou.com/ddd?id=2';</script>";
        die;
    }


    
}