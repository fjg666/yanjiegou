<?php
namespace app\bigshop\controller;
use think\Db;
use app\bigshop\controller\Common;
class Shop extends Common{
	protected  $shop,$floor;
    public function _initialize(){
        parent::_initialize();
        $this->shop=model('shop');
        $this->floor=Db::name('floor');
    }

    /**
     * 楼层列表
     **/
    public function floor(){
    	if(request()->isAjax()){
            $page =input('page')?input('page'):1;
            $pageSize =input('limit')?input('limit'):config('pageSize');
            $map['bs_id']=SHID;
            $list = $this->floor->order("id desc")
                 ->where($map)
                 ->paginate(array('list_rows'=>$pageSize,'page'=>$page))->toArray();     
            return ['code'=>0,'msg'=>"获取成功",'data'=>$list['data'],'count'=>$list['total'],'rel'=>1];
        }
        return $this->fetch();
    }
    /*
      添加楼层
     */
    public function FloorAdd(){
        if (request()->isAjax()) {
            $data=input('post.');
            $data['bs_id']=SHID;
            if($this->floor->insert($data)){
                $result['msg'] = '添加成功!';
                $result['code'] = 1;
                return $result;
            }
        }
        return $this->fetch();
    }

    /**
     * 编辑
     **/
    public function FloorEdit(){
        if (request()->isAjax()) {
            $data=input('post.');
            $data['bs_id']=SHID;
            if($this->floor->update($data)){
                $result['msg'] = '修改成功!';
                $result['code'] = 1;
                return $result;
            }
        }
        $id = input('id');
        $info=$this->floor->find($id);
        $this->assign('info', json_encode($info,true));
        return $this->fetch();
    }

    
    public function FloorDel(){
        $id = input('post.id');
        $this->floor->where(array('id'=>$id))->delete();
        return ['code'=>1,'msg'=>'删除成功！'];
    }
    /**
     * 楼层列表
     **/
    public function index(){
        if(request()->isAjax()){
            $page =input('page')?input('page'):1;
            $pageSize =input('limit')?input('limit'):config('pageSize');
            $map['bshopid']=SHID;
            $list = $this->shop->alias('s')
                 ->order("id desc")
                 ->join('floor f','f.id = s.floor_id','LEFT')
                 ->field('s.name as shopname,f.name as floorname,s.id,f.num,s.is_lock,s.lock_time,s.lock_info')
                 ->where($map)
                 ->paginate(array('list_rows'=>$pageSize,'page'=>$page))
                 ->each(function($row){
                    $row['is_lock_name']=get_status($row['is_lock'],'shop_is_lock');
                    $row['lock_time']=$row['lock_time']?date('Y-m-d H:i',$row['lock_time']):'-';
                 })
                 ->toArray();      
            return ['code'=>0,'msg'=>"获取成功",'data'=>$list['data'],'count'=>$list['total'],'rel'=>1];
        }
        return $this->fetch();
    }
    //设置商家状态
    public function setstatus(){
        $map['id'] =array('in',input('post.id/a'));
        $status=input('post.status');
        if ($status==1) {
           $data['lock_info']=input('post.info/s');
        }
        $data['lock_time']=time();
        $data['is_lock']=$status;
        if($this->shop->where($map)->update($data)!==false){
            return $this->resultmsg('设置成功！',1);
        }
        return $this->resultmsg('设置失败！',0);
    }

    public function lists(){
        if (request()->isPost()) {
            $sid=input('post.sid/a');
            $fid=input('post.fid/d');
            $map['id']=array('in',implode(',', $sid));
            if($this->shop->where($map)->update(['floor_id'=>$fid])){
                return $this->resultmsg('设置成功！',1);
            }
            return $this->resultmsg('设置失败！',0);
        }
        $list=$this->shop->where(['floor_id'=>0,'bshopid'=>SHID])->field('name,id')->select();
        $this->assign('lists',$list);
        return $this->fetch();
    }

    public function editfloor(){
        if (request()->isPost()) {
            $sid=input('post.sid/d');
            $fid=input('post.fid/d');
            $map['id']=$sid;
            if($this->shop->where($map)->update(['floor_id'=>$fid])){
                return $this->resultmsg('设置成功！',1);
            }
            return $this->resultmsg('设置失败！',0);
        }
        $floor=$this->shop->field('floor_id')->find(input('sid/d'));
        $list=$this->floor->select();
        $this->assign('lists',$list);
        $this->assign('floor',$floor);
        return $this->fetch();
    }

}