<?php
namespace app\admin\controller;
use think\Db;
use think\Request;
use think\View;
use app\admin\controller\Common;
class Goods extends Common{
    protected  $model,$mod_cate;
    public function _initialize(){
        parent::_initialize();
        $this->model = model('goods');
        $this->mod_cate=model('goodsCategory');
    }
    //商品列表
    public function index(){
         $way=input('way/s');
         if(Request::instance()->isAjax()){
            $catid=input('catid');
            $page =input('page')?input('page'):1;
            $pageSize =input('limit')?input('limit'):config('pageSize');
            $map=[];
            switch ($way) {
                case 1://已上架
                    $map['a.status']=1;
                    $map['a.check_status']=1;
                    break;
                case 2://待审核
                    $map['a.check_status']=0;
                    break;
                case 3://违规
                    $map['a.check_status']=-1;
                    break;         
            }
            $keyword=input('key');
            if(!empty($keyword)){$map['a.title']=array('like','%'.$keyword.'%');}
            $list = $this->model->alias('a')
                ->join('goodsCategory c','c.id = a.catid','LEFT')
                ->join('shop s','s.id = a.shopid','LEFT')
                ->field('a.*,c.catname,s.name as shopname')
                ->where($map)
                ->order("sorts desc,id desc")
                ->paginate(array('list_rows'=>$pageSize,'page'=>$page))
                ->each(function($row,$key){
                    $row['headimg'] = explode(',',$row['headimg'])[0];
                })
                ->toArray();
            return ['code'=>0,'msg'=>"获取成功",'data'=>$list['data'],'count'=>$list['total'],'rel'=>1];
        }
        $this->assign('way',$way);
        return $this->fetch('index');
    }



    //设置商品审核状态
    public function SetCheckStatus(){
        $map['id'] =array('in',input('post.id/a'));
        $status=input('post.status');
        if ($status=='-1') {
           $data['illegal_text']=input('post.text/s');
           $data['status']=0;
        }
        $data['check_status']=$status;
        if($this->model->where($map)->update($data)!==false){
            return $this->resultmsg('设置成功！',1);
        }
        return $this->resultmsg('设置失败！',0);
    }

     //设置商品上下架状态
    public function SetStatus(){
        $id=input('post.id');
        $status=input('post.status');
        if($this->model->where('id='.$id)->update(['status'=>$status])!==false){
            return $this->resultmsg('设置成功！',1);
        }
        return $this->resultmsg('设置失败！',0);
    }

    /*
       回收站
     */
    public function listDel(){
        $id = input('post.id');
        $model = $this->model;
        $model->where(array('id'=>$id))->delete();//转入回收站
        return ['code'=>1,'msg'=>'删除成功！'];
    }
    /*
      批量删除
     */
    public function delAll(){
        $map['id'] =array('in',input('post.ids/a'));
        if($this->model->where($map)->delete()){
           return $this->resultmsg('删除成功！',1);
        }
        return $this->resultmsg('删除失败！',0);
    }
    /*
      排序设置
     */
    public function listorder(){
        $model = $this->model;
        $catid = input('catid');
        $data = input('post.');
        $model->update($data);
        $result = ['msg' => '排序成功！','url'=>url('index',array('catid'=>$catid)), 'code' => 1];
        return $result;
    }
    public function delImg(){
        if(!input('post.url')){
            return ['code'=>0,'请指定要删除的图片资源'];
        }
        $file = ROOT_PATH.__PUBLIC__.input('post.url');
        if(file_exists($file) && trim(input('post.url'))!=''){
            is_dir($file) ? dir_delete($file) : unlink($file);
        }
        if(input('post.id')){
            $picurl = input('post.picurl');
            $picurlArr = explode(':',$picurl);
            $pics = substr(implode(":::",$picurlArr),0,-3);
            $model = $this->model;
            $map['id'] =input('post.id');
            $model->where($map)->update(array('pics'=>$pics));
        }
        $result['msg'] = '删除成功!';
        $result['code'] = 1;
        return $result;
    }


    public function getRegion(){
        $Region=db("region");
        $map['pid']=$_REQUEST["pid"];
        $map['type']=$_REQUEST["type"];
        $list=$Region->where($map)->select();
        echo json_encode($list);
    }

    /**
     * 修改属性
     *
     * @return void
     * @author 
     **/
    public function sttredit(){
        $data=input('post.');
        $updata[$data['sttr']]=$data['val'];

        $result=$this->model->where(array('id'=>$data['id']))->update($updata);
        if ($result) {
           return $this->resultmsg('操作成功',1);
        }
        return $this->resultmsg('操作失败',0);
    }
}