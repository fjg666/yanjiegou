<?php

namespace app\admin\Controller;

use think\Db;
use think\Request;
use ensh\Leftnav;
use app\api\controller\Base;
use app\admin\controller\Common;
use think\cache\driver\RedisPro;
use app\index\controller\Wechat;
class Shop extends Common
{
    protected  $model;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('shop');
        $this->assign('logomoduleid', 111);
        $this->assign('albummoduleid', 112);
    }
    /*
     * 商家列表
     */
    public function index()
    {
        if (Request::instance()->isAjax()) {
            $keyword = input('key');
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');
            $map = [];
            if (!empty($keyword)) {
                $map['s.name'] = array('like', '%' . $keyword . '%');
            }
            $list = $this->model->alias('s')
                ->join('bigshop bs', 'bs.id = s.bshopid', 'LEFT')
                ->where($map)
                ->order('s.sort desc')
                ->field('s.*,bs.name as bname')
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->each(function ($row) {
                    $row['statusname'] = get_status($row['status'], 'check');
                    // $row['lock_name'] = get_status($row['is_lock'], 'shop_is_lock');
                    $row['myaddress'] = $row['province'] . $row['city'] . $row['area'] . $row['street'] . $row['address'];
                })
                ->toArray();
            $rsult['code'] = 0;
            $rsult['msg'] = "获取成功";
            $lists = $list['data'];
            $rsult['data'] = $lists;
            $rsult['count'] = $list['total'];
            $rsult['rel'] = 1;
            return $rsult;
        } else {
            return $this->fetch();
        }
    }
    public function add()
    {
        if (Request::instance()->isAjax()) {
            $data = input('post.');
            $msg = $this->validate($data, 'Shop');
            if ($msg != 'true') {
                return $result = ['code' => 0, 'msg' => $msg];
            }
            $ad_data = ['username' => $data['admin_name'], 'pwd' => $data['admin_pwd']];
            $msg = $this->validate($ad_data, 'ShopAdmin');
            if ($msg != 'true') {
                return $result = ['code' => 0, 'msg' => $msg];
            }
            $data['shortname'] = GetShortName($data['name']);
            $res = model('shop')::creatadmin($data);
            if ($res) {
                $result['code'] = 1;
                $result['msg'] = '添加商家成功!';
                $result['url'] = url('admin/shop/index');
                return $result;
            } else {
                $result['code'] = 0;
                $result['msg'] = '添加商家失败!';
                $result['url'] = url('admin/shop/index');
                return $result;
            }
        } else {
            $arealist = Base::provice();
            $this->assign('arealist', $arealist);
            $bshop = model('bigshop')->field('id,name')->select();
            $this->assign('bshop', $bshop);
            $shopcategory=Db::name('shop_category')->select();
            $this->assign('shopcategory',$shopcategory);
            return $this->fetch();
        }
    }
    //获取子地区
    public function getchildarea()
    {
        $parent_id = request()->post('parent_id');
        $putype = request()->post('putype');
        Base::getchildareamy($parent_id, $putype);
    }
    //商家审核
    public function uncheck()
    {
        if (Request::instance()->isAjax()) {
            $keyword = input('key');
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');
            $map = [];
            //状态  1 审核中   2 审核通过  3  审核失败
            $map['status'] = 1;
            if (!empty($keyword)) {
                $map['name'] = array('like', '%' . $keyword . '%');
            }
            //$list=db('bigshop')->where($map)->order('sort desc')->paginate(array('list_rows'=>$pageSize,'page'=>$page))->toArray();
            $list = $this->model->where($map)->order('sort desc')->paginate(array('list_rows' => $pageSize, 'page' => $page))->toArray();
            foreach ($list['data'] as $k => $v) {
                $list['data'][$k]['shoplogo'] = '/' . $v['shoplogo'];
                $list['data'][$k]['myaddress'] = $v['province'] . $v['city'] . $v['area'] . $v['street'] . $v['address'];
            }
            $rsult['code'] = 0;
            $rsult['msg'] = "获取成功";
            $lists = $list['data'];
            //dump($list);
            $rsult['data'] = $lists;
            $rsult['count'] = $list['total'];
            $rsult['rel'] = 1;
            return $rsult;
        } else {
            return $this->fetch();
        }
    }
    //审核原因
    public function verify()
    {
        $result = [];
        if (Request::instance()->isAjax()) {
            $id = input('post.id');
            $verify_reason = input('post.verify_reason');
            $status = input('post.status');
            $data = [
                'verify_reason' => $verify_reason,
                'status' => $status
            ];
            $res = $this->model->save($data, ['id' => $id]);
            if ($res) {
                $result['code'] = 200;
                $result['msg'] = 'ok';
                echo json_encode($result, true);
            } else {
                $result['code'] = 0;
                $result['msg'] = 'no';
                echo json_encode($result, true);
            }
        }
    }
    public function edit()
    {
        if (Request::instance()->isAjax()) {
            $data = input('post.');
            unset($data['upfile']);

            $count = count($data['headimg']); //获取传过来有几张图片
            if ($count) {
                $data['headimg'] = implode(',', $data['headimg']);
            }
            $data['shortname'] = GetShortName($data['name']);
            $msg = $this->validate($data, 'Shop');
            if ($msg != 'true') {
                return $result = ['code' => 0, 'msg' => $msg];
            }
            $res = $this->model->save($data, ['id' => input('post.id')]);
            if ($res) {
                 if($data['status']==2){
                    $this->sendMsd($data['id'],2);
                }
                $result['code'] = 1;
                $result['msg'] = '修改商家成功!';
                $result['url'] = url('admin/shop/index');
                return $result;
            } else {
                $result['code'] = 0;
                $result['msg'] = '修改商家失败!';
                $result['url'] = url('admin/shop/index');
                return $result;
            }
        } else {
            $id = input('id');
            $info = $this->model->where(array('id' => $id))->find()->toArray();
            $headimg = explode(',', $info['headimg']);
            foreach ($headimg as $k => $v) {
                if (!is_object($v)) {
                    $info['src'][] = $v;
                }
            }
            if(!empty($info['yyzz'])){
                $info['yyzz'] = explode(',',$info['yyzz']);
            }
            if(!empty($info['identity_photo'])){
                $info['identity_photo'] = explode(',',$info['identity_photo']);
            }
            $this->assign('info', $info);           
            $arealist = Base::provice();
            $this->assign('arealist', $arealist);
            $bshop = model('bigshop')->field('id,name')->select();
            $this->assign('bshop', $bshop);
             $shopcategory=Db::name('shop_category')->select();
            $this->assign('shopcategory',$shopcategory);
            return $this->fetch();
        }
    }
    /*
     * 设置商家状态
     */
    public function bigshopState()
    {
        $id = input('post.id');
        $status = input('post.status');
        $info = db('bigshop')->where('id=' . $id)->update(['status' => $status]);
        if ($info) {
            $result['code'] = 1;
            $result['msg'] = '设置成功!';
        } else {
            $result['code'] = 0;
            $result['msg'] = '设置失败!';
        }
        return $result;
    }
    /*
     * 排序
     */
    public function listorder()
    {
        $model = db('bigshop');
        $data = input('post.');
        $model->update($data);
        $result = ['msg' => '排序成功！', 'url' => url('shop/index'), 'code' => 1];
        return $result;
    }
    /*
     * 单个删除
     */
    public function listDel()
    {
        $id = input('post.id');
        $res = $this->model->where('id', $id)->delete();
        if ($res) {
        	 Db::name('ShopAuthGroup')->where(['shopid'=>$id])->delete();
            Db::name('ShopAdmin')->where(['sid'=>$id])->delete();
            return ['code' => 1, 'msg' => '删除成功！'];
        } else {
            return ['code' => 0, 'msg' => '删除失败！'];
        }
    }
    public function lock(){
        $id=input('post.id');
        $is_lock=input('post.is_lock');
        if($is_lock==1){
            $res = $this->model->where('id', $id)->setField('is_lock',0);
             $lock_send=$this->model->where('id',$id)->value('lock_send');
            if($lock_send==0){
                $this->sendMsd($id,1);
                $res = $this->model->where('id', $id)->setField('lock_send',1);
            }
        }else{
            $res = $this->model->where('id', $id)->setField('is_lock',1);
        }
        if ($res) {
            return ['code' => 1, 'msg' => '设置成功！'];
        } else {
            return ['code' => 0, 'msg' => '设置失败！'];
        }
    }
      //模板消息
     public function sendMsd($shop_id,$type=1){   //1账号2审核     
        $shop_info=Db::name('shop')->where(['id'=>$shop_id])->find();
        $templateId1="XvLf3H2yxHLwljqO2VxKkXWECZ74aliaTLlQPamFgu4";
        $templateId2="kpw3FN35YMW3IcPKqruC6cSY5EnAROeDLwp2sKh_WKo";
        $openid=$shop_info['openid'];
        $url="";
        $data1=array(
            array(
                'value'=>"商家申请已审核成功！",
                "color"=>"#173177"
            ),
            array(
                'value'=>$shop_info['phone'],
                "color"=>"#173177"
            ),
            array(
                'value'=>"身份证后六位",
                "color"=>"#173177"
            ),
        );
        $remark1=array(
            'value'=>"请妥善保管账号密码,商家登录网址：http://svn.yanjiegou.com/shop",
            "color"=>"#173177"
        );
        $data2=array(
            array(
                'value'=>"商家申请已审核成功！",
                "color"=>"#173177"
            ),
            array(
                'value'=>$shop_info['name'],
                "color"=>"#173177"
            ),
            array(
                'value'=>date("Y-m-d H:i:s"),
                "color"=>"#173177"
            ),
        );
        $remark2=array(
            'value'=>"欢迎您的加盟！",
            "color"=>"#173177"
        );
        if($type==1 && !empty($shop_info['openid'])){
            $info =Wechat::templateMessageSend($openid, $templateId1, $url, $data1, $remark1);
        }elseif($type==2  && !empty($shop_info['openid'])){
            $info =Wechat::templateMessageSend($openid, $templateId2, $url, $data2, $remark2);
        }
        if(!empty($shop_info['openid'])){
            $result=Wechat::sendMes($info);
        }
    }
    /*
     * 多个删除
     */
    public function delAll()
    {
        $id = input('post.ids/a');
        $id = implode(",", $id);
        $model = db('shop');
        $model->where("id in ($id)")->delete();
         Db::name('ShopAuthGroup')->where("shopid in ($id)")->delete();
        Db::name('ShopAdmin')->where("sid in ($id)")->delete();
        $result['code'] = 1;
        $result['msg'] = '删除成功！';
        return $result;
    }
    //申请列表
    public function nows()
    {
        if (Request::instance()->isAjax()) {
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');
            $map = [];
            $keywords = input('key');
            if (!empty($keywords)) {
                $map['s.name|a.bank_number'] = array('like', "%" . $keywords . "%");
            }
            $status = input('status');
            if (!empty($status) || $status == '0') {
                $map['a.status'] = array('eq', $status);
            }
            $list = model('ShopFundNow')->alias('a')
                ->join('shop s', 's.id = a.shopid', 'LEFT')
                ->join('shop_category c','s.type =c.id',"LEFT")
                ->join('admin ad', 'ad.admin_id = a.douid', 'LEFT')
                ->field('a.*,s.name as shopname,ad.username as dousername,c.shop_category,c.brokerage')
                ->where($map)
                ->order("id desc")
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->each(function ($row) {
                    $row['statusname'] = get_status($row['status'], 'je');
                    $row['addtime'] = date('Y-m-d H:i:s', $row['addtime']);
                    $row['dotime'] = $row['dotime'] ? date('Y-m-d H:i:s', $row['dotime']) : '-';
                    $row['brokerage']=$row['brokerage']."%";
                })->toArray();
            return ['code' => 0, 'msg' => "获取成功", 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        }
        return $this->fetch();
    }
    public function fundnowdo()
    {
         $data['id'] = input('post.id/d');
        $data['status'] = input('post.status/d');        
        $data['info'] = input('post.info/s');       
       if($data['status']==2){
        $data['pzimg'] = input('post.pzimg/s'); 
        $data['tmoney'] = input('post.tmoney'); 
       }
        //获取商户信息
        $apply_info = Db::name("shop_fund_now")->where(['id' => $data['id']])->find($data['id']);
        $shopid = $apply_info['shopid'];
        $shop = model('shop')->get($shopid);
        $shop->lock_money = $shop->lock_money - $apply_info['money'];
        if ($shop->lock_money < 0) {
            $result['code'] = 0;
            $result['msg'] = "冻结资金异常";
            return $result;
        }
        if ($data['status'] == 1) {
            $shop->shop_money = $shop->shop_money + $apply_info['money'];
        }
        $data['douid'] = session('seadmininfo')['aid'];
        $data['dotime'] = time();    
         
        Db::startTrans();
        try {
            if (model('ShopFundNow')->update($data) && $shop->save()) {
                Db::commit();
                if ($data['status'] != 1) {
                    $logs['shopid'] = $shopid;
                    $logs['money'] = $apply_info['money'];
                    $logs['addtime'] = time();
                    $logs['note'] = "提现申请单号" . $apply_info['id'] . "申请提现¥" . $apply_info['money']."提现到账：¥".$data['tmoney'];
                    $logs['type'] = 1;
                    $logs['yue'] = $shop->shop_money;
                    model('ShopFundLog')->insert($logs);
                }
                $result['code'] = 1;
                $result['msg'] = '成功！';
                return $result;
            } else {
                Db::rollback();
                $result['code'] = 0;
                $result['msg'] = '失败！';
                return $result;
            }
        } catch (\Exception $e) {
            Db::rollback();
            $result['code'] = 0;
            $result['msg'] = '失败！';
            return $result;
        }
    }

    /**
     * 商家管理员
     **/
    public function admin()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $admin_data = [
                'username' => $data['admin_name'],
                'pwd' => authcode($data['admin_pwd']),
            ];
            $map = [
                'type' => 2,
                'sid' => $data['sid'],
                'admin_id' => $data['aid'],
            ];
            if (Db::name('ShopAdmin')->where($map)->update($admin_data)) {
                $result['code'] = 1;
                $result['msg'] = '成功！';
                return $result;
            }
            $result['code'] = 0;
            $result['msg'] = '失败！';
            return $result;
        }
        $sid = input('get.id/d');
        $info = Db::name('ShopAdmin')->alias('a')
            ->join('ShopAuthGroup s', 's.group_id = a.group_id', 'LEFT')
            ->where(['a.type' => 2, 'sid' => $sid, 'is_super' => 1])->field('a.admin_id,a.sid,s.group_id,username,rules')->find();
        $this->assign('info', $info);
        return $this->fetch();
    }
    //商家订单结算
    public function settlements()
    {
        if (Request::instance()->isAjax()) {
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');
            $map = [];           
            $list = model("settlements")->alias('a')
                    ->join("shop b", "a.shopId=b.id", "LEFT")
                    ->join("shop_category d","d.id=b.type","LEFT")
                    ->join("order c","c.settlementId=a.settlementId","LEFT")
                    ->field("a.*,b.name,c.freight,c.send_type,d.shop_category,d.brokerage")
                    ->where($map)
                    ->paginate(array('list_rows' => $pageSize, 'page' => $page))                   
                    ->toArray();
            foreach($list['data'] as &$v){
                if($v['send_type']==0){
                    $v['send_type']="普通配送";
                }elseif($v['send_type']==1){
                    $v['send_type']="跑腿";
                }else{
                    $v['send_type']="自取";
                }
                $v['brokerage']=$v['brokerage']."%";
            }
            return ['code' => 0, 'msg' => "获取成功", 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        } else {
            return $this->fetch();
        }
    }
    //确定订单结算
    public function dosettlements(){
        $id =input("id");
        $setInfo=Db::name('settlements')->where(['settlementId'=>$id])->find();
        //拼接数据
        $data['settlementType']=1;
        $data['backMoney']=$setInfo['settlementMoney'];
        $data['settlementStatus']=1;
        $data['settlementTime']=date("Y-m-d H:i:s",time());
        Db::startTrans();
        try {
            $results=Db::name("settlements")->where(['settlementId'=>$id])->update($data);
            $shop = model('shop')->get($setInfo['shopId']);
            $shop->shop_money=$shop->shop_money+$setInfo['settlementMoney'];            
            if ($results && $shop->save()) {
                $logs['shopid'] = $setInfo['shopId'];
                $logs['money'] = $setInfo['settlementMoney'];
                $logs['addtime'] = time();
                $logs['note'] = "订单结算单号：" . $setInfo['settlementNo'] . "结算金额¥" . $setInfo['settlementMoney'];
                $logs['type'] = 0;
                $logs['yue'] = $shop->shop_money;
                model('ShopFundLog')->insert($logs);
                Db::commit();                
                $result['code'] = 1;
                $result['msg'] = '成功！';
                return $result;
            } else {
                Db::rollback();
                $result['code'] = 0;
                $result['msg'] = '失败！';
                return $result;
            }
        } catch (\Exception $e) {
            Db::rollback();
            $result['code'] = 0;
            $result['msg'] = '失败！';
            return $result;
        }

    }
    //商家分类
    public function shopCategoryList(){
        if (Request::instance()->isAjax()) {
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');                   
            $list = Db::name('shop_category')                    
                    ->paginate(array('list_rows' => $pageSize, 'page' => $page))                   
                    ->toArray();
            return ['code' => 0, 'msg' => "获取成功", 'data' => $list['data'], 'count' => $list['total'], 'rel' => 1];
        } else {
            return $this->fetch();
        }
    }
    //添加商家分类
    public function addShopCategory(){
        if (Request::instance()->isAjax()) {
            $data = input('post.');           
            $res =Db::name('shopCategory')->insert($data);
            if ($res) {
                $result['code'] = 1;
                $result['msg'] = '添加分类成功!';
                $result['url'] = url('admin/shop/shopCategoryList');
                return $result;
            } else {
                $result['code'] = 0;
                $result['msg'] = '添加分类失败!';
                $result['url'] = url('admin/shop/shopCategoryList');
                return $result;
            }
        } else {           
            return $this->fetch();
        }
    }
   
    //编辑商家分类
    public function editShopCategory(){
        if (Request::instance()->isAjax()) {
            $data = input('post.');           
            $res =Db::name('shopCategory')->update($data);
            if ($res) {
                $result['code'] = 1;
                $result['msg'] = '编辑分类成功!';
                $result['url'] = url('admin/shop/shopCategoryList');
                return $result;
            } else {
                $result['code'] = 0;
                $result['msg'] = '编辑分类失败!';
                $result['url'] = url('admin/shop/shopCategoryList');
                return $result;
            }
        } else {    
            $id=input('id');//分类id
            $shop_category_info=Db::name('shop_category')->where(['id'=>$id])->find();
            $this->assign('shop_category_info',$shop_category_info);       
            return $this->fetch();
        }
    }
    //删除商家分类
    public function delShopCategory(){
        $id = input('post.id');
        Db::name('shop_category')->where('id',$id)->delete();
        $result['code'] = 1;
        $result['msg'] = '删除成功！';
        return $result;
    }

    //商家提现处理
    public function shopFunAgree(){
        $id=input('id');
        $fun_new=Db::name("shop_fund_now")->where(['id'=>$id])->find();
        $this->assign('fun_new',$fun_new);
        return $this->fetch();
    }
   
}
