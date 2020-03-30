<?php
/**
 +----------------------------------------------------------------------
 * 文件上传控制器
 +---------------------------------------------------------------------- 
 */
namespace app\upload\controller;
set_time_limit(0); 
class IndexController extends BaseController{
	
	//判断操作
	public function indexOp(){
		$action = input('action');
		switch($action){
			case 'config':
				return $this->config();
				break;
			default:
				$action = $action . 'Op';
				if( method_exists($this, $action) ){
					return $this->$action();
				}else{
					$this->message = '请求地址出错';
					return $this->outdata();
				}
		}
		
	}
	
	//获取配置信息--适配百度编辑器
	public function config(){
		$moduleInfo = $this->moduleInfo;
		//允许上传的图片类型
		if( $moduleInfo['allowextimage'] ){
			$allowextimage = array_map([$this, 'formatSuffix'], explode(',',$moduleInfo['allowextimage']));
		}else{
			$allowextimage = [];
		}
		//允许上传的图片类型
		if( $moduleInfo['allowextfile'] ){
			$allowextfile = array_map([$this, 'formatSuffix'], explode(',',$moduleInfo['allowextfile']));
		}else{
			$allowextfile = [];
		}
		$module_config= [
			//图片
			'imageMaxSize'			=> $moduleInfo['uploadmaxsize'] * 1000,
			'imageAllowFiles'		=> $allowextimage,
			'imageCompressBorder'	=> $moduleInfo['compresswidth'],
			'imageInsertAlign'		=> 'center',
			'imagePathFormat'		=> $moduleInfo['uploadformatimage'],
			//涂鸦图片			
			'scrawlMaxSize'			=> $moduleInfo['uploadmaxsize'] * 1000,
			'scrawlInsertAlign'		=> 'center',
			'scrawlPathFormat'		=> $moduleInfo['uploadformatimage'],
			//截图工具上传
			'snapscreenPathFormat'	=> $moduleInfo['uploadformatimage'],
			'snapscreenInsertAlign'	=> 'center',
			//抓取远程图片配置
			'catcherMaxSize'		=> $moduleInfo['uploadmaxsize'] * 1000,
			'catcherPathFormat'		=> $moduleInfo['uploadformatimage'],
			'catcherAllowFiles'		=> $allowextimage,
			//视频上传
			'videoMaxSize'			=> $moduleInfo['filemaxsize'] * 1000,
			'videoPathFormat'		=> $moduleInfo['filepathformat'],
			//上传文件
			'fileMaxSize'			=> $moduleInfo['filemaxsize'] * 1000,
			'filePathFormat'		=> $moduleInfo['filepathformat'],
			'fileAllowFiles'		=> $allowextfile,
			//列出图片
			'imageManagerInsertAlign'		=> 'center',
		];
		//配置文件中的配置
		$config = config('ueditor');
		//合并配置
		$config = array_merge($config,$module_config);
		return json($config);
	}
	
	//给允许上传的后缀名加点
	public function formatSuffix($ext){
		return $ext ? '.' . $ext : '';
	}
	
	//上传文件
	public function uploadfileOp(){
		//获取上传文件信息
		$file = request()->file($this->name);
		if( !is_object($file) ){
			$this->message = '上传文件无法创建对象';
			return $this->outdata();
		}
		//验证规则
		$validate = [
			'size'	=> $this->moduleInfo['filemaxsize'] * 1024,	//文件大小(字节)
			'ext'	=> $this->moduleInfo['fileallowfiles'],		//允许的文件类型
		];
		//文件名保存路径
		$savepath = $this->getFilePath($this->moduleInfo['filepathformat'], $this->moduleInfo);
		$uploadpath = UPLOADS_PATH;
		//移动文件
		$uploadInfo = $file->validate($validate)->move($uploadpath,$savepath);
		if( false === $uploadInfo ){
			$this->message = $file->getError();
			return $this->outdata();
		}else{
			//获取上传文件原始信息
			$attachurl = $uploadInfo->getSaveName();
			$this->uploadInfo = [
				'attachurl'		=> $attachurl,
				'attachthumb'	=> '',
				'title'			=> $file->getInfo('name'),
				'original'		=> $file->getInfo('name'),
				'filetype'		=> 1,	//文件
				'filesize'		=> $file->getInfo('size'),
			];
			//保存文件信息到数据库
			$result = $this->attachSave();
			if( false == $result ){
				$this->message = '文件保存失败';
				return $this->outdata();
			}
			//成功信息
			$this->message = 'success';
			return $this->outdata();
		}
	}
	
	//上传图片
	public function uploadimageOp(){
		//获取上传文件信息
		$file = request()->file($this->name);
		if( !is_object($file) ){
			$this->message = '上传文件无法创建对象';
			return $this->outdata();
		}
		//验证规则
		$validate = [
			'size'	=> $this->moduleInfo['uploadmaxsize'] * 1024,	//文件大小(字节)
			'ext'	=> $this->moduleInfo['uploadallowfiles'],		//允许的图片类型
		];
		//d($validate);
		//文件名保存路径
		$savepath = $this->getFilePath($this->moduleInfo['uploadformatimage'], $this->moduleInfo);
		$uploadpath = UPLOADS_PATH;
		//移动文件
		$uploadInfo = $file->validate($validate)->move($uploadpath,$savepath);
		if( false === $uploadInfo ){
			$this->message = $file->getError();
			return $this->outdata();
		}else{
			//获取上传文件原始信息
			$attachurl = $uploadInfo->getSaveName();
			$this->uploadInfo = [
				'attachurl'		=> $attachurl,
				'attachthumb'	=> '',
				'title'			=> $file->getInfo('name'),
				'original'		=> $file->getInfo('name'),
				'filetype'		=> 2,	//图片
				'filesize'		=> $file->getInfo('size'),
			];
			//允许最大宽度
			$compresswidth = $this->moduleInfo['compresswidth'];
			//允许最大高度
			$compressheight = $this->moduleInfo['compressheight'];
			//图片完整路径
			$fullpath = UPLOADS_PATH . $attachurl;
			//创建一个图片实例
			$image = \think\Image::open( $fullpath );
			$width = $image->width();	//图片宽度
			$height = $image->height();	//图片高度
			//压缩缩放图片
			if($width > $compresswidth || $height > $compressheight){
				$image->thumb($compresswidth,$compressheight)->save($fullpath);
			}
			//生成缩略图
			if($this->moduleInfo['thumb'] > 0){
				//缩略图文件地址
				$attachthumb = $this->getThumbPath($attachurl);
				//创建一个对象用于专门处理缩略图
				$thumb = \think\Image::open( $fullpath );
				$thumb->thumb($this->moduleInfo['thumbwidth'], $this->moduleInfo['thumbheight'], $this->moduleInfo['thumb'])->save(UPLOADS_PATH . $attachthumb);
				$this->uploadInfo['attachthumb'] = $attachthumb;
			}
			//加水印
			if($this->moduleInfo['markopen'] > 0){
				$markpos = $this->moduleInfo['markopen'] == 10 ? mt_rand(1,9) : $this->moduleInfo['markopen'];
				//水印文件路径
				$waterpath = UPLOADS_PATH . $this->moduleInfo['markfile'];
				//水印透明度
				$markalpha = $this->moduleInfo['markalpha'];
				//图片添加印水印
				$image->water($waterpath, $markpos, $markalpha)->save($fullpath);
			}
			
			//保存文件信息到数据库
			$result = $this->attachSave();
			if( false == $result ){
				$this->message = '文件保存失败';
				return $this->outdata();
			}
			//成功信息
			$this->message = 'success';
			return $this->outdata();
		}
	}
	
	//列出图片
	public function listimageOp(){
		$where = [];
		//文件类型--图片
		$where['attachtype'] = 2;
		//上传标示
		$attachmark = input('attachmark');
		$attachmark && $where['attachmark'] = $attachmark;
		
		$attachs = model('attachs');
		$rslist = $attachs->where($where)->order('attachid desc')->paginate(20)->toArray();
		$data = [];
		foreach($rslist['data'] as $row){
			$data[] = [
				'url'		=> $row['attachurl'],
				'mtime'		=> $row['postdate'],
				'original'	=> $row['attachdesc'],
			];
		}
		return json([
			'state' 	=> 'SUCCESS',
			'list' 		=> $data,
			'start' 	=> 0,
			'total' 	=> $rslist['total'],
		]);
	}
	
	//列出文件
	public function listfileOp(){
		$where = [];
		//文件类型--文件
		$where['attachtype'] = 1;
		//上传标示
		$attachmark = input('attachmark');
		$attachmark && $where['attachmark'] = $attachmark;
		
		$attachs = model('attachs');
		$rslist = $attachs->where($where)->order('attachid desc')->paginate(20)->toArray();
		$data = [];
		foreach($rslist['data'] as $row){
			$data[] = [
				'url'		=> $row['attachurl'],
				'mtime'		=> $row['postdate'],
				'original'	=> $row['attachdesc'],
			];
		}
		return json([
			'state' 	=> 'SUCCESS',
			'list' 		=> $data,
			'start' 	=> 0,
			'total' 	=> $rslist['total'],
		]);
	}
	
}


