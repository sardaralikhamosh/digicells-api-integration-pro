jQuery(document).ready(function($) {
    
    // Set default dates (today and tomorrow)
    var today = new Date();
    var tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    
    // Format dates as YYYY-MM-DD
    function formatDate(date) {
        var year = date.getFullYear();
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var day = String(date.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }
    
    // Set default values
    $('#check_in').val(formatDate(today));
    $('#check_out').val(formatDate(tomorrow));
    $('#destination').val('PAR'); // Default to Paris for testing
    
    // Auto-uppercase destination code
    $('#destination').on('input', function() {
        $(this).val($(this).val().toUpperCase());
        // Limit to 3 characters
        if ($(this).val().length > 3) {
            $(this).val($(this).val().substring(0, 3));
        }
    });
    
    // Handle form submission
    $('#digicells-hotel-search-form').on('submit', function(e) {
        e.preventDefault();
        
        // Get form data directly as simple fields
        var destination = $('#destination').val().trim().toUpperCase();
        var check_in = $('#check_in').val();
        var check_out = $('#check_out').val();
        var rooms = $('#rooms').val();
        var adults = $('#adults').val();
        var children = $('#children').val();
        
        console.log('Form Data:', {
            destination: destination,
            check_in: check_in,
            check_out: check_out,
            rooms: rooms,
            adults: adults,
            children: children
        });
        
        // Validate destination
        if (!destination || destination.length < 1 || destination.length > 3) {
            alert('Please enter a valid 1-3 character city code (e.g., PAR, NYC, LON, DXB)');
            return;
        }
        
        // Validate dates
        if (!check_in || !check_out) {
            alert('Please enter both check-in and check-out dates');
            return;
        }
        
        if (check_in >= check_out) {
            alert('Check-out date must be after check-in date');
            return;
        }
        
        // Validate guests
        if (parseInt(rooms) < 1) {
            alert('Please select at least 1 room');
            return;
        }
        
        if (parseInt(adults) < 1) {
            alert('Please select at least 1 adult');
            return;
        }
        
        // Show loader
        $('#digicells-search-loader').show();
        $('#digicells-search-results').html('');
        
        // Prepare form data for AJAX - using simple key-value pairs
        var formData = {
            action: 'digicells_search_hotels',
            nonce: digicells_ajax.nonce,
            destination: destination,
            check_in: check_in,
            check_out: check_out,
            rooms: rooms,
            adults: adults,
            children: children
        };
        
        console.log('Sending AJAX request:', formData);
        
        // Make AJAX request
        $.ajax({
            url: digicells_ajax.ajax_url,
            type: 'POST',
            data: formData,
            dataType: 'json',
            timeout: 60000, // 60 second timeout
            success: function(response) {
                console.log('AJAX Response:', response);
                $('#digicells-search-loader').hide();
                
                if (response.success) {
                    $('#digicells-search-results').html(response.data.html);
                    
                    // Scroll to results smoothly
                    $('html, body').animate({
                        scrollTop: $('#digicells-search-results').offset().top - 50
                    }, 500);
                } else {
                    var errorMsg = response.data || 'Unknown error occurred';
                    $('#digicells-search-results').html('<div class="digicells-error"><p>❌ ' + errorMsg + '</p></div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                console.error('XHR Response:', xhr.responseText);
                $('#digicells-search-loader').hide();
                $('#digicells-search-results').html('<div class="digicells-error"><p>❌ Network error: ' + error + '<br>Please try again.</p></div>');
            }
        });
    });
    
    // Handle view details clicks
    $(document).on('click', '.digicells-view-details', function() {
        var hotelCode = $(this).data('hotel-code');
        alert('Hotel details for code: ' + hotelCode + '\n\nFull details feature coming soon!');
    });
    
    // Add loading indicator styles
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .digicells-error {
                background: #fee;
                border: 1px solid #fcc;
                border-radius: 8px;
                padding: 20px;
                margin: 20px 0;
                color: #c33;
                text-align: center;
            }
            .digicells-results-header {
                margin: 20px 0;
                padding-bottom: 10px;
                border-bottom: 2px solid #667eea;
            }
            .digicells-results-header h3 {
                margin: 0;
                color: #333;
            }
        `)
        .appendTo('head');
});