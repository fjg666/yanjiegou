<?php
namespace app\admin\controller;
use think\Db;
use think\Request;
use app\admin\controller\Common;
use Yansongda\Pay\Pay;
use Yansongda\Pay\Log;
class Order extends Common{
    protected  $model;
    public function _initialize(){
        parent::_initialize();
        $this->model = model('order');
    }
    protected $config = [

        // 支付宝支付参数
        // 沙箱模式
        'debug'       => false,
        // 应用ID
        'app_id'        =>  '2019100968222438',
        'ali_public_key'    =>  'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAvxcJ07zYwEKEs8D4XeF73x+xA/YUUAk6QHuUfzWVRic7U6IIBXiqiUsRYSVLSl/JxROd8EReFD+Al+z/429xZiO/ugjG6c3yR9Ay3QcW/jG7+VVE4ECebUvYd9udkRir6hFVyssn9FpgtBXbraaqcBa+aEcZVXT8+2AGPPcrc/e086qfvvJSvyzanZzJDDcyZoOFP158UzzZ19GJdAro1zIviqL1aREDtz0RtJb37K7ct2JuDI/3q0op1CGpbPnzw7YbVX0qkD2yATDhkKDWz/UntrwNmwMHfNDFgzokevXmeKp1EbM+4gfX8TSbyBhWCpiBiS0hoIEGIE2BThmtYQIDAQAB',
        'private_key'   =>  'MIIEpAIBAAKCAQEA7lvw4L+ZZUxvsWadX3I9FjasJe2GeMeMNYXCYWRw0quW3MeL2bBl4y7kvWVX3NwddixrMBHKt70BvtEXzYgsGc4nxGzd40ZxyKtDCBRcWF1N+901qyOpUfI0HMSpQRV4OSbSqC6h7U/Eya/CJoSCijis2OuBABXrc0htwE08bPRRBsu2P4/N2EJAArOL9mJCYW8I+2urG6EMxp2yxqRtUap8VKVHL8cKtfNNCdkxLRYBmRyZxwICWc4T1t3WuEEt1nOv9PEwH4lHzYhiGsxaAj6W8GNJU8HlOvzBYhFZicrz0ACorNa/SlfHb/tZO3CcRfMvzNScVdYjk0eEiTrzvQIDAQABAoIBAQCxLz0/BI51w70fhWUkx1nrglazlv6oF8X9H2JgXXaU1CLAGcG2367Nk1VMCOKodiOcbeZ8BC3KKcD7ZJkqGriVsi7TkA3dXcdFYTHh9qiysyE+QbEcd9Ts6nuciwA6Nkh5S4e6p3eNXget2W4cjdIwB3NNiLsLIkA1ITkcgw2Q+xlOWUxyC1hS/St2VkHmqE9gYHJUQ+ZLmqyu/3FTdz1iPbmcLoT9NszUSdmrT07q+4QZZlKJWqU2Bi8zdSPYtf8ac2aYi4frr+UyIaTg2kIDXukQVzKutOB1fuxsgPbx0cJMGss6/E0eEVtCTjoG9ZL4wJ+fcxyoGNZL5D4cIc5tAoGBAPxK5/TCV+oI3kb7O1pFKgKoSAJK6Z0jfY0vDVO4vb1Ja6G1NWdiRpNFWYq3zIXAjChokzQRFRMPYYdYhhLNJZ8+AvxgIESs6zzT2XWBG0qmqkaALObLuf/jaVHtfJ90dmq71hTAWN3Ozkvm3R1qP9wzQd902wKDn+Vt664D3C37AoGBAPHcnm9gDhihSC7MneV19FyJhKyWFeLRtHZO97ofA16MU0kcjdYf7iqWXROPOfhr7o/tVJIcSJjgaoOVTKespkJsmgqDLMhJVL+93PDX+f4bqO6SJZXFlUVsxETeMNB0TW+BEoepAENo6VGPzhHATbBlZ+rjWbohzek+szMdUc+nAoGBALsJpVEVSyvcCz3AP046/Fwf+dKJSwwOJaQnf8/TpAbSiZLGzqKofv3raeinPl7iUoYakRcGmwMYYgt/G1aQ9BVMWdZURVfkgjkELbEpV9xOFupRV/h6jJgiNhBg6gUkyC10t8+GkdtO2C35J3AJNvK+pVVOQpdokX/7r7/AaNlFAoGAbqH2LwgHKqkLtayPRVjxUCrvb2qv1DMMk1mH47Ev/1288yKGlr3AWeax6LKJV+M3Gsr69mLNqnBtCIeQqtpEqvm2dLyQDYXNqG+W0uxYRC4u1gIwAxSANWONW9svBQtOKIUoDrn1juA8abyYDHKklt2r7TvV3Vh9MgYmPmlY9N0CgYBToV4zCPPNeS67BdEy8lkVSlYtrf4s59KYywXojtNAmD8Pwk6q429yKOFAOuYKcS9nTOAAs7tJHAcuSIu3tWza2CiCQPxoVz7lbKCaWjgXZuADB5F7Dro1p5WBBYJDZlQecYdrsxSVZ0RxcW/GQnBq9UFG0BGBHM8myslvliIuaA==',
        // 支付成功通知地址
        'notify_url'  => 'http://www.yanjgcom/api/activity/notify',
        // 网页支付回跳地址
        'return_url'  => 'http://www.yanjg.com/api/activity/index',
        //编码格式
        'charset' => "UTF-8",
        //签名方式
        'sign_type'=>"RSA2",
        //支付宝网关
        'gatewayUrl' => "https://openapi.alipay.com/gateway.do",
    ];
    //订单列表
    public function lists()
    {
        $status=input('status/d');
        if(Request::instance()->isAjax()){
            $page =input('page')?input('page'):1;
            $pageSize =input('limit')?input('limit'):config('pageSize');
            $keyword=input('key');
            $where = [];
            if(!empty($keyword)){
               $where['o.order_sn|u.mobile|s.name'] = ['like','%'.$keyword.'%'];
            }
            if(!empty($status)){
                $where['o.status']=$status;
            }
            $list = $this->model->alias('o')
                ->join('users u','u.id = o.user_id','LEFT')
                ->join('shop s','s.id = o.shop_id','LEFT')
                ->field('o.*,u.id as uid,u.mobile as umobile,s.id as sid,s.name as sname')
                ->order("o.id desc")
                ->where($where)
                ->paginate(array('list_rows'=>$pageSize,'page'=>$page))
                ->each(function($row){
                    $row['statusname']=get_status($row['status'],'order_status');
                    $row['pay_type']=get_status($row['pay_type'],'pay_type');
                    $row['add_time']=date('Y-m-d H:i:s',$row['add_time']);
                })->toArray();    
            return ['code'=>0,'msg'=>"获取成功",'data'=>$list['data'],'count'=>$list['total'],'rel'=>1];
            return $result;
        }
        return $this->fetch();
    }
    /**
     * 发货
     **/
    function send(){
        if (Request::instance()->isAjax()) {
            $data=input('post.');
            if($data['send_type']==1 && $data['expresscom']==0){
               return $this->resultmsg('请选择快递公司',0); 
            }
            if(empty($data['expresssn']) && $data['send_type']==1) {
                return $this->resultmsg('请填写有效快递单号',0);
            }
            $data['status']=3;
            $data['sendtime']=time();
            if($this->model->update($data)){
                return $this->resultmsg('提交成功',1);
            }
            return $this->resultmsg('提交失败',0);
        }
        $id=input('id/d');
        $info=$this->model->where(['id'=>$id])->field('mobile,address,id')->find();
        $this->assign('info',$info);
        return $this->fetch();
    }
    /**
     * 详情
     **/
    function info(){
        $id=input('id/d');
        $info=$this->model->where(['id'=>$id])->find();
        if ($info['status'] == 7) {
            $info['shtime']=Db::name('orderrefund')->where(['order_id'=>$id])->value('add_time');
        }
        if($info['send_type']==0){
            $info['send_type']="配送";
        }elseif($info['send_type']==1){
            $info['send_type']="跑腿";
        }else{
            $info['send_type']="自取";
        }
        //地址
        $info['address']=$info['province'].$info['city'].$info['area'].$info['address'];
        $info['pay_type']=get_status($info['pay_type'],'pay_type');
        $goods=Db::name('orderGoods')->alias('og')
              ->join('goods g','g.id = og.goodsid','left')
              ->field('og.*,g.title,g.headimg')
              ->where(['og.order_sn'=>$info['order_sn']])
              ->select(); 
        $counts=0;      
        foreach ($goods as $k => $v) {
            $goods[$k]['headimg']=explode(',',$v['headimg'])[0];
            $goods[$k]['count']=$v['num']*$v['price'];
            $counts+=$goods[$k]['count'];
        }
        $info['counts']=$counts;
        $this->assign('goods',$goods);
        $this->assign('info',$info);
        return $this->fetch();
    }

    /**
     * 售后订单
     **/
    public function refund(){
        // $model=model('orderrefund')::all()->toArray();       
        // $model = $model->toArray();
        
        if(Request::instance()->isAjax()){
            $page =input('page')?input('page'):1;
            $pageSize =input('limit')?input('limit'):config('pageSize');
            $keyword=input('key');
            $where = [];
            if(!empty($keyword)){
               $where['o.order_sn|a.mobile|u.username'] = ['like','%'.$keyword.'%'];
            }
            if(!empty($status)){
                $where['o.status']=7;
            }
            $list = model('orderrefund')->alias('a')
                ->join('order o','o.id = a.order_id','LEFT')
                ->join('users u','u.id = o.user_id','LEFT')
                ->join('shop s','s.id = o.shop_id','LEFT')
                ->join('goods g','g.id = a.goods_id','LEFT')
                ->field('a.*,o.pay_type,o.add_time oadd_time,o.status as ostatus,u.username,s.id as sid,s.name as sname,g.title,g.headimg')
                ->order("o.id desc")
                ->where($where)
                ->paginate(array('list_rows'=>$pageSize,'page'=>$page))
                ->each(function($row){
                    $row['pay_type']=get_status($row['pay_type'],'pay_type');
                    $row['add_time']=date('Y-m-d H:i:s',$row['add_time']);
                    $row['oadd_time']=date('Y-m-d H:i:s',$row['oadd_time']);
                    $row['headimg'] = explode(',',$row['headimg'])[0];
                    $row['statusname'] = get_status($row['status'],'check');
                })->toArray();    
            return ['code'=>0,'msg'=>"获取成功",'data'=>$list['data'],'count'=>$list['total'],'rel'=>1];
            return $result;

        }else{
          return  $this->fetch();
        }
    //     $this->assign('list',$model);
    //     return $this->fetch();
    }

    //设置商品审核状态
    public function setrefundstatus(){
        $map['id'] =array('in',input('post.id/a'));
        $data['status']=input('post.status/d');
        $data['shop_remark']=input('post.text/s');
        $data['do_time']=time();
        if(model('orderrefund')->where($map)->update($data)!==false){
            return $this->resultmsg('设置成功！',1);
        }
        return $this->resultmsg('设置失败！',0);
    }

    /**
     * 售后详情
     **/
    function refundinfo(){
        $id=input('id/d');
        $info=model('orderrefund')->where(['id'=>$id])->find();
        $goods=Db::name('orderGoods')->alias('og')
              ->join('goods g','g.id = og.goodsid','left')
              ->field('og.*,g.title,g.headimg')
              ->where(['og.id'=>$info['og_id']])
              ->select(); 
        $counts=0; 
        $info['imgs']=explode(',',$info['imgs']);
        $info['counts']=$goods[0]['num']*$goods[0]['price'];
        $this->assign('goods',$goods);
        $this->assign('info',$info);
        return $this->fetch();
    }


    // 进行退款
    public function Tmoney()
    {
        $id = input('id');
        $out_trade_no=Db::name("order")->where(['id'=>$id])->value('out_trade_no');
        $list = Db::name('order_trade')->where('out_trade_no',$out_trade_no)->field('out_trade_no,total_amount')->find();
        if(!$list){
            return $this->resultmsg('等待审核通过',0);
        }

        $order = [
            'out_trade_no' => $list['out_trade_no'],
            'refund_amount' => number_format($list['total_amount'],2,".",""),
            'out_request_no'=>$list['out_trade_no'].round(1000,9999),//多次退款
        ];
        $result = Pay::alipay($this->config)->refund($order);        

        if($result['msg'] == 'Success'){
            $rel = Db::name('orderrefund')->where('order_id',$id)->update(['type'=>1]);
            // return $this->resultmsg('退款成功',1);
            if($rel){
                return ['code'=>1,'msg'=>"退款成功"];
            }
            

        }else{
            // return $this->resultmsg('退款失败',0);
            return ['code'=>0,'msg'=>"退款失败"];
        }
    }
    
}
