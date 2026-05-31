<?php
class Hotelbeds_Cache {
    
    public function __construct() {
        add_action('wp_ajax_digicells_clear_cache', array($this, 'clear_cache'));
        add_action('wp_ajax_nopriv_digicells_clear_cache', array($this, 'clear_cache'));
    }
    
    public function clear_cache() {
        if (!wp_verify_nonce($_POST['nonce'], 'digicells_hotel_search')) {
            wp_die('Security check failed');
        }
        
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_digicells_hotel_search_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_digicells_hotel_search_%'");
        
        wp_send_json_success(array('message' => 'Cache cleared successfully'));
    }
    
    public function get_cache_size() {
        global $wpdb;
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_digicells_hotel_search_%'");
        return intval($count);
    }
}