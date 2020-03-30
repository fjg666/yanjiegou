<?php
namespace app\shop\controller;
use think\Db;
use think\Request;
use think\View;
use app\shop\model\Order as Od;
use app\shop\controller\Common;
class Order extends Common{
    protected  $model;
    public function _initialize(){
        parent::_initialize();
        $this->model = model('order');
    }
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
            $where['o.shop_id']=SHID;
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
           
        }
        return $this->fetch();
    }
    /**
     * 发货
     **/
    function send(){
        if (Request::instance()->isAjax()) {
            $data=input('post.');
          //halt($data);
            if($data['send']==1 && !$data['expresscom']){
               return $this->resultmsg('请选择快递公司',0); 
            }
            if(empty($data['expresssn']) && $data['send']==1) {
                return $this->resultmsg('请填写有效快递单号',0);
            }
            $list['status']=3;
            $list['sendtime']=time();
            $list['expresssn'] = $data['expresssn'];
            $list['expresscom'] = $data['expresscom'];
            $list['id'] = $data['id'];
            if($this->model->update($list)){
                return $this->resultmsg('提交成功',1);
            }
            return $this->resultmsg('提交失败',0);
        }
        $id=input('id/d');
        $info=$this->model->where(['id'=>$id])->field('mobile,address,id,city,province,area')->find();
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
        // $info['express_company']=config('system.express_company')[$info['expresscom']]['statusname'];
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
        
        if ($info['send_type'] == 0) {
            $info['send_type'] = "快递配送";
        }elseif ($info['send_type'] == 1) {
            $info['send_type'] = "专业配送";
        }elseif ($info['send_type'] == 2) {
            $info['send_type'] = "到店自取";
        }



        $this->assign('goods',$goods);
        $this->assign('info',$info);
        return $this->fetch();
    }

    /**
     * 售后订单
     **/
    public function refund(){
        $model=model('orderrefund');
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
            $where['o.shop_id']=SHID;
            $list = $model->alias('a')
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
                    $row['headimg'] = '/'.explode(',',$row['headimg'])[0];
                    $row['statusname'] = get_status($row['status'],'check');
                })->toArray();    
            return ['code'=>0,'msg'=>"获取成功",'data'=>$list['data'],'count'=>$list['total'],'rel'=>1];
            return $result;
        }
        return $this->fetch();
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
    
     // 发布配送订单
    public function pei()
    {
        $id = input('post.id');
        $list = Db::name('order')->where('id',$id)->find();
        $appKey ='c6832bdc4c4f4886b06df9f1043444dc';
        $guid = str_replace('-', '', $this->guid());
        $date = array(
                'price_token' => $list['price_token'],
                'order_price' => $list['order_price'],
                'balance_paymoney' => $list['balance_paymoney'],
                'receiver' => $list['getusername'],
                'receiver_phone' => $list['mobile'],
                'push_type' => 0,
                'special_type' => 0,
                'callme_withtake' => 0,
                'nonce_str' => strtolower($guid),
                'timestamp' => time(),
                'openid' => '819d065e887e43c291601412c0b36586',
                'appid' => '4a3f49278abc45f9bcbccf142d5ed481'
            );
        ksort($date);
        //halt($appKey);
        $date['sign'] = $this->sign($date, $appKey);
        $url = "http://openapi.uupaotui.com/v2_0/addorder.ashx";
        $res =  $this->request_post($url,$date);
        $res = json_decode($res,true);
        if($res['return_code'] == 'ok'){
            $rel = Db::name('shy_order')->where('id',$id)->update(['ordercode' => $res['ordercode'],'origin_id' => $res['origin_id']]);
            if($rel){
                return $this->resultmsg('发布订单成功',1);
            }else{
                return $this->resultmsg('发布订单失败',0);
            }
        }
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
    
    
}
