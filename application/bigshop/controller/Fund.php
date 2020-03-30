<?php
namespace app\bigshop\controller;
use think\Db;
use think\Request;
use think\View;
use app\bigshop\controller\Common;
class Fund extends Common{
    protected  $now,$log;
    public function _initialize(){
        parent::_initialize();
        $this->now = model('ShopFundNow');
        $this->log = model('ShopFundLog');
    }
    //申请列表
    public function nows(){
        if(Request::instance()->isAjax()){
            $page =input('page')?input('page'):1;
            $pageSize =input('limit')?input('limit'):config('pageSize');
            $map['a.shopid']=SHID;
            $list = $this->now->alias('a')
                ->join('shop s','s.id = a.shopid','LEFT')
                ->join('admin ad','ad.admin_id = a.douid','LEFT')
                ->field('a.*,s.name as shopname,ad.username as dousername')
                ->where($map)
                ->order("id desc")
               ->paginate(array('list_rows'=>$pageSize,'page'=>$page))
                ->each(function($row){
                    $row['status']=get_status($row['status'],'check');
                    $row['addtime']=date('Y-m-d H:i:s',$row['addtime']);
                    $row['dotime']=$row['dotime']?date('Y-m-d H:i:s',$row['dotime']):'-';
                })->toArray();    
            return ['code'=>0,'msg'=>"获取成功",'data'=>$list['data'],'count'=>$list['total'],'rel'=>1];
        }
        return $this->fetch();
    }

    //添加申请
    public function add()
    {
        if(Request::instance()->isAjax()) {
            $data = input('post.');
            $data['shopid']=SHID;
            $data['addtime']=time();
            $data['status']=0;
            $res = $this->now->insert($data);
            if($res){
                $result['code'] = 1;
                $result['msg'] = '添加成功!';
                return $result;
            }else{
                $result['code'] = 0;
                $result['msg'] = '添加失败!';
                return $result;
            }
        }else{
            return $this->fetch();
        }
    }

    //资金列表
    public function logs(){
        if(Request::instance()->isAjax()){
            $page =input('page')?input('page'):1;
            $pageSize =input('limit')?input('limit'):config('pageSize');
            $map['shopid']=SHID;
            $list = $this->log
                ->where($map)
                ->order("id desc")
               ->paginate(array('list_rows'=>$pageSize,'page'=>$page))
                ->each(function($row){
                    $row['addtime']=date('Y-m-d H:i:s',$row['addtime']);
                    $row['type']=get_status($row['type'],'shop_fund_log_type');
                })->toArray();    
            return ['code'=>0,'msg'=>"获取成功",'data'=>$list['data'],'count'=>$list['total'],'rel'=>1];
        }
        return $this->fetch();
    }

}