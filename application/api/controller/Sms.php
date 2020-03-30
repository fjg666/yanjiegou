<?php
namespace app\api\controller;
use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
class Sms extends Base
{
    /**

     * 验证码(阿里云短信)

     */

    public static function smsVerify($mobile, $code, $tempId)

    {

        AlibabaCloud::accessKeyClient(config('sms.appkey'), config('sms.secretKey'))

            ->regionId('cn-hangzhou') //replace regionId as you need（这个地方是发短信的节点，默认即可，或者换成你想要的）

            ->asGlobalClient();

        $data = [];

        try {

            $result = AlibabaCloud::rpcRequest()

                ->product('Dysmsapi')

                //->scheme('https') //https | http（如果域名是https，这里记得开启）

                ->version('2017-05-25')

                ->action('SendSms')

                ->method('POST')

                ->options([

                    'query'                 => [

                        'PhoneNumbers'      => $mobile,

                        'SignName'          => config('sms.signName'),

                        'TemplateCode'      => $tempId,

                        'TemplateParam'     => json_encode(['code'=>$code]),

                    ],

                ])

                ->request();

            $res    = $result->toArray();

            if($res['Code'] == 'OK'){

                $data['status'] = 1;

                $data['info']   = $res['Message'];

            }else{

                $data['status'] = 0;

                $data['info']   = $res['Message'];

            }

            return $data;

        } catch (ClientException $e) {

            $data['status'] = 0;

            $data['info']   = $e->getErrorMessage();

            return $data;

        } catch (ServerException $e) {

            $data['status'] = 0;

            $data['info']   = $e->getErrorMessage();

            return $data;

        }

    }
    public static function smsVerifyTwo($mobile, $order, $tempId)

    {

        AlibabaCloud::accessKeyClient(config('sms.appkey'), config('sms.secretKey'))

            ->regionId('cn-hangzhou') //replace regionId as you need（这个地方是发短信的节点，默认即可，或者换成你想要的）

            ->asGlobalClient();

        $data = [];

        try {

            $result = AlibabaCloud::rpcRequest()

                ->product('Dysmsapi')

                //->scheme('https') //https | http（如果域名是https，这里记得开启）

                ->version('2017-05-25')

                ->action('SendSms')

                ->method('POST')

                ->options([

                    'query'                 => [

                        'PhoneNumbers'      => $mobile,

                        'SignName'          => config('sms.signName2'),

                        'TemplateCode'      => $tempId,

                        'TemplateParam'     => json_encode(['order'=>$order]),

                    ],

                ])

                ->request();

            $res    = $result->toArray();

            if($res['Code'] == 'OK'){

                $data['status'] = 1;

                $data['info']   = $res['Message'];

            }else{

                $data['status'] = 0;

                $data['info']   = $res['Message'];

            }

            return $data;

        } catch (ClientException $e) {

            $data['status'] = 0;

            $data['info']   = $e->getErrorMessage();

            return $data;

        } catch (ServerException $e) {

            $data['status'] = 0;

            $data['info']   = $e->getErrorMessage();

            return $data;

        }

    }
}