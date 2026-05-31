<?php
add_action('admin_menu', 'digicells_add_admin_menu');
add_action('admin_init', 'digicells_settings_init');

function digicells_add_admin_menu() {
    add_menu_page(
        'Digicells API Pro',
        'Digicells API',
        'manage_options',
        'digicells-api-pro',
        'digicells_settings_page',
        'dashicons-building',
        25
    );
    
    add_submenu_page(
        'digicells-api-pro',
        'API Status',
        'API Status',
        'manage_options',
        'digicells-api-status',
        'digicells_status_page'
    );
}

function digicells_settings_init() {
    register_setting('digicells_api_settings', 'digicells_hotelbeds_api_key');
    register_setting('digicells_api_settings', 'digicells_hotelbeds_secret');
    register_setting('digicells_api_settings', 'digicells_hotelbeds_environment');
    register_setting('digicells_api_settings', 'digicells_cache_duration');
    
    add_settings_section(
        'digicells_api_section',
        'Hotelbeds API Configuration',
        'digicells_settings_section_callback',
        'digicells-api-pro'
    );
    
    add_settings_field(
        'digicells_hotelbeds_api_key',
        'API Key',
        'digicells_api_key_render',
        'digicells-api-pro',
        'digicells_api_section'
    );
    
    add_settings_field(
        'digicells_hotelbeds_secret',
        'API Secret',
        'digicells_secret_render',
        'digicells-api-pro',
        'digicells_api_section'
    );
    
    add_settings_field(
        'digicells_hotelbeds_environment',
        'Environment',
        'digicells_environment_render',
        'digicells-api-pro',
        'digicells_api_section'
    );
}

function digicells_api_key_render() {
    $value = get_option('digicells_hotelbeds_api_key', '');
    echo '<input type="text" name="digicells_hotelbeds_api_key" value="' . esc_attr($value) . '" class="regular-text">';
    echo '<p class="description">Enter your Hotelbeds API Key from the dashboard</p>';
}

function digicells_secret_render() {
    $value = get_option('digicells_hotelbeds_secret', '');
    echo '<input type="password" name="digicells_hotelbeds_secret" value="' . esc_attr($value) . '" class="regular-text">';
    echo '<p class="description">Enter your Hotelbeds API Secret</p>';
}

function digicells_environment_render() {
    $value = get_option('digicells_hotelbeds_environment', 'test');
    echo '<select name="digicells_hotelbeds_environment">
            <option value="test" ' . selected($value, 'test', false) . '>Test Environment (PRUEBAS)</option>
            <option value="live" ' . selected($value, 'live', false) . '>Live Production</option>
          </select>';
    echo '<p class="description">Use Test environment for development. Switch to Live when ready to accept real bookings.</p>';
}

function digicells_settings_section_callback() {
    echo '<p>Configure your Hotelbeds API credentials. You are currently using the <strong>PRUEBAS (Test)</strong> account with 50 requests/day limit.</p>';
}

function digicells_settings_page() {
    ?>
    <div class="wrap">
        <h1>Digicells API Integration Pro</h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('digicells_api_settings');
            do_settings_sections('digicells-api-pro');
            submit_button('Save API Credentials');
            ?>
        </form>
        
        <hr>
        
        <h2>Quick Setup Guide</h2>
        <div style="background: #f0f8ff; padding: 15px; border-left: 4px solid #0073aa;">
            <h3>Your API Credentials (Already Entered Above)</h3>
            <p><strong>API Key:</strong> 6f79b93bb911485a9b2e7b4b582b2d21</p>
            <p><strong>Secret:</strong> E7OfeGYARa</p>
            <p><strong>Environment:</strong> Test (PRUEBAS)</p>
            
            <h3>Using the Shortcodes:</h3>
            <p>Add the search form to any page using the shortcode: <code>[digicells_hotel_search]</code></p>
            <p>Add the results display using: <code>[digicells_hotel_results]</code></p>
            
            <h3>Example Page Content:</h3>
            <code>[digicells_hotel_search]<br>[digicells_hotel_results]</code>
        </div>
    </div>
    <?php
}

function digicells_status_page() {
    $api = new Hotelbeds_API();
    $is_connected = $api->check_status();
    $remaining_calls = $api->get_remaining_calls();
    ?>
    <div class="wrap">
        <h1>API Status Dashboard</h1>
        
        <div class="status-box" style="background: #f9f9f9; padding: 20px; border-radius: 8px;">
            <h2>Connection Status</h2>
            <?php if ($is_connected): ?>
                <p style="color: green; font-size: 18px;">✓ API Connected Successfully</p>
            <?php else: ?>
                <p style="color: red; font-size: 18px;">✗ API Connection Failed - Check your credentials</p>
            <?php endif; ?>
            
            <h2>Rate Limit Status</h2>
            <p>Remaining API calls today: <strong><?php echo esc_html($remaining_calls); ?></strong> out of 50</p>
            <div style="background: #e0e0e0; height: 10px; width: 100%; border-radius: 5px;">
                <div style="background: #0073aa; height: 10px; width: <?php echo ($remaining_calls / 50) * 100; ?>%; border-radius: 5px;"></div>
            </div>
            
            <h2>Usage Statistics (Last 30 Days)</h2>
            <?php
            $usage = get_option('digicells_api_usage', array());
            if (empty($usage)) {
                echo '<p>No API calls recorded yet.</p>';
            } else {
                echo '<table class="wp-list-table widefat fixed striped">';
                echo '<thead><tr><th>Date</th><th>API Calls</th></tr></thead><tbody>';
                foreach ($usage as $date => $count) {
                    echo '<tr><td>' . esc_html($date) . '</td><td>' . intval($count) . '</td></tr>';
                }
                echo '</tbody></table>';
            }
            ?>
        </div>
    </div>
    <?php
}