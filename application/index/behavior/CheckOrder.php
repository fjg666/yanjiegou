<?php
namespace app\index\behavior;
use think\Db;
class CheckOrder{
    public function run(&$params)
    {
        $time =strtotime("-10day");
        $where['status']=array('eq',3);
        $where['sendtime']=array('lt',$time);
        $order=Db::name("order")->where($where)->whereOr(['status'=>4])->select();
        foreach($order as $k=>$v){
            $refud_order=Db::name('orderrefund')->where('order_id',$v['id'])->find();
            if(empty($refud_order)){
                $data=array(
                    'affirmtime'=>time(),
                    'overtime'=>time(),
                    'status'=>5,
                    'id'=>$v['id']
                );
                Db::name('order')->update($data);
            }
        }

    }
}