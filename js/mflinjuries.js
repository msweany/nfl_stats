$(document).on("click", "#update_mfl_injuries", function(e) {
    getMflInjuries();
});

function getMflInjuries(){
    // let's show a loading icon
    $(".loading").show();
    // call api/mflPlayers.php
    $.ajax({
        url: "api/mflInjuries.php",
        type: "GET",
        dataType: "json",
        success: function(data) {
            $(".loading").hide();
            console.log(data);
            if(data.status == 100){
                $("#logs").prepend("All Injuries Updated<br />");
            }
        }
    });
}
