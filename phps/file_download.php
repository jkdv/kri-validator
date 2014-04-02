<?php
require_once './db_info.php';
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PW, DB_DB);
$mysqli->set_charset("UTF8");
$query = "SELECT * FROM `UPLOADED_FILE` WHERE FILE_ID='" . $_GET['id'] . "'";

if ($result = $mysqli->query($query)) {
	if ($row = $result->fetch_assoc()) {
		$server_path = $row['SERVER_FILE_NAME'];
		$org_file_name = $row['ORG_FILE_NAME'];
		$org_file_name = str_replace(' ', '_', $org_file_name);

		if(eregi("(MSIE 5.5|MSIE 6.0)", $HTTP_USER_AGENT)) {
			Header("Content-type: application/octet-stream");
			Header("Content-Length: " . filesize($server_path));
			Header("Content-Disposition: inline; filename=" . $org_file_name);
			Header("Content-Transfer-Encoding: binary");
			Header("Pragma: no-cache");
			Header("Expires: 0");
		} else {
			Header("Content-type: file/unknown");
			Header("Content-Length: " . filesize($server_path));
			Header("Content-Disposition: inline; filename=" . $org_file_name);
			Header("Content-Description: PHP3 Generated Data");
			Header("Pragma: no-cache");
			Header("Expires: 0");
		} 

		if (is_file($server_path)) {
			$fp = fopen($server_path, "r"); 
			if (!fpassthru($fp)) // 서버부하를 줄이려면 print 나 echo 또는 while 문을 이용한 기타 보단 이방법이... 
				fclose($fp);
		} else {
			die("해당 파일이나 경로가 존재하지 않습니다.");
		} 
	}
	$result->free();
}

$mysqli->close();
?>