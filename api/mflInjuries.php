<?php
header('Content-Type: application/json');
include 'functions.php';
prepTable('mfl_injuries');
$year = date('Y');

$players = file_get_contents('https://api.myfantasyleague.com/'.$year.'/export?TYPE=injuries&W=&JSON=1');

// Decode the JSON file
$json_data = json_decode($players,true);

foreach($json_data['injuries']['injury'] AS $p){
    mflInjury($p);
}

$output = (object)array(
    'message' => date("m/d/Y g:i a", time()),
    'status' => '100'
);
echo json_encode($output);