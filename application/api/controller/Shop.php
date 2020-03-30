<?php
namespace app\api\controller;
use app\api\model\Comment;
use app\api\model\Report;
use app\api\model\Shop as ShopModel;
use think\Db;
use think\Request;
// 商家
class Shop extends Base
{
    public function index()
    {
        $shop_id = input('post.shop_id');
        if(null===$shop_id){
            $this->json_error('请传过来商家编号');
        }

        $lat=input('post.lat');//纬度
        $lng=input('post.lng');//经度
        if(empty($lat) && empty($lng)){
            $this->json_error("获取位置失败！");
            die;
        }
        $tnum = Db::name('comment')->count();



        $res = Db::name('comment')->where(['shop_id'=>$shop_id])->field('sum(logistics) as logistics,sum(manner) as manner')->group('shop_id')->select();



        $num = Db::name('comment')->where(['shop_id'=>$shop_id])->count();


        //物流服务  logistics
        //服务态度  manner
        //liu
        $shop = Db::name("shop")->where('id',$shop_id)->field('id,name,shoplogo,intro,province,city,area,address,description,quality,service,longitude,latitude,GETDISTANCE(latitude,longitude,'.$lat.','.$lng.') as distance')->find();
        if(empty($shop)){
            $this->json_error('商家不存在！');
        }
        $shop['shop_fans']=ShopModel::get($shop_id)->shopFans()->count();
        $shop['shop_goods_num']=ShopModel::get($shop_id)->shopGoodsNum()->count();
        $shop['shoplogo'] = $this->domain().$shop['shoplogo'];       
        $shop['sale_num']=ShopModel::shopOrderNum($shop_id);
        if($res!=null){
            $res = $res[0];
            if($num>0){
                $shop['scomment'] = $num/$tnum;
                $shop['logistics'] = $res['logistics'] /$num;
                $shop['manner'] = $res['manner'] /$num;
            }else{
                $shop['scomment'] = $num/$tnum;
                $shop['logistics'] = 0;
                $shop['manner'] = 0;
            }
        }else{
            $shop['scomment'] = $num/$tnum;
            $shop['logistics'] = 0;
            $shop['manner'] = 0;
        }

        if($shop['distance']>1000){
            $shop['distance']=round($shop['distance']/1000,2)."km";
        }else{
            $shop['distance']=round($shop['distance'])."m";
        }  

        $user_id = input('post.user_id');
        if(null!=$user_id){
            //登录了
            $collectionshop = db('collectionshop')->where(['user_id'=>$user_id,'shop_id'=>$shop_id])->find();
            if(null!=$collectionshop){
                //代表收藏了
                $shop['is_collectionshop'] = 1;
            }else{
                //没有收藏
                $shop['is_collectionshop'] = 0;
            }
        }else{
            //没有登录
            $shop['is_collectionshop'] = 0;
        }
        //收藏商家数量
        $collectionshop = Db::name('collectionshop')->where('shop_id',$shop_id)->count();
        $shop['collectionshop'] = $collectionshop;

        $data = [];

        //商家信息
        $data['shop'] = $shop;

        //筛选type   1 最新    2全部   3高价格    4低价格

        //每页显示的数量
        $type = empty(input('post.type'))?1:input('post.type');

        //当前的页码
        $p = empty(input('post.p')) ?1:input('post.p');


        //每页显示的数量
        $rows = empty(input('post.rows'))?30:input('post.rows');


        switch($type){
            case 1:
                //1 最新
                $goods = \app\api\model\Goods::where('shopid',$shop_id)
                    ->field('id,title,headimg,price')
                    ->order('id','desc')
                    ->page($p,$rows)
                    ->select();
                break;
            case 2:
                //2全部
                $goods = \app\api\model\Goods::where('shopid',$shop_id)
                    ->field('id,title,headimg,price')
                    ->order('id','desc')
                    ->page($p,$rows)
                    ->select();
                break;
            case 3:
                //3高价格
                $goods = \app\api\model\Goods::where('shopid',$shop_id)
                    ->field('id,title,headimg,price')
                    ->order('price','desc')
                    ->page($p,$rows)
                    ->select();
                break;
            case 4:
                //4低价格
                $goods = \app\api\model\Goods::where('shopid',$shop_id)
                    ->field('id,title,headimg,price')
                    ->order('price','asc')
                    ->page($p,$rows)
                    ->select();
                break;
                
        	case 5:
                // brandid shy_goods_brand
                $brand = Db::name('goods')->alias('g')
                        ->join('shy_goods_brand b','g.brandid = b.id')
                        ->where('g.shopid',$shopid)
                        ->field('b.*')
                        ->group('brandid')
                        ->order('sort desc')
                        ->select();
                foreach ($brand as $key => $value) {
                    $brand[$key]['pic'] = $_SERVER['SERVER_NAME'].$value['pic'];
                }


                $this->json_success($brand, '成功');
                break;
            default:
                //1 最新
                $goods = \app\api\model\Goods::where('shopid',$shop_id)
                    ->field('id,title,headimg,price')
                    ->order('id','desc')
                    ->page($p,$rows)
                    ->select();
                break;
        }



        $myimgs = [];
        foreach($goods as $k=>$v){
            $headimg = explode(',',$v['headimg']);
            $goods[$k]['headimg'] = $this->domain().$headimg[0];
            $collectiongoods = Db::name('collectiongoods')->where('goods_id',$v['id'])->count();
            $goods[$k]['collectiongoods'] = $collectiongoods;
            if(null!=$user_id){
                //登录了
                $collectiongoods = db('collectiongoods')->where(['user_id'=>$user_id,'goods_id'=>$v['id']])->find();
                if(null!=$collectiongoods){
                    //代表收藏了
                    $goods[$k]['is_collectiongoods'] = 1;
                }else{
                    //没有收藏
                    $goods[$k]['is_collectiongoods'] = 0;
                }
            }else{
                //没有登录
                $goods[$k]['is_collectiongoods'] = 0;
            }
        }

        $data['goods'] = $goods;


        $this->json_success($data);
    }
    
    
    
    
    
    public function shopGoodsType(){
        $shopid = input('shopid');
        $shoptype = input('type');
        $p = empty(input('post.p')) ? 1 : input('post.p'); //当前的页码
        $rows = empty(input('post.rows')) ? 30 : input('post.rows'); //每页显示的数量

        if (empty($shopid) || empty($shoptype)) {
            $this->json_error('参数错误！');
        }

        $where = [
            'shopid'    =>  $shopid,
        ];
        switch ($shoptype) {
            case 1:
                // 新品
                $where['isnew'] = 1;
                break;
            case 2:
                // 热销
                $where['ishot'] = 1;
                break;
            case 3:
                // 促销
                $where['isdiscount'] = 1;
                break;
            case 4:
                // 清仓
                $where['isqc'] = 1;
                break;
            case 5:
                // brandid shy_goods_brand
                $brand = Db::name('goods')->alias('g')
                        ->join('shy_goods_brand b','g.brandid = b.id')
                        ->where('g.shopid',$shopid)
                        ->field('b.*')
                        ->group('brandid')
                        ->order('sort desc')
                        ->select();
                foreach ($brand as $key => $value) {
                    
                    $brand[$key]['pic'] = $this->domain().$value['pic'];
                }


                $this->json_success($brand, '成功');
                break;
            default:
                $this->json_error('错误！');
                break;
        }
        $goods = Db::name('goods')->where($where)
                    ->field('id,title,headimg,price,original_price')
                    ->order('id', 'desc')
                    ->page($p, $rows)
                    ->select();
        foreach ($goods as $key => $value) {
        	$headimgarr = explode(',', $value['headimg']);
            $goods[$key]['headimg'] = $this->domain().$headimgarr[0];
        }
        
        if ($goods) {
            $this->json_success($goods, '成功');
        }else{
            $this->json_error('', '没有数据');
        }
        

    }
    
    
    
    
    
    
    
    
    

    //商家举报
    public function report()
    {
        $shop_id = input('post.shop_id');
        if(null===$shop_id){
            $this->json_error('请传过来商家编号');
        }
        $rule=[
            'reason'=> 'require',
            'describe'=>'require'
        ];
        $msg=[
            'reason.require'=>'举报原因不能为空',
            'describe.require'=>'举报描述不能为空'
        ];
        $result=$this->validate(input('post.'),$rule,$msg);
        if(true !== $result){
            // 验证失败 输出错误信息
            $this->json_error($result);
            die;
        }else{
            if(!empty(input('post.imgsrc'))){
                $data = [
                    'shop_id'=>$shop_id,
                    'reason'=>input('post.reason'),
                    'describe'=>input('post.describe'),
                    'imgsrc'=>input('post.imgsrc'),
                    'add_time'=>time()
                ];

            }else{
                $data = [
                    'shop_id'=>$shop_id,
                    'reason'=>input('post.reason'),
                    'describe'=>input('post.describe'),
                    'add_time'=>time()
                ];
            }

            $id = Report::insertGetId($data);
            if($id){
                $info = Report::find($id);
                $this->json_success($info,'举报成功');
            }else{
                $this->json_error('举报失败');
            }

        }

    }
    //商家信息
    public function shopinfos(){
        $shop_id=input("post.shop_id");
        $shopInfo=Db::name("shop")
                        ->field("shoplogo,name,intro,content,longitude,latitude,addtime,star,province,city,area,street,quality,service,address,yyzz,headimg,description,quality,service")
                        ->where(['id'=>$shop_id])
                        ->find();
        $shopInfo['shop_fans']=ShopModel::get($shop_id)->shopFans()->count();
        $shopInfo['shop_goods_num']=ShopModel::get($shop_id)->shopGoodsNum()->count();
        $shopInfo['shoplogo'] = $this->domain().$shopInfo['shoplogo'];       
        $shopInfo['sale_num']=ShopModel::shopOrderNum($shop_id);
        $shopInfo['shop_address']=$shopInfo['province'].$shopInfo['city'].$shopInfo['street'].$shopInfo['address'];
        if($shopInfo['yyzz']){
            $yyzz=explode(',',$shopInfo['yyzz']);
            for($i=0; $i<count($yyzz); $i++){
                $yyzz[$i]=$this->domain().$yyzz[$i];
            }
            $shopInfo['yyzz']=$yyzz;
        }
        $count=Db::name('shop')->count();
        $shops=Db::name('shop')->field("SUM(description) description,SUM(quality) quality,SUM(service) service")->find();
        $description=$shops['description']/$count;//平均描述
        $quality=$shops['quality']/$count;//平均质量
        $service=$shops['service']/$count;//平均服务
        if($description==0){
            $shopinfo['description']=array(
                'description'=>0,
                'rate'=>0
            );
        }else{
            $shopInfo['description']=array(
                'description'=>$shopInfo['description'],
                'rate'=>round((($shopInfo['description']-$description)/$description*100),2)."%",
            );
        }       
        
       
        if($quality==0){
            $shopInfo['quality']=array(
                'quality'=>0,
                'rate'=>0
            );
        }else{
            $shopInfo['quality']=array(
                'quality'=>$shopInfo['quality'],
                'rate'=>round((($shopInfo['quality']-$quality)/$quality*100),2)."%",
            );
        }
        if($service==0){
            $shopInfo['quality']=array(
                'quality'=>0,
                'rate'=>0
            );
        }else{
            $shopInfo['service']=array(
                'service'=>$shopInfo['service'],
                'rate'=>round((($shopInfo['service']-$service)/$service*100),2)."%",
            );
        }
        
        $shop_order_count=Db::name('order')->where(['shop_id'=>$shop_id,''])->select();
        var_dump($shopInfo);       
        exit;
    }
    
    public function details()
    {
    	$shop_id = input('post.shop_id');
        if(null===$shop_id){
            $this->json_error('请传过来商家编号');
        }
        
        $shopInfo=Db::name("shop")
                        ->field("shoplogo,name,intro,content,longitude,latitude,addtime,star,province,city,area,street,quality,service,address,yyzz,headimg,description,quality,service")
                        ->where(['id'=>$shop_id])
                        ->find();
        $shopInfo['shop_fans']=ShopModel::get($shop_id)->shopFans()->count();
        $shopInfo['shop_goods_num']=ShopModel::get($shop_id)->shopGoodsNum()->count();
        $shopInfo['shoplogo'] = $this->domain().$shopInfo['shoplogo'];       
        $shopInfo['sale_num']=ShopModel::shopOrderNum($shop_id);
        $shopInfo['shop_address']=$shopInfo['province'].$shopInfo['city'].$shopInfo['street'].$shopInfo['address'];
        if($shopInfo['yyzz']){
            $yyzz=explode(',',$shopInfo['yyzz']);
            for($i=0; $i<count($yyzz); $i++){
                $yyzz[$i]=$this->domain().$yyzz[$i];
            }
            $shopInfo['yyzz']=$yyzz;
        }
        $count=Db::name('shop')->count();
        $shops=Db::name('shop')->field("SUM(description) description,SUM(quality) quality,SUM(service) service")->find();
        $description=$shops['description']/$count;//平均描述
        $quality=$shops['quality']/$count;//平均质量
        $service=$shops['service']/$count;//平均服务
        if($description==0){
            $shopinfo['description']=array(
                'description'=>0,
                'rate'=>0
            );
        }else{
            $shopInfo['description']=array(
                'description'=>$shopInfo['description'],
                'rate'=>round((($shopInfo['description']-$description)/$description*100),2)."%",
            );
        }       
        
       
        if($quality==0){
            $shopInfo['quality']=array(
                'quality'=>0,
                'rate'=>0
            );
        }else{
            $shopInfo['quality']=array(
                'quality'=>$shopInfo['quality'],
                'rate'=>round((($shopInfo['quality']-$quality)/$quality*100),2)."%",
            );
        }
        if($service==0){
            $shopInfo['quality']=array(
                'quality'=>0,
                'rate'=>0
            );
        }else{
            $shopInfo['service']=array(
                'service'=>$shopInfo['service'],
                'rate'=>round((($shopInfo['service']-$service)/$service*100),2)."%",
            );
        }
       $arr = json_encode($shopInfo);
       $this->json_success($arr);
    }

}