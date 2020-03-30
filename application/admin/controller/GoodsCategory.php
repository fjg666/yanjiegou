<?php
namespace app\admin\controller;
use think\Db;
use ensh\Tree;
use think\request;
class GoodsCategory extends Common
{
    protected $model, $categorys='' , $module,$groupId;
    function _initialize(){
        parent::_initialize();
        $this->model = model('GoodsCategory');
    }
    public function index(){
        $list = cache('GoodsCategory');
        $categorys='';
        if ($list) {
            foreach ($list as $r) {
                    $r['str_manage'] = '';
                    $r['str_manage'] .= '<a class="blue" title="添加子分类" href="' . url('GoodsCategory/add', array('parentid' => $r['id'])) . '">添加子分类</a> | <a class="blue" href="' . url('GoodsCategory/edit', array('id' => $r['id'])) . '" title="">修改</a> | <a class="red" href="javascript:del(\'' . $r['id'] . '\')" title="删除">删除</a> ';
                    $r['dis'] = $r['ismenu'] == 1 ? '<a href="javascript:;" onclick="ismenu(0,'.$r['id'].')" class="blue">显示</a>' : '<a href="javascript:;" onclick="ismenu(1,'.$r['id'].')" class="red">不显示</a>';
                    $array[] = $r;
            }
            $str = "<tr class='code\$parentid'><td class='visible-lg visible-md'>\$id</td>";
            $str .= "<td class='text-left'>\$spacer<a href='javascript:;' onclick='showson(this,\$id)' data-display=0 class='' title='查看子级'>\$catname </a>&nbsp;</td>";
            $str .= "<td class='visible-lg visible-md'>\$dis</td>";
            $str .= "<td><input type='text' size='10' data-id='\$id' value='\$sort' class='layui-input list_order'></td><td>\$str_manage</td></tr>";
            $tree = new Tree ($array);
            $tree->icon = array('&nbsp;&nbsp;&nbsp;│  ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
            $tree->nbsp = '&nbsp;&nbsp;&nbsp;';
            $categorys = $tree->get_tree(0, $str);
        }
        $this->assign('categorys', $categorys?$categorys:'<p style="color:red;text-align:center;font-size:16px">暂无分类请添加</p>');
        $this->assign('title','分类列表');
        return $this->fetch();
    }
    public function add(){
        if(request()->isPost()) {
            $data = input('post.');
            if(null === $data['parentid']){
                $data['level'] = $data['child'] = 1;
            }else{
                $parentid = $data['parentid'];
                $res = Db::name('goods_category')->where(['id'=>$parentid])->find();
                $arrparentid = explode(',',$res['arrparentid']);
                $data['level']=$res['level']+1;
                if ($data['level']<3) {
                    $data['child']=1;
                }
            }
            unset($data['file']);
            $id = $this->model->insert($data);
            if($id) {
                savecache('GoodsCategory');
                return $this->resultmsg('添加成功!',1,url('index'));
            }else{
                return  $this->resultmsg('添加失败!',0);
            }
        }else{
            $parentid = input('parentid/d');
            $array=$this->model->column('catname,id,parentid,child','id','arrparentid');
            $str  = "<option value='\$id' \$selected>\$spacer \$catname</option>";
            $tree = new Tree ($array);
            $categorys = $tree->get_tree(0,$str,$parentid);
            $this->assign('categorys', $categorys);
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
        $catid = input('param.id/d');
        $scount = $this->model->where(array('parentid'=>$catid))->count();
        if($scount){
            $result['info'] = '请先删除其子分类!';
            $result['status'] = 0;
            return $result;
        }else{
            $count=model('goods')->where(array('catid' =>$catid ))->count();
            if($count){
                $result['info'] = '请先删除该分类下所有数据!';
                $result['status'] = 0;
                return $result;
            }
        }
        $this->model->where(['id'=>$catid])->delete();
        savecache('GoodsCategory');
        $result['info'] = '分类删除成功!';
        $result['url'] = url('index');
        $result['status'] = 1;
        return $result;
    }
    public function ismenu(){
        $data = input('post.');
        if($this->model->where(array('id'=>$data['id']))->update(array('ismenu'=>$data['is']))){
            return ['info'=>'修改成功','url'=>'','status'=>1];
        }else{
            return ['info'=>'修改失败','url'=>'','status'=>0];
        }
    }
    public function cOrder(){
        $data = input('post.');
        $this->model->update($data);
        $result = ['msg' => '排序成功！', 'code' => 1,'url'=>url('index')];
        savecache('GoodsCategory');
        return $result;
    }
}