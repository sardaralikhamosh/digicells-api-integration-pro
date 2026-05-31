<?php
// AJAX handler for hotel search
add_action('wp_ajax_digicells_search_hotels', 'digicells_ajax_search_hotels');
add_action('wp_ajax_nopriv_digicells_search_hotels', 'digicells_ajax_search_hotels');

function digicells_ajax_search_hotels() {
    // Enable error reporting for debugging
    error_log('=== DIGICELLS SEARCH REQUEST RECEIVED ===');
    error_log('Raw POST data: ' . print_r($_POST, true));
    
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'digicells_hotel_search')) {
        error_log('Nonce verification failed');
        wp_send_json_error('Security verification failed. Please refresh the page.');
        return;
    }
    
    // Get values - using direct $_POST access
    $destination = isset($_POST['destination']) ? sanitize_text_field($_POST['destination']) : '';
    $check_in = isset($_POST['check_in']) ? sanitize_text_field($_POST['check_in']) : '';
    $check_out = isset($_POST['check_out']) ? sanitize_text_field($_POST['check_out']) : '';
    $rooms = isset($_POST['rooms']) ? intval($_POST['rooms']) : 1;
    $adults = isset($_POST['adults']) ? intval($_POST['adults']) : 1;
    $children = isset($_POST['children']) ? intval($_POST['children']) : 0;
    
    // Convert destination to uppercase
    $destination = strtoupper(trim($destination));
    
    // Log the received values
    error_log("Received - Destination: $destination, Check-in: $check_in, Check-out: $check_out, Rooms: $rooms, Adults: $adults, Children: $children");
    
    // Validate
    if (empty($destination)) {
        wp_send_json_error('Please enter a destination code');
        return;
    }
    
    if (strlen($destination) != 3) {
        wp_send_json_error('Destination code must be exactly 3 letters (e.g., PAR, NYC, LON)');
        return;
    }
    
    if (empty($check_in)) {
        error_log('ERROR: Check-in date is empty');
        wp_send_json_error('Please select a check-in date');
        return;
    }
    
    if (empty($check_out)) {
        error_log('ERROR: Check-out date is empty');
        wp_send_json_error('Please select a check-out date');
        return;
    }
    
    if ($check_in >= $check_out) {
        wp_send_json_error('Check-out date must be after check-in date');
        return;
    }
    
    if ($rooms < 1) {
        wp_send_json_error('Please select at least 1 room');
        return;
    }
    
    if ($adults < 1) {
        wp_send_json_error('Please select at least 1 adult');
        return;
    }
    
    // Build the API request payload
    $api_payload = array(
        'stay' => array(
            'from' => $check_in,
            'to' => $check_out
        ),
        'destination' => array(
            'code' => $destination
        ),
        'occupancies' => array()
    );
    
    // Build occupancy
    if ($children > 0) {
        $paxes = array();
        for ($i = 0; $i < $adults; $i++) {
            $paxes[] = array('type' => 'AD');
        }
        for ($i = 0; $i < $children; $i++) {
            $paxes[] = array('type' => 'CH', 'age' => 8);
        }
        $api_payload['occupancies'][] = array(
            'rooms' => $rooms,
            'adults' => $adults,
            'children' => $children,
            'paxes' => $paxes
        );
    } else {
        $api_payload['occupancies'][] = array(
            'rooms' => $rooms,
            'adults' => $adults,
            'children' => 0
        );
    }
    
    // Add language
    $api_payload['language'] = 'ENG';
    
    error_log('API Payload: ' . json_encode($api_payload));
    
    // Call the API
    $api = new Hotelbeds_API();
    $results = $api->search_hotels_direct($api_payload);
    
    if (is_wp_error($results)) {
        error_log('API Error: ' . $results->get_error_message());
        wp_send_json_error($results->get_error_message());
        return;
    }
    
    // Display results
    $hotels = isset($results['hotels']['hotels']) ? $results['hotels']['hotels'] : array();
    
    ob_start();
    
    if (empty($hotels)) {
        echo '<div style="text-align:center;padding:40px;background:#f9f9f9;border-radius:12px;margin:20px 0;">';
        echo '<p style="font-size:18px;">No hotels found for <strong>' . esc_html($destination) . '</strong></p>';
        echo '<p>from <strong>' . esc_html($check_in) . '</strong> to <strong>' . esc_html($check_out) . '</strong></p>';
        echo '<p style="color:#666;">Try these popular destinations:</p>';
        echo '<div style="display:flex;flex-wrap:wrap;gap:10px;justify-content:center;">';
        echo '<span style="background:#e0e0e0;padding:5px 10px;border-radius:5px;">PAR (Paris)</span>';
        echo '<span style="background:#e0e0e0;padding:5px 10px;border-radius:5px;">LON (London)</span>';
        echo '<span style="background:#e0e0e0;padding:5px 10px;border-radius:5px;">NYC (New York)</span>';
        echo '<span style="background:#e0e0e0;padding:5px 10px;border-radius:5px;">DXB (Dubai)</span>';
        echo '<span style="background:#e0e0e0;padding:5px 10px;border-radius:5px;">BKK (Bangkok)</span>';
        echo '</div></div>';
    } else {
        echo '<div style="margin:20px 0 30px;">';
        echo '<h3>🏨 Found ' . count($hotels) . ' hotels in ' . esc_html($destination) . '</h3>';
        echo '</div>';
        
        echo '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:25px;">';
        
        foreach ($hotels as $hotel) {
            $name = isset($hotel['name']['content']) ? $hotel['name']['content'] : (isset($hotel['name']) ? $hotel['name'] : 'Hotel Name');
            $code = isset($hotel['code']) ? $hotel['code'] : 0;
            $stars = isset($hotel['categoryCode']) ? intval($hotel['categoryCode']) : 0;
            $rate = isset($hotel['minRate']) ? $hotel['minRate'] : 0;
            $currency = isset($hotel['currency']) ? $hotel['currency'] : 'USD';
            
            echo '<div style="background:white;border-radius:12px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,0.1);transition:transform 0.3s;">';
            echo '<div style="height:180px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);display:flex;align-items:center;justify-content:center;font-size:50px;">🏨</div>';
            echo '<div style="padding:20px;">';
            echo '<h4 style="margin:0 0 10px;">' . esc_html($name) . '</h4>';
            
            if ($stars > 0) {
                echo '<div style="color:#ffc107;margin-bottom:10px;">' . str_repeat('★', $stars) . str_repeat('☆', 5 - $stars) . '</div>';
            }
            
            if ($rate > 0) {
                echo '<div style="margin:15px 0;padding-top:10px;border-top:1px solid #eee;">';
                echo '<span style="font-size:12px;color:#999;">Starting from</span><br>';
                echo '<span style="font-size:24px;font-weight:bold;color:#667eea;">' . esc_html($currency) . ' ' . number_format($rate, 2) . '</span>';
                echo '<span style="font-size:12px;color:#999;"> per night</span>';
                echo '</div>';
            }
            
            echo '<button onclick="alert(\'Hotel details for ' . esc_js($code) . ' - Coming soon!\')" style="width:100%;padding:12px;background:#667eea;color:white;border:none;border-radius:6px;cursor:pointer;font-weight:600;">View Details</button>';
            echo '</div></div>';
        }
        
        echo '</div>';
    }
    
    $html = ob_get_clean();
    wp_send_json_success(array('html' => $html));
}