<?php
namespace app\api\model;
use think\Model;
use think\Db;
class Goods extends Model
{
     //收藏
     public function collectiongoods(){
        return $this->hasMany('Collectiongoods','goods_id','id');
    }
    //收藏人
    public function if_collection($goods_id,$user_id){
        return Db::name('collectiongoods')->where(['user_id'=>$user_id,'goods_id'=>$goods_id])->count();
    }
}