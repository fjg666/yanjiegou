<?php
namespace app\shop\model;
use think\Model;
use think\Db;
class Admin extends Model
{
    protected $pk = 'admin_id';
    public function login($data,$code='open'){
        if($code=='open'){
            if(!$this->check($data['vercode'])){
                return ['code' => 0, 'msg' => '验证码错误'];
            }
        }
        $user=self::alias('a')
             ->join('shop s','s.id = a.sid','LEFT')
             ->join('shop_auth_group ag','a.group_id = ag.group_id','left')
             ->where(['a.username'=>$data['username'],'a.type'=>2])
             ->field('a.pwd,a.admin_id,a.username,a.is_open,a.group_id,a.avatar,s.id,s.name,s.status,s.shoplogo,ag.rules,ag.is_super,s.is_lock shop_lock,s.shortname') 
             ->find(); 
        if($user) {
            if ($user['shop_lock']==1) {
                return ['code' => 0, 'msg' => '此商家已被禁用'];
            }     
            if ($user['is_open']==1 && $data['password'] == authcode($user['pwd'],'DECODE')){
                $sinfo=[];
                $avatar = $user['avatar'] == '' ? '/static/admin/images/0.jpg' : $user['avatar'];
                $sinfo['ursname']=$user['username'];
                $sinfo['ursid']=$user['admin_id'];
                $sinfo['shid']=$user['id'];
                $sinfo['grid']=$user['group_id'];
                $sinfo['shname']=$user['name'];
                $sinfo['shlogo']=$user['shoplogo'];
                $sinfo['shortname']=$user['shortname'];
                $sinfo['avatar']=$avatar;
                $sinfo['rules']=$user['rules'];
                $sinfo['iswho']=$user['is_super'];
                session('sinfo',$sinfo);
                self::where(['admin_id'=>$user['admin_id']])->update(['ip'=>request()->ip()]);
                return ['code' => 1, 'msg' => '登录成功!']; //信息正确
            }else{
                return ['code' => 0, 'msg' => '用户名或者密码错误，重新输入!']; //密码错误
            }
        }else{
            return ['code' => 0, 'msg' => '用户不存在!']; //用户不存在
        }
    }
    public function getInfo($admin_id){
        $info = Db::name('admin')->field('pwd',true)->find($admin_id);
        return $info;
    }
    public function check($code){
        return captcha_check($code);
    }

} 

