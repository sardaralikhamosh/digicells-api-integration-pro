<?php
// AJAX handler for hotel search
add_action('wp_ajax_digicells_search_hotels', 'digicells_ajax_search_hotels');
add_action('wp_ajax_nopriv_digicells_search_hotels', 'digicells_ajax_search_hotels');

function digicells_ajax_search_hotels() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'digicells_hotel_search')) {
        wp_send_json_error('Security verification failed');
    }
    
    // Get and validate parameters
    $params = array(
        'destination' => sanitize_text_field($_POST['destination']),
        'check_in' => sanitize_text_field($_POST['check_in']),
        'check_out' => sanitize_text_field($_POST['check_out']),
        'rooms' => intval($_POST['rooms']),
        'adults' => intval($_POST['adults']),
        'children' => intval($_POST['children'])
    );
    
    // Validate dates
    if (empty($params['check_in']) || empty($params['check_out'])) {
        wp_send_json_error('Please enter check-in and check-out dates');
    }
    
    if ($params['check_in'] >= $params['check_out']) {
        wp_send_json_error('Check-out date must be after check-in date');
    }
    
    // Perform search
    $api = new Hotelbeds_API();
    $results = $api->search_hotels($params);
    
    if (is_wp_error($results)) {
        wp_send_json_error($results->get_error_message());
    }
    
    // Extract hotels from response
    $hotels = isset($results['hotels']['hotels']) ? $results['hotels']['hotels'] : array();
    
    // Start HTML output
    ob_start();
    
    if (empty($hotels)) {
        echo '<div class="digicells-no-results">
                <p>No hotels found for your criteria. Please try different dates or destination.</p>
              </div>';
    } else {
        echo '<div class="digicells-hotels-grid">';
        foreach ($hotels as $hotel) {
            ?>
            <div class="digicells-hotel-card">
                <?php if (isset($hotel['images'][0]['lowResUrl'])): ?>
                    <img src="<?php echo esc_url($hotel['images'][0]['lowResUrl']); ?>" alt="<?php echo esc_attr($hotel['name']['content']); ?>" class="digicells-hotel-image">
                <?php else: ?>
                    <div class="digicells-hotel-image-placeholder">🏨</div>
                <?php endif; ?>
                
                <div class="digicells-hotel-info">
                    <h3><?php echo esc_html($hotel['name']['content']); ?></h3>
                    
                    <?php if (isset($hotel['categoryCode'])): ?>
                        <div class="digicells-hotel-stars">
                            <?php echo str_repeat('★', intval($hotel['categoryCode'])); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="digicells-hotel-address">
                        <?php echo isset($hotel['address']['content']) ? esc_html($hotel['address']['content']) : 'Address not available'; ?>
                    </div>
                    
                    <?php if (isset($hotel['minRate'])): ?>
                        <div class="digicells-hotel-price">
                            <span class="price-label">Starting from:</span>
                            <span class="price-value"><?php echo esc_html($hotel['currency']); ?> <?php echo number_format($hotel['minRate'], 2); ?></span>
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
        
        echo '<div class="digicells-pagination">';
        if (isset($results['hotels']['total'])) {
            echo '<p>Found ' . intval($results['hotels']['total']) . ' hotels</p>';
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
    if (!wp_verify_nonce($_POST['nonce'], 'digicells_hotel_search')) {
        wp_send_json_error('Security verification failed');
    }
    
    $hotel_code = intval($_POST['hotel_code']);
    $api = new Hotelbeds_API();
    $details = $api->get_hotel_details($hotel_code);
    
    if (is_wp_error($details)) {
        wp_send_json_error($details->get_error_message());
    }
    
    wp_send_json_success($details);
}