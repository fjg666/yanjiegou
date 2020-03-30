<?php
namespace app\api\controller;
use app\admin\controller\Link;
use app\api\model\Goods;
use think\Db;
use think\Request;
use think\Config;
use Yansongda\Pay\Pay;
use Yansongda\Pay\Log;
class Activity extends Base
{

    protected $config = [

        // 支付宝支付参数
        // 沙箱模式
        'debug'       => false,
        // 应用ID
        'app_id'        =>  '2019100968222438',
        'ali_public_key'    =>  'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAvxcJ07zYwEKEs8D4XeF73x+xA/YUUAk6QHuUfzWVRic7U6IIBXiqiUsRYSVLSl/JxROd8EReFD+Al+z/429xZiO/ugjG6c3yR9Ay3QcW/jG7+VVE4ECebUvYd9udkRir6hFVyssn9FpgtBXbraaqcBa+aEcZVXT8+2AGPPcrc/e086qfvvJSvyzanZzJDDcyZoOFP158UzzZ19GJdAro1zIviqL1aREDtz0RtJb37K7ct2JuDI/3q0op1CGpbPnzw7YbVX0qkD2yATDhkKDWz/UntrwNmwMHfNDFgzokevXmeKp1EbM+4gfX8TSbyBhWCpiBiS0hoIEGIE2BThmtYQIDAQAB',
        'private_key'   =>  'MIIEpAIBAAKCAQEA7lvw4L+ZZUxvsWadX3I9FjasJe2GeMeMNYXCYWRw0quW3MeL2bBl4y7kvWVX3NwddixrMBHKt70BvtEXzYgsGc4nxGzd40ZxyKtDCBRcWF1N+901qyOpUfI0HMSpQRV4OSbSqC6h7U/Eya/CJoSCijis2OuBABXrc0htwE08bPRRBsu2P4/N2EJAArOL9mJCYW8I+2urG6EMxp2yxqRtUap8VKVHL8cKtfNNCdkxLRYBmRyZxwICWc4T1t3WuEEt1nOv9PEwH4lHzYhiGsxaAj6W8GNJU8HlOvzBYhFZicrz0ACorNa/SlfHb/tZO3CcRfMvzNScVdYjk0eEiTrzvQIDAQABAoIBAQCxLz0/BI51w70fhWUkx1nrglazlv6oF8X9H2JgXXaU1CLAGcG2367Nk1VMCOKodiOcbeZ8BC3KKcD7ZJkqGriVsi7TkA3dXcdFYTHh9qiysyE+QbEcd9Ts6nuciwA6Nkh5S4e6p3eNXget2W4cjdIwB3NNiLsLIkA1ITkcgw2Q+xlOWUxyC1hS/St2VkHmqE9gYHJUQ+ZLmqyu/3FTdz1iPbmcLoT9NszUSdmrT07q+4QZZlKJWqU2Bi8zdSPYtf8ac2aYi4frr+UyIaTg2kIDXukQVzKutOB1fuxsgPbx0cJMGss6/E0eEVtCTjoG9ZL4wJ+fcxyoGNZL5D4cIc5tAoGBAPxK5/TCV+oI3kb7O1pFKgKoSAJK6Z0jfY0vDVO4vb1Ja6G1NWdiRpNFWYq3zIXAjChokzQRFRMPYYdYhhLNJZ8+AvxgIESs6zzT2XWBG0qmqkaALObLuf/jaVHtfJ90dmq71hTAWN3Ozkvm3R1qP9wzQd902wKDn+Vt664D3C37AoGBAPHcnm9gDhihSC7MneV19FyJhKyWFeLRtHZO97ofA16MU0kcjdYf7iqWXROPOfhr7o/tVJIcSJjgaoOVTKespkJsmgqDLMhJVL+93PDX+f4bqO6SJZXFlUVsxETeMNB0TW+BEoepAENo6VGPzhHATbBlZ+rjWbohzek+szMdUc+nAoGBALsJpVEVSyvcCz3AP046/Fwf+dKJSwwOJaQnf8/TpAbSiZLGzqKofv3raeinPl7iUoYakRcGmwMYYgt/G1aQ9BVMWdZURVfkgjkELbEpV9xOFupRV/h6jJgiNhBg6gUkyC10t8+GkdtO2C35J3AJNvK+pVVOQpdokX/7r7/AaNlFAoGAbqH2LwgHKqkLtayPRVjxUCrvb2qv1DMMk1mH47Ev/1288yKGlr3AWeax6LKJV+M3Gsr69mLNqnBtCIeQqtpEqvm2dLyQDYXNqG+W0uxYRC4u1gIwAxSANWONW9svBQtOKIUoDrn1juA8abyYDHKklt2r7TvV3Vh9MgYmPmlY9N0CgYBToV4zCPPNeS67BdEy8lkVSlYtrf4s59KYywXojtNAmD8Pwk6q429yKOFAOuYKcS9nTOAAs7tJHAcuSIu3tWza2CiCQPxoVz7lbKCaWjgXZuADB5F7Dro1p5WBBYJDZlQecYdrsxSVZ0RxcW/GQnBq9UFG0BGBHM8myslvliIuaA==',
        // 支付成功通知地址
        'notify_url'  => 'http://www.yanjgcom/api/activity/notify',
        // 网页支付回跳地址
        'return_url'  => 'http://www.yanjg.com/api/activity/index',
        //编码格式
        'charset' => "UTF-8",
        //签名方式
        'sign_type'=>"RSA2",
        //支付宝网关
        'gatewayUrl' => "https://openapi.alipay.com/gateway.do",
    ];

    //活动
    public function index()
    {

        if (Request::instance()->isPost()){
            //当前的页码
            $p = empty(input('post.p')) ?1:input('post.p');


            //每页显示的数量
            $rows = empty(input('post.rows'))?10:input('post.rows');
            $goodsmodel = new Goods();

            //check_status--审核状态  -1:违规 0:未审核 1:已审核
            $where = [
                //0否1上架
                'g.status'=>1,
                //是否显示该商品0否1是
                'g.check_status'=>1

            ];

            //活动类型筛选
            $type = empty(input('post.type')) ?1:input('post.type');
            $lat=input('post.lat');//纬度
            $lng=input('post.lng');//经度
            if(empty($lat) &&empty($lng)){
                $this->json_error("获取位置失败！");
                die;
            }
            //isrecommand--商品属性推荐
            //isnew--商品属性新品
            //ishot--商品属性热卖
            //issendfree--商品属性包邮
            //isdiscount--商品属性促销
            switch($type){
                case 1:
                    //推荐
                    $where['isrecommand'] = 1;
                    break;
                case 2:
                //新品
                $where['isnew'] = 1;
                    break;
                case 3:
                    //热卖
                    $where['ishot'] = 1;
                    break;
                case 4:
                    //包邮
                    $where['issendfree'] = 1;
                    break;
                case 5:
                    //促销
                    $where['isdiscount'] = 1;
                    break;
                    
                case 6:
                	// 特价
                	$where['istj'] = 1;
            	case 7:
                	// 清仓
                	$where['isqc'] = 1;
                default:
                    //推荐
                    $where['isrecommand'] = 1;
            }


			
            $goods = $goodsmodel->alias('g')
                ->join('__SHOP__ s','s.id=g.shopid','LEFT')
                ->order('g.sorts asc,g.id desc')
                ->where($where)
                ->field('g.id,g.headimg,g.title,g.price,g.original_price,s.id as sid,s.name,s.shoplogo,GETDISTANCE(s.latitude,s.longitude,'.$lat.','.$lng.') as distance')
                ->page($p,$rows)
                ->select();

            foreach($goods as $k=>$v){
                $headimg = explode(',',$v['headimg']);
                $goods[$k]['headimg'] = $this->domain().$headimg[0];
                $goods[$k]['shoplogo'] = $this->domain().$v['shoplogo'];
                if($v['distance']>1000){
                    $goods[$k]['distance']=round($v['distance']/1000,2)."km";
                }else{
                    $goods[$k]['distance']=round($v['distance'])."m";
                }
            }
            $this->json_success($goods,'请求数据成功');

        }else{
            $this->json_error('请求方式有问题');
            die;
        }
    }


    //团购列表
    public function groupbuy()
    {
        //当前的页码
        $p = empty(input('post.p')) ?1:input('post.p');
        //每页显示的数量
        $rows = empty(input('post.rows'))?10:input('post.rows');

        $where = [
            'gb.is_expire'=>1
        ];
        $data = Db::name('groupbuy')->alias('gb')
            ->join('__SHOP__ s','s.id=gb.shop_id','LEFT')
            ->where($where)
            ->field('gb.*,s.id as sid,s.name,s.shoplogo')
            ->page($p,$rows)
            ->select();
        foreach($data as $k=>$v){
            $data[$k]['shoplogo'] = $this->domain().$v['shoplogo'];
        }
        $this->json_success($data);
    }

    //用户开团
    public function ogroupbuy()
    {
        $user_id = input('post.user_id');
        if(null===$user_id){
            $this->json_error('请传过来用户编号');
        }
        $groupbuy_id = input('post.groupbuy_id');
        if(null===$groupbuy_id){
            $this->json_error('请传过来开团编号');
        }
        $goods_id = input('post.goods_id');
        if(null===$goods_id){
            $this->json_error('请传过来商品编号');
        }
        $groupbubylog = Db::name('groupbubylog')->where(['groupbuy_id'=>$groupbuy_id,'ouid'=>$user_id,'goods_id'=>$goods_id])->find();
        if($groupbubylog!=null){
            $this->json_error('你已经开过团了，不能重复开团');
            die;
        }

        //根据开团编号，查看开团信息
        $groupbuy = Db::name('groupbuy')->where(['id'=>$groupbuy_id])->find();

        if($groupbuy==null){
            $this->json_error('该开团信息不存在');
            die;
        }

        $goods_id = $groupbuy['goods_id'];
        $shop_id = $groupbuy['shop_id'];

        $info = [
            'groupbuy_id'=>$groupbuy_id,
            'ouid'=>$user_id,//开团人用户编号
            'goods_id'=>$goods_id,
            'shop_id'=>$shop_id,
            'jnum'=>1,
            'stime'=>time()
        ];

    }

    //用户参与团购
    public function uigroupbuy()
    {
        $user_id = input('post.user_id');
        if(null===$user_id){
            $this->json_error('请传过来用户编号');
        }
        $groupbuy_id = input('post.groupbuy_id');
        if(null===$groupbuy_id){
            $this->json_error('请传过来团购编号');
        }
        $goods_id = input('post.goods_id');
        if(null===$user_id){
            $this->json_error('请传过来商品编号');
        }

        //查看是否有团购商品信息
        $groupbuy = Db::name('groupbuy')->where(['id'=>$groupbuy_id,'goods_id'=>$goods_id])->find();
        if($groupbuy==null){
            $this->json_error('不能参与该团购商品');
            die;
        }else{
            if($groupbuy['is_expire']==0){
                $this->json_error('该团购商品已经失效');
                die;
            }
            if($groupbuy['gnum']==0){
                $this->json_error('团购名额已经没有了');
                die;
            }
        }

    }

    // 物流实时查询
    public function getExpress() { 
        $user_id = input('post.user_id');
        if(null===$user_id){
            $this->json_error('请传过来用户编号');
        }

        $com = input('post.expresscom');
        $num = input('post.expresssn');
        // $com = 'zhongtong';
        // $num = '73124309026125';
        $key = Config::get('kuaidi')['key'];                      //客户授权key
        $customer = Config::get('kuaidi')['cus'];                 //查询公司编号
        $param = array (
            'com' => $com,           //快递公司编码
            'num' => $num,   //快递单号
            'phone' => '',              //手机号
            'from' => '',               //出发地城市
            'to' => '',                 //目的地城市
            'resultv2' => '1'           //开启行政区域解析
        );
    
        //请求参数
        $post_data = array();
        $post_data["customer"] = $customer;
        $post_data["param"] = json_encode($param);
        $sign = md5($post_data["param"].$key.$post_data["customer"]);
        $post_data["sign"] = strtoupper($sign);
        
        $url = 'http://poll.kuaidi100.com/poll/query.do';   //实时查询请求地址
        
        $params = "";
        foreach ($post_data as $k=>$v) {
            $params .= "$k=".urlencode($v)."&";     //默认UTF-8编码格式
        }
        $post_data = substr($params, 0, -1);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        $data = str_replace("\"", '"', $result );
       
        // $data['name'] = config('system.express_company')[$com]['statusname'];
        $data = json_decode($data,true);
        $data['name'] = config('system.express_company')[$com]['statusname'];
        
        $data = json_encode($data);
        $data = json_decode($data);
      	$this->json_success($data);
    }
  	
  	// 关于我们
    public function about()
    {
        $data = Db::name('system')->field('logo,tel,name')->find();
        $data['logo'] ='http://'.$_SERVER['HTTP_HOST'].$data['logo'];
      
        $this->json_success($data);
    }

    public function fund()
    {
        $aaa = 0.01;
        
    }   
}