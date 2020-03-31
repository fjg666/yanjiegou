<?php
namespace app\api\controller;
use app\admin\model\Signlog;
use app\api\model\Collectiongoods;
use app\api\model\Collectionshop;
use app\api\model\Couponlog;
use app\api\model\Goods;
use app\api\model\Order;
use app\api\model\Recvaddr;
use think\Db;
use think\Request;
use think\Session;
use app\api\controller\Base;
use think\Hook;
use think\Config;
class Users extends Base
{
    //个人中心首页
    public function index()
    {
        $id = input('post.id');
        if(null===$id){
            $this->json_error('请传过来用户编号');
        }
        //用户个人信息
        $user = \app\api\model\Users::where('id',$id)->find();
        if(empty($user)){
            $this->json_error('账号不存在');
            die;
        }

        //status 订单状态 1.待付款   2.待发货    3.已发货   4.待评价 5.已完成  6.已关闭
        //1.待付款
        $status1 = Order::where(['user_id'=>$id,'status'=>1])->count();
        //2.待发货
        $status2 = Order::where(['user_id'=>$id,'status'=>2])->count();
        //3.已发货(待收货)
        $status3 = Order::where(['user_id'=>$id,'status'=>3])->count();
        //4.待评价
        $status4 = Order::where(['user_id'=>$id,'status'=>4])->count();


        //收藏商品
        $collectiongoods = Collectiongoods::where('user_id',$id)->count();

        //收藏店铺
        $collectionshop = Collectiongoods::where('user_id',$id)->count();
        //优惠券
        $couponlog = Couponlog::where('user_id',$id)->count();

        if(null==$user['avatar']){
            $avatar = $this->domain().'/'.'static/home/images/default.png';
        }else{
            $avatar = $this->domain().'/'.$user['avatar'];
        }


        $data = [];

        $data = [
            'id'=>$id,
            'username'=>$user['username'],
            'mobile'=>$user['mobile'],
            'avatar'=>$avatar,
            'status1'=>$status1,
            'status2'=>$status2,
            'status3'=>$status3,
            'status4'=>$status4,
            'collectiongoods'=>$collectiongoods,
            'collectionshop'=>$collectionshop,
            'couponlog'=>$couponlog
        ];

        //当前的页码
        $p = empty(input('post.p')) ?1:input('post.p');


        //每页显示的数量
        $rows = empty(input('post.rows'))?10:input('post.rows');

        $goodsmodel = new Goods();

        $where = [
            //status  0否1上架
            'g.status'=>1,
            //check_status   --审核状态  -1:违规 0:未审核 1:已审核
            'g.check_status'=>1

        ];

        $goods = $goodsmodel->alias('g')
            ->join('__SHOP__ s','s.id=g.shopid','LEFT')
            ->order('g.readpoint desc,g.id desc')
            ->where($where)
            ->field('g.id,g.headimg,g.title,g.price,s.id as sid,s.name,s.shoplogo')
            ->page($p,$rows)
            ->select();

        foreach($goods as $k=>$v){
            $headimg = explode(',',$v['headimg']);
            $goods[$k]['headimg'] = $this->domain().'/'.$headimg[0];
            $goods[$k]['shoplogo'] = $this->domain().'/'.$v['shoplogo'];
        }

        $data['goods'] = $goods;



        $this->json_success($data);

    }
    //注册
    public function reg()
    {
        if (Request::instance()->isPost()){
            $mobile = input('post.mobile');
            if(null===$mobile){
                $this->json_error('请传过来手机号');
            }

            //检查手机号是否被注册
            $myuser = Db::name('users')->where(['mobile'=>$mobile])->find();

            if($myuser!=null){
                $this->json_error('手机号已经存在');
                die;
            }

            $password = input('post.password');
            if(null===$password){
                $this->json_error('请传过来密码');
            }
            $password_confirm = input('post.password_confirm');
            if(null===$password_confirm){
                $this->json_error('请传过来确认密码');
            }

            $code = input('post.code');
            if(null===$code){
                $this->json_error('请传过来验证码');
            }
            $rule=[
                'mobile'  => 'require|max:11|regex:/^1[3-8]{1}[0-9]{9}$/',
                'password'=>'require|length:6,20|confirm',
                'code'=>'require|number'
            ];
            $msg=[
                'mobile.require'=>'手机号码不能为空',
                'mobile.max'=>'手机号码不符合长度',
                'mobile.regex'=>'手机号码格式不正确',
                'password.require'=>'密码不能为空',
                'password.length'=>'密码的长度必须在6-20位之间',
                'password.confirm'=>'密码和确认密码不一致',
                'code.require'=>'验证码不能为空',
                'code.number'=>'验证码必须是数字'
            ];
            $result=$this->validate(input('post.'),$rule,$msg);



            if(true !== $result){
                // 验证失败 输出错误信息
                $this->json_error($result);
                die;
            }else{
                //查看手机号是否已经存在了
                $user = db('users')->where('mobile', $mobile)->find();
                if(null!==$user){
                    $this->json_error('手机号已经存在');
                    die;
                }
                //输入的验证码和session的验证码比对
                $yzm = Session::get($mobile.'code');
                if($code!=$yzm){
                    $this->json_error('输入的手机验证码不正确');
                    die;
                }

                //注册时间
                $reg_time = time();
                $data = [
                    'mobile'=>input('post.mobile'),
                    'reg_time'=>$reg_time,
                    'password'=>authcode($password)

                ];
                $res = \app\api\model\Users::create($data);

                if($res){
                    $info = [
                        'id'=>$res->id,
                        'mobile'=>$res->mobile,
                        'reg_time'=>date('Y-m-d H:i:s',$res->reg_time),
                        'avatar'=>$this->domain().'/'.'static/home/images/default.png'
                    ];
                    $this->json_success($info,'注册成功');
                }else{
                    $this->json_error('注册失败');
                }
            }
        }else{
            $this->json_error('请求方式有问题');
        }

    }


    public function check_mobile()
    {
        $mobile = input('post.mobile');
        if(null===$mobile){
            $this->json_error('请传过来手机号');
        }

        //检查手机号是否被注册
        $myuser = Db::name('users')->where(['mobile'=>$mobile])->find();

        if($myuser!=null){
            $this->json_error('手机号已经存在',-1);
            die;
        }else{
            $this->json_success([],'恭喜你该手机号可以注册');
        }
    }

	public function login()
	{
        if (Request::instance()->isPost()){
            $mobile = input('post.mobile');
            $password = input('post.password');

            if(null===$mobile){
                $this->json_error('请传过来手机号');
            }
            if(null===$password){
                $this->json_error('请传过来密码');
            }
            $rule=[
                'mobile'  => 'require|max:11|regex:/^1[3-8]{1}[0-9]{9}$/',
                'password'=>'require|length:6,20'
            ];
            $msg=[
                'mobile.require'=>'手机号码不能为空',
                'mobile.max'=>'手机号码不符合长度',
                'mobile.regex'=>'手机号码格式不正确',
                'password.require'=>'密码不能为空',
                'password.length'=>'密码的长度必须在6-20位之间'
            ];
            $result=$this->validate(input('post.'),$rule,$msg);
            if(true !== $result){
                // 验证失败 输出错误信息
                $this->json_error($result);
                die;
            }else{
                $user = \app\api\model\Users::where('mobile', $mobile)->find();
                if(empty($user)){
                    $this->json_error('账号不存在，请注册');
                    die;
                }

                if($password!=authcode($user['password'],'DECODE')){
                    $this->json_error('账号和密码不正确');
                    die;
                }



                $info = [
                    'last_login'=>time(),
                    'last_ip'=>request()->ip()
                ];
                $res = \app\api\model\Users::where('id', $user['id'])->update($info);
                if($res){
                    //0 男   1 女  2保密
                    switch($user['sex']){
                        case 0:
                            $sex = '男';
                            break;
                        case 1:
                            $sex = '女';
                            break;
                        case 2:
                            $sex = '保密';
                            break;
                        default:
                            $sex = '男';
                    }

                    if(null==$user['avatar']){
                        $avatar = $this->domain().'/'.'static/home/images/default.png';
                    }else{
                        $avatar = $this->domain().'/'.$user['avatar'];
                    }

                    $data = [
                        'id'=>$user['id'],
                        'username'=>$user['username'],
                        'othername'=>$user['othername'],
                        'sex'=>$sex,
                        'reg_time'=>date('Y-m-d H:i:s',$user['reg_time']),
                        'mobile'=>$user['mobile'],
                        'avatar'=>$avatar,
                    ];
                    $this->json_success($data,'登录成功');
                }else{
                    $this->json_error('登录失败，请重试');
                    die;
                }

            }
        }else{
            $this->json_error('请求方式有问题');
        }
	}


	//手机短信验证码登录
    public function yzmlogin()
    {
        if (Request::instance()->isPost()){
            $mobile = input('post.mobile');
            $code = input('post.code');

            if(null===$mobile){
                $this->json_error('请传过来手机号');
            }

            if(null===$code){
                $this->json_error('请传过来验证码');
            }

            $rule=[
                'mobile'  => 'require|max:11|regex:/^1[3-8]{1}[0-9]{9}$/',
                'code'=>'require|number'
            ];
            $msg=[
                'mobile.require'=>'手机号码不能为空',
                'mobile.max'=>'手机号码不符合长度',
                'mobile.regex'=>'手机号码格式不正确',
                'code.require'=>'验证码不能为空',
                'code.number'=>'验证码必须是数字'
            ];
            $result=$this->validate(input('post.'),$rule,$msg);
            if(true !== $result){
                // 验证失败 输出错误信息
                $this->json_error($result);
                die;
            }else{
                $user = \app\api\model\Users::where('mobile', $mobile)->find();
                if(empty($user)){
                    $this->json_error('账号不存在，请注册');
                    die;
                }

                //输入的验证码和session的验证码比对
                $yzm = Session::get($mobile.'code');
                if($code!=$yzm){
                    $this->json_error('输入的手机验证码不正确');
                    die;
                }



                $info = [
                    'last_login'=>time(),
                    'last_ip'=>request()->ip()
                ];
                $res = \app\api\model\Users::where('id', $user['id'])->update($info);
                if($res){
                    switch($user['sex']){
                        case 0:
                            $sex = '男';
                            break;
                        case 1:
                            $sex = '女';
                            break;
                        case 2:
                            $sex = '保密';
                            break;
                        default:
                            $sex = '男';
                    }

                    if(null==$user['avatar']){
                        $avatar = $this->domain().'/'.'static/home/images/default.png';
                    }else{
                        $avatar = $this->domain().'/'.$user['avatar'];
                    }

                    $data = [
                        'id'=>$user['id'],
                        'username'=>$user['username'],
                        'othername'=>$user['othername'],
                        'sex'=>$sex,
                        'reg_time'=>date('Y-m-d H:i:s',$user['reg_time']),
                        'mobile'=>$user['mobile'],
                        'avatar'=>$avatar,
                    ];
                    $this->json_success($data,'登录成功');
                }else{
                    $this->json_error('登录失败，请重试');
                    die;
                }

            }
        }else{
            $this->json_error('请求方式有问题');
        }
    }

    //找回密码
    public function find()
    {
        if (Request::instance()->isPost()){
            $mobile = input('post.mobile');
            $password = input('post.password');
            $password_confirm = input('post.password_confirm');

            $code = input('post.code');
            if(null===$mobile){
                $this->json_error('请传过来手机号');
            }
            if(null===$password){
                $this->json_error('请传过来密码');
            }
            if(null===$password_confirm){
                $this->json_error('请传过来确认密码');
            }
            if(null===$code){
                $this->json_error('请传过来验证码');
            }


            //输入的验证码和session的验证码比对
            $yzm = Session::get($mobile.'code');
            if($code!=$yzm){
                $this->json_error('输入的手机验证码不正确');
                die;
            }
            //查看手机号是否已经存在了
            $user = db('users')->where('mobile', $mobile)->find();
            if(null==$user){
                $this->json_error('手手机号不存在');
                die;
            }


            $rule=[
                'mobile'  => 'require|max:11|regex:/^1[3-8]{1}[0-9]{9}$/',
                'password'=>'require|length:6,20|confirm',
                'code'=>'require|number'
            ];
            $msg=[
                'mobile.require'=>'手机号码不能为空',
                'mobile.max'=>'手机号码不符合长度',
                'mobile.regex'=>'手机号码格式不正确',
                'password.require'=>'密码不能为空',
                'password.length'=>'密码的长度必须在6-20位之间',
                'password.confirm'=>'密码和确认密码不一致',
                'code.require'=>'验证码不能为空',
                'code.number'=>'验证码必须是数字'
            ];
            $result=$this->validate(input('post.'),$rule,$msg);
            if(true !== $result){
                // 验证失败 输出错误信息
                $this->json_error($result);
                die;
            }else{

                $data = [
                    'password'=>authcode($password)

                ];
                if($user){
                    $res = \app\api\model\Users::where('id',$user['id'])->update($data);
                    if($res){
                        $info = [
                            'id'=>$user['id'],
                            'mobile'=>$user['mobile']
                        ];
                        $this->json_success($info,'修改密码成功');
                    }else{
                        $this->json_error('修改密码失败');
                    }
                }

            }
        }else{
            $this->json_error('请求方式有问题');
        }
    }

    //登录发放优惠卷
    public function login_coupon(){
        $user_id = input('post.user_id');

        if(null===$user_id){
            $this->json_error('请传过来用户编号');
        }

        /*$coupon_info = Db::name("coupon")
            ->where("total_count",">","1")
            ->where("is_expire",0)
            ->orderRaw('rand()')
            ->find();*/

        //随机查出一条用户没有的优惠卷
        $coupon_info = Db::name("couponlog")->alias('l')
            ->join("shy_coupon c","l.coupon_id = c.id")
            ->where("user_id", $user_id)
            ->orderRaw('rand()')
            ->find();

        var_dump($coupon_info);


    }

    //个人中心资料修改
    public function modify()
    {
        if (Request::instance()->isPost()){

            $id = input('post.id');

            if(null===$id){
                $this->json_error('请传过来用户编号');
            }

            $user = \app\api\model\Users::where(['id'=>$id])->find();
            if(empty($user)){
                $this->json_error('账号不存在');
                die;
            }
            //0 男   1 女  2保密

            $sex = empty(input('post.sex')) ?0:input('post.sex');
//            $mobile = input('post.mobile');
//            if(null===$mobile){
//                $this->json_error('请传过来手机号');
//            }

            //昵称
            $username = input('post.username');
            if(null===$username){
                $this->json_error('请传过来昵称');
            }

            $rule=[
                //'mobile'  => 'require|max:11|regex:/^1[3-8]{1}[0-9]{9}$/',
                'username'=>'require'
            ];
            $msg=[
                //'mobile.require'=>'手机号码不能为空',
                //'mobile.max'=>'手机号码不符合长度',
                //'mobile.regex'=>'手机号码格式不正确',
                'username.require'=>'昵称不能为空'
            ];
            $result=$this->validate(input('post.'),$rule,$msg);


            if(true !== $result){
                // 验证失败 输出错误信息
                $this->json_error($result);
                die;
            }else{

                $data = [
                    'username'=>$username,
                    'sex'=>$sex

                ];

                $avatar = input('post.avatar');
                if(null!==$avatar){
                    $data['avatar'] =$avatar;
                }





                if($user){
                    $res = \app\api\model\Users::where('id',$user['id'])->update($data);
                    if($res){

                        $info = \app\api\model\Users::where('id',$user['id'])->field('id,mobile,username,sex,avatar')->find();
                            switch($info['sex']){
                                case 0:
                                    $sex = '男';
                                    break;
                                case 1:
                                    $sex = '女';
                                    break;
                                case 2:
                                    $sex = '保密';
                                    break;
                                default:
                                    $sex = '男';
                            }
                        if(null==$info['avatar']){
                            $avatar = $this->domain().'/'.'static/home/images/default.png';
                        }else{
                            $avatar = $this->domain().'/'.$info['avatar'];
                        }
                            $info['sex'] = $sex;
                            $info['avatar'] = $avatar;
                        $this->json_success($info,'修改个人信息成功');
                    }else{
                        $this->json_error('修改个人信息失败');
                    }
                }

            }
        }else{
            $this->json_error('请求方式有问题');
        }
    }

    //修改手机号1
    public function upd_mobile1()
    {
        if (Request::instance()->isPost()){
            $id = input('post.id');

            if(null===$id){
                $this->json_error('请传过来用户编号');
            }
            $user = \app\api\model\Users::where(['id'=>$id])->find();
            if(empty($user)){
                $this->json_error('账号不存在');
                die;
            }

            $mobile = input('post.mobile');
            if(null===$mobile){
                $this->json_error('请传过来手机号');
                die;
            }

            $code = input('post.code');
            if(null===$code){
                $this->json_error('请传过来验证码');
            }

            //输入的验证码和session的验证码比对
            $yzm = Session::get($mobile.'code');
            if($code!=$yzm){
                $this->json_error('输入的手机验证码不正确');
                die;
            }


            $rule=[
                'mobile'  => 'require|max:11|regex:/^1[3-8]{1}[0-9]{9}$/',
                'code'=>'require|number'
            ];
            $msg=[
                'mobile.require'=>'手机号码不能为空',
                'mobile.max'=>'手机号码不符合长度',
                'mobile.regex'=>'手机号码格式不正确',
                'code.require'=>'验证码不能为空',
                'code.number'=>'验证码必须是数字'
            ];
            $result=$this->validate(input('post.'),$rule,$msg);
            if(true !== $result){
                // 验证失败 输出错误信息
                $this->json_error($result);
                die;
            }else{
                $data = [
                    'mobile'=>$mobile,
                    'user_id'=>$id
                ];
                $this->json_success($data);
            }
        }else{
            $this->json_error('请求方式有问题');
        }
    }



    //修改手机号
    public function upd_mobile()
    {
        if (Request::instance()->isPost()){

            $id = input('post.id');

            if(null===$id){
                $this->json_error('请传过来用户编号');
            }

            $user = \app\api\model\Users::where(['id'=>$id])->find();
            if(empty($user)){
                $this->json_error('账号不存在');
                die;
            }
            $mobile = input('post.mobile');
            if(null===$mobile){
                $this->json_error('请传过来手机号');
                die;
            }

            if($mobile==$user->mobile){
                $this->json_error('手机号相同不用修改');
                die;
            }




            $code = input('post.code');
            if(null===$code){
                $this->json_error('请传过来验证码');
            }

            //输入的验证码和session的验证码比对
            $yzm = Session::get($mobile.'code');
            if($code!=$yzm){
                $this->json_error('输入的手机验证码不正确');
                die;
            }


            $rule=[
                'mobile'  => 'require|max:11|regex:/^1[3-8]{1}[0-9]{9}$/',
                'code'=>'require|number'
            ];
            $msg=[
                'mobile.require'=>'手机号码不能为空',
                'mobile.max'=>'手机号码不符合长度',
                'mobile.regex'=>'手机号码格式不正确',
                'code.require'=>'验证码不能为空',
                'code.number'=>'验证码必须是数字'
            ];
            $result=$this->validate(input('post.'),$rule,$msg);
            if(true !== $result){
                // 验证失败 输出错误信息
                $this->json_error($result);
                die;
            }else{

                $oneuser = \app\api\model\Users::where('mobile', $mobile)->find();
                if(!empty($oneuser)){
                    $this->json_error('手机号已经存在，请换一个');
                    die;
                }

                $data = [
                    'mobile'=>$mobile

                ];
                if($user){
                    $res = \app\api\model\Users::where('id',$user['id'])->update($data);
                    if($res){
                        if(null==$user['avatar']){
                            $avatar = $this->domain().'/'.'static/home/images/default.png';
                        }else{
                            $avatar = $this->domain().'/'.$user['avatar'];
                        }
                        $info = [
                            'id'=>$user['id'],
                            'mobile'=>$user['mobile'],
                            'username'=>$user['username'],
                            'avatar'=>$avatar
                        ];
                        $this->json_success($info,'修改手机号成功');
                    }else{
                        $this->json_error('修改手机号失败');
                    }
                }

            }
        }else{
            $this->json_error('请求方式有问题');
        }
    }

    //添加收货地址
    public function addaddr()
    {
        if (Request::instance()->isPost()){
            $user_id = input('post.user_id');
            if(null===$user_id){
                $this->json_error('请传过来用户编号');
            }

            //查看手机号是否已经存在了
            $user = \app\api\model\Users::where('id', $user_id)->find();
            if(empty($user)){
                $this->json_error('用户不存在，非法操作');
                die;
            }
            //收货人
            $consignee = input('post.consignee');
            if(null===$consignee){
                $this->json_error('请传过来收货人');
            }

            //手机号码
            $phone = input('post.phone');
            if(null===$phone){
                $this->json_error('请传过来手机号码');
            }

            // 省
            $province = input('post.province');
            if(null===$province){
                $this->json_error('请传过来省');
            }

            //市
            $city = input('post.city');
            if(null===$city){
                $this->json_error('请传过来市');
            }

            //  区
            $area = input('post.area');
            if(null===$area){
                $this->json_error('请传过来区');
            }

            // 镇或街道
            $street = input('post.street');

            //详细地址
            $address = input('post.address');
            if(null===$address){
                $this->json_error('请传过来详细地址');
            }
            //is_default是否默认收货地址，1是 0 否
            $is_default = (null !== input('post.is_default')) ? input('post.is_default'):0;


            $rule=[
                'consignee'=>'require',
                'phone'  => 'require|max:11|regex:/^1[3-8]{1}[0-9]{9}$/',
                'province'=>'require',
                'city'=>'require',
                'area'=>'require',
                'address'=>'require',
                'is_default'=>'in:0,1'
            ];
            $msg=[
                'consignee.require'=>'收货人不能为空',
                'phone.require'=>'手机号码不能为空',
                'phone.max'=>'手机号码不符合长度',
                'phone.regex'=>'手机号码格式不正确',
                'province.require'=>'省不能为空',
                'city.require'=>'市不能为空',
                'area.require'=>'区不能为空',
                'address.require'=>'详细地址不能为空',
                'is_default.in'=>'值不合法'
            ];
            $result=$this->validate(input('post.'),$rule,$msg);


            if(true !== $result){
                // 验证失败 输出错误信息
                $this->json_error($result);
                die;
            }else{

                if($is_default==1){
                    //之前是否有默认地址，有，就改为0
                    $myinfo = [
                        'is_default'=>0
                    ];
                    Recvaddr::where('user_id',$user_id)->update($myinfo);
                }
                $data = input('post.');
                $data['user_id'] = $user_id;
                $res = Recvaddr::create($data);
                if($res){
                    //查看当前用户中是否有默认地址，没有的话，默认把第一个设置为默认地址
                    //是否默认收货地址，1是 0 否
                    //is_delete 是否删除，默认0 不删除   1 删除
                    $recvaddr1 = Db::name('recvaddr')->where(['user_id'=>$user_id,'is_default'=>1,'is_delete'=>0])->find();
                    if($recvaddr1==null){
                        $recvaddr2 = Db::name('recvaddr')->where(['user_id'=>$user_id,'is_default'=>0,'is_delete'=>0])->order('id asc')->find();
                        $rid = $recvaddr2['id'];
                        Db::name('recvaddr')->where(['user_id'=>$user_id,'id'=>$rid])->update(['is_default'=>1]);
                    }
                    $this->json_success($data,'添加收货地址成功');
                }else{
                    $this->json_error('添加收货地址失败');
                }
            }



        }else{
            $this->json_error('请求方式有问题');
        }
    }

    //收货地址列表
    public function addrlist()
    {

        $user_id = input('post.user_id');
        if(null===$user_id){
            $this->json_error('请传过来用户编号');
        }
        $where = [
            'user_id'=>$user_id,
            'is_delete'=>0
        ];
        $data = Recvaddr::where($where)->order('is_default desc,id desc')->select();
        $this->json_success($data);
    }

    //设置默认的收货地址
    public function setdefaultaddr()
    {
        $user_id = input('post.user_id');
        if(null===$user_id){
            $this->json_error('请传过来用户编号');
        }

        $addrid = input('post.addrid');
        if(null===$addrid){
            $this->json_error('请传过来收货地址编号');
        }
        //之前是否有默认地址，有，就改为0
        $myinfo = [
            'is_default'=>0
        ];
        $res1 = Recvaddr::where('user_id',$user_id)->update($myinfo);
        $where = [
            'id'=>$addrid,
            'user_id'=>$user_id
        ];
        $info = [
            'is_default'=>1
        ];
        $res2 = Recvaddr::where($where)->update($info);
        if($res1&&$res2){
            $data = Recvaddr::where('id',$addrid)->find();
            $this->json_success($data,'设置默认收货地址成功');
        }else{
            $this->json_error('设置默认收货地址失败');
        }

    }

    //删除收货地址
    public function deladdr()
    {
        $user_id = input('post.user_id');
        if(null===$user_id){
            $this->json_error('请传过来用户编号');
        }

        $addrid = input('post.addrid');
        if(null===$addrid){
            $this->json_error('请传过来收货地址编号');
        }

        $where = [
            'user_id'=>$user_id,
            'id'=>$addrid
        ];

        $myinfo = [
            'is_delete'=>1
        ];

        $res = Recvaddr::where($where)->update($myinfo);

        if($res){
            $data = Recvaddr::where('id',$addrid)->find();
            $this->json_success($data,'删除收货地址成功');
        }else{
            $this->json_error('设删除收货地址失败');
        }

    }

    //查看历史收货地址
    public function hisaddr()
    {
        $user_id = input('post.user_id');
        if(null===$user_id){
            $this->json_error('请传过来用户编号');
        }

        $where = [
            'user_id'=>$user_id,
            'is_delete'=>1

        ];
        $data = Recvaddr::where($where)->order('is_default desc,id desc')->select();
        $this->json_success($data);
    }

    //修改收货地址
    public function updaddr()
    {
        $user_id = input('post.user_id');
        if (null === $user_id) {
            $this->json_error('请传过来用户编号');
        }

        $addrid = input('post.addrid');
        if (null === $addrid) {
            $this->json_error('请传过来收货地址编号');
        }
        $where = [
            'user_id' => $user_id,
            'id' => $addrid
        ];
        //收货人
        $consignee = input('post.consignee');
        if (null === $consignee) {
            $this->json_error('请传过来收货人');
        }

        //手机号码
        $phone = input('post.phone');
        if (null === $phone) {
            $this->json_error('请传过来手机号码');
        }

        // 省
        $province = input('post.province');
        if (null === $province) {
            $this->json_error('请传过来省');
        }

        //市
        $city = input('post.city');
        if (null === $city) {
            $this->json_error('请传过来市');
        }

        //  区
        $area = input('post.area');
        if (null === $area) {
            $this->json_error('请传过来区');
        }

        // 镇或街道
        $street = input('post.street');

        //详细地址
        $address = input('post.address');
        if (null === $address) {
            $this->json_error('请传过来详细地址');
        }
        //is_default是否默认收货地址，1是 0 否
        $is_default = (null !== input('post.is_default')) ? input('post.is_default') : 0;


        $rule = [
            'consignee' => 'require',
            'phone' => 'require|max:11|regex:/^1[3-8]{1}[0-9]{9}$/',
            'province' => 'require',
            'city' => 'require',
            'area' => 'require',
            'address' => 'require',
            'is_default' => 'in:0,1'
        ];
        $msg = [
            'consignee.require' => '收货人不能为空',
            'phone.require' => '手机号码不能为空',
            'phone.max' => '手机号码不符合长度',
            'phone.regex' => '手机号码格式不正确',
            'province.require' => '省不能为空',
            'city.require' => '市不能为空',
            'area.require' => '区不能为空',
            'address.require' => '详细地址不能为空',
            'is_default.in' => '值不合法'
        ];
        $result = $this->validate(input('post.'), $rule, $msg);


        if (true !== $result) {
            // 验证失败 输出错误信息
            $this->json_error($result);
            die;
        } else {

            if ($is_default == 1) {
                //之前是否有默认地址，有，就改为0
                $myinfo = [
                    'is_default' => 0
                ];
                Recvaddr::where('user_id', $user_id)->update($myinfo);
            }
            $data = input('post.');
            unset($data['user_id']);
            unset($data['addrid']);
            $res = Recvaddr::where($where)->update($data);

            $data['user_id'] = $user_id;
            $data['id'] = $addrid;
            if ($res) {
                $this->json_success($data, '修改收货地址成功');
            } else {
                $this->json_error('修改收货地址失败');
            }

        }
    }

    //获取所有省的列表
    public function getprovice()
    {

        $data = Base::provice();
        $this->json_success($data);
    }

    //获取省下的市区县
    public function getcity()
    {
        $parent_id = input('post.parent_id');
        if(null===$parent_id){
            $this->json_error('请传过来上级编号');
        }
        $data = Base::getchildarea($parent_id,0);
        $this->json_success($data);
        die;

    }


    //收藏商品

    public function collectiongoods()
    {
        $user_id = input('post.user_id');
        if(null===$user_id){
            $this->json_error('请传过来用户编号');
        }
        $goods_id = input('post.goods_id');
        if(null===$goods_id){
            $this->json_error('请传过来商品编号');
        }
        $where = [
            'user_id'=>$user_id,
            'goods_id'=>$goods_id
        ];
        //看是否已经收藏过该商品了
        $collectiongoods = Collectiongoods::where($where)->find();
        if(!empty($collectiongoods)){
            $this->json_error('你已经收藏该商品');
            die;
        }
        $data = [
            'user_id'=>$user_id,
            'goods_id'=>$goods_id,
            'add_time'=>time()
        ];
        $res = Collectiongoods::insert($data);
        if($res){
            $data['add_time'] = date('Y-m-d H:i:s',$data['add_time']);
            $this->json_success($data,'收藏商品成功');
            die;
        }else{
            $this->json_error('收藏商品失败');
            die;
        }

    }

    //查看我收藏的商品
    public function mycollectiongoods()
    {
        $user_id = input('post.user_id');
        if(null===$user_id){
            $this->json_error('请传过来用户编号');
        }
        //当前的页码
        $p = empty(input('post.p')) ?1:input('post.p');

        //每页显示的数量
        $rows = empty(input('post.rows'))?10:input('post.rows');

        $collectgoodsmodel = new Collectiongoods();


        $collectgoods = $collectgoodsmodel->alias('c')
            ->join('__GOODS__ g','g.id=c.goods_id','LEFT')
            ->order('c.id desc')
            ->where('c.user_id',$user_id)
            ->field('c.*,g.id as gid,g.headimg,g.title,g.price')
            ->page($p,$rows)
            ->select();

        foreach($collectgoods as $k=>$v){
            $headimg = explode(',',$v['headimg']);
            $collectgoods[$k]['headimg'] = $this->domain().'/'.$headimg[0];
        }



        $this->json_success($collectgoods,'请求数据成功');
    }

    //删除收藏的商品
    public function delcgoods()
    {
        $user_id = input('post.user_id');
        if(null===$user_id){
            $this->json_error('请传过来用户编号');
        }
        $goods_id = input('post.goods_id');
        if(null===$goods_id){
            $this->json_error('请传过来商品编号');
        }


        $res = Collectiongoods::where('user_id',$user_id)->where("goods_id in ($goods_id)")->delete();

        if($res){
            //当前的页码
            $p = empty(input('post.p')) ?1:input('post.p');

            //每页显示的数量
            $rows = empty(input('post.rows'))?10:input('post.rows');

            $collectgoodsmodel = new Collectiongoods();


            $collectgoods = $collectgoodsmodel->alias('c')
                ->join('__GOODS__ g','g.id=c.goods_id','LEFT')
                ->order('c.id desc')
                ->where('c.user_id',$user_id)
                ->field('c.*,g.id as gid,g.headimg,g.title,g.price')
                ->page($p,$rows)
                ->select();

            foreach($collectgoods as $k=>$v){
                $headimg = explode(',',$v['headimg']);
                $collectgoods[$k]['headimg'] = $this->domain().'/'.$headimg[0];
            }
            $this->json_success($collectgoods,'删除收藏商品成功');
        }else{
            $this->json_error('删除收藏商品失败');
        }
    }


    //收藏店铺（商家）

    public function collectionshp()
    {
        $user_id = input('post.user_id');
        if(null===$user_id){
            $this->json_error('请传过来用户编号');
        }
        $shop_id = input('post.shop_id');
        if(null===$shop_id){
            $this->json_error('请传过来商家编号');
        }
        $where = [
            'user_id'=>$user_id,
            'shop_id'=>$shop_id
        ];
        //看是否已经收藏过该商品了
        $collectionshop = Collectionshop::where($where)->find();
        if(!empty($collectionshop)){
            $this->json_error('你已经收藏该店铺');
            die;
        }
        $data = [
            'user_id'=>$user_id,
            'shop_id'=>$shop_id,
            'add_time'=>time()
        ];
        $res = Collectionshop::insert($data);
        if($res){
            $data['add_time'] = date('Y-m-d H:i:s',$data['add_time']);
            $this->json_success($data,'收藏店铺成功');
            die;
        }else{
            $this->json_error('收藏店铺失败');
            die;
        }

    }


    //查看我收藏的店铺
    public function mycollectionshop()
    {
        $user_id = input('post.user_id');
        if(null===$user_id){
            $this->json_error('请传过来用户编号');
        }
        //当前的页码
        $p = empty(input('post.p')) ?1:input('post.p');

        //每页显示的数量
        $rows = empty(input('post.rows'))?10:input('post.rows');

        $collectshopmodel = new Collectionshop();


        $collectshop = $collectshopmodel->alias('c')
            ->join('__SHOP__ s','s.id=c.shop_id','LEFT')
            ->order('c.id desc')
            ->where('c.user_id',$user_id)
            ->field('c.*,s.id as sid,s.shoplogo,s.name')
            ->page($p,$rows)
            ->select();

        foreach($collectshop as $k=>$v){
            $collectshop[$k]['shoplogo'] = $this->domain().'/'.$v['shoplogo'];
        }



        $this->json_success($collectshop,'请求数据成功');
    }


    //删除收藏的店铺
    public function delcshop()
    {
        $user_id = input('post.user_id');
        if(null===$user_id){
            $this->json_error('请传过来用户编号');
        }
        $shop_id = input('post.shop_id');
        if(null===$shop_id){
            $this->json_error('请传过来店铺编号');
        }
        $res = Collectionshop::where('user_id',$user_id)->where("shop_id in ($shop_id)")->delete();;
        if($res){
            //当前的页码
            $p = empty(input('post.p')) ?1:input('post.p');

            //每页显示的数量
            $rows = empty(input('post.rows'))?10:input('post.rows');

            $collectshopmodel = new Collectionshop();


            $collectshop = $collectshopmodel->alias('c')
                ->join('__SHOP__ s','s.id=c.shop_id','LEFT')
                ->order('c.id desc')
                ->where('c.user_id',$user_id)
                ->field('c.*,s.id as sid,s.shoplogo,s.name')
                ->page($p,$rows)
                ->select();

            foreach($collectshop as $k=>$v){
                $collectshop[$k]['shoplogo'] = $this->domain().'/'.$v['shoplogo'];
            }
            $this->json_success($collectshop,'删除收藏商品成功');
        }else{
            $this->json_error('删除收藏商品失败');
        }
    }

    //我的签到
    public function mysign()
    {
        //当前的页码
        $p = empty(input('post.p')) ?1:input('post.p');
        //每页显示的数量
        $rows = empty(input('post.rows'))?10:input('post.rows');

        $user_id = input('post.user_id');
        if(null===$user_id){
            $this->json_error('请传过来用户编号');
        }
        //code_source  1 签到  2 分享获取的
        $code_source = empty(input('post.code_source')) ?1:input('post.code_source');

        $signlog = Signlog::where(['user_id'=>$user_id,'code_source'=>$code_source])->order('id desc') ->page($p,$rows)->select();
        $this->json_success($signlog);

    }

    public function sendsms()
    {
        $mobile = input('post.mobile');

        if(null===$mobile){
            $this->json_error('请传过来手机号');
        }
        $rule=[
            'mobile'  => 'require|max:11|regex:/^1[3-8]{1}[0-9]{9}$/'
        ];
        $msg=[
            'mobile.require'=>'手机号码不能为空',
            'mobile.max'=>'手机号码不符合长度',
            'mobile.regex'=>'手机号码格式不正确'

        ];
        $result=$this->validate(input('post.'),$rule,$msg);



        if(true !== $result){
            // 验证失败 输出错误信息
            $this->json_error($result);
            die;
        }
        
        //阿里大鱼短信
        //短信签名
        $signName=config('sms.signName');
        //短信模板ID
        $templateCode=config('sms.templateCode');
        //短信接收号码
        $mobile = $mobile;
        //验证码
        $verifycode=strval(rand(1000,9999));
        //短信模板变量
        //$templateParam=array('code'=>$verifycode,'product'=>$signName);

        $res = Sms::smsVerify($mobile,$verifycode,$templateCode);
        if($res['status'] == 1){
            Session::set($mobile.'code',$verifycode);
            $this->json_success([],'验证码已发送',200);
        }else{

            $this->json_error("验证码发送失败，请联系客服");

        }

    }
    /**
 * str = "你好，订单号：123456789提醒你发货";
 */
    public function sendremind()
    {
        $user_id = input('post.u_id');
        $order_id = input('post.o_id');
        $shop_id = input('post.s_id');
        $order = Db::name('order')->alias('o')
                ->join('shop s','s.id = o.shop_id')
                ->field('o.order_sn,o.shop_id,s.phone')
                ->where('o.user_id',$user_id)
                ->where('o.order_sn',$order_id)
                ->where('o.shop_id',$shop_id)
                ->where('o.status',2)
                ->find();
        if(empty($user_id) || empty($order_id) || !$order){
            $this->json_error('参数错误');
        }
        $find = Db::name('Remind')
                ->whereTime('addtime', 'd')
                ->where('user_id',$user_id)
                ->where('shop_id',$shop_id)
                ->where('order_id',$order['order_sn'])
                ->select();
        if ($find) {
           $this->json_error('今天已经提醒过',100);
        }
        $rule=[
            'mobile'  => 'require|max:11|regex:/^1[3-8]{1}[0-9]{9}$/'
        ];
        $msg=[
            'mobile.require'=>'手机号码不能为空',
            'mobile.max'=>'手机号码不符合长度',
            'mobile.regex'=>'手机号码格式不正确'
        ];
        $result=$this->validate(['mobile'=>$order['phone']],$rule,$msg);
        if(true !== $result){
            $this->json_error($result);
        }

        

        //阿里大鱼短信
        //短信签名
        // $signName=config('sms.signName');
        //短信模板ID
        $templateCode=config('sms.templateCode2');
        //短信模板变量
        //$templateParam=array('code'=>$verifycode,'product'=>$signName);

        $res = Sms::smsVerifyTwo($order['phone'],$order['order_sn'],$templateCode);
        if($res['status'] == 1){
            $data = [
                'user_id'=>$user_id,
                'shop_id'=>$shop_id,
                'order_id'=>$order['order_sn'],
                'addtime'=>time()
            ];
            Db::name('Remind')->insert($data);
            $this->json_success([],'已发送给商家',200);
        }else{
            $this->json_error("发送失败，请联系客服");

        }

    }

    //问题反馈
    public function feedback()
    {
        $user_id = input('post.user_id');
        if(null===$user_id){
            $this->json_error('请传过来用户编号');
        }

        $rule=[
            'mobile'  => 'require|max:11|regex:/^1[3-8]{1}[0-9]{9}$/',
            'content'=>'require'
        ];
        $msg=[
            'mobile.require'=>'手机号码不能为空',
            'mobile.max'=>'手机号码不符合长度',
            'mobile.regex'=>'手机号码格式不正确',
            'content.require'=>'问题反馈内容不能为空'
        ];
        $result=$this->validate(input('post.'),$rule,$msg);

        if(true !== $result){
            // 验证失败 输出错误信息
            $this->json_error($result);
            die;
        }else{
            if(empty(input('post.imgsrc'))){
                $data = [
                    'user_id'=>$user_id,
                    'content'=>input('post.content'),
                    'mobile'=>input('post.mobile'),
                    'add_time'=>time()
                ];
            }else{
                $data = [
                    'user_id'=>$user_id,
                    'content'=>input('post.content'),
                    'mobile'=>input('post.mobile'),
                    'imgsrc'=>input('post.imgsrc'),
                    'add_time'=>time()
                ];
            }
            $id = db('feedback')->insertGetId($data);
            if($id){
                $this->json_success($data,'反馈成功');
                die;
            }else{
                $this->json_error('反馈失败');
            }

        }


    }

    //我的订单
    public function order()
    {
        $user_id = input('post.user_id');
        if(null===$user_id){
            $this->json_error('请传过来用户编号');
        }
        //当前的页码
        $p = empty(input('post.p')) ?1:input('post.p');
        //每页显示的数量
        $rows = empty(input('post.rows'))?10:input('post.rows');
        //status订单状态 1.待付款   2.待发货    3.已发货   4.待评价 5.已完成  6.已关闭 7售后
        //如果status不传，就是全部
        $status=input('post.status');
        $where['o.is_del']=array("eq",0);
        if($status){
            $where["o.status"] = array('eq',$status);
        	if ($status == 1) {
                $times = (time()-86400);
                $where['o.add_time'] = ['> time', $times];
                
                Db::name('order')->where('status',1)->where('user_id',$user_id)->where('is_del',0)->where('add_time', '<', $times)->setField('status', 6);
            }
        	
        }

        $orders = Db::name('order')->alias('o')
            ->join('shop s','s.id=o.shop_id','left')
            ->where($where)
            ->where(['o.user_id'=>$user_id,'is_del'=>0])
            ->order('o.id','desc')
            ->field('o.id as oid,o.order_sn,o.money,o.oldmoney,o.freight,o.total_num,o.pay_type,o.status,o.getusername,o.mobile as recmobile,o.shop_id,o.send_type,s.id as sid,s.name,s.shoplogo,o.expresscom,o.expresssn')
            ->select();

        //订单编号，去查找订单商品
        $order_sn = array_column($orders,'order_sn');

        $orders_goods = Db::name('order_goods')->alias('og')
            ->join('goods g','g.id=og.goodsid')
            //->join('shop s','s.id=g.shopid','left')
            //->field('og.price as ogprice,og.num,og.specification,og.order_sn as ogorder_sn,og.id as ogid,g.id as gid,g.title as gtitle,g.headimg,s.id as sid,s.name as sname,s.shoplogo')
            ->field('og.price as ogprice,og.num,og.specification,og.order_sn as ogorder_sn,og.id as ogid,g.id as gid,og.sku_id,g.title as gtitle,g.headimg')
            ->whereIn('og.order_sn',$order_sn)
            ->select();
        foreach($orders_goods as $gk=>$gv){
            foreach($orders as $kkk=>$vvv){
                if($gv['ogorder_sn']==$vvv['order_sn']){
                    $orders_goods[$gk]['status'] = $vvv['status'];
                    $orders_goods[$gk]['oid'] = $vvv['oid'];
                }
            }
        }

        $data = [];
        foreach($orders as $k=>$v) {
            $orders[$k]['shoplogo'] = $this->domain(). $v['shoplogo'];
            $totalnum = 0;
            $totalprice = 0;
            foreach ($orders_goods as $ok => $ov) {
                if ($v['order_sn'] == $ov['ogorder_sn']) {
                    $data['gitle'] = $ov['gtitle'];
                    $data['status'] = $ov['status'];
                    $data['oid'] = $ov['oid'];
                    $data['gid'] = $ov['gid'];
                    $data['ogid'] = $ov['ogid'];
                    $data['ogprice'] = $ov['ogprice'];
                    $data['num'] = $ov['num'];
                    $headimgs = explode(',', $ov['headimg']);
                    $data['headimg'] = $this->domain().$headimgs[0];
                    
                    /*---chen*/
                    $data['goods_attr'] = '';
                    if ($ov['sku_id'] != 0) {
                        $group_sku=Db::name('GoodsSttrxsku')->where('id',$ov['sku_id'])->value('group_sku');
                        $goods_attr = json_decode($group_sku,true);
                        if(!empty($goods_attr)){
                            foreach ($goods_attr as $ks=>$vs){
                                $SttrName=Db::name('GoodsSttr')->where('id',$ks)->value('key');
                                $SttrValName=Db::name('GoodsSttrval')->where('id',$vs)->value('sttr_value');
                                $data['goods_attr'] .=  $SttrName.':'.$SttrValName.' ';
                            }
                        }            
                    }
            		/*---chen*/
            
                    
                    $orders[$k]['goods'][] = $data;
                    $totalnum += $ov['num'];
                    $totalprice += $ov['num'] * $ov['ogprice'];
                    $orders[$k]['totalnum'] = $totalnum;
                    $orders[$k]['totalprice'] = $totalprice;
                }

            }
        }


        /*

        $shop_ids = array_column($orders,'shop_id');

        $shop_ids = implode(',',$shop_ids);

        $shops = Db::name('shop')->where('id','in',$shop_ids)->field('id,name,shoplogo')->page($p,$rows)->select();

        $data = [];

        foreach($shops as $k=>$v){
            $shops[$k]['shoplogo'] = $this->domain().'/'.$v['shoplogo'];
            foreach($orders as $kkk=>$vvv){
                if($v['id']==$vvv['shop_id']){
                    $shops[$k]['status'] = $vvv['status'];
                    $shops[$k]['oid'] = $vvv['oid'];
                }
            }
            $totalnum = 0;
            $totalprice = 0;
            foreach($orders_goods as $ok=>$ov){
                if($v['id']==$ov['sid']){
                    $data['gitle'] = $ov['gtitle'];
                    $data['status'] = $ov['status'];
                    $data['oid'] = $ov['oid'];
                    $data['gid'] = $ov['gid'];
                    $data['ogid'] = $ov['ogid'];
                    $data['ogprice'] = $ov['ogprice'];
                    $data['num'] = $ov['num'];
                    $headimgs = explode(',',$ov['headimg']);
                    $data['headimg'] = $this->domain().'/'.$headimgs[0];
                    $shops[$k]['goods'][] = $data;
                    $totalnum+=$ov['num'];
                    $totalprice+= $ov['num']*$ov['ogprice'];
                }
            }

            $shops[$k]['totalnum'] = $totalnum;
            $shops[$k]['totalprice'] = $totalprice;
        }
        */


        //$this->encrypt_success($orders);
        $this->json_success($orders);
    }


    //订单详情
    public function orderdetail()
    {
        $user_id = input('post.user_id');
        if(null===$user_id){
            $this->json_error('请传过来用户编号');
        }
        $order_id = input('post.order_id');
        if(null===$order_id){
            $this->json_error('请传过来订单编号');
        }

        $order = Db::name('order')->where(['user_id'=>$user_id,'id'=>$order_id])->field('id,order_sn,money,oldmoney,pay_type,freight,total_num,remark_shop,remark_member,add_time,status,getusername,mobile,province,city,area,address,shop_id,expresscom,expresssn,takes_time,takes_mobile,couponprice,paytime')->find();

        $oid = $order['id'];

        $order_trade = Db::name('order_trade')->whereIn('order_ids',$oid)->find();


        $order['djs_time'] = $order['add_time']+86400;
        $order['add_time'] = date('Y-m-d H:i:s',$order['add_time']);
        $order['paytime'] = date('Y-m-d H:i:s',$order['paytime']);

        $order['out_trade_no'] = $order_trade['out_trade_no'];

        $shop_id = $order['shop_id'];
        $shop = Db::name('shop')->where('id','=',$shop_id)->field('name,shoplogo,province,city,area,street,address')->find();


        $orders_goods = Db::name('order_goods')->alias('og')
            ->join('goods g','g.id=og.goodsid','LEFT')
            ->field('og.price as ogprice,og.num,og.specification,og.order_sn as ogorder_sn,og.id as og_id,g.id as gid,g.title as gtitle,g.headimg')
            ->where('og.order_sn','=',$order['order_sn'])
            ->select();

        foreach($orders_goods as $k=>$v){
            $headimg = explode(',',$v['headimg']);
            $orders_goods[$k]['headimg'] = $this->domain().$headimg[0];
        }
		$order['origin_id'] = $this->randCode();
        $order['sname'] = $shop['name'];
        $order['shoplogo'] = $this->domain().$shop['shoplogo'];
		$order['shopaddr'] = [
            'province'=>$shop['province'],
            'city'=>$shop['city'],
            'area'=>$shop['area'],
            'street'=>$shop['street'],
            'address'=>$shop['address'],
        ];
        $order['goods'] = $orders_goods;

        //返回base64加密数据
        //$this->encrypt_success($order);
        $this->json_success($order);
    }

    //我的购物车
    public function mycart()
    {
        $user_id = input('post.user_id');
        if(null===$user_id){
            $this->json_error('请传过来用户编号');
        }
        //当前的页码
        $p = empty(input('post.p')) ?1:input('post.p');
        //每页显示的数量
        $rows = empty(input('post.rows'))?10:input('post.rows');
        $carts = Db::name('shopcart')->alias('c')
            ->join('goods g','g.id=c.goods_id','LEFT')
            ->where(['c.user_id'=>$user_id,'c.is_new'=>0])
            ->field('c.*,g.shopid,g.headimg,g.title,g.price,g.total')
            ->select();

        $shop_ids = array_unique(array_column($carts,'shopid'));
        $shop_ids = implode(',',$shop_ids);

        $shops = Db::name('shop')->where('id','in',$shop_ids)->field('id,name,shoplogo')->page($p,$rows)->select();
        foreach($shops as $k=>$v){
            $shops[$k]['shoplogo'] = $this->domain().$v['shoplogo'];
            $shops[$k]['check'] = 'false';
            foreach($carts as $ck=>$cv){
                if($v['id']==$cv['shopid']){
                    $data['id'] = $cv['id'];
                    $data['goods_id'] = $cv['goods_id'];
                    $data['num'] = $cv['num'];
                    //$data['goods_attr'] = json_decode($cv['goods_attr'],true);
                  /*--chen*/
                    $data['total'] = $cv['total'];
                    $data['goods_attr'] = '';
                    if ($cv['sku_id'] != 0) {
                    	$data['total'] = Db::name('GoodsSttrxsku')->where('id',$cv['sku_id'])->value('number');
                    	
                        $goods_attr = json_decode($cv['goods_attr'],true);
                        if(!empty($goods_attr)){
                            foreach ($goods_attr as $ks=>$vs){
                                $SttrName=Db::name('GoodsSttr')->where('id',$ks)->value('key');
                                $SttrValName=Db::name('GoodsSttrval')->where('id',$vs)->value('sttr_value');
                                $data['goods_attr'] .=  $SttrName.':'.$SttrValName.' ';
                            } 
                        }                          
                        
                        
                    }
                    /*--chen*/
                    $data['title'] = $cv['title'];
                    // $data['price'] = $cv['price'];
                    $data['price'] = ($cv['sku_id']==0)?$cv['price']:(Db::name('GoodsSttrxsku')->where('id',$cv['sku_id'])->value('money'));
                    $data['check'] = 'false';
                    $headimgs = explode(',',$cv['headimg']);
                    $data['headimg'] = $this->domain().$headimgs[0];
                    $shops[$k]['goods'][] = $data;
                  
                  
                }
            }
        }
        $this->json_success($shops);


    }


    //根据城市名称获取经纬度
    public function getlng()
    {
        $city_name=input('post.qu_name');
        $area_name = input('post.area_name');
        if(empty($area_name) || empty($city_name)){
            $this->json_error('请传过来地区名称');
        }
        $map['a.area_name']  = ['like','%'.$city_name.'%'];
        $map['b.area_name']  = ['like','%'.$area_name.'%'];
        // $map['level']  = ['eq','3'];
        $data = Db::name('area')->alias('a')
        ->join('area b','a.id=b.parent_id','LEFT')
        ->field("b.*")
        ->where($map)->find();
        if(empty($data)){
            $this->json_error('没有数据');
        }else{
            $this->json_success($data,'获取经纬度成功');
        }

    }
    
    
     //版本更新 
    
   public function getVersion(){
       $type=input('post.app_type');//app类型0安卓1ios
       $version=input('post.version');//版本号
       $info=Db::name("app_version")->where(["app_type"=>$type])->order("id desc")->limit(1)->find();
       if($info['version']>$version){
        $this->json_success($info,'版本更新');
       }else{
        $this->json_error('无需更新');
       }
   }



	// 用户物流详情

	public function logistics()
    {
        $user_id = input('post.user_id');
        if(null===$user_id){
            $this->json_error('请传过来用户编号');
        }
        $arr = array();
        
        $orders = Db::name('order')->where('user_id',$user_id)->where('status',3)->where('expresssn','not null')->select();
        // halt($orders);
        $key = Config::get('kuaidi')['key'];                      //客户授权key
        $customer = Config::get('kuaidi')['cus'];                 //查询公司编号
        foreach($orders as $k => $v)
        {
            $com = $v['expresscom'];
            $num = $v['expresssn'];
            // $com = 'zhongtong';
            // $num = '73124309026125';
            
            $param = array (
                'com' => $com,           //快递公司编码
                'num' => $num,   //快递单号
                'phone' => '',              //手机号
                'from' => '',               //出发地城市
                'to' => '',                 //目的地城市
                'resultv2' => '1'           //开启行政区域解析
            );
        
            //请求参数
            $post_data = array();
            $post_data["customer"] = $customer;
            $post_data["param"] = json_encode($param);
            $sign = md5($post_data["param"].$key.$post_data["customer"]);
            $post_data["sign"] = strtoupper($sign);
            
            $url = 'http://poll.kuaidi100.com/poll/query.do';   //实时查询请求地址
            
            $params = "";
            foreach ($post_data as $k=>$v) {
                $params .= "$k=".urlencode($v)."&";     //默认UTF-8编码格式
            }
            $post_data = substr($params, 0, -1);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($ch);
            $data = str_replace("\"", '"', $result );
            $aaa = json_decode($data,true);
            if($aaa['message'] == 'ok'){
                $aaa = array_shift($aaa['data']);
            }
            $arr[] = $aaa;
            // $num[] = $aaa;
        }
       $arr = json_encode($arr);
       $this->json_success($arr);
    }



}