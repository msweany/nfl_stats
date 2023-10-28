//////////////////////WORIKIN ON THIS/////////////////////////

// check to see if there are game that need points updated
var calcUpdate = [];
function checkRank(){
    $.ajax({
        url: "ffl/checkRank.php",
        type: "POST",
        data: {action: 'check'},
        success: function(data){
            //console.log(data);
            if(data.count > 0 ){
                $("#logs").prepend(data.count + " games need ranked<br />");
                // add data.games_to_save.game_id to calcUpdate array
                calcUpdate.push(data.games_to_save);
            }
        }
    });
}
checkRank();

function updateRank(game_id){
    $.ajax({
        url: "ffl/getRank.php",
        type: "GET",
        data: {game: game_id, action: 'update'},
        success: function(data){
            console.log(data);
            $("#logs").prepend("Rank updated for game " + game_id + "<br />");
            // remove game from table
            $("#" + game_id).remove();
            // update the count of games that need points updated
            $("#updating_player_points").html($("#games_list tr").length + ' <i class="fa fa-spinner fa-spin" aria-hidden="true"></i>');
        }
    });
}

$(document).on("click", "#update_rank", function(){
    //console.log("clicked update player points");
    //console.log(calcUpdate);
    $("#games").html("<h2>Updating Rank Stats <span id='updating_player_points'></span></h2>");
    // loop through calcUpdate array and create a table to show the games that are being updated
    $("#games").append("<table class='table table-striped table-sm'><thead><tr><th>Game ID</th><th>Home</th><th>Away</th><th>Week</th><th>Season</th><th>Date</th></tr></thead><tbody id='games_list'></tbody></table>");
    $.each(calcUpdate, function(index, value){
        $.each(value, function(index, value){
            $("#games_list").append("<tr id='" + value.game_id +"'><td>" + value.game_id + "</td><td>" + value.home + "</td><td>" + value.away + "</td><td>" + value.week + "</td><td>" + value.season + "</td><td>" + value.date + "</td></tr>");
        });    
    });
    // put a 2 second delay then kick off the updates
    setTimeout(function(){
        $.each(calcUpdate, function(index, value){
            $.each(value, function(index, value){
                // put a delay in between each update
                setTimeout(function(){
                    updateStats(value.game_id);
                }, 500 * index);
            });    
        });
    }, 2000);
});