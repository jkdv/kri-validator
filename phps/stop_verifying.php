<?php
require_once './db_info.php';
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);

$file_id = $_POST['id'];
$command = 'ps -ef | grep php | grep '.$file_id.' | grep -v grep | awk \'{ printf("%s\\n", $2); }\'';
exec($command, $output);

if (count($output) > 0 && $output[0]) {
	foreach ($output as $value) {
		system('kill -9 '.$value);
	}
	echo "검증이 중단 되었습니다.";
} else {
	echo "검증이 진행 중이지 않습니다. 검증 상태를 '중지됨'으로 변경하겠습니다.";
}

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PW, DB_DB);
$mysqli->set_charset("UTF8");
$query = "UPDATE `UPLOADED_FILE` SET STATE_CODE='04', CERT_END_TIME=now() WHERE FILE_ID='$file_id'";
$mysqli->query($query);
$mysqli->close();
?>