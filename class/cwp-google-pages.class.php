<?php
class Clients_WP_Google_Pages {

    public function __construct() {
        add_action('admin_init', array( $this, 'settings_options_init' ));
        add_action('admin_menu', array( $this, 'admin_menus'), 12 );
    }

    public function settings_options_init() {
        register_setting( 'cwpgoogle_settings_options', 'cwpgoogle_settings_options', '' );
    }

    public function admin_menus() {
        add_submenu_page ( 'edit.php?post_type=bt_client' , 'Google Drive' , 'Google Drive' , 'manage_options' , 'cwp-google' , array( $this , 'ninja2pdf_google' ));
    }

    public function ninja2pdf_google() {
        include_once(CWPG_PATH_INCLUDES.'/cwp_google.php');
    }
}

new Clients_WP_Google_Pages();