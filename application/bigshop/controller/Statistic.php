<?php
namespace app\bigshop\controller;
use think\Db;
use think\Request;
use app\bigshop\controller\Common;
class Statistic extends Common{
    public function _initialize(){
        parent::_initialize();
    }
    //商品列表
    public function goodPh(){
         if(Request::instance()->isAjax()){
            $page =input('page')?input('page'):1;
            $pageSize =input('limit')?input('limit'):config('pageSize');
            $map=[];
            // $map['a.status']=1;
            // $map['a.check_status']=1;
            $map['s.bshopid']=SHID;
            $keyword=input('key');
            if(!empty($keyword)){$map['a.title']=array('like','%'.$keyword.'%');}
            $list = model('goods')->alias('a')
                ->join('goodsCategory c','c.id = a.catid','LEFT')
                ->join('shop s','s.id = a.shopid','LEFT')
                ->field('a.title,a.headimg,a.sold,a.goodsn,c.catname,s.name as shopname')
                ->where($map)
                ->order("sold desc")
                ->paginate(array('list_rows'=>$pageSize,'page'=>$page))
                ->each(function($row,$key){
                    $row['headimg'] = explode(',',$row['headimg'])[0];
                })
                ->toArray();
            return ['code'=>0,'msg'=>"获取成功",'data'=>$list['data'],'count'=>$list['total'],'rel'=>1];
        }
        return $this->fetch();
    }

    //商品列表
    public function shop(){
         if(Request::instance()->isAjax()){
            $page =input('page')?input('page'):1;
            $pageSize =input('limit')?input('limit'):config('pageSize');
            $map=[];
            // $map['a.status']=1;
            // $map['a.check_status']=1;
            $map['a.bshopid']=SHID;
            $keyword=input('key');
            if(!empty($keyword)){$map['a.title']=array('like','%'.$keyword.'%');}
            $list = model('shop')->alias('a')
                ->join('order o','a.id = o.shop_id','LEFT')
                ->field('a.id,a.shoplogo,a.name as shopname,sum(o.total_num) as num,sum(o.money) as money')
                ->where($map)
                ->order("money desc")
                ->paginate(array('list_rows'=>$pageSize,'page'=>$page))
                ->toArray();
            return ['code'=>0,'msg'=>"获取成功",'data'=>$list['data'],'count'=>$list['total'],'rel'=>1];
        }
        return $this->fetch();
    }

    /**
     * 订单统计
     */
    public function order(){
        $date=input('date/s','');
        if (empty($date)) {
           $startDate=date('Y-m-d',strtotime("-1month"));
           $endDate=date('Y-m-d');
        }else{
           $date=explode('~',$date);
           $startDate=trim($date[0]); 
           $endDate=trim($date[1]); 
        }
        $start = date('Y-m-d 00:00:00',strtotime($startDate));
        $end = date('Y-m-d 23:59:59',strtotime($endDate));
        $map['shop_id']=SHID;
        $rs = Db::name('order')->field('add_time,count(id) total')
                ->whereTime('add_time','between',[$start,$end])
                ->where($map)
                ->order('add_time asc')
                ->group("date_format(from_unixtime(add_time),'%Y-%m-%d')")->select();
        $top=$bottom=[];        
        foreach ($rs as $k => $v) {
            $top[]=date('Ymd',$v['add_time']);
            $bottom[]=$v['total'];
        }
        $this->assign('date',$startDate.' ~ '.$endDate);
        $this->assign('top',implode(',',$top));
        $this->assign('bottom',implode(',',$bottom));
        return $this->fetch();
    }

    /**
     * 销售统计
     */
    public function sale(){
        $date=input('date/s','');
        if (empty($date)) {
           $startDate=date('Y-m-d',strtotime("-1month"));
           $endDate=date('Y-m-d');
        }else{
           $date=explode('~',$date);
           $startDate=trim($date[0]); 
           $endDate=trim($date[1]); 
        }
        $start = date('Y-m-d 00:00:00',strtotime($startDate));
        $end = date('Y-m-d 23:59:59',strtotime($endDate));
        $map['shop_id']=SHID;
        $rs = Db::name('order')->field('add_time,sum(money) total')
                ->whereTime('add_time','between',[$start,$end])
                ->where($map)
                ->order('add_time asc')
                ->group("date_format(from_unixtime(add_time),'%Y-%m-%d')")->select();
        $top=$bottom=[];        
        foreach ($rs as $k => $v) {
            $top[]=date('Ymd',$v['add_time']);
            $bottom[]=$v['total'];
        }
        $this->assign('date',$startDate.' ~ '.$endDate);
        $this->assign('top',implode(',',$top));
        $this->assign('bottom',implode(',',$bottom));
        return $this->fetch();
    }
    
     /**
     * 会员统计
     */
    public function membersale(){
         if(Request::instance()->isAjax()){
            $page =input('page')?input('page'):1;
            $pageSize =input('limit')?input('limit'):config('pageSize');
            $map=[];
            $keyword=input('key');
            if(!empty($keyword)){$map['a.title']=array('like','%'.$keyword.'%');}
            $list = model('order')->alias('o')
                ->join('users a','o.user_id = a.id','LEFT')
                ->field('a.username,a.mobile,a.reg_time,a.id,sum(money) counts')
                ->where($map)
                ->order("counts desc")
                ->group('o.user_id')
                ->paginate(array('list_rows'=>$pageSize,'page'=>$page))
                ->each(function($row,$key){
                    $row['reg_time'] = date('Y-m-d',$row['reg_time']);
                })
                ->toArray();
            return ['code'=>0,'msg'=>"获取成功",'data'=>$list['data'],'count'=>$list['total'],'rel'=>1];
        }
        return $this->fetch();
    }

    /**
     * 会员增长趋势
     */
    public function membertrend(){
        $date=input('date/s','');
        if (empty($date)) {
           $startDate=date('Y-m-d',strtotime("-1month"));
           $endDate=date('Y-m-d');
        }else{
           $date=explode('~',$date);
           $startDate=trim($date[0]); 
           $endDate=trim($date[1]); 
        }
        $start = date('Y-m-d 00:00:00',strtotime($startDate));
        $end = date('Y-m-d 23:59:59',strtotime($endDate));
        $rs = Db::name('users')->field('reg_time,count(id) total')
                ->whereTime('reg_time','between',[$start,$end])
                //->where()
                ->order('reg_time asc')
                ->group("date_format(from_unixtime(reg_time),'%Y-%m-%d')")->select();
        $top=$bottom=[];        
        foreach ($rs as $k => $v) {
            $top[]=date('Ymd',$v['reg_time']);
            $bottom[]=$v['total'];
        }
        $this->assign('date',$startDate.' ~ '.$endDate);
        $this->assign('top',implode(',',$top));
        $this->assign('bottom',implode(',',$bottom));
        return $this->fetch();
    }

}