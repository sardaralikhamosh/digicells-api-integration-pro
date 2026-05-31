jQuery(document).ready(function($) {
    
    // Set default dates (today and tomorrow)
    var today = new Date();
    var tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    
    // Format dates as YYYY-MM-DD (required by Hotelbeds)
    function formatDate(date) {
        var year = date.getFullYear();
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var day = String(date.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }
    
    $('#check_in').val(formatDate(today));
    $('#check_out').val(formatDate(tomorrow));
    
    // Handle form submission
    $('#digicells-hotel-search-form').on('submit', function(e) {
        e.preventDefault();
        
        // Get form data
        var destination = $('#destination').val().trim().toUpperCase();
        var check_in = $('#check_in').val();
        var check_out = $('#check_out').val();
        var rooms = parseInt($('#rooms').val());
        var adults = parseInt($('#adults').val());
        var children = parseInt($('#children').val());
        
        // Validate destination code (must be 1-3 uppercase letters)
        if (destination.length < 1 || destination.length > 3) {
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
        
        // Create occupancies array with child ages if needed
        var occupancies = [];
        
        if (children > 0) {
            // For each room, create occupancy with children ages
            // Hotelbeds requires child ages (typically 2-12 years old)
            // Using default age of 8 for each child
            var childAges = [];
            for (var i = 0; i < children; i++) {
                childAges.push(8); // Default age 8
            }
            
            occupancies.push({
                rooms: rooms,
                adults: adults,
                children: children,
                paxes: childAges.map(function(age) {
                    return { type: 'CH', age: age };
                })
            });
        } else {
            occupancies.push({
                rooms: rooms,
                adults: adults,
                children: 0
            });
        }
        
        var formData = {
            destination: destination,
            check_in: check_in,
            check_out: check_out,
            occupancies: occupancies,
            action: 'digicells_search_hotels',
            nonce: digicells_ajax.nonce
        };
        
        // Show loader
        $('#digicells-search-loader').show();
        $('#digicells-search-results').html('');
        
        // Make AJAX request
        $.ajax({
            url: digicells_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                $('#digicells-search-loader').hide();
                
                if (response.success) {
                    $('#digicells-search-results').html(response.data.html);
                    
                    // Scroll to results
                    $('html, body').animate({
                        scrollTop: $('#digicells-search-results').offset().top - 30
                    }, 500);
                } else {
                    $('#digicells-search-results').html('<div class="digicells-no-results"><p>Error: ' + response.data + '</p></div>');
                }
            },
            error: function(xhr, status, error) {
                $('#digicells-search-loader').hide();
                $('#digicells-search-results').html('<div class="digicells-no-results"><p>Network error: ' + error + '</p></div>');
            }
        });
    });
    
    // Handle view details clicks
    $(document).on('click', '.digicells-view-details', function() {
        var hotelCode = $(this).data('hotel-code');
        alert('Hotel details for code: ' + hotelCode + '\n\nThis feature will show full hotel information in the next update.');
    });
    
    // Auto-uppercase destination code as user types
    $('#destination').on('input', function() {
        $(this).val($(this).val().toUpperCase());
    });
    
    // Add help text for destination codes
    $('#destination').attr('placeholder', 'PAR, NYC, LON, DXB, etc.');
    
    // When children count changes, show age input if needed
    $('#children').on('change', function() {
        var childCount = parseInt($(this).val());
        if (childCount > 0) {
            // You could add dynamic child age inputs here
            console.log('Please specify child ages in the next step');
        }
    });
});