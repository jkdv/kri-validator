<?php
exec('ps -ef | grep php | grep php | grep -v grep | awk \'{ printf("%s\\n", $2); }\'', $result);
foreach ($result as $key => $value) {
	echo "$key : $value" . PHP_EOL;
}
?>