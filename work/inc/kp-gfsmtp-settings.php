<?php
/** 
 * SMTP Per Form Settings
 * 
 * Build out our form specific SMTP settings
 * 
 * @since 7.4
 * @author Kevin Pirnie <me@kpirnie.com>
 * @package SMTP Settings for Gravity Forms
 * 
*/

// We don't want to allow direct access to this
defined( 'ABSPATH' ) || die( 'No direct script access allowed' );

// make sure the class doesnt already exist
if( ! class_exists( 'KP_GFSMTP_Settings' ) ) {

    /** 
     * Class KP_GFSMTP_Settings
     * 
     * The actual class creating the settings per form
     * 
     * @since 7.4
     * @access public
     * @author Kevin Pirnie <me@kpirnie.com>
     * @package SMTP Settings for Gravity Forms
     * 
     * @property object $_can_proceed Can we proceed?
     * 
    */
    class KP_GFSMTP_Settings {

        // set a "can proceed" variable that is set during class contruction
        private $_can_proceed;

        // fire us up
        public function __construct( ) {

            // set a variable if we can proceed or not
            $this -> _can_proceed = true;

            // this is just in case the initial check is somehow bypassed
            if ( ! class_exists( 'GFAPI' ) ) {

                // display an admin notice that we aren't setup properly
                add_action( 'admin_notices', function( ) : void {
                    echo '<div class="error">';
                    _e( '  <h1><span class="dashicons dashicons-dismiss" style="padding-top:5px;"></span> Whoops!</h1>', 'smtp-settings-gravity-forms' );
                    _e( '  <p>Something went wrong with your setup.</p>', 'smtp-settings-gravity-forms' );
                    _e( '  <p>Please make sure you have the latest GravityForms installed and activated.</p>', 'smtp-settings-gravity-forms' );
                    echo '</div>';
                } );

                // set the "can proceed" flag
                $this -> _can_proceed = false;

                // run the plugin deactivation hook
                deactivate_plugins( KPGFS_DIRNAME . '/' . KPGFS_FILENAME );

            }

            // if we can proceed
            if( $this -> _can_proceed ) {

                // we need some javascript so we can test the settings via ajax.  enqueue it
                add_action( 'admin_enqueue_scripts', function( ) : void {

                    // check if we're debugging
                    if( defined( 'WP_DEBUG' ) && WP_DEBUG ) {

                        // we are, so we can queue up the unminified version
                        wp_enqueue_script( 'kp_gf_smtptest_script', plugin_dir_url( KPGFS_DIRNAME . '/' . KPGFS_FILENAME ) . '/assets/js/adminajax-fortest.js', array( 'jquery' ) );

                    } else {

                        // we're not debugging so queue up the minified version
                        wp_enqueue_script( 'kp_gf_smtptest_script', plugin_dir_url( KPGFS_DIRNAME . '/' . KPGFS_FILENAME ) . '/assets/js/script.min.js', array( 'jquery' ) );
                    }

                    // localize our ajax
                    wp_localize_script( 'kp_gf_smtptest_script', 'kp_gfsmtp_ao', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), ) );

                }, PHP_INT_MAX );

                // now add in our ajaxian hook
                add_action( 'wp_ajax_kp_gfsmpt_test_action', function( ) : void {

                    // check if the SMTP username is an email address
                    if( is_email( $_POST['kp_gf_smtp_user'] ) ) {

                        // sanitize it
                        $_smtp_u = sanitize_email( $_POST['kp_gf_smtp_user'] );

                    // it's not
                    } else {

                        // sanitize it as a string
                        $_smtp_u = sanitize_text_field( $_POST['kp_gf_smtp_user'] );

                    }
            
                    // hold our form posted fields
                    $_smtp_password = sanitize_text_field( $_POST['kp_gf_smtp_pass'] );
                    $_smtp_server = sanitize_text_field( $_POST['kp_gf_smtp_server'] );
                    $_smtp_port = sanitize_text_field( $_POST['kp_gf_smtp_port'] );
                    $_smtp_enc_type = sanitize_text_field( $_POST['kp_gf_smtp_enc_type'] );

                    // if there is no smtp server, then there's nothing to do
                    if( ! empty( $_smtp_server ) ) {

                        // setup the path to the phpmailer
                        $_phpmailer_path = ABSPATH . WPINC . '/PHPMailer';

                        // require our phpmailer class files
                        foreach ( scandir( $_phpmailer_path ) as $filename ) {
                
                            // get our full path
                            $path = $_phpmailer_path . '/' . $filename;
                            
                            // if we have a file
                            if ( is_file( $path ) ) {
                
                                // include it
                                include_once ( $path );
                            }
                        }

                        // fire up the mailer class
                        $_mail = new PHPMailer\PHPMailer\PHPMailer( true );

                        // setup the defaults for PHPMailer
                        $_mail -> CharSet = get_bloginfo( 'charset' );
                        $_mail -> IsSMTP( );
                        $_mail -> Timeout = 10;

                        $_mail -> SMTPAuth = true;
                        $_mail -> Username = ( $_smtp_u ) ?? null;
                        $_mail -> Password = ( $_smtp_password ) ?? null;
                        switch( $_smtp_enc_type ) {
                            case 1: // ssl
                                $_mail -> SMTPSecure = 'ssl';
                                break;  
                            case 2: // starttls
                                $_mail -> SMTPSecure = 'tls';
                            default: 
                                break;
                        }
                        $_mail -> Host = ( $_smtp_server ) ?? null;
                        $_mail -> Port = ( $_smtp_port ) ?? 587;

                        // try to connect
                        try {

                            // set the connection to a "valid" flag
                            $_valid = $_mail -> SmtpConnect( );

                            // if valid is true, it succeeded
                            if( $_valid ){

                                // successful test
                                _e( 'Your test was sucessful.  Do not forget to save your settings.', 'smtp-settings-gravity-forms' );
                            } else {

                                // not successful test
                                _e( 'Your test was not sucessful.', 'smtp-settings-gravity-forms' );
                            }

                        } catch( Exception $e ) { 
                            
                            // connection no good... dump the error
                            _e( $e -> getMessage( ), 'smtp-settings-gravity-forms' );    
                        }
                        
                    } else {

                        // no smtp server, so there';'s nothing to test
                        _e( 'There is no SMTP server configured, so there is no need to test.', 'smtp-settings-gravity-forms' );

                    }

                    // make sure to gracefully kill wordpress for this
                    wp_die( );

                } );

            }

        }

        /** 
         * kp_gfsmtp_add_settings_tab
         * 
         * Create our settings tab
         * 
         * @since 7.4
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package SMTP Settings for Gravity Forms
         * 
         * @return void This method does not return anything.
         * 
        */
        public function kp_gfsmtp_add_settings_tab( ) : void {

            // make sure we can proceed
            if( $this -> _can_proceed ) {

                // create the settings menu item and assign it a name
                add_filter( 'gform_form_settings_menu', function( $menu_items ) : array {

                    $menu_items[] = array(
                        'name' => 'kp_gf_smtp_settings',
                        'label' => __( 'SMTP Settings', 'smtp-settings-gravity-forms' ),
                        'icon' => 'dashicons-admin-settings dashicons'
                        );
                
                    return $menu_items;

                } );

                // add the actual settings page
                add_action( 'gform_form_settings_page_kp_gf_smtp_settings', function( ) : void {

                    // get some form specific data
                    require_once( GFCommon::get_base_path( ) . '/form_detail.php' );

                    // get the forms ID
                    $_form_id = rgget( 'id' );

                    // by default, set this to false
                    $_res = false;

                    // do we have a post?
                    if ( $_POST && ( $_POST['kp_gform_smtp'] == 'yeppers' ) ) {

                        // check if the SMTP username is an email address
                        if( is_email( $_POST['kp_gf_smtp_user'] ) ) {

                            // sanitize it
                            $_smtp_u = sanitize_email( $_POST['kp_gf_smtp_user'] );

                        // it's not
                        } else {

                            // sanitize it as a string
                            $_smtp_u = sanitize_text_field( $_POST['kp_gf_smtp_user'] );

                        }

                        // add our new settings, let's put these in options.  that way when GF decides to change their 
                        // table structure... again... they will be preserved
                        $_args = array(
                            'kp_gf_smtp_force_from' => intval( $_POST['kp_gf_smtp_force_from'] ),
                            'kp_gf_smtp_from_email' => sanitize_email( $_POST['kp_gf_smtp_from_email'] ) ?? get_bloginfo( 'admin_email' ),
                            'kp_gf_smtp_from_name' => sanitize_text_field( $_POST['kp_gf_smtp_from_name'] ),
                            'kp_gf_smtp_replyto_email' => sanitize_email( $_POST['kp_gf_smtp_replyto_email'] ),
                            'kp_gf_smtp_server' => sanitize_text_field( $_POST['kp_gf_smtp_server'] ),
                            'kp_gf_smtp_port' => intval( $_POST['kp_gf_smtp_port'] ) ?? 587,
                            'kp_gf_smtp_enc_type' => intval( $_POST['kp_gf_smtp_enc_type'] ),
                            'kp_gf_smtp_user' => $_smtp_u ?? get_bloginfo( 'admin_email' ),
                            'kp_gf_smtp_pass' => KP_GFSMTP::kp_encrypt( $_POST['kp_gf_smtp_pass'] ),
                            'kp_gf_smtp_debug' => intval( $_POST['kp_gf_smtp_debug'] ),
                            'kp_gf_force_plaintext' => intval( $_POST['kp_gf_force_plaintext'] )
                        );

                        // check if the option alread exists
                        if( ! get_option( "kp_gf_smtp_settings_form_$_form_id" ) ){

                            // add the option
                            add_option( "kp_gf_smtp_settings_form_$_form_id", $_args );
                            
                        } else {

                            // update the option instead
                            update_option( "kp_gf_smtp_settings_form_$_form_id", $_args );

                        }

                        $_res = true;
                        
                    }

                    // form settings header
                    GFFormSettings::page_header( );

                    // if the save was successful, show a message
                    if( $_res ) {
                        ?>
                        <div class="updated below-h2" id="after_update_dialog">
                            <p>
                                <strong><?php _e( 'SMTP settings updated successfully.', 'smtp-settings-gravity-forms' ); ?></strong>
                            </p>
                        </div>
                        <?php
                    }

                    _e( '<h3><span><i class="fa fa-inbox"></i></span> SMTP Settings</h3>', 'smtp-settings-gravity-forms' );
                    _e( '<p>Configure your SMTP settings for this form below.  Leaving the HOST field blank will use the built-in Wordpress functionality, including any SMTP plugin you may have currently installed and active.</p>', 'smtp-settings-gravity-forms' );
                    ?>
                    <div class="gform_panel gform_panel_form_settings" id="smtp_settings">
                        <form action="" method="post" id="gform_form_settings">
                            <table class="gforms_form_settings" cellspacing="0" cellpadding="0">
                                <?php $this -> kp_gfsmtp_add_the_fields( $_form_id ); // add in the forms fields ?>
                            </table>
                            <div id="kp_gftest_msg" class="updated below-h2" style="display:none;"></div>
                        </form>
                    </div>
                    <?php
                    
                    // form settings footer
                    GFFormSettings::page_footer( );

                } );
                
            }

        }

        /** 
         * kp_gfsmtp_add_the_fields
         * 
         * Add in our settings fields
         * 
         * @since 7.4
         * @access private
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package SMTP Settings for Gravity Forms
         * 
         * @param int $form_id The form ID
         * 
         * @return void This method does not return anything.
         * 
        */
        private function kp_gfsmtp_add_the_fields( int $form_id ) : void {

            // get the form's SMTP settings
            $_smtp_settings = get_option( "kp_gf_smtp_settings_form_$form_id" );

            // set them to variables, so we can display in the form fields
            $_from_email = ( $_smtp_settings['kp_gf_smtp_from_email'] ) ?? get_bloginfo( 'admin_email' );
            $_from_name = ( $_smtp_settings['kp_gf_smtp_from_name'] ) ?? null;
            $_replyto_email = ( $_smtp_settings['kp_gf_smtp_replyto_email'] ) ?? null;
            $_host_val = ( $_smtp_settings['kp_gf_smtp_server'] ) ?? null;
            $_port_val = ( $_smtp_settings['kp_gf_smtp_port'] ) ?? 587;
            $_enc_val = ( $_smtp_settings['kp_gf_smtp_enc_type'] ) ?? 0;
            $_user_val = ( $_smtp_settings['kp_gf_smtp_user'] ) ?? get_bloginfo( 'admin_email' );
            
            $_tmp_pass = ( $_smtp_settings['kp_gf_smtp_pass'] ) ?? null;
            $_pass_val = KP_GFSMTP::kp_decrypt( $_tmp_pass );
            $_ff_val = ( $_smtp_settings['kp_gf_smtp_force_from'] ) ?? 0;
            $_debug_val = ( $_smtp_settings['kp_gf_smtp_debug'] ) ?? 0;
            $_plaintext = ( $_smtp_settings['kp_gf_force_plaintext'] ) ?? 0;

            ?>
                <tr>
                    <th><?php _e( 'Debug SMTP?', 'smtp-settings-gravity-forms' ); ?></th>
                    <td>
                        <input type="radio" name="kp_gf_smtp_debug" value="0"<?php if( $_debug_val == 0 ) echo " checked"; ?> /> <?php _e( 'No', 'smtp-settings-gravity-forms' ); ?>
                        <input type="radio" name="kp_gf_smtp_debug" value="3"<?php if( $_debug_val == 3 ) echo " checked"; ?> /> <?php _e( 'Yes', 'smtp-settings-gravity-forms' ); ?>
                        <small><br /><?php _e( '<strong>NOTE: </strong> This will write full SMTP debug information to the file <code>' . ABSPATH . 'wp-content/gf-smtp-log.log</code> on form submission.', 'smtp-settings-gravity-forms' ); ?></small>
                    </td>
                </tr>
                <tr>
                    <th><?php _e( 'Force From Override?', 'smtp-settings-gravity-forms' ); ?></th>
                    <td>
                        <input type="radio" name="kp_gf_smtp_force_from" value="0"<?php if( $_ff_val == 0 ) echo " checked"; ?> /> <?php _e( 'No', 'smtp-settings-gravity-forms' ); ?>
                        <input type="radio" name="kp_gf_smtp_force_from" value="1"<?php if( $_ff_val == 1 ) echo " checked"; ?> /> <?php _e( 'Yes', 'smtp-settings-gravity-forms' ); ?>
                        <small><br /><?php _e( '<strong>NOTE: </strong> Some providers like Office365, require the same From email address as is being used to send the email.<br />By default this plugin will not override the forms settings, check this to force it to.', 'smtp-settings-gravity-forms' ); ?></small>
                    </td>
                </tr>
                <tr>
                    <th><?php _e( 'From Email Address: ', 'smtp-settings-gravity-forms' ); ?></th>
                    <td><input type="email" name="kp_gf_smtp_from_email" value="<?php echo $_from_email ?>" />
                    <br /><small>
                        <?php _e( 'Use this to override the form\'s sending address, if you have the above checked, it must match the SMTP User below.<br />If this is left blank and the Force is allowed, it will fall back to the site\'s configured admin email address.', 'smtp-settings-gravity-forms' ); ?>
                        </small>
                    </td>
                </tr>
                <tr>
                    <th><?php _e( 'From Name: ', 'smtp-settings-gravity-forms' ); ?></th>
                    <td><input type="text" name="kp_gf_smtp_from_name" value="<?php echo $_from_name ?>" />
                    <small>(<?php _e( 'Optional', 'smtp-settings-gravity-forms' ); ?>)</small></td>
                </tr>
                <tr>
                    <th><?php _e( 'Reply-To Email Address: ', 'smtp-settings-gravity-forms' ); ?></th>
                    <td><input type="email" name="kp_gf_smtp_replyto_email" value="<?php echo $_replyto_email ?>" />
                    <small>(<?php _e( 'Optional', 'smtp-settings-gravity-forms' ); ?>)</small></td>
                </tr>
                <tr>
                    <th><?php _e( 'SMTP Host: ', 'smtp-settings-gravity-forms' ); ?></th>
                    <td><input type="text" name="kp_gf_smtp_server" value="<?php echo $_host_val ?>" /></td>
                </tr>
                <tr>
                    <th><?php _e( 'Encryption Type: ', 'smtp-settings-gravity-forms' ); ?></th>
                    <td>
                        <input type="radio" name="kp_gf_smtp_enc_type" value="0"<?php if( $_enc_val == 0 ) echo " checked"; ?> /> <?php _e( 'None', 'smtp-settings-gravity-forms' ); ?>
                        <input type="radio" name="kp_gf_smtp_enc_type" value="1"<?php if( $_enc_val == 1 ) echo " checked"; ?> /> <?php _e( 'SSL', 'smtp-settings-gravity-forms' ); ?>
                        <input type="radio" name="kp_gf_smtp_enc_type" value="2"<?php if( $_enc_val == 2 ) echo " checked"; ?> /> <?php _e( 'STARTTLS', 'smtp-settings-gravity-forms' ); ?>
                    </td>
                </tr>
                <tr>
                    <th><?php _e( 'SMTP Port: ', 'smtp-settings-gravity-forms' ); ?></th>
                    <td><input type="number" name="kp_gf_smtp_port" value="<?php echo $_port_val ?>" /></td>
                </tr>
                <tr>
                    <th><?php _e( 'SMTP Username: ', 'smtp-settings-gravity-forms' ); ?></th>
                    <td><input type="text" name="kp_gf_smtp_user" value="<?php echo $_user_val ?>" /></td>
                </tr>
                <tr>
                    <th><?php _e( 'SMTP Password: ', 'smtp-settings-gravity-forms' ); ?></th>
                    <td><input type="password" name="kp_gf_smtp_pass" value="<?php echo $_pass_val ?>" /></td>
                </tr>
                <tr>
                    <th><?php _e( 'Force Plain Text Emails?', 'smtp-settings-gravity-forms' ); ?></th>
                    <td>
                        <input type="radio" name="kp_gf_force_plaintext" value="0"<?php if( $_plaintext == 0 ) echo " checked"; ?> /> <?php _e( 'No', 'smtp-settings-gravity-forms' ); ?>
                        <input type="radio" name="kp_gf_force_plaintext" value="1"<?php if( $_plaintext == 1 ) echo " checked"; ?> /> <?php _e( 'Yes', 'smtp-settings-gravity-forms' ); ?>
                        <small><br /><?php _e( '<strong>NOTE: </strong> This will force plaintext emails to be sent.', 'smtp-settings-gravity-forms' ); ?></small>
                    </td>
                </tr>
                <tr>
                    <th>&nbsp;</th>
                    <td>
                        <input type="hidden" name="kp_gform_smtp" value="yeppers" />
                        <input type="submit" name="gform_save_settings" value="<?php _e( 'Update SMTP Settings', 'smtp-settings-gravity-forms' ); ?>" class="button-primary gfbutton" />
                        <button id="gform_test" class="button-primary gfbutton" style="background:#f3f5f6 !important;color:#0071a1 !important;">
                            <?php _e( 'Test SMTP Settings', 'smtp-settings-gravity-forms' ); ?>
                        </button>
                    </td>
                </tr>
            <?php
        }

    }

}
