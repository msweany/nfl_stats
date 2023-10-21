// check how many players need to be updated
function checkMflPlayers(){
    // call ajax with a post to api/mflPlayer.php with action=check
    $.ajax({
        type: "POST",
        url: "api/mflPlayer.php",
        data: {action: "check" , limit: 100},
        success: function(data){
            console.log(data);
            // if data is not empty
            if(data){
                // update the players
                //updateMflPlayers();
            }
        }
    });  
}

$(document).on("click", "#update_mfl_player_info", function(){
    console.log("update mfl player info");
    checkMflPlayers();
});