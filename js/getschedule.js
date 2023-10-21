function checkSchedule(){
    // GET api/getSchedule.php to see if there are more games available
    $.ajax({
        url: "api/getSchedule.php",
        type: "GET",
        success: function(data) {
            console.log(data);
            if(data.status == 100) {
                // get the games
                getGames(gamesPerRun);
            }else{
                getGames(gamesPerRun);
                console.log("miss");
            }
        }
    });
}
    
// listen for the button click and save game data
$(document).on("click", "#update_games", function(){
    // show loading
    $(".loading").show();
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
                        $("#logs").prepend(data.message + " : " + gamesLeft +" remain<br />");
                        getGames(gamesPerRun,1);
                        gamesLeft = gamesLeft - 1;
                    } else {
                        // break the foreach
                        $("#logs").prepend(data.message + "<br />");
                        return false;
                    }
                }
            });
        }, key * gamesDelay); 
    });
});