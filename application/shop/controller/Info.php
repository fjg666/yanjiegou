<?php
namespace app\shop\Controller;
use app\api\controller\Base;
use app\api\model\Area;
use think\Db;
use think\Request;
use app\shop\controller\Common;
class Info extends Common{
    protected  $model;
    public function _initialize(){
        parent::_initialize();
        $this->model=Db::name('shop');
        $this->assign('logomoduleid',111);
        $this->assign('albummoduleid',112);
    }
    /*
     * 商家列表
     */
    public function index(){
        $keyword=input('key');
        $page =input('page')?input('page'):1;
        $pageSize =input('limit')?input('limit'):config('pageSize');
        $map=[];
        if(!empty($keyword) ){
            $map['name']=array('like','%'.$keyword.'%');
        }
        $map['a.id']=(input('sid/d')) ? input('sid/d') : SHID ;
        $info = Db::name('shop')->alias('a')
              ->join('bigshop b','b.id=a.bshopid','left')
              ->field('a.*,b.name as bsname')
              ->where($map)
              ->find();
        $info['imgs']= explode(',',$info['headimg']);    
        $info['yyzz']=explode(',',$info['yyzz']);  
        $info['myaddress'] = $info['province'].$info['city'].$info['area'].$info['street'].$info['address'];
        $this->assign('info',$info);
        return $this->fetch();
    }
    
    //获取子地区
    public function getchildarea(){
        $parent_id = request()->post('parent_id');
        $putype = request()->post('putype');
        Base::getchildareamy($parent_id,$putype);
    }
   
    public function edit(){
        if(Request::instance()->isAjax()){
            $data = input('post.');
            /*---chen*/
            if (array_key_exists('tag',$data)) {
                $data['tag'] = json_encode($data['tag']);
            }else{
                $data['tag'] = '';
            }
            /*---chen*/
            unset($data['upfile']);
            $count = count($data['headimg']);//获取传过来有几张图片
            if($count){
                $data['headimg'] = implode(',',$data['headimg']);
            }           
            if(!empty($data['yyzz'])){
                $data['yyzz']=implode(',',$data['yyzz']);
            }else{
                $data['yyzz']="";
            }
            $data['shortname']=GetShortName($data['name']);
            $msg = $this->validate($data,'Shop');
            if($msg!='true'){
                return $result = ['code'=>0,'msg'=>$msg];
            }
            $res = $this->model->update($data);
            if($res){
                $result['code'] = 1;
                $result['msg'] = '修改商家成功!';
                return $result;
            }else{
                $result['code'] = 0;
                $result['msg'] = '修改商家失败!';
                return $result;
            }
        }else{
            $info=$this->model->where(array('id'=>SHID))->find();
            $headimg = explode(',',$info['headimg']);
            foreach($headimg as $k=>$v){
                if(!is_object($v)){
                    $info['src'][] = $v;
                }
            }
              
            if(!empty($info['yyzz'])){
                $info['yyzz'] = explode(',',$info['yyzz']);
            }
            if(!empty($info['identity_photo'])){
                $info['identity_photo'] = explode(',',$info['identity_photo']);
            }
            if (!empty($info['tag'])) {
                $info['tag'] = json_decode($info['tag'],true);
            }else{
                $info['tag'] = [];
            }
            $this->assign('info',$info);
            $arealist = Area::where('parent_id',0)->select();
            $this->assign('arealist',$arealist);
            $bshop=Db::name('bigshop')->field('id,name')->select();
            $this->assign('bshop',$bshop);
            $shop_category=Db::name('shop_category')->select();
            $this->assign('shopcategory',$shop_category);
            return $this->fetch();
        }
    }

    /**
     * 邮费设置
     **/
    public function postage(){
        $model=model('ShopPostage');
        $info=$model->where(['shop_id'=>SHID])->find();



        if (request()->isPost()) {
            $data = input('post.');
            $money=json_encode($data['yf']);
            if (empty($info)) {
                $model->insert(['shop_id'=>SHID,'money'=>$money]);
            } else {
                $model->where(['shop_id'=>SHID])->update(['money'=>$money]);
            }

            cache('arealist', NULL);
            $result['code'] = 1;
            $result['msg'] = '操作成功!';
            return $result;
        }


        $arealist=cache('arealist');
        if (!$arealist) {
            $arealist=Db::name('area')->where('level=1')->select();
            foreach ($arealist as $key => $val) {
                $arealist[$key]['son']=Db::name('area')->where('level=2 and parent_id ='.$val['id'])->select();
                

                // --chen
                $info_arr = json_decode($info['money'],true);
                $arealist[$key]['yf_val_money'] = empty($info)?'0':(array_key_exists($val['id'], $info_arr)?$info_arr[$val['id']]:'0');;
                foreach ($arealist[$key]['son'] as $k => $vals) {
                    $arealist[$key]['yfs_val_money'] = empty($info)?'0':(array_key_exists($vals['id'], $info_arr)?$info_arr[$vals['id']]:'0');
                }
                // --chen
            }
            cache('arealist', $arealist, 86400);
        }
        $this->assign('info',empty($info)?'':json_decode($info['money'],true));
        $this->assign('arealist',$arealist);  
        return $this->fetch();
    }   
}