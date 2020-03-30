<?php
namespace app\admin\controller;
use think\Controller;
use app\admin\model\Admin;
use think\captcha\Captcha;
use think\request;
class Login extends Controller
{
    private $system,$code;
    public function _initialize(){
        if (session('aid')) {
            $this->redirect('admin/index/index');
        }
        $this->system = cache('System');
        if(empty($this->system)){
            savecache('System');
        }
        $this->assign('system',cache('System')['code']);
    }
    public function index(){
        if(request()->isPost()) {
            $data = input('post.');
            $admin = new Admin();
            $code=cache('System')['code'];
            
            return $admin->login($data,$code);
        }else{
            return $this->fetch();
        }
    }
    public function verify(){
        $config =    [
            // 验证码字体大小
            'fontSize'    =>    25,
            // 验证码位数
            'length'      =>    4,
            // 关闭验证码杂点
            'useNoise'    =>    false,
            'bg'          =>    [255,255,255],
        ];
        $captcha = new Captcha($config);
        return $captcha->entry();
    }
}