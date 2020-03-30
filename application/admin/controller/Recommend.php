<?php

namespace app\admin\controller;

use think\Db;
use think\Request;
use app\admin\controller\Common;
use app\admin\model\GoodsCategory;

class Recommend extends Common {

    public function _initialize() {
        parent::_initialize();
        $this->assign('moduleid', 116);
    }

    /**
     * 推荐商圈
     * */
    public function bshop() {
        if (Request::instance()->isAjax()) {
            $status = input('status');
            $data = explode(',', input('data'));
            $recommend = $status ? 0 : 1;
            $result = Db::name('bigshop')->whereIn('id', $data)->setField('recommend', $recommend);
            if ($result) {
                return ['code' => 1, 'msg' => "设置成功"];
            } else {
                return ['code' => 0, 'msg' => "设置失败"];
            }
        } else {
            $list = Db::name('bigshop')->field('id,name title,recommend')->select();
            $arr = [];
            $data = [];
            foreach ($list as $key => $value) {
                if ($value['recommend']) {
                    $arr[] = $value['id'];
                }
                $data[$key]['value'] = $value['id'];
                $data[$key]['title'] = $value['title'];
            }
            $this->assign('list', json_encode($data));
            $this->assign('arr', json_encode($arr));
            return $this->fetch();
        }
    }

    public function getcate() {
        $pid = input('pid');
        $cate = Db::name('GoodsCategory')->where('parentid=' . $pid)->field('id,catname name')->select();
        return json(['data' => $cate]);
    }

    /**
     * 推荐商家
     * */
    public function shop() {
        if (Request::instance()->isAjax()) {
            $status = input('status');
            $data = explode(',', input('data'));
            $recommend = $status ? 0 : 1;
            $result = Db::name('shop')->whereIn('id', $data)->setField('recommend', $recommend);
            if ($result) {
                return ['code' => 1, 'msg' => "设置成功"];
            } else {
                return ['code' => 0, 'msg' => "设置失败"];
            }
        } else {
            $shop = Db::name("shop")->field("id,name,recommend")->where(['status' => 2])->select();
            $arr = [];
            $data = [];
            foreach ($shop as $key => $value) {
                if ($value['recommend']) {
                    $arr[] = $value['id'];
                }
                $data[$key]['value'] = $value['id'];
                $data[$key]['title'] = $value['name'];
            }
            $this->assign('list', json_encode($data));
            $this->assign('arr', json_encode($arr));
            return $this->fetch();
        }
    }

    /*
     * 根据商品分类获取商家
     */

    public function getShopByCategory() {
        $categoryId = input('data');
        if (empty($categoryId)) {
            return ['code' => 0, 'msg' => "数据不能为空"];
        }
        $ids = explode(",", $categoryId);
        $id = array_pop($ids);
        $goodsCategory = Db::name("goods_category")->where(['id' => $id])->find();
        $catId = $goodsCategory['arrchildid'];
        $catIds = explode(",", $catId);
        $shopids = Db::name("goods")->whereIn('shopid', $catIds)->column('shopid');
        if (empty($shopids)) {
            return ['code' => 0, 'msg' => '暂无商家'];
        }
        $shop = Db::name("shop")->field("id,name,recommend")->where(['status' => 2])->whereIn('id', $shopids)->select();
        $arr = [];
        $data =[];
        foreach ($shop as $key => $value) {
            if ($value['recommend']) {
                $arr[] = $value['id'];
            }
            $data[$key]['value'] = $value['id'];
            $data[$key]['title'] = $value['name'];
        }
        return ['code' => 1, 'arr' => $arr, 'list' => $data];
    }

    /**
     * 推荐商品
     * */
    public function goods() {
        if (Request::instance()->isAjax()) {
            //推荐类型
            $type = input('type') ? input('type') : 0;
            switch ($type) {
                case 1 :
                    $map['a.ishot'] = array('eq', 1); //热销
                    break;
                case 2 :
                    $map['a.isnew'] = array('eq', 1); //新品
                    break;
                case 3 :
                    $map['a.isdiscount'] = array('eq', 1); //折
                    break;
            }
            if (input('category')) {
                $categoryId = input('category'); //分类id
                $ids = explode(",", $categoryId);
                $id = array_pop($ids);
                $goodsCategory = Db::name("goods_category")->where(['id' => $id])->find();
                $catId = $goodsCategory['arrchildid'];
                $catIds = explode(",", $catId);
                $map['a.catid'] = array('in', $catIds);
            }
            if (input('keywords')) {//关键字
                $keywords =trim(input('keywords'));
                $map['a.title|a.goodsn|a.goodsn|b.name'] = array('like','%'.$keywords.'%');
            }  
            if(!isset($map)){
                return ['code'=>0,'msg'=>'参数错误！数据不能为空！'];
            }
            $goods = Db::name("goods")->alias('a')
                    ->join('shop b','a.shopid = b.id','LEFT')
                    ->where($map)->field('a.id,a.title,a.isrecommand')->select();
            $arr = [];
            $data = [];
            foreach ($goods as $key => $value) {
                if ($value['isrecommand']) {
                    $arr[] = $value['id'];
                }
                $data[$key]['value'] = $value['id'];
                $data[$key]['title'] = $value['title'];
            }
            return ['code' => 1, 'arr' => $arr, 'list' => $data];
        } else {           
            return $this->fetch();
        }
    }
    //推荐商品
    public function changeGoods(){
           $status = input('status');
            $data = explode(',', input('data'));
            $recommend = $status ? 0 : 1;
            $result = Db::name('goods')->whereIn('id', $data)->setField('isrecommand', $recommend);
            if ($result) {
                return ['code' => 1, 'msg' => "设置成功"];
            } else {
                return ['code' => 0, 'msg' => "设置失败"];
            }
    }

}
