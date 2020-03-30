<?php
namespace app\api\controller;
use Pay\Pay;
use think\config;
use think\Db;
use think\Request;

class Payment extends Base
{
    public function pay()
    {
        $user_id = input('post.user_id');
        if(null===$user_id){
            $this->json_error('请传过来用户编号');
        }
        //pay_type 支付方式   1支付宝  2微信  3银联
        $pay_type = input('post.pay_type');
        if(null===$pay_type){
            $this->json_error('请传过来支付方式');
        }
        $out_trade_no = input('post.out_trade_no');
        if(null===$out_trade_no){
            $this->json_error('请传过来支付交易编号');
        }

        //根据订单号，去订单金额
        $order_trade = Db::name('order_trade')->where(['out_trade_no'=>$out_trade_no])->find();
        if(null===$order_trade){
            $this->json_error('订单不存在，非法操作');
        }

        //订单是否有优惠券

        // $order_ids = $order_trade['order_ids'];

        // $orders = Db::name('order')->whereIn('id',$order_ids)->select();

        $total_amount = $order_trade['total_amount'];

        // $couponids = array_column($orders,'couponid');

        // $coupons = Db::name('coupon')->where(['is_expire'=>0])->whereIn('id',$couponids)->select();


        // if($coupons!=null){
        //     $price = 0;
        //     foreach($coupons as $ck=>$cv){
        //         $price+=$cv['sub_price'];
        //     }
        //     $total_amount = $total_amount-$price;

        // }

        if($total_amount<=0){
            $this->json_error('金额不合法');
            die;
        }
        if($pay_type==1){
            //支付宝
            $config = config('alipay');
            $pay = new \Pay\Pay($config);
            $pay->driver('alipay')->gateway('wap');
            // 支付参数
            $payOrder = [
                'out_trade_no' => $out_trade_no, // 商户订单号
                'total_amount' => $total_amount, // 支付金额
                'subject'      => '购买', // 支付订单描述
                'notify_url'   => config('alipay')['alipay']['return_url'], // 定义通知URL
            ];
            try {
                $options = $pay->driver('alipay')->gateway('wap')->apply($payOrder);
                echo $options;
            } catch (Exception $e) {
                echo "创建订单失败，" . $e->getMessage();
                die;
            }
        }else if($pay_type==2){
            //2微信
            // 支付参数
            $options = [
                'out_trade_no'     => $out_trade_no, // 订单号
                'total_fee'        => $total_amount*100, // 订单金额，**单位：分**
                'body'             => '购买', // 订单描述
                'spbill_create_ip' => getenv('HTTP_X_FORWARDED_FOR'), // 支付人的 IP
                'notify_url'       => config('alipay')['wechat']['notify_url'], // 定义通知URL
            ];
            $return_url = config('alipay')['wechat']['return_url'];
            // 实例支付对象
            $config = config('alipay');
            $pay = new \Pay\Pay($config);
            try {
                $result = $pay->driver('wechat')->gateway('wap')->apply($options, $return_url);
                //var_export($result);
                //header('Location: '.$result);
                echo $result;
            } catch (Exception $e) {
                echo "创建订单失败，" . $e->getMessage();
                die;
            }
        }


    }


    public function pay2()
    {

        /*
        $user_id = input('post.user_id');
        if(null===$user_id){
            $this->json_error('请传过来用户编号');
        }
        //pay_type 支付方式   1支付宝  2微信  3银联
        $pay_type = input('post.pay_type');
        if(null===$pay_type){
            $this->json_error('请传过来支付方式');
        }
        $out_trade_no = input('post.out_trade_no');
        if(null===$out_trade_no){
            $this->json_error('请传过来支付交易编号');
        }
        //根据订单号，去订单金额
        $order_trade = Db::name('order_trade')->where(['out_trade_no'=>$out_trade_no])->find();
        if(null===$order_trade){
            $this->json_error('订单不存在，非法操作');
        }
        $total_amount = $order_trade['total_amount'];
        if($pay_type==1){
            //支付宝
            $config = config('alipay');
            $pay = new \Pay\Pay($config);
            $pay->driver('alipay')->gateway('wap');
            // 支付参数
            $payOrder = [
                'out_trade_no' => $out_trade_no, // 商户订单号
                'total_amount' => $total_amount, // 支付金额
                'subject'      => '购买', // 支付订单描述
                'notify_url'   => config('alipay')['alipay']['return_url'], // 定义通知URL
            ];
            try {
                $options = $pay->driver('alipay')->gateway('wap')->apply($payOrder);
                echo $options;
            } catch (Exception $e) {
                echo "创建订单失败，" . $e->getMessage();
            }
        }else if($pay_type==2){
            //2微信
        }

        */

        //支付宝
//        $config = config('alipay');
//        $pay = new \Pay\Pay($config);
//        $pay->driver('alipay')->gateway('wap');
//        // 支付参数
//        $payOrder = [
//            'out_trade_no' => 'I624635298710003', // 商户订单号
//            'total_amount' => 0.01, // 支付金额
//            'subject'      => '购买', // 支付订单描述
//            'notify_url'   => config('alipay')['alipay']['return_url'], // 定义通知URL
//        ];
//        try {
//            $options = $pay->driver('alipay')->gateway('wap')->apply($payOrder);
//            echo $options;
//        } catch (Exception $e) {
//            echo "创建订单失败，" . $e->getMessage();
//        }

    }


    public function test()
    {
        //<form action="http://svn.yanjiegou.com/api/payment/pay3" method="post">
        $str = <<<EOF
        <form action="http://svn.yanjiegou.com/api/payment/pay3" method="post">
        <input type="text" value="10" name="total_fee">
        <input type="submit">
</form>
EOF;
        echo $str;

    }

    public function pay3()
    {

        // 支付参数
                $options = [
                    'out_trade_no'     => time(), // 订单号
                    'total_fee'        => input('post.total_fee'), // 订单金额，**单位：分**
                    'body'             => '订单描述', // 订单描述
                    'spbill_create_ip' => request()->ip(), // 支付人的 IP
                    'notify_url'       => config('alipay')['wechat']['notify_url'], // 定义通知URL
                ];
                $return_url = config('alipay')['wechat']['return_url'];
                // 实例支付对象
                $config = config('alipay');
                $pay = new \Pay\Pay($config);
                try {
                    $result = $pay->driver('wechat')->gateway('wap')->apply($options, $return_url);
                    //var_export($result);
                    header('Location: '.$result);
                } catch (Exception $e) {
                    echo $e->getMessage();
                }
    }


}