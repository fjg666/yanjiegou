<?php
namespace app\admin\controller;
use think\Db;
use ensh\Tree;
use think\request;
class Category extends Common
{
    protected $model, $categorys , $module,$groupId;
    function _initialize()
    {
        parent::_initialize();
        $this->model = model('category');
    }
    public function index()
    {
        $list = cache('Category');
        if ($list) {
            foreach ($list as $r) {
                    $r['str_manage'] = '';
                    $r['str_manage'] .= '<a class="" title="添加子栏目" href="' . url('Category/add', array('parentid' => $r['id'])) . '"> 添加子栏目</a>  <a class="" href="' . url('Category/edit', array('id' => $r['id'])) . '" title="修改"><i class="icon icon-pencil2"></i></a>  <a class="red" href="javascript:del(\'' . $r['id'] . '\')" title="删除"><i class="icon icon-bin"></i></a> ';
                    $r['dis'] = $r['ismenu'] == 1 ? '<font color="green">显示</font>' : '<font color="red">不显示</font>';
                    $array[] = $r;
            }
            $str = "<tr><td class='visible-lg visible-md'>\$id</td>";
            $str .= "<td class='text-left'>\$spacer<a href='' class='' title='查看内容'>\$catname </a>&nbsp;</td>";
            $str .= "<td><input type='text' size='10' data-id='\$id' value='\$sort' class='layui-input list_order'></td><td>\$str_manage</td></tr>";
            $tree = new Tree ($array);
            $tree->icon = array('&nbsp;&nbsp;&nbsp;│  ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
            $tree->nbsp = '&nbsp;&nbsp;&nbsp;';
            $categorys = $tree->get_tree(0, $str);
            $this->assign('categorys', $categorys);
        }else{
            $this->assign('categorys', '<p style="color:red;text-align:center;font-size:16px">暂无分类请添加</p>');
        }
        $this->assign('title','分类列表');
        return $this->fetch();
    }
    public function add(){
        if(Request::instance()->isAjax()){
            $data = input('post.');
            if(null === $data['parentid']){
                $data['level'] = $data['child'] = 1;
            }else{
                $parentid = $data['parentid'];
                $res = Db::name('Category')->where(['id'=>$parentid])->find();
                $data['level']=$res['level']+1;
                if ($data['level']<3) {
                    $data['child']=1;
                }
            }
            unset($data['file']);
            $id = $this->model->insert($data);
            if($id) {
                savecache('Category');
                return $this->resultmsg('添加成功!',1,url('index'));
            }else{
                return  $this->resultmsg('添加失败!',0);
            }
        }else{
            $parentid =	input('parentid/d');
            //栏目选择列表
            $array=$this->model->column('catname,id,parentid','id');
            $str  = "<option value='\$id' \$selected>\$spacer \$catname</option>";
            $tree = new Tree ($array);
            $categorys = $tree->get_tree(0,$str,$parentid);
            $this->assign('categorys', $categorys);
            $this->assign('title','添加分类');
            return $this->fetch();
        }
    }
    public function edit(){
        if(request()->isPost()) {
            $data = input('post.');
            unset($data['file']);
            if (false !==$id=$this->model->update($data)) {
                savecache('GoodsCategory');
                return $this->resultmsg('分类修改成功!',1,url('index'));
            }else{
                return  $this->resultmsg('分类修改失败!',0);
            }
        }
        $array=$this->model->column('*','id');
        $id = input('id');
        $module = db('module')->field('id,title,name')->select();
        $this->assign('modulelist',$module);
        $row = $array[$id];
        $row['imgUrl'] = imgUrl($row['image']);
        $parentid =	intval($row['parentid']);
        $result = $this->categorys;
        $str  = "<option value='\$id' \$selected>\$spacer \$catname</option>";
        $tree = new Tree ($array);
        $categorys = $tree->get_tree(0, $str,$parentid);
        $this->assign('row',$row);
        $this->assign('categorys', $categorys);
        $this->assign('title','编辑分类');
        return $this->fetch();
    }
    public function del() {
        $catid = input('param.id');
        $modules = $this->categorys[$catid]['module'];
        $modulesId = $this->categorys[$catid]['moduleid'];
        $scount = $this->model->where(array('parentid'=>$catid))->count();
        if($scount){
            $result['info'] = '请先删除其子栏目!';
            $result['status'] = 0;
            return $result;
        }
        $module  = db($modules);
        $arrchildid = $this->categorys[$catid]['arrchildid'];
        if($modules != 'page'){
            $fields = cache($modulesId.'_Field');
            $fieldse=array();
            foreach ($fields as $k=>$v){
                $fieldse[] = $k;
            }
            if(in_array('catid',$fieldse)){
                $count = $module->where('catid','in',$arrchildid)->count();
            }else{
                $count = $module->count();
            }
            if($count){
                $result['info'] = '请先删除该栏目下所有数据!';
                $result['status'] = 0;
                return $result;
            }
        }
        $pid = $this->categorys[$catid]['parentid'];
        $scat = $this->model->where(array('parentid'=>$pid))->count();
        if($scat==1){
            $this->model->where(array('id'=>$pid))->update(array('child'=>0));
        }
        $this->model->where('id','in',$arrchildid)->delete();
        $arr=explode(',',$arrchildid);
        foreach((array)$arr as $r){
            if($this->categorys[$r]['module']=='page'){
                $module=db('page');
                $module->delete($r);
            }
        }
        $this->repair();
        savecache('Category');
        $result['info'] = '栏目删除成功!';
        cache('cate', NULL);
        $result['url'] = url('index');
        $result['status'] = 1;
        return $result;
    }
    public function cOrder(){
        $data = input('post.');
        $this->model->update($data);
        $result = ['msg' => '排序成功！', 'code' => 1,'url'=>url('index')];
        savecache('Category');
        cache('cate', NULL);
        return $result;
    }
}