// Workshop Registrations Management JavaScript

// View registration details
window.ViewRegistration = function(registrationId) {
    $.ajax({
        url: '../workshop/action/workshop-action.php',
        type: 'POST',
        data: {
            action: 'get_registration',
            registration_id: registrationId
        },
        dataType: 'json',
        success: function(response) {
            if (!response.error && response.data) {
                let reg = response.data;
                
                // Display details
                $('#detail_participant_name').text(reg.participant_name || '');
                $('#detail_participant_email').text(reg.participant_email || '');
                $('#detail_participant_mobile').text(reg.participant_mobile || '');
                $('#detail_participant_city').text(reg.participant_city || '');
                $('#detail_workshop_title').text(reg.workshop_title || '');
                $('#detail_registration_date').text(reg.registration_time ? new Date(reg.registration_time).toLocaleString() : '');
                $('#detail_payment_status').html(getPaymentStatusBadge(reg.payment_status));
                $('#detail_payment_amount').text(reg.final_amount ? '₹' + parseFloat(reg.final_amount).toFixed(2) : '₹0.00');
                $('#detail_transaction_id').text(reg.transaction_id || 'N/A');
                $('#detail_payment_date').text(reg.payment_date ? new Date(reg.payment_date).toLocaleString() : 'N/A');
                
                // Set form values
                $('#registration_id').val(reg.ID);
                $('#payment_status').val(reg.payment_status || 'pending');
                $('#transaction_id').val(reg.transaction_id || '');
                $('#payment_amount').val(reg.final_amount || reg.payment_amount || '');
                if (reg.payment_date) {
                    let paymentDate = new Date(reg.payment_date);
                    $('#payment_date').val(paymentDate.toISOString().slice(0, 16));
                }
                $('#attended').prop('checked', reg.attended == 1);
                $('#feedback_rating').val(reg.feedback_rating || '');
                $('#feedback_comment').val(reg.feedback_comment || '');
                $('#certificate_issued').prop('checked', reg.certificate_issued == 1);
                
                $('#registrationModalLabel').text('Registration Details - ' + reg.participant_name);
                $('#registrationModal').modal('show');
            } else {
                alert(response.message || 'Failed to load registration');
            }
        },
        error: function() {
            alert('Error loading registration');
        }
    });
};

// Update registration
window.UpdateRegistration = function() {
    let registrationId = $('#registration_id').val();
    if (!registrationId) {
        alert('Registration ID is missing');
        return;
    }
    
    let formData = {
        action: 'update_registration',
        registration_id: registrationId,
        payment_status: $('#payment_status').val(),
        transaction_id: $('#transaction_id').val(),
        payment_amount: $('#payment_amount').val() || 0,
        payment_date: $('#payment_date').val() ? new Date($('#payment_date').val()).toISOString().slice(0, 19).replace('T', ' ') : null,
        attended: $('#attended').is(':checked') ? 1 : 0,
        feedback_rating: $('#feedback_rating').val() || null,
        feedback_comment: $('#feedback_comment').val() || '',
        certificate_issued: $('#certificate_issued').is(':checked') ? 1 : 0
    };
    
    // Show loading
    let saveBtn = $('#registrationModal').find('button[onclick="UpdateRegistration()"]');
    let originalText = saveBtn.html();
    saveBtn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i> Updating...');
    
    $.ajax({
        url: '../workshop/action/workshop-action.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            saveBtn.prop('disabled', false).html(originalText);
            
            if (!response.error) {
                alert(response.message || 'Registration updated successfully!');
                $('#registrationModal').modal('hide');
                if (typeof window.reloadRegistrationTable === 'function') {
                    window.reloadRegistrationTable();
                } else {
                    location.reload();
                }
            } else {
                alert(response.message || 'Failed to update registration');
            }
        },
        error: function(xhr, status, error) {
            saveBtn.prop('disabled', false).html(originalText);
            console.error('Registration update error:', xhr.responseText);
            alert('Error updating registration');
        }
    });
};

// Delete registration
window.DeleteRegistration = function(registrationId) {
    if (confirm('Are you sure you want to delete this registration? This action cannot be undone.')) {
        $.ajax({
            url: '../workshop/action/workshop-action.php',
            type: 'POST',
            data: {
                action: 'delete_registration',
                registration_id: registrationId
            },
            dataType: 'json',
            success: function(response) {
                if (!response.error) {
                    alert(response.message || 'Registration deleted successfully');
                    if (typeof window.reloadRegistrationTable === 'function') {
                        window.reloadRegistrationTable();
                    } else {
                        location.reload();
                    }
                } else {
                    alert(response.message || 'Failed to delete registration');
                }
            },
            error: function() {
                alert('Error deleting registration');
            }
        });
    }
};

// Helper function to get payment status badge
function getPaymentStatusBadge(status) {
    let badges = {
        'pending': '<span class="badge bg-warning">Pending</span>',
        'paid': '<span class="badge bg-success">Paid</span>',
        'failed': '<span class="badge bg-danger">Failed</span>',
        'refunded': '<span class="badge bg-secondary">Refunded</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">' + status + '</span>';
}

