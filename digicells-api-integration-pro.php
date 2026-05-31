<?php
/**
 * Plugin Name: Digicells API Integration Pro
 * Plugin URI: https://hamaribooking.com
 * Description: Professional Hotelbeds API integration for hotel booking and listing
 * Version: 1.0.0
 * Author: Digicells
 * Author URI: https://hamaribooking.com
 * License: GPL v2 or later
 * Text Domain: digicells-api-pro
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('DIGICELLS_VERSION', '1.0.0');
define('DIGICELLS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DIGICELLS_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main plugin class
 */
class Digicells_Api_Integration_Pro {
    
    private static $instance = null;
    private $api_handler = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    private function load_dependencies() {
        require_once DIGICELLS_PLUGIN_DIR . 'includes/class-hotelbeds-api.php';
        require_once DIGICELLS_PLUGIN_DIR . 'includes/class-hotelbeds-cache.php';
        require_once DIGICELLS_PLUGIN_DIR . 'admin/admin-menu.php';
        require_once DIGICELLS_PLUGIN_DIR . 'public/shortcodes.php';
    }
    
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }
    
    public function init() {
        // Initialize the API handler
        $this->api_handler = new Hotelbeds_API();
        
        // Register shortcodes
        add_shortcode('digicells_hotel_search', array($this, 'render_search_form'));
        add_shortcode('digicells_hotel_results', array($this, 'render_results'));
    }
    
    public function enqueue_assets() {
        wp_enqueue_style('digicells-hotel-css', DIGICELLS_PLUGIN_URL . 'public/css/hotel-search.css', array(), DIGICELLS_VERSION);
        wp_enqueue_script('digicells-hotel-js', DIGICELLS_PLUGIN_URL . 'public/js/hotel-search.js', array('jquery'), DIGICELLS_VERSION, true);
        
        wp_localize_script('digicells-hotel-js', 'digicells_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('digicells_hotel_search')
        ));
    }
    
    public function render_search_form() {
        ob_start();
        include DIGICELLS_PLUGIN_DIR . 'templates/search-form.php';
        return ob_get_clean();
    }
    
    public function render_results() {
        ob_start();
        include DIGICELLS_PLUGIN_DIR . 'templates/hotel-results.php';
        return ob_get_clean();
    }
}

// Initialize the plugin
function digicells_api_pro() {
    return Digicells_Api_Integration_Pro::get_instance();
}

digicells_api_pro();