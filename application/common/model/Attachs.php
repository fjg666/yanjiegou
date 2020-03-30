<?php
/*** ����
 */
namespace app\common\model;
use think\Model;
class Attachs extends Model{
	protected $insert = ['uid','postdate'];
	//���ù���Ա����Զ����--�������
	protected function setUidAttr(){
        return get_rulerid();
    }
	//���ù���Ա����Զ����--�������
	protected function setPostdateAttr(){
        return time();
    }
	//��������Ա
	public function ruler(){
        return $this->hasOne('ruler','rulerid','uid')->field('rulername');
    }
	//����ģ��
	public function module(){
        return $this->hasOne('module','moduleid','moduleid')->field('modulename');
    }
	//ɾ���ļ�
	public function deleteFile($filename){
		$attachurl = ROOT_PATH .'public'. $filename;
		//�ж�ͼƬ�ļ��Ƿ���ڣ�������ڣ���ɾ��
		if( is_file($attachurl) && file_exists($attachurl) ){
			unlink($attachurl);
		}
		return true;
	}
	//���ϴ���ʾɾ���ļ�
	public function deleteByAttachmark($attachmark){
		$rslist = $this->where('attachmark',$attachmark)->select();
		$attachIds = [];
		foreach($rslist as $row){
			//ɾ���ļ�
			$this->deleteFile($row['attachurl']);
			$attachIds[] = $row['attachid'];
		}
		if( count($attachIds) > 0 ){
			$this->where('attachid','in',$attachIds)->delete();		
		}
		return true;
	}
	//ɾ����Ϣ
	public function dels($attachid){
		$rowdata = $this->where('attachid',$attachid)->find();
		if($rowdata){
			//ɾ���ļ�
			$this->deleteFile($rowdata['attachurl']);
			$this->where('attachid',$attachid)->delete();
			return true;
		}else{
			return false;
		}
	}
}