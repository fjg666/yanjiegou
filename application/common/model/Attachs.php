<?php
/*** 附件
 */
namespace app\common\model;
use think\Model;
class Attachs extends Model{
	protected $insert = ['uid','postdate'];
	//设置管理员编号自动完成--数据完成
	protected function setUidAttr(){
        return get_rulerid();
    }
	//设置管理员编号自动完成--数据完成
	protected function setPostdateAttr(){
        return time();
    }
	//关联管理员
	public function ruler(){
        return $this->hasOne('ruler','rulerid','uid')->field('rulername');
    }
	//关联模型
	public function module(){
        return $this->hasOne('module','moduleid','moduleid')->field('modulename');
    }
	//删除文件
	public function deleteFile($filename){
		$attachurl = ROOT_PATH .'public'. $filename;
		//判断图片文件是否存在，如果存在，就删除
		if( is_file($attachurl) && file_exists($attachurl) ){
			unlink($attachurl);
		}
		return true;
	}
	//按上传标示删除文件
	public function deleteByAttachmark($attachmark){
		$rslist = $this->where('attachmark',$attachmark)->select();
		$attachIds = [];
		foreach($rslist as $row){
			//删除文件
			$this->deleteFile($row['attachurl']);
			$attachIds[] = $row['attachid'];
		}
		if( count($attachIds) > 0 ){
			$this->where('attachid','in',$attachIds)->delete();		
		}
		return true;
	}
	//删除信息
	public function dels($attachid){
		$rowdata = $this->where('attachid',$attachid)->find();
		if($rowdata){
			//删除文件
			$this->deleteFile($rowdata['attachurl']);
			$this->where('attachid',$attachid)->delete();
			return true;
		}else{
			return false;
		}
	}
}