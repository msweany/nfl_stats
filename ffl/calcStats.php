<?php
// change to enable/disable debug mode
$debug = true;

// set header
if (!$debug){
    header('Content-type: application/json');
}
# get the game id and save all the info for it
$game_id = $_GET['game'];
$game_info = getGame($game_id);
if($debug){
    echo '<pre>';
    print_r($game_info);
    echo '</pre>';
}
include '../api/connect.php';

######################################  functions  ######################################
function getGame($game_id){
    include '../api/connect.php';
    $sql = "SELECT * FROM games WHERE game_id = '".$game_id."'";
    $result = $mysqli->query($sql);
    while ($row = $result->fetch_assoc()) {
        $game = $row;
    }
    $mysqli->close();
    return $game;
}
function addStatsOff($s){
    include '../api/connect.php';
    $sql = "INSERT INTO calc_data_off VALUES(null,
        '".$s['game_id']."',
        '".$s['team']."',
        '".$s['opponent']."',
        '".$s['position']."',
        '".$s['pass_yds']."',
        '".$s['pass_td']."',
        '".$s['interception']."',
        '".$s['rush_yds']."',
        '".$s['rush_tds']."',
        '".$s['rec_yds']."',
        '".$s['rec_td']."', 
        '".$s['rec_rec']."',
        '".$s['fmb']."',
        '".$s['fmb_lost']."',
        '".$s['pass_two_point']."',
        '".$s['rush_two_point']."',
        '".$s['rec_two_point']."'
    ) ON DUPLICATE KEY UPDATE
        pass_yds = '".$s['pass_yds']."',
        pass_td = '".$s['pass_td']."',
        interception = '".$s['interception']."',
        rush_yds = '".$s['rush_yds']."',
        rush_tds = '".$s['rush_tds']."',
        rec_yds = '".$s['rec_yds']."',
        rec_td = '".$s['rec_td']."',
        rec_rec = '".$s['rec_rec']."',
        fmb = '".$s['fmb']."',
        fmb_lost = '".$s['fmb_lost']."',
        pass_two_point = '".$s['pass_two_point']."',
        rush_two_point = '".$s['rush_two_point']."',
        rec_two_point = '".$s['rec_two_point']."'
    ";
    if($debug){ echo $sql."<br/ >"; }
    $result = $mysqli->query($sql);
    $mysqli->close();
}

function addStatsDef($s){
    include '../api/connect.php';
    $sql = "INSERT INTO calc_data_def VALUES(null,
        '".$s['game_id']."',
        '".$s['team']."',
        '".$s['opponent']."',
        '".$s['position']."',
        '".$s['interception']."',
        '".$s['int_yds']."',
        '".$s['int_td']."',
        '".$s['solo_tackles']."',
        '".$s['tackles_for_loss']."',
        '".$s['qb_hits']."',
        '".$s['fum_rec']."',
        '".$s['fum_td']."',
        '".$s['forced_fum']."'
    ) ON DUPLICATE KEY UPDATE
        interception = '".$s['interception']."',
        int_yds = '".$s['int_yds']."',
        int_td = '".$s['int_td']."',
        solo_tackles = '".$s['solo_tackles']."',
        tackles_for_loss = '".$s['tackles_for_loss']."',
        qb_hits = '".$s['qb_hits']."',
        fum_rec = '".$s['fum_rec']."',
        fum_td = '".$s['fum_td']."',
        forced_fum = '".$s['forced_fum']."'
    ";
    if($debug){ echo $sql."<br/ >"; }
    $result = $mysqli->query($sql);
    $mysqli->close();
}

function addStatsTeamDef($s){
    include "../api/connect.php";
    $sql = "INSERT INTO calc_data_def_team VALUES(null,
        '".$s['game_id']."',
        '".$s['team']."',
        '".$s['opponent']."',
        '".$s['points_against']."',
        '".$s['safety']."',
        '".$s['blocked_fg']."',
        '".$s['blocked_punt']."',
        '".$s['blocked_fg_td']."',
        '".$s['blocked_punt_td']."'
    ) ON DUPLICATE KEY UPDATE
        points_against = '".$s['points_against']."',
        safety = '".$s['safety']."',
        blocked_fg = '".$s['blocked_fg']."',
        blocked_punt = '".$s['blocked_punt']."',
        blocked_fg_td = '".$s['blocked_fg_td']."',
        blocked_punt_td = '".$s['blocked_punt_td']."'
    ";
    if($debug){ echo $sql."<br/ >"; }
    $result = $mysqli->query($sql);
    $mysqli->close();
}

######################################  start offense  ######################################
# get offense data
$sql = " SELECT *, t1.team as team FROM player_stats_offense t1
    LEFT JOIN players t2 ON t1.player_id=t2.source_id
    WHERE t1.game_id ='$game_id'";
$result = $mysqli->query($sql);
while($row = $result->fetch_assoc()) {
    // combine the results by team and poisition and add up all the stats per position
    $players[$row['team']][$row['position']][] = $row;
    
}

// add up all the stats per position per team
foreach($players as $team => $positions) {
    foreach($positions as $position => $players) {
        if($position == 'FB'){ $position = 'RB'; }
        foreach($players as $player) {
            //print_r($player);
            $stats[$team][$position]['pass_yds'] += $player['pass_yds'];
            $stats[$team][$position]['pass_td'] += $player['pass_td'];
            $stats[$team][$position]['interception'] += $player['interception'];
            $stats[$team][$position]['rush_yds'] += $player['rush_yds'];
            $stats[$team][$position]['rush_tds'] += $player['rush_tds'];
            $stats[$team][$position]['rec_yds'] += $player['rec_yds'];
            $stats[$team][$position]['rec_td'] += $player['rec_td'];
            $stats[$team][$position]['rec_rec'] += $player['rec_rec'];
            $stats[$team][$position]['fmb'] += $player['fmb'];
            $stats[$team][$position]['fmb_lost'] += $player['fmb_lost'];
            $stats[$team][$position]['pass_two_point'] += $player['pass_two_point'];
            $stats[$team][$position]['rush_two_point'] += $player['rush_two_point'];
            $stats[$team][$position]['rec_two_point'] += $player['rec_two_point'];
        }
        $stats[$team][$position]['game_id']=$game_id;
        $stats[$team][$position]['team']=$team;
        // determine the "other" team
        if($team == $game_info['home']) {
            $stats[$team][$position]['opponent']=$game_info['away'];
        } else {
            $stats[$team][$position]['opponent']=$game_info['home'];
        }
    }
}

foreach($stats[$game_info['away']] as $k => $s){
    $s['position'] = $k;
    if($debug){ print_r($s); }
    addStatsOff($s);
}
foreach($stats[$game_info['home']] as $k => $s){
    $s['position'] = $k;
    if($debug){ print_r($s); }
    addStatsOff($s);
}

$players = array();

######################################  start defense  ######################################
$sql = "SELECT *, t1.team as team FROM player_stats_defense t1
    LEFT JOIN players t2 ON t1.player_id=t2.source_id
    WHERE t1.game_id = '$game_id'";
$result = $mysqli->query($sql);
while($row = $result->fetch_assoc()) {
    // combine the results by team and poisition and add up all the stats per position
    if(($row['position'] == 'DE') || ($row['position'] == 'DT') || ($row['position'] == 'NT')) { $row['position'] = 'DL'; }
    if(($row['position'] == 'CB') || ($row['position'] == 'SS') || ($row['position'] == 'FS')) { $row['position'] = 'DB'; }
    $players[$row['team']][$row['position']][] = $row;
    
}

// add up all the stats per position per team
foreach($players as $team => $positions) {
    foreach($positions as $position => $players) {
        foreach($players as $player) {
            //print_r($player);
            $statsD[$team][$position]['interception'] += $player['interception'];
            $statsD[$team][$position]['int_yds'] += $player['int_yds'];
            $statsD[$team][$position]['int_td'] += $player['int_td'];
            $statsD[$team][$position]['solo_tackles'] += $player['solo_tackles'];
            $statsD[$team][$position]['tackles_for_loss'] += $player['tackles_for_loss'];
            $statsD[$team][$position]['qb_hits'] += $player['qb_hits'];
            $statsD[$team][$position]['fum_rec'] += $player['fum_rec'];
            $statsD[$team][$position]['fum_td'] += $player['fum_td'];
            $statsD[$team][$position]['forced_fum'] += $player['forced_fum'];
        }
        $statsD[$team][$position]['game_id']=$game_id;
        $statsD[$team][$position]['team']=$team;
        // determine the "other" team
        if($team == $game_info['home']) {
            $statsD[$team][$position]['opponent']=$game_info['away'];
        } else {
            $statsD[$team][$position]['opponent']=$game_info['home'];
        }
    }
}

foreach($statsD[$game_info['away']] as $k => $s){
    $s['position'] = $k;
    if($debug){ print_r($s); }
    addStatsDef($s);
}
foreach($statsD[$game_info['home']] as $k => $s){
    $s['position'] = $k;
    if($debug){ print_r($s); }
    addStatsDef($s);
}

######################################  start team D  ######################################
$home = array();
$home_team = '';
$away = array();
$away_team = '';

# get basic game info
$sql = "SELECT * FROM games WHERE game_id='".$game_id."'"; 
$result = $mysqli->query($sql);
while ($row = $result->fetch_assoc()) {
    // create a home and away array
    if($row['home'] || $row['yards_h'] || $row['points_h'] || $row['turn_overs_h']){
        $home['team'] = $row['home'];
        $home['points_against'] = $row['points_a'];  
        $home_team = $row['home'];    
    }
    if($row['away'] || $row['yards_a'] || $row['points_a'] || $row['turn_overs_a']){
        $away['team'] = $row['away'];
        $away['points_against'] = $row['points_h'];
        $away_team = $row['away'];
    }
}

# get more game info
$sql = "SELECT * FROM game_stats WHERE game_id='".$game_id."'";    
$result = $mysqli->query($sql);
while ($row = $result->fetch_assoc()) {
    if($debug){
        echo '<pre>';
        print_r($row);
        echo '</pre>';
    }
    if($row['team'] == $away['team']){
        $away['safety'] = $row['safeties'];
        $away['blocked_fg'] = $row['blocked_fg'];
        $away['blocked_punt'] = $row['blocked_punt'];
        $away['blocked_fg_td'] = $row['blocked_fg_td'];
        $away['blocked_punt_td'] = $row['blocked_punt_td'];
    } else {
        $home['safety'] = $row['safeties'];
        $home['blocked_fg'] = $row['blocked_fg'];
        $home['blocked_punt'] = $row['blocked_punt'];
        $home['blocked_fg_td'] = $row['blocked_fg_td'];
        $home['blocked_punt_td'] = $row['blocked_punt_td'];
    }  
}

$home['game_id'] = $game_id;
$home['opponent'] = $away_team;
if($debug){ print_r($home); }
addStatsTeamDef($home);

$away['game_id'] = $game_id;
$away['opponent'] = $home_team;
if($debug){ print_r($away); }
addStatsTeamDef($away);
######################################  return  ######################################
$output['status']=100;
$output['message']='success';
$output['data']=$game_id;

echo json_encode($output);