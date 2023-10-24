<?php
########################## general functions ############################
# function to clear a table
function prepTable($table){
    include 'connect.php';
    $sql = "TRUNCATE TABLE $table";
    $mysqli->query($sql);
    $mysqli->close();
}
##########################  Team functions #####################
# get the abbr for a team, can receive name or abbreviation
function teamMap($team){
    # check to see if this is an alt team name
    $team = teamAlt($team);
    include 'connect.php';
    $sql = "SELECT abbr FROM `teams` WHERE (`team` = '$team' OR orig_abbr = '$team')";
    $result = $mysqli->query($sql);
    //print $sql;
    # if there is no match, return the original team name
    if ($result->num_rows == 0){
        return $team;
    }else{
        $row = $result->fetch_assoc();
        $abbr = $row['abbr'];
        return $abbr;
    } 
}

# if there is an alias for a team, return the current team name
function teamAlt($team){
    include 'connect.php';
    $sql = "SELECT team_id FROM `teams_alt` WHERE team = '".trim($team)."'";
    $result = $mysqli->query($sql);
    //print $sql;
    # if there is no match, return the original team name
    if ($result->num_rows == 0){
        return $team;
    }else{
        $row = $result->fetch_assoc();
        $team_id = $row['team_id'];
        # get the team name from the teams table
        $sql = "SELECT team FROM `teams` WHERE id = '$team_id'";
        $result = $mysqli->query($sql);
        $row = $result->fetch_assoc();
        $team = $row['team'];
        return $team;
    }
}

#################### player functions ############################
# get the player data from the database
function getPlayer($id){
    include 'connect.php';
    $sql = "SELECT * FROM players WHERE id='$id' LIMIT 1";
    $result = $mysqli->query($sql);
    while($row = $result->fetch_assoc()){
        $player = $row;
    }
    $mysqli->close();
    return $player;
}

############################  manage scaped data ############################
# clean up if the data has names for the week in the playoffs, convert to numbers
function convertPlayoffs2Week($year,$name){
    if($year < 2021){
        switch ($name){
            case 'WildCard':
                return 18;
            case 'Division':
                return 19;
            case 'ConfChamp':
                return 20;
            case 'SuperBowl':
                return 21;
            default:
                return $name; 
        }
    }else{
        switch ($name){
            case 'WildCard':
                return 19;
            case 'Division':
                return 20;
            case 'ConfChamp':
                return 21;
            case 'SuperBowl':
                return 22;
            default:
                return $name; 
        }
    }
}

##############################  mfl sync functions ###############################
# map to take MFL player data anc convert them to our format
function mflMap($player){
    if($player['team'] == 'JAC'){
        $player['team'] = 'JAX';
    }
    
    if($player['position'] == 'PK'){
        $player['position'] = 'K';
    }

    if($player['position'] == 'PN'){
        $player['position'] = 'P';
    }

    return $player;
}
# function to check and see if a player has a mfl_id in the DB, if not, try to match them against the sphinx index
function mflMatch($player){
    include 'connect-index.php';
    $player = mflMap($player);
    $positions = array('QB','RB','WR','TE','K','Def','DT','DE','LB','CB','S','P');
    # player['name'] is last, first, change to first last
    $name = explode(", ",$player['name']);
    $player['name'] = trim($name[1]) . " " . trim($name[0]);
    //$ignore = array(10948,10514,10271,9200,9207);
    $ignore = array();
    
    if((in_array($player['position'],$positions))&& (!in_array($player['id'],$ignore))){
        // check to see if the player is already matched
        if(checkMflId($player['id'])){
 
            // update the player with thier new team
            saveMatchedMfl(0,$player);
            logMFLSync($player,0,0,'id matched');
        }else{
            # set the scores were shooting for
            $safe_score = 4600;
            # make the name safe for the query
            $player['name'] = $mysqli->real_escape_string($player['name']);
            $sql = "SELECT *, WEIGHT() as score FROM players WHERE match('\"".$player['name']." ".$player['position']." ".$player['team']."\"/3') ORDER BY score DESC LIMIT 1";
            //print $sql;
            $result = $mysqli->query($sql);
            # update the player table with the mfl id
            if ($result->num_rows == 1){
                $row = $result->fetch_assoc();
                $score = $row['score'];
                $player_id = $row['id'];
                # only update over $safe_score or if they are a FA, since the match is lower
                if(($score > $safe_score) || ($player['team']=='FA')){
                    # it's a good match, update the record
                    saveMatchedMfl($player_id,$player);
                    logMFLSync($player,$player_id,$score,'matched index');
                }else{
                    # it's below the threshold, log it
                    logMFLSync($player,$player_id,$score,'failed score under '.$safe_score);
                    $player['my_id']= $player_id;
                    $player['score'] = $score;
                    $player['status'] = 'failed score under '.$safe_score;
                    //return $player;
                    $out = array('failed'=>$player,'good'=>getPlayer($player_id));
                    return $out;
                }
            }
        }
        
    }
}
# check to see if the player has a mfl_id in the DB
function checkMflId($id){
    include 'connect.php';
    $sql = "SELECT * FROM players WHERE mfl_id='$id' LIMIT 1";
    $result = $mysqli->query($sql);
    if ($result->num_rows == 1){
        return true;
    }else{
        return false;
    }
}
# update a player with their mfl id
function saveMatchedMfl($myId,$player){
    include 'connect.php';
    if($myId == 0){
        $sql = "UPDATE players SET team = '".$player['team']."' WHERE mfl_id = '".$player['id']."'";
    }else{
        $sql = "UPDATE players SET mfl_id = '".$player['id']."' AND team = '".$player['team']."' WHERE id = '$myId'";
    }
    //print $sql."<br />";
    $mysqli->query($sql);
    $mysqli->close();
}
# log the results of the mfl sync
function logMFLSync($player,$id,$score,$status){
    include 'connect.php';
    # use mysqli real escape string on name
    $player['name'] = $mysqli->real_escape_string($player['name']);
    $sql = "INSERT INTO mfl_log VALUES(null,'".$player['name']."','".$player['position']."','".$player['team']."','$id','".$player['id']."','$score','$status','".time()."') 
        ON DUPLICATE KEY UPDATE score = '$score', status = '$status', timestamp = '".time()."'";
    //print $sql."<br />";
    $mysqli->query($sql);
    $mysqli->close();
}
# add info to the mfl_inuries table
function mflInjury($item){
    include 'connect.php';
    $item['details'] = $mysqli->real_escape_string($item['details']);
    $sql = "INSERT INTO mfl_injuries VALUES('".$item['id']."','".$item['status']."','".$item['details']."','".$item['exp_return']."','".time()."')";
    //print $sql."<br />";
    //print_r($item);
    $mysqli->query($sql);
    $mysqli->close();
}

################################### mfl player info #########################################
function checkMflPlayers($limit){
    include 'connect.php';
    $sql = "SELECT * FROM players WHERE mfl_id > 5 AND birthdate = 0 LIMIT $limit"; 
    //print $sql;
    $result = $mysqli->query($sql);
    while($row = $result->fetch_assoc()){
        $players[] = $row['mfl_id'];
    }
    $mysqli->close();
    return $players;
}