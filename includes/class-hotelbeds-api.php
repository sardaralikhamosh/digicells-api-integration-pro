<?php
class Hotelbeds_API {
    
    private $api_key;
    private $secret;
    private $environment;
    private $base_url;
    
    public function __construct() {
        $this->api_key = get_option('digicells_hotelbeds_api_key', '');
        $this->secret = get_option('digicells_hotelbeds_secret', '');
        $this->environment = get_option('digicells_hotelbeds_environment', 'test');
        
        $this->base_url = ($this->environment === 'live') 
            ? 'https://api.hotelbeds.com/hotel-api/1.0' 
            : 'https://api.test.hotelbeds.com/hotel-api/1.0';
    }
    
    /**
     * Generate X-Signature for API authentication
     */
    private function generate_signature() {
        $timestamp = time();
        $signature_string = $this->api_key . $this->secret . $timestamp;
        $signature = hash('sha256', $signature_string);
        
        return array(
            'timestamp' => $timestamp,
            'signature' => $signature
        );
    }
    
    /**
     * Get API headers for authentication
     */
    private function get_headers() {
        $sig_data = $this->generate_signature();
        
        return array(
            'Api-Key' => $this->api_key,
            'X-Signature' => $sig_data['signature'],
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        );
    }
    
    /**
     * Search hotels based on criteria
     */
    public function search_hotels($params) {
        // Validate required parameters
        if (empty($this->api_key) || empty($this->secret)) {
            return new WP_Error('api_error', 'Hotelbeds API credentials not configured');
        }
        
        // Check cache first
        $cache_key = 'digicells_hotel_search_' . md5(json_encode($params));
        $cached_result = get_transient($cache_key);
        
        if ($cached_result !== false) {
            return $cached_result;
        }
        
        // Prepare the request body
        $body = array(
            'stay' => array(
                'from' => sanitize_text_field($params['check_in']),
                'to' => sanitize_text_field($params['check_out'])
            ),
            'occupancies' => array(
                array(
                    'rooms' => intval($params['rooms']),
                    'adults' => intval($params['adults']),
                    'children' => isset($params['children']) ? intval($params['children']) : 0
                )
            ),
            'language' => 'ENG'
        );
        
        // Add destination if provided
        if (!empty($params['destination'])) {
            $body['destination'] = array(
                'code' => sanitize_text_field($params['destination'])
            );
        }
        
        // Add geolocation if provided
        if (!empty($params['latitude']) && !empty($params['longitude'])) {
            $body['geolocation'] = array(
                'latitude' => floatval($params['latitude']),
                'longitude' => floatval($params['longitude']),
                'radius' => isset($params['radius']) ? intval($params['radius']) : 20
            );
        }
        
        // Make the API request
        $response = wp_remote_post($this->base_url . '/hotels', array(
            'headers' => $this->get_headers(),
            'body' => json_encode($body),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($status_code !== 200) {
            $error_msg = isset($body['error']['message']) ? $body['error']['message'] : 'Unknown API error';
            return new WP_Error('api_error', $error_msg);
        }
        
        // Cache the result for 1 hour (3600 seconds) to respect rate limits
        set_transient($cache_key, $body, 3600);
        
        // Update usage statistics
        $this->update_usage_stats();
        
        return $body;
    }
    
    /**
     * Get hotel details by code
     */
    public function get_hotel_details($hotel_code) {
        $sig_data = $this->generate_signature();
        
        $response = wp_remote_get($this->base_url . "/hotels/{$hotel_code}/details", array(
            'headers' => $this->get_headers()
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return json_decode(wp_remote_retrieve_body($response), true);
    }
    
    /**
     * Check API status
     */
    public function check_status() {
        $response = wp_remote_get($this->base_url . '/status', array(
            'headers' => $this->get_headers()
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        return $status_code === 200;
    }
    
    /**
     * Update and track API usage
     */
    private function update_usage_stats() {
        $today = date('Y-m-d');
        $usage = get_option('digicells_api_usage', array());
        
        if (!isset($usage[$today])) {
            $usage[$today] = 0;
        }
        
        $usage[$today]++;
        update_option('digicells_api_usage', $usage);
        
        // Clean up old entries (keep last 30 days)
        $thirty_days_ago = date('Y-m-d', strtotime('-30 days'));
        foreach ($usage as $date => $count) {
            if ($date < $thirty_days_ago) {
                unset($usage[$date]);
            }
        }
        update_option('digicells_api_usage', $usage);
    }
    
    /**
     * Get remaining API calls for today (respects 50/day limit)
     */
    public function get_remaining_calls() {
        $today = date('Y-m-d');
        $usage = get_option('digicells_api_usage', array());
        $used_today = isset($usage[$today]) ? $usage[$today] : 0;
        $limit = 50; // Test environment limit
        
        return max(0, $limit - $used_today);
    }
}