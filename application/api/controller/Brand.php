<?php

namespace app\api\controller;

use think\Db;
use think\Request;

class Brand extends Base
{
    //品牌全部
    public function lists()
    {
        if (Request::instance()->isPost()) {
            $list = Db::name('goodsBrand')->field('etitle')->order('etitle asc')->group('etitle')->select();
            foreach ($list as $k => $v) {
                $son = [];
                $son = Db::name('goodsBrand')->field('id,title,pic')->where(['etitle' => $v['etitle']])->select();
                foreach ($son as $ks => $vs) {
                    $son[$ks]['pic'] = $this->domain() . $vs['pic'];
                }
                $list[$k]['son'] = $son;
            }

            $this->json_success($list, '请求数据成功');
        } else {
            $this->json_error('请求方式有问题');
            die;
        }
    }

    //品牌精品
    public function jxlists()
    {
        if (Request::instance()->isPost()) {

            $list = Db::name('goodsBrand')->where(['jx' => 1])->select();
            foreach ($list as $k => $v) {
                $list[$k]['pic'] = $this->domain() . $v['pic'];
                // $goods = Db::name('goods')->field('id,headimg,title,price,original_price,cost_price,zk_price')->where(['brandid' => $v['id']])->limit(3)->select();
                $goods = Db::name('goods')->alias('g')
                        ->join('shy_shop s','s.id = g.shopid','LEFT') 
                        ->field('g.id,g.headimg,g.title,g.price,g.original_price,g.cost_price,g.zk_price')
                        ->where(['g.brandid' => $v['id']])
                        ->where('s.is_lock', 0)
                        ->limit(3)
                        ->select();
                if(empty($goods)){
                    unset($list[$k]);
                }else{
                    foreach ($goods as $gk => $gv) {
                        $headimg = explode(',', $gv['headimg']);
                        $goods[$gk]['headimg'] = $this->domain() . $headimg[0];
                    }
                    $list[$k]['goods'] = $goods;
                }
                
            }
            $this->json_success($list, '请求数据成功');
        } else {
            $this->json_error('请求方式有问题');
            die;
        }
    }
    //品牌页面限制展示品牌
    public function bandsAdv()
    {
        if (Request::instance()->isPost()) {

            $list = Db::name('goodsBrand')->order(['sort'=>'desc'])->limit(7)->select();
            foreach ($list as $k => $v) {
                $list[$k]['pic'] = $this->domain() . $v['pic'];                
            }
            $this->json_success($list, '请求数据成功');
        } else {
            $this->json_error('请求方式有问题');
            die;
        }
    }
    //断码单品
    public function dmgoods()
    {
        //当前的页码
        $p = empty(input('post.p')) ? 1 : input('post.p');
        //每页显示的数量
        $rows = empty(input('post.rows')) ? 10 : input('post.rows');
        $goodsmodel = new \app\api\model\Goods();
        $goods = $goodsmodel->alias('g')
            ->where(['g.isqc' => 1])
            ->field('g.id,g.headimg,g.title,g.price,g.original_price')
            ->page($p, $rows)
            ->select();

        foreach ($goods as $k => $v) {
            $headimg = explode(',', $v['headimg']);
            $goods[$k]['headimg'] = $this->domain() . $headimg[0];
        }

        $this->json_success($goods);
    }

    //断码清仓品牌
    public function dmbrandgoods()
    {
        if (Request::instance()->isPost()) {
            //当前的页码
            $p = empty(input('post.p')) ? 1 : input('post.p');
            //每页显示的数量
            $rows = empty(input('post.rows')) ? 10 : input('post.rows');
            $list = Db::name('goodsBrand')->page($p, $rows)->select();
            foreach ($list as $k => $v) {
                $list[$k]['pic'] = $this->domain() . $v['pic'];
                $goods = Db::name('goods')
                    ->where(['brandid' => $v['id'], 'isqc' => 1])
                    ->field('id,headimg,title,price,original_price')
                    ->limit(3)->select();
                if(empty($goods)){
                        unset($list[$k]);
                }else{
                    foreach ($goods as $gk => $gv) {
                        $headimg = explode(',', $gv['headimg']);
                        $goods[$gk]['headimg'] = $this->domain() . $headimg[0];
                    }
                    $list[$k]['goods'] = $goods;
                }
            }
            $this->json_success($list, '请求数据成功');
        } else {
            $this->json_error('请求方式有问题');
            die;
        }
    }

    //特价商品
    public function tjgoods()
    {
        if (Request::instance()->isPost()) {

            /*
                //当前的页码
            $p = empty(input('post.p')) ?1:input('post.p');
            //每页显示的数量
            $rows = empty(input('post.rows'))?10:input('post.rows');
            $goodsmodel = new \app\api\model\Goods();
            $goods = $goodsmodel->alias('g')
                ->where(['g.istj'=>1])
                ->field('g.id,g.headimg,g.title,g.price,g.original_price')
                ->page($p,$rows)
                ->select();

            foreach($goods as $k=>$v){
                $headimg = explode(',',$v['headimg']);
                $goods[$k]['headimg'] = $this->domain().$headimg[0];
            }

            $this->json_success($goods);
            */

            //当前的页码
            $p = empty(input('post.p')) ? 1 : input('post.p');
            //每页显示的数量
            $rows = empty(input('post.rows')) ? 10 : input('post.rows');
            $list = Db::name('goodsBrand')->page($p, $rows)->select();
            foreach ($list as $k => $v) {
                $list[$k]['pic'] = $this->domain() . $v['pic'];
                $goods = Db::name('goods')
                    ->where(['brandid' => $v['id'], 'istj' => 1])
                    ->field('id,headimg,title,price,original_price')
                    ->select();
                if(empty($goods)){
                        unset($list[$k]);
                }else{
                    foreach ($goods as $gk => $gv) {
                        $headimg = explode(',', $gv['headimg']);
                        $goods[$gk]['headimg'] = $this->domain() . $headimg[0];
                    }
                    $list[$k]['goods'] = $goods;
                }
                
            }         
            $this->json_success($list, '请求数据成功');
        } else {
            $this->json_error('请求方式有问题');
            die;
        }
    }

    //折扣商品
    public function zkgoods()
    {
        if (Request::instance()->isPost()) {

            /*
                //当前的页码
            $p = empty(input('post.p')) ?1:input('post.p');
            //每页显示的数量
            $rows = empty(input('post.rows'))?10:input('post.rows');
            $goodsmodel = new \app\api\model\Goods();
            $goods = $goodsmodel->alias('g')
                ->where(['g.iszk'=>1])
                ->field('g.id,g.headimg,g.title,g.price,g.original_price,zk_price')
                ->page($p,$rows)
                ->select();

            foreach($goods as $k=>$v){
                $headimg = explode(',',$v['headimg']);
                $goods[$k]['headimg'] = $this->domain().$headimg[0];
            }

            $this->json_success($goods);

            */
            //当前的页码
            $p = empty(input('post.p')) ? 1 : input('post.p');
            //每页显示的数量
            $rows = empty(input('post.rows')) ? 10 : input('post.rows');
            $list = Db::name('goodsBrand')->page($p, $rows)->select();
            foreach ($list as $k => $v) {
                $list[$k]['pic'] = $this->domain() . $v['pic'];
                $goods = Db::name('goods')
                    ->where(['brandid' => $v['id'], 'iszk' => 1])
                    ->field('id,headimg,title,price,original_price,zk_price')
                    ->select();
                if(empty($goods)){
                        unset($list[$k]);
                }else{
                    foreach ($goods as $gk => $gv) {
                        $headimg = explode(',', $gv['headimg']);
                        $goods[$gk]['headimg'] = $this->domain() . $headimg[0];
                    }
                    $list[$k]['goods'] = $goods;
                }
                // foreach ($goods as $gk => $gv) {
                //     $headimg = explode(',', $gv['headimg']);
                //     $goods[$gk]['headimg'] = $this->domain() . $headimg[0];
                // }
                // $list[$k]['goods'] = $goods;
            }
            $this->json_success($list, '请求数据成功');
        } else {
            $this->json_error('请求方式有问题');
            die;
        }
    }

    //商品排行
    public function phgoods()
    {
        if (Request::instance()->isPost()) {
            //当前的页码
            $p = empty(input('post.p')) ? 1 : input('post.p');
            //每页显示的数量
            $rows = empty(input('post.rows')) ? 10 : input('post.rows');
            $goodsmodel = new \app\api\model\Goods();
            $goods = $goodsmodel->alias('g')
                ->field('g.id,g.headimg,g.title,g.price,g.original_price,zk_price,g.sold')
                ->page($p, $rows)
                ->order('sold desc')
                ->select();

            foreach ($goods as $k => $v) {
                $headimg = explode(',', $v['headimg']);
                $goods[$k]['headimg'] = $this->domain() . $headimg[0];
            }

            $this->json_success($goods);
        } else {
            $this->json_error('请求方式有问题');
            die;
        }
    }
    //商家排行
    public function phshops()
    {
        if (Request::instance()->isPost()) {
            //当前的页码
            $p = empty(input('post.p')) ? 1 : input('post.p');
            //每页显示的数量
            $rows = empty(input('post.rows')) ? 10 : input('post.rows');
            $shops = Db::name('shop')->alias('g')
                ->join('order o', 'o.shop_id=g.id', 'LEFT')
                ->field('g.id,g.headimg,g.name,g.shoplogo,sum(o.total_num) num')
                ->page($p, $rows)
                ->order('num desc')
                ->select();

            foreach ($shops as $k => $v) {
                $headimg = explode(',', $v['headimg']);
                $shops[$k]['headimg'] = $this->domain() . $headimg[0];
                $shops[$k]['shoplogo'] = $this->domain() . $v['shoplogo'];
            }

            $this->json_success($shops);
        } else {
            $this->json_error('请求方式有问题');
            die;
        }
    }
    
    //附近品牌店
    public function brandshoplist()
    {
         //获取品牌id
        $brandid = input("post.brandid");
        $lat = input('post.lat'); //纬度
        $lng = input('post.lng'); //经度       
        if (empty($lat) && empty($lng)) {
            $this->json_error("获取位置失败！");
            die;
        }
         //当前的页码
         $p = empty(input('post.p')) ?1:input('post.p');
         //每页显示的数量
         $rows = empty(input('post.rows'))?10:input('post.rows');
        $shop=Db::name("shop")->alias("a")
              ->field("a.id,a.name,a.shortname,a.shoplogo,a.intro,a.content,
              a.address,a.headimg,a.shoplogo,a.longitude,a.latitude,a.status,GETDISTANCE(a.latitude,a.longitude,$lat,$lng) as distance")
              ->join("goods b","a.id=b.shopid","LEFT")
              ->where(["b.brandid"=>$brandid,"a.status"=>2])
              ->group("a.id")    
              ->order("distance asc") 
              ->page($p,$rows)       
              ->select();
        foreach($shop as &$v){
            $v['shoplogo']=$this->domain() . $v['shoplogo'];
            $v['headimg']=$this->domain() . $v['headimg'];
            if($v['distance']>1000){
                $v['distance']=round($v['distance']/1000,2)."km";
            }else{
                $v['distance']=round($v['distance'])."m";
            }
        }
        if($shop){
            $this->json_success($shop, '请求数据成功！');
        }else{
            $this->json_error('附近暂无商家！');            
        }
       
    }
     //品牌商品
    public function brandgoods(){
         //获取品牌id
         $brandid = input("post.brandid");
         $lat = input('post.lat'); //纬度
         $lng = input('post.lng'); //经度       
         if (empty($lat) && empty($lng)) {
             $this->json_error("获取位置失败！");
             die;
         }
          //当前的页码
          $p = empty(input('post.p')) ?1:input('post.p');
          //每页显示的数量
          $rows = empty(input('post.rows'))?10:input('post.rows');
         //标签类型
         $type=empty(input("post.type"))?1:input('post.type');
         $where['a.status']=array('eq',1);
         $where['a.check_status']=array('eq',1);
         $where['a.brandid']=array('eq',$brandid);
         if($type==1){//活动
           $goods=Db::name("goods")->alias("a")
            ->field("a.*,b.latitude,b.status,GETDISTANCE(b.latitude,b.longitude,$lat,$lng) as distance")
            ->join("shop b","a.shopid=b.id","LEFT")
            ->order("distance asc") 
            ->where($where)
            ->where(function($query){
                $whereor['a.isqc']=array('eq',1);
                $whereor['a.istj']=array('eq',1);
                $whereor['a.iszk']=array('eq',1);
                $whereor['a.isdiscount']=array('eq',1);
                $whereor['a.ishot']=array('eq',1);
                $query->whereOr($whereor);
            })
            // ->whereOr($whereor)
            ->page($p,$rows)    
            ->select();
         }
         if($type==2){//最新
            $goods=Db::name("goods")->alias("a")
            ->field("a.*,b.latitude,b.status,GETDISTANCE(b.latitude,b.longitude,$lat,$lng) as distance")
            ->join("shop b","a.shopid=b.id","LEFT")
            ->order("a.id desc,distance asc") 
            ->where($where)            
            ->page($p,$rows)    
            ->select();
         }
         if($type==3){
            $goods=Db::name("goods")->alias("a")
            ->field("a.*,b.latitude,b.status,GETDISTANCE(b.latitude,b.longitude,$lat,$lng) as distance")
            ->join("shop b","a.shopid=b.id","LEFT")
            ->order("distance asc") 
            ->where($where)            
            ->page($p,$rows)    
            ->select();
         }
        
        foreach($goods as &$v){
            $headimg = explode(',', $v['headimg']);
            $v['headimg'] = $this->domain() . $headimg[0];
        }
        if($goods){
            $this->json_success($goods, '请求数据成功！');
        }else{
            $this->json_error('附近暂无品牌商品！');            
        }
    }
}
