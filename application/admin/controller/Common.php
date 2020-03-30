<?php
namespace app\admin\controller;
use think\Db;
use think\Controller;
class Common extends Controller
{
    protected $cacheModel=['System','GoodsCategory','Category'];
    public function _initialize()
    {
        //判断管理员是否登录
        define('ADID', session('seadmininfo.aid'));
        if (!ADID) {
            $this->redirect('admin/login/index');
        }
        define('MODULE_NAME',request()->controller());
        define('ACTION_NAME',request()->action());
        
        //权限管理
        //当前操作权限ID
        if(ADID!=1){
            $this->HrefId = Db::name('auth_rule')->where('href',MODULE_NAME.'/'.ACTION_NAME)->value('id');
            //当前管理员权限
            $map['a.admin_id'] = ADID;
            $rules=Db::name('admin')->alias('a')
                ->join('auth_group ag','a.group_id = ag.group_id','left')
                ->where($map)
                ->value('ag.rules');
            $this->adminRules = explode(',',$rules);
            if($this->HrefId){
                if(!in_array($this->HrefId,$this->adminRules)){
                    $this->error('您无此操作权限');
                }
            }
        }
        foreach($this->cacheModel as $r){
            if (!cache($r)) {
               savecache($r);
            }
        }
        $this->assign('attachmark',get_attachmark());
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
        return $result;    
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
    
    // 设置某个属性
    public function setvalue(){
        $id=input('post.id');
        $k=input('post.k');
        $v=input('post.v');
        $info=Db::name(MODULE_NAME)->where('id='.$id)->update([$k=>$v]);
        if($info){
            return $this->resultmsg('设置成功',1);
        }
        return $this->resultmsg('设置失败',0);
    }
}