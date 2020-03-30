<?php
namespace app\bigshop\controller;
use app\api\controller\Base;
use think\Db;
use think\Request;
use app\bigshop\controller\Common;
class Info extends Common{
    protected  $model;
    public function _initialize(){
        parent::_initialize();
        $this->model=Db::name('bigshop');
        $this->assign('logomoduleid',113);
        $this->assign('albummoduleid',114);
    }

    public function edit(){
        if(Request::instance()->isAjax()){
            $data = input('post.');
            unset($data['file']);
            $count = count($data['headimg']);//获取传过来有几张图片
            if($count){
                $data['headimg'] = implode(',',$data['headimg']);
            }
            $msg = $this->validate($data,'Bigshop');
            if($msg!='true'){
                return $result = ['code'=>0,'msg'=>$msg];
            }
            $res = $this->model->update($data);
            if($res){
                $result['code'] = 1;
                $result['msg'] = '修改商圈成功!';
                $result['url'] = url('index');
                return $result;
            }else{
                $result['code'] = 0;
                $result['msg'] = '修改商圈失败!';
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
            $this->assign('info',$info);
            $arealist = Base::provice();
            $this->assign('arealist',$arealist);
            return $this->fetch();
        }

    }

    public function index()
    {
        $info=$this->model->where(array('id'=>SHID))->find();
        $info['headimg'] = explode(',',$info['headimg']);
        $info['address']=$info['province'].$info['city'].$info['area'].$info['street'].$info['address'];
        $this->assign('info',$info);
        $arealist = Base::provice();
        $this->assign('arealist',$arealist);
        return $this->fetch();
    }
   
}