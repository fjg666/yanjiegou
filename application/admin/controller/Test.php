<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use app\admin\controller\Common;
class Test extends Common{
	public function _initialize(){
        parent::_initialize();
    }
	public function index(){
         return $this->fetch();
	}
    public function do_upload(){
        //引入文件
        \think\Loader::import('PHPExcel.PHPExcel');
        $objPHPExcel = new \PHPExcel();
        //获取表单上传文件
        $file = request()->file('file');
        $info = $file->validate(['ext' => 'xlsx,xls'])->move(ROOT_PATH . 'public' . DS . 'uploads');
        //数据为空返回错误
        if(empty($info)){
            $output['status'] = false;
            $output['info'] = '导入数据失败~';
            $this->ajaxReturn($output);
        }
        //获取文件名
        $exclePath = $info->getSaveName();
        //上传文件的地址
        $filename = ROOT_PATH . 'public' . DS . 'uploads'.DS . $exclePath;
        $extension = strtolower( pathinfo($filename, PATHINFO_EXTENSION) );
        \think\Loader::import('PHPExcel.IOFactory.PHPExcel_IOFactory');
        if ($extension =='xlsx') {
            $objReader = new \PHPExcel_Reader_Excel2007();
            $objExcel = $objReader ->load($filename);
        } else if ($extension =='xls') {
            $objReader = new \PHPExcel_Reader_Excel5();
            $objExcel = $objReader->load($filename);
        }
        $excel_array=$objExcel->getsheet(0)->toArray();   //转换为数组格式
        array_shift($excel_array);  //删除第一个数组(标题);
        //array_shift($excel_array);  //删除th
        $data=[];
        foreach ($excel_array as $k=>$v){
            $data[$k]["id"]=$v[0];//单号
            $data[$k]["parentid"]=$v[1];//类型名称
            $data[$k]["catname"]=$v[2];
        }
        //d($data);
        if (Db::name('goodsType')->insertAll($data)) {
        	echo 1;die;
        }
        echo 2;
        //d(genTree5($data));
    }
    // public function test(){
    //     ini_set('max_execution_time', '0');
    //     $list=Db::name('goodsCategory')->where('level=1')->select();
    //     foreach ($list as $k => $v) {
    //         $s=Db::name('goodsCategory')->where('parentid='.$v['id'])->column('arrchildid','id');
    //         $str='';
    //         $str=$v['id'].','.implode(',',$s);
    //         Db::name('goodsCategory')->where('id='.$v["id"])->update(['arrchildid'=>$str]);
    //     }
    // }
// public function out(){
// 	$path = dirname(__FILE__);
// 	vendor("PHPExcel.PHPExcel");
// 	vendor("PHPExcel.PHPExcel.Writer.Excel5");
// 	vendor("PHPExcel.PHPExcel.Writer.Excel2007");
// 	vendor("PHPExcel.PHPExcel.IOFactory");
// 	$objPHPExcel = new \PHPExcel();
// 	$objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);
// 	$objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
// 	$sql = db('info')->select();
// 	$objPHPExcel->setActiveSheetIndex(0)
// 	->setCellValue('A1', 'ID编号')
// 	->setCellValue('B1', '用户名')
// 	->setCellValue('C1', '性别')
// 	->setCellValue('D1', '地址');
// 	/*--------------开始从数据库提取信息插入Excel表中------------------*/
// 	//$i=2; //定义一个i变量，目的是在循环输出数据是控制行数
// 	$count = count($sql); //计算有多少条数据
// 	//echo $count;
// 	//die;
// 	for ($i = 2; $i <= $count+1; $i++) {
// 	$objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $sql[$i-2]['id']);
// 	$objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $sql[$i-2]['name']);
// 	$objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $sql[$i-2]['gender']);
// 	$objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $sql[$i-2]['address']);
// 	}
// 	/*--------------下面是设置其他信息------------------*/
// 	$objPHPExcel->getActiveSheet()->settitle('信息'); //设置sheet的名称
// 	$objPHPExcel->setActiveSheetIndex(0); //设置sheet的起始位置
// 	$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5'); //通过PHPExcel_IOFactory的写函数将上面数据写出来
// 	$PHPWriter = \PHPExcel_IOFactory::createWriter( $objPHPExcel,"Excel2007");
// 	header('Content-Disposition: attachment;filename="用户信息.xlsx"');
// 	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
// 	$PHPWriter->save("php://output"); //表示在$path路径下面生成demo.xlsx文件
// }
// public function in(){
// 	Loader::import('PHPExcel.PHPExcel');
// 	$objPHPExcel = new \PHPExcel();
// 	//获取表单上传文件
// 	$file = request()->file('excel');
// 	$info = $file->validate(['size'=>156780,'ext'=>'xlsx,xls,csv'])->move(ROOT_PATH . 'public' . DS . 'excel');
// 	if($info){
// 	//获取文件名
// 	$exclePath = $info->getSaveName();
// 	//上传文件的地址
// 	$file_name = ROOT_PATH . 'public' . DS . 'excel' . DS . $exclePath;
// 	$objReader = \PHPExcel_IOFactory::createReader('Excel5');
// 	//加载文件内容,编码utf-8
// 	$obj_PHPExcel = $objReader->load($file_name, $encode = 'utf-8');
// 	echo "<pre>";
// 	$excel_array = $obj_PHPExcel->getsheet(0)->toArray(); //转换为数组格式
// 	array_shift($excel_array); //删除第一个数组(标题);
// 	$data = [];
// 	foreach ($excel_array as $k => $v) {
// 	$data[$k]['name'] = $v['0'];
// 	$data[$k]['gender'] = $v['1'];
// 	$data[$k]['address'] = $v['2'];
// 	}
// 	d($data);
// 	//批量插入数据
// 	//$success = Db::name('info')->insertAll($data);
// 	echo '数据添加成功';
// 	}else{
// 	// 上传失败获取错误信息
// 	echo $file->getError();
// 	}
// }
// public function exportExcel(){
//         ini_set('memory_limit', '1024M');//设置php允许的文件大小最大值
//         Loader::import('PHPExcel.Classes.PHPExcel');//必须手动导入，否则会报PHPExcel类找不到
//         $objPHPExcel = new \PHPExcel();
//         $worksheet = $objPHPExcel->getActiveSheet();
//         $objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
//         $objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
// // Set document properties
//         $objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
//             ->setLastModifiedBy("Maarten Balliauw")
//             ->setTitle("Office 2007 XLSX Test Document")
//             ->setSubject("Office 2007 XLSX Test Document")
//             ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
//             ->setKeywords("office 2007 openxml php")
//             ->setCategory("Test result file");
//         $objPHPExcel->setActiveSheetIndex(0)
//             ->setCellValue('A1', '昵称')
//             ->setCellValue('B1', '链接')
//             ->setCellValue('C1', '房间号')
//             ->setCellValue('D1', '分组');
// // Rename worksheet
//         $objPHPExcel->getActiveSheet()->setTitle('Simple');
// // Set active sheet index to the first sheet, so Excel opens this as the first sheet
//         $objPHPExcel->setActiveSheetIndex(0);
// // Redirect output to a client’s web browser (Excel2007)
//         header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
//         header('Content-Disposition: attachment;filename="'.date('Ymd').'.xlsx"');
//         header('Cache-Control: max-age=0');
// // If you're serving to IE 9, then the following may be needed
//         header('Cache-Control: max-age=1');
// // If you're serving to IE over SSL, then the following may be needed
//         header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
//         header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
//         header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
//         header ('Pragma: public'); // HTTP/1.0
//         $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
//         $objWriter->save('php://output');
//         exit;
//     }
}