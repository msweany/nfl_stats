<?php
header('Content-Type: application/json');
include 'connect.php';
include 'functions.php';

if(isset($_GET['year'])){
    $year = $_GET['year'];
}else{
    $year = date("Y");
}

# lets try to use curl to get the JSON from our functions
$url = "https://ffl-stuff.azurewebsites.net/api/getSchedule?code=".getenv('FFL_API_KEY')."&year=".$year;
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$result = curl_exec($ch);
curl_close($ch);
# now we have the JSON in $result, let's decode it into an array
$games = json_decode($result, true);

$output = array();
$failed = false;
# now we have an array of games, let's format and print them out
$output['data'] = $games['games'];

$i=1;
foreach($games['games'] as $game){
    # if the week is null, skip this row
    if($game['week'] == null){
        continue;
    }
    # lets populate the teams table first
    # get the home team
    $home = teamAlt($game['at']);
    if($game['winner'] == $home){
        $away = $game['loser'];
        $away = teamAlt($away);
    }else{
        $away = $game['winner'];
        $away = teamAlt($away);
        $home = $game['loser'];
        $home = teamAlt($home);
    }
    $sql = "INSERT INTO teams VALUES(null,'$home','','','','') ON DUPLICATE KEY UPDATE team='$home'";
    $mysqli->query($sql);
    # if the statement failed, print that
    if($mysqli->error){
        $failed = true;
        $output['error_sql'][] = $sql;
    }
    $sql = "INSERT INTO teams VALUES(null,'$away','','','','') ON DUPLICATE KEY UPDATE team='$away'";
    $mysqli->query($sql);
    
    # if the statement failed, print that
    if($mysqli->error){
        $failed = true;
        $output['error_sql'][] = $sql;
    }
    $i++;

    # convert playoffs to week
    $game['week'] = convertPlayoffs2Week($year,$game['week']);
    # get the home team
    $home = teamMap($game['at']);
    if(teamMap($game['winner']) == $home){
        $points_h = $game['points_w'];
        $yards_h = $game['yards_w'];
        $to_h = $game['to_w'];
        $points_a = $game['points_l'];
        $yards_a = $game['yards_l'];
        $to_a = $game['to_l'];
        $away = $game['loser'];
        $away = teamMap($away);
    }else{
        $points_h = $game['points_l'];
        $yards_h = $game['yards_l'];
        $to_h = $game['to_l'];
        $points_a = $game['points_w'];
        $yards_a = $game['yards_w'];
        $to_a = $game['to_w'];
        $away = $game['winner'];
        $away = teamMap($away);
        $home = $game['loser'];
        $home = teamMap($home);
    }
    $game['winner'] = teamMap($game['winner']);
    if($game['game_status'] == "final"){
        $sql = "INSERT INTO games 
        VALUES(null,'".$game['game_id']."','".$game['week']."','".$game['season']."','".$game['day']."','".$game['date']."','".$game['time']."','".$game['game_status']."','".$game['winner']."',
        '$home','$points_h','$yards_h','$to_h','$away','$points_a','$yards_a','$to_a','0') 
        ON DUPLICATE KEY UPDATE week='".$game['week']."',season='".$game['season']."',day='".$game['day']."',date='".$game['date']."',
        time='".$game['time']."',game_status='".$game['game_status']."',winner='".$game['winner']."',home='$home',points_h='$points_h',
        yards_h='$yards_h',turn_overs_h='$to_h',away='$away',points_a='$points_a',yards_a='$yards_a',turn_overs_a='$to_a'";
    }else{
        $sql = "INSERT INTO games 
        VALUES(null,'".$game['game_id']."','".$game['week']."','".$game['season']."','".$game['day']."','".$game['date']."','".$game['time']."','".$game['game_status']."','',
        '$home','0','0','0','$away','0','0','0','0')
        ON DUPLICATE KEY UPDATE week='".$game['week']."',season='".$game['season']."',day='".$game['day']."',date='".$game['date']."',
        time='".$game['time']."',game_status='".$game['game_status']."',home='$home',away='$away'";
    }
    
    $mysqli->query($sql);
    # if the statement failed, print that
    if($mysqli->error){
        $failed = true;
        $output['error_sql'][] = $sql;
        //print $sql.'<br />';
    }
}

$output['status'] = 100;
echo json_encode($output);