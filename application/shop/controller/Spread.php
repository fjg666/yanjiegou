<?php

namespace app\shop\controller;

use think\Db;
use think\Request;
use app\shop\controller\Common;

class Spread extends Common {

    public function _initialize() {
        parent::_initialize();
        $this->model = model('spread');
        $this->shop = model('shop');
    }

    //推广列表
    public function lists()
    {
        if(Request::instance()->isAjax()){
            $page =input('page')?input('page'):1;
            $pageSize =input('limit')?input('limit'):config('pageSize');

            $list = $this->model
                ->field('shop_id,shop_name,click_number,page,start_date,end_date,add_date')
                ->order("add_date desc")
                ->paginate(array('list_rows'=>$pageSize,'page'=>$page))
                ->toArray();

            $str = '';
            foreach($list['data'] as $key => $val){
                $page = explode(",",$val['page']);
                if($page[0] == 'index'){
                    $str .= "首页";
                }

                if(!empty($page[1]) && $page[1] == 'car'){
                    $str .= ",购物车";
                }

                if(!empty($page[2]) && $page[2] == 'info'){
                    $str .= ",我的页面";
                }
                $list['data'][$key]['page'] = $str;
            }

            return ['code'=>0,'msg'=>"获取成功",'data'=>$list['data'],'count'=>$list['total'],'rel'=>1];
            return $result;
        }
        return $this->fetch();
    }

    //添加推广
    public function add(){
        if (Request::instance()->isAjax()) {
            $data=input('post.');

            $add['shop_id'] = $data['shop_id'];
            $add['shop_name'] = $this->shop->where("id",$data['shop_id'])->value('name');
            $add['page'] = implode(',',$data['pages']);
            $add['between_time'] = $data['between_time'];
            $add['add_date'] = date("Y-m-d H:i:s");
            $add['type'] = 2;

            if($this->model->insert($add)){
                $result['msg'] = '添加成功!';
                $result['code'] = 1;
                return $result;
            }
        }else{
            //查询商家列表
            $shopList = $this->shop->field('id,name')->where("status", 2)->select();
            $this->assign('shopList', $shopList);
            return $this->fetch();
        }
    }
}
