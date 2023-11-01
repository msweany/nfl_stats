<?php
header('Content-Type: application/json');

# lets get the data for this week
include '../api/connect.php';
$sql = "SELECT t1.points,t2.week,t2.season,t3.mfl_id FROM `players_points` t1 
    LEFT JOIN games t2 ON t1.game_id=t2.game_id
    LEFT JOIN players t3 ON t1.player_id=t3.id
    WHERE t2.season='2023'";
$result = $mysqli->query($sql);
while($row = $result->fetch_assoc()){
    $ranks[] = $row;
}

$test = array('token' => getenv('FFL_TOKEN'),'points' => $ranks);

$url = 'http://app.fozzil.net/ffl/common/receive-points.php';
$ch = curl_init($url);


curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$result = curl_exec($ch);
curl_close($ch);

# now we have the JSON in $result, let's decode it into an array
$data = json_decode($result, true);

print_r($data);