<?php
namespace app\admin\controller;
use think\Db;
use think\Env;
use app\admin\controller\Common;
class Index extends Common
{
    public function _initialize(){
        parent::_initialize();
    }
    public function index(){
        $map=[];
        //导航
        // 获取缓存数据
        $authRule = cache('authRule');
        if(!$authRule){
            if(ADID!=1){
              
               $map['id']=array('in',$this->adminRules);
            }
            $map['menustatus']=1;
            $authRule = Db::name('authRule')->where($map)->order('sort asc')->column('*','id');
            cache('authRule', $authRule, 3600);
        }
        $this->assign('menus',genTree9($authRule));
        return $this->fetch();
    }
    public function main(){
        $data=[];
        $t = time();
        $start = mktime(0,0,0,date("m",$t),date("d",$t),date("Y",$t));
        $end = mktime(23,59,59,date("m",$t),date("d",$t),date("Y",$t));
        $data['tody']['users'] = Db::name('users')->where('reg_time','between',[$start,$end])->count();
        $data['tody']['order'] = Db::name('order')->where('add_time','between',[$start,$end])->count();
        $data['tody']['shop2']  = Db::name('shop')->where(['addtime'=>['between',[$start,$end]],'status'=>2])->count();
        $data['tody']['bshop']  = Db::name('bigshop')->where(['addtime'=>['between',[$start,$end]]])->count();
        $data['tody']['shop1']  = Db::name('shop')->where(['addtime'=>['between',[$start,$end]],'status'=>1])->count();
        $data['tody']['goods1']  = Db::name('goods')->where(['createtime'=>['between',[$start,$end]],'check_status'=>1,'status'=>1])->count();
        $data['tody']['goods0']  = Db::name('goods')->where(['createtime'=>['between',[$start,$end]],'check_status'=>0])->count();
        
        $data['count']['users'] = Db::name('users')->count();
        $data['count']['order'] = Db::name('order')->count();
        $data['count']['order_money'] = Db::name('order')->sum('money');
        $data['count']['shop2']  = Db::name('shop')->where(['status'=>2])->count();
        $data['count']['bshop']  = Db::name('bigshop')->count();
        $data['count']['goods1']  = Db::name('goods')->where(['check_status'=>1,'status'=>1])->count();
        
        $startDate=date('Y-m-d',strtotime("-1month"));
        $endDate=date('Y-m-d');  
        $start = date('Y-m-d 00:00:00',strtotime($startDate));
        $end = date('Y-m-d 23:59:59',strtotime($endDate));
        $rs = Db::name('order')->field('add_time,sum(money) sum,count(id) total')
                ->whereTime('add_time','between',[$start,$end])
                //->where()
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
        $result['url'] = url('admin/index/index');
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
        session('seadmininfo',null);
        $this->redirect('login/index');
    }


    public function test(){
        $rowdata = [
            'moduleid'      => 123, 
            'attachmark'    => get_attachmark(),    //上传标示
        ];
        $this->assign('rowdata',$rowdata);
        return $this->fetch();
    }
    
}
