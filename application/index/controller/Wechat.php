<?php

namespace app\index\controller;

use think\Request;
use think\Db;
use think\Controller;
use think\Cookie;
use think\Session;
use think\Cache as cache;

class Wechat extends Controller
{
    //获取票据
    public static function getAccessToken()
    {
        $appid = config("wchat.appid");
        $appsecret = config("wchat.appsecret");
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $appid . "&secret=" . $appsecret;
        if (cache::get('access_token')) {
            $accesstoken = cache::get('access_token');
        } else {
            $data = self::httpUtil($url);
            $data = json_decode($data, true); //获取票据
            $accesstoken = $data['access_token'];
            cache::set('access_token', $accesstoken, 7200);
        }
        return $accesstoken;
    }


    public static function httpUtil($url, $data = '', $method = 'GET')
    {
        try {
            $curl = curl_init(); // 启动一个CURL会话
            curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
            curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
            curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
            if ($method == 'POST') {
                curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
                if ($data != '') {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
                }
            }
            curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
            curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
            $tmpInfo = curl_exec($curl); // 执行操作
            curl_close($curl); // 关闭CURL会话
            return $tmpInfo; // 返回数据
        } catch (Exception $e) { }
    }

    //添加消息模板
    public static function sendMes($data)
    {
        $accesstoken = self::getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=" . $accesstoken;
        return self::httpUtil($url, $data, "POST");
    }
    //组装消息参数
    public static function templateMessageSend($openid, $templateId, $url, $data, $remark)
    {
        $arr = array(
            'touser' => $openid,
            'template_id' => $templateId
        );
        if ($url && !empty($url)) {
            $arr['url'] = $url;
        }
        $arr['topcolor'] = "#FF0000";
        $keyword = array();
        foreach ($data as $k => $v) {
            if ($k == 0) {
                $keyword['first'] = array(
                    'value' => $v['value'],
                    "color" => $v['color']

                );
            } else {
                $keyword['keyword' . $k] = array(
                    'value' => $v['value'],
                    "color" => $v['color']
                );
            }
        }
        if (!empty($remark)) {
            $keyword['remark'] = $remark;
        }
        $arr['data'] = $keyword;
        return json_encode($arr);
    }
    //验证身份证
    public  static function idCard($url)
    {
        $url = urlencode($url);
        $AccessToken = self::getAccessToken();
        $ocrUrl = "https://api.weixin.qq.com/cv/ocr/idcard?img_url=" . $url . "&access_token=" . $AccessToken;
        $data = self::httpUtil($ocrUrl);
        return $data;
    }
    //获取字符串
    public static function createNonceStr($length = 16)
    {

        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    public static  function getJsApiTicket()
    {
        if (cache::get('ticket_token')) {
            $ticket = cache::get('ticket_token');
        } else {
            $accessToken = self::getAccessToken();
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
            $res = json_decode(self::httpUtil($url), true);
            $data = self::httpUtil($url);
            $data = json_decode($data, true); //获取票据
            $ticket = $data['ticket'];
            cache::set('ticket_token', $ticket, 7000);
        }
        return $ticket;
    }
    public static function getSignPackage($url="")
    {
        $jsapiTicket = self::getJsApiTicket();
        // 注意 URL 一定要动态获取，不能 hardcode. 
        if(empty($url)){
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        }       
        $timestamp = time();
        $nonceStr = self::createNonceStr();
        // 这里参数的顺序要按照 key 值 ASCII 码升序排序 
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
        $signature = sha1($string);
        $signPackage = array(
            "appId"     =>  config("wchat.appid"),
            "nonceStr"  => $nonceStr,
            "timestamp" => $timestamp,
            "url"       => $url,
            "signature" => $signature,
            "rawString" => $string
        );
        return $signPackage;
    }
}
