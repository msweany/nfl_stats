<?php
header('Content-Type: application/json');
include 'connect.php';
# check if game_synced timestamp is 7 days or less ago

$sql = "SELECT game_id,week,season,date,home,away FROM games WHERE game_status = 'final' AND (game_synced = 0 OR date >= CURDATE() - INTERVAL 7 DAY)";
$result = $mysqli->query($sql);
if ($result->num_rows > 0) {
    $games = array();
    while($row = $result->fetch_assoc()) {
        $games[] = $row;
    }
    $output = array(
        'status' => 100, 
        'message' => 'Games found', 
        'data' => $games
    );
} else {
    $output = array(
        'status' => 200,
        'message' => 'No games found'
    );
}
echo json_encode($output);