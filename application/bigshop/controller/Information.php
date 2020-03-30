<?php
//消息管理
namespace app\bigshop\controller;
use think\Controller;
use think\Request;
use app\bigshop\controller\Common;
class Information extends Common
{
    //消息列表
    public function index()
    {
        if(Request::instance()->isAjax()) {
            $page =input('page')?input('page'):1;
            $pageSize =input('limit')?input('limit'):config('pageSize');
            $informationmodel = new \app\admin\model\Information();
            $type_id = input('post.type_id')?input('post.type_id'):7;

            //消息类型，1 指定用户消息  2 全用户消息  3 指定商家消息   4 全商家消息    5 指定商圈消息  6 全商圈消息   7 总消息，商家 商圈 用户都能收到
            switch($type_id){
                case '1':
                    $informations = $informationmodel->alias('i')
                        ->join('__USERS__ u','u.id = i.user_id','LEFT')
                        ->field('i.*,u.id as uid,u.mobile as umobile')
                        ->where('i.type_id',$type_id)
                        ->order("i.id desc")
                        ->page($page,$pageSize)
                        ->select();
                    break;
                case '2':
                    $informations = $informationmodel->where('type_id',$type_id)->order("id desc")->page($page,$pageSize)->select();

                    break;
                case '3':
                    $informations = $informationmodel->alias('i')
                        ->join('__SHOP__ s','s.id = i.shop_id','LEFT')
                        ->field('i.*,s.id as sid,s.name as sname')
                        ->where('i.type_id',$type_id)
                        ->order("i.id desc")
                        ->page($page,$pageSize)
                        ->select();
                    break;
                case '4':
                    $informations = $informationmodel->where('type_id',$type_id)->order("id desc")->page($page,$pageSize)->select();
                    break;
                case '5':
                    $informations = $informationmodel->alias('i')
                        ->join('__BIGSHOP__ bs','bs.id = i.bigshop_id','LEFT')
                        ->field('i.*,bs.id as bsid,bs.name as bsname')
                        ->where('i.type_id',$type_id)
                        ->order("i.id desc")
                        ->page($page,$pageSize)
                        ->select();
                    break;
                case '6':
                    $informations = $informationmodel->where('type_id',$type_id)->order("id desc")->page($page,$pageSize)->select();
                    break;
                case 7:
                    $informations = $informationmodel->order("id desc")->where('type_id',$type_id)->page($page,$pageSize)->select();
                    break;
            }

            foreach($informations as $k=>$v){
                $informations[$k]['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
                //消息类型，1 指定用户消息  2 全用户消息  3 指定商家消息   4 全商家消息    5 指定商圈消息  6 全商圈消息   7 总消息，商家 商圈 用户都能收到
                $mstr = '';
                switch($v['type_id']){
                    case 1:
                        $mstr = '指定用户';
                        $informations[$k]['recevier'] = $v['umobile'];
                        break;
                    case 2:
                        $mstr = '全用户';
                        $informations[$k]['recevier'] = $mstr;
                        break;
                    case 3:
                        $mstr = '指定商家';
                        $informations[$k]['recevier'] = $v['sname'];
                        break;
                    case 4:
                        $mstr = '全商家';
                        $informations[$k]['recevier'] = $mstr;
                        break;
                    case 5:
                        $mstr = '指定商圈';
                        $informations[$k]['recevier'] = $v['bsname'];
                        break;
                    case 6:
                        $mstr = '全商圈';
                        $informations[$k]['recevier'] = $mstr;
                        break;
                    case 7:
                        $mstr = '系统';
                        $informations[$k]['recevier'] = $mstr.'所有用户商家商圈';
                        break;
                }
                $informations[$k]['type_id'] = $mstr;

            }


            $count = count($informations);
            $result = [
                'code'=>0,
                'msg'=>'ok',
                'count'=>$count,
                'data'=>$informations
            ];
            return $result;

        }else{
            return $this->fetch();
        }

    }

    public function send()
    {
        if(Request::instance()->isAjax()) {
            //构建数组
            $data = input('post.');
            $type_id = $data['type_id'];

            //type_id 消息类型，1 指定用户消息  2 全用户消息  3 指定商家消息   4 全商家消息    5 指定商圈消息  6 全商圈消息   7 总消息，商家 商圈 用户都能收到
            $info = [];
            switch($type_id){
                case 1:
                    $users_id = $data['ids'];
                    $uids = explode(',',$users_id);
                    foreach($uids as $k=>$v){
                        $info[$k]['user_id'] = $v;
                        $info[$k]['title'] = $data['title'];
                        $info[$k]['content'] = $data['content'];
                        $info[$k]['add_time'] = time();
                        $info[$k]['type_id'] = $type_id;
                    }
                    break;
                case 2:
                    unset($data['ids']);
                    $mydata = [
                        'title'=>$data['title'],
                        'content'=>$data['content'],
                        'add_time'=>time(),
                        'type_id'=>$type_id,
                    ];
                    $info[]= $mydata;
                    break;
                case 3:
                    //3 指定商家消息
                    $shops_id = $data['ids'];
                    $ids = explode(',',$shops_id);
                    foreach($ids as $k=>$v){
                        $info[$k]['shop_id'] = $v;
                        $info[$k]['title'] = $data['title'];
                        $info[$k]['content'] = $data['content'];
                        $info[$k]['add_time'] = time();
                        $info[$k]['type_id'] = $type_id;
                    }
                    break;
                case 4:
                    unset($data['ids']);
                    $mydata = [
                        'title'=>$data['title'],
                        'content'=>$data['content'],
                        'add_time'=>time(),
                        'type_id'=>$type_id,
                    ];
                    $info[]= $mydata;
                    break;
                case 5:
                    //5 指定商圈消息
                    $bigshops_id = $data['ids'];
                    $ids = explode(',',$bigshops_id);
                    foreach($ids as $k=>$v){
                        $info[$k]['bigshop_id'] = $v;
                        $info[$k]['title'] = $data['title'];
                        $info[$k]['content'] = $data['content'];
                        $info[$k]['add_time'] = time();
                        $info[$k]['type_id'] = $type_id;
                    }
                    break;
                case 6:
                    unset($data['ids']);
                    $mydata = [
                        'title'=>$data['title'],
                        'content'=>$data['content'],
                        'add_time'=>time(),
                        'type_id'=>$type_id,
                    ];
                    $info[]= $mydata;
                    break;
                case 7:
                    $info = [
                        'title'=>$data['title'],
                        'content'=>$data['content'],
                        'add_time'=>time(),
                        'type_id'=>$type_id
                    ];
                    break;
            }

            $res = db('information')->insertAll($info);
            if($res){
                $result['code'] = 1;
                $result['msg'] = '发送消息成功!';
                $result['url'] = url('index');
                return $result;
            }else{
                $result['code'] = 0;
                $result['msg'] = '发送消息失败!';
                $result['url'] = url('index');
                return $result;
            }

        }else{
            $type_id = input('get.type_id');
            $this->assign('type_id',$type_id);
            //type_id 消息类型，1 指定用户消息  2 全用户消息  3 指定商家消息   4 全商家消息    5 指定商圈消息  6 全商圈消息   7 总消息，商家 商圈 用户都能收到
            switch($type_id){
                case '1':
                    $msg = '指定用户消息';
                    break;
                case '2':
                    $msg = '全用户消息';
                    break;
                case '3':
                    $msg = '指定商家消息';
                    break;
                case '4':
                    $msg = '全商家消息';
                    break;
                case '5':
                    $msg = '指定商圈消息';
                    break;
                case '6':
                    $msg = '全商圈消息';
                    break;
                case '7':
                    $msg = '系统消息';
                    break;
            }
            $this->assign('msg',$msg);
            $ids = input('get.ids');
            $this->assign('ids',$ids);
            return $this->fetch();
        }

    }

    public function delAll(){
        $id=input('post.ids/a');
        $id=implode(",",$id);
        db('information')->where("id in ($id)")->delete();
        return $this->resultmsg('删除成功',1);
    }


}
