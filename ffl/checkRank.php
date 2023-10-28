<?php
header('Content-type: application/json');

$output = array();
$games_saved = array();
$games_to_save = array();

$games_saved = array();
include '../api/connect.php';
include 'functions.php';
# check which games have been saved already
$sql = "SELECT DISTINCT(game_id) FROM rank_points";
$result = $mysqli->query($sql);
while($row = $result->fetch_assoc()) {
    $games_saved[] = $row['game_id'];
}

# which season are we in?
$sql = "SELECT MAX(season) AS season FROM games";
$result = $mysqli->query($sql);
while($row = $result->fetch_assoc()){
    $season = $row['season'];
}

$i=0;
# loop through all the games and check if they have been saved
$sql = "SELECT * FROM games WHERE season ='$season' AND game_synced != 0 ORDER BY season DESC, week ASC";
$result = $mysqli->query($sql);
while($row = $result->fetch_assoc()) {
    $game_id = $row;
    if (!in_array($game_id['game_id'], $games_saved)) {
        $games_to_save[$row['week']][] = $game_id;
        $i++;
    }
    
}
$mysqli->close();

$output['current_season'] = $season;
$output['games_to_save'] = $games_to_save;
$output['count_weeks'] = count($games_to_save);
$output['count_total'] = $i;
echo json_encode($output);