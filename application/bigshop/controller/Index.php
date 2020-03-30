<?php
namespace app\bigshop\controller;
use think\Db;
use think\Env;
use app\bigshop\controller\Common;
class Index extends Common
{
    public function _initialize(){
        parent::_initialize();
    }
    public function index(){
        return $this->fetch();
    }
    public function main(){
        $shop=Db::name('shop');
        $data=[];
        $t = time();
        $start = mktime(0,0,0,date("m",$t),date("d",$t),date("Y",$t));
        $end = mktime(23,59,59,date("m",$t),date("d",$t),date("Y",$t));
        $data['goods']['g1']  = Db::name('goods')->where(['bigshopid'=>SHID,'check_status'=>1,'status'=>1])->count();
        $data['shop']['s0']  = $shop->where(['bshopid'=>SHID,'is_lock'=>0])->count();
        $data['shop']['s1']  = $shop->where(['bshopid'=>SHID,'is_lock'=>1])->count();
        
        $list=$shop->where(['bshopid'=>SHID])->column('id');
        $sids=implode(',',$list);
        $data['order']['or1'] = Db::name('order')->where(['shop_id'=>['in',$sids],'status'=>1])->count();
        $data['order']['or2'] = Db::name('order')->where(['shop_id'=>['in',$sids],'status'=>2])->count();
        $data['order']['or3'] = Db::name('order')->where(['shop_id'=>['in',$sids],'status'=>3])->count();
        $data['order']['or4'] = Db::name('order')->where(['shop_id'=>['in',$sids],'status'=>4])->count();
        $data['order']['or7'] = Db::name('order')->where(['shop_id'=>['in',$sids],'status'=>7])->count();
        $data['order']['or8'] = Db::name('order')->where(['shop_id'=>['in',$sids],'status'=>8])->count();
        
        $startDate=date('Y-m-d',strtotime("-1month"));
        $endDate=date('Y-m-d');  
        $start = date('Y-m-d 00:00:00',strtotime($startDate));
        $end = date('Y-m-d 23:59:59',strtotime($endDate));
        $rs = Db::name('order')->field('add_time,sum(money) sum,count(id) total')
                ->whereTime('add_time','between',[$start,$end])
                ->where(['shop_id'=>['in',$sids],])
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
        session('bsinfo',null);
        $this->redirect('login/index');
    }
    
    public function test(){
        $list=Db::name('shop_auth_rule')->where('type=1')->select();
        $str='';
        foreach ($list as $k => $v) {
            $str.=','.$v['id'];
        }
        echo $str;
    }
}
