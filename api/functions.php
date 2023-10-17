<?php
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