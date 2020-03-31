<?php
namespace app\api\controller;
use app\api\model\Couponlog;
use think\Db;
use think\Request;
//优惠券
class Coupon extends Base
{
    public function index()
    {

        $user_id = input('post.user_id');
        if(null===$user_id){
            $this->json_error('请传过来用户编号');
            die;
        }
        //当前的页码
        $p = empty(input('post.p')) ?1:input('post.p');
        //每页显示的数量
        $rows = empty(input('post.rows'))?10:input('post.rows');

        //type_id优惠券类型  1 平台优惠券 2 商家优惠券
        $type_id = empty(input('post.type_id')) ?1:input('post.type_id');
        if($type_id==1){
            //查询用户是否已经领取了优惠券
            $couponlogs = Db::name('couponlog')->alias('clog')
                ->join('__COUPON__ c','c.id=clog.coupon_id','LEFT')
                ->where('clog.user_id',$user_id)
                ->where('c.type_id',$type_id)   //优惠券类型  1 平台优惠券 2 商家优惠券
                ->field('clog.coupon_id')
                ->select();

            $coupon_ids = array_column($couponlogs,'coupon_id');

            $data = db('coupon')
                ->where(['type_id'=>$type_id])
                ->order('sort asc,id desc')
                ->field('id,type_id,name,min_price,sub_price,begin_time,end_time,add_time,is_expire,special')
                ->select();

            foreach($data as $k=>$v){
                $data[$k]['time'] = date('m.d');
                $data[$k]['begin_time'] = date('Y-m-d H:i:s',$v['begin_time']);
                $data[$k]['end_time'] = date('Y-m-d H:i:s',$v['end_time']);
                $data[$k]['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
                //是否领取过了
                if(in_array($v['id'],$coupon_ids)){
                    $data[$k]['is_lingqu'] = 1;
                }else{
                    $data[$k]['is_lingqu'] = 0;
                }
            }

        }else{
            //查询用户是否已经领取了优惠券
            $couponlogs = Db::name('couponlog')->alias('clog')

                ->join('__COUPON__ c','c.id=clog.coupon_id','LEFT')
                ->where('clog.user_id',$user_id)
                ->where('c.type_id',$type_id)   //优惠券类型  1 平台优惠券 2 商家优惠券
                ->field('clog.coupon_id')
                ->select();

            $coupon_ids = array_column($couponlogs,'coupon_id');

            /*
            $couponsmodel = new \app\api\model\Coupon();
            $coupons = $couponsmodel->alias('c')
                ->join('__SHOP__ s','s.id=c.shop_id','LEFT')
                ->where(['c.type_id'=>$type_id])
                ->order('c.sort asc,c.id desc')
                ->field('c.*,s.id as sid,s.name as sname,s.shoplogo')
                ->page($p,$rows)
                ->select();
            */
            $coupons = Db::name('coupon')->where(['type_id'=>$type_id])->order('sort asc,id desc')->page($p,$rows)->select();

            $shop_ids = array_unique(array_column($coupons,'shop_id'));
            $data = Db::name('shop')->where('id','in',$shop_ids)->field('id,name,shoplogo')->select();
            foreach($coupons as $k=>$v){
                $coupons[$k]['time'] = date('m.d');
                $coupons[$k]['begin_time'] = date('Y-m-d H:i:s',$v['begin_time']);
                $coupons[$k]['end_time'] = date('Y-m-d H:i:s',$v['end_time']);
                $coupons[$k]['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
                //是否领取过了
                if(in_array($v['id'],$coupon_ids)){
                    $coupons[$k]['is_lingqu'] = 1;
                }else{
                    $coupons[$k]['is_lingqu'] = 0;
                }
            }

            

            $info = [];
            foreach($data as $sk=>$sv){
                $data[$sk]['shoplogo'] = $this->domain().$sv['shoplogo'];
                foreach($coupons as $ck=>$cv){
                    if($sv['id']==$cv['shop_id']){

                        $info['id'] = $cv['id'];
                        $info['type_id'] = $cv['type_id'];
                        $info['name'] = $cv['name'];
                        $info['min_price'] = $cv['min_price'];
                        $info['sub_price'] = $cv['sub_price'];
                        $info['begin_time'] = $cv['begin_time'];
                        $info['end_time'] = $cv['end_time'];
                        $info['add_time'] = $cv['add_time'];
                        $info['total_count'] = $cv['total_count'];
                        $info['sort'] = $cv['sort'];
                        $info['rule'] = $cv['rule'];
                        $info['is_expire'] = $cv['is_expire'];
                        $info['special'] = $cv['special'];
                        $info['time'] = $cv['time'];
                        $info['is_lingqu'] = $cv['is_lingqu'];

                        $data[$sk]['coupons'][] = $info;
                    }
                }
            }

        }

        $this->json_success($data);

    }

    //用户领取优惠券
    public function receive()
    {
        $user_id = input('post.user_id');
        if(empty($user_id)){
            $this->json_error('请传过来用户编号');
            die;
        }

        $coupon_id = input('post.coupon_id');
        if(empty($coupon_id)){
            $this->json_error('请传过来优惠券编号');
            die;
        }

        //领取之前判断是否已经领取过了
        $coupon = Couponlog::where(['user_id'=>$user_id,'coupon_id'=>$coupon_id])->find();
        if(!empty($coupon)){
            $this->json_error('你已经领取过，不要重复领取');
            die;
        }
        $data = [
            'user_id'=>$user_id,
            'coupon_id'=>$coupon_id,
            'add_time'=>time(),
            'receive_time'=>time()
        ];
        $total_count = Db::name('Coupon')->where('id', $coupon_id)->value('total_count');
        if (!$total_count || $total_count <= 0) {
            $this->json_error('抱歉优惠卷暂无');
            die;
        }
        $id = Couponlog::insertGetId($data);
        if($id){
            // 卷减一
            Db::name('Coupon')->where('id', $coupon_id)->setDec('total_count');
            $couponlog = Couponlog::find($id);
            $this->json_success($couponlog,'领取成功');
            die;
        }else{
            $this->json_error('领取失败');
            die;
        }
    }

    //我的优惠券列表
    public function mycoupon()
    {
        //当前的页码
        $p = empty(input('post.p')) ?1:input('post.p');
        //每页显示的数量
        $rows = empty(input('post.rows'))?10:input('post.rows');

        //type_id优惠券类型  1 平台优惠券 2 商家优惠券
        $type_id = empty(input('post.type_id')) ?1:input('post.type_id');
        $user_id = input('post.user_id');
        if(null===$user_id){
            $this->json_error('请传过来用户编号');
            die;
        }

      
      
      	/*---chen*/
        // 平台优惠券加
        $shi_time = time();
        $hd_coupon = Db::name('coupon')->where('type_id',1)->where('begin_time','elt',$shi_time)->where('end_time','gt',$shi_time)->select();
        foreach ($hd_coupon as $hd_k => $hd_v) {
            $find = Db::name('couponlog')->where('user_id',$user_id)->where('coupon_id',$hd_v['id'])->find();
            if (!$find) {
                $hd_log = [
                    'user_id'   =>$user_id,
                    'coupon_id' =>$hd_v['id'],
                    'add_time'  =>time(),
                    'receive_time'=>time()
                ];
                Db::name('couponlog')->insert($hd_log);
            }
        }
        /*---chen*/
      
      
      
      
        $data = [];

        if($type_id==1){
            $couponlogmodel = new Couponlog();
            $coupons = $couponlogmodel->alias('clog')
                ->join('__COUPON__ c','c.id=clog.coupon_id','LEFT')
                ->where(['c.type_id'=>$type_id,'clog.user_id'=>$user_id,'clog.is_use'=>0])
                ->order('clog.id desc')
                ->field('clog.id as clogid,clog.user_id,clog.is_use,clog.receive_time,c.*')
                ->page($p,$rows)
                ->select();
			foreach ($coupons as $key => $value) {
                $coupons[$key]['add_time']=date('Y-m-d H:i:s',$value['add_time']);
                $coupons[$key]['begin_time']=date('Y-m-d H:i:s',$value['begin_time']);
                $coupons[$key]['end_time']=date('Y-m-d H:i:s',$value['end_time']);
            }
            $data = $coupons;
        }else{

            $coupons = Db::name('couponlog')->alias('clog')
                ->join('__COUPON__ c','c.id=clog.coupon_id','LEFT')
                ->join('__SHOP__ s','s.id=c.shop_id','LEFT')
                ->where(['c.type_id'=>$type_id,'clog.user_id'=>$user_id,'clog.is_use'=>0])
                ->order('c.is_expire desc,clog.id desc')
                ->field('clog.id as clogid,clog.user_id,clog.is_use,clog.receive_time,c.*,s.id as sid,s.name as sname,s.shoplogo')
                ->page($p,$rows)
                ->select();


            $shop_ids = array_column($coupons,'shop_id');
            $shops = Db::name('shop')->whereIn('id',$shop_ids)->field('id as shopid,name as sname,shoplogo')->select();




            $mycoupons = [];
            foreach($shops as $sk=>$sv){
                $data[$sk]['shopid'] = $sv['shopid'];
                $data[$sk]['sname'] = $sv['sname'];
                $data[$sk]['shoplogo'] = $this->domain().$sv['shoplogo'];
                foreach($coupons as $kk=>$vv){
                    if($sv['shopid']==$vv['shop_id']){
                        $mycoupons['clogid'] = $vv['clogid'];
                        $mycoupons['user_id'] = $vv['user_id'];
                        $mycoupons['is_use'] = $vv['is_use'];
                        $mycoupons['receive_time'] = date('Y-m-d H:i:s',$vv['receive_time']);
                        $mycoupons['id'] = $vv['id'];
                        $mycoupons['type_id'] = $vv['type_id'];
                        $mycoupons['name'] = $vv['name'];
                        $mycoupons['min_price'] = $vv['min_price'];
                        $mycoupons['sub_price'] = $vv['sub_price'];
                        $mycoupons['begin_time'] = date('Y-m-d H:i:s',$vv['begin_time']);
                        $mycoupons['end_time'] = date('Y-m-d H:i:s',$vv['end_time']);
                        $mycoupons['add_time'] = date('Y-m-d H:i:s',$vv['add_time']);
                        $mycoupons['total_count'] = $vv['total_count'];
                        $mycoupons['sort'] = $vv['sort'];
                        $mycoupons['is_expire'] = $vv['is_expire'];
                        $mycoupons['shop_id'] = $vv['shop_id'];
                        $mycoupons['shop_name'] = $vv['shop_name'];
                        $mycoupons['special'] = $vv['special'];
                        $mycoupons['sid'] = $vv['sid'];
                        $mycoupons['sname'] = $vv['sname'];
                        $mycoupons['shoplogo'] = $this->domain().$vv['shoplogo'];
                        $data[$sk]['coupons'][] = $mycoupons;
                    }
                }

            }

        }



        $this->json_success($data);

    }
    //商家优惠券
    public function shop()
    {
        //当前的页码
        $p = empty(input('post.p')) ?1:input('post.p');
        //每页显示的数量
        $rows = empty(input('post.rows'))?10:input('post.rows');


        $shop_id = input('post.shop_id');
        if(null===$shop_id){
            $this->json_error('请传过来商家编号');
            die;
        }

        //查询用户是否已经领取了优惠券
        $user_id = input('post.user_id');
        if(null===$user_id){
            $this->json_error('请传过来用户编号');
            die;
        }
        $couponlogs = Db::name('couponlog')->alias('clog')

            ->join('__COUPON__ c','c.id=clog.coupon_id','LEFT')
            ->where('clog.user_id',$user_id)
            ->where('c.shop_id',$shop_id)            
            ->where('c.type_id',2)   //优惠券类型  1 平台优惠券 2 商家优惠券
            ->field('clog.coupon_id')
            ->select();

        $coupon_ids = array_column($couponlogs,'coupon_id');


        $couponsmodel = new \app\api\model\Coupon();
        $coupons = $couponsmodel->alias('c')
            ->join('__SHOP__ s','s.id=c.shop_id','LEFT')
             // ->where('c.end_time',">",time())
            ->where(['c.shop_id'=>$shop_id])
            ->where('c.type_id',2)
            ->order('c.sort asc,c.id desc')
            ->field('c.*,s.id as sid,s.name as sname,s.shoplogo')
            ->page($p,$rows)
            ->select();



        foreach($coupons as $k=>$v){
            //是否领取过了
            if(in_array($v['id'],$coupon_ids)){
                // $coupons[$k]['is_lingqu'] = 1;
                unset($coupons[$k]);


            }else{
                $coupons[$k]['is_lingqu'] = 0;

                $coupons[$k]['time'] = date('m.d');
                $coupons[$k]['begin_time'] = date('Y-m-d H:i:s',$v['begin_time']);
                $coupons[$k]['end_time'] = date('Y-m-d H:i:s',$v['end_time']);
                $coupons[$k]['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
                $coupons[$k]['shoplogo'] = $this->domain().$v['shoplogo'];
            }
        }



        $this->json_success($coupons);

    }

    //清空商家失效优惠卷
    public function del_coupon(){
        $user_id = input('post.user_id');
        if(null===$user_id){
            $this->json_error('请传过来用户编号');
            die;
        }

        $type = input('post.type'); //1=平台优惠卷 2=商家优惠卷
        if(null===$type){
            $this->json_error('请传过来优惠卷类型');
            die;
        }

        //清空已失效的优惠卷
        Db::name('shy_coupon')->where("type_id", $type)->where("is_expire", 1)->delete();

        if($type == 2){
            //清空已使用的优惠卷
            Db::name("shy_couponlog")->where("user_id", $user_id)->where("is_use", 1)->delete();
        }

        $this->json_success('','成功');
    }
}
