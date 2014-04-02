<?php
require_once './db_info.php';
require_once '../PHPExcel/Classes/PHPExcel.php';

/** Error reporting */
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);
ini_set('memory_limit', -1);

$surfix_dir = date("m-d-Y") . '/';
$save_dir = UPLOAD_PATH . $surfix_dir;
if (!is_dir($save_dir)) {
	mkdir($save_dir, 0755, true);
}

$file = $_FILES["file"];

if (!is_uploaded_file($file["tmp_name"])) {
	die("업로드에 실패하였습니다.");
}

if ($file["type"] != "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet") {
	die("MS Excel 2007 또는 그 이상 버전의 엑셀 파일이 아닙니다.");
}

$tmpfname = tempnam($save_dir, '');

if(!move_uploaded_file($file["tmp_name"], $tmpfname)) {
	die("파일을 지정한 디렉토리에 저장하는데 실패했습니다.");
}

// If succeeded.
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PW, DB_DB);
$mysqli->set_charset("UTF8");

// get a file id
$query = "SELECT concat('E', lpad(nextval('sq_excel'), 29, 0)) as sq_excel";
$result = $mysqli->query($query);
$row = $result->fetch_array();

$file_id = $row[0];
$result->free();

// Start a transaction.
$query = "INSERT INTO `UPLOADED_FILE`(`FILE_ID`, `SERVER_DIR`, `SERVER_FILE_NAME`, `ORG_FILE_NAME`) VALUES (?,?,?,?)";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("ssss", $file_id, $surfix_dir, $tmpfname, $file["name"]);
$stmt->execute();
$stmt->close();

// Upload excel data
$inputFileName = $tmpfname;
$objPHPExcel = PHPExcel_IOFactory::load($inputFileName);
$objWorksheet = $objPHPExcel->getActiveSheet();
$highestRow = $objWorksheet->getHighestRow(); // e.g. 10
//$highestColumn = $objWorksheet->getHighestColumn(); // e.g 'F'
//$highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn); // e.g. 5

$query = "INSERT INTO `EXCEL`(`FILE_ID`, `연번`, `접수번호`, `성명`, `순번`, `실적종류`, `구분1`, `구분2`, `IF`, `JR`, `순위`, `저널수`, `발표년월`, `논문제목`, `저자수`, `참여역할`, `게제지명`, `본인점수`, `ISSN`, `인정여부`, `KRI검증여부`, `1차점수`, `검증점수`, `비고`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $mysqli->prepare($query);

for ($row = 2; $row <= $highestRow; ++$row) {
	for ($i = 0; $i <= 22; $i++)
		$value[$i] = $objWorksheet->getCellByColumnAndRow($i, $row)->getValue();

	$stmt->bind_param("ssssssssssssssssssssssss",
		$file_id,
		$value[0 ],
		$value[1 ],
		$value[2 ],
		$value[3 ],
		$value[4 ],
		$value[5 ],
		$value[6 ],
		$value[7 ],
		$value[8 ],
		$value[9 ],
		$value[10],
		$value[11],
		$value[12],
		$value[13],
		$value[14],
		$value[15],
		$value[16],
		$value[17],
		$value[18],
		$value[19],
		$value[20],
		$value[21],
		$value[22]
	);

	$stmt->execute();
}

$stmt->close();

$total_cnt = 0;
$sci_cnt = 0;
$scopus_cnt = 0;
$kri_cnt = 0;

$query = "SELECT count(1) as TOTAL_CNT, count(if(`구분1` in ('SSCI', 'SCI', 'SCIE'), 1, null)) as SCI_CNT, count(if(`구분1`='Scopus', 1, null)) as SCOPUS_CNT, count(if(`구분1`='한국연구재단등재지', 1, null)) as KCI_CNT FROM `EXCEL` WHERE `FILE_ID`='$file_id'";
if ($result = $mysqli->query($query)) {
	if ($row = $result->fetch_assoc()) {
		$total_cnt = $row['TOTAL_CNT'];
		$sci_cnt = $row['SCI_CNT'];
		$scopus_cnt = $row['SCOPUS_CNT'];
		$kri_cnt = $row['KCI_CNT'];
	}
	$result->free();
}

$etc_cnt = $total_cnt - ($sci_cnt + $scopus_cnt + $kri_cnt);

$query = "UPDATE `UPLOADED_FILE` SET `TOTAL_CNT`=?, `SCI_CNT`=?, `SCOPUS_CNT`=?, `KCI_CNT`=?, `ETC_CNT`=? WHERE `FILE_ID`=?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("iiiiis", $total_cnt, $sci_cnt, $scopus_cnt, $kri_cnt, $etc_cnt, $file_id);
$stmt->execute();
$stmt->close();

$mysqli->close();

header('Location: ../');
?>