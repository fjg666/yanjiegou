<?php
namespace app\admin\controller;
use think\Db;
use think\request;
use app\admin\model\ShareSetting;

class Fsale extends Common
{
    protected $mod_set;
    public function _initialize(){
        parent::_initialize();
        $this->mod_set=model('ShareSetting');
        $this->mod_share=model('Share');
    }
    //基础设置
    public function baseset(){
        if(Request::instance()->isAjax()) {
            $data = input('post.');
            $where=['store_id'=>1];
            if($this->mod_set->where($where)->update($data)!==false){
                return ['code'=>1,'msg'=>'设置成功！','url'=>''];
            }else{
                return ['code'=>0,'msg'=>'设置失败！'];
            }
        }
        $info=$this->mod_set->where(['store_id'=>1])->find();
        $this->assign('info',$info);
        $this->assign('selected', 'null');
        $this->assign('title','分销基础设置');
        return $this->fetch();
    }
    //佣金设置
    public function feeset(){
        if(Request::instance()->isAjax()) {
            $data = input('post.');
            $where=['store_id'=>1];
            if($this->mod_set->where($where)->update($data)!==false){
                return ['code'=>1,'msg'=>'设置成功！','url'=>''];
            }else{
                return ['code'=>0,'msg'=>'设置失败！'];
            }
        }
        $info=$this->mod_set->where(['store_id'=>1])->find();
        $this->assign('info',$info);
        $this->assign('title','佣金设置');
        return $this->fetch();
    }

    /**
     * 分销商
     *
     * @return void
     * @author 
     **/
    public function share(){
       if(Request::instance()->isAjax()){
            $keyword=input('key');
            $catid=input('catid');
            $page =input('page')?input('page'):1;
            $pageSize =input('limit')?input('limit'):config('pageSize');
            $map=[];
            $sharemodel=model('share');
            if(!empty($keyword) ){
                $map['u.username|u.mobile']=array('like','%'.$keyword.'%');
            }
            $list = $sharemodel->alias('s')
                ->join('users u','s.user_id = u.id','LEFT')
                ->field('s.*,u.username')
                ->where($map)
                ->order("s.id desc")
                ->paginate(array('list_rows'=>$pageSize,'page'=>$page))
                ->each(function($row,$key){
                    $row['addtime'] = date('Y-m-d H:i:s',$row['addtime']);
                })->toArray();
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

    /**
     * 分销订单
     *
     * @return void
     * @author 
     **/
    public function shareOrder(){
        if(Request::instance()->isAjax()){
            $list=[['name'=>'<p>haha1111</p>
            <b>haha</b>','mobile'=>1111],['name'=>'haha','mobile'=>22222],['name'=>'haha','mobile'=>33333]];
            $rsult['code'] = 0;
            $rsult['msg'] = "获取成功";
            $rsult['data'] = $list;
            $rsult['count'] = 100;
            $rsult['rel'] = 1;
            return $rsult;
        }else{
            return $this->fetch();
        }
    }

    /**
     * 体现管理
     *
     * @return void
     * @author 
     **/
    public function tixian(){
        if(Request::instance()->isAjax()){
            $list=[['name'=>'<p>haha1111</p>
            <b>haha</b>','mobile'=>1111],['name'=>'haha','mobile'=>22222],['name'=>'haha','mobile'=>33333]];
            $rsult['code'] = 0;
            $rsult['msg'] = "获取成功";
            $rsult['data'] = $list;
            $rsult['count'] = 100;
            $rsult['rel'] = 1;
            return $rsult;
        }else{
            return $this->fetch();
        }
    }

}?>