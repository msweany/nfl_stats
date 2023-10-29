<?php
header('Content-Type: application/json');

# lets get the data for this week
include '../api/connect.php';
$sql = "SELECT * FROM rank_summary";
$result = $mysqli->query($sql);
while($row = $result->fetch_assoc()){
    $ranks[] = $row;
}

$test = array('token' => getenv('FFL_TOKEN'),'ranks' => $ranks);

$url = 'http://app.fozzil.net/ffl/common/receive-ranks.php';
$ch = curl_init($url);


curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$result = curl_exec($ch);
curl_close($ch);

# now we have the JSON in $result, let's decode it into an array
$data = json_decode($result, true);

print_r($data);


