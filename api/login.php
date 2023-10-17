<?php
header('Access-Control-Allow-Headers: Content-Type, x-xsrf-token');
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
header('Content-type: application/json');

// hash of user name + password - username+phone ** you should move this to env var in virtual hosts file
$users = array('d4256515c673bd8f90ea02aa1226968c');

$username = strtolower($_POST['user']);
$password = $_POST['pass'];

$hash = md5($username.$password);

# check to see if they passed us a username/password
if (isset($username) && isset($password)) {
    # check to see if the username/password combination is valid
    if(in_array($hash, $users)){
        require_once("session.php");
        $output=array(
            "status"=>"100",
            "message"=>"approved",
            "key"=>$key
        );
    }else{
        $output=array(
            "status"=>"200",
            "message"=>"failed"
        );
    }
}else{
    $output=array(
        "status"=>"200",
        "message"=>"failed"
    );
}
print(json_encode($output));
?>
