<?php
namespace app\bigshop\controller;
use think\Db;
use think\Controller;
class Common extends Controller
{
    public function _initialize()
    {
        //判断管理员是否登录
        define('URID', session('bsinfo.ursid'));
        if (!URID) {
            $this->redirect('bigshop/login/index');
        }
        define('SHID', session('bsinfo.shid'));
        define('GRID', session('bsinfo.grid'));
        define('SUPER',session('bsinfo.iswho'));
        define('SHNAME', session('bsinfo.shname'));
        define('SHORTNAME', session('bsinfo.shortname'));
        define('MODULE_NAME',strtolower(request()->controller()));
        define('ACTION_NAME',strtolower(request()->action()));
        //当前操作权限ID
        if(SUPER!=1){
            $urls = Db::name('BigshopAuthRule')->where(['href'=>MODULE_NAME.'/'.ACTION_NAME,'type'=>1])->value('id');
            if($urls){
                if(!in_array($urls,session('bsinfo.rules'))){
                    $this->error('您无此操作权限');
                }
            }
        }
        $this->getmenu();
        $attachmark    = get_attachmark();    //上传标示
        $this->assign('attachmark',$attachmark);
    }
    //空操作
    public function _empty(){
        return $this->error('空操作，返回上次访问页面中...',url('index/main'));
    }

    public function resultmsg($msg='操作成功',$code=1,$url=null,$data=''){
        $result['msg'] = $msg;
        $result['url'] = $url;
        $result['code'] = $code;
        $result['data']=$data;
        return $result;die;    
    }

    /**
      * 获取唯一编号
      *
      * @return void
      * @author 
      **/
     public function getUniqueCode(){
        $code=new Code(); 
        $card_no = $code->encodeID(mt_rand(00000000,99999999),5); 
        //d($card_no);  
        $card_pre = '755';   
        $card_vc = substr(md5($card_pre.$card_no),0,2);   
        $card_vc = strtoupper($card_vc);   
        return $card_pre.$card_no.$card_vc;   
        
     }


    //通用缩略图上传接口
    public function upload()
    {
        if($this->request->isPost()){
            $res['code']=1;
            $res['msg'] = '上传成功！';
            $file = $this->request->file('file');
            $info = $file->move(ROOT_PATH . 'public' .'/' . 'uploads');
            //halt( $info);
            if($info){
                $res['name'] = $info->getFilename();
                $path = 'uploads'.'/' .$info->getSaveName();
                $res['filepath'] =str_replace('\\', '/', $path);
            }else{
                $res['code'] = 0;
                $res['msg'] = '上传失败！'.$file->getError();
            }
            return $res;
        }
    }

    public function getmenu(){
         // 获取缓存数据
        $bsrule = cache('bsrule');
        if(!$bsrule){
            $bsrule = Db::name('BigshopAuthRule')->where(['menustatus'=>1,'type'=>1])->order('sort asc')->select();
            cache('bsrule', $bsrule, 3600);
        }
        $bsrules = explode(',',session('bsinfo.rules'));
        $menus = array();
        foreach ($bsrule as $key=>$val){
            $bsrule[$key]['href'] = url($val['href']);
            if($val['pid']==0){
                if(SUPER!=1){
                    if(in_array($val['id'],$bsrules)){
                        $menus[] = $val;
                    }
                }else{
                    $menus[] = $val;
                }    
            }
        }
        foreach ($menus as $k=>$v){
            foreach ($bsrule as $kk=>$vv){
                if($v['id']==$vv['pid']){
                    if(SUPER!=1) {
                        if (in_array($vv['id'], $bsrules)) {
                            $menus[$k]['children'][] = $vv;
                        }
                    }else{
                        $menus[$k]['children'][] = $vv;
                    }
                }
            }
        }
        $this->assign('menus',$menus);
    }
}