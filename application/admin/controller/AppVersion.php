<?php

namespace app\admin\controller;

use think\Db;
use think\Request;
use think\View;
use app\admin\controller\Common;
class AppVersion extends Common
{
    //版本列表
    public function index()
    {
        if (Request::instance()->isPost()) {
            $page = input('page') ? input('page') : 1;
            $pageSize = input('limit') ? input('limit') : config('pageSize');
            $list = Db::name("app_version")
                ->order("id desc")
                ->paginate(array('list_rows' => $pageSize, 'page' => $page))
                ->toArray();              
            $rsult['code'] = 0;
            $rsult['msg'] = "获取成功";
            $lists = $list['data'];
            $rsult['data'] = $lists;
            $rsult['count'] = $list['total'];
            $rsult['rel'] = 1;
            return $rsult;
        } else {
            return $this->fetch();
        }
    }
    //添加版本
    public function addVersion()
    {
        if (Request::instance()->isPost()) {
            $info = input("post.");
            $data['app_type'] = $info['app_type'];
            $data['version'] = $info['version'];
            $data['content'] = $info['content'];
            $data['link'] = $info['link'];
            $data['update_time'] = date("Y-m-d H:i:s");
            $results = Db::name("app_version")->insert($data);
            if ($results) {
                $result['msg'] = '添加成功!';
                $result['code'] = 1;
                return $result;
            } else {
                $result['msg'] = '添加失败!';
                $result['code'] = 0;
                return $result;
            }
        } else {
            return $this->fetch();
        }
    }
    //编辑版本
    public function edit(){
        if(Request::instance()->isPost()){
            $info = input("post.");
            $data['app_type'] = $info['app_type'];
            $data['version'] = $info['version'];
            $data['content'] = $info['content'];
            $data['link'] = $info['link']; 
            $data['update_time'] = date("Y-m-d H:i:s");
            $data['id']=$info['id'];
            $results = Db::name("app_version")->update($data);
            if ($results) {
                $result['msg'] = '编辑成功!';
                $result['code'] = 1;
                return $result;
            } else {
                $result['msg'] = '编辑失败!';
                $result['code'] = 0;
                return $result;
            }
        }else{
            $id=input("id");
            $data=Db::name("app_version")->where(["id"=>$id])->find();
            $this->assign("data",$data);
            return $this->fetch();
        }
    }
    //删除版本
    public function del(){
        $id=input("id");
        $results=Db::name("app_version")->where(['id'=>$id])->delete();
        if ($results) {
            $result['msg'] = '删除成功!';
            $result['code'] = 1;
            return $result;
        } else {
            $result['msg'] = '删除失败!';
            $result['code'] = 0;
            return $result;
        }
    }
}
