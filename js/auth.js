function checkAuth(){
    if(localStorage.getItem('fozzil_nfl_TNHF3QMUXOBGGO9OR1IPY5O86')){
        // check to see if the localstorage matches the session variable
        $.post( "api/session.php", { "check":"true" })
            .done(function( data ) {
            if(data.key == localStorage.getItem('fozzil_nfl_TNHF3QMUXOBGGO9OR1IPY5O86')){
                // show the page
                $(".container").show();
            }else{
                // redirect them to the login page
                window.location.replace("login.html");
            }
        });
    }else{
        // redirect them to the login page
        window.location.replace("login.html");
    }
}
