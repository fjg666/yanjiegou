<?php
namespace app\api\controller;
use Pay\Pay;
use think\config;
use think\Db;
use think\Request;

class Wxpay
{
    //微信异步通知
    public function notify()
    {
        //微信
        $config = config('alipay');
        $pay = new \Pay\Pay($config);
        $data = $pay->driver('wechat')->gateway('wap')->verify(file_get_contents('php://input'));

        if ($data) {
            file_put_contents('notify.txt', "收到来自微信的异步通知\r\n", FILE_APPEND);
            file_put_contents('notify.txt', "订单单号：{$data['out_trade_no']}\r\n", FILE_APPEND);
            file_put_contents('notify.txt', "订单金额：{$data['total_fee']}\r\n\r\n", FILE_APPEND);
            // 支付通知数据获取成功
            if ($data['result_code'] == 'SUCCESS' && $data['return_code'] == 'SUCCESS') {
                //开启事务
                Db::startTrans();
                $out_trade_no = $data['out_trade_no'];
                $order_trade = Db::name('order_trade')->where(['out_trade_no'=>$out_trade_no])->find();
                $order_ids = $order_trade['order_ids'];
                $order_sns = json_decode($order_trade['order_sns'],true);
                $order_goods = Db::name('order_goods')->whereIn('order_sn',$order_sns)->select();
                //--0拍下减库存   1付款减库存   2永不减库存
                foreach($order_goods as $mk=>$mv){
                    Db::name('goods')->where(['id'=>$mv['goodsid'],'totalcnf'=>1])->setDec('total',$mv['num']);
                }
                $info = [
                    'trade_status' => $data['result_code'],
                    'trade_no'=>$data['transaction_id'],
                    //'gmt_create'=>$data['gmt_create'],
                    //'gmt_payment'=>$data['gmt_payment'],
                    'notify_time'=>$data['time_end']
                ];
                $res1 = Db::name('order_trade')->where(['id'=>$order_trade['id']])->update($info);
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
        } else {
            file_put_contents('notify.txt', "收到异步通知\r\n", FILE_APPEND);
        }

        //echo "success";
        $xml = "<xml>
              <return_code><![CDATA[%s]]></return_code>
              <return_msg><![CDATA[%s]]></return_msg>
            </xml>";
        $result = sprintf($xml,$data['return_code'],$data['return_msg']);
        echo $result;

    }
    public function returnurl()
    {
        echo "<script type='text/javascript'>location.href='http://wap.yanjiegou.com/ddd?id=2';</script>";
        die;
    }
}