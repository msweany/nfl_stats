<?php
header('Content-type: application/json');

$output = array();
$games_saved = array();
$games_to_save = array();

include '../api/connect.php';
# check which games have been saved already
$sql = "SELECT DISTINCT(game_id) FROM players_points";
$result = $mysqli->query($sql);
while($row = $result->fetch_assoc()) {
    $games_saved[] = $row['game_id'];
}


# loop through all the games and check if they have been saved
$sql = "SELECT * FROM games";
$result = $mysqli->query($sql);
while($row = $result->fetch_assoc()) {
    $game_id = $row;
    if (!in_array($game_id['game_id'], $games_saved)) {
        $games_to_save[] = $game_id;
    }
}
$mysqli->close();

$output['games_to_save'] = $games_to_save;
$output['count'] = count($games_to_save);
echo json_encode($output);