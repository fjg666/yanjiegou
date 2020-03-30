<?php
namespace app\bigshop\controller;
use think\Db;
use ensh\Leftnav;
use app\bigshop\model\BigshopAdmin as Admin;
use app\bigshop\model\BigshopAuthGroup as AuthGroup;
use app\bigshop\model\BigshopAuthRule as authRule;
use think\request;
use think\Validate;
class Auth extends Common
{
    //管理员列表
    public function adminList(){
        if(Request::instance()->isAjax()){
            $val=input('val');
            $url['val'] = $val;
            $this->assign('testval',$val);
            $map=[];
            if($val){
                $map['username|email|tel']= array('like',"%".$val."%");
            }
            if (SUPER!=1){
                $map['a.admin_id']=URID;
            }
            $map['a.type']=1;
            $map['a.sid']=SHID;
            $list=Admin::alias('a')
                ->join('shop_auth_group ag','a.group_id = ag.group_id','left')
                ->field('a.*,ag.title,ag.is_super')
                ->where($map)
                ->select();
            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list,'rel'=>1];
        }
        return view();
    }
    //管理员添加
    public function adminAdd(){
        if(Request::instance()->isAjax()){
            $data = input('post.');
            $check_user = Admin::get(['username'=>$data['username']]);
            if ($check_user) {
                return $result = ['code'=>0,'msg'=>'用户已存在，请重新输入用户名!'];
            }
            $data['pwd'] = authcode(input('post.pwd', ''));
            $data['type']=1;
            $data['sid']=SHID;
            $data['add_time'] = time();
            //验证
            $msg = $this->validate($data,'app\admin\validate\Admin');
            if($msg!='true'){
                return $result = ['code'=>0,'msg'=>$msg];
            }
            //单独验证密码
            $checkPwd = Validate::make([input('post.pwd')=>'require']);
            if (false === $checkPwd) {
                return $result = ['code'=>0,'msg'=>'密码不能为空！'];
            }
            unset($data['file']);
            //添加
            if (Admin::create($data)){
                return ['code'=>1,'msg'=>'管理员添加成功!','url'=>url('adminList')];
            } 
            return ['code'=>0,'msg'=>'管理员添加失败!'];
        }else{
            $auth_group = AuthGroup::where(['type'=>1,'shopid'=>SHID])->select();
            $this->assign('authGroup',$auth_group);
            $this->assign('title',lang('add').lang('admin'));
            $this->assign('info','null');
            $this->assign('selected', 'null');
            return view('adminForm');
        }
    }
    //删除管理员
    public function adminDel(){
        $admin_id=input('post.admin_id');
        if (SUPER==1){
            Admin::where('admin_id','=',$admin_id)->delete();
            return $result = ['code'=>1,'msg'=>'删除成功!'];
        }
        return $result = ['code'=>0,'msg'=>'您没有删除管理员的权限!'];
    }
    //修改管理员状态
    public function adminState(){
        $id=input('post.id');
        $is_open=input('post.is_open');
        if (empty($id)){
            $result['status'] = 0;
            $result['info'] = '用户ID不存在!';
            $result['url'] = url('adminList');
            return $result;
        }
        Admin::where('admin_id='.$id)->update(['is_open'=>$is_open]);
        $result['status'] = 1;
        $result['info'] = '用户状态修改成功!';
        $result['url'] = url('adminList');
        return $result;
    }
    //更新管理员信息
    public function adminEdit(){
        if(request()->isPost()){
            $data = input('post.');
            $pwd=input('post.pwd');
            $map['admin_id']=array('neq',$data['admin_id']);
            $where['admin_id'] = $data['admin_id'];
            if($data['username']){
                $map['username']=$data['username'];
                $check_user = Admin::where($map)->find();
                if ($check_user) {
                    return $result = ['code'=>0,'msg'=>'用户已存在，请重新输入用户名!'];
                }
            }
            if ($pwd){
                $data['pwd']=authcode(input('post.pwd', ''));
            }else{
                unset($data['pwd']);
            }
            $msg = $this->validate($data,'app\admin\validate\Admin');
            if($msg!='true'){
                return $result = ['code'=>0,'msg'=>$msg];
            }
            $data['type']=1;
            $data['sid']=SHID;
            unset($data['file']);
            Admin::update($data,$where);
            if( $data['admin_id'] == URID){
                session('bsinfo.ursname',$data['username']);
                $avatar = $data['avatar']==''?'/static/admin/images/0.jpg':$data['avatar'];
                session('bsinfo.avatar',$avatar);
            }
            return $result = ['code'=>1,'msg'=>'管理员修改成功!','url'=>url('adminList')];
        }else{
            $auth_group = AuthGroup::where(['type'=>1,'shopid'=>SHID])->select();
            $admin = new Admin();
            $info = $admin->getInfo(input('admin_id'));
            $this->assign('info', json_encode($info,true));
            $this->assign('authGroup',$auth_group);
            $this->assign('title',lang('edit').lang('admin'));
            return view('adminEdit');
        }
    }
    /*-----------------------用户组管理----------------------*/
    //用户组管理
    public function adminGroup(){
        if(request()->isPost()){
            $list = AuthGroup::where(['shopid'=>SHID])->select();
            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list,'rel'=>1];
        }
        return view();
    }
    //删除管理员分组
    public function groupDel(){
        AuthGroup::where('group_id','=',input('id'))->delete();
        return $result = ['code'=>1,'msg'=>'删除成功!'];
    }
    //添加分组
    public function groupAdd(){
        if(request()->isPost()){
            $data=input('post.');
            unset($data['group_id']);
            $data['addtime']=time();
            $data['shopid']=SHID;
            $data['type']=1;
            AuthGroup::create($data);
            $result['msg'] = '用户组添加成功!';
            $result['url'] = url('adminGroup');
            $result['code'] = 1;
            return $result;
        }else{
            $this->assign('title','添加用户组');
            $this->assign('info','null');
            return $this->fetch('groupForm');
        }
    }
    //修改分组
    public function groupEdit(){
        if(request()->isPost()) {
            $data=input('post.');
            $data['shopid']=SHID;
            $data['type']=1;
            $where['group_id'] = $data['group_id'];
            AuthGroup::update($data,$where);
            $result = ['code'=>1,'msg'=>'用户组修改成功!','url'=>url('adminGroup')];
            return $result;
        }else{
            $id = input('id');
            $info = AuthGroup::get(['group_id'=>$id]);
            $this->assign('info', $info);
            $this->assign('title','编辑用户组');
            return $this->fetch('groupForm');
        }
    }
    //分组配置规则
    public function groupAccess(){
        $nav = new Leftnav();
        $admin_rule=authRule::where(['type'=>1])->field('id,pid,title')->order('sort asc')->select();
        $rules = AuthGroup::where(['group_id'=>input('id'),'type'=>1])->value('rules');
        $arr = $nav->auth($admin_rule,$pid=0,$rules);
        $arr[] = array(
            "id"=>0,
            "pid"=>0,
            "title"=>"全部",
            "open"=>true
        );
        $this->assign('data',json_encode($arr,true));
        return $this->fetch();
    }
    public function groupSetaccess(){
        $rules = input('post.rules');
        if(empty($rules)){
            return array('msg'=>'请选择权限!','code'=>0);
        }
        $data = input('post.');
        $data['shopid']=SHID;
        $data['type']=1;
        $where['group_id'] = $data['group_id'];
        if(AuthGroup::update($data,$where)){
            return array('msg'=>'权限配置成功!','url'=>url('adminGroup'),'code'=>1);
        }else{
            return array('msg'=>'保存错误','code'=>0);
        }
    }
    /********************************权限管理*******************************/
    public function adminRule(){
        if(request()->isPost()){
            $arr = authRule::where(['type'=>1])->order('pid asc,sort asc')->select();
            foreach($arr as $k=>$v){
                $arr[$k]['lay_is_open']=false;
            }
            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$arr,'is'=>true,'tip'=>'操作成功'];
        }
        return view();
    }
    
    public function clear(){
        // $arr = authRule::where(['pid'=>['neq',0],'type'=>1])->select();
        // foreach ($arr as $k=>$v){
        //     $p = authRule::where(['id'=>$v['pid'],'type'=>1])->find();
        //     if(!$p){
        //         authRule::where(['id'=>$v['id'],'type'=>1])->delete();
        //     }
        // }
        // cache('authRule', NULL);
        // cache('authRuleList', NULL);
        $this->success('清除成功');
    }
    public function ruleAdd(){
        if(request()->isPost()){
            $data = input('post.');
            $data['addtime'] = time();
            $data['type']=1;
            authRule::create($data);
            // cache('authRule', NULL);
            // cache('authRuleList', NULL);
            // cache('addAuthRuleList', NULL);
            return $result = ['code'=>1,'msg'=>'权限添加成功!','url'=>url('adminRule')];
        }else{
            $nav = new Leftnav();
            // $arr = cache('addAuthRuleList');
            // if(!$arr){
                $authRule = authRule::all(function($query){
                    $query->order('sort', 'asc')->where('type',1);
                });
                $arr = $nav->menu($authRule);
                // cache('addAuthRuleList', $arr, 3600);
            // }
            $this->assign('admin_rule',$arr);//权限列表
            return $this->fetch();
        }
    }
    public function ruleOrder(){
        $data = input('post.');
        if(authRule::update($data)!==false){
            // cache('authRuleList', NULL);
            // cache('authRule', NULL);
            // cache('addAuthRuleList', NULL);
            return $result = ['code'=>1,'msg'=>'排序更新成功!','url'=>url('adminRule')];
        }else{
            return $result = ['code'=>0,'msg'=>'排序更新失败!'];
        }
    }
    //设置权限菜单显示或者隐藏
    public function ruleState(){
        $id=input('post.id');
        $menustatus=input('post.menustatus');
        if(authRule::where('id='.$id)->update(['menustatus'=>$menustatus])!==false){
            // cache('authRule', NULL);
            // cache('authRuleList', NULL);
            // cache('addAuthRuleList', NULL);
            return ['status'=>1,'msg'=>'设置成功!'];
        }else{
            return ['status'=>0,'msg'=>'设置失败!'];
        }
    }
    //设置权限是否验证
    public function ruleTz(){
        $id=input('post.id');
        $authopen=input('post.authopen');
        if(authRule::where('id='.$id)->update(['authopen'=>$authopen])!==false){
            // cache('authRule', NULL);
            // cache('authRuleList', NULL);
            // cache('addAuthRuleList', NULL);
            return ['status'=>1,'msg'=>'设置成功!'];
        }else{
            return ['status'=>0,'msg'=>'设置失败!'];
        }
    }
    public function ruleDel(){
        authRule::destroy(['id'=>input('param.id')]);
        // cache('authRule', NULL);
        // cache('authRuleList', NULL);
        // cache('addAuthRuleList', NULL);
        return $result = ['code'=>1,'msg'=>'删除成功!'];
    }
    public function ruleEdit(){
        if(request()->isPost()) {
            $datas = input('post.');
            $datas['type']=1;
            if(authRule::update($datas)) {
                // cache('authRule', NULL);
                // cache('authRuleList', NULL);
                // cache('addAuthRuleList', NULL);
                return json(['code' => 1, 'msg' => '保存成功!', 'url' => url('adminRule')]);
            } else {
                return json(['code' => 0, 'msg' =>'保存失败！']);
            }
        }else{
            $admin_rule = authRule::get(function($query){
                $query->where(['id'=>input('id'),'type'=>1])->field('id,href,title,icon,sort,menustatus');
            });
            $this->assign('rule',$admin_rule);
            return $this->fetch();
        }
    }
    
}