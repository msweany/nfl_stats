<?php
// json header
header('Content-type: application/json');
include 'connect-ffl.php';
// get the game from the URL
$game = $_GET['game'];
$game = '202310190nor'; #  carr 2 pt conversion pass
$game = '202310080pit'; # pit safety and blicked punt
$game = '202309170nwe'; # NE gets blocked FG
$game = '202201020rav'; # bal pick/six
$game = '202309100nyg'; # nyg blocked FG for TD
$game = '202112180clt'; # ind blocked punt for TD
$game = '202310220chi'; # chi gets xp blocked

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

################################# Defensive scoring ########################################
// get defensive stuff
$scoring = array();
include 'connect-ffl.php';
$sql = "SELECT category,points FROM scoring WHERE position = 'D'";
$result = $mysqli->query($sql);
while ($row = $result->fetch_assoc()) {
    $scoring[$row['category']] = $row['points'];
}
$mysqli->close();
print_r($scoring);

$home = array();
$home_team = '';
$away = array();
$away_team = '';
include '../api/connect.php';
# get basic game info
$sql = "SELECT * FROM games WHERE game_id='".$game."'"; 
$result = $mysqli->query($sql);
while ($row = $result->fetch_assoc()) {
    //print_r($row);
    // create a home and away array
    if($row['home'] || $row['yards_h'] || $row['points_h'] || $row['turn_overs_h']){
        $home['team'] = $row['home'];
        $home_team = $row['home'];
        $home['points_against'] = $row['points_a'];        
    }
    if($row['away'] || $row['yards_a'] || $row['points_a'] || $row['turn_overs_a']){
        $away['team'] = $row['away'];
        $away['points_against'] = $row['points_h'];
        $away_team = $row['away'];
    }
}


# get more game info
$sql = "SELECT * FROM game_stats WHERE game_id='".$game."'";    
$result = $mysqli->query($sql);
while ($row = $result->fetch_assoc()) {
    //print_r($row);
    if($row['team'] == $away['team']){
        $home['interception'] = $row['pass_int'];
        $home['fum_recovery'] = $row['fumbles_lost'];
        $home['sack'] = $row['sacked'];
        $away['safety'] = $row['safeties'];
        $away['blocked_fg'] = $row['blocked_fg'];
        $away['blocked_punt'] = $row['blocked_punt'];
        $away['blocked_fg_td'] = $row['blocked_fg_td'];
        $away['blocked_punt_td'] = $row['blocked_punt_td'];
    } else {
        $away['interception'] = $row['pass_int'];
        $away['fum_recovery'] = $row['fumbles_lost'];
        $away['sack'] = $row['sacked'];
        $home['safety'] = $row['safeties'];
        $home['blocked_fg'] = $row['blocked_fg'];
        $home['blocked_punt'] = $row['blocked_punt'];
        $home['blocked_fg_td'] = $row['blocked_fg_td'];
        $home['blocked_punt_td'] = $row['blocked_punt_td'];
    }  
}

$home['def_td'] = 0;
$away['def_td'] = 0;
// get defensive stats
$sql = "SELECT * FROM player_stats_defense WHERE game_id='".$game."'";
$result = $mysqli->query($sql);
while ($row = $result->fetch_assoc()) {
    // add up all TD's for each team - flip them because the other team got the 
    if($row['team'] == $home_team){
        if($row['int_td']){
            $home['def_td'] = $home['def_td'] + $row['int_td'];
        }
        if($row['fum_td']){
            $home['def_td'] = $home['def_td'] + $row['fum_td'];
        }
    } else {
        if($row['int_td']){
            $away['def_td'] = $away['def_td'] + $row['int_td'];
        }
        if($row['fum_td']){
            $away['def_td'] = $away['def_td'] + $row['fum_td'];
        }
    }
    
}

# need to add blocked TD (both kick and punt)

print_r($home);
print_r($away);
//print_r($player_points);
