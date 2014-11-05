<?php
/**
 * File: L_Excel.class.php
 * Functionality: 利用 PHPExcel 读取 Excel 文件中的内容
 * Author: Nic XIE
 * Date: 2012-09-24
 * Remark: 仅读取活动的 SHEET and Thanks to PHPExcel team !
 */

require_once BASE_PATH.'/plugin/PHPExcel/Classes/PHPExcel.php';
require_once BASE_PATH.'/plugin/PHPExcel/Classes/PHPExcel/Writer/Excel5.php';
require_once BASE_PATH.'/plugin/PHPExcel/Classes/PHPExcel/Reader/Excel5.php';

$cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp; 
$cacheSettings = array( 'memoryCacheSize' => '600MB' ); 
PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);


class L_Excel{
	
	// source excel file:
	private $file    = '';

	// excel reader:
	private $reader  = null;
	
	// PHPExcel:
	private $excel   = null;
	
	// current sheet:
	private $sheet   = null;
	
	// start
	// 有 TITLE，则从第二行开始读取，无取从第一行开始读
	private $start   = 1;
	
	// columns:
	public $columns = 0;
	
	// rows:
	public $rows    = 0;
	
	// result:
	public $result  = array();
	
	// 只读取指定的列
	private $neededColumns = array();

	// Init 
	function __construct($file, $hasTitle = 0, $neededColumns = ''){
		$this->file  = $file;
		
		if($hasTitle){
			$this->start = 2;
		}
		
		// 指定读取的列, 将列名转成大写
		if($neededColumns && is_array($neededColumns)){
			foreach($neededColumns as $k => $v){
				$neededColumns[$k] = strtoupper($v);
			}
			$this->neededColumns = $neededColumns;
		}
		
		$this->reader = new PHPExcel_Reader_Excel5;
		$this->excel  = $this->reader->load($this->file);
		
		// GO
		$this->sheet   = $this->excel->getActiveSheet();
		$this->columns = $this->sheet->getHighestColumn();
		$this->rows    = $this->sheet->getHighestRow();
	}
	
	
	// retriveData
	public function retrive(){
		for($currentRow = $this->start; $currentRow <= $this->rows; $currentRow++){

			// 从第A列开始输出
			for($currentColumn ='A'; $currentColumn <= $this->columns; $currentColumn++){
		
				// 将字符转为十进制数
				if($this->neededColumns){
					if(in_array($currentColumn, $this->neededColumns)){
						$val = $this->sheet->getCellByColumnAndRow(ord($currentColumn) - 65, $currentRow)->getValue();
						$this->result[$currentColumn][] = $val;
					}
				}else{
					$val = $this->sheet->getCellByColumnAndRow(ord($currentColumn) - 65, $currentRow)->getValue();
					$this->result[$currentColumn][] = $val;
				}
			
			}
		}
		
		return $this->result;
	}
	
	public function retrive1($rowNum){
		if($rowNum)
			$this->rows = $rowNum;
		$ii = 0;
		for($currentRow = $this->start; $currentRow <= $this->rows; $currentRow++){
			// 从第A列开始输出
			$ii++;
			for($currentColumn ='A'; $currentColumn <= $this->columns; $currentColumn++){
		
				// 将字符转为十进制数
				if($this->neededColumns){
					if(in_array($currentColumn, $this->neededColumns)){
						$val = $this->sheet->getCellByColumnAndRow(ord($currentColumn) - 65, $currentRow)->getValue();
						$this->result[$ii][] = $val;
					}
				}else{
					$val = $this->sheet->getCellByColumnAndRow(ord($currentColumn) - 65, $currentRow)->getValue();
					$this->result[$ii][] = $val;
				}
			}
		}
		return $this->result;
	}
	
	static public function excel_export($data,$excelFileName,$sheetTitle){
	    /*
	     * excel导出函数
	     * $data为从数据库中获取到的数据
	     * $excelFileName下载的excel的文件名称
	     * $sheetTitle第一个工作区的名称
	     *  
	    */
		
	    /* 实例化类 */
	    $objPHPExcel = new PHPExcel();
	    
	    /* 设置输出的excel文件格式 */
	    $objWriter=new PHPExcel_Writer_Excel5($objPHPExcel);
	    //$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
	    
	    /* 设置当前的sheet */
	    $objPHPExcel->setActiveSheetIndex(0);
	    $objActSheet = $objPHPExcel->getActiveSheet();
	    
	    /* sheet标题 */
	    $objActSheet->setTitle($sheetTitle);
	    
	    $objActSheet->setCellValue('A1', '姓名');
	    $objActSheet->setCellValue('B1', '手机');
	    $objActSheet->setCellValue('C1', '区域');
	    $objActSheet->setCellValue('D1', '板块');
	    $objActSheet->setCellValue('E1', '公司');
	    $objActSheet->setCellValue('F1', '门店');
	    
	    $i = 2;
	    foreach($data as $value) {
	    	$objActSheet->setCellValue('A'.$i, $value['name']);
	    	$objActSheet->setCellValue('B'.$i, $value['mobile']);
	    	$objActSheet->setCellValue('C'.$i, $value['region']);
	    	$objActSheet->setCellValue('D'.$i, $value['district']);
	    	$objActSheet->setCellValue('E'.$i, $value['company']);
	    	$objActSheet->setCellValue('F'.$i, $value['shop']);
	    	$i++;
	    }
	   
	    
	    /* 生成文件 */
	    $putPutFileName = TMP_PATH . '/' . $excelFileName . ".xls";
	    $objWriter->save($putPutFileName); 
	    
	    
	    
	    /* 生成到浏览器，提供下载 */
	    /**header("Pragma: public");
	    header("Expires: 0");
	    header("Cache-Control:must-revalidate,post-check=0,pre-check=0");
	    header("Content-Type:application/force-download");
	    header("Content-Type:application/vnd.ms-execl");
	    header("Content-Type:application/octet-stream");
	    header("Content-Type:application/download");
	    header('Content-Disposition:attachment;filename="'.$excelFileName.'.xls"');
	    header("Content-Transfer-Encoding:binary");
	    $objWriter->save('php://output');**/
	}
	
	static public function excel_export_flow($data,$excelFileName,$sheetTitle){
	    /*
	     * excel导出函数
	     * $data为从数据库中获取到的数据
	     * $excelFileName下载的excel的文件名称
	     * $sheetTitle第一个工作区的名称
	     *  
	    */
		
	    /* 实例化类 */
	    $objPHPExcel = new PHPExcel();
	    
	    /* 设置输出的excel文件格式 */
	    $objWriter=new PHPExcel_Writer_Excel5($objPHPExcel);
	    //$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
	    
	    /* 设置当前的sheet */
	    $objPHPExcel->setActiveSheetIndex(0);
	    $objActSheet = $objPHPExcel->getActiveSheet();
	    
	    /* sheet标题 */
	    $objActSheet->setTitle($sheetTitle);
	    
	    $objActSheet->setCellValue('A1','编号');
	    $objActSheet->setCellValue('B1','流水编号');
	    $objActSheet->setCellValue('C1','时间');
	    $objActSheet->setCellValue('D1','小区');
	    $objActSheet->setCellValue('E1','客户名/项目名');
	    $objActSheet->setCellValue('F1','收益人');
	    $objActSheet->setCellValue('G1','业务类型');
		$objActSheet->setCellValue('H1','业务量');
		$objActSheet->setCellValue('I1','业务额');
		$objActSheet->setCellValue('J1','提成(%)');
		$objActSheet->setCellValue('K1','收益额');
		$objActSheet->setCellValue('L1','结算状态');
		
	    $i = 2;
	    foreach($data as $value) {
			if($value['type'] == 1 || $value['type'] == 7) $type = '推荐客户';
			elseif($value['type'] == 2) $type = '发展团队';
			elseif($value['type'] == 3) $type = '代理商加盟';
			elseif($value['type'] == 4) $type = '区内业务';
			elseif($value['type'] == 5) $type = '区外业务';
			elseif($value['type'] == 6) $type = '项目代理人';
			elseif($value['type'] == 99) $type = 'fdw收益';
			
			if($value['isValid'] == 0){
				$checkout = '已冻结';
			} else {
				if($value['status'] == 0) $checkout = '待结算';
				elseif($value['status'] == 1) $checkout = '已结算';
				elseif($value['status'] == 2) $checkout = '已申请结算';
				elseif($value['status'] == 3) $checkout = '初审通过';
			}
			
	    	$objActSheet->setCellValue('A'.$i, $value['id']);
	    	$objActSheet->setCellValue('B'.$i, $value['flowNO']);
	    	$objActSheet->setCellValue('C'.$i, date('Y-m-d',$value['addTime']));
	    	$objActSheet->setCellValue('D'.$i, $value['property']);
	    	$objActSheet->setCellValue('E'.$i, $value['targetName']);
	    	$objActSheet->setCellValue('F'.$i, $value['realName']);
			$objActSheet->setCellValue('G'.$i, $type);
			$objActSheet->setCellValue('H'.$i, $value['deals']);
			$objActSheet->setCellValue('I'.$i, $value['projectFee']);
			$objActSheet->setCellValue('J'.$i, $value['percentage']);
			$objActSheet->setCellValue('K'.$i, $value['income']);
			$objActSheet->setCellValue('L'.$i, $checkout);
	    	$i++;
	    }
	    	
	    
	    /* 生成文件 */
	    /* $putPutFileName = "test.xlsx";
	    $objWriter->save($putPutFileName); */
	    
	    /* 生成到浏览器，提供下载 */
	    header("Pragma: public");
	    header("Expires: 0");
	    header("Cache-Control:must-revalidate,post-check=0,pre-check=0");
	    header("Content-Type:application/force-download");
	    header("Content-Type:application/vnd.ms-execl");
	    header("Content-Type:application/octet-stream");
	    header("Content-Type:application/download");
	    header('Content-Disposition:attachment;filename="'.$excelFileName.'.xls"');
	    header("Content-Transfer-Encoding:binary");
	    $objWriter->save('php://output');
	}
	
}

?>