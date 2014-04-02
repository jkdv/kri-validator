<?php
$pattern = $_POST['pattern'];
$data = $_POST['data'];

preg_match_all($pattern, $data, $matches);
var_dump($matches);
?>