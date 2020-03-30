<?php
namespace app\common\model;
use think\Model;
use think\Db;
class Users extends Model{
    protected $pk = 'id';
   
    public function getInfo($admin_id){
        $info = Db::name('Users')->field('pwd',true)->find($admin_id);
        return $info;
    }
    public function check($code){
        return captcha_check($code);
    }
}