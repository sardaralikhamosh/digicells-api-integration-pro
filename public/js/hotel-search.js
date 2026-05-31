jQuery(document).ready(function($) {
    $('#digicells-hotel-search-form').on('submit', function(e) {
        e.preventDefault();
        
        // Get form data
        var formData = {
            destination: $('#destination').val(),
            check_in: $('#check_in').val(),
            check_out: $('#check_out').val(),
            rooms: $('#rooms').val(),
            adults: $('#adults').val(),
            children: $('#children').val(),
            action: 'digicells_search_hotels',
            nonce: digicells_ajax.nonce
        };
        
        // Validate dates
        if (!formData.check_in || !formData.check_out) {
            alert('Please enter both check-in and check-out dates');
            return;
        }
        
        if (formData.check_in >= formData.check_out) {
            alert('Check-out date must be after check-in date');
            return;
        }
        
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
            error: function() {
                $('#digicells-search-loader').hide();
                $('#digicells-search-results').html('<div class="digicells-no-results"><p>Network error. Please try again.</p></div>');
            }
        });
    });
    
    // Handle view details clicks (delegated for dynamic content)
    $(document).on('click', '.digicells-view-details', function() {
        var hotelCode = $(this).data('hotel-code');
        alert('Hotel details for code: ' + hotelCode + '\n\nThis feature can be extended to show a modal with full hotel details.');
    });
    
    // Set default dates (today and tomorrow)
    var today = new Date();
    var tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    
    $('#check_in').val(today.toISOString().split('T')[0]);
    $('#check_out').val(tomorrow.toISOString().split('T')[0]);
});