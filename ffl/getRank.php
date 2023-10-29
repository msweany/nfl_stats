<?php
$debug = false;

// json header
if(!$debug) {
    header('Content-type: application/json');
}
$output = array();
include '../api/connect.php';
include 'functions.php';

$week_in = $_GET['week'];
$year_in = $_GET['year'];

$games_season = getSeasonGames($year_in,$week_in);
$player_points = array();
# break up to each week
foreach($games_season as $game_id){
    $date = getWeek($game_id);
    $game_info = getGame($game_id);
    $game_stats = getGameStats($game_id);
    if($debug){
        printDebug($game_info,"Game Info");
        printDebug($game_stats, "Game Stats");  
    }

    ####################################### offense ###########################################
    
    # get the scoring system
    $scoring = array();
    $sql = "SELECT category,points FROM scoring_system WHERE position = 'O'";
    $result = $mysqli->query($sql);
    while ($row = $result->fetch_assoc()) {
        $scoring[$row['category']] = $row['points'];
    }

    if($debug){
        printDebug($scoring,'offense scoring');
    }

    $sql = "SELECT * FROM calc_data_off WHERE game_id='$game_id'";
    $result = $mysqli->query($sql);
    while($row = $result->fetch_assoc()) {
        $offense[] = $row;
    }

    # loop through the players and add up the points
    foreach($offense as $pk => $pv){
        $points = 0;
        $p = array();
        foreach($pv as $k => $v){
            if(array_key_exists($k, $scoring)){
                $points = $points + ($v * $scoring[$k]);
                $pv['points_'.$k]=($v * $scoring[$k]);
                $p['game_id']=$pv['game_id'];
                $p['position'] = $pv['position'];
                $p['category']=$k;
                $p['value']=$v;
                $p['points']=($v * $scoring[$k]);
            }
        }
        $pv['points'] = $points;
        $this_player['points'] = round($points,2);
        $this_player['game_id'] = $pv['game_id'];
        $this_player['team'] = $pv['opponent']; # team that had the points against them
        #$this_player['opponent'] = $pv['opponent'];
        $this_player['position'] = $pv['position'];
        $this_player['week'] = $date['week'];
        $this_player['season'] = $date['season'];
        // check to see if this player is already in the array
        if(array_key_exists($pv['position'], $player_points)){
            $player_points[$pv['team']][$pv['position']]['points'] = $player_points[$pv['position']]['points'] + $points;
        } else {
            $player_points[$pv['team']][$pv['position']] = $this_player;
        }
        if($debug){
            printDebug($pv,'offense player');
        }
    }

    if($debug){
        printDebug($player_points,'player points');
    }
    ####################################### defense ###########################################
    # get the scoring system
    $scoring = array();
    $sql = "SELECT category,points FROM scoring_system WHERE position = 'D'";
    $result = $mysqli->query($sql);
    while ($row = $result->fetch_assoc()) {
        $scoring[$row['category']] = $row['points'];
    }

    if($debug){
        printDebug($scoring,'def scoring');
    }

    $defense = array();

    $sql = "SELECT * FROM calc_data_def WHERE game_id='$game_id'";
    $result = $mysqli->query($sql);
    while($row = $result->fetch_assoc()) {
        $defense[$row['team']][] = $row;
    }

    if($debug){
        printDebug($defense,'def players');
    }
    $teams = array();
    foreach($defense as $team => $stats){
        $teams[$team]['team']=$team;
        foreach($stats as $s){
            // add up what we need to
            $teams[$s['team']]['interception']=$game_stats[$s['opponent']]['pass_int'];
            $teams[$s['team']]['int_td']=$teams[$s['team']]['int_td']+$s['int_td'];
            $teams[$s['team']]['fum_rec']=$teams[$s['team']]['fum_rec']+$s['fum_rec'];
            $teams[$s['team']]['def_td']=$teams[$s['team']]['def_td']+$s['fum_td'];
            $teams[$s['team']]['fum_rec']=$teams[$s['team']]['fum_rec']+$s['fum_rec'];
            $teams[$s['team']]['safety']=$game_stats[$s['team']]['safeties'];
            $teams[$s['team']]['blocked_fg']=$game_stats[$s['team']]['blocked_fg'];
            $teams[$s['team']]['blocked_punt']=$game_stats[$s['team']]['blocked_punt'];
            $teams[$s['team']]['blocked_fg_td']=$game_stats[$s['team']]['blocked_fg_td'];
            $teams[$s['team']]['blocked_punt_td']=$game_stats[$s['team']]['blocked_punt_td'];
            $teams[$s['team']]['sack']=$game_stats[$s['opponent']]['sacked'];
        }
    }

    if($debug){
        printDebug($teams,'def team');    
    }

    # figure out points against
    if($teams[$game_info['home']]['team'] == $game_info['home']){
        $homePtsAgainst = $game_info['points_a'];
        $awayPtsAgainst = $game_info['points_h'];
        # figure out the points_against category
        $homeCategory = pointsAgainst($homePtsAgainst);
        $awayCategory = pointsAgainst($awayPtsAgainst);
        $teams[$game_info['home']][$homeCategory]=1;
        $teams[$game_info['away']][$awayCategory]=1;
    }

    if($debug){
        print '####################### def team points math #######################<br>';
    }
    # loop through $teams and add up the points
    foreach ($teams as $k => $team){
        foreach($team as $key => $stat) {
            // set the team
            if($key == 'team'){
                $this_team = $stat;
            }
            # check to see if the key is in the scoring array and add the points
            if(array_key_exists($key, $scoring)){
                $teams[$this_team]['points'] = $teams[$this_team]['points'] + ($stat * $scoring[$key]);
                if($debug){
                    print $this_team .' - '.$key.' - '.$stat.' - points: '.$stat * $scoring[$key].'<br>';
                }
            }
        }
    }

    if($debug){
        printDebug($teams,'def team after points');    
    }
    
    $this_def = array();
    # add the defense points to the player_points array
    foreach($teams as $k => $team){
        $player_points[$k]['DEF']['game_id'] = $game_id;
        $player_points[$k]['DEF']['team'] = $team['team'];
        $player_points[$k]['DEF']['position'] = 'DEF';
        $player_points[$k]['DEF']['week'] = $date['week'];
        $player_points[$k]['DEF']['season'] = $date['season'];
        $player_points[$k]['DEF']['points'] = $team['points'];
    }

    if($debug){
        printDebug($player_points,'player points with def');    
    }
}

# create arrays for each position
$QB = array();
$RB = array();  
$WR = array();
$TE = array();
$DEF = array();
# save the points to the DB
$approved = array("QB","RB","WR","TE","DEF");
foreach($player_points as $key => $val){
    foreach($val as $stats){
        # add these to the db if they are one of the approved positions
        if(in_array($stats["position"], $approved)){
            # add them to the correct array based on position
            if($stats["position"] == "QB"){
                $QB[] = $stats;
            } elseif($stats["position"] == "RB"){
                $RB[] = $stats;
            } elseif($stats["position"] == "WR"){
                $WR[] = $stats;
            } elseif($stats["position"] == "TE"){
                $TE[] = $stats;
            } elseif($stats["position"] == "DEF"){
                $DEF[] = $stats;
            }
        }
    }
}

$rank = 1; // Initialize the counter before the loop
foreach($approved as $pos){
    if($pos == 'DEF'){
        # sort DESC
        usort($$pos, function($a, $b) {
            return $b['points'] - $a['points']; // Compare 'points' in desc order
        });
    }else{
        # sort the array by points ASC
        usort($$pos, function($a, $b) {
            return $a['points'] - $b['points']; // Compare 'points' in ascending order
        });
    }

    foreach ($$pos as $player) {
        $player['rank']=$rank;
        addRank($player);
        $rank++;
    }
    $rank = 1; // Reset the counter for the next position
}

##################################  RANK SUMMARY ###########################################
$year_in = '2023';
$player_points = array();
// get all the team/position combos
$sql = "SELECT team, position FROM rank_points GROUP BY team, position;";
$result = $mysqli->query($sql);
while($row = $result->fetch_assoc()) {
    // we need these later
    $team = $row["team"];
    $position = $row["position"];
    # get the season summary
    $sql = "SELECT COUNT(*) as games, SUM(points) as total_points, (SUM(points)/COUNT(*)) as points FROM `rank_points` WHERE team='".$row['team']."' AND position='".$row['position']."';";
    $result2 = $mysqli->query($sql);
    while($row2 = $result2->fetch_assoc()) {
        $row2['team'] = $team;
        $row2['position'] = $position;
        $row2['category'] = 'season';
        $row2['season'] = $year_in;
        $player_points[] = $row2;
        //printPretty($row2);
        //addRankSummary($row2);
    }

    $trend_count = 4;
    # get the last $trend_count games trend
    $sql = "SELECT COUNT(*) as games, SUM(points) as total_points, (SUM(points)/COUNT(*)) as points
        FROM (
            SELECT *
            FROM `rank_points`
            WHERE team='".$row['team']."' AND position='".$row['position']."'
            ORDER BY week DESC
            LIMIT $trend_count
        ) AS subquery;";
    $result3 = $mysqli->query($sql);
    while($row3 = $result3->fetch_assoc()) {
        $row3['team'] = $team;
        $row3['position'] = $position;
        $row3['category'] = 'trending';
        $row3['season'] = $year_in;
        $player_points[] = $row3;
        //printPretty($row3);
    }
}


# create arrays for each position
$QB = array();
$RB = array();  
$WR = array();
$TE = array();
$DEF = array();
# save the points to the DB
$approved = array("QB","RB","WR","TE","DEF");
foreach($player_points as $stats){
    # add these to the db if they are one of the approved positions
    if(in_array($stats["position"], $approved)){
        # add them to the correct array based on position
        if($stats["position"] == "QB"){
            $QB[$stats['category']][] = $stats;
        } elseif($stats["position"] == "RB"){
            $RB[$stats['category']][] = $stats;
        } elseif($stats["position"] == "WR"){
            $WR[$stats['category']][] = $stats;
        } elseif($stats["position"] == "TE"){
            $TE[$stats['category']][] = $stats;
        } elseif($stats["position"] == "DEF"){
            $DEF[$stats['category']][] = $stats;
        }
    } 
}

foreach ($approved as $pos) {
    foreach ($$pos as $key => &$cat) {
        if($pos == 'DEF'){
            // Sort the $cat array by 'points' key in descending order using a custom comparison function
            usort($cat, function ($a, $b) {
                if (abs($a['points'] - $b['points']) < 0.000001) {
                    return 0;
                }
                return ($a['points'] < $b['points']) ? 1 : -1;
            });
        }else{
            // Sort the $cat array by 'points' key in ascending order using a custom comparison function
            usort($cat, function ($a, $b) {
                if (abs($b['points'] - $a['points']) < 0.000001) {
                    return 0;
                }
                return ($b['points'] < $a['points']) ? 1 : -1;
            });
        }
        $rank = 1; // Initialize the counter before the loop
        foreach ($cat as $stats){
            $stats['rank'] = $rank;
            addRankSummary($stats);
            $rank++;
        }
        
        
    }
}

######################################  send to app.fozzil.net ###########################################
$sql = "SELECT * FROM rank_summary";
$result = $mysqli->query($sql);
while($row = $result->fetch_assoc()){
    $ranks[] = $row;
}

$send_this = array('token' => getenv('FFL_TOKEN'),'ranks' => $ranks);

$url = 'http://app.fozzil.net/ffl/common/receive-ranks.php';
$ch = curl_init($url);

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($send_this));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$result = curl_exec($ch);
curl_close($ch);

# now we have the JSON in $result, let's decode it into an array
$data = json_decode($result, true);

$output['send_data']= $data['status'];
$output['status'] = 100;
$output['message']= 'All players ranked';
$output['week']= $week_in;
echo json_encode($output);

