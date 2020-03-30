<?php
namespace app\index\behavior;
use think\Db;
class DelCartNew{//删除一建购买无效的购物车
    public function run(&$params)
    {
        $time =strtotime("-1day");
        $where['is_new']=array('eq',1);
        $where['create_time']=array('lt',$time);        
        $order=Db::name("shopcart")->where($where)->delete(); 
    }
    
}