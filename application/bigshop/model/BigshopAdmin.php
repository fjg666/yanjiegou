<?php
namespace app\bigshop\model;
use think\Model;
use think\Db;
class BigshopAdmin extends Model
{
    protected $pk = 'admin_id';
    public function login($data,$code='open'){
        if($code=='open'){
            if(!$this->check($data['vercode'])){
                return ['code' => 0, 'msg' => '验证码错误'];
            }
        }
        $user=self::alias('a')
             ->join('bigshop s','s.id = a.sid','LEFT')
             ->join('bigshop_auth_group ag','a.group_id = ag.group_id','left')
             ->where(['a.username'=>$data['username'],'a.type'=>1])
             ->field('a.pwd,a.admin_id,a.username,a.is_open,a.group_id,a.avatar,s.id,s.name,s.status,s.bigshoplogo,ag.rules,ag.is_super') 
             ->find(); 
        if($user) {
            if ($user['status']==0) {
                return ['code' => 0, 'msg' => '此商圈已被禁用'];
            }     
            if ($user['is_open']==1 && $data['password'] == authcode($user['pwd'],'DECODE')){
                $bsinfo=[];
                $avatar = $user['avatar'] == '' ? '/static/admin/images/0.jpg' : $user['avatar'];
                $bsinfo['ursname']=$user['username'];
                $bsinfo['ursid']=$user['admin_id'];
                $bsinfo['shid']=$user['id'];
                $bsinfo['grid']=$user['group_id'];
                $bsinfo['shname']=$user['name'];
                $bsinfo['shlogo']=$user['bigshoplogo'];
                $bsinfo['avatar']=$avatar;
                $bsinfo['rules']=$user['rules'];
                $bsinfo['iswho']=$user['is_super'];
                session('bsinfo',$bsinfo);
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
        $info = self::field('pwd',true)->find($admin_id);
        return $info;
    }
    public function check($code){
        return captcha_check($code);
    }

} 

