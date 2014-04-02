<?php
require_once './db_info.php';
$file_id = $_POST['id'];

/** Error reporting */
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);

$divisor = 1;
$path = '../log/'.$file_id;

if (!is_dir($path)) {
	mkdir($path, 0755, true);
}

$ret = system(PHP_PATH . "php -f ./cli_certify.php -- $file_id $divisor 0 >> $path/result_0.txt &");
for ($i=1; $i < $divisor; $i++) { 
	system(PHP_PATH . "php -f ./cli_certify.php -- $file_id $divisor $i >> $path/result_".$i.".txt &");
}

if ($ret !== FALSE)
	echo '검증이 시작되었습니다.';
else
	echo '에러가 발생하여 검증이 시작되지 않았습니다.';
?>