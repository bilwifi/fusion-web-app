<?php

if (!defined('ABSPATH')) {
    exit;
}

final class FusionWebApp
{
    /**
     * Recipients tables
     */
    private $t_recipients;

    /**
     * Notifications tables
     */
    private $t_all;

    /**
     * Screens tables
     */
    private $t_screens;

    /**
     * Item edited
     */
    public $item = null;

    /**
     * Action in course
     */
    public $action = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        /**
         * ob init
         */
        ob_start();

        /**
         * Session start
         */
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        /**
         * Load Clases
         */
        $this->load_all_clases();

        /**
         * Set table names
         */
        $this->set_table_names();

        /**
         * Add custom fields for rest api
         */
        $this->rest_api_add_custom_routes();

        /**
         * Add plugin to admin menu
         */
        add_action('admin_menu', array($this, 'add_plugin_to_admin_menu'), 9);

        /**
         * Register fields for new push form
         */
        add_action('admin_init', array($this, 'register_and_build_fields_new_push'));

        /**
         * Function Send Push
         */
        add_action('admin_post_send_new_push', array(new send_push_notification_class(), 'send_push'));

        /**
         * Register fields for register app screen form
         */
        add_action('admin_init', array($this, 'register_and_build_fields_register_app_screen'));

        /**
         * Function Register App Screen
         */
        add_action('admin_post_register_new_app_screen', array($this, 'register_new_app_screen'));
        add_action('admin_post_register_edit_app_screen', array($this, 'register_edit_app_screen'));

        /**
         * Messages for admin
         */
        add_action('admin_notices', array($this, 'show_alert_messages'));

        /**
         * Create data table
         */
        $this->create_tables_if_do_not_exists();

        /**
         * Load languages
         */
        add_action('plugins_loaded', array($this, 'load_languages'));
        // add_filter('locale', array($this, 'set_es_locale'));

        /**
         * Add javascript
         */
        add_action('admin_enqueue_scripts', array($this, 'load_scripts'));
    }

    /**
     * Add custom fields for rest api
     */
    public function rest_api_add_custom_routes()
    {
        // https://abc.com/wp-json/appregister-user-token/
        add_action('rest_api_init', function () {
            register_rest_route('app', '/register-user-token', array(
                'methods' => 'POST',
                'callback' => array($this, 'add_user_app_push_token')
            ));
        });
        // https://abc.com/wp-json/app/remove-user-token
        add_action('rest_api_init', function () {
            register_rest_route('app', '/remove-user-token', array(
                'methods' => 'POST',
                'callback' => array($this, 'remove_user_app_push_token')
            ));
        });
        // https://abc.com/wp-json/app/get-main-categories
        add_action('rest_api_init', function () {
            register_rest_route('app', '/get-main-categories', array(
                'methods' => 'GET',
                'callback' => array($this, 'get_categories_app')
            ));
        });
    }

    /**
     * Load all clases
     */
    public function load_all_clases()
    {
        $this->load_class('push');
        $this->load_class('list-all');
        $this->load_class('list-recipients');
        $this->load_class('list-screens');
    }

    /**
     * Load scripts
     */
    public function load_scripts()
    {
        wp_enqueue_script(FUSION_WA_SLUG, FUSION_WA_ASSETS . 'js/script.js', array(), FUSION_WA_VERSION, true);
    }

    /**
     * Force Spanish
     */
    public function set_es_locale($locale)
    {
        if (is_admin()) {
            return 'es_ES';
        }
        return $locale;
    }

    /**
     * Load plugin textdomain.
     */
    function load_languages()
    {
        $language_loaded = load_plugin_textdomain(FUSION_WA_SLUG, false, FUSION_WA_DIR_LANG);
        if (!$language_loaded) {
            error_log('Language not found: ' . FUSION_WA_DIR_LANG);
        }
    }

    /**
     * Add plygin to Dashboard menu
     */
    public function add_plugin_to_admin_menu()
    {
        add_menu_page(__('Fusion WA', FUSION_WA_SLUG), __('Fusion WA', FUSION_WA_SLUG), 'administrator', FUSION_WA_SLUG, array($this, 'all_list'), 'dashicons-smartphone', 26);

        add_submenu_page(
            FUSION_WA_SLUG,
            __('Fusion :: All Notifications', FUSION_WA_SLUG),
            __('All Notifications', FUSION_WA_SLUG),
            'administrator',
            FUSION_WA_SLUG,
            array($this, 'all_list')
        );

        add_submenu_page(
            FUSION_WA_SLUG,
            __('Fusion :: New Push', FUSION_WA_SLUG),
            __('New Push', FUSION_WA_SLUG),
            'administrator',
            FUSION_WA_SLUG . '-send_new',
            array($this, 'send_push_page')
        );

        add_submenu_page(
            FUSION_WA_SLUG,
            __('Fusion :: List Recipient', FUSION_WA_SLUG),
            __('Recipients', FUSION_WA_SLUG),
            'administrator',
            FUSION_WA_SLUG . '-recipients',
            array($this, 'recipients_list')
        );

        add_submenu_page(
            FUSION_WA_SLUG,
            __('Fusion :: Screens', FUSION_WA_SLUG),
            __('Screens', FUSION_WA_SLUG),
            'administrator',
            FUSION_WA_SLUG . '-screens',
            array($this, 'screens_list')
        );

        add_submenu_page(
            FUSION_WA_SLUG,
            __('Fusion :: Register Screen', FUSION_WA_SLUG),
            __('Register Screen', FUSION_WA_SLUG),
            'administrator',
            FUSION_WA_SLUG . '-register_new_app_screen',
            array($this, 'register_app_screen_page')
        );
    }

    /**
     * Set table name
     */
    private function set_table_names()
    {
        global $wpdb;
        $this->t_recipients = $wpdb->prefix . FUSION_WA_T_RECIPIENTS;
        $this->t_all = $wpdb->prefix . FUSION_WA_T_ALL;
        $this->t_screens = $wpdb->prefix . FUSION_WA_T_SCREENS;
    }

    /**
     * Plugin all list page
     */
    public function all_list()
    {
        $this->list = new send_push_notifications_list_all_class();
        $this->list->prepare_items();
        $this->load_template('list-all');
    }

    /**
     * Plugin settings page
     */
    public function recipients_list()
    {
        $this->list = new send_push_notifications_list_recipients_class();
        $this->list->prepare_items();
        $this->load_template('list-recipients');
    }

    /**
     * Register Screen Form
     */
    public function register_edit_app_screen()
    {
        $params = [];
        $params['title']        = sanitize_text_field($_POST['title']);
        $params['description']  = sanitize_text_field($_POST['description']);
        $params['screen']       = sanitize_text_field($_POST['screen']);
        $params['requires_id']  = sanitize_text_field($_POST['requires_id']);
        $params['id']           = sanitize_text_field($_POST['id']);

        if (empty($params['title']) || empty($params['description']) || empty($params['screen'])) {
            // go back with error
            $_SESSION[FUSION_WA_SLUG . '-message-type'] = 'error';
            $_SESSION[FUSION_WA_SLUG . '-message'] = __('Please, make sure all required fields are completed.', FUSION_WA_SLUG);
            $_SESSION['POST'] = $params;
            wp_redirect(wp_get_referer());
            die();
        }

        global $wpdb;
        // prepare
        $data = array(
            'title'       => $params['title'],
            'description' => $params['description'],
            'screen'      => $params['screen'],
            'requires_id' => $params['requires_id'] == 0 ? false : true
        );
        // save
        $updated = $wpdb->update($this->t_screens, $data, ['id' => $params['id']]);
        // handle response
        if ($updated) {
            // success!
            $_SESSION[FUSION_WA_SLUG . '-message-type'] = 'success';
            $_SESSION[FUSION_WA_SLUG . '-message'] = __('New app screen successfully updated.', FUSION_WA_SLUG);
            wp_redirect(FUSION_WA_URL . '-screens');
            die();
        } else {
            // go back with error
            $_SESSION[FUSION_WA_SLUG . '-message-type'] = 'error';
            $_SESSION[FUSION_WA_SLUG . '-message'] = __('An error occurred... please, try again. Error: ' . $wpdb->print_error, FUSION_WA_SLUG);
            wp_redirect(wp_get_referer());
            die();
        }
    }

    /**
     * Register Screen Form
     */
    public function register_new_app_screen()
    {
        $params = [];
        $params['title']        = sanitize_text_field($_POST['title']);
        $params['description']  = sanitize_text_field($_POST['description']);
        $params['screen']       = sanitize_text_field($_POST['screen']);
        $params['requires_id']  = sanitize_text_field($_POST['requires_id']);

        if (empty($params['title']) || empty($params['description']) || empty($params['screen'])) {
            // go back with error
            $_SESSION[FUSION_WA_SLUG . '-message-type'] = 'error';
            $_SESSION[FUSION_WA_SLUG . '-message'] = __('Please, make sure all required fields are completed.', FUSION_WA_SLUG);
            $_SESSION['POST'] = $params;
            wp_redirect(wp_get_referer());
            die();
        }

        global $wpdb;
        // prepare
        $data = array(
            'title'       => $params['title'],
            'description' => $params['description'],
            'screen'      => $params['screen'],
            'requires_id' => $params['requires_id'] == 0 ? false : true
        );
        // save
        $wpdb->insert($this->t_screens, $data);
        // handle response
        if ($wpdb->insert_id) {
            // success!
            $_SESSION[FUSION_WA_SLUG . '-message-type'] = 'success';
            $_SESSION[FUSION_WA_SLUG . '-message'] = __('New app screen successfully registered.', FUSION_WA_SLUG);
            wp_redirect(FUSION_WA_URL . '-screens');
            die();
        } else {
            // go back with error
            $_SESSION[FUSION_WA_SLUG . '-message-type'] = 'error';
            $_SESSION[FUSION_WA_SLUG . '-message'] = __('An error occurred... please, try again. Error: ' . $wpdb->print_error, FUSION_WA_SLUG);
            wp_redirect(wp_get_referer());
            die();
        }
    }

    /**
     * Register Screen Form
     */
    public function register_app_screen_page()
    {
        $this->load_template('register_app_screen');
        unset($_SESSION['POST']);
    }

    /**
     * Send Push Form
     */
    public function send_push_page()
    {
        $this->action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : '';
        if ($this->action == 'edit' && isset($_REQUEST['id'])) {
            $this->item = $this->get_item(esc_sql($_REQUEST['id']), $this->t_all);
        }
        $this->load_template('send_push');
        unset($_SESSION['POST']);
    }

    /**
     * Template main plugin page
     */
    public function screens_list()
    {
        $this->action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : '';
        if ($this->action == 'edit-app-screen' && isset($_REQUEST['id'])) {
            $this->item = $this->get_item(esc_sql($_REQUEST['id']), $this->t_screens);
            return $this->load_template('edit_app_screen');
        }
        $this->list = new send_push_notifications_list_screens_class();
        $this->list->prepare_items();
        $this->load_template('list-app-screens');
    }

    /**
     * Get screens
     */
    public function get_screens()
    {
        global $wpdb;
        $sql = "SELECT * FROM " . $this->t_screens;
        return $wpdb->get_results($sql);
    }

    /**
     * Get item
     */
    public function get_item($id, $table)
    {
        global $wpdb;
        $sql = "SELECT * FROM " . $table . " where id = $id LIMIT 1";
        return $wpdb->get_row($sql);
    }

    /**
     * Load Template
     */
    public function load_template($template)
    {
        return include_once FUSION_WA_DIR . 'templates/' . $template . '.php';
    }

    /**
     * Load Class
     */
    public function load_class($class)
    {
        return include_once FUSION_WA_DIR . 'includes/class-' . $class . '.php';
    }

    /**
     * Get main categories
     */
    public function get_categories_app()
    {

        $blogs = [];
        $blogs['latest'] = get_posts(array('category' => 1, 'numberposts' => 5));
        $blogs['count'] = get_category(1)->count;
        $blogs['folders'] = get_categories(array('child_of' => 1));
        if (count($blogs['folders']) > 0) {
            foreach ($blogs['folders'] as $key => $folder) {
                $mediaId = get_term_meta($folder->cat_ID, 'app_category_image', true);
                $blogs['folders'][$key]->{"app_category_image"} = wp_get_attachment_image_src($mediaId, 'medium_large')[0];
            }
        }
        if (count($blogs['latest']) > 0) {
            foreach ($blogs['latest'] as $key => $latest) {
                $mediaId = get_post_meta($latest->ID, '_thumbnail_id', true);
                $blogs['latest'][$key]->{"featured_media_url"} = wp_get_attachment_image_src($mediaId, 'medium_large')[0];
            }
        }

        $audios = [];
        $audios['latest'] = get_posts(array('category' => 32, 'numberposts' => 5));
        $audios['count'] = get_category(32)->count;
        $audios['folders'] = get_categories(array('child_of' => 32));
        if (count($audios['folders']) > 0) {
            foreach ($audios['folders'] as $key => $folder) {
                $mediaId = get_term_meta($folder->cat_ID, 'app_category_image', true);
                $audios['folders'][$key]->{"app_category_image"} = wp_get_attachment_image_src($mediaId, 'medium_large')[0];
            }
        }

        if (count($audios['latest']) > 0) {
            foreach ($audios['latest'] as $key => $latest) {
                $mediaId = get_post_meta($latest->ID, '_thumbnail_id', true);
                $audios['latest'][$key]->{"featured_media_url"} = wp_get_attachment_image_src($mediaId, 'medium_large')[0];
                $audios['latest'][$key]->{"app_audio_url"} = get_post_meta($latest->ID, 'app_audio_url', true);
            }
        }

        $videos = [];
        $videos['latest'] = get_posts(array('category' => 38, 'numberposts' => 5));
        $videos['count'] = get_category(38)->count;
        $videos['folders'] = get_categories(array('child_of' => 38));
        if (count($videos['folders']) > 0) {
            foreach ($videos['folders'] as $key => $folder) {
                $mediaId = get_term_meta($folder->cat_ID, 'app_category_image', true);
                $videos['folders'][$key]->{"app_category_image"} = wp_get_attachment_image_src($mediaId, 'medium_large')[0];
            }
        }
        if (count($videos['latest']) > 0) {
            foreach ($videos['latest'] as $key => $latest) {
                $mediaId = get_post_meta($latest->ID, '_thumbnail_id', true);
                $videos['latest'][$key]->{"featured_media_url"} = wp_get_attachment_image_src($mediaId, 'medium_large')[0];
                $videos['latest'][$key]->{"app_video_url"} = get_post_meta($latest->ID, 'app_video_url', true);
            }
        }

        return [
            'blogs' => $blogs,
            'audios' => $audios,
            'videos' => $videos
        ];
    }

    /**
     * Create database table
     */
    private function create_tables_if_do_not_exists()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $this->t_recipients)) !== $this->t_recipients) {
            // create table
            $sql = "CREATE TABLE $this->t_recipients (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                token varchar(255) DEFAULT '' NOT NULL,
                installation_id varchar(255) DEFAULT '' NOT NULL,
                name_device varchar(255) DEFAULT '' NOT NULL,
                platform varchar(255) DEFAULT '' NOT NULL,
                status ENUM('active', 'trashed') NOT NULL default 'active',
                updated_at timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY  (id)
            ) $charset_collate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $this->t_all)) !== $this->t_all) {
            // create table
            $sql = "CREATE TABLE $this->t_all (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                title varchar(255) DEFAULT '' NOT NULL,
                subtitle varchar(255) DEFAULT '' NOT NULL,
                body varchar(255) DEFAULT '' NOT NULL,
                screen varchar(255) DEFAULT '' NULL,
                status ENUM('sent', 'draft', 'trashed') NOT NULL default 'sent',
                recipients integer(11) DEFAULT 0 NOT NULL,
                updated_at timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY  (id)
            ) $charset_collate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $this->t_screens)) !== $this->t_screens) {
            // create table
            $sql = "CREATE TABLE $this->t_screens (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                title varchar(255) DEFAULT '' NOT NULL,
                description varchar(255) DEFAULT '' NULL,
                screen varchar(255) DEFAULT '' NOT NULL,
                requires_id boolean DEFAULT '0',
                status ENUM('active', 'trashed') NOT NULL default 'active',
                updated_at timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY  (id)
            ) $charset_collate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }

    /**
     * Alerts feedback
     */
    function show_alert_messages()
    {
        if (isset($_SESSION[FUSION_WA_SLUG . '-message'])) {
            echo '<div class="notice notice-' . esc_attr($_SESSION[FUSION_WA_SLUG . '-message-type']) . '"><p>' . esc_html($_SESSION[FUSION_WA_SLUG . '-message']) . '</p></div>';
            unset($_SESSION[FUSION_WA_SLUG . '-message']);
            unset($_SESSION[FUSION_WA_SLUG . '-message-type']);
        }
    }

    /**
     * Register fields form
     */
    public function register_and_build_fields_register_app_screen()
    {
        // add section
        add_settings_section(
            // ID used to identify this section and with which to register options
            FUSION_WA_SLUG . 'register_app_screen_general_section',
            // Title to be displayed on the administration page
            __('Register your app screens here', FUSION_WA_SLUG),
            // Callback used to render the description of the section
            array($this, 'register_screen_form_message'),
            // Page on which to add this section of options
            'register_screen_fields_form'
        );

        // Message title field
        add_settings_field(
            'title',
            __('Screen title', FUSION_WA_SLUG),
            array($this, 'render_settings_field'),
            'register_screen_fields_form',
            FUSION_WA_SLUG . 'register_app_screen_general_section',
            array(
                'type'              => 'input',
                'subtype'           => 'text',
                'id'                => 'title',
                'name'              => 'title',
                'required'          => true,
                'min'               => '3',
                'max'               => '50',
                'get_options_list'  => '',
                'value_type'        => 'normal',
                'autofocus'         => true,
                'wp_data'           => 'option'
            )
        );
        register_setting(
            'register_screen_fields_form',
            'title'
        );

        // Description field
        add_settings_field(
            'description',
            __('Description', FUSION_WA_SLUG),
            array($this, 'render_settings_field'),
            'register_screen_fields_form',
            FUSION_WA_SLUG . 'register_app_screen_general_section',
            array(
                'type'              => 'textarea',
                'id'                => 'description',
                'name'              => 'description',
                'required'          => false,
                'max'               => '255',
                'get_options_list'  => '',
                'value_type'        => 'normal',
                'wp_data'           => 'option'
            )
        );
        register_setting(
            'register_screen_fields_form',
            'description'
        );

        // Message title field
        add_settings_field(
            'screen',
            __('Route', FUSION_WA_SLUG),
            array($this, 'render_settings_field'),
            'register_screen_fields_form',
            FUSION_WA_SLUG . 'register_app_screen_general_section',
            array(
                'type'              => 'input',
                'subtype'           => 'text',
                'id'                => 'screen',
                'name'              => 'screen',
                'required'          => true,
                'min'               => '3',
                'max'               => '50',
                'get_options_list'  => '',
                'value_type'        => 'normal',
                'wp_data'           => 'option'
            )
        );
        register_setting(
            'register_screen_fields_form',
            'screen'
        );

        // Screen route field
        add_settings_field(
            'requires_id',
            __('Requires ID', FUSION_WA_SLUG),
            array($this, 'render_settings_field'),
            'register_screen_fields_form',
            FUSION_WA_SLUG . 'register_app_screen_general_section',
            array(
                'type'              => 'select',
                'id'                => 'requires_id',
                'name'              => 'requires_id',
                'required'          => true,
                'get_options_list'           => [
                    [
                        'value' => 0,
                        'label' => 'No'
                    ],
                    [
                        'value' => 1,
                        'label' => 'Yes'
                    ]
                ],
                'default'       => 0,
                'description'   => __('Select yes if the screen on your app load an item by its ID.', FUSION_WA_SLUG),
                'value_type'    => 'normal',
                'wp_data'       => 'option'
            )
        );
        register_setting(
            'register_screen_fields_form',
            'requires_id'
        );
    }

    /**
     * Register fields form
     */
    public function register_and_build_fields_new_push()
    {
        // add section
        add_settings_section(
            // ID used to identify this section and with which to register options
            FUSION_WA_SLUG . 'general_section',
            // Title to be displayed on the administration page
            __('Complete the form to send a push notification', FUSION_WA_SLUG),
            // Callback used to render the description of the section
            array($this, 'new_push_form_message'),
            // Page on which to add this section of options
            'send_push_fields_form'
        );

        // Message title field
        add_settings_field(
            'title',
            __('Message title', FUSION_WA_SLUG),
            array($this, 'render_settings_field'),
            'send_push_fields_form',
            FUSION_WA_SLUG . 'general_section',
            array(
                'type'              => 'input',
                'subtype'           => 'text',
                'id'                => 'title',
                'name'              => 'title',
                'required'          => true,
                'autofocus'         => true,
                'min'               => '3',
                'max'               => '50',
                'get_options_list'  => '',
                'value_type'        => 'normal',
                'wp_data'           => 'option'
            )
        );
        register_setting(
            'send_push_fields_form',
            'title'
        );

        // Message subtitle field
        add_settings_field(
            'subtitle',
            __('Message subtitle - iOS', FUSION_WA_SLUG),
            array($this, 'render_settings_field'),
            'send_push_fields_form',
            FUSION_WA_SLUG . 'general_section',
            array(
                'type'              => 'input',
                'subtype'           => 'text',
                'id'                => 'subtitle',
                'name'              => 'subtitle',
                'required'          => true,
                'min'               => '3',
                'max'               => '50',
                'get_options_list'  => '',
                'value_type'        => 'normal',
                'wp_data'           => 'option'
            )
        );
        register_setting(
            'subtitle',
            'body'
        );

        // Message body field
        add_settings_field(
            'body',
            __('Message body', FUSION_WA_SLUG),
            array($this, 'render_settings_field'),
            'send_push_fields_form',
            FUSION_WA_SLUG . 'general_section',
            array(
                'type'              => 'textarea',
                'id'                => 'body',
                'name'              => 'body',
                'required'          => true,
                'min'               => '5',
                'max'               => '170',
                'get_options_list'  => '',
                'value_type'        => 'normal',
                'wp_data'           => 'option'
            )
        );
        register_setting(
            'send_push_fields_form',
            'body'
        );

        // Get registered screens
        $get_screens = $this->get_screens();
        $set_screen_list = [];
        if (count($get_screens) > 0) {
            foreach ($get_screens as $key => $screen) {
                $set_screen_list[$key]['value'] = $screen->screen;
                $set_screen_list[$key]['label'] = $screen->title;
            }
        }

        // Message body field
        add_settings_field(
            'screen',
            __('Screen', FUSION_WA_SLUG),
            array($this, 'render_settings_field'),
            'send_push_fields_form',
            FUSION_WA_SLUG . 'general_section',
            array(
                'type'              => 'select',
                'id'                => 'screen',
                'name'              => 'screen',
                'required'          => false,
                'get_options_list'  => $set_screen_list,
                'description'       => __('Select the screen you want to redirect users when they tap on the push notification or leave it empty if the main screen is what you need.', FUSION_WA_SLUG),
                'value_type'        => 'normal',
                'wp_data'           => 'option'
            )
        );
        register_setting(
            'send_push_fields_form',
            'screen'
        );
    }

    /**
     * Preappend message form
     */
    public function register_screen_form_message()
    {
        echo '<p>' . __('The name of the route must match the one registered on your App.', FUSION_WA_SLUG) . '</p>';
    }

    /**
     * Preappend message form
     */
    public function new_push_form_message()
    {
        echo '<p>' . __('The message will be sent immediately.', FUSION_WA_SLUG) . '</p>';
    }

    /**
     * Input fields template
     */
    public function render_settings_field($args)
    {
        if ($args['wp_data'] == 'option') {
            $wp_data_value = get_option($args['name']);
        } elseif ($args['wp_data'] == 'post_meta') {
            $wp_data_value = get_post_meta($args['post_id'], $args['name'], true);
        }

        switch ($args['type']) {
            case 'input':
                $value =  (isset($_SESSION['POST'][$args['name']])) ? $_SESSION['POST'][$args['name']] : ($this->item !== null ? $this->item->{$args['name']} : ($args['value_type'] == 'serialized' ? serialize($wp_data_value) : $wp_data_value));
                if ($args['subtype'] != 'checkbox') {
                    $prependStart = (isset($args['prepend_value'])) ? '<div class="input-prepend"> <span class="add-on">' . $args['prepend_value'] . '</span>' : '';
                    $prependEnd = (isset($args['prepend_value'])) ? '</div>' : '';
                    $step = (isset($args['step'])) ? 'step="' . $args['step'] . '"' : '';
                    $min = (isset($args['min'])) ? 'min="' . $args['min'] . '"' : '';
                    $max = (isset($args['max'])) ? 'max="' . $args['max'] . '"' : '';
                    $autofocus = isset($args['autofocus']) ? 'autofocus="true"' : '';
                    if (isset($args['disabled'])) {
                        echo $prependStart . '<input type="' . esc_attr($args['subtype']) . '" ' . esc_attr($autofocus) . ' id="' . esc_attr($args['id']) . '_disabled" ' . esc_attr($step) . ' ' . esc_attr($max) . ' ' . esc_attr($min) . ' name="' . esc_attr($args['name']) . '_disabled" size="40" disabled value="' . esc_attr($value) . '" /><input type="hidden" id="' . esc_attr($args['id']) . '" ' . esc_attr($step) . ' ' . esc_attr($max) . ' ' . esc_attr($min) . ' name="' . esc_attr($args['name']) . '" size="40" value="' . esc_attr($value) . '" />' . $prependEnd;
                    } else {
                        echo $prependStart . '<input type="' . esc_attr($args['subtype']) . '" ' . esc_attr($autofocus) . ' id="' . esc_attr($args['id']) . '" "' . esc_attr($args['required']) . '" ' . esc_attr($step) . ' ' . esc_attr($max) . ' ' . esc_attr($min) . ' name="' . esc_attr($args['name']) . '" size="40" value="' . esc_attr($value) . '" />' . $prependEnd;
                    }
                } else {
                    $checked = ($value) ? 'checked' : '';
                    echo '<input type="' . esc_attr($args['subtype']) . '" id="' . esc_attr($args['id']) . '" "' . esc_attr($args['required']) . '" name="' . esc_attr($args['name']) . '" size="40" value="1" ' . esc_attr($checked) . ' />';
                }
                break;
            case 'textarea':
                $value = (isset($_SESSION['POST'][$args['name']])) ? $_SESSION['POST'][$args['name']] : ($this->item !== null ? $this->item->{$args['name']} : ($args['value_type'] == 'serialized' ? serialize($wp_data_value) : $wp_data_value));
                $prependStart = (isset($args['prepend_value'])) ? '<div class="input-prepend"> <span class="add-on">' . $args['prepend_value'] . '</span>' : '';
                $prependEnd = (isset($args['prepend_value'])) ? '</div>' : '';
                $step = (isset($args['step'])) ? 'step="' . $args['step'] . '"' : '';
                $min = (isset($args['min'])) ? 'minlength="' . $args['min'] . '"' : '';
                $max = (isset($args['max'])) ? 'maxlength="' . $args['max'] . '"' : '';
                echo $prependStart . '<textarea rows="5" cols="38" id="' . esc_attr($args['id']) . '" "' . esc_attr($args['required']) . '" ' . esc_attr($step) . ' ' . esc_attr($max) . ' ' . esc_attr($min) . ' name="' . esc_attr($args['name']) . '" size="40">' . esc_textarea($value) . '</textarea>' . $prependEnd;
                break;
            case 'select':
                $value = (isset($_SESSION['POST'][$args['name']])) ? $_SESSION['POST'][$args['name']] : ($this->item !== null ? $this->item->{$args['name']} : ($args['value_type'] == 'serialized' ? serialize($wp_data_value) : $wp_data_value));
                $prependStart = (isset($args['prepend_value'])) ? '<div class="input-prepend"> <span class="add-on">' . $args['prepend_value'] . '</span>' : '';
                $prependEnd = (isset($args['prepend_value'])) ? '</div>' : '';
                $description = '<p style="margin-bottom: 10px;">' . $args['description'] . '</p>';
                $options = $args['required'] == false ? '<option value="">-</option>' : '';
                foreach ($args['get_options_list'] as $option) {
                    $isSelected = esc_attr($value) == $option['value'] ? ' selected ' : '';
                    $options .= '<option value="' . $option['value'] . '" ' . $isSelected . '>' . $option['label'] . '</option>';;
                }
                echo $prependStart . $description . '<select id="' . esc_attr($args['id']) . '" "' . esc_attr($args['required']) . '" name="' . esc_attr($args['name']) . '">' . $options . '</select>' . $prependEnd;
                break;
            default:
                # code...
                break;
        }
    }

    /**
     * Custom Rest Routes
     * Add new token
     */
    function add_user_app_push_token(WP_REST_Request $request)
    {
        global $wpdb;
        $parameters = $request->get_params();

        // Authenticate
        $user = wp_authenticate($parameters['username'] ?? '', $parameters['password'] ?? '');
        if (is_wp_error($user)) {
            // return $user;
            return new WP_Error('authentication_failed', $user->errors, array('status' => 404));
        }

        // validate data
        if (!isset($parameters['installation_id']) || !isset($parameters['name_device']) || !isset($parameters['token']) || !isset($parameters['platform'])) {
            // Create the response object
            $response = new WP_REST_Response(['success' => false, 'error' => 'fields_not_valid']);

            // Add a custom status code
            $response->set_status(201);

            return $response;
        }

        // remove if exists
        $wpdb->delete($this->t_recipients, array('installation_id' => $parameters['installation_id']));
        $wpdb->delete($this->t_recipients, array('token' => $parameters['token']));

        // insert new record
        $data = array(
            'token'             => $parameters['token'],
            'installation_id'   => $parameters['installation_id'],
            'name_device'       => $parameters['name_device'],
            'platform'          => $parameters['platform']
        );
        $wpdb->insert($this->t_recipients, $data);

        // Create the response object
        if ($wpdb->insert_id) {
            $response = new WP_REST_Response(['success' => true]);
        } else {
            $response = new WP_REST_Response(['success' => false, 'error' => 'could_not_insert_record']);
        }

        // Add a custom status code
        $response->set_status(201);

        return $response;
    }

    /**
     * Custom Fields Rest
     * Remove token
     */
    function remove_user_app_push_token(WP_REST_Request $request)
    {
        global $wpdb;
        $parameters = $request->get_params();

        // Authenticate
        $user = wp_authenticate($parameters['username'] ?? '', $parameters['password'] ?? '');
        if (is_wp_error($user)) {
            // return $user;
            return new WP_Error('authentication_failed', $user->errors, array('status' => 404));
        }

        // validate data
        if (!isset($parameters['installation_id']) && !isset($parameters['token'])) {
            // Create the response object
            $response = new WP_REST_Response(['device_removed' => false]);

            // Add a custom status code
            $response->set_status(201);

            return $response;
        }

        // remove if exists
        $removedByInstallation = $wpdb->delete($this->t_recipients, array('installation_id' => $parameters['installation_id']));
        $removedByToken = $wpdb->delete($this->t_recipients, array('token' => $parameters['token']));

        // Create the response object
        if ($removedByInstallation || $removedByToken) {
            $response = new WP_REST_Response(['device_removed' => true]);
        } else {
            $response = new WP_REST_Response(['device_removed' => false]);
        }

        // Add a custom status code
        $response->set_status(201);

        return $response;
    }
}
