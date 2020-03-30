<?php
namespace app\api\controller;
//商圈
use think\Db;

class Bigshop extends Base
{
    public function index()
    {
        $bshop_id = input('post.bshop_id');
        if(null===$bshop_id){
            $this->json_error('请传过来商圈编号');
        }

        $lat=input('post.lat');//纬度
        $lng=input('post.lng');//经度
        if(empty($lat) &&empty($lng)){
            $this->json_error("获取位置失败！");
            die;
        }
        $floor_id = empty(input('post.floor_id')) ?1:input('post.floor_id');

        $bshop = db('bigshop')->where('id',$bshop_id)->field('id,name,bigshoplogo,intro,province,city,area,address,GETDISTANCE(latitude,longitude,'.$lat.','.$lng.') as distance')->find();
        if($bshop['distance']>1000){
            $bshop['distance']=round($bshop['distance']/1000,2)."km";
        }else{
            $bshop['distance']=round($bshop['distance'])."m";
        }

        $bshop['bigshoplogo'] = $this->domain().$bshop['bigshoplogo'];


        //获取当前商圈的楼层信息
        $floors = Db::name('floor')->where(['bs_id'=>$bshop_id])->select();




        $data = [];

        //商家信息
        $data['bshop'] = $bshop;

        $data['floors'] = $floors;


        //每页显示的数量
        $type = empty(input('post.type'))?1:input('post.type');
        //当前的页码
        $p = empty(input('post.p')) ?1:input('post.p');
        //每页显示的数量
        $rows = empty(input('post.rows'))?30:input('post.rows');


        $shops = db('shop')->alias('s')
            ->join('bigshop bs','bs.id=s.bshopid','left')
            ->order('s.id','desc')
            ->field('s.id as sid,s.name as sname,s.bshopid,s.shoplogo,s.intro,s.province,s.city,s.area,s.floor_id,s.address')
            ->where(['s.bshopid'=>$bshop_id,'s.floor_id'=>$floor_id])
            ->page($p,$rows)
            ->select();

        foreach($shops as $sk=>$sv){
            $shops[$sk]['shoplogo'] = $this->domain().$sv['shoplogo'];
        }

        $data['shops'] = $shops;





        $this->json_success($data);
    }
    
}