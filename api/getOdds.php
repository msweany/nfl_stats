<?php
header('Content-Type: application/json');
include 'connect.php';
include 'functions.php';

$output = array();
$count = 0;
$sql = "SELECT * FROM games WHERE game_status = 'upcoming' ORDER BY date LIMIT 16";
$result = $mysqli->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $data = getOdds($row['game_id']);
        $data['favorite'] = teamMap($data['favorite']);
        // if $data favorite is not null save it
        if ($data['favorite'] != null) {
            saveOdds($data);
            $count++;
        }
    }
} 
if($count > 0) {
    // sends to app.fozzil.net/common/receive-odds.php
    sendOdds(time());
    $output['count'] = $count;
    $output['status'] = 100;  
    $output['message'] = 'odds updated';  
} else {
    $output['message'] = 'No new odds';
    $output['status'] = 200;
}

echo json_encode($output);