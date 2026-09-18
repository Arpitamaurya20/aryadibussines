// Workshop Management JavaScript

// Add new workshop
window.AddWorkshop = function() {
    resetWorkshopForm();
    $('#workshopModalLabel').text('Add New Workshop');
    $('#workshop_form_action').val('add');
    $('#workshop_form_id').val('');
    $('#workshopModal').modal('show');
};

// Edit workshop
window.EditWorkshop = function(workshopId) {
    $.ajax({
        url: '../workshop/action/workshop-action.php',
        type: 'POST',
        data: {
            action: 'get_workshop',
            workshop_id: workshopId
        },
        dataType: 'json',
        success: function(response) {
            if (!response.error && response.data) {
                let workshop = response.data;
                
                // Basic fields
                $('#workshop_title').val(workshop.workshop_title || '');
                $('#slug').val(workshop.slug || '');
                $('#short_description').val(workshop.short_description || '');
                
                // Set Quill editor content
                setTimeout(function() {
                    if (window.quillEditor && workshop.detailed_description) {
                        window.quillEditor.root.innerHTML = workshop.detailed_description;
                    }
                }, 300);
                
                $('#category_id').val(workshop.category_id || '');
                $('#tags').val(workshop.tags || '');
                
                // Session Type & Mode
                $('#workshop_mode').val(workshop.workshop_mode || 'online');
                $('#session_type').val(workshop.session_type || 'single');
                $('#language').val(workshop.language || 'English');
                $('#level').val(workshop.level || 'beginner');
                
                // Online fields
                $('#meeting_platform').val(workshop.meeting_platform || '');
                $('#meeting_link').val(workshop.meeting_link || '');
                $('#meeting_id').val(workshop.meeting_id || '');
                $('#meeting_passcode').val(workshop.meeting_passcode || '');
                $('#auto_send_meeting_link').prop('checked', workshop.auto_send_meeting_link == 1);
                
                // Offline fields
                $('#venue_name').val(workshop.venue_name || '');
                $('#venue_address').val(workshop.venue_address || '');
                $('#city').val(workshop.city || '');
                $('#google_map_link').val(workshop.google_map_link || '');
                
                // Schedule
                $('#session_date').val(workshop.session_date || '');
                $('#start_time').val(workshop.start_time || '');
                $('#end_time').val(workshop.end_time || '');
                $('#time_zone').val(workshop.time_zone || 'IST');
                $('#is_recurring').prop('checked', workshop.is_recurring == 1);
                $('#recurring_pattern').val(workshop.recurring_pattern || '');
                $('#recurring_end_date').val(workshop.recurring_end_date || '');
                
                // Capacity
                $('#total_seats').val(workshop.total_seats || '');
                $('#show_seat_availability').prop('checked', workshop.show_seat_availability == 1);
                $('#auto_close_registration').prop('checked', workshop.auto_close_registration == 1);
                if (workshop.registration_deadline) {
                    let deadline = new Date(workshop.registration_deadline);
                    $('#registration_deadline').val(deadline.toISOString().slice(0, 16));
                }
                $('#allow_waiting_list').prop('checked', workshop.allow_waiting_list == 1);
                $('#waiting_list_capacity').val(workshop.waiting_list_capacity || '');
                
                // Pricing
                $('#pricing_type').val(workshop.pricing_type || 'free');
                $('#price').val(workshop.price || '');
                $('#discount_type').val(workshop.discount_type || '');
                $('#discount_value').val(workshop.discount_value || '');
                $('#discount_start_date').val(workshop.discount_start_date || '');
                $('#discount_end_date').val(workshop.discount_end_date || '');
                $('#coupon_code_support').prop('checked', workshop.coupon_code_support == 1);
                $('#payment_gateway').val(workshop.payment_gateway || '');
                
                // Certification
                $('#provide_certificate').prop('checked', workshop.provide_certificate == 1);
                $('#auto_generate_certificate').prop('checked', workshop.auto_generate_certificate == 1);
                
                // Notifications
                $('#email_notification').prop('checked', workshop.email_notification == 1);
                $('#sms_notification').prop('checked', workshop.sms_notification == 1);
                $('#whatsapp_notification').prop('checked', workshop.whatsapp_notification == 1);
                $('#reminder_before_session').val(workshop.reminder_before_session || '');
                $('#reminder_unit').val(workshop.reminder_unit || 'hours');
                $('#post_session_feedback').prop('checked', workshop.post_session_feedback == 1);
                
                // SEO
                $('#meta_title').val(workshop.meta_title || '');
                $('#meta_description').val(workshop.meta_description || '');
                $('#meta_keywords').val(workshop.meta_keywords || '');
                
                // Status
                $('#status').val(workshop.status || 'draft');
                $('#show_on_homepage').prop('checked', workshop.show_on_homepage == 1);
                $('#featured').prop('checked', workshop.featured == 1);
                
                // Images
                if (workshop.thumbnail_image) {
                    $('#thumbnail_image').val(workshop.thumbnail_image);
                    $('#thumbnail_image_preview').html('<img src="' + window.getImageDisplayUrl(workshop.thumbnail_image) + '" class="workshop-image-preview" />');
                }
                if (workshop.og_image) {
                    $('#og_image').val(workshop.og_image);
                    $('#og_image_preview').html('<img src="' + window.getImageDisplayUrl(workshop.og_image) + '" class="workshop-image-preview" />');
                }
                
                // Gallery images
                if (workshop.gallery_images) {
                    try {
                        let galleryImages = JSON.parse(workshop.gallery_images);
                        let galleryPreview = $('#gallery_preview');
                        galleryPreview.html('');
                        galleryImages.forEach(function(imgUrl) {
                            let displayUrl = window.getImageDisplayUrl(imgUrl);
                            let img = $('<img>').attr('src', displayUrl)
                                .addClass('workshop-image-preview')
                                .css({'margin': '5px', 'display': 'inline-block'});
                            galleryPreview.append(img);
                        });
                        $('#gallery_images_existing').val(workshop.gallery_images);
                    } catch (e) {
                        console.error('Error parsing gallery images:', e);
                    }
                }
                
                // Set form action
                $('#workshop_form_action').val('edit');
                $('#workshop_form_id').val(workshop.ID);
                $('#workshopModalLabel').text('Edit Workshop');
                $('#workshopModal').modal('show');
            } else {
                alert(response.message || 'Failed to load workshop');
            }
        },
        error: function() {
            alert('Error loading workshop');
        }
    });
}

// Save workshop
window.SaveWorkshop = function() {
    // Validate required fields
    if (!$('#workshop_title').val().trim()) {
        alert('Workshop title is required');
        return;
    }
    
    // Get Quill editor content
    let detailedDescription = '';
    if (window.quillEditor) {
        detailedDescription = window.quillEditor.root.innerHTML;
    }
    
    // Prepare form data
    let formData = new FormData($('#workshopForm')[0]);
    formData.append('action', 'save_workshop');
    formData.append('detailed_description', detailedDescription);
    
    // Show loading
    let saveBtn = $('#workshopModal').find('button[onclick="SaveWorkshop()"]');
    let originalText = saveBtn.html();
    saveBtn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i> Saving...');
    
    $.ajax({
        url: '../workshop/action/workshop-action.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            saveBtn.prop('disabled', false).html(originalText);
            
                if (!response.error) {
                alert(response.message || 'Workshop saved successfully!');
                $('#workshopModal').modal('hide');
                if (typeof window.reloadWorkshopTable === 'function') {
                    window.reloadWorkshopTable();
                } else {
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                }
            } else {
                alert(response.message || 'Failed to save workshop');
            }
        },
        error: function(xhr, status, error) {
            saveBtn.prop('disabled', false).html(originalText);
            console.error('Workshop save error:', xhr.responseText);
            let errorMsg = 'Error saving workshop';
            try {
                let errorResponse = JSON.parse(xhr.responseText);
                if (errorResponse.message) {
                    errorMsg = errorResponse.message;
                }
            } catch (e) {
                errorMsg = xhr.responseText;
            }
            alert(errorMsg);
        }
    });
}

// Reset workshop form
window.resetWorkshopForm = function() {
    $('#workshopForm')[0].reset();
    if (window.quillEditor) {
        window.quillEditor.root.innerHTML = '';
    }
    $('#thumbnail_image_preview').html('');
    $('#og_image_preview').html('');
    $('#gallery_preview').html('');
    $('#workshop_form_action').val('add');
    $('#workshop_form_id').val('');
}


// Image preview handlers
$(document).ready(function() {
    // Thumbnail image preview
    $('#thumbnail_image_file').on('change', function(e) {
        let file = e.target.files[0];
        if (file) {
            let reader = new FileReader();
            reader.onload = function(e) {
                $('#thumbnail_image_preview').html('<img src="' + e.target.result + '" class="workshop-image-preview" />');
            };
            reader.readAsDataURL(file);
        }
    });
    
    // OG image preview
    $('#og_image_file').on('change', function(e) {
        let file = e.target.files[0];
        if (file) {
            let reader = new FileReader();
            reader.onload = function(e) {
                $('#og_image_preview').html('<img src="' + e.target.result + '" class="workshop-image-preview" />');
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Gallery images preview
    $('#gallery_images').on('change', function(e) {
        let files = e.target.files;
        let preview = $('#gallery_preview');
        preview.html('');
        for (let i = 0; i < files.length; i++) {
            let reader = new FileReader();
            reader.onload = function(e) {
                let img = $('<img>').attr('src', e.target.result)
                    .addClass('workshop-image-preview')
                    .css({'margin': '5px', 'display': 'inline-block'});
                preview.append(img);
            };
            reader.readAsDataURL(files[i]);
        }
    });
    
    // Toggle online/offline fields based on mode
    $('#workshop_mode').on('change', function() {
        let mode = $(this).val();
        if (mode === 'online') {
            $('.offline-fields').hide();
            $('.online-fields').show();
        } else if (mode === 'offline') {
            $('.online-fields').hide();
            $('.offline-fields').show();
        } else {
            $('.online-fields').show();
            $('.offline-fields').show();
        }
    });
    
    // Initialize mode visibility
    $('#workshop_mode').trigger('change');
    
    // Toggle pricing fields
    $('#pricing_type').on('change', function() {
        if ($(this).val() === 'free') {
            $('.pricing-fields').hide();
        } else {
            $('.pricing-fields').show();
        }
    });
    $('#pricing_type').trigger('change');
});

// Helper function to get image display URL
window.getImageDisplayUrl = function(imagePath) {
    if (!imagePath) return '';
    if (imagePath.indexOf('http') === 0) {
        return imagePath; // Full URL
    }
    // For admin panel: if path doesn't start with 'admin/', add it
    if (imagePath.indexOf('admin/') !== 0) {
        return 'admin/' + imagePath.replace(/^\.\.\//, '').replace(/^\.\//, '');
    }
    return imagePath.replace(/^\.\.\//, '').replace(/^\.\//, '');
};

