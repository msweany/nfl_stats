function checkSchedule(){
    $("#game_control").html('  <i class="fa fa-spinner fa-spin fa-2x" aria-hidden="true"></i>');
    // GET api/getSchedule.php to see if there are more games available
    $.ajax({
        url: "api/getSchedule.php",
        type: "GET",
        success: function(data) {
            $("#game_control").html('');
            console.log(data);
            if(data.status == 100) {
                console.log("hit");
                // there are more games to scrape
                // get the games
                getGames(gamesPerRun);
            }else{
                console.log("miss");
            }
            // check for any games that can be scraped
            //getGames(gamesPerRun);
        }
    });
}
    
