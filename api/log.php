<?php
header('Content-Type: application/json');
include 'connect.php';
$sql = "SELECT * FROM mfl_log WHERE status LIKE '%failed%'";
$result = $mysqli->query($sql);
if ($result->num_rows > 0) {
    $output = array(
        'status' => 100,
        'message' => 'failed players found',
        'data' => $result->num_rows
    );
} else {
    $output = array(
        'status' => 101,
        'message' => 'No failed players found'
    );
}
echo json_encode($output);