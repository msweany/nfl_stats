<?php
// json header
header('Content-type: application/json');
include 'connect-ffl.php';
// get the game from the URL
$game = $_GET['game'];
$game = '202310190nor';

// load the scoring systems O = offense, D = defense, R = return, K = kicking 

$array = array(
    'O' => 'player_stats_offense',
    'K' => 'player_stats_kicking',
    'R' => 'player_stats_return',
);

# array to hold final player data
$player_points = array();
$this_player = array();

foreach($array as $system => $table){
    $scoring = array();
    include 'connect-ffl.php';
    $sql = "SELECT category,points FROM scoring WHERE position = '".$system."'";
    $result = $mysqli->query($sql);
    while ($row = $result->fetch_assoc()) {
        $scoring[$row['category']] = $row['points'];
    }
    $mysqli->close();
    //print_r($scoring);

    include '../api/connect.php';
    $pv = array(); 
    // let's get some players
    $sql = "SELECT * FROM ".$table." WHERE game_id = '".$game."'";
    $result = $mysqli->query($sql);
    while ($row = $result->fetch_assoc()) {
        $offense[] = $row;
    }
    $mysqli->close();
    //print_r($offense);
     
    foreach($offense as $pk => $pv){
        $points = 0;
        
        foreach($pv as $k => $v){
            if(array_key_exists($k, $scoring)){
                $points = $points + ($v * $scoring[$k]);
                $pv['points_'.$k]=($v * $scoring[$k]);
            }
        }
        $pv['points'] = $points;
        //print_r($pv);
        $this_player['points'] = $points;
        $this_player['player_id'] = $pv['player_id'];
        $this_player['game_id'] = $pv['game_id'];
        // check to see if this player is already in the array
        if(array_key_exists($pv['player_id'], $player_points)){
            $player_points[$pv['player_id']]['points'] = $player_points[$pv['player_id']]['points'] + $points;
        } else {
            $player_points[$pv['player_id']] = $this_player;
        }
    } 
}

// get defensive stuff
$home = array();
$away = array();
include '../api/connect.php';
# get basic game info
$sql = "SELECT * FROM games WHERE game_id='".$game."'"; 
$result = $mysqli->query($sql);
while ($row = $result->fetch_assoc()) {
    print_r($row);
    // create a home and away array
    if($row['home'] || $row['yards_h'] || $row['points_h'] || $row['turn_overs_h']){
        $home['team'] = $row['home'];
        $home['points_against'] = $row['points_a'];        
    }
    if($row['away'] || $row['yards_a'] || $row['points_a'] || $row['turn_overs_a']){
        $away['team'] = $row['away'];
        $away['points_against'] = $row['points_h'];
    }
}



/*
        # need safety
        # need blocked kick
        # need td's
*/

# get more game info
$sql = "SELECT * FROM game_stats WHERE game_id='".$game."'";    
$result = $mysqli->query($sql);
while ($row = $result->fetch_assoc()) {
    print_r($row);
    if($row['team'] == $away['team']){
        $home['interception'] = $row['pass_int'];
        $home['fum_recovery'] = $row['fumbles_lost'];
        $home['sack'] = $row['sacked'];
    } else {
        $away['interception'] = $row['pass_int'];
        $away['fum_recovery'] = $row['fumbles_lost'];
        $away['sack'] = $row['sacked'];
    }
    
}


// get defensive stats
$sql = "SELECT * FROM player_stats_defense WHERE game_id='".$game."'";
$result = $mysqli->query($sql);
while ($row = $result->fetch_assoc()) {
    print_r($row);
    
}


print_r($home);
print_r($away);
//print_r($player_points);
