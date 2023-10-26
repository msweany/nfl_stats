<?php 
#include 'connect.php';

$tables = array('teams', 'games', 'game_info', 'game_stats', 'players', 
                'player_snap_counts', 'player_stats_adv_defense', 'player_stats_adv_passing', 
                'player_stats_adv_receiving', 'player_stats_adv_rushing', 'player_stats_defense', 
                'player_stats_kicking', 'player_stats_offense', 'player_stats_return'
            );

foreach($tables as $table) {
    $sql = "Truncate table `$table`";
    $mysqli->query($sql);
}

# rebuild the teams table
$sql = "INSERT INTO `teams` (`id`, `team`, `orig_abbr`, `abbr`, `roof`, `surface`) VALUES
(100, 'Kansas City Chiefs', 'KAN', 'KCC', 'outdoors', 'grass'),
(101, 'Detroit Lions', 'DET', 'DET', 'dome', 'fieldturf'),
(102, 'Indianapolis Colts', 'IND', 'IND', 'retractable roof ', 'fieldturf'),
(103, 'Jacksonville Jaguars', 'JAX', 'JAX', 'outdoors', 'grass'),
(104, 'Atlanta Falcons', 'ATL', 'ATL', 'retractable roof ', 'fieldturf'),
(105, 'Carolina Panthers', 'CAR', 'CAR', 'outdoors', 'grass'),
(106, 'Pittsburgh Steelers', 'PIT', 'PIT', 'outdoors', 'grass'),
(107, 'San Francisco 49ers', 'SFO', 'SFO', 'outdoors', 'grass'),
(108, 'Washington Commanders', 'WAS', 'WAS', 'outdoors', 'grass'),
(109, 'Arizona Cardinals', 'ARI', 'ARI', 'retractable roof ', 'grass'),
(110, 'Baltimore Ravens', 'BAL', 'BAL', 'outdoors', 'grass'),
(111, 'Houston Texans', 'HOU', 'HOU', 'retractable roof ', 'astroturf'),
(112, 'Cleveland Browns', 'CLE', 'CLE', 'outdoors', 'grass'),
(113, 'Cincinnati Bengals', 'CIN', 'CIN', 'outdoors', 'fieldturf'),
(114, 'New Orleans Saints', 'NOR', 'NOS', 'dome', 'sportturf'),
(115, 'Tennessee Titans', 'TEN', 'TEN', 'outdoors', 'grass'),
(116, 'Minnesota Vikings', 'MIN', 'MIN', 'dome', 'sportturf'),
(117, 'Tampa Bay Buccaneers', 'TAM', 'TBB', 'outdoors', 'grass'),
(118, 'Denver Broncos', 'DEN', 'DEN', 'outdoors', 'grass'),
(119, 'Las Vegas Raiders', 'LVR', 'LVR', 'dome', 'grass'),
(120, 'Chicago Bears', 'CHI', 'CHI', 'outdoors', 'grass'),
(121, 'Green Bay Packers', 'GNB', 'GBP', 'outdoors', 'grass'),
(122, 'Los Angeles Chargers', 'LAC', 'LAC', 'dome', 'matrixturf'),
(123, 'Miami Dolphins', 'MIA', 'MIA', 'outdoors', 'grass'),
(124, 'New England Patriots', 'NWE', 'NEP', 'outdoors', 'fieldturf'),
(125, 'Philadelphia Eagles', 'PHI', 'PHI', 'outdoors', 'grass'),
(126, 'Seattle Seahawks', 'SEA', 'SEA', 'outdoors', 'fieldturf'),
(127, 'Los Angeles Rams', 'LAR', 'LAR', 'dome', 'matrixturf'),
(128, 'New York Giants', 'NYG', 'NYG', 'outdoors', 'fieldturf'),
(129, 'Dallas Cowboys', 'DAL', 'DAL', 'retractable roof ', 'matrixturf'),
(130, 'New York Jets', 'NYJ', 'NYJ', 'outdoors', 'fieldturf'),
(131, 'Buffalo Bills', 'BUF', 'BUF', 'outdoors', 'a_turf')";
$mysqli->query($sql);

print "Done";