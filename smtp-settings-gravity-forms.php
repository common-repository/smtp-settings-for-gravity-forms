<?php

// We don't want to allow direct access to this
defined( 'ABSPATH' ) || die( 'No direct script access allowed' );

/*
Plugin Name: SMTP Settings for Gravity Forms
Description: <strong>REQUIRED:</strong> GravityForms installed and activated. Adds a new form settings section for configuring SMTP mail sending settings for each form.
Plugin URI: https://kevinpirnie.com
Author: Kevin C. Pirnie
Version: 0.12.89
Requires PHP: 7.4
Network: false
Text Domain: smtp-settings-gravity-forms
License: GPLv3
License URI:  https://www.gnu.org/licenses/gpl-3.0.html
*/

// setup the full page to this plugin
define( 'KPGFS_PATH', dirname( __FILE__ ) );

// setup the directory name
define( 'KPGFS_DIRNAME', basename( dirname( __FILE__ ) ) );

// setup the primary plugin file name
define( 'KPGFS_FILENAME', basename( __FILE__ ) );

// Require our primary
require( KPGFS_PATH . '/work/common.php' );
