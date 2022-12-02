jQuery ( function ( $ ) {
    
    $ ( document ).on( 'submit', '#feedback-form', function( e ) {

        e.preventDefault();        

        var data = $( '#feedback-form' ).serialize();

        $.ajax( {
            type: 'POST',
            url: formObj.restURL + 'baseUrl/v1/baseEndPoint/feedback',
            beforeSend: function ( xhr ) {
                xhr.setRequestHeader( 'X-WP-Nonce', formObj.restNonce );
            },
            data: { 
                values: data               
            },
            success: function( response ) {   
                obj = JSON.parse( response );
                if ( obj.res == 'successed' ) {
                    $( '#feedback-form .form-group' ).remove();
                    $( '#feedback-form' ).append( '<div class="success-notification">Thank you for sending us your feedback.<div>' );
                }
                if ( obj.res == 'failed' ){
                    $( '#feedback-form' ).append( '<div class="failed-notification">Sorry, failed to send your feedback.<div>' );
                }
            },
            error: function (error) {
                alert('error -- ' + eval(error));
            }
        });

    });
});