// call api/log.php to see if there are any new log entries
function checkLog(){
    $.ajax({
        url: "api/log.php",
        type: "GET",
        dataType: "json",
        success: function(data) {
            if(data.status == 100){ 
                if(data.data == 1){
                    $("#logs").prepend(data.data + " player failed in mfl_log<br />");
                }else{
                    $("#logs").prepend(data.data + " players failed in mfl_log<br />");
                }
            }else{
                $("#logs").prepend("No players failed in mfl_log<br />");
            }
        }
    });
}
checkLog();