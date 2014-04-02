<?php
require_once './db_info.php';
require_once '../PHPExcel/Classes/PHPExcel.php';

/** Error reporting */
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);
ini_set('memory_limit', -1);
ini_set('max_execution_time', 300);

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PW, DB_DB);
$mysqli->set_charset("UTF8");

$file_id = $_GET["id"];

$query = "SELECT `연번`, `접수번호`, `성명`, `순번`, `실적종류`, `구분1` as `구분(1)`, `구분2` as `구분(2)`, `IF` as `I_F`, `JR` as `JR(%)`, `순위`, `저널수`, `발표년월`, `논문제목`, `저자수`, `참여역할`, `게제지명`, `본인점수`, `ISSN`, `인정여부`, `KRI검증여부`, `1차점수`, `검증점수`, `비고`, FUNC_CODE('CC0001', STATE_CODE) AS `SYS_KRI검증여부`, `SYS_발표년월`, `SYS_논문제목`, `SYS_전체저자수` AS `SYS_저자수`, `SYS_학술지명` AS `SYS_게제지명`, `SYS_ISSN`, `SYS_참여자`, `SYS_게제권`, `SYS_게재호`, `SYS_시작페이지`, `SYS_종료페이지`, `SYS_발행처명`, `SYS_검증ID`, `SYS_비고`, if(`발표년월` like concat(`SYS_발표년월`,'%'), 1, if(`SYS_발표년월` is null, 1, 0)) AS `DIFF_발표년월`, if(`저자수`=if(`SYS_전체저자수`>0,`SYS_전체저자수`,`저자수`), 1, 0) AS `DIFF_저자수`, if(`ISSN`=if(`SYS_ISSN` <> '', `SYS_ISSN`,`ISSN`), 1, 0) AS `DIFF_ISSN`, if(`SYS_비고`<>'', 0, 1) AS `DIFF_비고` FROM `EXCEL` WHERE FILE_ID='$file_id'";

// Create new PHPExcel object
$objPHPExcel = new PHPExcel();
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
$objWorkSheet = $objPHPExcel->setActiveSheetIndex(0);

// Zoom
$objWorkSheet->getSheetView()->setZoomScale(80);

// set initial column widths and height
$objWorkSheet->getColumnDimension('A')->setWidth(8); // 연번
$objWorkSheet->getColumnDimension('B')->setWidth(10); // 접수번호
$objWorkSheet->getColumnDimension('C')->setWidth(10); // 성명
$objWorkSheet->getColumnDimension('D')->setWidth(8); // 순번
$objWorkSheet->getColumnDimension('F')->setWidth(11); // 구분1
$objWorkSheet->getColumnDimension('G')->setWidth(11); // 구분2
$objWorkSheet->getColumnDimension('L')->setWidth(12); // 발표년월
$objWorkSheet->getColumnDimension('M')->setWidth(32); // 논문제목
$objWorkSheet->getColumnDimension('O')->setWidth(12); // 참여역할
$objWorkSheet->getColumnDimension('P')->setWidth(20); // 게제지명
$objWorkSheet->getColumnDimension('R')->setWidth(11); // ISSN

$objWorkSheet->getColumnDimension('Z')->setWidth(32); // SYS_논문제목
$objWorkSheet->getColumnDimension('AB')->setWidth(20); // SYS_게제지명
$objWorkSheet->getColumnDimension('AC')->setWidth(11); // SYS_ISSN
$objWorkSheet->getColumnDimension('AD')->setWidth(20); // SYS_참여자
$objWorkSheet->getColumnDimension('AI')->setWidth(20); // SYS_발행처명
$objWorkSheet->getColumnDimension('AJ')->setWidth(20); // SYS_검증ID
$objWorkSheet->getColumnDimension('AK')->setWidth(11); // SYS_비고
$objWorkSheet->getRowDimension(1)->setRowHeight(40);

if ($result = $mysqli->query($query)) {
	$finfo = $result->fetch_fields();

	// Write column names on the first line
	foreach ($finfo as $key => $val)
		if (strpos($val->name, 'DIFF_') === FALSE)
			$objWorkSheet->setCellValueByColumnAndRow($key, 1, $val->name);

	$rowIndex = 1;
	$styleFillRed = array(
		'fill' => array(
			'type' => PHPExcel_Style_Fill::FILL_SOLID,
			'startcolor' => array('argb' => 'FFFF0000'),
		),
	);
	$styleFillOrange = array(
		'fill' => array(
			'type' => PHPExcel_Style_Fill::FILL_SOLID,
			'startcolor' => array('argb' => 'FFFFC000'),
		),
	);

	while ($row = $result->fetch_assoc()) {
		$rowIndex++;
		$colIndex = 'A';
		foreach ($finfo as $val) {
			$column = $val->name;
			$value = $row[$column];

			if (strpos($column, 'DIFF_') === FALSE) {
				$objWorkSheet->setCellValue($colIndex++.$rowIndex, $value);

				if ($column == 'SYS_KRI검증여부' && $value == '완료') {
					$objWorkSheet->getStyle('M'.$rowIndex)->applyFromArray($styleFillOrange);
					$objWorkSheet->getStyle('Z'.$rowIndex)->applyFromArray($styleFillOrange);
				}
			} else {
				if ($column == 'DIFF_발표년월' && $value == '0')
					$objWorkSheet->getStyle('Y'.$rowIndex)->applyFromArray($styleFillRed);
				if ($column == 'DIFF_저자수' && $value == '0')
					$objWorkSheet->getStyle('AA'.$rowIndex)->applyFromArray($styleFillRed);
				if ($column == 'DIFF_ISSN' && $value == '0')
					$objWorkSheet->getStyle('AC'.$rowIndex)->applyFromArray($styleFillRed);
				if ($column == 'DIFF_비고' && $value == '0')
					$objWorkSheet->getStyle('AK'.$rowIndex)->applyFromArray($styleFillRed);
			}
		}
		// Set row heights
		$objWorkSheet->getRowDimension($rowIndex)->setRowHeight(40);
	}

	$result->free();
	$highestRow = $objWorkSheet->getHighestRow();
	$highestCol = $objWorkSheet->getHighestColumn();

	// Style the first row
	$styleArray = array(
		'fill' => array(
			'type' => PHPExcel_Style_Fill::FILL_SOLID,
			'startcolor' => array('argb' => 'FF92D050'),
		),
		'font' => array(
			'bold' => true,
		),
	);
	$objWorkSheet->getStyle('A1:'.$highestCol.'1')->applyFromArray($styleArray);

	// Style the columns of 접수번호, 성명, and 순번
	$styleArray = array(
		'fill' => array(
			'type' => PHPExcel_Style_Fill::FILL_SOLID,
			'startcolor' => array('argb' => 'FFC5D9F1'),
		),
	);
	$objWorkSheet->getStyle('B2:D'.$highestRow)->applyFromArray($styleArray);

	// Bordering the entire sheet containing data
	$styleArray = array(
		'borders' => array(
			'allborders' => array(
				'style' => PHPExcel_Style_Border::BORDER_THIN,
				'color' => array('argb' => 'FF000000'),
			),
		),
		'alignment' => array(
			'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
			'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
			'wrap' => true,
		),
	);
	$objWorkSheet->getStyle('A1:'.$highestCol.$highestRow)->applyFromArray($styleArray);

	// Exception for alignment of 논문제목
	$objWorkSheet->getStyle('M2:'.'M'.$highestRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_GENERAL);
	$objWorkSheet->getStyle('Z2:'.'Z'.$highestRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_GENERAL);
}

$mysqli->close();

// Rename worksheet
$objPHPExcel->getActiveSheet()->setTitle('논문검증');

// Redirect output to a client’s web browser (Excel2007)
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.date('Ymd').'_Verified-by-System.xlsx"');
header('Cache-Control: max-age=0');

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');
?>