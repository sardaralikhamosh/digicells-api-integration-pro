<?php
// AJAX handler for hotel search
add_action('wp_ajax_digicells_search_hotels', 'digicells_ajax_search_hotels');
add_action('wp_ajax_nopriv_digicells_search_hotels', 'digicells_ajax_search_hotels');

function digicells_ajax_search_hotels() {
    // Verify nonce
    if (!check_ajax_referer('digicells_hotel_search', 'nonce', false)) {
        wp_send_json_error('Security verification failed');
        return;
    }
    
    // Get parameters directly from POST
    $destination = isset($_POST['destination']) ? strtoupper(trim(sanitize_text_field($_POST['destination']))) : '';
    $check_in = isset($_POST['check_in']) ? sanitize_text_field($_POST['check_in']) : '';
    $check_out = isset($_POST['check_out']) ? sanitize_text_field($_POST['check_out']) : '';
    $rooms = isset($_POST['rooms']) ? intval($_POST['rooms']) : 1;
    $adults = isset($_POST['adults']) ? intval($_POST['adults']) : 1;
    $children = isset($_POST['children']) ? intval($_POST['children']) : 0;
    
    // Debug logging
    error_log('Digicells Search Request - Destination: ' . $destination);
    error_log('Digicells Search Request - Dates: ' . $check_in . ' to ' . $check_out);
    error_log('Digicells Search Request - Rooms: ' . $rooms . ', Adults: ' . $adults . ', Children: ' . $children);
    
    // Validate destination code (must be 1-3 characters)
    if (empty($destination) || strlen($destination) < 1 || strlen($destination) > 3) {
        wp_send_json_error('Please enter a valid 1-3 character city code (e.g., PAR, NYC, LON)');
        return;
    }
    
    // Validate dates
    if (empty($check_in) || empty($check_out)) {
        wp_send_json_error('Please enter both check-in and check-out dates');
        return;
    }
    
    // Validate date format and order
    if ($check_in >= $check_out) {
        wp_send_json_error('Check-out date must be after check-in date');
        return;
    }
    
    // Build occupancies array correctly for Hotelbeds API
    $occupancies = array();
    
    if ($children > 0) {
        // When children are present, we need to specify child ages
        // Hotelbeds requires ages for each child (typically between 2-12)
        $paxes = array();
        for ($i = 0; $i < $adults; $i++) {
            $paxes[] = array('type' => 'AD');
        }
        for ($i = 0; $i < $children; $i++) {
            $paxes[] = array('type' => 'CH', 'age' => 8); // Default age 8
        }
        
        $occupancies[] = array(
            'rooms' => $rooms,
            'adults' => $adults,
            'children' => $children,
            'paxes' => $paxes
        );
    } else {
        // No children - simple occupancy
        $occupancies[] = array(
            'rooms' => $rooms,
            'adults' => $adults,
            'children' => 0
        );
    }
    
    // Prepare parameters for API
    $params = array(
        'destination' => $destination,
        'check_in' => $check_in,
        'check_out' => $check_out,
        'occupancies' => $occupancies
    );
    
    // Debug log the final params
    error_log('Digicells Final Params: ' . json_encode($params));
    
    // Perform search
    $api = new Hotelbeds_API();
    $results = $api->search_hotels($params);
    
    if (is_wp_error($results)) {
        error_log('Digicells API Error: ' . $results->get_error_message());
        wp_send_json_error($results->get_error_message());
        return;
    }
    
    // Extract hotels from response
    $hotels = isset($results['hotels']['hotels']) ? $results['hotels']['hotels'] : array();
    
    // Start HTML output
    ob_start();
    
    if (empty($hotels)) {
        echo '<div class="digicells-no-results">';
        echo '<p>No hotels found for <strong>' . esc_html($destination) . '</strong> from <strong>' . esc_html($check_in) . '</strong> to <strong>' . esc_html($check_out) . '</strong>.</p>';
        echo '<p>Try:</p>';
        echo '<ul>';
        echo '<li>A different destination code (e.g., PAR, NYC, LON, DXB)</li>';
        echo '<li>Different travel dates</li>';
        echo '<li>Fewer guests</li>';
        echo '</ul>';
        echo '</div>';
    } else {
        echo '<div class="digicells-results-header">';
        echo '<h3>Found ' . count($hotels) . ' hotels in ' . esc_html($destination) . '</h3>';
        echo '</div>';
        
        echo '<div class="digicells-hotels-grid">';
        foreach ($hotels as $hotel) {
            // Safely get hotel data
            $hotel_name = isset($hotel['name']['content']) ? $hotel['name']['content'] : (isset($hotel['name']) ? $hotel['name'] : 'Hotel Name Not Available');
            $hotel_code = isset($hotel['code']) ? $hotel['code'] : 0;
            $stars = isset($hotel['categoryCode']) ? intval($hotel['categoryCode']) : 0;
            $address = isset($hotel['address']['content']) ? $hotel['address']['content'] : (isset($hotel['address']) ? $hotel['address'] : 'Address not available');
            $min_rate = isset($hotel['minRate']) ? $hotel['minRate'] : 0;
            $currency = isset($hotel['currency']) ? $hotel['currency'] : 'USD';
            
            // Get image
            $image_url = '';
            if (isset($hotel['images']) && is_array($hotel['images']) && count($hotel['images']) > 0) {
                $image_url = isset($hotel['images'][0]['lowResUrl']) ? $hotel['images'][0]['lowResUrl'] : '';
            }
            ?>
            <div class="digicells-hotel-card">
                <?php if ($image_url): ?>
                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($hotel_name); ?>" class="digicells-hotel-image">
                <?php else: ?>
                    <div class="digicells-hotel-image-placeholder">🏨</div>
                <?php endif; ?>
                
                <div class="digicells-hotel-info">
                    <h3><?php echo esc_html($hotel_name); ?></h3>
                    
                    <?php if ($stars > 0): ?>
                        <div class="digicells-hotel-stars">
                            <?php echo str_repeat('★', $stars) . str_repeat('☆', 5 - $stars); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="digicells-hotel-address">
                        <?php echo esc_html($address); ?>
                    </div>
                    
                    <?php if ($min_rate > 0): ?>
                        <div class="digicells-hotel-price">
                            <span class="price-label">Starting from:</span>
                            <span class="price-value"><?php echo esc_html($currency); ?> <?php echo number_format($min_rate, 2); ?></span>
                            <span class="price-period">per night</span>
                        </div>
                    <?php endif; ?>
                    
                    <button class="digicells-view-details" data-hotel-code="<?php echo esc_attr($hotel_code); ?>">
                        View Details
                    </button>
                </div>
            </div>
            <?php
        }
        echo '</div>';
    }
    
    $html = ob_get_clean();
    wp_send_json_success(array('html' => $html));
}

// AJAX handler for hotel details
add_action('wp_ajax_digicells_get_hotel_details', 'digicells_get_hotel_details');
add_action('wp_ajax_nopriv_digicells_get_hotel_details', 'digicells_get_hotel_details');

function digicells_get_hotel_details() {
    if (!check_ajax_referer('digicells_hotel_search', 'nonce', false)) {
        wp_send_json_error('Security verification failed');
        return;
    }
    
    $hotel_code = isset($_POST['hotel_code']) ? intval($_POST['hotel_code']) : 0;
    
    if (!$hotel_code) {
        wp_send_json_error('Invalid hotel code');
        return;
    }
    
    $api = new Hotelbeds_API();
    $details = $api->get_hotel_details($hotel_code);
    
    if (is_wp_error($details)) {
        wp_send_json_error($details->get_error_message());
        return;
    }
    
    wp_send_json_success($details);
}