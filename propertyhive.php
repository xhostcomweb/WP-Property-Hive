<?php
/**
 * Plugin Name: Property Hive
 * Plugin URI: https://wordpress.org/plugins/propertyhive/
 * Description: Estate Agency Property Software Plugin for WordPress
 * Version: 1.0.11
 * Author: PropertyHive
 * Author URI: http://wp-property-hive.com
 * Requires at least: 3.8
 * Tested up to: 4.4.1
 * 
 * Text Domain: propertyhive
 * Domain Path: /i18n/languages/
 *
 * @package PropertyHive
 * @category Core
 * @author PropertyHive
 */
  
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'PropertyHive' ) )
{
    /**
    * Main PropertyHive Class
    *
    * @class PropertyHive
    * @version 1.0.11
    */
    final class PropertyHive {
         
        /**
         * @var string
         */
        public $version = '1.0.11';
         
        /**
         * @var PropertyHive The single instance of the class
         */
        protected static $_instance = null;
        
        /**
         * Main PropertyHive Instance
         *
         * Ensures only one instance of PropertyHive is loaded or can be loaded.
         *
         * @static
         * @return PropertyHive - Main instance
         */
        public static function instance() 
        {
            if ( is_null( self::$_instance ) ) 
            {
                self::$_instance = new self();
            }
            return self::$_instance;
        }
        
        /**
         * Cloning is forbidden.
         *
         * @since 1.0.0
         */
        public function __clone() {
            _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'propertyhive' ), '1.0.0' );
        }
    
        /**
         * Unserializing instances of this class is forbidden.
         *
         * @since 1.0.0
         */
        public function __wakeup() {
            _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'propertyhive' ), '1.0.0' );
        }
        
        /**
         * PropertyHive Constructor.
         * @access public
         * @return PropertyHive
         */
        public function __construct() 
        {            
            // Auto-load classes on demand
            if ( function_exists( "__autoload" ) ) {
                spl_autoload_register( "__autoload" );
            }
    
            spl_autoload_register( array( $this, 'autoload' ) );
    
            // Define constants
            $this->define_constants();
            
            // Include required files
            $this->includes();
    
            // Init API
            //$this->api = new PH_API();
    
            // Hooks
            add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'action_links' ) );
            //add_action( 'widgets_init', array( $this, 'include_widgets' ) );
            add_action( 'init', array( $this, 'init' ), 0 );
            add_action( 'init', array( $this, 'include_template_functions' ) );
            add_action( 'init', array( 'PH_Shortcodes', 'init' ) );
            add_action( 'after_setup_theme', array( $this, 'setup_environment' ) );
    
            // Loaded action
            do_action( 'propertyhive_loaded' );
        }
        
        /**
         * Show action links on the plugin screen
         *
         * @param mixed $links
         * @return array
         */
        public function action_links( $links )
        {
            return array_merge( array(
                '<a href="' . admin_url( 'admin.php?page=ph-settings' ) . '">' . __( 'Settings', 'propertyhive' ) . '</a>',
                '<a href="' . esc_url( apply_filters( 'propertyhive_url', 'http://wp-property-hive.com/', 'propertyhive' ) ) . '">' . __( 'Website', 'propertyhive' ) . '</a>',
                //'<a href="' . esc_url( apply_filters( 'propertyhive_support_url', 'http://support.woothemes.com/' ) ) . '">' . __( 'Premium Support', 'propertyhive' ) . '</a>',
            ), $links );
        }
    
        /**
         * Auto-load PH classes on demand to reduce memory consumption.
         *
         * @param mixed $class
         * @return void
         */
        public function autoload( $class )
        {
            $path  = null;
            $class = strtolower( $class );
            $file = 'class-' . str_replace( '_', '-', $class ) . '.php';
            
            if ( strpos( $class, 'ph_shortcode_' ) === 0 ) {
                $path = $this->plugin_path() . '/includes/shortcodes/';
            } elseif ( strpos( $class, 'ph_meta_box' ) === 0 ) {
                $path = $this->plugin_path() . '/includes/admin/post-types/meta-boxes/';
            } elseif ( strpos( $class, 'ph_admin' ) === 0 ) {
                $path = $this->plugin_path() . '/includes/admin/';
            }
    
            if ( $path && is_readable( $path . $file ) ) {
                include_once( $path . $file );
                return;
            }
    
            // Fallback
            if ( strpos( $class, 'ph_' ) === 0 ) {
                $path = $this->plugin_path() . '/includes/';
            }
    
            if ( $path && is_readable( $path . $file ) ) {
                include_once( $path . $file );
                return;
            }
        }
    
        /**
         * Define PH Constants
         */
        private function define_constants() 
        {
            define( 'PH_PLUGIN_FILE', __FILE__ );
            define( 'PH_VERSION', $this->version );
    
            if ( ! defined( 'PH_TEMPLATE_PATH' ) ) {
                define( 'PH_TEMPLATE_PATH', $this->template_path() );
            }
        }
        
        /**
         * Include required core files used in admin and on the frontend.
         */
        private function includes() {
            include_once( 'includes/ph-core-functions.php' );
            include_once( 'includes/class-ph-install.php' );
    
            if ( is_admin() ) {
                include_once( 'includes/admin/class-ph-admin.php' );
                include_once( 'includes/class-ph-third-party-contacts.php' );
            }
    
            if ( defined( 'DOING_AJAX' ) ) {
                $this->ajax_includes();
            }
    
            if ( ! is_admin() || defined( 'DOING_AJAX' ) ) {
                $this->frontend_includes();
            }
    
            $this->query = include( 'includes/class-ph-query.php' );                // The main query class
    
            include_once( 'includes/class-ph-post-types.php' );                     // Registers post types
            include_once( 'includes/class-ph-countries.php' );                     // Manages interaction with countries and currency
        }
    
        /**
         * Include required ajax files.
         */
        public function ajax_includes() {
            include_once( 'includes/class-ph-ajax.php' );                   // Ajax functions for admin and the front-end
        }
    
        /**
         * Include required frontend files.
         */
        public function frontend_includes() {
            include_once( 'includes/ph-template-hooks.php' );
            include_once( 'includes/class-ph-template-loader.php' );        // Template Loader
            include_once( 'includes/class-ph-frontend-scripts.php' );       // Frontend Scripts
            include_once( 'includes/ph-form-functions.php' );               // Form Renderers
            include_once( 'includes/class-ph-form-handler.php' );           // Form Handlers
            include_once( 'includes/class-ph-shortcodes.php' );             // Shortcodes class
        }
    
        /**
         * Function used to Init PropertyHive Template Functions - This makes them pluggable by plugins and themes.
         */
        public function include_template_functions() {
            include_once( 'includes/ph-template-functions.php' );
        }
    
        /**
         * Include core widgets
         */
        public function include_widgets() {
            /*include_once( 'includes/abstracts/abstract-ph-widget.php' );
            include_once( 'includes/widgets/class-ph-widget-properties.php' );*/
        }
    
        /**
         * Init PropertyHive when WordPress Initialises.
         */
        public function init() {
            // Before init action
            do_action( 'before_propertyhive_init' );
    
            // Set up localisation
            $this->load_plugin_textdomain();
    
            // Session class, handles session data for users - can be overwritten if custom handler is needed
            //$session_class = apply_filters( 'propertyhive_session_handler', 'PH_Session_Handler' );
    
            // Load class instances
            //$this->product_factory = new PH_Product_Factory();     // Product Factory to create new product instances
            $this->countries       = new PH_Countries();            // Countries class
            //$this->integrations    = new PH_Integrations();     // Integrations class
           // $this->session         = new $session_class();
    
            // Email Actions
            /*$email_actions = array(
                'propertyhive_low_stock',
                'propertyhive_no_stock',
                'propertyhive_product_on_backorder',
                'propertyhive_order_status_pending_to_processing',
                'propertyhive_order_status_pending_to_completed',
                'propertyhive_order_status_pending_to_on-hold',
                'propertyhive_order_status_failed_to_processing',
                'propertyhive_order_status_failed_to_completed',
                'propertyhive_order_status_completed',
                'propertyhive_new_customer_note',
                'propertyhive_created_customer'
            );
    
            foreach ( $email_actions as $action )
                add_action( $action, array( $this, 'send_transactional_email' ), 10, 10 );*/
    
            // Init action
            do_action( 'propertyhive_init' );
        }
    
        /**
         * Load Localisation files.
         *
         * Note: the first-loaded translation file overrides any following ones if the same translation is present
         */
        public function load_plugin_textdomain() {
            $locale = apply_filters( 'plugin_locale', get_locale(), 'propertyhive' );
    
            // Admin Locale
            /*if ( is_admin() ) {
                load_textdomain( 'propertyhive', WP_LANG_DIR . "/propertyhive/propertyhive-admin-$locale.mo" );
                load_textdomain( 'propertyhive', dirname( __FILE__ ) . "/i18n/languages/propertyhive-admin-$locale.mo" );
            }
            
            // Global + Frontend Locale
            load_textdomain( 'propertyhive', WP_LANG_DIR . "/propertyhive/propertyhive-$locale.mo" );
            load_plugin_textdomain( 'propertyhive', false, plugin_basename( dirname( __FILE__ ) ) . "/i18n/languages" );*/
        }
    
        /**
         * Ensure theme and server variable compatibility and setup image sizes..
         */
        public function setup_environment() {

            // IIS
            if ( ! isset($_SERVER['REQUEST_URI'] ) ) {
                $_SERVER['REQUEST_URI'] = substr( $_SERVER['PHP_SELF'], 1 );
                if ( isset( $_SERVER['QUERY_STRING'] ) ) {
                    $_SERVER['REQUEST_URI'].='?'.$_SERVER['QUERY_STRING'];
                }
            }
    
            // NGINX Proxy
            if ( ! isset( $_SERVER['REMOTE_ADDR'] ) && isset( $_SERVER['HTTP_REMOTE_ADDR'] ) ) {
                $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_REMOTE_ADDR'];
            }
    
            if ( ! isset( $_SERVER['HTTPS'] ) && ! empty( $_SERVER['HTTP_HTTPS'] ) ) {
                $_SERVER['HTTPS'] = $_SERVER['HTTP_HTTPS'];
            }
    
            // Support for hosts which don't use HTTPS, and use HTTP_X_FORWARDED_PROTO
            if ( ! isset( $_SERVER['HTTPS'] ) && ! empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' ) {
                $_SERVER['HTTPS'] = '1';
            }
        }
    
        /** Helper functions ******************************************************/
    
        /**
         * Get the plugin url.
         *
         * @return string
         */
        public function plugin_url() {
            return untrailingslashit( plugins_url( '/', __FILE__ ) );
        }
    
        /**
         * Get the plugin path.
         *
         * @return string
         */
        public function plugin_path() {
            return untrailingslashit( plugin_dir_path( __FILE__ ) );
        }
    
        /**
         * Get the template path.
         *
         * @return string
         */
        public function template_path() {
            return apply_filters( 'PH_TEMPLATE_PATH', 'propertyhive/' );
        }
    
        /**
         * Get Ajax URL.
         *
         * @return string
         */
        public function ajax_url() {
            return admin_url( 'admin-ajax.php', 'relative' );
        }
    
        /**
         * Return the WC API URL for a given request
         *
         * @param mixed $request
         * @param mixed $ssl (default: null)
         * @return string
         */
        public function api_request_url( $request, $ssl = null ) {
            if ( is_null( $ssl ) ) {
                $scheme = parse_url( get_option( 'home' ), PHP_URL_SCHEME );
            } elseif ( $ssl ) {
                $scheme = 'https';
            } else {
                $scheme = 'http';
            }
    
            if ( get_option('permalink_structure') ) {
                return esc_url_raw( trailingslashit( home_url( '/ph-api/' . $request, $scheme ) ) );
            } else {
                return esc_url_raw( add_query_arg( 'ph-api', $request, trailingslashit( home_url( '', $scheme ) ) ) );
            }
        }
    
        /**
         * Init the mailer and call the notifications for the current filter.
         * @internal param array $args (default: array())
         * @return void
         */
        /*public function send_transactional_email() {
            $this->mailer();
            $args = func_get_args();
            do_action_ref_array( current_filter() . '_notification', $args );
        }*/
    
        /** Load Instances on demand **********************************************/
    
        /**
         * Get Checkout Class.
         *
         * @return PH_Checkout
         */
        /*public function checkout() {
            return PH_Checkout::instance();
        }*/
    
        /**
         * Get gateways class
         *
         * @return PH_Payment_Gateways
         */
        /*public function payment_gateways() {
            return PH_Payment_Gateways::instance();
        }*/
    
        /**
         * Get shipping class
         *
         * @return PH_Shipping
         */
        /*public function shipping() {
            return PH_Shipping::instance();
        }*/
    
        /**
         * Email Class.
         *
         * @return PH_Email
         */
        /*public function mailer() {
            return PH_Emails::instance();
        }|*/
    }

}

/**
 * Returns the main instance of PH to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return PropertyHive
 */
function PH() {
    return PropertyHive::instance();
}

// Global for backwards compatibility.
$GLOBALS['propertyhive'] = PH();