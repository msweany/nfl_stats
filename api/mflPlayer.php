<?php
header('Content-Type: application/json');
include 'functions.php';

$output = $_POST;
if($_POST['action'] == 'check'){
    $data = checkMflPlayers($_POST['limit']);
}
/*
$year = date('Y');

$players = file_get_contents('https://api.myfantasyleague.com/'.$year.'/export?TYPE=playerProfile&P=14836&JSON=1');

// Decode the JSON file
$json_data = json_decode($players,true);

print_r($json_data);
//foreach($json_data['injuries']['injury'] AS $p){
    //mflInjury($p);
//}
*/
$output = (object)array(
    'message' => date("m/d/Y g:i a", time()),
    'status' => '100',
    'data' => $data
);
echo json_encode($output);