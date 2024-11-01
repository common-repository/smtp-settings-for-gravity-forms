<?php
/** 
 * GF Common Extention
 * 
 * This class will extend some common methods in GravityForms
 * 
 * @since 7.4
 * @author Kevin Pirnie <me@kpirnie.com>
 * @package SMTP Settings for Gravity Forms
 * 
*/

// We don't want to allow direct access to this
defined( 'ABSPATH' ) || die( 'No direct script access allowed' );

// if the gravity forms plugin is not active, just exit
if( ! class_exists( 'GFCommon' ) ) {

	// just return
	return;
}

// make sure the class doesnt already exist
if( ! class_exists( 'KP_GFSMTP_Extend_Common' ) ) {

	/** 
     * Class KP_GFSMTP_Extend_Common
     * 
     * The actual class extending GF Common
     * 
     * @since 7.4
     * @access public
     * @author Kevin Pirnie <me@kpirnie.com>
     * @package SMTP Settings for Gravity Forms
     * 
    */
	class KP_GFSMTP_Extend_Common Extends GFCommon { 

		/** 
         * send_notification
         * 
         * Public static method to setup the notifications for the form
         * 
         * @since 7.4
         * @access public
         * @static
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package SMTP Settings for Gravity Forms
		 * @see gravityforms-plugin/common.php
         * 
         * @return void Returns a formatted array for GF processed notification
         * 
        */
		public static function send_notification( $notification, $form, $lead, $data = array( ) ) : array {
	
			$notification = gf_apply_filters( array( 'gform_notification', $form['id'] ), $notification, $form, $lead );
	 
			$to_field = '';
			if ( rgar( $notification, 'toType' ) == 'field' ) {
				$to_field = rgar( $notification, 'toField' );
				if ( rgempty( 'toField', $notification ) ) {
					$to_field = rgar( $notification, 'to' );
				}
			}
	
			$email_to = rgar( $notification, 'to' );
			//do routing logic if "to" field doesn't have a value (to support legacy notifications that will run routing prior to this method)
			if ( empty( $email_to ) && rgar( $notification, 'toType' ) == 'routing' && ! empty( $notification['routing'] ) ) {
				$email_to = array();
				foreach ( $notification['routing'] as $routing ) {
					if ( rgempty( 'email', $routing ) ) {
						continue;
					}
	
					GFCommon::log_debug( __METHOD__ . '(): Evaluating Routing - rule => ' . print_r( $routing, 1 ) );
	
					$source_field   = RGFormsModel::get_field( $form, rgar( $routing, 'fieldId' ) );
					$field_value    = RGFormsModel::get_lead_field_value( $lead, $source_field );
					$is_value_match = RGFormsModel::is_value_match( $field_value, rgar( $routing, 'value', '' ), rgar( $routing, 'operator', 'is' ), $source_field, $routing, $form ) && ! RGFormsModel::is_field_hidden( $form, $source_field, array(), $lead );
	
					if ( $is_value_match ) {
						$email_to[] = $routing['email'];
					}
	
					GFCommon::log_debug( __METHOD__ . '(): Evaluating Routing - field value => ' . print_r( $field_value, 1 ) );
					$is_value_match = $is_value_match ? 'Yes' : 'No';
					GFCommon::log_debug( __METHOD__ . '(): Evaluating Routing - is value match? ' . $is_value_match );
				}
	
				$email_to = join( ',', $email_to );
			} elseif ( ! empty( $to_field ) ) {
				$source_field = RGFormsModel::get_field( $form, $to_field );
				$email_to     = RGFormsModel::get_lead_field_value( $lead, $source_field );
			}
	
			// Running through variable replacement
			$to        = GFCommon::replace_variables( $email_to, $form, $lead, false, false, false, 'text', $data );
			$subject   = GFCommon::replace_variables( rgar( $notification, 'subject' ), $form, $lead, false, false, false, 'text', $data );
			$from      = GFCommon::replace_variables( rgar( $notification, 'from' ), $form, $lead, false, false, false, 'text', $data );
			$from_name = GFCommon::replace_variables( rgar( $notification, 'fromName' ), $form, $lead, false, false, false, 'text', $data );
			$bcc       = GFCommon::replace_variables( rgar( $notification, 'bcc' ), $form, $lead, false, false, false, 'text', $data );
			$replyTo   = GFCommon::replace_variables( rgar( $notification, 'replyTo' ), $form, $lead, false, false, false, 'text', $data );

			/**
			 * Enable the CC header for the notification.
			 *
			 * @since 2.3
			 *
			 * @param bool  $enable_cc    Should the CC header be enabled?
			 * @param array $notification The current notification object.
			 * @param array $from         The current form object.
			 */
			$enable_cc = gf_apply_filters( array( 'gform_notification_enable_cc', $form['id'], $notification['id'] ), false, $notification, $form );
	
			// Set CC if enabled.
			$cc = $enable_cc ? GFCommon::replace_variables( rgar( $notification, 'cc' ), $form, $lead, false, false, false, 'text', $data ) : null;
	
			$message_format = rgempty( 'message_format', $notification ) ? 'html' : rgar( $notification, 'message_format' );
	
			$merge_tag_format = $message_format === 'multipart' ? 'html' : $message_format;
	
			$message = GFCommon::replace_variables( rgar( $notification, 'message' ), $form, $lead, false, false, ! rgar( $notification, 'disableAutoformat' ), $merge_tag_format, $data );
	
			if ( apply_filters( 'gform_enable_shortcode_notification_message', true, $form, $lead ) ) {
				$message = do_shortcode( $message );
			}
	
			// Allow attachments to be passed as a single path (string) or an array of paths, if string provided, add to array.
			$attachments = rgar( $notification, 'attachments' );
			if ( ! empty( $attachments ) ) {
				$attachments = is_array( $attachments ) ? $attachments : array( $attachments );
			} else {
				$attachments = array();
			}
	
			// Add attachment fields.
			if ( rgar( $notification, 'enableAttachments', false ) ) {
	
				// Get file upload fields and upload root.
				$upload_fields = GFCommon::get_fields_by_type( $form, array( 'fileupload' ) );
				$upload_root   = GFFormsModel::get_upload_root();
	
				foreach ( $upload_fields as $upload_field ) {
	
					// Get field value.
					$attachment_urls = rgar( $lead, $upload_field->id );
	
					// If field value is empty, skip.
					if ( empty( $attachment_urls ) ) {
						self::log_debug( __METHOD__ . '(): No file(s) to attach for field #' . $upload_field->id );
						continue;
					}
	
					// Convert to array.
					$attachment_urls = $upload_field->multipleFiles ? json_decode( $attachment_urls, true ) : array( $attachment_urls );
	
					self::log_debug( __METHOD__ . '(): Attaching file(s) for field #' . $upload_field->id . '. ' . print_r( $attachment_urls, true ) );
	
					// Loop through attachment URLs; replace URL with path and add to attachments.
					foreach ( $attachment_urls as $attachment_url ) {
						$attachment_url = preg_replace( '|^(.*?)/gravity_forms/|', $upload_root, $attachment_url );
						$attachments[]  = $attachment_url;
					}
	
				}
	
			}
	
			$attachments = array_unique( $attachments );
	
			if ( $message_format === 'multipart' ) {
	
				// Creating alternate text message.
				$text_message = GFCommon::replace_variables( rgar( $notification, 'message' ), $form, $lead, false, false, ! rgar( $notification, 'disableAutoformat' ), 'text', $data );
	
				if ( apply_filters( 'gform_enable_shortcode_notification_message', true, $form, $lead ) ) {
					$text_message = do_shortcode( $text_message );
				}
	
				// Formatting text message. Removes all tags.
				$text_message = self::format_text_message( $text_message );
	
				// Sends text and html messages to send_email()
				$message = array(
					'html' => $message,
					'text' => $text_message,
				);
			}

			return compact( 'to', 'from', 'bcc', 'replyTo', 'subject', 'message', 'from_name', 'message_format', 'attachments', 'cc' );
		}

	}

}
