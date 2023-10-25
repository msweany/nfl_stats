<?php
header('Content-Type: application/json');
include 'functions.php';

if($_POST['action'] == 'count'){
    $data = countPlayersWithoutBday();
}

if($_POST['action'] == 'check'){
    $data = checkPlayers($_POST['limit']);
}

if($_POST['action'] == 'update'){
    
    # lets try to use curl to get the JSON our function
    $base_url = "https://ffl-stuff.azurewebsites.net/api/getPlayer?code=".getenv('FFL_API_KEY')."&player=";
    //print $base_url;
    $output = array();
    # loop through the results and get the game info
    if(isset($_POST['player'])){
        $url = $base_url.$_POST['player'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);

        curl_close($ch);

        # split the player by / and get the last item
        $player_break = explode('/', $_POST['player']);
        # remove the .htm at the end of the player
        $player_id = explode('.', $player_break[3])[0];


        $data = json_decode($result, true);
        $data['player_id'] = $player_id;
        
        // update the player
        $update = updatePlayer($data);
        $data['player_id'] = $player_id;
        if($update == 1){
            $data['message'] = 'error';
        }else{
            $data['message'] = 'success';
        }
    }
}

$output = (object)array(
    'message' => date("m/d/Y g:i a", time()),
    'status' => '100',
    'data' => $data
);
echo json_encode($output);