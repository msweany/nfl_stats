var player_sleep = 2000;
var player_update_per = 1000;
// check how many players need to be updated
function checkPlayers(){
    // call ajax with a post to api/mflPlayer.php with action=check
    $.ajax({
        type: "POST",
        url: "api/getPlayer.php",
        data: {action: "check" , limit: player_update_per},
        success: function(data){
            console.log(data);
            // if data is not empty
            if(data){
                $("#games").html("<h2>Players that will be updated <span id='updated_count'></span></h2>");
                // create a table for all the data to be added to
                $("#games").append("<table class='table table-striped table-sm'><thead><tr><th>Name</th><th>Position</th><th>Team</th><th>Player ID</th></thead><tbody id='player_list'></tbody></table>");
                // list the players we need to update
                $.each(data.data, function(i, p){
                    $("#player_list").append("<tr id='"+p.source_id+"'><td>" + p.name + "</td><td>" + p.position + "</td><td>" + p.team + "</td><td>" + p.source_id + "</td></tr>");
                });
                setTimeout(function() {
                    $.each(data.data, function(i, p){
                        setTimeout(function() {
                            updatePlayer(p['source_url']);
                        }, i * player_sleep);
                    });
                }, player_sleep);
            }
        }
    });  
}

$(document).on("click", "#update_player_info", function(){
    console.log("update player info");
    checkPlayers();
});

function updatePlayer(player){
    // call ajax with a post to api/getPlayer.php with action=update
    $.ajax({
        type: "POST",
        url: "api/getPlayer.php",
        data: {action: "update", player: player},
        success: function(data){
            // if data is not empty
            if(data){
                if(data.data['message'] == "error"){
                    // log the player has been completed
                    $("#logs").prepend(data.data['player_id'] + " error<br />");
                    // mark this row addClass table-danger
                    $("#"+data.data['player_id']).addClass("table-danger");
                }else{
                    // log the player has been completed
                    $("#logs").prepend(data.data['player_id'] + " complete<br />");
                    // remove the player from the list
                    $("#"+data.data['player_id']).remove();
                    $("#updated_count").html($("#player_list tr").length);
                }
                
            }
        }
    });
}

function checkCountPlayers(){
    // call ajax with a post to api/mflPlayer.php with action=check
    $.ajax({
        type: "POST",
        url: "api/getPlayer.php",
        data: {action: "count"},
        success: function(data){
            // if data is not empty
            if(data){
                if(data.data > 0){
                    $("#logs").prepend(data.data + " birthdays to update<br />");
                }
            }
        }
    });  
}
checkCountPlayers();