<?php
namespace app\admin\Controller;
use app\api\controller\Base;
use think\Controller;
use think\Db;
use ensh\Tree;
use think\Request;
use app\admin\controller\Common;
class Bigshop extends Common{
    protected  $model;
    public function _initialize(){
        parent::_initialize();
        $this->model=Db::name('bigshop');
        $this->assign('logomoduleid',113);
        $this->assign('albummoduleid',114);
    }
    /*
     * 商圈列表
     */
    public function index(){
        if(Request::instance()->isAjax()){
            $keyword=input('key');
            $page =input('page')?input('page'):1;
            $pageSize =input('limit')?input('limit'):config('pageSize');
            $map=[];
            if(!empty($keyword) ){
                $map['name']=array('like','%'.$keyword.'%');
            }
            //$list=db('bigshop')->where($map)->order('sort desc')->paginate(array('list_rows'=>$pageSize,'page'=>$page))->toArray();
            $list = $this->model->where($map)->order('sort desc')->paginate(array('list_rows'=>$pageSize,'page'=>$page))->toArray();
            foreach($list['data'] as $k=>$v){
                $list['data'][$k]['myaddress'] = $v['province'].$v['city'].$v['area'].$v['street'].$v['address'];
            }
            $rsult['code'] = 0;
            $rsult['msg'] = "获取成功";
            $lists = $list['data'];
            $rsult['data'] = $lists;
            $rsult['count'] = $list['total'];
            $rsult['rel'] = 1;
            return $rsult;
        }else{
            return $this->fetch();
        }
    }
    public function add(){
        if(Request::instance()->isAjax()){
            $data = input('post.');
            $msg = $this->validate($data,'Bigshop');
            if($msg!='true'){
                return $result = ['code'=>0,'msg'=>$msg];
            }
            $ad_data=['username'=>$data['admin_name'],'pwd'=>$data['admin_pwd']];
            $msg = $this->validate($ad_data,'BigshopAdmin');
            if($msg!='true'){
                return $result = ['code'=>0,'msg'=>$msg];
            }
            $res=model('bigshop')::creatadmin($data);
            if($res){
                $result['code'] = 1;
                $result['msg'] = '添加商圈成功!';
                $result['url'] = url('admin/bigshop/index');
                return $result;
            }else{
                $result['code'] = 0;
                $result['msg'] = '添加商圈失败!';
                $result['url'] = url('admin/bigshop/index');
                return $result;
            }
        }else{
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
    public function edit(){
        if(Request::instance()->isAjax()){
            $data = input('post.');
            unset($data['upfile']);
            $count = count($data['headimg']);//获取传过来有几张图片
            if($count){
                $data['headimg'] = implode(',',$data['headimg']);
            }
            $msg = $this->validate($data,'Bigshop');
            if($msg!='true'){
                return $result = ['code'=>0,'msg'=>$msg];
            }
            $res = $this->model->update($data,['id' => input('post.id')]);
            if($res){
                $result['code'] = 1;
                $result['msg'] = '修改商圈成功!';
                $result['url'] = url('admin/bigshop/index');
                return $result;
            }else{
                $result['code'] = 0;
                $result['msg'] = '修改商圈失败!';
                $result['url'] = url('admin/bigshop/index');
                return $result;
            }
        }else{
            $id=input('id');
            $info=$this->model->where(array('id'=>$id))->find();
            $headimg = explode(',',$info['headimg']);
            foreach($headimg as $k=>$v){
                if(!is_object($v)){
                    $info['src'][] = $v;
                }
            }
            $this->assign('info',$info);
            $arealist = Base::provice();
            $this->assign('arealist',$arealist);
            return $this->fetch();
        }
    }
    /*
     * 排序
     */
    public function listorder(){
        $model =db('bigshop');
        $data = input('post.');
        $model->update($data);
        $result = ['msg' => '排序成功！','url'=>url('Bigshop/index'), 'code' => 1];
        return $result;
    }
    /*
     * 单个删除
     */
    public function listDel(){
        $id = input('post.id');
        $model = db('bigshop');
        $model->where(array('id'=>$id))->delete();//转入回收站
        return ['code'=>1,'msg'=>'删除成功！'];
    }
    /*
     * 多个删除
     */
    public function delAll(){
        $id=input('post.ids/a');
        $id=implode(",",$id);
        $model = db('bigshop');
        $model->where("id in ($id)")->delete();
        $result['code'] =1;
        $result['msg'] ='删除成功！';
        return $result;
    }

    /**
     * 商家管理员
     **/
    public function admin(){
        if (request()->isPost()) {
            $data=input('post.');
            $admin_data=[
                'username'=>$data['admin_name'],
                'pwd'=>authcode($data['admin_pwd']),
            ];
            $map=[
                'type'=>1,
                'sid'=>$data['sid'],
                'admin_id'=>$data['aid'],
            ];
            if(Db::name('BigshopAdmin')->where($map)->update($admin_data)){
                $result['code'] =1;
                $result['msg'] ='成功！';
                return $result;
            }
            $result['code'] =0;
            $result['msg'] ='失败！';
            return $result;
        }
        $sid=input('get.id/d');
        $info=Db::name('BigshopAdmin')->alias('a')
            ->join('BigshopAuthGroup s','s.group_id = a.group_id','LEFT')
            ->where(['a.type'=>1,'sid'=>$sid,'is_super'=>1])->field('a.admin_id,a.sid,s.group_id,username,rules')->find(); 
        $this->assign('info',$info);
        return $this->fetch();
    }

    public function floor(){
        $id=input('get.id');
        $floor=Db::name('floor')->where(['bs_id'=>$id])->column('*','id');
        foreach ($floor as $k => $v) {
            $floor[$k]['shop']=Db::name('shop')->where(['bshopid'=>$id,'floor_id'=>$k])->field('id,name')->select();
        }
        $this->assign('floor',$floor);
        return $this->fetch();
    }
}