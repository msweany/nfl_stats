function checkSchedule(){
    // show loading
    $(".loading").show();
    // GET api/getSchedule.php to see if there are more games available
    $.ajax({
        url: "api/getSchedule.php",
        type: "GET",
        success: function(data) {
            // hide loading
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

$(document).on("click", "#check_games", function(){
    checkSchedule();
});
