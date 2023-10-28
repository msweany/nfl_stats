//////////////////////WORIKIN ON THIS/////////////////////////

// check to see if there are game that need points updated
var rankUpdate = [];
var currentSeason = 0;
function checkRank(){
    $.ajax({
        url: "ffl/checkRank.php",
        type: "POST",
        data: {action: 'check'},
        success: function(data){
            //console.log(data);
            currentSeason = data.current_season;
            if(data.count_total > 0 ){
                $("#logs").prepend(data.count_total + " games need ranked<br />");
                $("#logs").prepend(data.count_weeks + " weeks from this season<br />");
                // add data.games_to_save.game_id to calcUpdate array
                rankUpdate.push(data.games_to_save);
            }
        }
    });
}
checkRank();

function updateRank(week,season){
    $.ajax({
        url: "ffl/getRank.php",
        type: "GET",
        data: {week: week, year: season},
        success: function(data){
            console.log(data);
            $("#logs").prepend("Rank updated for week " + week + "<br />");
            // remove week from table
            $("#week_" + week).remove();
            // update the count of games that need points updated
            $("#updating_rank").html($("#games_list tr").length + ' <i class="fa fa-spinner fa-spin" aria-hidden="true"></i>');
        }
    });
}

$(document).on("click", "#update_rank", function(){
    console.log("clicked update rank");
    $("#games").html("<h2>Games to update</h2><table id='games_list' class='table table-striped table-bordered'><thead><tr><th>GameId</th><th>Week</th><th>Home</th><th>Away</th></tr></thead><tbody></tbody></table>");
    $.each(rankUpdate, function(index, value){
        $.each(value, function(index, value){
            $.each(value, function(index, value){
                // this gets us the weeks, now we can loop through them and update the rank
                $("#games_list tbody").append("<tr id='week_" + value.week + "'><td>" + value.game_id + "</td><td>" + value.week + "</td><td>" + value.home + "</td><td>" + value.away + "</td></tr>");
            });
        });
    });
    // delay 2 seconds, then run this
    setTimeout(function(){
        $.each(rankUpdate, function(index, value){
            $.each(value, function(index, value){
                // this gets us the weeks, now we can loop through them and update the rank
                console.log(index);
                updateRank(index, currentSeason);
            });
        });
    }, 2000);
    
});