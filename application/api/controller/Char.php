<?php
namespace app\api\controller;
use think\Controller;
use think\Db;
//加载GatewayClient。关于GatewayClient参见本页面底部介绍
require_once __DIR__ . '/../../../vendor/GatewayClient/Gateway.php';
// GatewayClient 3.0.0版本开始要使用命名空间
use GatewayClient\Gateway;
// 设置GatewayWorker服务的Register服务ip和端口，请根据实际情况改成实际值(ip不能是0.0.0.0)
Gateway::$registerAddress = '127.0.0.1:1238';
class Char extends Base
{
    public function msglog()
    {
		// $uid = input('uid');
  //  	$infouid = input('infouid');
    	
  //  	if (empty($infouid) || empty($uid)) {
  //  		return json_encode(['code'=>100,'msg'=>'缺少条件']);
  //  	}
  //  	$data = Db::name('chatLog')
  //  			->where("(`uid` = '$uid' AND `infouid` = '$infouid') OR (`uid` = '$infouid' AND `infouid` = '$uid')")
		// 		->whereTime('add_time','-3 month')
  //  			->select();
  //  	$arr = ['code'=>0,'data'=>$data];
  //  	return json($arr);
		$uid = input('uid');
    	$infouid = input('infouid');
    	
    	if (empty($infouid) || empty($uid)) {
    		return json_encode(['code'=>100,'msg'=>'缺少条件']);
    	}
    	$data = Db::name('chatLog')
    			->where("(`uid` = '$uid' AND `infouid` = '$infouid') OR (`uid` = '$infouid' AND `infouid` = '$uid')")
				->whereTime('add_time','-3 month')
    			->select();
        // input('num')?(!is_int(input('num'))?'':exit()):'';
        // input('page')?(!is_int(input('page'))?'':exit()):'';

        if (empty($data)) {
            $arr = ['code'=>0,'data'=>''];
            return json($arr);
        }
        $num = input('num')?input('num'):5;
        $page = input('page')?input('page'):1;
        $start = -($page*$num);
        $count = count($data);
        if (ceil($count/$num) < $page) {
            $arr = ['code'=>0,'data'=>''];
            return json($arr);
        }
        if ($count < (-$start)) {
            $yv = $count%$num;
            $data = array_slice($data, (-$count), $yv);
        }else{
            $data = array_slice($data, $start, $num);
        }

        foreach ($data as $key => $value) {
            $data[$key]['add_time'] = date("Y-m-d H:i",$value['add_time']);
        }

    	$arr = ['code'=>0,'data'=>$data];
    	return json($arr);
    }
    /**
	* 绑定uid
    */
    public function bind()
    {
    	$client_id = input('client_id');
    	$uid = input('uid');
    	if (empty($client_id) || empty($uid)) {
    		return json(['code'=>100,'msg'=>'缺少条件','data'=>'','url'=>'']);
    		die();
    	};
    	Gateway::bindUid($client_id, $uid);
    	$message = json_encode(['code'=>0,'msg'=>'绑定成功']);
    	Gateway::sendToUid($uid, $message);
    }
    /*已读*/
    public function yesread(){
        $id = input('id');
        if (!empty($id)) {
            Db::name('chat')->where('id',$id)->update(['no_read' => 0]);
        }
        
    }
    public function sendmsg()
    {
    	$uid = input('uid');
    	$infouid = input('infouid');
		$msg = input('msg')?htmlspecialchars(input('msg')):'';
		$type = input('type')?input('type'):0;
    	if (empty($infouid) || empty($uid)) {
    		Gateway::sendToUid($uid,json_encode(['code'=>100,'msg'=>'缺少条件','type'=>'message','data'=>['uid'=>$uid,'infouid'=>$infouid]]));
    		die();
    	};
        if (strpos($uid,'user') !== false) {
            $user_id = str_replace('user','',$uid);
            $user = Db::name('users')->field('id as uid,username,avatar')->where('id',$user_id)->find();
            if (!$user) {
                return json(['code'=>100,'msg'=>'账号错误']);
            }
            $arr = [
                'uid'   =>  'user'.$user['uid'],
                'shopid'=>  $infouid,
                'name'  =>  $user['username'],
                'headimg'=> $user['avatar'],
            ];
        }
        if (strpos($uid,'shop') !== false) {
            $user_id = str_replace('shop','',$uid);
            $shop = Db::name('shop')->field('id as shopid,name,shoplogo')->where('id',$user_id)->find();
            if (!$shop) {
                return json(['code'=>100,'msg'=>'账号错误']);
            }
            $arr = [
                'uid'   =>  $infouid,
                'shopid'=>  'shop'.$shop['shopid'],
                'name'  =>  $shop['name'],
                'headimg'=> $shop['shoplogo'],
            ];
        }
// 聊天记录
        $info = $this->chat_log($uid,$infouid,$msg,$type);
        
        if ($info['chatlog_id']) {
            $arr['chat_id'] = $info['chat_id'];
            $arr['chatlog_id'] = $info['chatlog_id'];
            $arr['chat_noread'] = $info['chat_noread'];
            $data = [
                'code'  =>  0,
                'data'  =>  $arr,
                'msg'   =>  $msg,
                'type'  =>  'message',
                'msg_type'=>$type,
            ];
            Gateway::sendToUid($infouid, json_encode($data));
			return json(['code'=>0,'msg'=>'已发送']);
		}
    }
    private function chat_log($uid,$infouid,$text,$type)
    {
        $data = [
            'uid'   =>  $uid, 
            'infouid'   =>  $infouid,
            'content'       =>  $text,
            'add_time'  =>  time(),
            'type'	=>	$type
        ];
        $info['chatlog_id'] = Db::name('chatLog')->insertGetId($data);
        
        $find = Db::name('chat')->where('uid',$uid)->where('infouid',$infouid)->find();
        if (!$find) {
            $list = [
                'uid'  =>  $uid,
                'infouid'=> $infouid,
                'no_read'=> 1
            ];
            $info['chat_id']=Db::name('chat')->insertGetId($list);
            $info['chat_noread'] = 1;
            
            Db::name('chat')->insertGetId(['uid'=>$infouid,'infouid'=>$uid]);
        }else{
            Db::name('chat')->where('id',$find['id'])->setInc('no_read');
            $info['chat_id'] = $find['id'];
            $info['chat_noread'] = $find['no_read']+1;
        }
		return $info;
    }
    
    
    
    public function shop_log()
    {
        $id = input('shopid');
        $shopid = 'shop'.$id;
        $sel = Db::name('chat')
                ->where('infouid',$shopid)
                ->select();
        foreach ($sel as $key => $value) {
            $uid = str_replace('user','',$value['uid']);
            $users = Db::name('users')->field('username,avatar,mobile')->where('id',$uid)->find();
            if(!$users){
                unset($sel[$key]);
                continue;
            }
            if ($users['username'] == '') {
                $users['username'] = $users['mobile'];
            }
            $sel[$key]['username'] = $users['username'];
            $sel[$key]['avatar'] = $users['avatar'];
            $sel[$key]['log'] = Db::name('chatLog')
                ->where("(`uid` = '".$value['uid']."' AND `infouid` = '".$value['infouid']."') OR (`uid` = '".$value['infouid']."' AND `infouid` = '".$value['uid']."')")
                ->whereTime('add_time','-3 month')
                ->select();
        }
        return $sel;
    }

    //单独联系用户
    public function contact_log()
    {
        $id = input('shopid');
        $userid = input('userid');
        $shopid = 'shop'.$id;
        $userid = 'user'.$userid;
        $sel = Db::name('chat')
            ->where('infouid',$shopid)
            ->where('uid',$userid)
            ->select();
        foreach ($sel as $key => $value) {
            $uid = str_replace('user','',$value['uid']);
            $users = Db::name('users')->field('username,avatar,mobile')->where('id',$uid)->find();
            echo '123123';die;
            if(!$users){
                unset($sel[$key]);
                continue;
            }
            if ($users['username'] == '') {
                $users['username'] = $users['mobile'];
            }
            $sel[$key]['username'] = $users['username'];
            $sel[$key]['avatar'] = $users['avatar'];
            $sel[$key]['log'] = Db::name('chatLog')
                ->where("(`uid` = '".$value['uid']."' AND `infouid` = '".$value['infouid']."') OR (`uid` = '".$value['infouid']."' AND `infouid` = '".$value['uid']."')")
                ->whereTime('add_time','-3 month')
                ->select();
        }
        return $sel;
    }
    
    
    public function user_log(){
        $id = input('userid');
        if (empty($id)) {
            $arr = ['code'=>0, 'msg'=>'参数错误', 'data'=>''];
            return json_encode($arr);
        }
        $userid = 'user'.$id;
        $sel = Db::name('chat')
                ->where('uid',$userid)
                ->select();
        if (!empty($sel)) {
            foreach ($sel as $key => $value) {
                $uids = $value['uid'];
                $infouids = $value['infouid'];
                $shop_id = str_replace('shop','',$value['infouid']);
                $shop = Db::name('shop')->field('id as shopid,name,shoplogo')->where('id',$shop_id)->find();
	            if(!$shop){
	            	unset($sel[$key]);
	                continue;
	            }
                
                //最后一条
                $msg = Db::name('chatLog')
                ->where("(`uid` = '$uids' AND `infouid` = '$infouids') OR (`uid` = '$infouids' AND `infouid` = '$uids')")
                ->where('type',0)
                ->order('id desc')
                ->find();


                if ($shop) {
                    $sel[$key]['shoplogo'] = $shop?"http://svn.yanjiegou.com".$shop['shoplogo']:'';
                    $sel[$key]['name'] = $shop?$shop['name']:'';
                    $sel[$key]['msg'] = $msg?$msg['content']:'';
                    $sel[$key]['add_time'] = $msg?date("Y-m-d H:i", $msg['add_time']):'';
                }
            }
        }
        $arr = ['code'=>200, 'msg'=>'成功', 'data'=>$sel];
        return json_encode($arr);
    }
    
    
    public function message_log(){
        $id = input('userid');
        if (empty($id)) {
            $arr = ['code'=>0, 'msg'=>'参数错误', 'data'=>''];
            return json_encode($arr);
        }
        $userid = 'user'.$id;
        $sel = Db::name('chat')
                ->where('infouid',$userid)
                ->select();
        if (!empty($sel)) {
            foreach ($sel as $key => $value) {
                $uids = $value['uid'];
                $infouids = $value['infouid'];
                $shop_id = str_replace('shop','',$value['uid']);
                $shop = Db::name('shop')->field('id as shopid,name,shoplogo')->where('id',$shop_id)->find();
                if(!$shop){
                    unset($sel[$key]);
                    continue;
                }
                
                //最后一条
                $msg = Db::name('chatLog')
                ->where("(`uid` = '$uids' AND `infouid` = '$infouids') OR (`uid` = '$infouids' AND `infouid` = '$uids')")
                ->order('id desc')
                ->find();

                if ($shop) {
                    $sel[$key]['shoplogo'] = $shop?"http://svn.yanjiegou.com".$shop['shoplogo']:'';
                    $sel[$key]['name'] = $shop?$shop['name']:'';
                    $sel[$key]['msg'] = $msg?$msg['content']:'';
                    $sel[$key]['add_time'] = $msg?date("Y-m-d H:i", $msg['add_time']):'';
                    $sel[$key]['type'] = $msg?$msg['type']:'';
                }

                

            }
        }
        $arr = ['code'=>200, 'msg'=>'成功', 'data'=>$sel];
        return json_encode($arr);
    }
    
}