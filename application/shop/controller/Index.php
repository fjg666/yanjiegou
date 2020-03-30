<?php
namespace app\shop\controller;
use think\Db;
use think\Env;
use app\shop\controller\Common;
class Index extends Common
{
    public function _initialize(){
        parent::_initialize();
    }
    public function index(){
        return $this->fetch();
    }
   public function main(){
        $data=[];
        $t = time();
        $start = mktime(0,0,0,date("m",$t),date("d",$t),date("Y",$t));
        $end = mktime(23,59,59,date("m",$t),date("d",$t),date("Y",$t));
        $data['goods']['g1']  = Db::name('goods')->where(['id'=>SHID,'check_status'=>1,'status'=>1])->count();
        $data['goods']['g2']  = Db::name('goods')->where(['id'=>SHID,'check_status'=>0])->count();
        $data['goods']['g3']  = Db::name('goods')->where(['id'=>SHID,'check_status'=>1,'status'=>0])->count();
        $data['goods']['g4']  = Db::name('goods')->where(['id'=>SHID,'check_status'=>-1])->count();
        
        $data['order']['or1'] = Db::name('order')->where(['shop_id'=>SHID,'status'=>1])->count();
        $data['order']['or2'] = Db::name('order')->where(['shop_id'=>SHID,'status'=>2])->count();
        $data['order']['or3'] = Db::name('order')->where(['shop_id'=>SHID,'status'=>3])->count();
        $data['order']['or4'] = Db::name('order')->where(['shop_id'=>SHID,'status'=>4])->count();
        $data['order']['or7'] = Db::name('order')->alias('a')->join('users u','u.id = a.user_id','RIGHT')->where(['a.shop_id'=>SHID,'a.status'=>7])->count();
        $data['order']['or8'] = Db::name('order')->where(['shop_id'=>SHID,'status'=>8])->count();
        
        
        
        $total = 10;
        $where_sku_goods = Db::name('GoodsSttrxsku')->where('number','<',$total)->group('goods_id')->column('goods_id');
        $where_goods = Db::name('Goods')->where('total','<',$total)->column('id');
        if (!empty($where_goods) && !empty($where_sku_goods)) {
            $where_all = array_unique(array_merge($where_goods, $where_sku_goods));
        }else{
            $where_all = empty($where_sku_goods)?$where_goods:$where_sku_goods;
        }
        $map['id']=['in',$where_all];
        $map['shopid']=SHID;

        $data['goods']['g5'] = Db::name('goods')->where($map)->count();
        
        
        
        
        
        
        
        
        
        $startDate=date('Y-m-d',strtotime("-1month"));
        $endDate=date('Y-m-d');  
        $start = date('Y-m-d 00:00:00',strtotime($startDate));
        $end = date('Y-m-d 23:59:59',strtotime($endDate));
        $rs = Db::name('order')->field('add_time,sum(money) sum,count(id) total')
                ->whereTime('add_time','between',[$start,$end])
                ->where(['shop_id'=>SHID])
                ->order('add_time asc')
                ->group("date_format(from_unixtime(add_time),'%Y-%m-%d')")->select();
        if (!empty($rs)) {
            $top=$bottom=[];        
            foreach ($rs as $k => $v) {
                $top[]=date('Ymd',$v['add_time']);
                $bottom[1][]=$v['sum'];
                $bottom[2][]=$v['total'];
            }
        } else {
            $top[]='';
            $bottom[1]=[0];
            $bottom[2]=[0];
        }
/*---chen*/
        $pt_find = Db::name('order')
                ->field('id,freight,money')
                ->where('send_type',1)
                ->where('shop_id',SHID)
                ->whereTime('paytime', 'm')
                ->whereIn('status',[2,3,4,5])
                ->select();
        $pt['o_num'] = count($pt_find);
        $pt['money'] = empty($pt_find)?0:array_sum(array_column($pt_find,'freight'));
        $pt['o_money'] = empty($pt_find)?0:array_sum(array_column($pt_find,'money'));
        $this->assign('pt',$pt);        
/*---chen*/
        
        
        
        
        $this->assign('top',implode(',',$top));
        $this->assign('bottom1',implode(',',$bottom[1]));
        $this->assign('bottom2',implode(',',$bottom[2]));
        $this->assign('data',$data);
        return $this->fetch();
    }
    public function navbar(){
        return $this->fetch();
    }
    public function nav(){
        return $this->fetch();
    }


    public function clear(){
        if ($this->_deleteDir(RUNTIME_PATH)) {
            $result['info'] = '清除缓存成功!';
            $result['status'] = 1;
        } else {
            $result['info'] = '清除缓存失败!';
            $result['status'] = 0;
        }
        $result['url'] = url('index');
        return $result;
    }
    private function _deleteDir($R)
    {
        $handle = opendir($R);
        while (($item = readdir($handle)) !== false) {
            if ($item != '.' and $item != '..') {
                if (is_dir($R . '/' . $item)) {
                    $this->_deleteDir($R . '/' . $item);
                } else {
                    if (!unlink($R . '/' . $item))
                        die('error!');
                }
            }
        }
        closedir($handle);
        return rmdir($R);
    }

    //退出登陆
    public function logout(){
        session('sinfo',null);
        $this->redirect('shop/login/index');
    }
    
    public function test(){
        $list=Db::name('shop_auth_rule')->where('type=2')->select();
        foreach ($list as $k => $v) {
            $v['type']=1;
            unset($v['id']);
            Db::name('shop_auth_rule')->insert($v);
        }
    }
}
