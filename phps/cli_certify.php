<?php
require_once './db_info.php';
require_once './kri_api.php';

if (PHP_SAPI !== 'cli')
	die("올바른 경로로 실행되지 않았습니다.");

if (count($argv) < 2)
	die("매개 변수가 올바르지 않습니다.");

$file_id = $argv[1];
$mod_divisor = $argv[2];
$mod_remainder = $argv[3];

/** Error reporting */
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PW, DB_DB);
$mysqli->set_charset("UTF8");

$query = "UPDATE `UPLOADED_FILE` SET STATE_CODE='02', CERT_START_TIME=now(), CERT_END_TIME=null WHERE FILE_ID='$file_id'";
$mysqli->query($query);

$query = "SELECT * FROM `EXCEL` WHERE STATE_CODE='00' AND `실적종류`='논문' AND FILE_ID='$file_id' AND mod(`연번`, $mod_divisor)=$mod_remainder";
//$query.= " LIMIT 0 , 30";

$kri = new KriApi();
$agc_id = '131040';
$rschr_reg_no = '10206181';
$kri->setAuthParameters($agc_id, $rschr_reg_no);

if ($result = $mysqli->query($query)) {
	echo date("Y-m-d H:i:s") . ' Select Excel' . PHP_EOL;

	$query = "UPDATE `UPLOADED_FILE` SET PROC_CNT=ifnull(PROC_CNT,0)+1, VERI_CNT=ifnull(VERI_CNT,0)+?, UNVERI_CNT=ifnull(UNVERI_CNT,0)+?, AVG_PROC_SEC=((ifnull(AVG_PROC_SEC,0)*ifnull(PROC_CNT,0))+?)/(ifnull(PROC_CNT,0)+1) WHERE FILE_ID=?";
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param('iiis', $veri_yn, $unveri_yn, $avg_time, $file_id);

	$query2 = "UPDATE `EXCEL` SET `STATE_CODE`=?,`SYS_논문제목`=?,`SYS_학술지명`=?,`SYS_참여자`=?,`SYS_발표년월`=?,`SYS_게제권`=?,`SYS_게재호`=?,`SYS_시작페이지`=?,`SYS_종료페이지`=?,`SYS_ISSN`=?,`SYS_발행처명`=?,`SYS_전체저자수`=?,`SYS_논문초록`=?, `SYS_검증ID`=?, `SYS_비고`=? WHERE `FILE_ID`=? AND `연번`=?";
	$stmt2 = $mysqli->prepare($query2);
	$stmt2->bind_param('ssssssssssssssssi', $state_code, $sys_title, $sys_journal, $sys_participants, $sys_ym, $sys_volumn, $sys_issue, $sys_startpage, $sys_endpage, $sys_issn, $sys_company, $sys_num, $sys_abstract, $sys_veriid, $sys_note, $file_id, $seq_no);

	while ($row = $result->fetch_assoc()) {
		$start_sec = microtime(true);
		$title = $row['논문제목'];
		$year = substr($row['발표년월'], 0, 4);
		$state_code = '01';
		$veri_yn = 0;
		$unveri_yn = 1;

		$sys_title = '';
		$sys_journal = '';
		$sys_participants = '';
		$sys_ym = '';
		$sys_volumn = '';
		$sys_issue = '';
		$sys_startpage = '';
		$sys_endpage = '';
		$sys_issn = '';
		$sys_company = '';
		$sys_num = '';
		$sys_abstract = '';
		$sys_veriid = '';
		$sys_note = '';
		$seq_no = $row['연번'];

		echo " 연번 " . $seq_no . PHP_EOL;
		$journal_level = '';
		if ($row['구분1'] == "SSCI" || $row['구분1'] == "SCI" || $row['구분1'] == "SCIE")
			$journal_level = KriApi::SCI;
		else if ($row['구분1'] == "Scopus")
			$journal_level = KriApi::SCOPUS;
		else
			$journal_level = KriApi::KCI;
		$arr_data = $kri->verify($title, $year, $journal_level);

		if (count($arr_data) == 18) {
			$state_code = '03';
			$veri_yn = 1;
			$unveri_yn = 0;

			$sys_title = $arr_data[0];
			$sys_journal = $arr_data[1];
			$sys_participants = $arr_data[2];
			$sys_ym = $arr_data[3];
			$sys_volumn = $arr_data[5];
			$sys_issue = $arr_data[6];
			$sys_startpage = $arr_data[7];
			$sys_endpage = $arr_data[8];
			$sys_issn = $arr_data[9];
			$sys_company = $arr_data[11];
			$sys_num = $arr_data[12];
			$sys_abstract = $arr_data[13];
			$sys_veriid = $arr_data[14];
			$sys_note = $arr_data[17];
		}

		$kri->resetRandomIp();
		$end_sec = microtime(true);
		$avg_time = $end_sec - $start_sec + 0.1;

		$stmt->execute();
		$stmt2->execute();
	}
	$stmt->close();
	$stmt2->close();
	$result->free();
}

$query = "UPDATE `UPLOADED_FILE` SET STATE_CODE='03', CERT_END_TIME=now() WHERE FILE_ID='$file_id'";
$mysqli->query($query);

$mysqli->close();
?>