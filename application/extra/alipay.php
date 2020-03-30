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
        'debug'      => false,
        // 应用ID
        'app_id'     => 'wx85e07af83a0706bf',
        // 微信支付商户号
        'mch_id'     => '1536316121',
        /*
         // 子商户公众账号ID
         'sub_appid'  => '子商户公众账号ID，需要的时候填写',
         // 子商户号
         'sub_mch_id' => '子商户号，需要的时候填写',
        */
        // 微信支付密钥
        'mch_key'    => 'sd0s89s08fes9D9FS890F8SD90DFSDF8',
        // 微信证书 cert 文件
        'ssl_cer'    => ROOT_PATH.'public'.'/cert/apiclient_cert.pem',
        // 微信证书 key 文件
        'ssl_key'    => ROOT_PATH.'public'.'/cert/apiclient_key.pem',
        // 缓存目录配置
        'cache_path' => '',
        // 支付成功通知地址
        'notify_url' => 'http://svn.yanjiegou.com/api/wxpay/notify',
        // 网页支付回跳地址
        'return_url' => 'http://svn.yanjiegou.com/api/wxpay/returnurl',
    ],
    // 支付宝支付参数
    'alipay' => [
        // 沙箱模式
        // 'debug'       => true,
        'debug'       => false,
        // 应用ID
        'app_id'        =>  '2019100968222438',
        // 'app_id'      => '2019060265440176',
        // 支付宝公钥(1行填写)
        // 'public_key'  => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAmjqoop8XrvVoerunKekelURajbeP0IgFswgyjrBZXiAn98IgLlS1Tk525YXJ90s7m6vDeyyfGb/XrRoua/tcy37b+mK32k2Mb0jVausP+3sa1zudO3sSJ+KN3XwwptHQJ00NkqomOGOXv9x1G3cRi89YcRzFlASaYk8+f1+LkajRwiGnk+gEWmyS75l3UxbSAkN10gIEGGzv3RJ+9AoIY15jgUW2ubp5You6rBsapbftXnrcxqZ2oTT7xO/ilWgmgvcbuQew75UgSX26MAIQ91EnGKYtCyt9wWtcUleavNe6qsj4klMdpJDblWzcOI2a4S5dlo9Ihbl/KAE7YSVsCwIDAQAB',
        'public_key'    =>  'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAvxcJ07zYwEKEs8D4XeF73x+xA/YUUAk6QHuUfzWVRic7U6IIBXiqiUsRYSVLSl/JxROd8EReFD+Al+z/429xZiO/ugjG6c3yR9Ay3QcW/jG7+VVE4ECebUvYd9udkRir6hFVyssn9FpgtBXbraaqcBa+aEcZVXT8+2AGPPcrc/e086qfvvJSvyzanZzJDDcyZoOFP158UzzZ19GJdAro1zIviqL1aREDtz0RtJb37K7ct2JuDI/3q0op1CGpbPnzw7YbVX0qkD2yATDhkKDWz/UntrwNmwMHfNDFgzokevXmeKp1EbM+4gfX8TSbyBhWCpiBiS0hoIEGIE2BThmtYQIDAQAB',
        // 支付宝私钥(1行填写)
        // 'private_key' => 'MIIEpAIBAAKCAQEAxGe5JWE7k19MrL4IsLrq2hDcuF8mvoRQXentFV6i2uSWbFsbxgFjwJp6C+UXgKA2jOgqqMizGJyI2Yvo+e44xf0ucPAvkvaA8eQaQ4E+n/aTDPwilg2RpEe0rkGbNk21EL2luotIOgzZK2NPwc+9P3YkMbZMh7DJAQJhQLFm60mIEGm5LVyUCbu84KyvULCq8lX5ME43QMnx2lIrdjWV1OfN4jJDuU3r1KhZ6pEo8c6XjFp+Hyacxfyo+SopLlnEhmpPcBYSH6d05W/9Keg3Lqfza5A4YV3hDLXimK+BRfdNcniBeK3AD+e3mMYdqoE4hTF0Gxd8pv6PR3O13bmnLwIDAQABAoIBAQDBeKNrbQKww5nWOER7Q0WBlka24BRcbB52xK6k9FpcYfzDtGQBgvDuk71R5lRmgmv6FeGf7kRuJBSyqB3RxZbrgeGzowZaMLUIkvhEMxaroMtuaRjw02D6gSA836ezsIyCdy7AOd/mPy3WjbfNZYQ49Xnl9nwg1kbK1btTbO3DKyqdabsYF25OpXIAW90jb7ucyHruwconZvrEn+PeRWzP4yg8mJmb35MBOnUK5swlL9Bz3Zw3LVBZxyfT02wrt1rhq4+7jdqM5mqSawjGMkMvOYsAwVWUF8JBb2NWtRiEzS7PFbjNq2dtgtH5mVxlzF7BtTW//aCFXlJgHlY7P92BAoGBAPQgAw0wLYiKE4hv5sZ9vnm4pG4zSE9iKJhN+FZeA3m8bCYSjg2r9BM0eRZK7QWLpphnxGot12oVqCOGdXkY9u35giJiVtPg+1ZM0WSdOGWXit3memBfQXW3ZI13RJgTiNqG/P65Aozwr5Ub5vzF0ssyY6mxVilTS8J2jK7vibNhAoGBAM31ebY4cu5ymI4BIYle/FDZ/96A1sLaBJiHWhOIkh/RZBerODtVDJKJn5yOBwIQ1A7U111Grc6Y21bj27cvYJtuBb6ctdIk8NmykiG9kYZ6rbnep7DbHBW1AT5kdJTqjihytZa8o2pJC60oNl4tpmwwZFwta3K8sxgycPF2lvSPAoGAHJ90PCOd8xhdWe3k3Pj5UEQ540HYBJa5s8HQkC/NsIRLGdurFCdJIsdQOzDlwXSyP8RK3zgovaN0Z1XoiB5JNXW/sFBfZdBHJ8Mx+d4FMsQl4AaZ6prAjhDGlV+ah0ojDZwuJZ+DkQrXS2BOIO0A3ho2XTsRox7FDzPfItOrDYECgYEAqkigFem8FKvIt/f1a2eOQ6bKJ4PsjHPHQvj9n5LWBdqQ4ATfXCboWyvQPJcs8idJvO17FpK+V0cIamHAIkfYnwmrVDqrFZEXVVaP/beHX2GEy11s1guCv+vEmHpj7U+0s3qL6pISpmi4b7UEpn8lzuN/xrqC0P11MbdarDl5e78CgYBG/VYMn9B5FdKD8qW7gLV6dQK5vziBJ2MfDWbZfJ5bJoiJJT33HE4xJ3JY5KVyJI8HKgvVl1TybzPjgs3ZMfsaP3QnkAMubi0DpDHL4+jQRtllJUPT0g3SJiyind1nY6HW7zCNsPOlZPvpblCnBDf+qXQXm4hNww5xxb1R0c4vnQ==',
        'private_key'   =>  'MIIEpAIBAAKCAQEA7lvw4L+ZZUxvsWadX3I9FjasJe2GeMeMNYXCYWRw0quW3MeL2bBl4y7kvWVX3NwddixrMBHKt70BvtEXzYgsGc4nxGzd40ZxyKtDCBRcWF1N+901qyOpUfI0HMSpQRV4OSbSqC6h7U/Eya/CJoSCijis2OuBABXrc0htwE08bPRRBsu2P4/N2EJAArOL9mJCYW8I+2urG6EMxp2yxqRtUap8VKVHL8cKtfNNCdkxLRYBmRyZxwICWc4T1t3WuEEt1nOv9PEwH4lHzYhiGsxaAj6W8GNJU8HlOvzBYhFZicrz0ACorNa/SlfHb/tZO3CcRfMvzNScVdYjk0eEiTrzvQIDAQABAoIBAQCxLz0/BI51w70fhWUkx1nrglazlv6oF8X9H2JgXXaU1CLAGcG2367Nk1VMCOKodiOcbeZ8BC3KKcD7ZJkqGriVsi7TkA3dXcdFYTHh9qiysyE+QbEcd9Ts6nuciwA6Nkh5S4e6p3eNXget2W4cjdIwB3NNiLsLIkA1ITkcgw2Q+xlOWUxyC1hS/St2VkHmqE9gYHJUQ+ZLmqyu/3FTdz1iPbmcLoT9NszUSdmrT07q+4QZZlKJWqU2Bi8zdSPYtf8ac2aYi4frr+UyIaTg2kIDXukQVzKutOB1fuxsgPbx0cJMGss6/E0eEVtCTjoG9ZL4wJ+fcxyoGNZL5D4cIc5tAoGBAPxK5/TCV+oI3kb7O1pFKgKoSAJK6Z0jfY0vDVO4vb1Ja6G1NWdiRpNFWYq3zIXAjChokzQRFRMPYYdYhhLNJZ8+AvxgIESs6zzT2XWBG0qmqkaALObLuf/jaVHtfJ90dmq71hTAWN3Ozkvm3R1qP9wzQd902wKDn+Vt664D3C37AoGBAPHcnm9gDhihSC7MneV19FyJhKyWFeLRtHZO97ofA16MU0kcjdYf7iqWXROPOfhr7o/tVJIcSJjgaoOVTKespkJsmgqDLMhJVL+93PDX+f4bqO6SJZXFlUVsxETeMNB0TW+BEoepAENo6VGPzhHATbBlZ+rjWbohzek+szMdUc+nAoGBALsJpVEVSyvcCz3AP046/Fwf+dKJSwwOJaQnf8/TpAbSiZLGzqKofv3raeinPl7iUoYakRcGmwMYYgt/G1aQ9BVMWdZURVfkgjkELbEpV9xOFupRV/h6jJgiNhBg6gUkyC10t8+GkdtO2C35J3AJNvK+pVVOQpdokX/7r7/AaNlFAoGAbqH2LwgHKqkLtayPRVjxUCrvb2qv1DMMk1mH47Ev/1288yKGlr3AWeax6LKJV+M3Gsr69mLNqnBtCIeQqtpEqvm2dLyQDYXNqG+W0uxYRC4u1gIwAxSANWONW9svBQtOKIUoDrn1juA8abyYDHKklt2r7TvV3Vh9MgYmPmlY9N0CgYBToV4zCPPNeS67BdEy8lkVSlYtrf4s59KYywXojtNAmD8Pwk6q429yKOFAOuYKcS9nTOAAs7tJHAcuSIu3tWza2CiCQPxoVz7lbKCaWjgXZuADB5F7Dro1p5WBBYJDZlQecYdrsxSVZ0RxcW/GQnBq9UFG0BGBHM8myslvliIuaA==',
        // 支付成功通知地址
        'notify_url'  => 'http://svn.yanjiegou.com/api/alipay/notify',
        // 网页支付回跳地址
        'return_url'  => 'http://svn.yanjiegou.com/api/alipay/returnurl',
        //编码格式
        'charset' => "UTF-8",
        //签名方式
        'sign_type'=>"RSA2",
        //支付宝网关
        'gatewayUrl' => "https://openapi.alipay.com/gateway.do",
    ],
];