<?php
namespace app\admin\controller;
use app\admin\model\Users as UsersModel;
use app\api\controller\Base;
use think\request;
use app\admin\controller\Common;
class Users extends Common{
    //会员列表
    public function index(){
        if(request()->isPost()){
            $key=input('post.key');
            $page =input('page')?input('page'):1;
            $pageSize =input('limit')?input('limit'):config('pageSize');
            $list=db('users')->alias('u')
                ->join(config('database.prefix').'user_level ul','u.level = ul.level_id','left')
                ->field('u.*,ul.level_name')
                ->where('u.email|u.mobile|u.username','like',"%".$key."%")
                ->order('u.id desc')
                ->paginate(array('list_rows'=>$pageSize,'page'=>$page))
                ->toArray();
            foreach ($list['data'] as $k=>$v){
                $list['data'][$k]['reg_time'] = date('Y-m-d H:s',$v['reg_time']);
            }
            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];
        }
        return $this->fetch();
    }
    //设置会员状态
    public function usersState(){
        $id=input('post.id');
        $is_lock=input('post.is_lock');
        if(db('users')->where('id='.$id)->update(['is_lock'=>$is_lock])!==false){
            return ['status'=>1,'msg'=>'设置成功!'];
        }else{
            return ['status'=>0,'msg'=>'设置失败!'];
        }
    }
    //添加会员
    public function add()
    {
        if(Request::instance()->isAjax()) {
            $data = input('post.');
            $data['reg_time'] = time();
            $data['password']=authcode($data['password']);
            $user = new UsersModel();
            $res = db('users')->insert($data);
            if($res){
                $result['code'] = 1;
                $result['msg'] = '会员添加成功!';
                $result['url'] = url('admin/users/index');
                return $result;
            }else{
                $result['code'] = 0;
                $result['msg'] = '会员添加失败!';
                $result['url'] = url('admin/users/index');
                return $result;
            }
        }else{

            $user_level=db('user_level')->order('sort')->select();
            $this->assign('title',lang('edit').lang('user'));
            $this->assign('user_level',$user_level);
            $arealist = Base::provice();
            $this->assign('arealist',$arealist);

            return $this->fetch();
        }
    }

    //获取子地区
    public function getchildarea(){
        $parent_id = request()->post('parent_id');
        $putype = request()->post('putype');
        Base::getchildareamy($parent_id,$putype);
    }



    //编辑会员
    public function edit($id=''){
        if(request()->isPost()){
            $user = db('users');
            $data = input('post.');
            if(empty($data['password'])){
                unset($data['password']);
            }else{
                $data['password']=authcode($data['password']);
            }
            if ($user->update($data)!==false) {
                $result['msg'] = '会员修改成功!';
                $result['url'] = url('index');
                $result['code'] = 1;
            } else {
                $result['msg'] = '会员修改失败!';
                $result['code'] = 0;
            }
            return $result;
        }else{
            $user_level=db('user_level')->order('sort')->select();
            $info = UsersModel::get($id);
            $this->assign('info',$info);
            $this->assign('title',lang('edit').lang('user'));
            $this->assign('user_level',$user_level);
            return $this->fetch();
        }
    }


    public function usersDel(){
        db('users')->delete(['id'=>input('id')]);
        db('user_oauth')->delete(['uid'=>input('id')]);
        return $result = ['code'=>1,'msg'=>'删除成功!'];
    }
    public function delall(){
        $map[] =array('id','IN',input('param.ids/a'));
        db('users')->where($map)->delete();
        $result['msg'] = '删除成功！';
        $result['code'] = 1;
        $result['url'] = url('index');
        return $result;
    }

    /***********************************会员组***********************************/
    public function userGroup(){
        if(request()->isPost()){
            $userLevel=db('user_level');
            $list=$userLevel->order('sort')->select();
            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list,'rel'=>1];
        }
        return $this->fetch();
    }
    public function groupAdd(){
        if(request()->isPost()){
            $data = input('post.');
            db('user_level')->insert($data);
            $result['msg'] = '会员组添加成功!';
            $result['url'] = url('userGroup');
            $result['code'] = 1;
            return $result;
        }else{
            $this->assign('title',lang('add')."会员组");
            $this->assign('info','null');
            return $this->fetch('groupForm');
        }
    }
    public function groupEdit(){
        if(request()->isPost()) {
            $data = input('post.');
            $res = db('user_level')->where('level_id',$data['level_id'])->update($data);
            if($res){
                $result['msg'] = '会员组修改成功!';
                $result['url'] = url('userGroup');
                $result['code'] = 1;
                return $result;
            }else{
                $result['msg'] = '会员组修改失败!';
                $result['url'] = url('userGroup');
                $result['code'] = 0;
                return $result;
            }

        }else{
            $map['level_id'] = input('param.level_id');
            $info = db('user_level')->where($map)->find();
            $this->assign('title',lang('edit')."会员组");
            $this->assign('info',$info);
            return $this->fetch('groupEdit');
        }
    }
    public function groupDel(){
        $level_id=input('level_id');
        if (empty($level_id)){
            return ['code'=>0,'msg'=>'会员组ID不存在！'];
        }
        db('user_level')->where(array('level_id'=>$level_id))->delete();
        return ['code'=>1,'msg'=>'删除成功！'];
    }
    public function groupOrder(){
        $userLevel=db('user_level');
        $data = input('post.');
        $userLevel->update($data);
        $result['msg'] = '排序更新成功!';
        $result['url'] = url('userGroup');
        $result['code'] = 1;
        return $result;
    }




}