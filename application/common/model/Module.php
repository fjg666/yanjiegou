<?php
namespace app\common\model;
use think\Model;
class Module extends Model{
	
	//�޸��� - ����ͼƬ��ʽ
	protected function setUploadallowfilesAttr($value){
		return implode(',', $value);
    }
	
	//�޸��� - �����ļ���ʽ
	protected function setFileallowfilesAttr($value){
		return implode(',', $value);
    }
	
	//�޸���
	protected function setAttributeAttr($value){
        return array_sum($value);
    }
}