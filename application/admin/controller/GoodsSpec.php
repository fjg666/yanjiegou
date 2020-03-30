<?php
namespace app\admin\controller;
use think\Db;
use ensh\Tree;
use think\request;
class GoodsSpec extends Common
{
    // protected $model, $categorys='' , $module,$groupId;
    function _initialize(){
        parent::_initialize();
        $this->model = model('GoodsSttr');
    }
    public function index(){
        // $list = cache('GoodsSttr');
        // if (!$list) {
            
        // }
        $list = Db::name('GoodsSttr')
                ->alias('s')
                ->field('s.*,c.catname')
                ->join('GoodsCategory c','s.type_id = c.id','left')
                ->where('s.status','1')
                ->select();


        if ($list) {
            $categorys = '';
            foreach ($list as $r) {
                $id_text = "<tr style='display:table-row;'><td class='visible-lg visible-md'>".$r['id']."</td>";
                $key_text = "<td class='visible-lg visible-md'>".$r['key']."</td>";
                $catname_text = "<td class='visible-lg visible-md'>".$r['catname']."</td>";
                $sort_text = "<td><input type='text' size='10' data-id='".$r['id']."' value='".$r['sort']."' class='layui-input list_order'></td>";
                $text = '<td><a class="blue" href="' . url('GoodsSpec/edit', array('id' => $r['id'])) . '" title="">修改</a> | <a class="red" href="javascript:del(\'' . $r['id'] . '\')" title="删除">删除</a></td></tr>';
                $categorys .= $id_text.$key_text.$catname_text.$sort_text.$text; 
            }
        }
        // print_r($categorys);exit;
        $this->assign('categorys', $categorys?$categorys:'<p style="color:red;text-align:center;font-size:16px">暂无规格请添加</p>');
        $this->assign('title','规格列表');
        return $this->fetch();
    }
    public function add(){
        if(request()->isPost()) {
            $data = input('post.');
            if (empty($data['parentid']) || empty($data['catname'])) {
                return  $this->resultmsg('必填项没有填写!',0);
            }
            $post = [
                'type_id'=>$data['parentid'],
                'key'=>$data['catname'],
                'is_main'=>1
            ];
            $id = Db::name('GoodsSttr')->insert($post);
            if($id) {
                // savecache('GoodsSpec');
                return $this->resultmsg('添加成功!',1,url('index'));
            }else{
                return  $this->resultmsg('添加失败!',0);
            }
        }else{
            $parentid = input('parentid/d');
            $array=Db::name('GoodsCategory')->where('parentid',0)->column('catname,id,parentid,child','id','arrparentid');
            // print_r($array);exit;
            $categorys = '';
            foreach ($array as $key => $value) {
                $categorys  .= "<option value='".$value['id']."'>".$value['catname']."</option>";
            }

            $this->assign('categorys', $categorys);
            return $this->fetch();
        }
    }
    public function edit(){
        if(request()->isPost()) {
            $data = input('post.');
            if (false !==$id=Db::name('GoodsSttr')->update($data)) {
                // savecache('GoodsSpec');
                return $this->resultmsg('规格修改成功!',1,url('index'));
            }else{
                return  $this->resultmsg('规格修改失败!',0);
            }
        }
        


        $id = input('id');
        $res = Db::name('GoodsSttr')->where('id',$id)->find();
        $array=Db::name('GoodsCategory')->where('parentid',0)->column('catname,id,parentid,child','id','arrparentid');
        // print_r($array);exit;
        $categorys = '';
        foreach ($array as $key => $value) {
            if ($value['id'] == $res['type_id']) {
                $categorys  .= "<option value='".$value['id']."' selected>".$value['catname']."</option>";
            }else{
                $categorys  .= "<option value='".$value['id']."'>".$value['catname']."</option>";
            }
        }

        $this->assign('categorys', $categorys);
        $this->assign('row',$res);
        $this->assign('title','编辑规格');
        return $this->fetch();
    }
    public function del() {
        $catid = input('param.id/d');
        $scount = $this->model->where(array('pid'=>$catid))->count();
        if($scount){
            $result['info'] = '请先删除其子规格!';
            $result['status'] = 0;
            return $result;
        }
        $this->model->where(['id'=>$catid])->delete();
        // savecache('GoodsSpec');
        $result['info'] = '规格删除成功!';
        $result['url'] = url('index');
        $result['status'] = 1;
        return $result;
    }
    public function cOrder(){
        $data = input('post.');
        $this->model->update($data);
        $result = ['msg' => '排序成功！', 'code' => 1,'url'=>url('index')];
        // savecache('GoodsSpec');
        return $result;
    }
}