<?php
namespace app\api\controller;
use app\api\model\Area;
use app\api\model\Users;
use think\Controller;
use think\Request;
class Base extends Controller
{

    public function __construct(){
        header("Content-type: application/json");
        header("Access-Control-Allow-Origin:  *");
        header("Access-Control-Allow-Methods: GET, OPTIONS, POST");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding, X-Requested-With, Origin");
        header('content-type:application/json;charset=utf8');
    }


    /**
     * json失败返回
     * @param string $msg
     * @param array $data
     * @param int $code
     */
    public function json_error($msg = '失败', $code = 0)
    {
        exit(json_encode(['code' => $code, 'msg' => $msg], JSON_UNESCAPED_UNICODE));
    }

    /**
     * json成功返回
     * @param array $data
     * @param string $msg
     * @param int $code
     */
    public function json_success($data = [], $msg = '成功', $code = 200)
    {
        if (is_string($data)) {
            $msg  = $data;
            $data = [];
        }
        exit(json_encode(['code' => $code, 'msg' => $msg, 'data' => $data], JSON_UNESCAPED_UNICODE));
    }

    /**
     * json成功加密返回
     * @param array $data
     * @param string $msg
     * @param int $code
     */
    public function encrypt_success($data = [], $msg = '成功', $code = 200)
    {
        if (is_string($data)) {
            $msg  = $data;
            $data = [];
        }
        $json = json_encode(['code' => $code, 'msg' => $msg, 'data' => $data]);
        exit(base64_encode($json));
    }

    //获取当前域名
    function domain()
    {
        $request = Request::instance();
        $domain=$request->domain();
        return $domain;
    }

    public  static function provice()
    {
        $provice = Area::where('parent_id',0)->select();
        return $provice;
    }

    //获取子地区
    public  static function getchildareamy($parent_id,$putype) {
        $where['parent_id'] = $parent_id;
        if ($putype == 1) {
            $where['is_open'] = 1;
        }
        $area = Area::where($where)->field('id, area_name, is_reg, is_open')->select();
        if ($area) {
            $option = '';
            foreach ($area as $key => $value) {
                $option .= '<option value="'.$value['area_name'].'" title="'.$value['id'].'">'.$value['area_name'].'</option>';
            }
            echo json_encode(array('error' => 0, 'option' => $option));
        } else {
            echo json_encode(array('error' => 1));
        }
    }


    public  static function getchildarea($parent_id,$putype)
    {
        $where['parent_id'] = $parent_id;
        if ($putype == 1) {
            $where['is_open'] = 1;
        }
        $area = Area::where($where)->field('id, area_name, is_reg, is_open')->select();
        if ($area) {
            return $area;
        } else {
            return false;
        }
    }

    /*
    * 生成随机字符串
    * @param int       $length  要生成的随机字符串长度
    * @param string    $type    随机码类型：0，数字+大小写字母；1，数字；2，小写字母；3，大写字母；4，特殊字符；-1，数字+大小写字母+特殊字符
    * @return string
    */
    protected function randCode($length = 5, $type = 0)
    {
        $arr = array(1 => "0123456789", 2 => "abcdefghijklmnopqrstuvwxyz", 3 => "ABCDEFGHIJKLMNOPQRSTUVWXYZ", 4 => "~@#$%^&*(){}[]|");
        if ($type == 0) {
            array_pop($arr);
            $string = implode("", $arr);
        } elseif ($type == "-1") {
            $string = implode("", $arr);
        } else {
            $string = $arr[$type];
        }
        $count = strlen($string) - 1;
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $string[rand(0, $count)];
        }
        return $code;
    }

    /**
     *功能：计算两个时间戳之间相差的日时分秒
     *$begin_time  开始时间戳
     *$end_time    结束时间戳
     */
    protected function timediff($begin_time,$end_time){
        if($begin_time < $end_time){
            $starttime = $begin_time;
            $endtime = $end_time;
        }else{
            $starttime = $end_time;
            $endtime = $begin_time;
        }

        //计算天数
        $timediff = $endtime-$starttime;
        $days = intval($timediff/86400);
        //计算小时数
        $remain = $timediff%86400;
        $hours  = intval($remain/3600);
        //计算分钟数
        $remain = $remain%3600;
        $mins = intval($remain/60);
        //计算秒数
        $secs = $remain%60;
        $res  = array("day" => $days,"hour" => $hours,"min" => $mins,"sec" => $secs);
        return $res;
    }

    //通用缩略图上传接口
    public function upload()
    {
        if($this->request->isPost()){
            $file = $this->request->file('file');
            if(null===$file){
                $this->json_error('请选择要上传的图片');
                die;
            }
            $info = $file->move(ROOT_PATH . 'public' .'/' . 'uploads');
            //halt( $info);
            if($info){
                $res['name'] = $info->getFilename();
                $path = 'uploads'.'/' .$info->getSaveName();
                $res['filepath'] =str_replace('\\', '/', $path);
                $this->json_success($res,'上传成功！');
            }else{
                $this->json_error('上传失败！'.$file->getError());
            }
        }
    }



    function base64imgupload(){
        $base64 = input('post.file');
        $ary = $this -> base64imgsave($base64);
        $this->json_success($ary);
    }
    
    function base64video(){
    	// var_dump($_FILES);exit;
    		if (!array_key_exists('file',input()) || empty(input())) {
	            $this->json_error('值为空');
	        }
	    	$base64 = input('post.file');
	        $ary = $this -> base64audiosave($base64, ['mp3','wav']);
	        $this->json_success($ary);
    	
    	
    }
    
    
    function videoUpload(){
        // print_r($_FILES);exit;
        if ($_FILES['file']['type'] != 'audio/mp3') {
            $this->json_error('图片格式不正确！');
        }
        if ($_FILES['file']['size'] > 2097152) {
            $this->json_error('音频过大！');
        }




        $ymd = date("Ymd"); //图片路径地址
        $basedir = ROOT_PATH . 'public' .'/' . 'uploads/'.$ymd.'';
        $fullpath = $basedir;



        if(!is_dir($fullpath)){
            mkdir($fullpath,0777,true);
        }
        

        $video = '/'.md5(date('YmdHis').rand(1000, 9999)).'.mp3';


        $file = move_uploaded_file($_FILES['file']['tmp_name'], $fullpath.$video);
        
        if ($file) {
            $path = '/uploads'.'/'.$ymd.'' .$video;
            $res['filepath'] =str_replace('\\', '/', $path);
            $this->json_success($res,'上传成功！');
        }
        
    }

    

    //base64上传的图片储存到服务器本地
    protected function base64imgsave($img, $types=[]){

        $ymd = date("Ymd"); //图片路径地址

        $basedir = ROOT_PATH . 'public' .'/' . 'uploads/'.$ymd.'';
        $fullpath = $basedir;
        if(!is_dir($fullpath)){
            mkdir($fullpath,0777,true);
        }
        $types = empty($types)? array('jpg', 'gif', 'png', 'jpeg'):$types;

        $img = str_replace(array('_','-'), array('/','+'), $img);

        $b64img = substr($img, 0,100);

        if(preg_match('/^(data:\s*image\/(\w+);base64,)/', $b64img, $matches)){

            $type = $matches[2];
            if(!in_array($type, $types)){
                $this->json_error('图片格式不正确，只支持 jpg、gif、png、jpeg哦！');
                die;
            }
            $img = str_replace($matches[1], '', $img);
            $img = base64_decode($img);
            $photo = '/'.md5(date('YmdHis').rand(1000, 9999)).'.'.$type;
            file_put_contents($fullpath.$photo, $img);


            $path = '/uploads'.'/'.$ymd.'' .$photo;
            $res['filepath'] =str_replace('\\', '/', $path);
            $this->json_success($res,'上传成功！');
        }else{
        	var_dump($matches);exit;
        }

    }
    
    
    
    
    
    
    protected function base64audiosave($img, $types = []){

        $ymd = date("Ymd"); //图片路径地址

        $basedir = ROOT_PATH . 'public' .'/' . 'uploads/'.$ymd.'';
        $fullpath = $basedir;
        if(!is_dir($fullpath)){
            mkdir($fullpath,0777,true);
        }
        $types = empty($types)? array('jpg', 'gif', 'png', 'jpeg'):$types;

        $img = str_replace(array('_','-'), array('/','+'), $img);

        $b64img = substr($img, 0,100);

        if(preg_match('/^(data:\s*audio\/(\w+);base64,)/', $b64img, $matches)){

            $type = $matches[2];
            if(!in_array($type, $types)){
                $this->json_error('语音格式不正确！');
                die;
            }
            $img = str_replace($matches[1], '', $img);
            $img = base64_decode($img);
            $photo = '/'.md5(date('YmdHis').rand(1000, 9999)).'.'.$type;
            file_put_contents($fullpath.$photo, $img);


            $path = '/uploads'.'/'.$ymd.'' .$photo;
            $res['filepath'] =str_replace('\\', '/', $path);
            $this->json_success($res,'上传成功！');
        }

    }






}