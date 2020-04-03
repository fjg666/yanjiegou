<?php

namespace app\api\controller;

use app\api\model\Comment;
use think\Db;
use think\Request;
use think\Session;
use think\Cookie;
// 商品
class Goods extends Base
{
    public function show()
    {
        $goods_id = input('post.goods_id');
        if (null === $goods_id) {
            $this->json_error('请传过来商品编号');
        }

        $goodsmodel = new \app\api\model\Goods();
        $goods = $goodsmodel->alias('g')
            ->join('__SHOP__ s', 's.id=g.shopid', 'LEFT')
            ->order('g.sorts asc,g.id desc')
            ->where('g.id', $goods_id)
            ->field('g.id,g.headimg,g.title,g.price,g.original_price,g.cost_price,g.content,g.sold,g.parameter,s.tag,s.id as sid,s.name,s.shoplogo,s.star,s.description,s.quality,s.service,s.province,s.city,s.area,s.street,s.address,g.issendfree')
            ->find();
        if ($goods['parameter'] != '') {
            $parameter[] = json_decode($goods['parameter'],true);
            $goods['parameter'] = $parameter;
        }
        if (!empty($goods['tag'])) {
            $goods['tag'] = json_decode($goods['tag'],true);
        }


        $headimg = explode(',', $goods['headimg']);
        $imgs = [];
        foreach ($headimg as $k => $v) {
            $imgs[] = $this->domain() . $v;
        }
        $lat=input('post.lat');//纬度
        $lng=input('post.lng');//经度
        if(empty($lat) &&empty($lng)){
            $this->json_error("获取位置失败！");
            die;
        }
        $goods['headimg'] = $imgs;
        $goods['shoplogo'] = $this->domain() . $goods['shoplogo'];

        $user_id = input('post.user_id');
        if (null != $user_id) {
            //登录了
            $collectiongoods = db('collectiongoods')->where(['user_id' => $user_id, 'goods_id' => $goods_id])->find();
            if (null != $collectiongoods) {
                //代表收藏了
                $goods['is_collectiongoods'] = 1;
            } else {
                //没有收藏
                $goods['is_collectiongoods'] = 0;
            }
        } else {
            //没有登录
            $goods['is_collectiongoods'] = 0;
        }

        //当前的页码
        $p = empty(input('post.p')) ? 1 : input('post.p');
        //每页显示的数量
        $rows = empty(input('post.rows')) ? 3 : input('post.rows');

        //评论
        $comments = Db::name('comment')->alias('c')
            ->join('__USERS__ u', 'u.id=c.user_id')
            ->where('c.goods_id', $goods_id)
            ->field('c.id as cid,c.content,c.imgsrc,c.video,c.add_time,u.avatar,u.username,u.mobile')
            ->page($p, $rows)
            ->select();



        foreach ($comments as $k => $v) {
            $imgsrc = explode(',', $v['imgsrc']);
            foreach ($imgsrc as $key => $val) {
                $imgsrc[$key] = $this->domain() . $val;
            }
            $comments[$k]['imgsrc'] = $imgsrc;
            $comments[$k]['avatar'] = $this->domain() . $v['avatar'];
            $comments[$k]['add_time'] = date('Y-m-d H:i:s', $v['add_time']);
        }




        $goods['comment'] = $comments;



        $where = [
            //status  0否1上架
            'g.status' => 1,
            //check_status   --审核状态  -1:违规 0:未审核 1:已审核
            'g.check_status' => 1

        ];

        $tjgoods = $goodsmodel->alias('g')
            ->join('__SHOP__ s', 's.id=g.shopid', 'LEFT')
            ->order('g.readpoint desc,g.id desc')
            ->where($where)
            ->field('g.id,g.headimg,g.title,g.price,s.id as sid,s.name,s.shoplogo,s.longitude,s.latitude,GETDISTANCE(s.latitude,s.longitude,'.$lat.','.$lng.') as distance')
            ->page($p, $rows)
            ->select();
        foreach ($tjgoods as $k => $v) {
            $headimg = explode(',', $v['headimg']);
            $tjgoods[$k]['headimg'] = $this->domain() . $headimg[0];
            $tjgoods[$k]['shoplogo'] = $this->domain() . $v['shoplogo'];
            if($v['distance']>1000){
                $tjgoods[$k]['distance']=round($v['distance']/1000,2)."km";
            }else{
                $tjgoods[$k]['distance']=round($v['distance'])."m";
            }
        }
        $goods['goods_sttr']=$this->getsttrgroup($goods_id);
        $goods['tjgoods'] = $tjgoods;
        $sale_num = $this->goodsSaleNum($goods_id);
        $goods['sale_num'] = $sale_num ? $sale_num : 0;



        $this->readNumber($goods_id);
        $this->json_success($goods);
    }
    function readNumber($goods_id){
        // 点击量加1
        $readgoods = Session::get('readgoods');
        if (empty($readgoods)) {
            Db::name('goods')->where('id', $goods_id)->setInc('readpoint');
            $readgoods[$goods_id] = time();
            Session::set('readgoods',$readgoods);
        }else{
            // 不为空，判断有没有goods_id.
            if (array_key_exists($goods_id,$readgoods)) {
                // 有id判断时间是否在一天内
                if (($readgoods[$goods_id]+86400) < time() ) {
                    Db::name('goods')->where('id', $goods_id)->setInc('readpoint');
                    $readgoods[$goods_id] = time();
                    Session::set('readgoods',$readgoods);
                }
            }else{
                // 没有id第一次点击，加
                Db::name('goods')->where('id', $goods_id)->setInc('readpoint');
                $readgoods[$goods_id] = time();
                Session::set('readgoods',$readgoods);
            }
        }
    }


    /**
     * 商品评论列表
     * goods_id
     * p
     * rows
     */
    public function getcommentlist(){
        $goods_id = input('goods_id');
        if (empty($goods_id)) {
            $this->json_error('请传过来商品编号');
        }
        //当前的页码
        $p = empty(input('post.p')) ? 1 : input('post.p');
        //每页显示的数量
        $rows = empty(input('post.rows')) ? 3 : input('post.rows');

        //评论
        $comments = Db::name('comment')->alias('c')
            ->join('__USERS__ u', 'u.id=c.user_id')
            ->where('c.goods_id', $goods_id)
            ->field('c.id as cid,c.order_gid,c.content,c.imgsrc,c.video,c.add_time,u.avatar,u.username,u.mobile')
            ->page($p, $rows)
            ->select();

        foreach ($comments as $k => $v) {
            if (!empty($v['imgsrc'])) {
                $imgsrc = explode(',', $v['imgsrc']);
                foreach ($imgsrc as $key => $val) {
                    $imgsrc[$key] = $this->domain() . $val;
                }
                $comments[$k]['imgsrc'] = $imgsrc;
            }
            if (!empty($v['avatar'])) {
                $comments[$k]['avatar'] = $this->domain() . $v['avatar'];
            }
            $comments[$k]['add_time'] = date('Y-m-d H:i:s', $v['add_time']);


            $order_gid = $v['order_gid'];
        }
        $this->json_success($comments);
    }
    //获取商品的月销量--v
    public function goodsSaleNum($goods_id)
    {
        $where['a.goodsid'] = array('eq', $goods_id);
        //开始计算时间30天       
        $time = strtotime("-30day");
        $where['b.paytime'] = array('egt', $time);
        $where['b.status'] = array('eq', 5);
        $count = Db::name("order_goods")->alias("a")
            ->join("order b", "a.order_sn=b.order_sn", "LEFT")
            ->where($where)
            ->value("SUM(a.num)");
        return $count;
    }
    //-v
    //点击搜索商品
    public function search()
    {
        $goods_name = input('post.goods_name');
        if (null === $goods_name) {
            $this->json_error('请传过来商品名称');
        }
        //当前的页码
        $p = empty(input('post.p')) ? 1 : input('post.p');
        //每页显示的数量
        $rows = empty(input('post.rows')) ? 10 : input('post.rows');



        $goodsmodel = new \app\api\model\Goods();
        $where = [
            //status  0否1上架
            'g.status' => 1,
            //check_status   --审核状态  -1:违规 0:未审核 1:已审核
            'g.check_status' => 1

        ];

        $where['g.title'] = ['like', '%' . $goods_name . '%'];

        $goods = $goodsmodel->alias('g')
            ->join('__SHOP__ s', 's.id=g.shopid', 'LEFT')
            ->order('g.readpoint desc,g.id desc')
            ->where($where)
            ->field('g.id,g.headimg,g.title,g.price,s.id as sid,s.name,s.shoplogo,s.headimg as sheadimg,g.sold,g.ishot')
            ->page($p, $rows)
            ->select();

        foreach ($goods as $k => $v) {
            $headimg = explode(',', $v['headimg']);
            $sheadimg = explode(',', $v['sheadimg']);
            $goods[$k]['headimg'] = $this->domain() . $headimg[0];

            $goods[$k]['sheadimg'] = $this->domain() . $sheadimg[0];

            $goods[$k]['shoplogo'] = $this->domain() . $v['shoplogo'];
        }

        $this->json_success($goods);
    }

    //商品评论
    public function comment()
    {
        $user_id = input('post.user_id');
        if (null === $user_id) {
            $this->json_error('请传过来用户编号');
        }
        $order_id = input('post.order_id');
        if (null === $order_id) {
            $this->json_error('请传过来订单商品编号');
        }
        $goods_id = input('post.goods_id');
        if (null === $goods_id) {
            $this->json_error('请传过来商品编号');
        }

        $rule = [
            'content' => 'require',
            'logistics' => 'require',
            'manner' => 'require'
        ];
        $msg = [
            'content.require' => '评论内容不能为空',
            'logistics.require' => '物流服务不能为空',
            'manner.require' => '服务态度不能为空'
        ];
        $result = $this->validate(input('post.'), $rule, $msg);

        if (true !== $result) {
            // 验证失败 输出错误信息
            $this->json_error($result);
            die;
        } else {
            $goods = db('goods')->where('id', $goods_id)->find();
            if (null == $goods) {
                $this->json_error('非法操作');
                die;
            }
            if (empty(input('post.imgsrc'))) {
                $data = [
                    'goods_id' => $goods_id,
                    'user_id' => $user_id,
                    'shop_id' => $goods['shopid'],
                    'order_gid' => $order_id,
                    'content' => input('post.content'),
                    'logistics' => input('post.logistics'),
                    'manner' => input('post.manner'),
                    'add_time' => time()
                ];
            } else {
                $data = [
                    'goods_id' => $goods_id,
                    'user_id' => $user_id,
                    'order_gid' => $order_id,
                    'content' => input('post.content'),
                    'logistics' => input('post.logistics'),
                    'manner' => input('post.manner'),
                    'imgsrc' => input('post.imgsrc'),
                    'add_time' => time()
                ];
            }


            $id = db('comment')->insertGetId($data);
            if ($id) {
                //评论成功，更改状态
                Db::name('order_goods')->where(['id' => $order_id, 'goodsid' => $goods_id])->update(['status' => 1]);

                $res = Db::name('order_goods')->where(['id' => $order_id, 'goodsid' => $goods_id])->find();
                $order_sn = $res['order_sn'];
                $res2 = Db::name('order_goods')->where(['order_sn' => $order_sn, 'status' => 1])->select();

                $status = array_column($res2, 'status');
                if (!in_array(0, $status)) {
                    //订单状态status 1.待付款   2.待发货    3.已发货   4.待评价 5.已完成  6.已关闭 7.售后 8.取消订单
                    Db::name('order')->where(['order_sn' => $order_sn])->update(['status' => 5]);
                }

                $data['comment_id'] = $id;
                $this->json_success($data, '评论成功');
                die;
            } else {
                $this->json_error('评论失败');
            }
        }
    }

    //加入购物车
    public function addcart()
    {
        $user_id = input('post.user_id');
        if (null === $user_id) {
            $this->json_error('请传过来用户编号');
        }
        $goods_id = input('post.goods_id');
        if (null === $goods_id) {
            $this->json_error('请传过来商品编号');
        }
        $num = input('post.num');
        if (null === $num) {
            $this->json_error('请传过来购买数量');
        }
        if ($num <= 0) {
            $this->json_error('数量不合法');
            die;
        }
        /*--chen*/
        $is_spec=Db::name('goods')->where('id',$goods_id)->value('is_spec');
        $sku_id = input('post.sku_id')?input('post.sku_id'):0;
        if ($is_spec == 1) {
            if (empty($sku_id)) {
                $this->json_error('请传过来sku编号');
            }
            // 查看sku库存是否充足
            $sku = Db::name('GoodsSttrxsku')->where('id', $sku_id)->find();
            $number = $sku ? $sku['number'] : 0;
            if (!$sku || $num > $number) {
                $this->json_error('库存不足');
                die;
            }
        }
        
        $is_new = input('post.is_new') ? input('post.is_new') : 0;
        /*--chen*/


        //查看总库存是否够
        $goods = Db::name('goods')->where('id', '=', $goods_id)->find();
        $total = $goods['total'];
        if ($num > $total && $is_new == 0) {
            $this->json_error('总库存不足');
            die;
        }
        /*--chen*/
        //        $goods_attr = input('post.goods_attr');
        //        if(null===$goods_attr){
        //            $this->json_error('请传过来商品属性');
        //        }

        //        if(!is_json($goods_attr)){
        //            $this->json_error('商品属性格式不对');
        //            die;
        //        }
        /*--重写*/
        // 商品属性由sku获得
        if ($is_spec == 1) {
            $goods_attr = $sku['group_sku'];
        }else{
            $goods_attr = '';
        }
        

        /*--chen*/

        $info = [
            'user_id' => $user_id,
            'goods_id' => $goods_id,
            'num' => $num,
            'goods_attr' => $goods_attr,
            'is_new' => $is_new,
            'create_time' => time(),
            'update_time' => time()
        ];
        /*--chen*/
        if ($is_spec == 1) {
            $info['sku_id'] = $sku_id;
            $where['sku_id'] = $sku_id;
        } 
        
        //立即购买
        if($is_new==1){
            $cart_id = Db::name('shopcart')->insertGetId($info);
            $data['cart_id']=$cart_id;
            $this->json_success($data,"加入购物车成功");
            die;
        }
               
        /*--chen*/
        //加入之前判断购物车是否已经有了，有了话，只加数量
        $where['goods_id'] = $goods_id;
        $where['user_id'] = $user_id;
        $where['goods_attr'] = $goods_attr;
        $where['is_new']=0;        
        $shopcart = Db::name('shopcart')->where($where)->find();
        if (null === $shopcart) {
            //插入之前，取到所有的数量，看是否库存充足
            $res = Db::name('shopcart')->insertGetId($info);
        } else {

            //就是更新数量
            //更新数量前，看库存是否充足
            $cartnum = $shopcart['num'];
            $mynum = $cartnum + $num;
            if ($mynum > $total) {
                $this->json_error('总库存不足');
                die;
            }
            /*--chen*/
            // cku库存检测
            if ($is_spec == 1) {
                $number = $sku['number'];
                $sku_num = $cartnum + $num;
                if ($sku_num > $number) {
                    $this->json_error('库存不足');
                    die;
                }
            }
            /*--chen*/


            $cart_id = $shopcart['id'];
            $res = Db::name('shopcart')->where('id', '=', $cart_id)->setInc('num', $num);
        }

        if ($res) {
            //查看当前用户的购物车信息
            $data = Db::name('shopcart')->alias('c')
                ->join('__GOODS__ g', 'g.id = c.goods_id', 'LEFT')
                // ->where('user_id', '=', $user_id)
                ->where(['c.user_id'=>$user_id,'c.is_new'=>0])
                ->order('c.id desc')
                ->field('g.shopid,g.headimg,g.title,c.*')
                ->select();
            foreach ($data as $k => $v) {
                $headimg = explode(',', $v['headimg']);
                $data[$k]['headimg'] = $this->domain() . $headimg[0];
                $data[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                $data[$k]['update_time'] = date('Y-m-d H:i:s', $v['update_time']);
            }

            $this->json_success($data, '加入购物车成功');
            die;
        } else {
            $this->json_error('加入购物车失败');
            die;
        }
    }

    //购物车加减
    public function makecart()
    {
        $user_id = input('post.user_id');
        if (null === $user_id) {
            $this->json_error('请传过来用户编号');
        }
        $cart_id = input('post.cart_id');
        if (null === $cart_id) {
            $this->json_error('请传过来购物车编号');
        }
        //操作类型，一个是输入   加   减
        $ctype = input('post.ctype');
        if (null === $ctype) {
            $this->json_error('请传过来操作类型');
        }
        $cart = Db::name('shopcart')->where(['user_id' => $user_id, 'id' => $cart_id])->find();

        $goods_id = $cart['goods_id'];
        //查看库存是否够
        $goods = Db::name('goods')->where('id', '=', $goods_id)->find();
        $total = $goods['total'];

        if ($ctype == 1) {  //加
            //取出购物车中的数量
            $cartnum = $cart['num'];
            if ($cartnum + 1 > $total) {
                $this->json_error('库存不足');
                die;
            }
            $res = Db::name('shopcart')->where(['user_id' => $user_id, 'id' => $cart_id])->setInc('num', 1);
        } else if ($ctype == 2) { //减
            $res = Db::name('shopcart')->where(['user_id' => $user_id, 'id' => $cart_id])->setDec('num', 1);
        } else {
            //输入数字
            $inputnum = input('post.num');
            if (null === $inputnum) {
                $this->json_error('请输入购买的数量');
            }
            //取出购物车中的数量
            $cartnum = $cart['num'];
            if ($cartnum + $inputnum > $total) {
                $this->json_error('库存不足');
                die;
            }
            $res = Db::name('shopcart')->where(['user_id' => $user_id, 'id' => $cart_id])->update(['num' => $inputnum]);
        }

        if ($res) {
            Db::name('shopcart')->where(['user_id' => $user_id, 'id' => $cart_id])->update(['update_time' => time()]);
            //查看当前用户的购物车信息
            $data = Db::name('shopcart')->alias('c')
                ->join('__GOODS__ g', 'g.id = c.goods_id', 'LEFT')
                ->where(['c.user_id'=>$user_id,'c.is_new'=>0])
                ->order('c.id desc')
                ->field('g.shopid,g.headimg,g.title,c.*')
                ->select();
            foreach ($data as $k => $v) {
                $headimg = explode(',', $v['headimg']);
                $data[$k]['headimg'] = $this->domain() . $headimg[0];
                $data[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                $data[$k]['update_time'] = date('Y-m-d H:i:s', $v['update_time']);
            }
            $this->json_success($data, '更新购物车成功');
        } else {
            $this->json_error('更新购物车失败');
        }
    }

    //购物车删除
    public function delcart()
    {
        $user_id = input('post.user_id');
        if (null === $user_id) {
            $this->json_error('请传过来用户编号');
        }
        $cart_id = input('post.cart_id');
        if (null === $cart_id) {
            $this->json_error('请传过来购物车编号');
        }
        $res = Db::name('shopcart')->where('user_id', '=', $user_id)->where("id in ($cart_id)")->delete();
        if ($res) {
            //查看当前用户的购物车信息
            $data = Db::name('shopcart')->alias('c')
                ->join('__GOODS__ g', 'g.id = c.goods_id', 'LEFT')
                ->where(['c.user_id'=>$user_id,'c.is_new'=>0])
                ->order('c.id desc')
                ->field('g.shopid,g.headimg,g.title,c.*')
                ->select();
            foreach ($data as $k => $v) {
                $headimg = explode(',', $v['headimg']);
                $data[$k]['headimg'] = $this->domain() . $headimg[0];
                $data[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                $data[$k]['update_time'] = date('Y-m-d H:i:s', $v['update_time']);
            }
            $this->json_success($data, '删除购物车成功');
        } else {
            $this->json_error('删除购物车失败');
        }
    }

    //商品推荐
    public function recommend()
    {
        $user_id = input('post.user_id');
        if (null === $user_id) {
            $this->json_error('请传过来用户编号');
        }
        //查看当前用户购买了哪些商品
        $order_sns = Db::name('order')->where(['user_id' => $user_id])->field('order_sn')->select();
        $order_sns = array_column($order_sns, 'order_sn');

        $goods_ids = Db::name('order_goods')->whereIn('order_sn', $order_sns)->field('goodsid')->select();
        $goods_ids = array_unique(array_column($goods_ids, 'goodsid'));

        $goods = Db::name('goods')->alias('g')
            ->join('__SHOP__ s', 's.id=g.shopid', 'LEFT')
            ->order('g.readpoint desc,g.id desc')
            ->whereNotIn('g.id', $goods_ids)
            ->field('g.id,g.headimg,g.title,g.price,s.id as sid,s.name,s.shoplogo')
            ->limit(20)
            ->select();

        foreach ($goods as $k => $v) {
            $goods[$k]['shoplogo'] = $this->domain() . $v['shoplogo'];
            $headimg = explode(',', $v['headimg']);
            $goods[$k]['headimg'] = $this->domain() . $headimg[0];
        }
        $this->json_success($goods);
    }

    /*--chen*/
    /**
     * 根据组合获取库存
     * $group   组合： ["1","6"]
     * $goods_id    商品id
     * */
    public function stock()
    {
        $json = input('post.group');
        $goods_id = input('post.goods_id');
        if (empty($json) || empty($goods_id)) {
            $this->json_error('参数错误');
        }
        $sttr_arr = json_decode($json, true);
        $group_id = [];
        foreach ($sttr_arr as $key => $value) {
            $sttr_id = Db::name('GoodsSttrval')->where('id', $value)->value('sttr_id');
            $sttr = Db::name('GoodsSttr')->where('id', $sttr_id)->where('is_main', 1)->find();
            if ($sttr) {
                $group_id[] = $value;
            }
        }
        asort($group_id);
        // 组合
        $arr = implode('_', $group_id);
        $find = Db::name('GoodsSttrxsku')->where('sttrval_group', $arr)->where('goods_id', $goods_id)->find();
        if ($find) {
            $this->json_success($find, '成功');
        } else {
            $this->json_error('没有该组合');
        }
    }

    /**
     * 获取商品属性
     * $goods_id    商品id
     * return   vue格式json对象，前台不需要遍历
     * */
    public function getgoodsttr()
    {
        $goods_id = input('post.goods_id');
        if (empty($goods_id) || !is_numeric($goods_id)) {
            $this->json_error('参数错误');
        }
        $sttrArr = Db::name('GoodsSttrval')->field('sttr_id')->where('goods_id', $goods_id)->group('sttr_id')->select();
        if (empty($sttrArr)) {
            $this->json_error('没有属性');
        }
        // 图片
        $imgUrl = '';
        $headimg = Db::name('goods')->where('id',$goods_id)->value('headimg');
        if (!empty($headimg)) {
            $headimg = explode(',', $headimg);
            $imgUrl = $headimg[0];
        }
        
        $data = [];
        foreach ($sttrArr as $key => $value) {
            $sttr = Db::name('GoodsSttr')->where('id', $value['sttr_id'])->where('status', 1)->find();
            $sttrval = Db::name('GoodsSttrval')->field('id,sttr_value')->where('goods_id',$goods_id)->where('sttr_id', $value['sttr_id'])->where('status', 1)->select();
            $sttrcount = Db::name('GoodsSttrval')->field('id,sttr_value')->where('sttr_id', $value['sttr_id'])->where('status', 1)->count();
            $sttrarr = [];
            foreach ($sttrval as $k => $v) {
                $sttrarr[$k] = [
                    'id'    =>  $v['id'],
                    'name'  =>  $v['sttr_value'],
                    'imgUrl' =>  $this->domain().$imgUrl,
                ];
            }
            $data['info'][$key] = [
                'k_id' => $sttr['id'],
                'k' => $sttr['key'],
                'v' => $sttrarr,
                'k_s' => 's' . $sttr['id'],
                'count' => $sttrcount
            ];
        }
        $data['list'] = $this->getsttrgroup($goods_id);
               // print_r($data);exit;
        $this->json_success($data, '成功');
    }



    /**
     * SKU表所有属性组合
     * $goods_id    商品id
     * */
    protected function getsttrgroup($goods_id)
    {
        if (empty($goods_id)) {
            $this->json_error('参数错误');
        }
        $data = Db::name('GoodsSttrxsku')->where('goods_id', $goods_id)->where('number', 'gt', 0)->select();
        $info=array();
        foreach ($data as $key => $value) {
            $group = json_decode($value['group_sku'], true);
            $info[$key] = [
                'id' => $value['id'],  //sku_id
                'price' => $value['money']*100,           //价格
                'discount' => '',      //折扣
                'stock_num' => $value['number'],     //库存
                'goods_id' => $value['goods_id']   //商品id
            ];
            // 规格
            foreach ($group as $k => $v) {
                $info[$key]['s' . $k] = $v;
            }
        }
        
        //        $info[] = [
        //            'id'=> $value['id'],  //sku_id
        //            'price'=> ,           //价格
        //            'discount'=> '',      //折扣
        //            's1'=> '1215',        //s1属性的id
        //            's2'=> '1193',        //s2属性的id
        //            'stock_num'=> 20,     //库存
        //            'goods_id'=> 946755   //商品id
        //        ];
        return $info;
    }
    
     
}
