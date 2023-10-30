<?php
// change to enable/disable debug mode
$debug = false;

// set header
if (!$debug){
    header('Content-type: application/json');
}
include 'connect.php';
include 'functions.php';


$failed_ts = false;

# lets try to use curl to get the JSON our function
$base_url = "https://ffl-stuff.azurewebsites.net/api/getGame?code=".getenv('FFL_API_KEY')."&game=";

$game_id = $_GET['game'];
$output = array();
# loop through the results and get the game info
if(isset($_GET['game'])){
    $failed = false;
    $url = $base_url.$game_id;
    if($debug){ print $url.'<br />'; }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($ch);

    # now we have the JSON in $result, let's decode it into an array
    $data = json_decode($result, true);
    curl_close($ch);

    # now we have an array of games, let's format and print them out
    if($debug){
        print "<pre>";
        print_r($data['game_info']);
        print "</pre>";
    }
    # save the game info to the database
    /*ArrayIN
    (
        [game_id] => 202309070kan
        [game_date] => September 7th, 2023
        [away_team] => Detroit Lions
        [home_team] => Kansas City Chiefs
        [won_toss] => Kansas City Chiefs
        [won_toss_action] => deferred
        [roof] => outdoors
        [surface] => grass
        [duration] => 3:02
        [attendance] => 73,522
        [vegas_line] => Kansas City Chiefs -6.5
        [over_under] => 53.5 (under)
    )
    */

    # if $data['game_info']['roof'] has a ( in it, split and strip to only make it the first part 
    if(strpos($data['game_info']['roof'], '(') !== false){
        $data['game_info']['roof'] = explode('(', $data['game_info']['roof'])[0];
    }
    # if nothing is there for won_toss, enter NA
    if($data['game_info']['won_toss_action'] == null){ $data['game_info']['won_toss_action'] = 'NA'; }
    # get the team abbr for the away team
    $data['game_info']['away_team'] = teamMap($data['game_info']['away_team']);
    # get the team abbr for the home team
    $data['game_info']['home_team'] = teamMap($data['game_info']['home_team']);
    # get the team abbr for who won the toss
    $data['game_info']['won_toss'] = teamMap($data['game_info']['won_toss']);
    # let's clean up the vegas line
    $vegas = explode('-', $data['game_info']['vegas_line']);
    $vegas_team = teamMap(trim($vegas[0]));
    $vegas_line = "-".trim($vegas[1]);
    $data['game_info']['vegas_line'] = $vegas_team.' '.$vegas_line;
    $sql = "INSERT INTO game_info VALUES (null,
        '".$data['game_info']['game_id']."',
        '".$data['game_info']['game_date']."', 
        '".$data['game_info']['away_team']."', 
        '".$data['game_info']['home_team']."', 
        '".$data['game_info']['won_toss']."', 
        '".$data['game_info']['won_toss_action']."', 
        '".$data['game_info']['roof']."', 
        '".$data['game_info']['surface']."', 
        '".$data['game_info']['duration']."', 
        '".$data['game_info']['attendance']."', 
        '".$data['game_info']['vegas_line']."', 
        '".$data['game_info']['over_under']."'
    ) ON DUPLICATE KEY UPDATE 
        game_date='".$data['game_info']['game_date']."', 
        away_team='".$data['game_info']['away_team']."', 
        home_team='".$data['game_info']['home_team']."', 
        won_toss='".$data['game_info']['won_toss']."', 
        won_toss_action='".$data['game_info']['won_toss_action']."', 
        roof='".$data['game_info']['roof']."', 
        surface='".$data['game_info']['surface']."', 
        duration='".$data['game_info']['duration']."', 
        attendance='".$data['game_info']['attendance']."', 
        vegas_line='".$data['game_info']['vegas_line']."', 
        over_under='".$data['game_info']['over_under']."'
    ";
    $mysqli->query($sql);
    if($debug){ print "completed game_info<br />"; print $sql.'<br />';}
    # if the statement failed, print that
    if($mysqli->error){
        $failed = true;
        $output['error_sql'][] = $sql;
        $output['error_data']=$stat;
        if($failed_ts){
            print "<pre>";
            print_r($stat);
            print "</pre>";
        }
    }

    # now we need to update the team info for roof and surface
    $sql = "UPDATE teams SET 
        roof='".$data['game_info']['roof']."', 
        surface='".$data['game_info']['surface']."'
    WHERE team='".$data['game_info']['home_team']."'";
    $mysqli->query($sql);
    if($debug){ print "completed teams update<br />"; print $sql.'<br />';}
    # if the statement failed, print that
    if($mysqli->error){
        $failed = true;
        $output['error_sql'][] = $sql;
        $output['error_data']=$stat;
        if($failed_ts){
            print "<pre>";
            print_r($stat);
            print "</pre>";
        }
    }
    if($debug){ print "completed team update<br />";}
    if($debug){
        print "<pre>";
        print_r($data['away_stats']);
        print "<pre>";
    }
    /* save game_stats to the database, here's the format of data
    Array
    (
        [team] => DET
        [location] => away
        [game_id] => 202309070kan
        [first_downs] => 19
        [rush_att] => 34
        [rush_yds] => 118
        [rush_tds] => 1
        [pass_cmp] => 22
        [pass_att] => 35
        [pass_yds] => 253
        [pass_td] => 1
        [pass_int] => 0
        [sacked] => 1
        [sacked_yds] => 3
        [net_pass_yards] => 250
        [total_yards] => 368
        [fumbles] => 2
        [fumbles_lost] => 1
        [turnovers] => 1
        [penalties] => 4
        [penalties_yds] => 63
        [third_down_conv] => 5
        [third_down_att] => 15
        [fourth_down_conv] => 1
        [fourth_down_att] => 3
        [time_of_possession] => 32:38
    )
    */
    $data['away_stats']['team'] = teamMap($data['away_stats']['team']);
    if($data['away_stats']['rush_yds'] == null){ $data['away_stats']['rush_yds'] = 0; }
    $sql = "INSERT INTO game_stats VALUES (null,
        '".$data['away_stats']['game_id']."',
        '".$data['away_stats']['team']."',
        '".$data['away_stats']['location']."',
        '".$data['away_stats']['safeties']."',
        '".$data['away_stats']['blocked_fg']."',
        '".$data['away_stats']['blocked_punt']."',
        '".$data['away_stats']['blocked_fg_td']."',
        '".$data['away_stats']['blocked_punt_td']."',
        '".$data['away_stats']['first_downs']."',
        '".$data['away_stats']['rush_att']."',
        '".$data['away_stats']['rush_yds']."',
        '".$data['away_stats']['rush_tds']."',
        '".$data['away_stats']['pass_cmp']."',
        '".$data['away_stats']['pass_att']."',
        '".$data['away_stats']['pass_yds']."',
        '".$data['away_stats']['pass_td']."',
        '".$data['away_stats']['pass_int']."',
        '".$data['away_stats']['sacked']."',
        '".$data['away_stats']['sacked_yds']."',
        '".$data['away_stats']['net_pass_yards']."',
        '".$data['away_stats']['total_yards']."',
        '".$data['away_stats']['fumbles']."',
        '".$data['away_stats']['fumbles_lost']."',
        '".$data['away_stats']['turnovers']."',
        '".$data['away_stats']['penalties']."',
        '".$data['away_stats']['penalties_yds']."',
        '".$data['away_stats']['third_down_conv']."',
        '".$data['away_stats']['third_down_att']."',
        '".$data['away_stats']['fourth_down_conv']."',
        '".$data['away_stats']['fourth_down_att']."',
        '".$data['away_stats']['time_of_possession']."'
    ) ON DUPLICATE KEY UPDATE 
        team='".$data['away_stats']['team']."',
        location='".$data['away_stats']['location']."',
        first_downs='".$data['away_stats']['first_downs']."',
        safeties='".$data['away_stats']['safeties']."',
        blocked_fg='".$data['away_stats']['blocked_fg']."',
        blocked_punt='".$data['away_stats']['blocked_punt']."',
        blocked_fg_td='".$data['away_stats']['blocked_fg_td']."',
        blocked_punt_td='".$data['away_stats']['blocked_punt_td']."',
        rush_att='".$data['away_stats']['rush_att']."',
        rush_yds='".$data['away_stats']['rush_yds']."',
        rush_tds='".$data['away_stats']['rush_tds']."',
        pass_cmp='".$data['away_stats']['pass_cmp']."',
        pass_att='".$data['away_stats']['pass_att']."',
        pass_yds='".$data['away_stats']['pass_yds']."',
        pass_td='".$data['away_stats']['pass_td']."',
        pass_int='".$data['away_stats']['pass_int']."',
        sacked='".$data['away_stats']['sacked']."',
        sacked_yds='".$data['away_stats']['sacked_yds']."',
        net_pass_yards='".$data['away_stats']['net_pass_yards']."',
        total_yards='".$data['away_stats']['total_yards']."',
        fumbles='".$data['away_stats']['fumbles']."',
        fumbles_lost='".$data['away_stats']['fumbles_lost']."',
        turnovers='".$data['away_stats']['turnovers']."',
        penalties='".$data['away_stats']['penalties']."',
        penalties_yds='".$data['away_stats']['penalties_yds']."',
        third_down_conv='".$data['away_stats']['third_down_conv']."',
        third_down_att='".$data['away_stats']['third_down_att']."',
        fourth_down_conv='".$data['away_stats']['fourth_down_conv']."',
        fourth_down_att='".$data['away_stats']['fourth_down_att']."',
        time_of_possession='".$data['away_stats']['time_of_possession']."'";
    $mysqli->query($sql);
    if($debug){ print "completed away_stats<br />"; print $sql.'<br />';}
    # if the statement failed, print that
    if($mysqli->error){
        $failed = true;
        $output['error_sql'][] = $sql;
        $output['error_data']=$stat;
        if($failed_ts){
            print "<pre>";
            print_r($stat);
            print "</pre>";
        }
    }
    
    if($debug){
        print "<pre>";
        print_r($data['home_stats']);
        print "<pre>";
    }
    $data['home_stats']['team'] = teamMap($data['home_stats']['team']);
    # if rush_yds is null, give it a zero
    if($data['home_stats']['rush_yds'] == null){ $data['home_stats']['rush_yds'] = 0; }
    $sql = "INSERT INTO game_stats VALUES (null,
        '".$data['home_stats']['game_id']."',
        '".$data['home_stats']['team']."',
        '".$data['home_stats']['location']."',
        '".$data['home_stats']['first_downs']."',
        '".$data['home_stats']['safeties']."',
        '".$data['home_stats']['blocked_fg']."',
        '".$data['home_stats']['blocked_punt']."',
        '".$data['home_stats']['blocked_fg_td']."',
        '".$data['home_stats']['blocked_punt_td']."',
        '".$data['home_stats']['rush_att']."',
        '".$data['home_stats']['rush_yds']."',
        '".$data['home_stats']['rush_tds']."',
        '".$data['home_stats']['pass_cmp']."',
        '".$data['home_stats']['pass_att']."',
        '".$data['home_stats']['pass_yds']."',
        '".$data['home_stats']['pass_td']."',
        '".$data['home_stats']['pass_int']."',
        '".$data['home_stats']['sacked']."',
        '".$data['home_stats']['sacked_yds']."',
        '".$data['home_stats']['net_pass_yards']."',
        '".$data['home_stats']['total_yards']."',
        '".$data['home_stats']['fumbles']."',
        '".$data['home_stats']['fumbles_lost']."',
        '".$data['home_stats']['turnovers']."',
        '".$data['home_stats']['penalties']."',
        '".$data['home_stats']['penalties_yds']."',
        '".$data['home_stats']['third_down_conv']."',
        '".$data['home_stats']['third_down_att']."',
        '".$data['home_stats']['fourth_down_conv']."',
        '".$data['home_stats']['fourth_down_att']."',
        '".$data['home_stats']['time_of_possession']."'
    ) ON DUPLICATE KEY UPDATE 
        team='".$data['home_stats']['team']."',
        location='".$data['home_stats']['location']."',
        first_downs='".$data['home_stats']['first_downs']."',
        safeties='".$data['home_stats']['safeties']."',
        blocked_fg='".$data['home_stats']['blocked_fg']."',
        blocked_punt='".$data['home_stats']['blocked_punt']."',
        blocked_fg_td='".$data['home_stats']['blocked_fg_td']."',
        blocked_punt_td='".$data['home_stats']['blocked_punt_td']."',
        rush_att='".$data['home_stats']['rush_att']."',
        rush_yds='".$data['home_stats']['rush_yds']."',
        rush_tds='".$data['home_stats']['rush_tds']."',
        pass_cmp='".$data['home_stats']['pass_cmp']."',
        pass_att='".$data['home_stats']['pass_att']."',
        pass_yds='".$data['home_stats']['pass_yds']."',
        pass_td='".$data['home_stats']['pass_td']."',
        pass_int='".$data['home_stats']['pass_int']."',
        sacked='".$data['home_stats']['sacked']."',
        sacked_yds='".$data['home_stats']['sacked_yds']."',
        net_pass_yards='".$data['home_stats']['net_pass_yards']."',
        total_yards='".$data['home_stats']['total_yards']."',
        fumbles='".$data['home_stats']['fumbles']."',
        fumbles_lost='".$data['home_stats']['fumbles_lost']."',
        turnovers='".$data['home_stats']['turnovers']."',
        penalties='".$data['home_stats']['penalties']."',
        penalties_yds='".$data['home_stats']['penalties_yds']."',
        third_down_conv='".$data['home_stats']['third_down_conv']."',
        third_down_att='".$data['home_stats']['third_down_att']."',
        fourth_down_conv='".$data['home_stats']['fourth_down_conv']."',
        fourth_down_att='".$data['home_stats']['fourth_down_att']."',
        time_of_possession='".$data['home_stats']['time_of_possession']."'";
    if($debug){ print "completed home_stats<br />"; print $sql.'<br />';}
    $mysqli->query($sql);
    
    # if the statement failed, print that
    if($mysqli->error){
        $failed = true;
        $output['error_sql'][] = $sql;
        $output['error_data']=$stat;
        if($failed_ts){
            print "<pre>";
            print_r($stat);
            print "</pre>";
        }
    }

    if($debug){
        print "<pre>";
        print_r($data['offense']);
        print "<pre>";
    }
    /* save player_stats_offense to the database, here's the format of data
    [GoffJa00] => Array
            (
                [game_id] => 202309070kan
                [player] => Jared Goff
                [player_url] => /players/G/GoffJa00.htm
                [player_id] => GoffJa00
                [team] => DET
                [location] => away
                [pass_cmp] => 22
                [pass_att] => 35
                [pass_yds] => 253
                [pass_td] => 1
                [int] => 0
                [sack] => 1
                [sack_yds_lost] => 3
                [pass_cmp_longest] => 33
                [pass_rating] => 94.1
                [rush_att] => 5
                [rush_yds] => -1
                [rush_td] => 0
                [rush_att_longest] => 2
                [rec_tar] => 0
                [rec_rec] => 0
                [rec_yds] => 0
                [rec_td] => 0
                [rec_longest] => 0
                [fmb] => 0
                [fmb_lost] => 0
            )
    */

    foreach($data['offense'] as $stat){
        # if rating is null, put 0.0 in there
        if($stat['pass_rating'] == null){ $stat['pass_rating'] = 0.0; }
        $stat['team'] = teamMap($stat['team']);
        # get the player id ready for insert (remove any ')
        $stat['player_id'] = str_replace("'", "", $stat['player_id']);
        $sql = "INSERT INTO player_stats_offense VALUES (null,
            '".$stat['game_id']."',
            '".$stat['player_id']."',
            '".$stat['team']."',
            '".$stat['pass_cmp']."',
            '".$stat['pass_att']."',
            '".$stat['pass_yds']."',
            '".$stat['pass_td']."',
            '".$stat['pass_two_point']."',
            '".$stat['int']."',
            '".$stat['sack']."',
            '".$stat['sack_yds_lost']."',
            '".$stat['pass_cmp_longest']."',
            '".$stat['pass_rating']."',
            '".$stat['rush_att']."',
            '".$stat['rush_yds']."',
            '".$stat['rush_td']."',
            '".$stat['rush_two_point']."',
            '".$stat['rush_att_longest']."',
            '".$stat['rec_tar']."',
            '".$stat['rec_rec']."',
            '".$stat['rec_yds']."',
            '".$stat['rec_td']."',
            '".$stat['rec_two_point']."',
            '".$stat['rec_longest']."',
            '".$stat['fmb']."',
            '".$stat['fmb_lost']."'
        ) ON DUPLICATE KEY UPDATE
            pass_cmp='".$stat['pass_cmp']."',
            pass_att='".$stat['pass_att']."',
            pass_yds='".$stat['pass_yds']."',
            pass_td='".$stat['pass_td']."',
            pass_two_point='".$stat['pass_two_point']."',
            interception='".$stat['int']."',
            sack='".$stat['sack']."',
            sack_yds_lost='".$stat['sack_yds_lost']."',
            pass_cmp_longest='".$stat['pass_cmp_longest']."',
            pass_rating='".$stat['pass_rating']."',
            rush_att='".$stat['rush_att']."',
            rush_yds='".$stat['rush_yds']."',
            rush_td='".$stat['rush_td']."',
            rush_two_point='".$stat['rush_two_point']."',
            rush_att_longest='".$stat['rush_att_longest']."',
            rec_tar='".$stat['rec_tar']."',
            rec_rec='".$stat['rec_rec']."',
            rec_yds='".$stat['rec_yds']."',
            rec_td='".$stat['rec_td']."',
            rec_two_point='".$stat['rec_two_point']."',
            rec_longest='".$stat['rec_longest']."',
            fmb='".$stat['fmb']."',
            fmb_lost='".$stat['fmb_lost']."'
        ";
        $mysqli->query($sql);
        # if the statement failed, print that
        if($mysqli->error){
            $failed = true;
            $output['error_sql'][] = $sql;
            $output['error_data']=$stat;
            if($failed_ts){
                print "<pre>";
                print_r($stat);
                print "</pre>";
            }
        }
    }

    if($debug){
        print "<pre>";
        print_r($data['adv_passing']);
        print "<pre>";
    }
    /* save adv_passing to the database, here's the format of data
    /* save player_stats_offense to the database, here's the format of data
        [GoffJa00] => Array
            (
                [game_id] => 202309070kan
                [player] => Jared Goff
                [player_url] => /players/G/GoffJa00.htm
                [player_id] => GoffJa00
                [team] => DET
                [first_downs] => 11
                [first_downs_pct] => 30.6
                [intended_air_yds] => 238
                [intended_air_yds_per_att] => 6.8
                [completed_air_yds] => 144
                [completed_air_yds_per_cmp] => 6.5
                [completed_air_yds_per_att] => 4.1
                [pass_yds_after_catch] => 109
                [pass_yds_after_catch_per_cmp] => 5.0
                [pass_dropped] => 3
                [pass_dropped_per_att] => 9.1%
                [poor_throws] => 4
                [poor_throws_pct] => 12.1%
                [times_blitzed] => 12
                [times_hurried] => 0
                [times_hit] => 3
                [time_pressured] => 4
                [time_pressured_per_dropback] => 11.1%
                [scrambels] => 0
                [yds_per_scrambel_att] => 
            )
    */
    foreach($data['adv_passing'] as $stat){
        # if it's pass_dropped_per_att strip the % sign
        $stat['pass_dropped_per_att'] = str_replace('%', '', $stat['pass_dropped_per_att']);
        # if it's poor_throws_pct strip the % sign
        $stat['poor_throws_pct'] = str_replace('%', '', $stat['poor_throws_pct']);
        # if it's time_pressured_per_dropback strip the % sign
        $stat['time_pressured_per_dropback'] = str_replace('%', '', $stat['time_pressured_per_dropback']);
        # if yds_per_scrambel_att is null, put 0.0 in there
        if($stat['yds_per_scrambel_att'] == null){ $stat['yds_per_scrambel_att'] = 0.0; }
        # if first_downs is null, put 0 in there
        if($stat['first_downs'] == null){ $stat['first_downs'] = 0; }
        # if first_downs_pct is null, put 0.0 in there
        if($stat['first_downs_pct'] == null){ $stat['first_downs_pct'] = 0.0; }
        #if completed_air_yds_per_cmp is null, put 0.0 in there
        if($stat['completed_air_yds_per_cmp'] == null){ $stat['completed_air_yds_per_cmp'] = 0; }
        # if pass_yds_after_catch_per_cmp is null, put 0.0 in there
        if($stat['pass_yds_after_catch_per_cmp'] == null){ $stat['pass_yds_after_catch_per_cmp'] = 0.0; }
        $stat['team'] = teamMap($stat['team']);
        # get the player id ready for insert (remove any ')
        $stat['player_id'] = str_replace("'", "", $stat['player_id']);
        $sql = "INSERT INTO player_stats_adv_passing VALUES (null,
            '".$stat['game_id']."',
            '".$stat['player_id']."',
            '".$stat['team']."',
            '".$stat['first_downs']."',
            '".$stat['first_downs_pct']."',
            '".$stat['intended_air_yds']."',
            '".$stat['intended_air_yds_per_att']."',
            '".$stat['completed_air_yds']."',
            '".$stat['completed_air_yds_per_cmp']."',
            '".$stat['completed_air_yds_per_att']."',
            '".$stat['pass_yds_after_catch']."',
            '".$stat['pass_yds_after_catch_per_cmp']."',
            '".$stat['pass_dropped']."',
            '".$stat['pass_dropped_per_att']."',
            '".$stat['poor_throws']."',
            '".$stat['poor_throws_pct']."',
            '".$stat['times_blitzed']."',
            '".$stat['times_hurried']."',
            '".$stat['times_hit']."',
            '".$stat['time_pressured']."',
            '".$stat['time_pressured_per_dropback']."',
            '".$stat['scrambels']."',
            '".$stat['yds_per_scrambel_att']."'
        ) ON DUPLICATE KEY UPDATE
            first_downs='".$stat['first_downs']."',
            first_downs_pct='".$stat['first_downs_pct']."',
            intended_air_yds='".$stat['intended_air_yds']."',
            intended_air_yds_per_att='".$stat['intended_air_yds_per_att']."',
            completed_air_yds='".$stat['completed_air_yds']."',
            completed_air_yds_per_cmp='".$stat['completed_air_yds_per_cmp']."',
            completed_air_yds_per_att='".$stat['completed_air_yds_per_att']."',
            pass_yds_after_catch='".$stat['pass_yds_after_catch']."',
            pass_yds_after_catch_per_cmp='".$stat['pass_yds_after_catch_per_cmp']."',
            pass_dropped='".$stat['pass_dropped']."',
            pass_dropped_per_att='".$stat['pass_dropped_per_att']."',
            poor_throws='".$stat['poor_throws']."',
            poor_throws_pct='".$stat['poor_throws_pct']."',
            times_blitzed='".$stat['times_blitzed']."',
            times_hurried='".$stat['times_hurried']."',
            times_hit='".$stat['times_hit']."',
            time_pressured='".$stat['time_pressured']."',
            time_pressured_per_dropback='".$stat['time_pressured_per_dropback']."',
            scrambels='".$stat['scrambels']."',
            yds_per_scrambel_att='".$stat['yds_per_scrambel_att']."'
        ";
        $mysqli->query($sql);
        # if the statement failed, print that
        if($mysqli->error){
            $failed = true;
            $output['error_sql'][] = $sql;
            $output['error_data']=$stat;
            if($failed_ts){
                print "<pre>";
                print_r($stat);
                print "</pre>";
            }
        }
    }

    if($debug){
        print "<pre>";
        print_r($data['adv_rushing']);
        print "<pre>";
    }
    /* save adv_rushing to the database
    here's the data format
    [MontDa01] => Array
            (
                [game_id] => 202309070kan
                [player] => David Montgomery
                [player_url] => /players/M/MontDa01.htm
                [player_id] => MontDa01
                [team] => DET
                [location] => away
                [first_downs] => 4
                [rush_yds_before_contact] => 51
                [rush_yds_before_contact_per_att] => 2.4
                [yds_after_contact] => 23
                [yds_after_contact_per_att] => 1.1
                [broken_tackles] => 1
                [rush_att_per_broken_tackle] => 21.0
            )
    */

    foreach($data['adv_rushing'] as $stat){
        # if it's rush_yds_before_contact_per_att change it to 0.0
        if($stat['rush_yds_before_contact_per_att'] == null){ $stat['rush_yds_before_contact_per_att'] = 0.0; }
        # if it's yds_after_contact_per_att change it to 0.0
        if($stat['yds_after_contact_per_att'] == null){ $stat['yds_after_contact_per_att'] = 0.0; }
        # if it's rush_att_per_broken_tackle change it to 0.0
        if($stat['rush_att_per_broken_tackle'] == null){ $stat['rush_att_per_broken_tackle'] = 0.0; }
        # if first_downs is null change it to 0
        if($stat['first_downs'] == null){ $stat['first_downs'] = 0; }
        $stat['team'] = teamMap($stat['team']);
        # get the player id ready for insert (remove any ')
        $stat['player_id'] = str_replace("'", "", $stat['player_id']);
        $sql = "INSERT INTO player_stats_adv_rushing VALUES (null,
            '".$stat['game_id']."',
            '".$stat['player_id']."',
            '".$stat['team']."',
            '".$stat['first_downs']."',
            '".$stat['rush_yds_before_contact']."',
            '".$stat['rush_yds_before_contact_per_att']."',
            '".$stat['yds_after_contact']."',
            '".$stat['yds_after_contact_per_att']."',
            '".$stat['broken_tackles']."',
            '".$stat['rush_att_per_broken_tackle']."'
        ) ON DUPLICATE KEY UPDATE
            first_downs='".$stat['first_downs']."',
            rush_yds_before_contact='".$stat['rush_yds_before_contact']."',
            rush_yds_before_contact_per_att='".$stat['rush_yds_before_contact_per_att']."',
            yds_after_contact='".$stat['yds_after_contact']."',
            yds_after_contact_per_att='".$stat['yds_after_contact_per_att']."',
            broken_tackles='".$stat['broken_tackles']."',
            rush_att_per_broken_tackle='".$stat['rush_att_per_broken_tackle']."'
        ";
        $mysqli->query($sql);
        # if the statement failed, print that
        if($mysqli->error){
            $failed = true;
            $output['error_sql'][] = $sql;
            $output['error_data']=$stat;
            if($failed_ts){
                print "<pre>";
                print_r($stat);
                print "</pre>";
            }
        }
    }

    if($debug){
        print "<pre>";
        print_r($data['adv_receiving']);
        print "<pre>";
    }
    /* save adv_receiving to the database
    here's the data format
    [StxxAm00] => Array
            (
                [game_id] => 202309070kan
                [player] => Amon-Ra St. Brown
                [player_url] => /players/S/StxxAm00.htm
                [player_id] => StxxAm00
                [team] => DET
                [location] => away
                [first_downs] => 4
                [yds_in_air_before_catch] => 51
                [yds_in_air_before_catch_per_rec] => 8.5
                [yds_after_catch] => 20
                [yds_after_catch_per_rec] => 3.3
                [avg_depth_of_target] => 6.7
                [broken_tackles] => 0
                [rec_per_broken_tackle] => 
                [dropped_pass] => 0
                [dropped_pass_pct] => 0.0
                [int_when_targeted] => 0
                [pass_rating_when_targeted] => 127.5
            )
    */

    foreach($data['adv_receiving'] as $stat){
        # if rec_per_broken_tackle is null, change it to 0.0
        if($stat['rec_per_broken_tackle'] == null){ $stat['rec_per_broken_tackle'] = 0.0; }
        # if yds_in_air_before_catch_per_rec is null or 0, change it to 0.0
        if($stat['yds_in_air_before_catch_per_rec'] == null || $stat['yds_in_air_before_catch_per_rec'] == 0){ $stat['yds_in_air_before_catch_per_rec'] = 0.0; }
        # if yds_after_catch_per_rec is null, change it to 0.0
        if($stat['yds_after_catch_per_rec'] == null){ $stat['yds_after_catch_per_rec'] = 0.0; }
        # if first_downs is null, change it to 0
        if($stat['first_downs'] == null){ $stat['first_downs'] = 0; }
        $stat['team'] = teamMap($stat['team']);
        # get the player id ready for insert (remove any ')
        $stat['player_id'] = str_replace("'", "", $stat['player_id']);
        $sql = "INSERT INTO player_stats_adv_receiving VALUES (null,
            '".$stat['game_id']."',
            '".$stat['player_id']."',
            '".$stat['team']."',
            '".$stat['first_downs']."',
            '".$stat['yds_in_air_before_catch']."',
            '".$stat['yds_in_air_before_catch_per_rec']."',
            '".$stat['yds_after_catch']."',
            '".$stat['yds_after_catch_per_rec']."',
            '".$stat['avg_depth_of_target']."',
            '".$stat['broken_tackles']."',
            '".$stat['rec_per_broken_tackle']."',
            '".$stat['dropped_pass']."',
            '".$stat['dropped_pass_pct']."',    
            '".$stat['int_when_targeted']."',
            '".$stat['pass_rating_when_targeted']."'
        ) ON DUPLICATE KEY UPDATE
            first_downs='".$stat['first_downs']."',
            yds_in_air_before_catch='".$stat['yds_in_air_before_catch']."',
            yds_in_air_before_catch_per_rec='".$stat['yds_in_air_before_catch_per_rec']."',
            yds_after_catch='".$stat['yds_after_catch']."',
            yds_after_catch_per_rec='".$stat['yds_after_catch_per_rec']."',
            avg_depth_of_target='".$stat['avg_depth_of_target']."',
            broken_tackles='".$stat['broken_tackles']."',
            rec_per_broken_tackle='".$stat['rec_per_broken_tackle']."',
            dropped_pass='".$stat['dropped_pass']."',
            dropped_pass_pct='".$stat['dropped_pass_pct']."',
            int_when_targeted='".$stat['int_when_targeted']."',
            pass_rating_when_targeted='".$stat['pass_rating_when_targeted']."'
        ";
        $mysqli->query($sql);
        # if the statement failed, print that
        if($mysqli->error){
            $failed = true;
            $output['error_sql'][] = $sql;
            $output['error_data']=$stat;
            if($failed_ts){
                print "<pre>";
                print_r($stat);
                print "</pre>";
            }
        }
    }

    if($debug){
        print "<pre>";
        print_r($data['defense']);
        print "<pre>";
    }
    /* save defense to the database
    here's the data format
    [BranBr00] => Array
            (
                [game_id] => 202309070kan
                [player] => Brian Branch
                [player_url] => /players/B/BranBr00.htm
                [player_id] => BranBr00
                [team] => DET
                [location] => away
                [int] => 1
                [int_yds] => 50
                [int_td] => 1
                [int_longest_ret] => 50
                [solo_tackles] => 0.0
                [ast_tackles] => 3
                [tackles_for_loss] => 2
                [qb_hits] => 1
                [fum_rec] => 0
                [fum_yds] => 0
                [fum_td] => 0
                [forced_fum] => 0
            )
    */

    //// add sacks!!!!!!!!!!!!!!!!!!!!!
    foreach($data['defense'] as $stat){
        $stat['team'] = teamMap($stat['team']);
        # get the player id ready for insert (remove any ')
        $stat['player_id'] = str_replace("'", "", $stat['player_id']);
        $sql = "INSERT INTO player_stats_defense VALUES (null,
            '".$stat['game_id']."',
            '".$stat['player_id']."',
            '".$stat['team']."',
            '".$stat['int']."',
            '".$stat['int_yds']."',
            '".$stat['int_td']."',
            '".$stat['int_longest_ret']."',
            '".$stat['solo_tackles']."',
            '".$stat['ast_tackles']."',
            '".$stat['tackles_for_loss']."',
            '".$stat['qb_hits']."',
            '".$stat['fum_rec']."',
            '".$stat['fum_yds']."',
            '".$stat['fum_td']."',
            '".$stat['forced_fum']."'
        ) ON DUPLICATE KEY UPDATE
            interception='".$stat['int']."',
            int_yds='".$stat['int_yds']."',
            int_td='".$stat['int_td']."',
            int_longest_ret='".$stat['int_longest_ret']."',
            solo_tackles='".$stat['solo_tackles']."',
            ast_tackles='".$stat['ast_tackles']."',
            tackles_for_loss='".$stat['tackles_for_loss']."',
            qb_hits='".$stat['qb_hits']."',
            fum_rec='".$stat['fum_rec']."',
            fum_yds='".$stat['fum_yds']."',
            fum_td='".$stat['fum_td']."',
            forced_fum='".$stat['forced_fum']."'    
        ";
        $mysqli->query($sql);
        # if the statement failed, print that
        if($mysqli->error){
            $failed = true;
            $output['error_sql'][] = $sql;
            $output['error_data']=$stat;
            if($failed_ts){
                print "<pre>";
                print_r($stat);
                print "</pre>";
            }
        }
    }


    if($debug){
        print "<pre>";
        print_r($data['adv_defense']);
        print "<pre>";
    }
    /* save adv_defense to the database
    here's the data format
    [AnzaAl00] => Array
            (
                [game_id] => 202309070kan
                [player] => Alex Anzalone
                [player_url] => /players/A/AnzaAl00.htm
                [player_id] => AnzaAl00
                [team] => DET
                [location] => away
                [times_targeted_when_def] => 5
                [completed_passed_when_def] => 4
                [completed_pct_when_def] => 80.0%
                [rec_yds_allowed] => 22
                [rec_yds_per_rec_allowed] => 5.5
                [rec_yds_per_targeted] => 4.4
                [td_against] => 1
                [pass_rating_when_targeted] => 124.6
                [avg_depth_when_targeted] => 0.6
                [yds_in_air_when_completed] => -1
                [yds_after_catch] => 23
                [times_blitzed] => 2
                [qb_hurries] => 0
                [qb_knockdowns] => 1
                [qb_pressures] => 1
                [missed_tackles] => 1
                [missed_tackle_pct] => 14.3%
            )
    */
    foreach($data['adv_defense'] as $stat){
        # if it's completed_pct_when_def strip the % sign
        $stat['completed_pct_when_def'] = str_replace('%', '', $stat['completed_pct_when_def']);
        # if it's missed_tackle_pct strip the % sign
        $stat['missed_tackle_pct'] = str_replace('%', '', $stat['missed_tackle_pct']);
        # if missed_tackle_pct is null, change it to 0.0
        if($stat['missed_tackle_pct'] == null){ $stat['missed_tackle_pct'] = 0.0; }
        # if completed_pct_when_def is null, change it to 0
        if($stat['completed_pct_when_def'] == null){ $stat['completed_pct_when_def'] = 0; }
        # if rec_yds_allowed is null, change it to 0
        if($stat['rec_yds_allowed'] == null){ $stat['rec_yds_allowed'] = 0; }
        # if rec_yds_per_rec_allowed is null, change it to 0.0
        if($stat['rec_yds_per_rec_allowed'] == null){ $stat['rec_yds_per_rec_allowed'] = 0.0; }
        # if rec_yds_per_targeted is null, change it to 0.0
        if($stat['rec_yds_per_targeted'] == null){ $stat['rec_yds_per_targeted'] = 0.0; }
        # if td_against is null, change it to 0
        if($stat['td_against'] == null){ $stat['td_against'] = 0; }
        # if pass_rating_when_targeted is null, change it to 0.0
        if($stat['pass_rating_when_targeted'] == null){ $stat['pass_rating_when_targeted'] = 0.0; }
        # if avg_depth_when_targeted is null, change it to 0.0
        if($stat['avg_depth_when_targeted'] == null){ $stat['avg_depth_when_targeted'] = 0.0; }
        # if yds_in_air_when_completed is null, change it to 0
        if($stat['yds_in_air_when_completed'] == null){ $stat['yds_in_air_when_completed'] = 0; }
        # if yds_after_catch is null, change it to 0
        if($stat['yds_after_catch'] == null){ $stat['yds_after_catch'] = 0; }
        $stat['team'] = teamMap($stat['team']);
        # get the player id ready for insert (remove any ')
        $stat['player_id'] = str_replace("'", "", $stat['player_id']);
        $sql = "INSERT INTO player_stats_adv_defense VALUES (null,
            '".$stat['game_id']."',
            '".$stat['player_id']."',
            '".$stat['team']."',
            '".$stat['times_targeted_when_def']."',
            '".$stat['completed_passed_when_def']."',
            '".$stat['completed_pct_when_def']."',
            '".$stat['rec_yds_allowed']."',
            '".$stat['rec_yds_per_rec_allowed']."',
            '".$stat['rec_yds_per_targeted']."',
            '".$stat['td_against']."',
            '".$stat['pass_rating_when_targeted']."',
            '".$stat['avg_depth_when_targeted']."',
            '".$stat['yds_in_air_when_completed']."',
            '".$stat['yds_after_catch']."',
            '".$stat['times_blitzed']."',
            '".$stat['qb_hurries']."',
            '".$stat['qb_knockdowns']."',
            '".$stat['qb_pressures']."',
            '".$stat['missed_tackles']."',
            '".$stat['missed_tackle_pct']."'
        ) ON DUPLICATE KEY UPDATE
            times_targeted_when_def='".$stat['times_targeted_when_def']."',
            completed_passed_when_def='".$stat['completed_passed_when_def']."',
            completed_pct_when_def='".$stat['completed_pct_when_def']."',
            rec_yds_allowed='".$stat['rec_yds_allowed']."',
            rec_yds_per_rec_allowed='".$stat['rec_yds_per_rec_allowed']."',
            rec_yds_per_targeted='".$stat['rec_yds_per_targeted']."',
            td_against='".$stat['td_against']."',
            pass_rating_when_targeted='".$stat['pass_rating_when_targeted']."',
            avg_depth_when_targeted='".$stat['avg_depth_when_targeted']."',
            yds_in_air_when_completed='".$stat['yds_in_air_when_completed']."',
            yds_after_catch='".$stat['yds_after_catch']."',
            times_blitzed='".$stat['times_blitzed']."',
            qb_hurries='".$stat['qb_hurries']."',
            qb_knockdowns='".$stat['qb_knockdowns']."',
            qb_pressures='".$stat['qb_pressures']."',
            missed_tackles='".$stat['missed_tackles']."',
            missed_tackle_pct='".$stat['missed_tackle_pct']."'
        ";
        $mysqli->query($sql);
        # if the statement failed, print that
        if($mysqli->error){
            $failed = true;
            $output['error_sql'][] = $sql;
            $output['error_data']=$stat;
            if($failed_ts){
                print "<pre>";
                print_r($stat);
                print "</pre>";
            }
        }
    }   

    if($debug){
        print "<pre>";
        print_r($data['returns']);
        print "<pre>";
    }
    /* save returns to the database
    here's the data format
    [RaymKa00] => Array
            (
                [game_id] => 202309070kan
                [player] => Kalif Raymond
                [player_url] => /players/R/RaymKa00.htm
                [player_id] => RaymKa00
                [team] => DET
                [location] => away
                [kickoff_returns] => 0
                [kickoff_return_yds] => 0
                [kickoff_return_yds_per] => 
                [kickoff_returns_td] => 0
                [kickoff_return_longest] => 0
                [punt_returns] => 1
                [punt_return_yds] => 16
                [punt_return_yds_per] => 16.0
                [punt_return_td] => 0
                [punt_return_longest] => 16
            )
    */
    foreach($data['returns'] as $stat){
        #if kickoff_return_yds_per is null, change it to 0.0
        if($stat['kickoff_return_yds_per'] == null){ $stat['kickoff_return_yds_per'] = 0.0; }
        #if punt_return_yds_per is null, change it to 0.0
        if($stat['punt_return_yds_per'] == null){ $stat['punt_return_yds_per'] = 0.0; }
        $stat['team'] = teamMap($stat['team']);
        # get the player id ready for insert (remove any ')
        $stat['player_id'] = str_replace("'", "", $stat['player_id']);
        $sql = "INSERT INTO player_stats_return VALUES (null,
            '".$stat['game_id']."',
            '".$stat['player_id']."',
            '".$stat['team']."',
            '".$stat['kickoff_returns']."',
            '".$stat['kickoff_return_yds']."',
            '".$stat['kickoff_return_yds_per']."',
            '".$stat['kickoff_returns_td']."',
            '".$stat['kickoff_return_longest']."',
            '".$stat['punt_returns']."',
            '".$stat['punt_return_yds']."',
            '".$stat['punt_return_yds_per']."',
            '".$stat['punt_return_td']."',
            '".$stat['punt_return_longest']."'
        ) ON DUPLICATE KEY UPDATE   
            kickoff_returns='".$stat['kickoff_returns']."',
            kickoff_return_yds='".$stat['kickoff_return_yds']."',
            kickoff_return_yds_per='".$stat['kickoff_return_yds_per']."',
            kickoff_returns_td='".$stat['kickoff_returns_td']."',
            kickoff_return_longest='".$stat['kickoff_return_longest']."',
            punt_returns='".$stat['punt_returns']."',
            punt_return_yds='".$stat['punt_return_yds']."',
            punt_return_yds_per='".$stat['punt_return_yds_per']."',
            punt_return_td='".$stat['punt_return_td']."',
            punt_return_longest='".$stat['punt_return_longest']."'
        ";
        $mysqli->query($sql);
        # if the statement failed, print that
        if($mysqli->error){
            $failed = true;
            $output['error_sql'][] = $sql;
            $output['error_data']=$stat;
            if($failed_ts){
                print "<pre>";
                print_r($stat);
                print "</pre>";
            }
        }

    }

    if($debug){
        print "<pre>";
        print_r($data['kicking']);
        print "<pre>";
    }
    /* save kicking to the database
    here's the data format
    [PattRi01] => Array
            (
                [game_id] => 202309070kan
                [player] => Riley Patterson
                [player_url] => /players/P/PattRi01.htm
                [player_id] => PattRi01
                [team] => DET
                [location] => away
                [xp_made] => 3
                [xp_attempts] => 3
                [fg_made] => 
                [fg_attempts] => 
                [punts] => 0
                [punt_yds] => 0
                [punt_yds_per] => 
                [punt_longest] => 0
            )
    */
    foreach($data['kicking'] as $stat){
        # if xp_made is null, change it to 0
        if($stat['xp_made'] == null){ $stat['xp_made'] = 0; }
        # if xp_attempts is null, change it to 0
        if($stat['xp_attempts'] == null){ $stat['xp_attempts'] = 0; }
        # if fg_made is null, change it to 0
        if($stat['fg_made'] == null){ $stat['fg_made'] = 0; }
        # if fg_attempts is null, change it to 0    
        if($stat['fg_attempts'] == null){ $stat['fg_attempts'] = 0; }
        # if punts_yds_per is null, change it to 0.0
        if($stat['punt_yds_per'] == null){ $stat['punt_yds_per'] = 0.0; }
        $stat['team'] = teamMap($stat['team']);
        # get the player id ready for insert (remove any ')
        $stat['player_id'] = str_replace("'", "", $stat['player_id']);
        $sql = "INSERT INTO player_stats_kicking VALUES (null,
            '".$stat['game_id']."',
            '".$stat['player_id']."',
            '".$stat['team']."',
            '".$stat['xp_made']."',
            '".$stat['xp_attempts']."',
            '".$stat['fg_made']."',
            '".$stat['fg_attempts']."',
            '".$stat['punts']."',
            '".$stat['punt_yds']."',
            '".$stat['punt_yds_per']."',
            '".$stat['punt_longest']."'
        ) ON DUPLICATE KEY UPDATE
            xp_made='".$stat['xp_made']."',
            xp_attempts='".$stat['xp_attempts']."',
            fg_made='".$stat['fg_made']."',
            fg_attempts='".$stat['fg_attempts']."',
            punts='".$stat['punts']."',
            punt_yds='".$stat['punt_yds']."',
            punt_yds_per='".$stat['punt_yds_per']."',
            punt_longest='".$stat['punt_longest']."'
        ";
        $mysqli->query($sql);
        # if the statement failed, print that
        if($mysqli->error){
            $failed = true;
            $output['error_sql'][] = $sql;
            $output['error_data']=$stat;
            if($failed_ts){
                print "<pre>";
                print_r($stat);
                print "</pre>";
            }
        }
    }

    if($debug){
        print "<pre>";
        print_r($data['adv_kicking']);
        print "<pre>";
    }
    /* save kicking to the database
    here's the data format
    "GrupBl00": {
            "game_id": "202310190nor",
            "player_id": "GrupBl00",
            "fg_made_50_plus": 0,
            "fg_made_40_49": 1,
            "fg_made_0_39": 2,
            "fg_miss_50_plus": 1,
            "fg_miss_40_49": 0,
            "fg_miss_0_39": 0
        },
    */
    foreach($data['adv_kicking'] as $stat){ 
        $sql = "INSERT INTO player_stats_adv_kicking VALUES (null,
            '".$stat['game_id']."',
            '".$stat['player_id']."',
            '".$stat['fg_made_50_plus']."',
            '".$stat['fg_made_40_49']."',
            '".$stat['fg_made_0_39']."',
            '".$stat['fg_miss_50_plus']."',
            '".$stat['fg_miss_40_49']."',
            '".$stat['fg_miss_0_39']."'
        ) ON DUPLICATE KEY UPDATE
            fg_made_50_plus='".$stat['fg_made_50_plus']."',
            fg_made_40_49='".$stat['fg_made_40_49']."',
            fg_made_0_39='".$stat['fg_made_0_39']."',
            fg_miss_50_plus='".$stat['fg_miss_50_plus']."',
            fg_miss_40_49='".$stat['fg_miss_40_49']."',
            fg_miss_0_39='".$stat['fg_miss_0_39']."'
        ";
        if($debug){
            print $sql.' <br />';
        }
        $mysqli->query($sql);
        # if the statement failed, print that   
        if($mysqli->error){
            $failed = true;
            $output['error_sql'][] = $sql;
            $output['error_data'] = $stat;
            if($failed_ts){
                print "<pre>";
                print_r($stat);
                print "</pre>";
            }
        }
    }

    # finally get all the play_by_play for reference
    if($debug){
        print "<pre>";
        print_r($data['play_by_play']);
        print "<pre>";
    }
    /* save play_by_play to the database
    here's the data format
    [0] => Array
    (
        [game_id] => 202112180clt
        [quarter] => 
        [time] => 
        [down] => 
        [togo] => 
        [location] => 
        [away_team] => 
        [home_team] => 
        [detail] => Patriots won the coin toss and deferred, Colts to receive the opening kickoff.
    )
    */
    foreach($data['play_by_play'] as $key => $stat){
        # if quarter is null, change it to 0
        if($stat['quarter'] == null){ $stat['quarter'] = 0; }
        # if time is null, change it to 0:00
        if($stat['time'] == null){ $stat['time'] = '0:00'; }
        # if down is null, change it to 0
        if($stat['down'] == null){ $stat['down'] = 0; }
        # if togo is null, change it to 0
        if($stat['togo'] == null){ $stat['togo'] = 0; }
        # if location is null, change it to NA
        if($stat['location'] == null){ $stat['location'] = 'NA'; }
        # if away_team is null, change it to away_team
        if($stat['away_team'] == null){ $stat['away_team'] = '0'; }
        # if home_team is null, change it to home_team
        if($stat['home_team'] == null){ $stat['home_team'] = '0'; }
        # make detail safe for insert
        $stat['detail'] = $mysqli->real_escape_string($stat['detail']);
        $sql = "INSERT INTO play_by_play VALUES (null,
            '".$stat['game_id']."',
            '".$stat['quarter']."',
            '".$stat['time']."',
            '".$stat['down']."',
            '".$stat['togo']."',
            '".$stat['location']."',
            '".$stat['away_team']."',
            '".$stat['home_team']."',
            '".$stat['detail']."'
        ) ON DUPLICATE KEY UPDATE
            game_id='".$stat['game_id']."',
            quarter='".$stat['quarter']."',
            time='".$stat['time']."',
            down='".$stat['down']."',
            togo='".$stat['togo']."',
            location='".$stat['location']."',
            away_team='".$stat['away_team']."',
            home_team='".$stat['home_team']."',
            detail='".$stat['detail']."'
        ";
        if($debug){
            print $sql.' <br />';
        }
        $mysqli->query($sql);
        # if the statement failed, print that
        if($mysqli->error){
            $failed = true;
            $output['error_sql'][] = $sql;
            $output['error_data']=$stat;
            if($failed_ts){
                print "<pre>";
                print_r($stat);
                print "</pre>";
            }
            
        }
    }

    if($debug){
        print "<pre>";
        print_r($data['snap_counts']);
        print "<pre>";
    }
    /* save snap_counts to the database
    here's the data format
    [SmitDo02] => Array
            (
                [game_id] => 202309070kan
                [player] => Donovan Smith
                [player_url] => /players/S/SmitDo02.htm
                [player_id] => SmitDo02
                [team] => KAN
                [location] => home
                [position] => T
                [offense_num] => 65
                [offense_pct] => 100%
                [defense_num] => 0
                [defense_pct] => 0%
                [special_teams_num] => 4
                [special_teams_pct] => 15%
            )
    */
    foreach($data['snap_counts'] as $stat){
        # if offense_pct has a % sign, strip it
        $stat['offense_pct'] = str_replace('%', '', $stat['offense_pct']);
        # if defense_pct has a % sign, strip it
        $stat['defense_pct'] = str_replace('%', '', $stat['defense_pct']);
        # if special_teams_pct has a % sign, strip it
        $stat['special_teams_pct'] = str_replace('%', '', $stat['special_teams_pct']);
        $stat['team'] = teamMap($stat['team']);
        # get the player id ready for insert (remove any ')
        $stat['player_id'] = str_replace("'", "", $stat['player_id']);
        $sql = "INSERT INTO player_snap_counts VALUES (null,
            '".$stat['game_id']."',
            '".$stat['player_id']."',
            '".$stat['team']."',
            '".$stat['position']."',
            '".$stat['offense_num']."',
            '".$stat['offense_pct']."',
            '".$stat['defense_num']."',
            '".$stat['defense_pct']."',
            '".$stat['special_teams_num']."',
            '".$stat['special_teams_pct']."'
        ) ON DUPLICATE KEY UPDATE
            offense_num='".$stat['offense_num']."',
            offense_pct='".$stat['offense_pct']."',
            defense_num='".$stat['defense_num']."',
            defense_pct='".$stat['defense_pct']."',
            special_teams_num='".$stat['special_teams_num']."',
            special_teams_pct='".$stat['special_teams_pct']."'
        ";
        $mysqli->query($sql);
        # if the statement failed, print that
        if($mysqli->error){
            $failed = true;
            $output['error_sql'][] = $sql;
            $output['error_data']=$stat;
            if($failed_ts){
                print "<pre>";
                print_r($stat);
                print "</pre>";
            }
        }

        # we also want to save to the player table
        # make sure the name is sanitized for insert
        $stat['player'] = $mysqli->real_escape_string($stat['player']);
        $stat['player_url'] = $mysqli->real_escape_string($stat['player_url']);
        # get the player id ready for insert (remove any ')
        $stat['player_id'] = str_replace("'", "", $stat['player_id']);
        $sql = "INSERT INTO players VALUES (null,
            '".$stat['player_id']."',
            '0',
            '".$stat['player']."',
            '".$stat['player_url']."',
            '".$stat['position']."',
            '".$stat['team']."',
            '0','',''
        ) ON DUPLICATE KEY UPDATE
            source_id='".$stat['player_id']."',
            name='".$stat['player']."',
            source_url='".$stat['player_url']."',
            position='".$stat['position']."',
            team='".$stat['team']."'
        ";
        if($debug){
            print $sql.' <br />';
        }
        $mysqli->query($sql);
        # if the statement failed, print that
        if($mysqli->error){
            $failed = true;
            $output['error_sql'][] = $sql;
            $output['error_data']=$stat;
            if($failed_ts){
                print "<pre>";
                print_r($stat);
                print "</pre>";
            }
            
        }

        #if failed is false still update this game to show it's been synced
        if(!$failed){
            $sql = "UPDATE games SET game_synced='".time()."' WHERE game_id='".$game_id."'";
            $mysqli->query($sql);
            # if the statement failed, print that
            if($mysqli->error){
                $failed = true;
                $output['error_sql'][] = $sql;
                $output['error_data']=$stat;
                if($failed_ts){
                    print "<pre>";
                    print_r($stat);
                    print "</pre>";
                }
            }
        }
    }

    if($failed){
        $output['status'] = 200;
        $output['message'] = 'Game '.$game_id.' failed';
    }else{
        $output['status'] = 100;
        $output['message'] = 'Game '.$game_id.' completed';
    }
    echo json_encode($output);
}   