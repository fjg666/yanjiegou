<?php
/**
 * 模型
 */
namespace app\admin\controller;
use think\Db;
use think\request;
use app\admin\controller\Common;
class Module extends Common
{
    public function initialize(){
        parent::initialize();
    }
	//首页
	public function index(){
				
		return $this->fetch();
    }
	
	//数据
	public function data(){
		//分页大小
		$page =input('page')?input('page'):1;
        $pageSize =input('limit')?input('limit'):config('pageSize');
		$list = model('module')
		     ->alias('r')
		     ->order('moduleid asc')
		     ->paginate(array('list_rows'=>$pageSize,'page'=>$page))
		     ->each(function($row,$key){
			$row['markopenname'] = get_status($row['markopen'],'water','module');
			$row['thumbname'] = get_status($row['thumb'],'thumb','module');
		     })->toArray();
		return ['code'=>0,'msg'=>"获取成功",'data'=>$list['data'],'count'=>$list['total'],'rel'=>1];
    }
	
	//获取当印图片目录下的文件
	public function getMarkFiles(){
		$arrayFile = [];
		$dir = ROOT_PATH . 'public/static/admin/images/watermark';
		$files = scandir($dir);
		foreach($files as $file){
			$filename = $dir . DS . $file;
			if( is_file($filename) ){
				$arrayFile[] = [
					'name'	=> $file,
					'path'	=> '/static/admin/images/watermark/' .$file,
				];
			}
		}
		return $arrayFile;
	}
	
	//添加
	public function add(){
        if(request()->isPost()){
			$module = model('module');
			$result = $module->allowField(true)->validate(true)->save($_POST);
			if(false === $result){
				return json(['status'=>'error','message'=>$module->getError()]);
			}else{
				return json(['status'=>'success', 'message'=>'添加成功', 'url'=>url('index')]);
			}
		}
		//获取水印文件
		$arrayFile = $this->getMarkFiles();
		$this->assign('arrayFile',$arrayFile);
		$rowdata = [
			'uploadformatimage'	=> '/uploads/{uploadcatalog}/{yyyy}{mm}/{yyyy}{mm}{dd}{hh}{ii}{ss}{rand:6}',
			'filepathformat'	=> '/uploads/{uploadcatalog}/{yyyy}{mm}/{yyyy}{mm}{dd}{hh}{ii}{ss}{rand:6}',
		];
		$this->assign('rowdata',$rowdata);
		return $this->fetch();
    }
	
	//修改
	public function edit(){
		if(request()->isPost()){
			$module = model('module');
			$result = $module->allowField(true)->validate(true)->isUpdate(true)->save($_POST);
			if(false === $result){
				return json(['status'=>'error','message'=>$module->getError()]);
			}else{
				return json(['status'=>'success', 'message'=>'修改成功', 'url'=>url('index')]);
			}
		}
		$module = model('module');
		$id = input('id/d');
		$rowdata = $module->where('id',$id)->find();
		$this->assign('rowdata',$rowdata);
		
		//获取水印文件
		$arrayFile = $this->getMarkFiles();
		$this->assign('arrayFile',$arrayFile);
		return $this->fetch();
    }
		
	//单个字段的修改
    public function field(){
		$module = model('module');
		$id = input('id/d');
		$fieldname = input('fieldname');
		$value = input('value');
		$result = $module->where('id',$id)->setField($fieldname,$value);
		if(false === $result){
			return json(['status'=>'error','message'=>$module->getError()]);
		}else{
			return json(['status'=>'success','message'=>'编辑成功']);
		}
    }
		
	//删除
	public function delete(){
		$module = model('module');
		$id = gp('id','d');
		$rowdata = $module->where('id',$id)->find();
		if(empty($rowdata)){
			return json(['status'=>'error','message'=>'模型不存在']);
		}
		//判断此模型下是否有分类存在
		$moduleid = $rowdata['moduleid'];
		$category = model('category');
		$categoryRow = $category->where('moduleid',$moduleid)->find();
		if($categoryRow){
			return json(['status'=>'error','message'=>'此模型有分类存在，不能删除']);
		}
		//判断此模型下是否有信息存在
		$model = model($rowdata['moduleapp']);
		$rowInfo = $model->find();
		if( $rowInfo ){
			return json(['status'=>'error','message'=>'此模型有信息存在，不能删除']);
		}
		//判断此模型下是否有附件信息
		$attachs = model('attachs');
		$attachsInfo = $attachs->where('moduleid',$moduleid)->find();
		if( $attachsInfo ){
			return json(['status'=>'error','message'=>'此模型有附件信息存在，不能删除']);
		}
		//执行删除操作
		$module->where('id',$id)->delete();
		return json(['status'=>'success','message'=>'删除成功']);
    }
	
}