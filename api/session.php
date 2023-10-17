<?php
header('Access-Control-Allow-Headers: Content-Type, x-xsrf-token');
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
header('Content-type: application/json');
header("Access-Control-Allow-Origin: app.fozzil.net");

$key='R9V8L1AUQ0MIRV78M6NVYMGYC824MWL0UYX3XWUTC2UQRG0D7WR9D4AW8FY0B49WAYAT9BXRCOV6OHE3DCSB4ZXPQ6YUETSIX8SX';
if(isset($_POST['check'])){
    $output=array("key"=>$key);
    echo json_encode($output);
}
?>
