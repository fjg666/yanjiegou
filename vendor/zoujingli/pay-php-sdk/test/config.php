<?php

// +----------------------------------------------------------------------
// | pay-php-sdk
// +----------------------------------------------------------------------
// | 版权所有 2014~2017 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zoujingli/pay-php-sdk
// +----------------------------------------------------------------------

return [
    // 微信支付参数
    'wechat' => [
        // 沙箱模式
        'debug'      => true,
        // 应用ID
        'app_id'     => 'wxe335431b79068046',
        // 微信支付商户号
        'mch_id'     => '1300513101',
        /*
         // 子商户公众账号ID
         'sub_appid'  => '子商户公众账号ID，需要的时候填写',
         // 子商户号
         'sub_mch_id' => '子商户号，需要的时候填写',
        */
        // 微信支付密钥
        'mch_key'    => 'AGNq9Z6I9xQ7usWT2xPXc76pS9HUvcoq',
        // 微信证书 cert 文件
        'ssl_cer'    => __DIR__ . '/cert/1300513101_cert.pem',
        // 微信证书 key 文件
        'ssl_key'    => __DIR__ . '/cert/1300513101_key.pem',
        // 缓存目录配置
        'cache_path' => '',
        // 支付成功通知地址
        'notify_url' => '',
        // 网页支付回跳地址
        'return_url' => '',
    ],
    // 支付宝支付参数
    'alipay' => [
        // 沙箱模式
        'debug'       => true,
        // 应用ID
        'app_id'      => '2016092900621940',
        // 支付宝公钥(1行填写)
        'public_key'  => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAs67dXUph6VqjE34bIJkOGnncjyJFpd8i4knZ47RIphHqqjOkpRKhwHIvVFH+buqjsakw9cZrOb/I7u8Lp1CJsnUVsThktLttp2L4yKKagb8jzegTzYoVjbPPy0EBS/R2cQ+6+7V5BOWEpq8QwFavf8E4Pvwxob4yiS0Fn+FQG322nDAZ9flIDZiji2DtF31lYdPUEVVfXTv2JiRSUocsbH1vYN4frTrrGBvT2cNN9ODqBIz0vnyZlgqxHc9LiS94cl3eTLl/nYTHKhYExmdKZ+j6MHncMyXiiquZ4k8HG74N2Bc7zYbQ/d5jO4w7//eWyibajpVRo0Nc8rdpWi2GNwIDAQAB',
        // 支付宝私钥(1行填写)
        'private_key' => 'MIIEowIBAAKCAQEAs67dXUph6VqjE34bIJkOGnncjyJFpd8i4knZ47RIphHqqjOkpRKhwHIvVFH+buqjsakw9cZrOb/I7u8Lp1CJsnUVsThktLttp2L4yKKagb8jzegTzYoVjbPPy0EBS/R2cQ+6+7V5BOWEpq8QwFavf8E4Pvwxob4yiS0Fn+FQG322nDAZ9flIDZiji2DtF31lYdPUEVVfXTv2JiRSUocsbH1vYN4frTrrGBvT2cNN9ODqBIz0vnyZlgqxHc9LiS94cl3eTLl/nYTHKhYExmdKZ+j6MHncMyXiiquZ4k8HG74N2Bc7zYbQ/d5jO4w7//eWyibajpVRo0Nc8rdpWi2GNwIDAQABAoIBAEcLBIs2NbBGHeQ/IAqreWAOfp45NsB2kRxXhsb5KSBARUA2WwrJaxrkCsKUCL1iGIbOFoWWhh63LYMLENh+h3L/yCvh2C99S8W65BKv99cE8+sdr3a8+fik96utcA3QAmSBi7Sp88dz2BbvcPgbThh9FPgSTq1cvi5ulqK4OywixqnOWXWnzfPbykMfzS33Pr4i8VGtveVHDYkkpo+134rOmID/wFDzwmdVDgvcQpUosUtqquw4+JSrhml7QJDocLMgnEhFQZ0PBCsvoORwwH/jXY6Fok23IOGGPAV1cvsSBJMVnWw6MtGkUofcq+PQNF3GgFJKjJOY2ZLYMU/gMQECgYEA35OLS0XQhhG3290Oxq+qAKI406pOe1C9r6pSA9gjKP4rPpgJNM1UWENwnimc8QUFSnJSeF0G1Od/Iugw4EmcaRmnHl4xlE/LyGjcZSbx1GCOmT7/YAwz3irODhy5ADmVsXxT6T87VBrNL+ZyMG6yARoy3P54TiLPmmTSjmMpUNECgYEAzb2/GCcY1fGqQ2JYoMbIwZA+7dy4zoVe3rTdS6sxCsZOriQJnzrArorGWGBMyk9NSx6O/hdI/G0P6V5Oad0VsRl4tjOYBic5u94xrfelyGDxSPn7n3LOduRrND3djQDuvxqddXjM9Z3/YENTi/9UXOE+YgLEbLfNpxeO+vlfaIcCgYEApOf7/xpQkwLwnQ/w/SXGe88roRvl5VkJgE9dTQ6X6H2IwhN7/uFQTAX6Q/6njrLIUz/9il/7UQArK0EeA+DnqDHOrTyGz0EIKxsFLOrLxn0t0OTBkkON4lmqQIh5ACx5OD9e2RTVmtRl8eXE5epQCzYavBxJ+j/85oFlOLb1W3ECgYBGUuI7njmF/2mkSLxkkC3Uw7pO0ZA1vy6zTv0JPUWxGiGQsm67h3iO8I5lbN0ylbKkx5g2z7y504mJyhthYhllBIDXPoFrMQMx7PvsK+b1u/UdbZ0NXk+mIeNm4vKUnMn+dll16smb9tpwi/LFuouah78r/ygmxRi9UXck4BI1hwKBgH6cBxIetv7gKk4rIvPS62E0acvcBjYD/J2nDbMv9QFjiC0rQOXETCKb3LozrsrLE0ufedXCrs7mo4+4I+WF81yd0W+BT08CQmGOYX0GpiztD/xm94ikKnJ+svrPwufYbVC64aGAT148Tv3tj5K5Tvo+kgJRca+VyK2xLCEgO0kd',
        // 支付成功通知地址
        'notify_url'  => 'http://www.yanjiegou.com/api/alipay/notify',
        // 网页支付回跳地址
        'return_url'  => 'http://www.yanjiegou.com/api/alipay/returnurl',
        //编码格式
        'charset' => "UTF-8",
        //签名方式
        'sign_type'=>"RSA2",
        //支付宝网关
        'gatewayUrl' => "https://openapi.alipay.com/gateway.do",
    ],
];