<?php
/** 
 * Global plugin class
 * 
 * This class contains some common methods
 * 
 * @since 7.4
 * @author Kevin Pirnie <me@kpirnie.com>
 * @package SMTP Settings for Gravity Forms
 * 
*/

// We don't want to allow direct access to this
defined( 'ABSPATH' ) OR die( 'No direct script access allowed' );

// make sure the class doesnt already exist
if( ! class_exists( 'KP_GFSMTP' ) ) {

    /** 
     * Class KP_GFSMTP
     * 
     * The actual class containing the global static methods
     * 
     * @since 7.4
     * @access public
     * @author Kevin Pirnie <me@kpirnie.com>
     * @package SMTP Settings for Gravity Forms
     * 
    */
    class KP_GFSMTP {

        /** 
         * kp_encrypt
         * 
         * This method attempts to encrypt the string passed
         * 
         * @since 7.4
         * @access public
         * @static
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package SMTP Settings for Gravity Forms
         * 
         * @param string $_str A Nullable string to be encrypted
         * 
         * @return string Returns an encrypted string
         * 
        */
        public static function kp_encrypt( ?string $_str ) : string {

            // hold our return
            $_ret = '';

            // make sure the openssl library exists
            if( ! function_exists( 'openssl_encrypt' ) ) {

                // it does not, so all we can really do is base64encode the string
                $_ret = base64_encode( $_str );

            // otherwise
            } else {

                // the encryption method
                $_enc_method = "AES-256-CBC";

                // generate a key based on the SECURE_AUTH_KEY from wp-config.php
                $_key = hash( 'sha256', SECURE_AUTH_KEY );

                // generate an initialization vector based on the SECURE_AUTH_SALT from wp-config.php
                $_iv = substr( hash( 'sha256', SECURE_AUTH_SALT ), 0, 16 );

                // return the base64 encoded version of our encrypted string
                $_ret = base64_encode( openssl_encrypt( $_str, $_enc_method, $_key, 0, $_iv ) );

            }

            // return our string
            return $_ret;

        }

        /** 
         * kp_decrypt
         * 
         * This method attempts to decrypt the string passed
         * 
         * @since 7.4
         * @access public
         * @static
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package SMTP Settings for Gravity Forms
         * 
         * @param string $_str A Nullable string to be decrypted
         * 
         * @return string Returns a decrypted string
         * 
        */
        public static function kp_decrypt( ?string $_str ) : string {

            // hold our return
            $_ret = '';

            // make sure the openssl library exists
            if( ! function_exists( 'openssl_decrypt' ) ) {

                // it does not, so all we can really do is base64decode the string
                $_ret = base64_decode( $_str );

            // otherwise
            } else {

                // the encryption method
                $_enc_method = "AES-256-CBC";

                // generate a key based on the SECURE_AUTH_KEY from wp-config.php
                $_key = hash( 'sha256', SECURE_AUTH_KEY );

                // generate an initialization vector based on the SECURE_AUTH_SALT from wp-config.php
                $_iv = substr( hash( 'sha256', SECURE_AUTH_SALT ), 0, 16 );

                // return the decrypted string
                $_ret = openssl_decrypt( base64_decode( $_str ), $_enc_method, $_key, 0, $_iv );

            }

            // return our stringn
            return $_ret;

        }

        /** 
         * write_log
         * 
         * Public static method to 
         * 
         * @since 7.4
         * @access public
         * @static
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package The Cache Purger
         * 
         * @return void This method does not return anything
         * 
        */
        public static function write_log( string $_msg ) : void {

            // set a path to hold the purge log
            $_path = ABSPATH . 'wp-content/gf-smtp-log.log';

            // I want to append a timestamp to the message
            $_message = '[' . current_time( 'mysql' ) . ']: ' . __( $_msg, 'smtp-settings-gravity-forms' );

            // unfortunately we cannot use wp's builtin filesystem hanlders for this
            // the put_contents method only writes/overwrites contents, and does not append
            // we need this to append the content

            // append the message to the purge log file
            file_put_contents( $_path, $_message, FILE_APPEND | LOCK_EX );
            
        }

    }

}
