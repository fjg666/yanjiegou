<?php
namespace app\admin\controller;
class UsersCard extends Common{

    protected  $model;
    public function _initialize(){
        parent::_initialize();
        $this->model=model('UsersCard');
    }
     /**
      * 首页数据
      **/
     public function index(){
        if(request()->isPost()){
            $keyword=input('key');
            $page =input('page')?input('page'):1;
            $pageSize =input('limit')?input('limit'):config('pageSize');
            $map=[];
            if(!empty($keyword) ){
                $map['uc.name']=array('like','%'.$keyword.'%');
            }
            $list=$this->model->alias('uc')
                 ->join('shop s','s.id = uc.shopid','LEFT')
                 ->field('uc.*,s.name as shopname')
                 ->where($map)
                 ->order('uc.sort asc')
                 ->paginate(array('list_rows'=>$pageSize,'page'=>$page))
                 ->each(function($row,$key){
                    $row['status'] = get_status($row['status'],'userscard_status');
                    $row['shopname']=$row['shopid']==0?'平台':$row['shopname'];
                 })->toArray();    
            $rsult['code'] = 0;
            $rsult['msg'] = "获取成功";
            $lists = $list['data'];
            $rsult['data'] = $lists;
            $rsult['count'] = $list['total'];
            $rsult['rel'] = 1;
            return $rsult;
        }
        return $this->fetch();
     }

     /**
      * 添加会员卡
      **/
    public function add(){
        if(request()->isPost()) {           
            $data = input('post.');
            $data['adduid'] = session('seadmininfo.aid');
            $data['addusername'] = session('seadmininfo.username');
            $data['shopid']=0;
            $data['create_time']=time();
            $insert=$this->model->insert($data);
            if($insert){
                return $this->resultmsg('添加成功');
            }
            return $this->resultmsg('添加失败',0);
        }
        return $this->fetch();
     }

     /**
      * 编辑会员卡 
      **/
    public function edit(){
        if(request()->isPost()) {
           $data=input('post.');
            if($this->model->update($data)){
                return $this->resultmsg('修改成功');
            }
            return $this->resultmsg('修改失败',0);
        }
        $id = input('id');
        if($id){
            $info=$this->model->find($id);
            $this->assign('info',$info);
            return $this->fetch();
        }else{           
            return $this->fetch("add");
        }
       
     }

    public function listorder(){
        $data = input('post.');
        $this->model->update($data);
        return $this->resultmsg('排序成功',1,url('index'));
    }

    public function listDel(){
        $id = input('post.id');
        $this->model->where(array('id'=>$id))->delete();//转入回收站
        return $this->resultmsg('删除成功',1);
    }


    public function delAll(){
        $id=input('post.ids/a');
        $id=implode(",",$id);
        $this->model->where("id in ($id)")->delete();
        return $this->resultmsg('删除成功',1);
    }
    /**
     * 平台会员卡
     **/
    public function info(){
        $info=$this->model->where(['shopid'=>0])->find();       
        $this->assign('info',$info);
        return $this->fetch();
    }
}?>