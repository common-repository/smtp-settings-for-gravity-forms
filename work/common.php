<?php
/** 
 * Common
 * 
 * Setup the plugins common methodology and functionality
 * 
 * @since 7.4
 * @author Kevin Pirnie <me@kpirnie.com>
 * @package Kevin's Framework
 * 
*/

// We don't want to allow direct access to this
defined( 'ABSPATH' ) || die( 'No direct script access allowed' );

// Plugin Activation
register_activation_hook( KPGFS_PATH . '/kp-gravityforms-smtp.php', function( ) : void { 

    // let's check to make sure that GravityForms is actually installed and activated
    if( ! class_exists( 'GFAPI' ) ) {

        // throw an error
        wp_die( __( '<h1>GravityForms Required</h1><p>This plugin requires GravityForms to be installed and activated.</p>', 'smtp-settings-gravity-forms' ), 
            __( 'Cannot Activate: GravityForms Required', 'smtp-settings-gravity-forms' ),
            array(
                'back_link' => true,
            ) );

    }

    // check the PHP version, and deny if lower than 7.4
    if ( version_compare( PHP_VERSION, '7.4', '<=' ) ) {

        // it is, so throw and error message and exit
        wp_die( __( '<h1>PHP To Low</h1><p>Due to the nature of this plugin, it cannot be run on lower versions of PHP.</p><p>Please contact your hosting provider to upgrade your site to at least version 7.3.</p>', 'smtp-settings-gravity-forms' ), 
            __( 'Cannot Activate: PHP To Low', 'smtp-settings-gravity-forms' ),
            array(
                'back_link' => true,
            ) );

    }

} );

// Plugin De-Activation
register_deactivation_hook( KPGFS_PATH . '/kp-gravityforms-smtp.php', function( ) : void {

    // we need to find all of our created options, and delete them
    // make sure the class exists first
    if( class_exists( 'GFAPI' ) ) {

        // get all our gravity forms
        $_forms = GFAPI::get_forms( );
        
        // loop over the list of forms
        foreach( $_forms as $_form ) {

            // get the form ID
            $_id = $_form['id'];
            
            // now delete the option for it
            delete_option( "kp_gf_smtp_settings_form_$_id" );

        }
    }
    
} );

// make sure the plugin is indeed activated
if( in_array( KPGFS_DIRNAME . '/' . KPGFS_FILENAME, apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

    // setup our autoload
    spl_autoload_register( function( $_cls ) : void {

        // reformat the class name to match the file name for inclusion
        $_class = strtolower( str_ireplace( '_', '-', $_cls ) );

        // now get the path
        $_path = KPGFS_PATH . '/work/inc/' . $_class . '.php';

        // if the file exists
        if( file_exists( $_path ) ) {

            // include it once
            include_once( $_path );
        }

    } );

    // only need to do this in admin
    add_action( 'admin_init', function( ) : void {

        // implement our settings tab and work
        $_gf_smtp_settings = new KP_GFSMTP_Settings( );

        // add our settings tab to the forms settings
        $_gf_smtp_settings -> kp_gfsmtp_add_settings_tab( );

    }, PHP_INT_MAX );

    // hook into wp initialization
    add_action( 'init', function( ) : void {

        // fire up the smtp override
        $_gf_smtp = new KP_GFSMTP_Send_Override( );

        // perform our actions
        $_gf_smtp -> kp_gf_smtp_sender( );

    }, PHP_INT_MAX );
    
}
