<?php
header('Content-Type: application/json');
include 'connect.php';
if($_POST['action']=='mfl_team_update'){
    $sql = "UPDATE `players` SET `mfl_id` = '".$_POST['mfl_id']."', team='".$_POST['mfl_team']."' WHERE id = '".$_POST['id']."'";
    $mysqli->query($sql);
    # update them in the log also
    $sql = "UPDATE mfl_log SET status = 'user clicked to update' WHERE my_id = '".$_POST['id']."'";
    $mysqli->query($sql);
}

$output = array(
    'status' => 100,
    'message' => 'updated',
    'data' => $sql
);

echo json_encode($output);