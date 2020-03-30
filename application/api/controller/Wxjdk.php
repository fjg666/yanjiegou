<?php

namespace app\api\controller;

use app\api\model\Ad;
use app\api\model\Bigshop;
use app\api\model\Goods;
use think\Db;
use think\Request;
use geo\Geohash;
use app\index\controller\Wechat;
class Wxjdk extends Base
{
    public function share()
    { 
        $url =input("reqUrl");
        $data=Wechat::getSignPackage($url);
        $this->json_success($data,'请求数据成功');
    }
}
