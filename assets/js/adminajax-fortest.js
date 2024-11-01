jQuery( document ).ready( function( $ ) {

    // get the test button
    $_test_button = jQuery( '#gform_test' );

    // check if we're clicking on it
    $_test_button.on( 'click', function( ev ) {

        // prevent he default behavior
        ev.preventDefault( );

        // make sure to notify that it may take a minute
        if( ! confirm( 'The test may take a minute, please have patience and do not click this button again.' ) ) {

            // just return without doing anything else.
            return;
        }

        // setup the data to pass
        var _data = {
            'action' : 'kp_gfsmpt_test_action',
            "kp_gf_smtp_debug" : jQuery( 'input[name="kp_gf_smtp_debug"]:checked' ).val( ),
            "kp_gf_smtp_force_from" : jQuery( 'input[name="kp_gf_smtp_force_from"]:checked' ).val( ),
            "kp_gf_smtp_from_email" : jQuery( 'input[name="kp_gf_smtp_from_email"]' ).val( ),
            "kp_gf_smtp_from_name" : jQuery( 'input[name="kp_gf_smtp_from_name"]' ).val( ),
            "kp_gf_smtp_server" : jQuery( 'input[name="kp_gf_smtp_server"]' ).val( ),
            "kp_gf_smtp_enc_type" : jQuery( 'input[name="kp_gf_smtp_enc_type"]:checked' ).val( ),
            "kp_gf_smtp_port" :  jQuery( 'input[name="kp_gf_smtp_port"]' ).val( ),
            "kp_gf_smtp_user" : jQuery( 'input[name="kp_gf_smtp_user"]' ).val( ),
            "kp_gf_smtp_pass" : jQuery( 'input[name="kp_gf_smtp_pass"]' ).val( ),
            "kp_gf_force_plaintext" : jQuery( 'input[name="kp_gf_force_plaintext"]:checked' ).val( )
        };

        console.log(_data);

        // the message containner
        var _msg = jQuery( '#kp_gftest_msg' );

        // setup our ajax settings
        var _set = {
            url: kp_gfsmtp_ao.ajax_url,
            type: 'post',
            data: _data,
            beforeSend: function( ) {

                // slide down the message container and show "Please wait"
                _msg.slideDown( 'fast', function( ) {

                    // show a please hold message
                    _msg.html( '<p>Please hold... <img src="/wp-admin/images/loading.gif" alt="Please hold" /></p>' );

                } );
            },
            success: function( response ) {

                // throw a message in there
                _msg.html( "<p>" + response + "</p>" );

                // 4 second timeout for the slide up
                setTimeout( function( ) {

                    // slide the message back up
                    _msg.slideUp( "fast" );

                }, 4000 );

            },
            error: function( jqXHR, textStatus, errorThrown ) {

                // throw a message error in there
		        _msg.html( "<h4>There was an error testing your settings</h4><p>" + errorThrown + "</p>" );
            
                // slide the message down... only
                _msg.slideToggle( 'fast' );

            },
        }

        // post the data to our ajaxon functionality
        jQuery.ajax( _set );       

    } );

} );
  