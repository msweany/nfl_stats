// listen for the button click and save game data
$(document).on("click", "#update_games", function(){
    console.log("clicked");
    console.log(gamesUpdate);
    // loop through the gamesUpdate array and update the games via getGame.php?game = game_id
    $.each(gamesUpdate, function(key,value) {
        setTimeout(function() {
            $.ajax({
                url: "api/getGame.php",
                type: "GET",
                dataType: "json",
                data: {game: value},
                success: function(data) {
                    console.log(data);
                    // if status is 100, then we're good
                    if(data.status == '100'){
                        // add the game to the top of the logs
                        $("#logs").prepend("<p>" + data.message + " : " + gamesLeft +" remain</p>");
                        getGames(gamesPerRun);
                        gamesLeft = gamesLeft - 1;
                    } else {
                        // break the foreach
                        $("#logs").prepend("<p>" + data.message + "</p>");
                        return false;
                    }
                }
            });
        }, key * gamesDelay); 
    });
});