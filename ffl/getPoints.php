<?php
// json header
header('Content-type: application/json');

$debug = false;
############################  functions to process points and create array ############################
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

if($debug){ print "start processing<br />";}
#############################  start processing ########################################
if(isset($_GET['game'])){
    // get the game from the URL
    $game = $_GET['game'];
    # load variables the data will use
    $date = getWeek($game);
    $game_info = getGame($game);
    // reset any points for this game in the players_points_data table
    //resetGamePoints($game);
    #$game = '202310190nor'; #  carr 2 pt conversion pass
    #$game = '202310080pit'; # pit safety and blicked punt
    #$game = '202309170nwe'; # NE gets blocked FG
    #$game = '202201020rav'; # bal pick/six
    #$game = '202309100nyg'; # nyg blocked FG for TD
    #$game = '202112180clt'; # ind blocked punt for TD
    #$game = '202310220chi'; # chi gets xp blocked

    // load the scoring systems O = offense, D = defense, R = return, K = kicking 
    $array = array(
        'O' => 'player_stats_offense',
        'K' => 'player_stats_kicking',
        'R' => 'player_stats_return',
    );

    # array to hold final player data
    $player_points = array();
    $this_player = array();
    include '../api/connect.php';
    // loop through the scoring systems and figure out O, K, R points
    foreach($array as $system => $table){
        
        # get the scoring system
        $scoring = array();
        $sql = "SELECT category,points FROM scoring_system WHERE position = '".$system."'";
        $result = $mysqli->query($sql);
        while ($row = $result->fetch_assoc()) {
            $scoring[$row['category']] = $row['points'];
        }
        
        if($debug){
            echo '<pre>';
            print_r($scoring);
            echo '</pre>';
        }

        $pv = array(); 
        // let's get some players
        $sql = "SELECT * FROM ".$table." WHERE game_id = '".$game."'";
        if($debug){ print $sql.'<br />'; }
        $result = $mysqli->query($sql);
        if($debug){ print_r($result); }
        while ($row = $result->fetch_assoc()) {
            $offense[] = $row;
        }
        if($debug){
            echo '<pre>';
            print_r($offense);
            echo '</pre>';
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
                    $p['player_id']=lookupPlayer($pv['player_id'])['id'];
                    $p['category']=$k;
                    $p['value']=$v;
                    $p['points']=($v * $scoring[$k]);
                    if($p['points'] != 0){
                        // updated this record in the DB
                        addPointsData($p);
                    }
                    
                }
            }
            $pv['points'] = $points;
            $this_player['points'] = round($points,2);
            $this_player['source_id'] = $pv['player_id'];
            $this_player['game_id'] = $pv['game_id'];
            $this_player['team'] = $pv['team'];
            $this_player['position'] = lookupPlayer($pv['player_id'])['position'];
            $this_player['player_id'] = lookupPlayer($pv['player_id'])['id'];
            $this_player['week'] = $date['week'];
            $this_player['season'] = $date['season'];
            $this_player['game_info'] = $game_info['away'].'@'.$game_info['home'];
            // check to see if this player is already in the array
            if(array_key_exists($pv['player_id'], $player_points)){
                $player_points[$pv['player_id']]['points'] = $player_points[$pv['player_id']]['points'] + $points;
            } else {
                $player_points[$pv['player_id']] = $this_player;
            }
            if($debug){
                echo '<pre>';
                print_r($pv);
                echo '</pre>';
            }
        } 
    }

    ################################# Defensive scoring ########################################
    // get defensive stuff
    $scoring = array();
    $sql = "SELECT category,points FROM scoring_system WHERE position = 'D'";
    $result = $mysqli->query($sql);
    while ($row = $result->fetch_assoc()) {
        $scoring[$row['category']] = $row['points'];
    }
    if($debug){
        echo '<pre>';
        print_r($scoring);
        echo '</pre>';
    }

    $home = array();
    $home_team = '';
    $away = array();
    $away_team = '';
    # get basic game info
    $sql = "SELECT * FROM games WHERE game_id='".$game."'"; 
    $result = $mysqli->query($sql);
    while ($row = $result->fetch_assoc()) {
        if($debug){
            echo '<pre>';
            print_r($row);
            echo '</pre>';
        }
        // create a home and away array
        if($row['home'] || $row['yards_h'] || $row['points_h'] || $row['turn_overs_h']){
            $home['team'] = $row['home'];
            $home['points_against'] = $row['points_a'];  
            # figure out which points against category to use
            $point_against = pointsAgainst($row['points_a']);
            $home[$point_against] = 1;
            $home_team = $row['home'];    
        }
        if($row['away'] || $row['yards_a'] || $row['points_a'] || $row['turn_overs_a']){
            $away['team'] = $row['away'];
            $away['points_against'] = $row['points_h'];
            # figure out which points against category to use
            $point_against = pointsAgainst($row['points_h']);
            $away[$point_against] = 1;
            $away_team = $row['away'];
        }
    }


    # get more game info
    $sql = "SELECT * FROM game_stats WHERE game_id='".$game."'";    
    $result = $mysqli->query($sql);
    while ($row = $result->fetch_assoc()) {
        if($debug){
            echo '<pre>';
            print_r($row);
            echo '</pre>';
        }
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
    $mysqli->close();

    # loop through home and away and add up the points
    foreach($home as $k => $v){
        $p = array();   
        if(array_key_exists($k, $scoring)){
            $home['points'] = $home['points'] + ($v * $scoring[$k]);

            // prepare the data to be added to the DB
            $p['game_id']=$game;
            $p['player_id']=lookupPlayer($home['team'].'_DEF')['id'];
            $p['category']=$k;
            $p['value']=$v;
            $p['points']=($v * $scoring[$k]);
            if($p['points'] != 0){
                // updated this record in the DB
                addPointsData($p);
            }
        }
    }
    if($debug){
        echo '<pre>';
        print_r($home);
        echo '</pre>';
    }
    //print_r($home);
    # build out the array and add it to player_points
    $this_player['team'] = $home['team'];
    $this_player['game_id'] = $game;
    $this_player['source_id'] = $home['team'].'_DEF';
    $this_player['position'] = 'DEF';
    $this_player['points'] = round($home['points'],2);
    $this_player['week'] = $date['week'];
    $this_player['season'] = $date['season'];
    $this_player['player_id'] = lookupPlayer($this_player['source_id'])['id'];
    $this_player['game_info'] = $game_info['away'].'@'.$game_info['home'];
    $player_points[$this_player['source_id']] = $this_player;

    foreach($away as $k => $v){
        $p = array();
        if(array_key_exists($k, $scoring)){
            $away['points'] = $away['points'] + ($v * $scoring[$k]);
            
            // prepare the data to be added to the DB
            $p['game_id']=$game;
            $p['player_id']=lookupPlayer($away['team'].'_DEF')['id'];
            $p['category']=$k;
            $p['value']=$v;
            $p['points']=($v * $scoring[$k]);
            if($p['points'] != 0){
                // updated this record in the DB
                addPointsData($p);
            }
        }
    }
    # build out the array and add it to player_points
    $this_player['team'] = $away['team'];
    $this_player['game_id'] = $game;
    $this_player['source_id'] = $away['team'].'_DEF';
    $this_player['position'] = 'DEF';
    $this_player['points'] = round($away['points'],2);
    $this_player['week'] = $date['week'];
    $this_player['season'] = $date['season'];
    $this_player['player_id'] = lookupPlayer($this_player['source_id'])['id'];
    $this_player['game_info'] = $game_info['away'].'@'.$game_info['home'];
    $player_points[$this_player['source_id']] = $this_player;
    if($debug){
        echo '<pre>';
        print_r($away);
        echo '</pre>';
    }
    //print_r($away);
    foreach($player_points as $p){
        $result = addPoints($p);
        if (!$result) {
            echo "SQL ERROR: for ".$p['player_id']."<br>";
        }

    }
    echo json_encode($player_points);
}