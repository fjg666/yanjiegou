<?php
namespace app\api\model;
use think\Model;
use think\Db;
class Shop extends Model
{

    //商家粉丝
    public function shopFans(){
        return $this->hasMany("collectionshop","shop_id");
    }
    //商家商品数量
    public function shopGoodsNum(){
        return $this->hasMany("goods","id");
    }
    //商家销量
    public static function shopOrderNum($shop_id){
        $order_num=Db::name("order")->where(['shop_id'=>$shop_id,'status'=>5])->count();
        return $order_num;
    }
    
}