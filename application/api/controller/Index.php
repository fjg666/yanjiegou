<?php
namespace app\api\controller;
use app\api\model\Ad;
use app\api\model\Bigshop;
use app\api\model\Goods;
use think\Db;
use think\Request;
use geo\Geohash;

class Index extends Base
{

    //首页轮播图
    public function adv()
    {
        $type_id = input('get.type_id') ? input('get.type_id') : 1;
        /*分支修改*/
        $where['type_id'] = $type_id;
        $where['open'] = 1;

        $data = Ad::where($where)->order('sort desc,ad_id')->field('name,ad_id,pic,url')->select();
        foreach ($data as $k => $v) {
            $data[$k]['pic'] = $this->domain(). $v['pic'];
        }
        $this->json_success($data);
    }

    //首页三张商家图
    public function bigshop()
    {
        $limit = input('get.num') ? input('get.num') : 3;
        $where = [
            'is_home' => 1,

        ];
        $bigshops = Bigshop::where($where)->limit($limit)->order('sort asc,id desc')->field('id,name,headimg,intro')->select();
        $data = [];
        foreach ($bigshops as $k => $v) {
            $headimg = explode(',',$v['headimg']);
            $data[$k]['id'] = $v['id'];
            $data[$k]['name'] = $v['name'];
            $data[$k]['intro'] = $v['intro'];
            $data[$k]['headimg'] = $this->domain().$headimg[0];
        }

        $this->json_success($data,'请求数据成功');
    }

    //首页商品显示
    public function goods()
    {
        if (Request::instance()->isPost()){
            //当前的页码
            $p = empty(input('post.p')) ?1:input('post.p');

            //每页显示的数量
            $rows = empty(input('post.rows'))?10:input('post.rows');
            $user_id =empty(input('post.user_id')) ? "" : input('post.user_id');
            $lat=input('post.lat');//纬度
            $lng=input('post.lng');//经度
            $goodsmodel = new Goods();
            if(empty($lat) &&empty($lng)){
                $this->json_error("获取位置失败！");
                die;
            }
            $where = [
                //status  0否1上架
                'g.status'=>1,
                //check_status   --审核状态  -1:违规 0:未审核 1:已审核
                'g.check_status'=>1,
                's.is_lock'=>0 //商家锁定

            ];

            $goods = $goodsmodel->alias('g')
                ->join('__SHOP__ s','s.id=g.shopid','LEFT')               
                ->where($where)
                ->field('g.id,g.headimg,g.title,g.price,g.label,s.id as sid,s.name,s.shoplogo,s.longitude,s.latitude,GETDISTANCE(s.latitude,s.longitude,'.$lat.','.$lng.') as distance')
                ->order('distance asc')                              
                ->page($p,$rows)
                ->select(); 
            foreach($goods as $k=>$v){
                $headimg = explode(',',$v['headimg']);
                $goods[$k]['headimg'] = $this->domain().$headimg[0];
                $goods[$k]['shoplogo'] = $this->domain().$v['shoplogo'];
                $goods[$k]['collection_num']=$goodsmodel::get($v['id'])->collectiongoods()->count();
                if($v['distance']>1000){
                    $goods[$k]['distance']=round($v['distance']/1000,2)."km";
                }else{
                    $goods[$k]['distance']=round($v['distance'])."m";
                }
                if($user_id){
                 $goods[$k]['is_collection']=$goodsmodel->if_collection($v['id'],$user_id);               
                }else{
                    $goods[$k]['is_collection']=0;
                }
                $goods[$k]['label'] = explode(',', $v['label']);
            }              
            $this->json_success($goods,'请求数据成功');

        }else{
            $this->json_error('请求方式有问题');
            die;
        }


    }
    //推荐商品
    public function recommendGoods(){
        if (Request::instance()->isPost()){
            //当前的页码
            $p = empty(input('post.p')) ?1:input('post.p');
            //每页显示的数量
            $rows = empty(input('post.rows'))?10:input('post.rows');          
            $lat=input('post.lat');//纬度
            $lng=input('post.lng');//经度
            if(empty($lat) && empty($lng)){
                $this->json_error("获取位置失败！");
                die;
            }
            $goodsmodel = new Goods();
            $where = [               
                'g.status'=>1,                
                'g.check_status'=>1,
                'g.isrecommand'=>1,
                's.is_lock'=>0 //商家锁定

            ];
            $goods = $goodsmodel->alias('g')
                ->join('__SHOP__ s','s.id=g.shopid','LEFT')
                ->order('g.readpoint desc,g.id asc')
                ->where($where)
                ->field('g.id,g.headimg,g.title,g.price,g.label,s.id as sid,s.name,s.shoplogo,s.longitude,s.latitude,GETDISTANCE(s.latitude,s.longitude,'.$lat.','.$lng.') as distance')
                ->order('distance ASC')
                ->page($p,$rows)
                ->select();
            foreach($goods as $k=>$v){
                $headimg = explode(',',$v['headimg']);
                $goods[$k]['headimg'] = $this->domain().$headimg[0];
                $goods[$k]['shoplogo'] = $this->domain().$v['shoplogo'];
                //$goods[$k]['collection_num']=$goodsmodel::get($v['id'])->collectiongoods()->count();
                if($v['distance']>1000){
                    $goods[$k]['distance']=round($v['distance']/1000,2)."km";
                }else{
                    $goods[$k]['distance']=round($v['distance'])."m";
                }                
                $goods[$k]['label'] = explode(',', $v['label']);
            }              
            $this->json_success($goods,'请求数据成功');

        }else{
            $this->json_error('请求方式有问题');
            die;
        }
    }

    //商家推广
    public function SpreadGoods(){
        $shop = Db::name('spread')->field("shop_id,shop_name")->where("is_failed", 2)->orderRaw('rand()')->limit(1)->select();
        if($shop){
            $goods = Db::name("goods")->alias('g')
                ->join('__SHOP__ s','s.id=g.shopid','LEFT')
                ->order('g.readpoint desc,g.id asc')
                ->where("id", $shop[0]['shop_id'])
                ->field('g.id,g.headimg,g.title,g.price,g.label,s.id as sid,s.name,s.shoplogo,s.longitude,s.latitude,GETDISTANCE(s.latitude,s.longitude,'.$lat.','.$lng.') as distance')
                ->order('distance ASC')
                ->select();

            foreach($goods as $key => $val){
                $headimg = explode(',',$val['headimg']);
                $goods[$key]['headimg']  = $this->domain().$headimg[0];
                $goods[$key]['shoplogo'] = $this->domain().$val['shoplogo'];
                //$goods[$k]['collection_num']=$goodsmodel::get($v['id'])->collectiongoods()->count();
                if($val['distance']>1000){
                    $goods[$key]['distance']=round($val['distance']/1000,2)."km";
                }else{
                    $goods[$key]['distance']=round($val['distance'])."m";
                }
                $goods[$key]['label'] = explode(',', $val['label']);
            }
        }
    }

    //统计用户日活量
    public function UserBrisk(){
        if (Request::instance()->isPost()) {
            $ip = input('post.ip');//用户ip地址
            if(empty($ip)){
                $this->json_error("参数为空！");
            }

            //判断当天这个ip是否访问
            $checkIp = Db::name("dailyactivity")
                ->where("date_format(from_unixtime(login_time), '%Y-%m-%d') = date_format(now(), '%Y-%m-%d')")
                ->where("ip",$ip)
                ->find();

            if(empty($checkIp)){
                //如果没有访问 则记录
                $add['login_time'] = time();
                $add['ip'] = $ip;
                $checkAdd = Db::name("dailyactivity")->insert($add);
                if($checkAdd){
                    echo 1;
                }else{
                    echo 2;
                }
            }
        }
    }
    
   
    
}