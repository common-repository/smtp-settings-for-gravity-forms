<?php
/** 
 * Send Override
 * 
 * This class will be used to override the default GravityForms notifications sender
 * and pull in our form specific SMTP settings to send with
 * if the settings do not exist, it will fall back to the builtin Wordpress methodology
 * 
 * @since 7.4
 * @author Kevin Pirnie <me@kpirnie.com>
 * @package SMTP Settings for Gravity Forms
 * 
*/

// We don't want to allow direct access to this
defined( 'ABSPATH' ) || die( 'No direct script access allowed' );

// make sure the class doesnt already exist
if( ! class_exists( 'KP_GFSMTP_Send_Override' ) ) {

    /** 
     * Class KP_GFSMTP_Send_Override
     * 
     * The actual class doing the override
     * 
     * @since 7.4
     * @access public
     * @author Kevin Pirnie <me@kpirnie.com>
     * @package SMTP Settings for Gravity Forms
     * 
     * @property object $_mail The built-in mailer object
     * 
    */
    class KP_GFSMTP_Send_Override {

        // hold our mailer object
        private $_mail;

        // fire us up
        public function __construct( ) {

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
            $this -> _mail = new PHPMailer\PHPMailer\PHPMailer( true );

            // setup the defaults for PHPMailer
            $this -> _mail -> CharSet = get_bloginfo( 'charset' );
            $this -> _mail -> IsSMTP( );
            $this -> _mail -> Timeout = 10;
        
        }

        /** 
         * kp_gf_smtp_sender
         * 
         * The method overrides the default Wordpress Mail sender with a SMTP sender, per form
         * 
         * @since 7.4
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package SMTP Settings for Gravity Forms
         * 
         * @return void This method does not return anything.
         * 
        */
        public function kp_gf_smtp_sender( ) : void {

            // hook into the pre_send filter so we can abort sending the notification through gravity forms, 
            // and hook directly into wp_mail so we can potentially modify it for the SMTP settings for this form
            add_filter( 'gform_pre_send_email', function( $email, $message_format, $notification, $entry ) {

                // get the form ID
                $_form_id = $entry['form_id'];

                // get the form
                $form = GFAPI::get_form( $_form_id );

                // get the forms smtp settings
                $_settings = get_option( "kp_gf_smtp_settings_form_$_form_id" );

                // enable debug if configured
                $this -> _mail -> SMTPDebug = ( $_settings['kp_gf_smtp_debug'] ) ?? 0;

                // setup the mailer's debug output to the log file only if set
                $this -> _mail -> Debugoutput = function( $str, $level ) use( $_settings ) {

                    // get the debug
                    $_debug = ( $_settings['kp_gf_smtp_debug'] ) ?? 0;
                    
                    // if we're set to debug
                    if( $_debug != 0 ) {

                        // write to the log
                        KP_GFSMTP::write_log( $str );

                    }

                };

                // check if the settings are populated for this form, and the server is not empty
                if( $_settings && ! empty( $_settings['kp_gf_smtp_server'] ) ) {

                    // hold a valid flag
                    $_valid = true;

                    // get the notifications setup
                    $_notices = $this -> prep_notice( $notification, $form, $entry );

                    // see if we are going to override force plaintext emails
                    if( filter_var( $_settings['kp_gf_force_plaintext'], FILTER_VALIDATE_BOOLEAN ) ) {

                        // we are forcing plaintext here
                        $this -> _mail -> IsHTML( false );
                        $this -> _mail -> ContentType = 'text/plain; charset=utf-8';

                    } else {

                        // setup the content type and if the email is plain text or html
                        $this -> _mail -> IsHTML( false );

                        // should it be html?
                        if( $message_format == 'html' ) {

                            // yup, set it
                            $this -> _mail -> IsHTML( true );
                            $this -> _mail -> ContentType = 'text/html; charset=utf-8';
                        }
                    }

                    // setup the SMPT authentication properties
                    $this -> _mail -> SMTPAuth = true;
                    
                    // setup the SMTP username
                    $this -> _mail -> Username = sanitize_text_field( $_settings[ 'kp_gf_smtp_user' ] );
                    
                    // setup the SMTP password
                    $this -> _mail -> Password = KP_GFSMTP::kp_decrypt( $_settings['kp_gf_smtp_pass'], sanitize_text_field( $_settings[ 'kp_gf_smtp_user' ] ) );
                    
                    // determine which method of encryption should be used
                    switch( $_settings[ 'kp_gf_smtp_enc_type' ] ) {
                        case 1: // ssl
                            $this -> _mail -> SMTPSecure = 'ssl';
                            break;  
                        case 2: // starttls
                            $this -> _mail -> SMTPSecure = 'tls';
                        default: 
                            break;
                    }
                    
                    // setup the SMTP server
                    $this -> _mail -> Host = filter_var( $_settings[ 'kp_gf_smtp_server' ], FILTER_SANITIZE_URL );
                    
                    // setup the SMTP port
                    $this -> _mail -> Port = intval( ( $_settings[ 'kp_gf_smtp_port' ] ) ?? 587 );

                    // hold the from email address
                    $_from = '';

                    // hold the from name
                    $_from_name = '';

                    // process and send the notifications
                    if( filter_var( $_settings['kp_gf_smtp_force_from'], FILTER_VALIDATE_BOOLEAN ) ) {

                        // override the form notifications settings, and use the address from or settings.
                        $_from = ( sanitize_email( $_settings[ 'kp_gf_smtp_from_email' ] ) ) ?: get_bloginfo( 'admin_email' );

                        // set the from name
                        $_from_name = ( sanitize_text_field( $_settings[ 'kp_gf_smtp_from_name' ] ) ) ?: get_bloginfo( 'name' );
                        
                    } else {

                        // set the from
                        $_from = $_notices['from'];

                        // set the from name
                        $_from_name = $_notices['from_name'];

                    }

                    // set the from
                    if( is_email( $_from ) ) {

                        // set the From Email address and name
                        $this -> _mail -> SetFrom( $_from, $_from_name );

                    } else {

                        // log
                        KP_GFSMTP::write_log( __( 'You have an invalid FROM email address set.' ) );
                    }

                    // who's this going out to
                    if( is_numeric( $_notices['to'] ) ) {

                        // we need to look up the value
                        $_to = $entry[ $_notices['to'] ];

                    } else {

                        $_to = $_notices['to'];
                        
                    }

                    // if the to email address is valid
                    if( is_email( $_to ) ) {

                        // add the email address
                        $this -> _mail -> AddAddress( $_to );

                    } else {

                        // set the flag to false!
                        $_valid = false;

                        // log
                        KP_GFSMTP::write_log( __( 'You have an invalid TO email address set.' ) );

                    }

                    // add the CC is not empty and is a valid email address
                    if( ! empty( $_notices['cc'] ) && is_email( $_notices['cc'] ) ) {

                        // set the CC
                        $this -> _mail -> addCC( $_notices['cc'] );

                    }

                    // add the BCC is not empty and is a valid email address
                    if( ! empty( $_notices['bcc'] ) && is_email( $_notices['bcc'] ) ) {

                        // set the BCC
                        $this -> _mail -> addBCC( $_notices['bcc'] );

                    }

                    // hold a reply to email
                    $_reply_to = '';

                    // check if we're setting a reply to
                    if( ! empty( $_settings['kp_gf_smtp_replyto_email'] ) && is_email( $_settings['kp_gf_smtp_replyto_email'] ) ) {

                        // set the reply to
                        $_reply_to = ( sanitize_email( $_settings['kp_gf_smtp_replyto_email'] ) ) ?? '';
                    
                    // otherwise check the defaults
                    } else { 
                        
                        if( ! empty( $_notices['reply_to'] ) && is_email( $_notices['reply_to'] ) ) {

                            // set the REPLYTO
                            $_reply_to = $_notices['reply_to'];

                        }
                    }

                    // now if it actually exists
                    if( is_email( $_reply_to ) ) {

                        // add the reply to
                        $this -> _mail -> addReplyTo( $_reply_to );

                    }

                    // if there is an attachment
                    if( is_array( $_notices['attachments'] ) ) {

                        // we have multiple attachments, loop through them and attach to the email
                        // is simply the path to the uploaded file
                        $_aCt = count( $_notices['attachments'] );

                        // loop over the attachments
                        for( $i = 0; $i < $_aCt; ++$i ) {

                            // add the attachment
                            $this -> _mail -> addAttachment( $_notices['attachments'][$i] );
                        }
                    }

                    // set the message subject
                    $this -> _mail -> Subject = $_notices['subject'];

                    // set the body of the email
                    $this -> _mail -> Body = $_notices['message'];

                    // if we're valid
                    if( $_valid ) {

                        // try to trap this
                        try {

                            // shoot it out
                            $_sent = $this -> _mail -> Send( );

                        // catch the phpmailer exception if there is one
                        } catch ( phpmailerException $_e ) {

                            // toss the exception message into our sent variable for debugging later
                            $_sent = $_e -> errorMessage( );

                        // catch the exception if there is one
                        } catch( Exception $_e ) {

                            // toss the exception message into our sent variable for debugging later
                            $_sent = $_e -> getMessage( );

                        }

                    // we aren't
                    } else {

                        // set the sent to false
                        $_sent = false;

                    }

                    // if we are set to debug
                    if( filter_var( $_settings['kp_gf_smtp_debug'], FILTER_VALIDATE_BOOLEAN ) ) {

                        // write to the log
                        KP_GFSMTP::write_log( $_sent );

                    }
                    
                    // clear out the mail objects for the next notification
                    $this -> _mail -> clearAllRecipients( );
                    $this -> _mail -> clearReplyTos( );
                    $this -> _mail -> clearAttachments( );

                    // abort the default built-in mail sending for gravityforms, so this one gets used
                    $email[ 'abort_email' ] = true;

                // there are no settings present
                } else {

                    // write a message to the log that there are no settings
                    KP_GFSMTP::write_log( 'There are no settings configured.' );

                }

                // no matter what we need to return the email
                return $email;

            }, 10, 4 );

        }

        /** 
         * prep_notice
         * 
         * The method attempts to prep the notifications
         * using default values where there are none
         * 
         * @since 7.4
         * @access private
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package SMTP Settings for Gravity Forms
         * 
         * @param $_notify The form notification
         * @param $_form The form
         * @param $_entry The form's submitted entry
         * 
         * @return array Returns an array for defaults/values for the forum submission notifications
         * 
        */
        private function prep_notice( $_notify, $_form, $_entry ) : array {

            // get the form lead
            $_lead = RGFormsModel::get_lead( $_entry['id'] );

            // get the form notifications
            $_notice = KP_GFSMTP_Extend_Common::send_notification( $_notify, $_form, $_lead );

            // setup the inner variables so we can account for necessary values
            $_to = ( ! empty( $_notice['to'] ) ) ? GFCommon::replace_variables( $_notice['to'], $_form, $_entry ) : get_bloginfo( 'admin_email' );
            $_from = ( ! empty( $_notice['from'] ) ) ? GFCommon::replace_variables( $_notice['from'], $_form, $_entry ) : get_bloginfo( 'admin_email' ); 
            $_from_name = ( ! empty( $_notice['from_name'] ) ) ? GFCommon::replace_variables( $_notice['from_name'], $_form, $_entry ) : '';
            $_cc = ( ! empty( $_notice['cc'] ) ) ? GFCommon::replace_variables( $_notice['cc'], $_form, $_entry ) : '';
            $_bcc = ( ! empty( $_notice['bcc'] ) ) ? GFCommon::replace_variables( $_notice['bcc'], $_form, $_entry ) : '';
            $_reply_to = ( ! empty( $_notice['replyTo'] ) ) ? GFCommon::replace_variables( $_notice['replyTo'], $_form, $_entry ) : '';
            $_subject = ( ! empty( $_notice['subject'] ) ) ? GFCommon::replace_variables( $_notice['subject'], $_form, $_entry ) : '';
            $_message = ( ! empty( $_notice['message'] ) ) ? GFCommon::replace_variables( $_notice['message'], $_form, $_entry ) : '';
            $_attachments = ( $_notice['attachments'] ) ?? '';

            // setup the return array
            $_ret = array(
                'to' => $_to,
                'from' => $_from,
                'from_name' => $_from_name,
                'cc' => $_cc,
                'bcc' => $_bcc,
                'reply_to' => $_reply_to,
                'subject' => $_subject,
                'message' => $_message,
                'attachments' => $_attachments,
            );

            // return the array
            return $_ret;
        }   

    }

}
