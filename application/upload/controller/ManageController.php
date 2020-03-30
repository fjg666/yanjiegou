<?php
/**
 +----------------------------------------------------------------------
 * 附件管理
 +----------------------------------------------------------------------
 */	
namespace app\upload\controller;
use think\Controller;
class ManageController extends BaseController{
	
	//构造方法
	public function __construct(){
		parent::__construct();
		//判断上传者身份
		$identity = input('identity');
		if( $identity ){
			$this->identity = $identity;
		}
		if( $this->identity = 'ruler' ){
			$uid = get_ruleruid();
		}elseif( $this->identity = 'member' ){
			$uid = get_memberid();
		}else{
			$uid = 0;
		}
		if( !$uid ){
			return json(['status'=>100,'message'=>'请先登录']);
		}
	}
	
	//管理首页
	public function indexOp(){
		//模块ID
		$moduleid = input('moduleid');
		$this->assign('moduleid', $moduleid);
		//上传标示
		$attachmark = input('attachmark');
		$this->assign('attachmark', $attachmark);
		//上传者身份
		$identity = input('identity');
		$this->assign('identity', $identity);
		//文本框名称
		$inputname = input('inputname');
		$this->assign('inputname', $inputname);
		//预览名称
		$previewname = input('previewname');
		$this->assign('previewname', $previewname);
		
		return $this->fetch();		
	}
	
	//数据
	public function dataOp(){
		$where = [];
		//文件类型--图片
		$attachtype = input('attachtype/d');
		$attachtype = in_array($attachtype,[1,2]) ? $attachtype : 2;
		$where['attachtype'] = $attachtype;
		//上传标示
		$attachmark = input('attachmark');
		$attachmark && $where['attachmark'] = $attachmark;
		
		$attachs = model('attachs');
		$rslist = $attachs->where($where)->order('attachid desc')->paginate(10);
		return json($rslist);
	}
	
	//单个字段的修改
    public function fieldOp(){
		$attachs = model('attachs');
		$attachid = input('attachid/d');
        $fieldname = input('fieldname');
        $value = input('value');
		$result = $attachs->where('attachid',$attachid)->setField($fieldname,$value);
		if(false === $result){
			return json(['status'=>'error','message'=>$attachs->getError()]);
		}else{
			return json(['status'=>'success','message'=>'编辑成功']);
		}
    }
	
	//删除
	public function deleteOp(){
		$attachs = model('attachs');
		$attachid = input('attachid/d');
		$rowdata = $attachs->where('attachid',$attachid)->find();
		if($rowdata){
			//删除文件
			$rowdata['attachurl'] && $attachs->deleteFile($rowdata['attachurl']);
			//删除缩略图文件
			$rowdata['attachthumb'] && $attachs->deleteFile($rowdata['attachthumb']);
			//执行删除操作
			$attachs->where('attachid',$attachid)->delete();
			return json(['status'=>'success','message'=>'删除成功']);
		}else{
			return json(['status'=>'error','message'=>'信息不存在']);
		}
    }
	
}
