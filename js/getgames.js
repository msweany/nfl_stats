// set variables used in the script
var gamesUpdate = [];
var gamesPerRun = 100;
var gamesLeft = 100;
var gamesDelay = 2000; // 2000 milliseconds = 2 seconds
// check to see if there are any games that need to be saved
function getGames(count,stage=0){
    // show loading gif
    $(".loading").show();
    // get the games that need to be updated at getGames.php
    $.ajax({
        url: "api/getGames.php",
        type: "GET",
        dataType: "json",
        success: function(data) {
            if(stage == 0){
                // hide loading gif
                $(".loading").hide();
            }
            console.log(data);
            if(data.status == '100'){
                if( data.data.length < 20){
                    gamesLeft=data.data.length;
                }
                // create an array to hold games
                gamesUpdate = [];
                // hide the button to check the games
                $("#check_games").hide();
                // show the button to game_control to kick off the update
                $("#update_games").show();
                
                $("#games").html("<h2>Games to be updated</h2>");
                // create a table for all the data to be added to
                $("#games").append("<table class='table table-striped table-sm'><thead><tr><th>Game ID</th><th>Home</th><th>Away</th><th>Week</th><th>Season</th><th>Date</th></tr></thead><tbody id='games_list'></tbody></table>");
                // list the games we need to update
                var i = 1;
                $.each(data.data, function(key,val) {
                    $("#games_list").append("<tr><td>" + val.game_id + "</td><td>" + val.home + "</td><td>" + val.away + "</td><td>" + val.week + "</td><td>" + val.season + "</td><td>" + val.date + "</td></tr>");
                    // add to the gamesUpdate array
                    gamesUpdate.push(val.game_id);
                });   
            }else{
                $("#logs").prepend(data.message + "<br />");
                $("#games").html("Games all updated");
                // hide loading gif
                $(".loading").hide();
                // hide the button to update the games
                $("#update_games").hide();
                // show the button to check the games
                $("#check_games").show();
            } 
        }
    });
}

$(document).on("click", "#check_games", function(){
    getGames(gamesPerRun);
});