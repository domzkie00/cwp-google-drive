<?php
/**
 * Plugin Name: Clients WP - Google Drive
 * Plugin URI:  https://www.gravity2pdf.com
 * Description: Deliver to Google Drive the converted PDF from Clients WP
 * Version:     1.0
 * Author:      gravity2pdf
 * Author URI:  https://github.com/raphcadiz
 * Text Domain: cl-wp-google
 */

if (!class_exists('Clients_WP_Google')):

    define( 'CWPG_PATH', dirname( __FILE__ ) );
    define( 'CWPG_PATH_INCLUDES', dirname( __FILE__ ) . '/includes' );
    define( 'CWPG_PATH_CLASS', dirname( __FILE__ ) . '/class' );
    define( 'CWPG_FOLDER', basename( CWPG_PATH ) );
    define( 'CWPG_URL', plugins_url() . '/' . CWPG_FOLDER );
    define( 'CWPG_URL_INCLUDES', CWPG_URL . '/includes' );
    define( 'CWPG_URL_CLASS', CWPG_URL . '/class' );
    define( 'CWPG_VERSION', 1.0 );

    register_activation_hook( __FILE__, 'clients_wp_google_activation' );
    function clients_wp_google_activation(){
        if ( ! class_exists('Clients_WP') ) {
            deactivate_plugins( plugin_basename( __FILE__ ) );
            wp_die('Sorry, but this plugin requires the Restrict Content Pro and Clients WP to be installed and active.');
        }

    }

    add_action( 'admin_init', 'clients_wp_google_activate' );
    function clients_wp_google_activate(){
        if ( ! class_exists('Clients_WP') ) {
            deactivate_plugins( plugin_basename( __FILE__ ) );
        }
    }

    /*
     * include necessary files
     */
    require_once(CWPG_PATH.'/vendor/autoload.php');
    require_once(CWPG_PATH_CLASS . '/cwp-google-main.class.php');
    require_once(CWPG_PATH_CLASS . '/cwp-google-pages.class.php');

    /* Intitialize licensing
     * for this plugin.
     */
    if( class_exists( 'Clients_WP_License_Handler' ) ) {
        $cwp_google = new Clients_WP_License_Handler( __FILE__, 'Clients WP - Google Drive', CWPG_VERSION, 'gravity2pdf', null, null, 7547);
    }

    add_action( 'plugins_loaded', array( 'Clients_WP_Google', 'get_instance' ) );
endif;