<?php
require_once './db_info.php';
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PW, DB_DB);
$mysqli->set_charset("UTF8");
$query = "SELECT *, FUNC_CODE('CC0001', STATE_CODE) AS STATE_NM, if(CERT_END_TIME is null, timediff(now(), CERT_START_TIME), timediff(CERT_END_TIME, CERT_START_TIME)) AS ELAPSED_TIME, ifnull(CERT_END_TIME, timestampadd(SECOND, AVG_PROC_SEC*(TOTAL_CNT-PROC_CNT), now())) AS ESTIMATED_TIME FROM `UPLOADED_FILE` ORDER BY FILE_ID DESC";

$doc = new DOMDocument("1.0");
$doc->formatOutput = true;
$root = $doc->createElement("file_list");
$root = $doc->appendChild($root);

if ($result = $mysqli->query($query)) {
	$finfo = $result->fetch_fields();
	while ($row = $result->fetch_assoc()) {
		$list = $doc->createElement("file");
		$list = $root->appendChild($list);

		foreach ($finfo as $val) {
			$column = $val->name;
			$value = $row[$column];

			if (strpos($column, 'SERVER') !== FALSE)
				continue;

			$node = $doc->createElement($column);
			$node = $list->appendChild($node);
			$value = $doc->createTextNode($value);
			$value = $node->appendChild($value);
		}
	}

	$result->free();
}

$mysqli->close();
echo $doc->saveXML();
?>