jQuery(document).ready(function($) {
    $('#simple-submission-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var responseDiv = $('#form-response');
        
        // Clear previous messages
        responseDiv.removeClass('error success').text('');
        
        // Disable submit button to prevent multiple submissions
        form.find('button[type="submit"]').prop('disabled', true);
        
        // Show loading indicator
        responseDiv.text('Submitting...');
        
        // Prepare form data
        var formData = form.serialize();
        
        // AJAX submission
        $.ajax({
            url: simpleFormAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'simple_form_submit',
                ...formData
            },
            success: function(response) {
                if (response.success) {
                    responseDiv.addClass('success').text(response.data);
                    form[0].reset(); // Clear form fields
                } else {
                    responseDiv.addClass('error').text(response.data);
                }
            },
            error: function() {
                responseDiv.addClass('error').text('An error occurred. Please try again.');
            },
            complete: function() {
                // Re-enable submit button
                form.find('button[type="submit"]').prop('disabled', false);
            }
        });
    });
});