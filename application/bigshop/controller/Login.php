<?php
namespace app\bigshop\controller;
use think\Controller;
use think\captcha\Captcha;
use think\request;
class Login extends Controller
{
    private $system,$code;
    public function _initialize(){
        if (session('bsinfo.shid')) {
            $this->redirect('bigshop/index/index');
        }
    }
    public function index(){
        if(request()->isPost()) {
            $data = input('post.');
            return model('BigshopAdmin')->login($data);
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