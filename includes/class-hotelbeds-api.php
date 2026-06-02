<?php
class Hotelbeds_API {
    
    private $api_key;
    private $secret;
    private $environment;
    private $base_url;
    
public function __construct() {
    $this->api_key = trim(get_option('digicells_hotelbeds_api_key', ''));
    $this->secret = trim(get_option('digicells_hotelbeds_secret', ''));
    $this->environment = get_option('digicells_hotelbeds_environment', 'test');
    
    // FORCE TEST ENVIRONMENT since Live is not allowed with these credentials
    $this->base_url = 'https://api.test.hotelbeds.com/hotel-api/1.0';
    
    // Remove the conditional that checks for 'live'
    // This ensures you ALWAYS use the test endpoint
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
    if (empty($this->api_key) || empty($this->secret)) {
        return new WP_Error('api_error', 'API credentials not configured');
    }
    
    // Check cache
    $cache_key = 'digicells_hotel_search_' . md5(json_encode($params));
    $cached_result = get_transient($cache_key);
    if ($cached_result !== false) {
        return $cached_result;
    }
    
    $sig_data = $this->generate_signature();
    
    // Build request body
    $body = array(
        'stay' => array(
            'from' => $params['check_in'],
            'to' => $params['check_out']
        ),
        'occupancies' => $params['occupancies'],
        'language' => 'ENG'
    );
    
    // Add destination
    if (!empty($params['destination'])) {
        $body['destination'] = array(
            'code' => $params['destination']
        );
    }
    
    $response = wp_remote_post($this->base_url . '/hotels', array(
        'headers' => array(
            'Api-Key' => $this->api_key,
            'X-Signature' => $sig_data['signature'],
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode($body),
        'timeout' => 30
    ));
    
    if (is_wp_error($response)) {
        return $response;
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    $body_response = json_decode(wp_remote_retrieve_body($response), true);
    
    if ($status_code !== 200) {
        $error_msg = isset($body_response['error']['message']) ? $body_response['error']['message'] : 'API error';
        return new WP_Error('api_error', $error_msg);
    }
    
    // Cache for 1 hour
    set_transient($cache_key, $body_response, 3600);
    
    return $body_response;
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
 * Direct search method - accepts pre-formatted payload
 */
public function search_hotels_direct($payload) {
    if (empty($this->api_key) || empty($this->secret)) {
        error_log('Hotelbeds API: Missing credentials');
        return new WP_Error('api_error', 'API credentials not configured');
    }
    
    // Check cache
    $cache_key = 'digicells_hotel_search_' . md5(json_encode($payload));
    $cached_result = get_transient($cache_key);
    if ($cached_result !== false) {
        error_log('Hotelbeds API: Returning cached result');
        return $cached_result;
    }
    
    $sig_data = $this->generate_signature();
    
    $url = $this->base_url . '/hotels';
    
    error_log('Hotelbeds API: Calling URL: ' . $url);
    error_log('Hotelbeds API: Payload: ' . json_encode($payload));
    
    $response = wp_remote_post($url, array(
        'headers' => array(
            'Api-Key' => $this->api_key,
            'X-Signature' => $sig_data['signature'],
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode($payload),
        'timeout' => 30,
        'sslverify' => false  // Only for testing
    ));
    
    if (is_wp_error($response)) {
        error_log('Hotelbeds API: WP Error - ' . $response->get_error_message());
        return $response;
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    error_log('Hotelbeds API: Response Code: ' . $status_code);
    error_log('Hotelbeds API: Response Body: ' . substr($body, 0, 500));
    
    if ($status_code !== 200) {
        $error_msg = 'API returned status ' . $status_code;
        $body_data = json_decode($body, true);
        if (isset($body_data['error']['message'])) {
            $error_msg = $body_data['error']['message'];
        }
        return new WP_Error('api_error', $error_msg);
    }
    
    $body_data = json_decode($body, true);
    
    // Cache for 1 hour
    set_transient($cache_key, $body_data, 3600);
    
    return $body_data;
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