<?php
$username=getenv('DB_USER');
$password=getenv('DB_PASS');
$database='ffl';
$server='localhost';

$mysqli = new mysqli($server, $username, $password, $database);
$mysqli->set_charset("utf8");
?>
