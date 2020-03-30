<?php
/**
 +----------------------------------------------------------------------
 * 上传配置
 +---------------------------------------------------------------------- 
 */
return [
	
	//模型信息
	'moduleInfo'	=> [
		'uploadcatalog'		=> 'images',
		'uploadformatimage'	=> '/uploads/{uploadcatalog}/{yyyy}{mm}/{yyyy}{mm}{dd}{hh}{ii}{ss}{rand:6}',
		'allowmaxsizeimage'	=> 2048,				//允许图片最大体积（单位KB）			
		'allowextimage'		=> 'jpg,jpeg,png',		//允许图片格式
		'allowmaxwidth'		=> 900,					//允许图片最大宽度
		'allowmaxheight'	=> 1200,				//允许图片最大高度
		'quality'			=> 90,					//图片质量
		'markopen'			=> 0,					//水印位置
		'markfile'			=> '',					//水印文件
		'markalpha'			=> 100,					//水印透明度
		'thumb'				=> 3,					//是否生成缩略图
		'thumbwidth'		=> 200,					//缩略图宽度
		'thumbheight'		=> 150,					//缩略图高度
		'uploadformatfile'	=> '/uploads/file/{yyyy}{mm}/{yyyy}{mm}{dd}{hh}{ii}{ss}{rand:6}',
		'allowmaxsizefile'	=> 2048,				//允许文件最大体积（单位KB）
		'allowextfile'		=> 'txt,doc,docx,excel,pdf,ppt',		//允许图片最大宽度
	],
	
	//文件上传验证规则
	'validate'		=> [
		//允许上传的文件大小
		'size'		=> 2048000,		
		//允许上传的文件后缀，多个用逗号分割或者数组
		'ext'		=> ['jpg','jpeg','png','gif'],
		//允许上传的文件MIME类型，多个用逗号分割或者数组
		'type'		=> ['jpg','jpeg','png','gif'],
	],
	
	
];