<?php
/** Error reporting */
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);

$file_id = $_POST['id'];

require_once './db_info.php';
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PW, DB_DB);
$mysqli->set_charset("UTF8");
$query = "SELECT * FROM `UPLOADED_FILE` WHERE FILE_ID='" . $file_id . "'";

if ($result = $mysqli->query($query)) {
	if ($row = $result->fetch_assoc()) {
		$server_path = $row['SERVER_FILE_NAME'];

		if (!is_file($server_path)) {
			$result->free();
			$mysqli->close();
			die("해당 파일이나 경로가 존재하지 않습니다.");
		}

		unlink($server_path);

		$query = "DELETE FROM `UPLOADED_FILE` WHERE FILE_ID='" . $file_id . "'";
		$mysqli->query($query);
		$query = "DELETE FROM `EXCEL` WHERE FILE_ID='" . $file_id . "'";
		$mysqli->query($query);
	}
	$result->free();
}
$mysqli->close();
echo '파일이 삭제되었습니다.';
?>