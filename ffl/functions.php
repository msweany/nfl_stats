<?php
################################### common functions ###################################
function printPretty($array){
    print "<pre>";
    print_r($array);
    print "</pre>";
}
function printDebug($array,$message=""){
    print "############################################## ".$message." ##############################################<br />";
    print "<pre>";
    print_r($array);
    print "</pre>";
}
function printDebugSQL($sql,$message=""){
    print "############################################## ".$message." ##############################################<br />";
    print $sql."<br />";
}
# get the game info
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
# get the week and season
function getWeek($game){
    include '../api/connect.php';
    $sql = "SELECT * FROM games WHERE game_id = '".$game."'";
    $result = $mysqli->query($sql);
    while ($row = $result->fetch_assoc()) {
        $date['week'] = $row['week'];
        $date['season'] = $row['season'];
    }
    $mysqli->close();
    return $date;
}
# get stats from game_stats
function getGameStats($game_id){
    include '../api/connect.php';
    $sql = "SELECT * FROM game_stats WHERE game_id = '".$game_id."'";
    $result = $mysqli->query($sql);
    while ($row = $result->fetch_assoc()) {
        $game[$row['team']] = $row;
    }
    return $game;
}
# get all games in a season
function getSeasonGames($season,$week){
    include '../api/connect.php';
    $sql = "SELECT game_id,week FROM games WHERE season = '".$season."' AND week='".$week."' AND game_synced != 0 ORDER BY week ASC";
    $result = $mysqli->query($sql);
    while ($row = $result->fetch_assoc()) {
        # combine by week
        $games[] = $row['game_id'];
    }
    $mysqli->close();
    return $games;
}
################################### getPoints ###################################
# figure out what category to use for points against
function pointsAgainst($points){
    if($points >= 35){
        return 'points_against_35_plus';
    } elseif($points >= 28){
        return 'points_against_28_34';
    } elseif($points >= 21){
        return 'points_against_21_27';
    } elseif($points >= 14){
        return 'points_against_14_20';
    } elseif($points >= 7){
        return 'points_against_7_13';
    } elseif($points >= 1){
        return 'points_against_1_6';
    } else {
        return 'points_against_0';
    }
}
# get the player's info
function lookupPlayer($player_id){
    include '../api/connect.php';
    $sql = "SELECT * FROM players WHERE source_id = '".$player_id."'";
    $result = $mysqli->query($sql);
    // check if query failed
    while ($row = $result->fetch_assoc()) {
        $player = $row;
    }
    $mysqli->close();
    return $player;
}
# add points to the ffl table
function addPoints($player){
    include '../api/connect.php';
    $sql = "INSERT INTO players_points VALUES (null,
        '".$player['game_id']."',
        '".$player['player_id']."',
        '".$player['points']."',
        '".time()."'
    ) ON DUPLICATE KEY UPDATE
        points = '".$player['points']."',
        updated = '".time()."'
    ";
    $result = $mysqli->query($sql);
    $mysqli->close();
    return $result;
}
function addPointsData($data){
    include '../api/connect.php';
    $sql = "INSERT INTO players_points_data VALUES(null,
        '".$data['game_id']."',
        '".$data['player_id']."',
        '".$data['category']."',
        '".$data['value']."',
        '".$data['points']."'
    ) ON DUPLICATE KEY UPDATE
        value = '".$data['value']."',
        points = '".$data['points']."'
    ";
    $result = $mysqli->query($sql);
    $mysqli->close();
}
function resetGamePoints($game_id){
    include '../api/connect.php';
    $sql = "UPDATE players_points_data SET points = '0' WHERE game_id = '".$game_id."'";
    $result = $mysqli->query($sql);
    $mysqli->close();
}
###################################### calcStats ######################################
function addStatsOff($s){
    global $debug;
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
    global $debug;
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
        '".$s['forced_fum']."',0,0,0,0,0,0
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
    global $debug;
    include "../api/connect.php";
    $sql = "INSERT INTO calc_data_def VALUES(null,
        '".$s['game_id']."',
        '".$s['team']."',
        '".$s['opponent']."',
        'DEF',0,0,0,0,0,0,0,0,0,
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
###################################### calcRank ######################################
function addRank($s){
    include "../api/connect.php";
        $sql = "INSERT INTO rank_points VALUES(null,
        '".$s['game_id']."',
        '".$s['team']."',
        '".$s['season']."',
        '".$s['week']."',
        '".$s['position']."',
        '".$s['points']."',
        '".$s['rank']."'
    ) ON DUPLICATE KEY UPDATE
        points = '".$s['points']."',
        ranking = '".$s['rank']."'
    ";    
    $mysqli->query($sql);
    $mysqli->close();
}