$(document).on("click", "#update_mfl_players", function(e) {
    e.preventDefault();
    getMflPlayers();
});

function getMflPlayers(){
    // let's show a loading icon
    $(".loading").show();
    // call api/mflPlayers.php
    $.ajax({
        url: "api/mflPlayers.php",
        type: "GET",
        dataType: "json",
        success: function(data) {
            $(".loading").hide();
            console.log(data);
            if(data.status == 100){
                if(data.data.length == 0){
                    $("#logs").prepend("No Players To Review");
                }else{
                    $("#games").html("<h2>Players To Review</h2>");
                    // create a table, then append to it
                    $('#games').append('<table id="players" class="table table-striped table-sm"><thead><tr class="table-primary"><th>Player</th><th>Team</th><th>Position</th><th>Score</th><th></th></tr></th></tr></thead><tbody></tbody></table>');
                    // loop thorugh the data and add to the table
                    var save_id = '';
                    $.each(data.data, function(key,val) {
                        $.each(val, function(k,v) {
                            if(k == 'failed'){
                                // append to the body of the table
                                $('#players tbody').append('<tr class="player_'+v.my_id+'"><td>'+v.name+'</td><td>'+v.team+'</td><td>'+v.position+'</td><td>'+v.score+'</td><td><button vals="'+v.my_id+':'+v.id+':'+v.team+'" class="btn btn-outline-secondary btn-sm update_mfl_team"><i class="fa fa-arrow-circle-o-up" aria-hidden="true"></i></button></td></tr>');
                            }else{
                                save_id = v.id;
                                $('#players tbody').append('<tr class="player_'+v.id+'"><td>'+v.name+'</td><td>'+v.team+'</td><td>'+v.position+'</td><td>'+v.mfl_id+'</td><td></td></tr>');
                            }
                        });
                        // add a row beween each set
                        $('#players tbody').append('<tr class="table-primary player_'+save_id+'"><td colspan="5">&nbsp;</td></tr>');
                    });
                }
                
            }

        }
    });
}

$(document).on("click", ".update_mfl_team", function(e) {
    console.log($(this).attr('vals'));
    // split the results by :
    var vals = $(this).attr('vals').split(':');
    // if there are 2 valus, then it's just an ID update
    if(vals.length == 2){
        //updateMflPlayer(vals[0],vals[1]);  
        // send a post to api/setPlayer.php
        $.ajax({
            url: "api/setPlayer.php",
            type: "POST",
            dataType: "json",
            data: {id: vals[0], mfl_id: vals[1], action: 'mfl_id_update'},
            success: function(data) {
                console.log(data);
                if(data.status == 100){
                    $(".player_"+vals[0]).hide();
                    $("#logs").prepend(vals[0]+' updated<br>');
                }
            }
        }); 
    }
    // 3 we update the team name also
    if(vals.length == 3){
        //updateMflTeam(vals[0],vals[1],vals[2]);
        $.ajax({
            url: "api/setPlayer.php",
            type: "POST",
            dataType: "json",
            data: {id: vals[0], mfl_id: vals[1], mfl_team: vals[2], action: 'mfl_team_update'},
            success: function(data) {
                console.log(data);
                if(data.status == 100){
                    $(".player_"+vals[0]).hide();
                    $("#logs").prepend(vals[0]+' updated<br>');
                }
            }
        });
    }

});