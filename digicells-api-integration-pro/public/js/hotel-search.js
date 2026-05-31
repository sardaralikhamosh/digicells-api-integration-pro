jQuery(document).ready(function($) {
    
    console.log('Hotel search script loaded');
    
    // Format date function
    function formatDate(date) {
        var year = date.getFullYear();
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var day = String(date.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }
    
    // Set default dates
    var today = new Date();
    var tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    var dayAfter = new Date(today);
    dayAfter.setDate(dayAfter.getDate() + 2);
    
    $('#check_in').val(formatDate(tomorrow));
    $('#check_out').val(formatDate(dayAfter));
    $('#destination').val('PAR');
    
    // Form submission
    $('#digicells-hotel-search-form').on('submit', function(e) {
        e.preventDefault();
        
        // Get values directly
        var destination = $('#destination').val().trim().toUpperCase();
        var check_in = $('#check_in').val();
        var check_out = $('#check_out').val();
        var rooms = $('#rooms').val();
        var adults = $('#adults').val();
        var children = $('#children').val();
        
        console.log('Searching for:', destination, check_in, 'to', check_out);
        
        // Validate
        if (!destination || destination.length < 1 || destination.length > 3) {
            alert('Please enter city code (PAR, NYC, LON, DXB)');
            return;
        }
        
        if (!check_in || !check_out) {
            alert('Please select dates');
            return;
        }
        
        // Build data WITHOUT underscores
        var postData = new FormData();
        postData.append('action', 'digicells_search_hotels');
        postData.append('nonce', digicells_ajax.nonce);
        postData.append('destination', destination);
        postData.append('check_in', check_in);
        postData.append('check_out', check_out);
        postData.append('rooms', rooms);
        postData.append('adults', adults);
        postData.append('children', children);
        
        // Show loader
        $('#digicells-search-loader').show();
        $('#digicells-search-results').html('').hide().fadeIn();
        
        // Send request
        $.ajax({
            url: digicells_ajax.ajax_url,
            type: 'POST',
            data: postData,
            processData: false,
            contentType: false,
            dataType: 'json',
            timeout: 30000,
            success: function(response) {
                $('#digicells-search-loader').hide();
                console.log('Response:', response);
                
                if (response.success) {
                    $('#digicells-search-results').html(response.data.html);
                } else {
                    $('#digicells-search-results').html(
                        '<div style="background:#fff3f3;border:1px solid #ffcdd2;border-radius:8px;padding:20px;margin:20px 0;text-align:center;">' +
                        '<p style="color:#d32f2f;margin:0;">❌ ' + response.data + '</p>' +
                        '</div>'
                    );
                }
            },
            error: function(xhr, status, error) {
                $('#digicells-search-loader').hide();
                console.error('AJAX Error:', error);
                $('#digicells-search-results').html(
                    '<div style="background:#fff3f3;border:1px solid #ffcdd2;border-radius:8px;padding:20px;margin:20px 0;text-align:center;">' +
                    '<p style="color:#d32f2f;margin:0;">❌ Connection error. Please refresh and try again.</p>' +
                    '</div>'
                );
            }
        });
        
        return false;
    });
    
    // Uppercase destination
    $('#destination').on('input', function() {
        $(this).val($(this).val().toUpperCase().substring(0, 3));
    });
});