<?php

namespace app\index\controller;

use think\Request;
use think\Db;
use think\Controller;
use think\Cookie;
use think\Session;
use app\index\controller\Wechat;
class Index extends Controller
{
    public function index()
    {
       
        if(!Session::get('openid')){
            $openid=$this->getShopUserOpenid();
            Session::set('openid',$openid);
        }
        $openid=Session::get('openid'); //获取openid
        $shop_info=Db::name("shop")->where(['openid'=>$openid])->find();
        if(empty($shop_info)){
            $this->assign('is_apply',0);      
        }else{       
            if($shop_info['status']==1){
                return view('shenhe',['status'=>1]);
            }elseif($shop_info['status']==3){
                return view('shenhe',['status'=>3,'verify_reason'=>$shop_info['verify_reason']]);
            }    
            $this->assign('is_apply',1);//已申请
        }
        $this->assign('openid',$openid);       
        // $this->assign('openid',111);
        // $this->assign('is_apply',0); 
        $shop_category=Db::name("shop_category")->field('id,shop_category value')->select();
        $this->assign('shop_category',json_encode($shop_category));    
        return view();
    }
     public function shenhe(){
         return view();
     } 
     //重新审核
     public function edit(){
        if(!Session::get('openid')){
            $openid=$this->getShopUserOpenid();
            Session::set('openid',$openid);
        }
        $openid=Session::get('openid'); //获取openid
        $shop_info=Db::name("shop")->alias("a")
        ->join("shop_category b","a.type=b.id","LEFT")
        ->field("a.*,b.shop_category")
        ->where(['a.openid'=>$openid])->find();
        $shop_info['identity_photo']=explode(",",$shop_info['identity_photo']);
        $shop_category=Db::name("shop_category")->field('id,shop_category value')->select();
        $this->assign('shop_category',json_encode($shop_category));  
        $this->assign('shop_info',$shop_info);
        $this->assign('openid',$openid);
        return view();
     }


    //获取商户openid
    public function getShopUserOpenid()
    {
        
        $appid=config("wchat.appid");
        $appsecret=config("wchat.appsecret");       
        if(input('code')){
            $code=input('code');
            $oauth2Url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$appid."&secret=".$appsecret."&code=".$code."&grant_type=authorization_code";
            $data=$this->httpUtil($oauth2Url);   
            $getInfo=json_decode($data,true); 
            if(isset($getInfo['errcode'])){
                $this->error($getInfo['errcode'].$getInfo['errmsg'],"index/index");
            }   
            $openid = $getInfo['openid'];            
            return $openid;          
        }else{
            $redirect_uri = urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $appid . "&redirect_uri=" . $redirect_uri . "&response_type=code&scope=snsapi_userinfo&state=STATE&connect_redirect=1#wechat_redirect";
            header("Location:" . $url);
        }
    }
    //提交申请
    public function addapply(){ 
        $shop_info=Db::name("shop")->where(['openid'=>input('openid')])->find();
        if(!empty($shop_info)){
            return $this->json_error('请勿重复提交申请！');
        }
        if(empty(input('post.shoplogo'))){
            return $this->json_error('商家logo不能为空！');
        }          
        if(empty(input('post.name'))){
            return $this->json_error('商家名称不能为空！');
        } 
        if(empty(input('post.type'))){
            return $this->json_error('所属行业不能为空！');
        } 
        if(empty(input('post.city'))){
            return $this->json_error('地区不能为空！');
        }  
        if(empty(input('post.longitude')) && empty(input('post.latitude'))){
            return $this->json_error('定位错误，请重新定位！');
        }    
        if(empty(input('post.phone'))){
            return $this->json_error('手机号不能为空！');
        }   
        if(!preg_match("/^1[34578]{1}\d{9}$/",input('post.phone'))){
            return $this->json_error('手机格式不正确！');
        }
        $phone=input('post.phone');
        $info=Db::name('shop')->where(['phone'=>$phone])->find();
        if(!empty($info)){
            return $this->json_error('手机号已存在！');
        }      
        if(empty(input('post.identity_photo/a')) && count(input('post.identity_photo/a'))<2){
            return $this->json_error('身份证正反面不能为空！');
        }       
        if(empty(input('post.yyzz'))){
            return $this->json_error('营业执照不能为空！');
        }  
       
        $identity_z=input('post.identity_photo/a')[0];
        $identity_f=input('post.identity_photo/a')[1];
        $identity_zurl='http://' . $_SERVER['HTTP_HOST'].$identity_z;
        $identity_furl='http://' . $_SERVER['HTTP_HOST'].$identity_f;
        $data=Wechat::idCard($identity_zurl);
        $identityInfo=json_decode($data,true); 
        if($identityInfo['errmsg']=="ok"){
            $shenfenid=substr($identityInfo['id'],-6);
        }else{
            return $this->json_error('身份证证件有误！'); 
        }
        $identity_photo=implode(',',input('post.identity_photo/a'));
        $yyzz=input('post.yyzz');
       
        $data=array(
                'name'=>input('post.name'),//商家名称
                'phone'=>input('post.phone'),//联系电话
                'linkmen'=>input('post.linkmen'),//联系人
                'shoplogo'=>input('post.shoplogo'),//商家logo
                'province'=>input('post.province'),//商家省份
                'city'=>input('post.city'),//商家城市
                'area'=>input('post.area'),//商家区域
                'street'=>input('post.street'),//街道
                'address'=>input('post.address'),//商家详细地址
                'longitude'=>input('post.longitude'),//经度
                'latitude'=>input('post.latitude'),//纬度
                'intro'=>input('post.intro'),//商家简介
                'identity_photo'=>$identity_photo,//商家身份证
                'yyzz'=>$yyzz,//商家营业执照
                'shortname'=>GetShortName(input('post.name')),//商家短名字
                'status'=>1,//申请状态      
                'is_lock'=>1,//申请状态      
                'openid'=>input('openid'),//商家openid    
                'bphone'=>input('post.bphone'),//备用电话              
                'type'=>input('post.type'),//备用电话              
        );   
        $result=Db::name('shop')->insertGetId($data);  
             
        if($result){
            $this->createAdmin($result,$data['phone'],$shenfenid);
         return  $this->json_success('提交成功！');
        }else{
            return $this->json_error('提交失败！');
        }
    }
     //提交申请
     public function editapply(){ 
       
        if(empty(input('post.shoplogo'))){
            return $this->json_error('商家logo不能为空！');
        }          
        if(empty(input('post.name'))){
            return $this->json_error('商家名称不能为空！');
        } 
        if(empty(input('post.type'))){
            return $this->json_error('所属行业不能为空！');
        } 
        if(empty(input('post.city'))){
            return $this->json_error('地区不能为空！');
        }  
        if(empty(input('post.longitude')) && empty(input('post.latitude'))){
            return $this->json_error('定位错误，请重新定位！');
        }    
        if(empty(input('post.phone'))){
            return $this->json_error('手机号不能为空！');
        }
        if(!preg_match("/^1[34578]{1}\d{9}$/",input('post.phone'))){
            return $this->json_error('手机格式不正确！');
        }        
        if(empty(input('post.identity_photo/a')) && count(input('post.identity_photo/a'))<2){
            return $this->json_error('身份证正反面不能为空！');
        }
       
        if(empty(input('post.yyzz'))){
            return $this->json_error('营业执照不能为空！');
        }  
        $identity_z=input('post.identity_photo/a')[0];
        $identity_f=input('post.identity_photo/a')[1];
        $identity_zurl='http://' . $_SERVER['HTTP_HOST'].$identity_z;
        $identity_furl='http://' . $_SERVER['HTTP_HOST'].$identity_f;
        $data=Wechat::idCard($identity_zurl);
        $identityInfo=json_decode($data,true); 
        if($identityInfo['errmsg']=="ok"){
            $shenfenid=substr($identityInfo['id'],-6);
        }else{
            return $this->json_error('身份证证件有误！'); 
        }
        $identity_photo=implode(',',input('post.identity_photo/a'));
        $yyzz=input('post.yyzz');
        $shop_id=input('id');
        $data=array(
                'name'=>input('post.name'),//商家名称
                'phone'=>input('post.phone'),//联系电话
                'linkmen'=>input('post.linkmen'),//联系人
                'shoplogo'=>input('post.shoplogo'),//商家logo
                'province'=>input('post.province'),//商家省份
                'city'=>input('post.city'),//商家城市
                'area'=>input('post.area'),//商家区域
                'street'=>input('post.street'),//街道
                'address'=>input('post.address'),//商家详细地址
                'longitude'=>input('post.longitude'),//经度
                'latitude'=>input('post.latitude'),//纬度
                'intro'=>input('post.intro'),//商家简介
                'identity_photo'=>$identity_photo,//商家身份证
                'yyzz'=>$yyzz,//商家营业执照
                'shortname'=>GetShortName(input('post.name')),//商家短名字
                'status'=>1,//申请状态      
                'is_lock'=>1,//申请状态      
                'openid'=>input('openid'),//商家openid    
                'bphone'=>input('post.bphone'),//备用电话              
                'type'=>input('post.type'),//备用电话              
        );   
        $result=Db::name('shop')->where(['id'=>$shop_id])->update($data);               
        if($result){
            Db::name('ShopAuthGroup')->where(['shopid'=>$shop_id])->delete();
            Db::name('ShopAdmin')->where(['sid'=>$shop_id])->delete();
            $this->createAdmin($shop_id,$data['phone'],$shenfenid);
         return  $this->json_success('提交成功！');
        }else{
            return $this->json_error('提交失败！');
        }
    }
    //创建超级用户
    public function createAdmin($shop_id,$user_name,$pwd="123456"){
        $list=Db::name('ShopAuthRule')->where('type=2')->field('id')->column('id','id');
	        $group_data=[
	            'shopid'=>$shop_id,
	            'is_super'=>1,
	            'type'=>2,
	            'addtime'=>time(),
	            'rules'=>'0,'.implode(',',$list),
	            'title'=>'超级管理员',    
	     	];
	     	$group_id=Db::name('ShopAuthGroup')->insertGetId($group_data);
	     	$admin_data=[
	            'type'=>2,
	            'sid'=>$shop_id,
	            'username'=>$user_name,
	            'pwd'=>authcode($pwd),
	            'group_id'=>$group_id,
	            'is_open'=>1,
	     	];
	     	$adminres=Db::name('ShopAdmin')->insert($admin_data);
    }
    
    public function httpUtil($url, $data = '', $method = 'GET')
    {
        try {

            $curl = curl_init(); // 启动一个CURL会话
            curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
            curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
            curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
            if ($method == 'POST') {
                curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
                if ($data != '') {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
                }
            }
            curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
            curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
            $tmpInfo = curl_exec($curl); // 执行操作
            curl_close($curl); // 关闭CURL会话
            return $tmpInfo; // 返回数据
        } catch (Exception $e) { }
    }
    
    //上传头像
    public function addImageUpload(){
        $file=Request::instance()->file('file');
    }
    //获取位置
    public function getaddress(){
        if(file_exists('area.json')){
            $info=file_get_contents('area.json');
        }else{
            set_time_limit(0);
            $data=Db::name("area")->field('id,area_name value,parent_id pid')->select();           
            $info=$this->getTrees($data,0);
            $info=json_encode($info);
            file_put_contents('area.json',$info);
        }        
        return $info;
    }
    
    public  function getTrees($data,$pid){
          $info=[];
         foreach($data as $k=>$v){
            if($v['pid']==$pid){                
                $v['childs']=$this->getTrees($data,$v['id']);
                $info[]=$v;
                unset($data[$k]);
            }
         }
         return $info;
    }
   
     /**
     * json失败返回
     * @param string $msg
     * @param array $data
     * @param int $code
     */
    public function json_error($msg = '失败', $code = 0)
    {
        exit(json_encode(['code' => $code, 'msg' => $msg], JSON_UNESCAPED_UNICODE));
    }

    /**
     * json成功返回
     * @param array $data
     * @param string $msg
     * @param int $code
     */
    public function json_success($data = [], $msg = '成功', $code = 200)
    {
        if (is_string($data)) {
            $msg  = $data;
            $data = [];
        }
        exit(json_encode(['code' => $code, 'msg' => $msg, 'data' => $data], JSON_UNESCAPED_UNICODE));
    }
    //测试模板消息
    public function sendMsd(){
        $openid=$this->getShopUserOpenid();
        $templateId="XvLf3H2yxHLwljqO2VxKkXWECZ74aliaTLlQPamFgu4";
        $url="";
        $data=array(
            array(
                'value'=>"沿街购测试",
                "color"=>"#173177"
            ),
            array(
                'value'=>"18224513696",
                "color"=>"#173177"
            ),
            array(
                'value'=>"3696",
                "color"=>"#173177"
            ),
        );
        $remark=array(
            'value'=>"请妥善保管。",
            "color"=>"#173177"
        );
        $data =Wechat::templateMessageSend($openid, $templateId, $url, $data, $remark);
        $result=Wechat::sendMes($data);
    }
    
}
