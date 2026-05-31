<?php
// AJAX handler for hotel search
add_action('wp_ajax_digicells_search_hotels', 'digicells_ajax_search_hotels');
add_action('wp_ajax_nopriv_digicells_search_hotels', 'digicells_ajax_search_hotels');

function digicells_ajax_search_hotels() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'digicells_hotel_search')) {
        wp_send_json_error('Security verification failed');
        return;
    }
    
    // Get parameters (now expecting occupancies array)
    $destination = strtoupper(sanitize_text_field($_POST['destination']));
    $check_in = sanitize_text_field($_POST['check_in']);
    $check_out = sanitize_text_field($_POST['check_out']);
    $occupancies = isset($_POST['occupancies']) ? $_POST['occupancies'] : array();
    
    // Validate destination code (must be 1-3 characters)
    if (strlen($destination) < 1 || strlen($destination) > 3) {
        wp_send_json_error('Destination code must be 1-3 characters (e.g., PAR, NYC, LON)');
        return;
    }
    
    // Validate dates
    if (empty($check_in) || empty($check_out)) {
        wp_send_json_error('Please enter check-in and check-out dates');
        return;
    }
    
    // Validate date format and order
    if ($check_in >= $check_out) {
        wp_send_json_error('Check-out date must be after check-in date');
        return;
    }
    
    // Validate occupancies
    if (empty($occupancies)) {
        wp_send_json_error('Please select number of rooms and guests');
        return;
    }
    
    // Prepare parameters for API
    $params = array(
        'destination' => $destination,
        'check_in' => $check_in,
        'check_out' => $check_out,
        'occupancies' => $occupancies
    );
    
    // Perform search
    $api = new Hotelbeds_API();
    $results = $api->search_hotels($params);
    
    if (is_wp_error($results)) {
        wp_send_json_error($results->get_error_message());
        return;
    }
    
    // Extract hotels from response
    $hotels = isset($results['hotels']['hotels']) ? $results['hotels']['hotels'] : array();
    
    // Start HTML output
    ob_start();
    
    if (empty($hotels)) {
        echo '<div class="digicells-no-results">
                <p>No hotels found for ' . esc_html($destination) . ' from ' . esc_html($check_in) . ' to ' . esc_html($check_out) . '.</p>
                <p>Try different dates or destination code.</p>
              </div>';
    } else {
        echo '<div class="digicells-hotels-grid">';
        foreach ($hotels as $hotel) {
            ?>
            <div class="digicells-hotel-card">
                <?php 
                // Get hotel image
                $image_url = '';
                if (isset($hotel['images']) && is_array($hotel['images']) && count($hotel['images']) > 0) {
                    $image_url = $hotel['images'][0]['lowResUrl'] ?? '';
                }
                ?>
                <?php if ($image_url): ?>
                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($hotel['name']['content'] ?? 'Hotel'); ?>" class="digicells-hotel-image">
                <?php else: ?>
                    <div class="digicells-hotel-image-placeholder">🏨</div>
                <?php endif; ?>
                
                <div class="digicells-hotel-info">
                    <h3><?php echo esc_html($hotel['name']['content'] ?? 'Hotel Name Not Available'); ?></h3>
                    
                    <?php if (isset($hotel['categoryCode'])): ?>
                        <div class="digicells-hotel-stars">
                            <?php 
                            $stars = intval($hotel['categoryCode']);
                            echo str_repeat('★', $stars) . str_repeat('☆', 5 - $stars);
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="digicells-hotel-address">
                        <?php 
                        $address = $hotel['address']['content'] ?? $hotel['address'] ?? 'Address not available';
                        echo esc_html($address);
                        ?>
                    </div>
                    
                    <?php if (isset($hotel['minRate'])): ?>
                        <div class="digicells-hotel-price">
                            <span class="price-label">Starting from:</span>
                            <span class="price-value"><?php echo esc_html($hotel['currency'] ?? 'USD'); ?> <?php echo number_format($hotel['minRate'], 2); ?></span>
                            <span class="price-period">per night</span>
                        </div>
                    <?php endif; ?>
                    
                    <button class="digicells-view-details" data-hotel-code="<?php echo esc_attr($hotel['code']); ?>">
                        View Details
                    </button>
                </div>
            </div>
            <?php
        }
        echo '</div>';
        
        if (isset($results['hotels']['total'])) {
            echo '<div class="digicells-pagination">';
            echo '<p>Found ' . intval($results['hotels']['total']) . ' hotels</p>';
            echo '</div>';
        }
    }
    
    $html = ob_get_clean();
    wp_send_json_success(array('html' => $html));
}

// AJAX handler for hotel details
add_action('wp_ajax_digicells_get_hotel_details', 'digicells_get_hotel_details');
add_action('wp_ajax_nopriv_digicells_get_hotel_details', 'digicells_get_hotel_details');

function digicells_get_hotel_details() {
    if (!wp_verify_nonce($_POST['nonce'], 'digicells_hotel_search')) {
        wp_send_json_error('Security verification failed');
        return;
    }
    
    $hotel_code = intval($_POST['hotel_code']);
    $api = new Hotelbeds_API();
    $details = $api->get_hotel_details($hotel_code);
    
    if (is_wp_error($details)) {
        wp_send_json_error($details->get_error_message());
        return;
    }
    
    wp_send_json_success($details);
}