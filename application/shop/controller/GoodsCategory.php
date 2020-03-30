<?php
namespace app\shop\controller;
use think\Db;
use ensh\Tree;
use think\request;
class GoodsCategory extends Common
{
    protected $model, $categorys='' , $module,$groupId;
    function _initialize()
    {
        parent::_initialize();
        $this->model = model('GoodsCategory');
    }
    public function index()
    {
        $list=Db::name('GoodsCategory')->order('sort')->select();
        $categorys='';
        if ($list) {
            foreach ($list as $r) {
                    $r['str_manage'] = '';
                    $r['str_manage'] .= '<a class="green" title="添加子分类" href="' . url('GoodsCategory/add', array('parentid' => $r['id'])) . '">添加子分类</a> | <a class="green" href="' . url('GoodsCategory/edit', array('id' => $r['id'])) . '" title="">修改</a> | <a class="red" href="javascript:del(\'' . $r['id'] . '\')" title="删除">删除</a> ';

                    $r['dis'] = $r['ismenu'] == 1 ? '<a href="javascript:;" onclick="ismenu(0,'.$r['id'].')" class="green">显示</a>' : '<a href="javascript:;" onclick="ismenu(1,'.$r['id'].')" class="red">不显示</a>';
                    $array[] = $r;
            }
            $str = "<tr><td class='visible-lg visible-md'>\$id</td>";
            $str .= "<td class='text-left'>\$spacer<a href='' class='green' title='查看内容'>\$catname </a>&nbsp;</td>";

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
            unset($data['file']);
            $data['child'] = isset($data['child'])?1:0;
            $id = $this->model->insert($data);
            if($id) {
                $this->repair();
                $this->repair();
                return $this->resultmsg('添加成功!',1,url('index'));
            }else{
                return  $this->resultmsg('添加失败!',0);
            }
        }else{
            
            $parentid = input('parentid/d');
            $array=$this->model->column('catname,id,parentid,child','id');
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
            $data['child'] = isset($data['child']) ? '1' : '0';
            if (false !==$id=$this->model->update($data)) {
                $this->repair();
                $this->repair();
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

    public function repair() {
        @set_time_limit(500);
        $this->categorys = $categorys = array();
        $categorys =  $this->model->where("parentid=0")->order('sort ASC,id ASC')->select();
        $this->set_categorys($categorys);
        if(is_array($this->categorys)) {
            foreach($this->categorys as $id => $cat) {
                $this->categorys[$id]['arrparentid'] = $arrparentid = $this->get_arrparentid($id);
                $this->categorys[$id]['arrchildid'] = $arrchildid = $this->get_arrchildid($id);
                $this->categorys[$id]['parentdir'] = $parentdir = $this->get_parentdir($id);
                $this->model->update(array('parentdir'=>$parentdir,'arrparentid'=>$arrparentid,'arrchildid'=>$arrchildid,'id'=>$id));
            }
        }

    }
    public function set_categorys($categorys = array()) {
        if (is_array($categorys) && !empty($categorys)) {
            foreach ($categorys as $id => $c) {
                $this->categorys[$c['id']] = $c;
                $r = $this->model->where(array("parentid"=>$c['id']))->Order('sort ASC,id ASC')->select();
                $this->set_categorys($r);
            }
        }
        return true;
    }
    public function get_arrparentid($id, $arrparentid = '') {
        if(!is_array($this->categorys) || !isset($this->categorys[$id])) return false;
        $parentid = $this->categorys[$id]['parentid'];
        $arrparentid = $arrparentid ? $parentid.','.$arrparentid : $parentid;
        if($parentid) {
            $arrparentid = $this->get_arrparentid($parentid, $arrparentid);
        } else {
            $this->categorys[$id]['arrparentid'] = $arrparentid;
        }
        return $arrparentid;
    }
    public function get_arrchildid($id) {
        $arrchildid = $id;
        if(is_array($this->categorys)) {
            foreach($this->categorys as $catid => $cat) {
                if($cat['parentid'] && $id != $catid) {
                    $arrparentids = explode(',', $cat['arrparentid']);
                    if(in_array($id, $arrparentids)){
                        $arrchildid .= ','.$catid;
                    }
                }
            }
        }
        return $arrchildid;
    }
    public function get_parentdir($id) {
        if($this->categorys[$id]['parentid']==0){
            return '';
        }
        $arrparentid = $this->categorys[$id]['arrparentid'];
        unset($r);
        if ($arrparentid) {
            $arrparentid = explode(',', $arrparentid);
            $arrcatdir = array();
            foreach($arrparentid as $pid) {
                if($pid==0) continue;
                $arrcatdir[] = $this->categorys[$pid]['catdir'];
            }
            return implode('/', $arrcatdir).'/';
        }
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
        //savecache('Category');
        $this->model->where(['id'=>$catid])->delete();
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
        savecache('Category');
        cache('cate', NULL);
        return $result;
    }
}