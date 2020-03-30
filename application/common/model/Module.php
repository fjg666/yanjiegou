<?php
namespace app\common\model;
use think\Model;
class Module extends Model{
	
	//修改器 - 允许图片格式
	protected function setUploadallowfilesAttr($value){
		return implode(',', $value);
    }
	
	//修改器 - 允许文件格式
	protected function setFileallowfilesAttr($value){
		return implode(',', $value);
    }
	
	//修改器
	protected function setAttributeAttr($value){
        return array_sum($value);
    }
}