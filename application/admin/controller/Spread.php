<?php

namespace app\admin\controller;

use think\Db;
use think\Request;
use app\admin\controller\Common;

class Spread extends Common {

    public function _initialize() {
        parent::_initialize();
        $this->model = model('spread');
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
            var_dump($list);die;
            return ['code'=>0,'msg'=>"获取成功",'data'=>$list['data'],'count'=>$list['total'],'rel'=>1];
            return $result;
        }
        return $this->fetch();
    }
}
