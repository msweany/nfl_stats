// set variables used in the script
var gamesUpdate = [];
var gamesPerRun = 100;
var gamesLeft = 100;
var gamesDelay = 2000; // 2000 milliseconds = 2 seconds
// check to see if there are any games that need to be saved
function getGames(count){
    // get the games that need to be updated at getGames.php
    $.ajax({
        url: "api/getGames.php",
        type: "GET",
        dataType: "json",
        success: function(data) {
            //console.log(data);
            if(data.status == '100'){
                if( data.data.length < 20){
                    gamesLeft=data.data.length;
                }
                $("#games_count").html("<p>Games to be updated " + data.data.length + "</p>");
                // create an array to hold games
                gamesUpdate = [];
                // add the button to game_control to kick off the update
                $("#game_control").html("<button id='update_games' class='btn btn-primary' style='margin-bottom: 5px'>Update Games</button>");
                // list the games we need to update
                var i = 1;
                $.each(data.data, function(key,value) {
                    var gamesList = $("<ul class='list-group' style='width: 300px; margin-bottom: 8px'></ul>");
                    // loop through the resulting  and append the games to the list
                    //$("#games").append("<li>" + key + " " +value + "</li>");
                    
                    $.each(value, function(key,val) {
                        gamesList.append("<li class='list-group-item'>" + key + " " +val + "</li>");
                        if(key=='game_id'){
                            if(i<=count){
                                // add this val to the games array
                                gamesUpdate.push(val);
                            }
                            i++;
                        }
                    });
                    $("#games").append(gamesList);
                });   
            }else{
                $("#games").html("<p>" + data.message + "</p>");
            } 
        }
    });
}