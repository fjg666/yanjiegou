<?php
namespace app\shop\controller;
use think\Controller;
use think\captcha\Captcha;
use think\request;
use think\Session;
use think\Db;
class Login extends Controller
{
    private $system,$code;
    public function _initialize(){
        if (session('shop_ursid')) {
            $this->redirect('shop/index/index');
        }
    }
    public function index(){
        if(request()->isPost()) {
            $data = input('post.');
            return model('ShopAdmin')->login($data);
        }else{
            return $this->fetch();
        }
    }
    public function verify(){
        $config =    [
            // 验证码字体大小
            'fontSize'    =>    25,
            // 验证码位数
            'length'      =>    4,
            // 关闭验证码杂点
            'useNoise'    =>    false,
            'bg'          =>    [255,255,255],
        ];
        $captcha = new Captcha($config);
        return $captcha->entry();
    }
    //忘记密码
    public function forget(){
        if(request()->isPost()){
            $data=input('post.');
            $phone=$data['phone'];//手机号
            $vercode=$data['vercode'];//
            $mscode=$data['mscode'];//短信验证码
            $username=$data['username'];
            if(!captcha_check($vercode)){
                return ['code' => 0, 'msg' => '验证码错误'];
            }
            if(Session::get($phone.'code') !=$mscode){
                return ['code' => 0, 'msg' => '短信验证码错误'];
            }
            $shop_info=Db::name('shop')->alias("a")
            ->join('shop_admin b',"a.id=b.sid","LEFT")
            ->field("b.admin_id")
            ->where(['a.phone'=>$phone,'b.username'=>$username])->find();           
            if(empty($shop_info)){
                return ['code' => 0, 'msg' => '账户不存在请联系客服！'];
            }else{
                Session::set('admin_id',$shop_info['admin_id']);
                return ['code' => 1, 'msg' =>"验证成功！"];
            }
            
        }else{
            return view();
        }
       
    }
    //修改密码
    public function changepwd(){  
        if(request()->isPost()){
            $data=input('post.');
            $username=$data['username'];
            $pwd1=$data['pwd1'];//
            $pwd=$data['pwd'];
            $admin_id=Session::get('admin_id');           
            if($admin_id==NULL){
                return ['code' => 0, 'msg' => '非法来源'];
            }
            if($pwd1 !=$pwd){
                return ['code' => 0, 'msg' => '确认密码不一样'];
            }
            $info=array(
                'username'=>$username,
                'pwd'=>authcode($pwd)
            );
            $user=Db::name('ShopAdmin')->where('username',$username)->where('admin_id','<>',$admin_id)->find();
            if(!empty($user)){
                return ['code' => 0, 'msg' => '用户名已存在请重新选择！'];
            }
            $result=Db::name('ShopAdmin')->where(['admin_id'=>$admin_id])->update($info);
            if($result){
                Session::set('admin_id',null);
                return ['code' => 1, 'msg' => '重置账户密码成功！'];               
            }else{               
                return ['code' => 0, 'msg' =>"重置账户密码失败！"];
            }
            
        }else{            
            return view();
        }
    }
}