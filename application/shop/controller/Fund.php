<?php

namespace app\shop\controller;

use think\Db;
use think\Request;
use think\View;
use app\shop\controller\Common;

class Fund extends Common
{

    protected $now, $log, $shop, $order;

    public function _initialize()
    {
        parent::_initialize();
        $this->now = model('ShopFundNow');
        $this->log = model('ShopFundLog');
        $this->order = model('Order');
        $this->shop = model('Shop');
    }

    //申请列表
    public function nows()
    {
        if (Request::instance()->isAjax()) {
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');
            $map['a.shopid'] = SHID;
            $list = $this->now->alias('a')
                ->join('shop s', 's.id = a.shopid', 'LEFT')
                ->join('admin ad', 'ad.admin_id = a.douid', 'LEFT')
                ->field('a.*,s.name as shopname,ad.username as dousername')
                ->where($map)
                ->order("id desc")
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->each(function ($row) {
                    $row['status'] = get_status($row['status'], 'je');
                    $row['addtime'] = date('Y-m-d H:i:s', $row['addtime']);
                    $row['dotime'] = $row['dotime'] ? date('Y-m-d H:i:s', $row['dotime']) : '-';
                })->toArray();
            return ['code' => 0, 'msg' => "获取成功", 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        } else {
            $shop = Db::name('shop')->where(['id' => SHID])->find();
            $this->assign('shop_money', $shop['shop_money']);
            $this->assign('lock_money', $shop['lock_money']);
            return $this->fetch();
        }
    }

    //添加申请
    public function add()
    {
        if (Request::instance()->isAjax()) {
            $data = input('post.');
            $bank_name = input('post.bank_name');
            $banks = explode(':', $bank_name);
            $data['bank_name'] = $banks[0];
            $data['bank_number'] = $banks[1];
            $data['bank_address'] = $banks[2];
            $data['shopid'] = SHID;
            $data['addtime'] = time();
            $data['status'] = 0;
            $money = $data['money'];
            Db::startTrans();
            try {
                $res = $this->now->insert($data);
                $id = $this->now->getLastInsID();
                $shop = $this->shop->get(SHID);
                $shop->shop_money = $shop->shop_money - $money;
                if ($shop->shop_money < 0) {
                    $result['code'] = 0;
                    $result['msg'] = "提现金额大于账户余额";
                    return $result;
                }
                $shop->lock_money = $shop->lock_money + $money;
                $results = $shop->save();
                if ($res && $results != false) {
                    Db::commit();
                    $result['code'] = 1;
                    $result['msg'] = '添加成功!';
                    return $result;
                } else {
                    Db::rollback();
                    $result['code'] = 0;
                    $result['msg'] = '添加失败!';
                    return $result;
                }
            } catch (\Exception $e) {
                Db::rollback();
                $result['code'] = 0;
                $result['msg'] = '添加失败!';
                return $result;
            }
        } else {
            $banks = Db::name('shop_bank')->where(['shop_id' => SHID])->select();
            $this->assign('banks', $banks);
            //获取行业佣金比
            $shopid = SHID;
            $type=Db::name("shop")->where(['id'=>$shopid])->value('type');
            $yong=Db::name("shop_category")->where(['id'=>$type])->value('brokerage');
            $this->assign('yong',$yong);
            return $this->fetch();
        }
    }

    //资金列表
    public function logs()
    {
        if (Request::instance()->isAjax()) {
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');
            $map['shopid'] = SHID;
            $list = $this->log
                ->where($map)
                ->order("id desc")
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->each(function ($row) {
                    $row['addtime'] = date('Y-m-d H:i:s', $row['addtime']);
                    if ($row['type'] == 1) {
                        $row['money'] = "- ¥" . $row['money'];
                    } else {
                        $row['money'] = "+ ¥" . $row['money'];
                    }
                    $row['yue'] = "¥" . $row['yue'];
                    $row['type'] = get_status($row['type'], 'shop_fund_log_type');
                })->toArray();
            return ['code' => 0, 'msg' => "获取成功", 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }
        return $this->fetch();
    }

    //订单结算列表
    public function order()
    {
        if (Request::instance()->isAjax()) {
            $where['shop_id'] = SHID;
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : 5;
            $where['status'] = 5; //已完成订单
            $where['settlementId'] = 0;
            $list = $this->order
                ->where($where)
                ->order('id','desc')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->each(function ($row) {
                    switch ($row['pay_type']) {
                        case 1:
                            $row['pay_type'] = "支付宝";
                            break;
                        case 2:
                            $row['pay_type'] = "微信";
                            break;
                        case 3:
                            $row['pay_type'] = "银联";
                            break;
                    }
                    $row['add_time'] = date('Y-m-d H:i:s', $row['add_time']);
                })
                ->toArray();
            return ['code' => 0, 'msg' => '获取成功！', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        } else {
            return $this->fetch();
        }
    }
    //申请订单结算
    public function setOrderjs()
    {
        //获取订单id
        $orderid = input('id');
        //查询订单信息
        $order_info = $this->order->where(['id' => $orderid])->find();
        //组合结算订单
        $setOrder['settlementNo'] = "";
        $setOrder['settlementType'] = 1;
        $setOrder['shopId'] = $order_info['shop_id'];
        if($order_info['send_type']==1){//跑腿
            $order_info['money']=$order_info['money']-$order_info['freight'];//跑腿费用
            $setOrder['remarks']="平台扣除跑腿费用：¥".$order_info['freight'];
        }
        $setOrder['settlementMoney'] = $order_info['money'];
        $setOrder['createTime'] = date("Y-m-d H:i:s", time());       
        Db::startTrans();
        try {
            $settlementid = Db::name('settlements')->insertGetId($setOrder);
            $settlementNo = $settlementid . (fmod($settlementid, 7));
            Db::name('settlements')->where(['settlementid' => $settlementid])->update(['settlementNo' => $settlementNo]);
            //反写订单
            $this->order->where(['id' => $orderid])->update(['settlementId' => $settlementid]);
            Db::commit();
            $result['code'] = 1;
            $result['msg'] = '申请成功！';
            return $result;
        } catch (\Exception $e) {
            Db::rollback();
            $result['code'] = 0;
            $result['msg'] = '申请失败！';
            return $result;
            
        }
    }
     //more 订单结算
    public function allOrderjs()
    {
        //获取订单号
        $orderIds = input("post.ids/a");
        foreach ($orderIds as $k => $v) {
            //查询订单信息
            $order_info = $this->order->where(['id' => $v])->find();
            //组合结算订单
            $setOrder['settlementNo'] = "";
            $setOrder['settlementType'] = 1;
            $setOrder['shopId'] = $order_info['shop_id'];
            if ($order_info['send_type'] == 1) { //跑腿
                $order_info['money'] = $order_info['money'] - $order_info['freight']; //跑腿费用
                $setOrder['remarks'] = "平台扣除跑腿费用：¥" . $order_info['freight'];
            }
            $setOrder['settlementMoney'] = $order_info['money'];
            $setOrder['createTime'] = date("Y-m-d H:i:s", time());
            Db::startTrans();
            try {
                $settlementid = Db::name('settlements')->insertGetId($setOrder);
                $settlementNo = $settlementid . (fmod($settlementid, 7));
                Db::name('settlements')->where(['settlementid' => $settlementid])->update(['settlementNo' => $settlementNo]);
                //反写订单
                $this->order->where(['id' => $v])->update(['settlementId' => $settlementid]);
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                break;
            }
        }
        $result['code'] = 1;
        $result['msg'] = '申请成功！';
        return $result;
    }
    //结算信息
    public function settlements(){
        if (Request::instance()->isAjax()) {
            $where['shopId'] = SHID;
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : 5;                      
            $list = model('Settlements')
                ->where($where)
                ->order('settlementId','desc')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->each(function ($row) {
                    switch ($row['settlementType']) {
                        case 0:
                            $row['settlementType'] = "自动结算";
                            break;
                        case 1:
                            $row['settlementType'] = "手动结算";
                            break;                        
                    } 
                    if($row['settlementStatus']==1){
                        $row['settlementStatus']="已结算";
                    }else{
                        $row['settlementStatus']="未结算";
                    }                   
                })
                ->toArray();
               
            return ['code' => 0, 'msg' => '获取成功！', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        } 
    }
    //已结算订单
    public function jsOrder(){
        if (Request::instance()->isAjax()) {
            $where['a.shop_id'] = SHID;
            $where['b.settlementStatus']=1;
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : 5;                                  
            $list =model("settlements")->alias("b")
                ->join('order a','a.settlementId = b.settlementId',"LEFT")
                ->where($where)
                ->order('a.id desc')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->each(function ($row) {               
                    if($row['settlementStatus']==1){
                        $row['settlementStatus']="已结算";
                    }else{
                        $row['settlementStatus']="未结算";
                    }  
                    switch ($row['pay_type']) {
                        case 1:
                            $row['pay_type'] = "支付宝";
                            break;
                        case 2:
                            $row['pay_type'] = "微信";
                            break;
                        case 3:
                            $row['pay_type'] = "银联";
                            break;
                    }                 
                })
                ->toArray(); 
            return ['code' => 0, 'msg' => '获取成功！', 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        } 
    }

    //资金账户列表
    public  function bank()
    {
        if (Request::instance()->isAjax()) {
            $banks = Db::name('shop_bank')
                ->where(['shop_id' => SHID])
                ->select();
            foreach ($banks as &$v) {
                if ($v['type'] == 0) {
                    $v['type'] = "银行卡";
                }
            }
            return ['code' => 0, 'msg' => '获取成功', 'data' => $banks];
        } else {
            return $this->fetch();
        }
    }
    //添加账户列表
    public function addBank()
    {
        if (Request::instance()->isAjax()) {
            $data = input("post.");
            $data['shop_id'] = SHID;
            $result = Db::name('shop_bank')->insert($data);
            if ($result) {
                return ['code' => 1, 'msg' => "添加成功！"];
            } else {
                return ['code' => 0, 'msg' => "添加失败！"];
            }
        } else {
            return $this->fetch();
        }
    }
    //编辑账户信息
    public function editBank()
    {
        if (Request::instance()->isAjax()) {
            $data = input("post.");
            $result = Db::name("shop_bank")->update($data);
            if ($result) {
                return ['code' => 1, 'msg' => "修改成功！"];
            } else {
                return ['code' => 0, 'msg' => '修改失败'];
            }
        } else {
            $id = input('id');
            $banks = Db::name('shop_bank')->find($id);
            $this->assign('banks', $banks);
            return $this->fetch();
        }
    }
    //删除账户
    public function deleteBank()
    {
        $result = Db::name("shop_bank")->where(['id' => input('id'), 'shop_id' => SHID])->delete();
        if ($result) {
            return ['code' => 1, 'msg' => '删除成功！'];
        } else {
            return ['code' => 0, 'msg' => '删除失败！'];
        }
    }
        
    
    
}
