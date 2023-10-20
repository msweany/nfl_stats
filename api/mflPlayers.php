<?php
header('Content-Type: application/json');
# start the indexer
shell_exec("python3 sphinx.py start");

include 'connect.php';
include 'functions.php';
$year = date('Y');

$players = file_get_contents('https://api.myfantasyleague.com/'.$year.'/export?TYPE=players&JSON=1');

// Decode the JSON file
$json_data = json_decode($players,true);

# create array to hold failed players
$failed = array();
foreach($json_data['players']['player'] AS $p){
    $fail = mflMatch($p);
    if($fail != null){
        $failed[] = $fail;
    }
}

# stop the indexer
shell_exec("python3 sphinx.py stop");

$output = (object)array(
    'message' => date("m/d/Y g:i a", time()),
    'status' => '100',
    'data' => $failed
);
echo json_encode($output);