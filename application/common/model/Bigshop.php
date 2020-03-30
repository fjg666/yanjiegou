<?php
namespace app\common\model;
use think\Model;
use think\Db;
class Bigshop extends Model
{

     /**
      * 创建超级管理员
      **/
     public static function creatadmin($data){
     	//开启事务
        Db::startTrans();
	        unset($data['upfile']);
	        $admin_name=$data['admin_name'];
	        $admin_pwd=$data['admin_pwd'];
	        unset($data['admin_name']);
	        unset($data['admin_pwd']);
	        if (isset($data['headimg'])) {
	        	 $count = count($data['headimg']);//获取传过来有几张图片
		        if($count){
		            $data['headimg'] = implode(',',$data['headimg']);
		        }
	        }
	        $data['addtime']=time();
	        $res = self::insertGetId($data);
	        $list=Db::name('BigshopAuthRule')->where('type=1')->field('id')->column('id','id');
	        $group_data=[
	            'shopid'=>$res,
	            'is_super'=>1,
	            'type'=>1,
	            'addtime'=>time(),
	            'rules'=>'0,'.implode(',',$list),
	            'title'=>'超级管理员',    
	     	];
	     	$group_id=Db::name('BigshopAuthGroup')->insertGetId($group_data);
	     	$admin_data=[
	            'type'=>1,
	            'sid'=>$res,
	            'username'=>$admin_name,
	            'pwd'=>authcode($admin_pwd),
	            'group_id'=>$group_id,
	            'is_open'=>1,
	     	];
	     	$adminres=Db::name('BigshopAdmin')->insert($admin_data);
     	if ($res && $group_id && $adminres){
     		 //如果全部成功,提交事务
            Db::commit();
            return true;
     	}else{
     		//如果失败,回滚事务
            Db::rollback();
            return false;
     	}
     }
}