<?php
/** Error reporting */
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);

require_once './phps/kri_api.php';

$agc_id = '131040';
$rschr_reg_no = '10206181';

$kri = new KriApi();
$kri->setAuthParameters($agc_id, $rschr_reg_no);
$title = "색채 인지와 언어 표현의 상관성(1)";
$year = intval('2012');
//$r_data = $kri->getRawData($title, $year, KriApi::KCI);
$r_data = $kri->getRawData($title, $year, KriApi::KCI);

var_dump($r_data);
?>