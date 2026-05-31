<?php
// AJAX handler for hotel search
add_action('wp_ajax_digicells_search_hotels', 'digicells_ajax_search_hotels');
add_action('wp_ajax_nopriv_digicells_search_hotels', 'digicells_ajax_search_hotels');

function digicells_ajax_search_hotels() {
    // Debug log
    error_log('=== DIGICELLS SEARCH START ===');
    error_log('POST: ' . print_r($_POST, true));
    
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'digicells_hotel_search')) {
        wp_send_json_error('Security error. Please refresh page.');
        return;
    }
    
    // Get values directly from POST
    $destination = isset($_POST['destination']) ? strtoupper(trim($_POST['destination'])) : '';
    $check_in = isset($_POST['check_in']) ? trim($_POST['check_in']) : '';
    $check_out = isset($_POST['check_out']) ? trim($_POST['check_out']) : '';
    $rooms = isset($_POST['rooms']) ? intval($_POST['rooms']) : 1;
    $adults = isset($_POST['adults']) ? intval($_POST['adults']) : 1;
    $children = isset($_POST['children']) ? intval($_POST['children']) : 0;
    
    error_log("Values: dest=$destination, in=$check_in, out=$check_out, rooms=$rooms, adults=$adults, children=$children");
    
    // Validate
    if (empty($destination) || strlen($destination) != 3) {
        wp_send_json_error('Please enter a valid 3-letter city code (e.g., PAR, NYC, LON)');
        return;
    }
    
    if (empty($check_in)) {
        wp_send_json_error('Please select check-in date');
        return;
    }
    
    if (empty($check_out)) {
        wp_send_json_error('Please select check-out date');
        return;
    }
    
    if ($check_in >= $check_out) {
        wp_send_json_error('Check-out must be after check-in');
        return;
    }
    
    if ($rooms < 1) {
        wp_send_json_error('At least 1 room required');
        return;
    }
    
    if ($adults < 1) {
        wp_send_json_error('At least 1 adult required');
        return;
    }
    
    // Build API request
    $occupancies = array();
    
    if ($children > 0) {
        $paxes = array();
        for ($i = 0; $i < $adults; $i++) {
            $paxes[] = array('type' => 'AD');
        }
        for ($i = 0; $i < $children; $i++) {
            $paxes[] = array('type' => 'CH', 'age' => 8);
        }
        $occupancies[] = array(
            'rooms' => $rooms,
            'adults' => $adults,
            'children' => $children,
            'paxes' => $paxes
        );
    } else {
        $occupancies[] = array(
            'rooms' => $rooms,
            'adults' => $adults,
            'children' => 0
        );
    }
    
    $api_params = array(
        'destination' => $destination,
        'check_in' => $check_in,
        'check_out' => $check_out,
        'occupancies' => $occupancies
    );
    
    error_log('API Params: ' . json_encode($api_params));
    
    // Call API
    $api = new Hotelbeds_API();
    $results = $api->search_hotels($api_params);
    
    if (is_wp_error($results)) {
        error_log('API Error: ' . $results->get_error_message());
        wp_send_json_error($results->get_error_message());
        return;
    }
    
    // Display results
    $hotels = isset($results['hotels']['hotels']) ? $results['hotels']['hotels'] : array();
    
    ob_start();
    
    if (empty($hotels)) {
        echo '<div style="text-align:center;padding:40px;background:#f9f9f9;border-radius:12px;">';
        echo '<p>No hotels found for <strong>' . esc_html($destination) . '</strong></p>';
        echo '<p>from <strong>' . esc_html($check_in) . '</strong> to <strong>' . esc_html($check_out) . '</strong></p>';
        echo '<p>Try: PAR (Paris), NYC (New York), LON (London), DXB (Dubai), BKK (Bangkok)</p>';
        echo '</div>';
    } else {
        echo '<div style="margin:20px 0 30px;padding-bottom:15px;border-bottom:2px solid #667eea;">';
        echo '<h3 style="margin:0;">🏨 Found ' . count($hotels) . ' hotels in ' . esc_html($destination) . '</h3>';
        echo '</div>';
        
        echo '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px;">';
        
        foreach ($hotels as $hotel) {
            $name = isset($hotel['name']['content']) ? $hotel['name']['content'] : 'Hotel';
            $code = isset($hotel['code']) ? $hotel['code'] : 0;
            $stars = isset($hotel['categoryCode']) ? intval($hotel['categoryCode']) : 0;
            $rate = isset($hotel['minRate']) ? $hotel['minRate'] : 0;
            $currency = isset($hotel['currency']) ? $hotel['currency'] : 'USD';
            
            echo '<div style="background:white;border-radius:12px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,0.1);">';
            echo '<div style="height:160px;background:#667eea;display:flex;align-items:center;justify-content:center;font-size:48px;">🏨</div>';
            echo '<div style="padding:15px;">';
            echo '<h4 style="margin:0 0 8px;">' . esc_html($name) . '</h4>';
            if ($stars > 0) {
                echo '<div style="color:#ffc107;margin-bottom:8px;">' . str_repeat('★', $stars) . str_repeat('☆', 5 - $stars) . '</div>';
            }
            if ($rate > 0) {
                echo '<div style="margin:10px 0;"><strong>' . esc_html($currency) . ' ' . number_format($rate, 2) . '</strong> per night</div>';
            }
            echo '<button onclick="alert(\'Hotel ' . esc_js($code) . ' details coming soon\')" style="width:100%;padding:10px;background:#667eea;color:white;border:none;border-radius:6px;cursor:pointer;">View Details</button>';
            echo '</div></div>';
        }
        
        echo '</div>';
    }
    
    $html = ob_get_clean();
    wp_send_json_success(array('html' => $html));
}