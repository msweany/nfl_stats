<?php
$username=getenv('DB_USER');
$password=getenv('DB_PASS');
$database='nfl_data';
$server='localhost';

$mysqli = new mysqli($server, $username, $password, $database);
$mysqli->set_charset("utf8");
?>
