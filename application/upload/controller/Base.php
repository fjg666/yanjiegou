<?php

namespace app\upload\controller;
use think\Controller;
//header('Access-Control-Allow-Origin: http://www.baidu.com'); //设置http://www.baidu.com允许跨域访问
//header('Access-Control-Allow-Headers: X-Requested-With,X_Requested_With'); //设置允许的跨域header
//定义上传的文件路径
define('UPLOADS_PATH',ROOT_PATH . 'public');

class Base extends Controller{
	//上传模型编号
	protected $moduleid;
	//上传模型信息
	protected $moduleInfo;
	//上传标示
	protected $attachmark;
	//上传者身份：ruler(管理员),member(会员)
	protected $identity = 'ruler';
	//上传者方式：common(通用),ueditor(百度编辑器)
	protected $uploadway = 'common';
	//上传的文件域名称
	protected $name = 'upfile';
	//上传文件信息
	protected $file;
	//上传后的文件信息
	protected $uploadInfo = [];
	//提示信息
	protected $message;
	//返回信息
	protected $result = [];
		
	//初始化
	public function _initialize(){
		//判断上传者身份
		// $identity = input('identity');
		// if( $identity ){
		// 	$this->identity = $identity;
		// }
		// if( $this->identity == 'ruler' ){
		// 	$uid = get_ruleruid();
		// }elseif( $this->identity == 'member' ){
		// 	$uid = get_memberid();
		// 	if (!$uid) {
		// 		$uid=input('uid/d');
		// 	}
		// }else{
		// 	$uid = 0;
		// }
		// if( !$uid ){
		// 	$this->message = '上传者身份错误';
		// 	return $this->outdata();
		// }
		//上传标示
		$attachmark = input('attachmark');
		if( !preg_match('/^\d{18}$/', $attachmark) ){
			$attachmark = get_attachmark();
		}
		$this->attachmark = $attachmark;
		//上传的文件域名称
		if( !empty($name) ){
			$this->name = $name;
		}
		//判断上传模型
		$module = model('module');
		$moduleid = input('moduleid/d');
		$moduleInfo = $module->where('moduleid',$moduleid)->find();
		if( empty($moduleInfo) ){
			$this->message = '上传模型错误';
			return $this->outdata();
		}else{
			$moduleInfo = $moduleInfo->toArray();
			$this->moduleInfo = config('upload.moduleInfo');
			$this->moduleInfo = array_merge($this->moduleInfo, $moduleInfo);
			$this->moduleid = $moduleid;
		}
    }
	
	/**
     +----------------------------------------------------------------------
	 * 跟据原图片路径生成一个缩略图路径
     +----------------------------------------------------------------------
	 */
	public function getThumbPath($attachurl){
		list($pathname,$ext) = explode('.', $attachurl);
		return $pathname . '_thumb' . '.' . $ext;
	}
	
	/**
     +----------------------------------------------------------------------
	 * 生成文件名称文件路径
     +----------------------------------------------------------------------
	 */
	public function getFilePath($format,$params){
		//替换日期事件
		$t = time();
		$d = explode('-', date("Y-y-m-d-H-i-s"));
		if( is_array($params) ){
			foreach($params as $key=>$value){
				$format = str_replace('{'. $key .'}', $value, $format);
			}
		}
		$format = str_replace("{yyyy}", $d[0], $format);
		$format = str_replace("{yy}", $d[1], $format);
		$format = str_replace("{mm}", $d[2], $format);
		$format = str_replace("{dd}", $d[3], $format);
		$format = str_replace("{hh}", $d[4], $format);
		$format = str_replace("{ii}", $d[5], $format);
		$format = str_replace("{ss}", $d[6], $format);
		$format = str_replace("{time}", $t, $format);
		//替换随机字符串
		$randNum = mt_rand(1, 99999999) . mt_rand(1, 99999999);
		if( preg_match("/\{rand\:([\d]*)\}/i", $format, $matches) ){
			$format = preg_replace("/\{rand\:[\d]*\}/i", substr($randNum, 0, $matches[1]), $format);
		}
		//$arrayPath = explode('/',$format);
		return $format;
	}
	
	//保存附件信息
	protected function attachSave(){
		//保存图片信息到数据库
		$data = [
			'attachtype'	=> $this->uploadInfo['filetype'],
			'attachmark'	=> $this->attachmark,
			'attachurl'		=> $this->uploadInfo['attachurl'],
			'attachthumb'	=> $this->uploadInfo['attachthumb'],
			'attachdesc'	=> $this->uploadInfo['original'],	//获取文件原来的名字
			'attachsize'	=> $this->uploadInfo['filesize'],	//文件大小
			'moduleid'		=> $this->moduleid,
		];
		$attachs = model('attachs');
		$result = $attachs->allowField(true)->save($data);
		if(false === $result){
			return false;
		}else{
			return true;
		}
	}
	
	//输出结果
	protected function outdata(){
		//文件上传方式
		$uploadway = input('uploadway');
		if( $uploadway ){
			$this->uploadway = $uploadway;
		}
		//针对百度编辑器，有固定的返回格式
		if( 'ueditor' == $this->uploadway ){
			$result = $this->ueditordata();
		}else{
			if( 'success' == $this->message ){
				$result = [
					'status'		=> 'success',
					'message'		=> '上传成功',
					'uploadInfo'	=> [
						'attachurl'		=> $this->uploadInfo['attachurl'],
						'attachthumb'	=> $this->uploadInfo['attachthumb'],
					],
				];
			}else{
				$result = [
					'status'		=> 'error',
					'message'		=> $this->message,
				];
			}
		}
		return json($result);
	}
	
	//百度编辑器返回结果
	protected function ueditordata(){
		if( 'success' == $this->message ){
			$result = [
				'state'		=> 'SUCCESS',
				'url'		=> $this->uploadInfo['attachurl'],
				'title'		=> $this->uploadInfo['title'],
				'original'	=> $this->uploadInfo['original'],
				'type'		=> $this->uploadInfo['filetype'],
				'size'		=> $this->uploadInfo['filesize'],
			];
			return $result;
		}else{
			return ['state'=>$this->message];
		}
	}
	
}
