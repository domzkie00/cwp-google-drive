<?php if ( ! defined( 'ABSPATH' ) ) exit;

class Clients_WP_Google{
    
    private static $instance;

    public static function get_instance()
    {
        if( null == self::$instance ) {
            self::$instance = new Clients_WP_Google();
        }

        return self::$instance;
    }

    function __construct(){
        add_action('admin_init', array($this, 'register_integration'));
        add_action('admin_init', array($this, 'get_access_token'));
        add_action('admin_enqueue_scripts', array( $this, 'cwp_google_add_admin_scripts' ));
        add_action('wp_enqueue_scripts', array($this, 'cwp_google_add_wp_scripts'), 20, 1);
        add_action('wp_ajax_get_folder_list', array($this, 'get_folder_list_ajax'));
        add_filter('the_content', array($this, 'folder_content_table'));
        add_action('wp_ajax_delete_file', array($this, 'delete_file_ajax'));
        add_action('init', array($this, 'upload_file'));
    }

    public function cwp_google_add_admin_scripts() {
        wp_register_script('cwp_google_admin_scripts', CWPG_URL . '/assets/js/cwp-google-admin-scripts.js', '1.0', true);
        $cwpg_admin_script = array(
            'ajaxurl' => admin_url( 'admin-ajax.php' )
        );
        wp_localize_script('cwp_google_admin_scripts', 'cwpg_admin_script', $cwpg_admin_script );
        wp_enqueue_script('cwp_google_admin_scripts');
    }

    public function cwp_google_add_wp_scripts() {
        wp_register_script('cwp_google_wp_scripts', CWPG_URL . '/assets/js/cwp-google-scripts.js', '1.0', true);
        $cwpg_wp_script = array(
            'ajaxurl' => admin_url( 'admin-ajax.php' )
        );
        wp_localize_script('cwp_google_wp_scripts', 'cwpg_wp_script', $cwpg_wp_script );
        wp_enqueue_script('cwp_google_wp_scripts');
    }

    public function register_integration($array) {
        $google = array(
            'google' => array(
                'key'       => 'google',
                'label'     => 'Google Drive'
            )
        );

        $clients_wp_integrations = get_option('clients_wp_integrations');
        
        if(is_array($clients_wp_integrations)) {
            $merge_integrations = array_merge($clients_wp_integrations, $google);
            update_option('clients_wp_integrations', $merge_integrations);
        } else {
            update_option('clients_wp_integrations', $google);
        }
        
    }

    private function createFolderGoogleDrive($service, $folder_name, $parent = null){
        $data_file = array();
        if($parent == null){
            $data_file = array(
                'name'      => $folder_name,
                'mimeType'  => 'application/vnd.google-apps.folder'
            );
        }
        else {
            $data_file = array(
                'name'      => $folder_name,
                'mimeType'  => 'application/vnd.google-apps.folder',
                'parents'   => array($parent)
            );
        }

        $fileMetadata = new Google_Service_Drive_DriveFile($data_file);
        $file = $service->files->create($fileMetadata, array(
                'fields' => 'id')
            );

        return $file->id;
    }

    public function get_access_token(){
        if (isset($_REQUEST['cwpintegration']) && $_REQUEST['cwpintegration'] == 'google' ):
            $cwpgoogle_settings_options = get_option('cwpgoogle_settings_options');
            $app_key    = isset($cwpgoogle_settings_options['app_key']) ? $cwpgoogle_settings_options['app_key'] : '';
            $app_secret = isset($cwpgoogle_settings_options['app_secret']) ? $cwpgoogle_settings_options['app_secret'] : '';
            $app_token  = isset($cwpgoogle_settings_options['app_token']) ? $cwpgoogle_settings_options['app_token'] : '';

            if(!empty($app_key) && !empty($app_secret)) {
                session_start();
                $google_keys = array( 
                        'client_id' => $app_key, 
                        'client_secret' => $app_secret
                    );
                $client = new Google_Client();
                $client->setAuthConfig($google_keys);
                $client->addScope(Google_Service_Drive::DRIVE);
                $client->setRedirectUri(admin_url( 'edit.php?post_type=bt_client&page=cwp-google&cwpintegration=google' ));
                $client->setAccessType('offline');
                $client->setApprovalPrompt('force');

                if (! isset($_GET['code'])) {
                    $auth_url = $client->createAuthUrl();
                    header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
                } else {
                    $client->authenticate($_GET['code']);
                    $response = $client->getAccessToken();
                    $cwpgoogle_settings_options['app_token'] = json_encode($response);
                    update_option( 'cwpgoogle_settings_options', $cwpgoogle_settings_options );
                    header('Location: ' . admin_url( 'edit.php?post_type=bt_client&page=cwp-google' ));
                }
                
            }
            
        endif;
    }

    public function google_client() {
        $cwpgoogle_settings_options = get_option('cwpgoogle_settings_options');
        $app_key    = isset($cwpgoogle_settings_options['app_key']) ? $cwpgoogle_settings_options['app_key'] : '';
        $app_secret = isset($cwpgoogle_settings_options['app_secret']) ? $cwpgoogle_settings_options['app_secret'] : '';
        $app_token  = isset($cwpgoogle_settings_options['app_token']) ? $cwpgoogle_settings_options['app_token'] : '';

        if(!empty($app_key) && !empty($app_secret)) {
            $google_keys = array( 
                'client_id' => $app_key, 
                'client_secret' => $app_secret
            );
            $client = new Google_Client();
            $client->setAuthConfig($google_keys);
            $client->setAccessType('offline');
            $client->setAccessToken($app_token);
            if ($client->isAccessTokenExpired()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                $response = $client->getAccessToken();
                $cwpgoogle_settings_options['app_token'] = json_encode($response);
                update_option( 'cwpgoogle_settings_options', $cwpgoogle_settings_options );
            }

            return $client;
        }
    }

    public function get_folder_list_ajax() {
        $client = $this->google_client();
        $service = new Google_Service_Drive($client);
        $parameters['q'] = "mimeType='application/vnd.google-apps.folder' and 'root' in parents and trashed=false";
        $results = $service->files->listFiles($parameters);
        echo json_encode($results);

        die();
    }

    public function folder_content_table() {
        global $pages;

        foreach($pages as $page) {
            $page_content = nl2br($page);
            if (strpos($page, '[cwp_') !== FALSE) {
                $args = array(
                    'meta_key' => '_clients_page_shortcode',
                    'meta_value' => $page,
                    'post_type' => 'bt_client_page',
                    'post_status' => 'any',
                    'posts_per_page' => -1
                );
                $posts = get_posts($args);

                foreach($posts as $post) {
                    $integration = get_post_meta($post->ID, '_clients_page_integration', true);
                    $root_folder = get_post_meta($post->ID, '_clients_page_integration_folder', true);

                    if (isset($integration) && isset($root_folder)) {
                        if((!empty($integration) && $integration == 'google') && !empty($root_folder)) {
                            
                            $linked_client_id = get_post_meta($post->ID, '_clients_page_client', true);
                            $client_email = get_post_meta($linked_client_id, '_bt_client_group_owner', true);

                            if(is_user_logged_in()) {
                                $current_user = wp_get_current_user();
                                if(!current_user_can('administrator')) {
                                    if($current_user->user_email != $client_email) {
                                        echo 'You are not allowed to see this contents.';
                                        return;
                                    }
                                } else {
                                    if($current_user->user_email != $client_email) {
                                        echo 'You are not allowed to see this contents.';
                                        return;
                                    }
                                }
                            } else {
                                echo 'You are not allowed to see this contents.';
                                return;
                            }

                            $client = $this->google_client();
                            $service = new Google_Service_Drive($client);
                            $parameters['q'] = "'{$root_folder}' in parents and trashed=false";
                            $parameters['fields'] = "files(id,size,name,modifiedTime,parents)";
                            $result = $service->files->listFiles($parameters);

                            $folder_parameters['q'] = "mimeType='application/vnd.google-apps.folder' and 'root' in parents and trashed=false";
                            $folder_results = $service->files->listFiles($folder_parameters);

                            foreach($folder_results as $folder) {
                                if($folder['id'] == $root_folder) {
                                    $folder_name = $folder['name'];
                                }
                            }

                            ob_start();
                            include_once(CWPG_PATH_INCLUDES . '/cwp-google-table.php');
                            $page_content .= ob_get_clean();
                        }
                    }
                }
            }

            return $page_content;
        }
    }

    public function upload_file() {
        if(isset($_POST['action'])) {
            if($_POST['action'] == 'googledrive_upload_file') {
                $filename = $_FILES["upload_file"]["name"];
                $filepath = realpath($_FILES["upload_file"]["tmp_name"]);
                $mimeType = $_FILES["upload_file"]["type"];
                $cwpgoogle_settings_options = get_option('cwpgoogle_settings_options');
                $app_key    = isset($cwpgoogle_settings_options['app_key']) ? $cwpgoogle_settings_options['app_key'] : '';
                $app_secret = isset($cwpgoogle_settings_options['app_secret']) ? $cwpgoogle_settings_options['app_secret'] : '';
                $app_token  = isset($cwpgoogle_settings_options['app_token']) ? $cwpgoogle_settings_options['app_token'] : '';

                if(!empty($app_key) && !empty($app_secret)) {
                    $client = $this->google_client();
                    $service = new Google_Service_Drive($client);

                    $file = new Google_Service_Drive_DriveFile();
                    $file->setName($filename);
                    $file->setMimeType($mimeType);
                    $file->setParents(array($_POST['path']));

                    try {
                        $data = file_get_contents($filepath);

                        $createdFile = $service->files->create($file, array(
                            'data' => $data,
                            'mimeType' => $mimeType,
                        ));
                    } catch (Exception $e) {
                        print "An error occurred: " . $e->getMessage();
                    }
                }
            }
        }
    }

    public function delete_file_ajax() {
        try {
            $client = $this->google_client();
            $service = new Google_Service_Drive($client);
            $result = $service->files->delete($_POST['data']['id']);
            echo "File deleted.";
        } catch (Exception $e) {
            print_r("An error occurred: " . $e->getMessage());
        }
        die();
    }
}