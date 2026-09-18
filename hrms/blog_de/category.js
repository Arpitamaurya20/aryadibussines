// Blog Category Management JavaScript

// Add new category
function AddCategory() {
    resetCategoryForm();
    $('#categoryModalLabel').text('Add New Category');
    $('#category_form_action').val('add');
    $('#category_form_id').val('');
    $('#categoryModal').modal('show');
}

// Edit category
function EditCategory(categoryId) {
    $.ajax({
        url: '../blog/action/blog-action.php',
        type: 'POST',
        data: {
            action: 'get_category',
            category_id: categoryId
        },
        dataType: 'json',
        success: function(response) {
            if (!response.error && response.data) {
                let category = response.data;
                
                $('#category_name').val(category.category_name || '');
                $('#category_description').val(category.category_description || '');
                
                // Category image
                if (category.category_image) {
                    // Handle both local paths and full URLs
                    let imageUrl = category.category_image;
                    if (imageUrl.indexOf('http') !== 0) {
                        // For admin panel: if path doesn't start with 'admin/', add it
                        // Local paths stored as 'uploads/...' should become 'admin/uploads/...'
                        if (imageUrl.indexOf('admin/') !== 0) {
                            imageUrl = 'admin/' + imageUrl.replace(/^\.\.\//, '').replace(/^\.\//, '');
                        } else {
                            imageUrl = imageUrl.replace(/^\.\.\//, '').replace(/^\.\//, '');
                        }
                    }
                    $('#category_image').val(category.category_image);
                    $('#category_image_preview').html('<img src="' + imageUrl + '" class="category-image-preview" />');
                } else {
                    $('#category_image').val('');
                    $('#category_image_preview').html('');
                }
                
                // Clear file input
                $('#category_image_file').val('');
                
                // Set form action
                $('#category_form_action').val('edit');
                $('#category_form_id').val(category.ID);
                $('#categoryModalLabel').text('Edit Category');
                $('#categoryModal').modal('show');
            } else {
                Alert(response.message || 'Failed to load category');
            }
        },
        error: function() {
            Alert('Error loading category');
        }
    });
}

// Save category
function SaveCategory() {
    // Validate required fields
    if (!$('#category_name').val().trim()) {
        Alert('Category name is required');
        return;
    }
    
    // Prepare form data
    let formData = new FormData($('#categoryForm')[0]);
    formData.append('action', 'save_category');
    
    // Debug: Log file info
    let fileInput = document.getElementById('category_image_file');
    if (fileInput && fileInput.files && fileInput.files.length > 0) {
        console.log('File selected:', fileInput.files[0].name, 'Size:', fileInput.files[0].size);
    } else {
        console.log('No file selected');
    }
    
    // Show loading
    let saveBtn = $('#categoryModal').find('button[onclick="SaveCategory()"]');
    let originalText = saveBtn.html();
    saveBtn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i> Saving...');
    
    $.ajax({
        url: '../blog/action/blog-action.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            saveBtn.prop('disabled', false).html(originalText);
            
            if (!response.error) {
                let successMsg = response.message || 'Category saved successfully!';
                if (response.upload_error) {
                    successMsg += ' (Note: Image upload failed: ' + response.upload_error + ')';
                }
                Alert(successMsg);
                $('#categoryModal').modal('hide');
                // Reload DataTable instead of full page reload
                if (typeof reloadCategoryTable === 'function') {
                    reloadCategoryTable();
                } else {
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                }
            } else {
                let errorMsg = response.message || 'Failed to save category';
                if (response.upload_error) {
                    errorMsg += ' (Image upload error: ' + response.upload_error + ')';
                }
                Alert(errorMsg);
            }
        },
        error: function(xhr, status, error) {
            saveBtn.prop('disabled', false).html(originalText);
            console.error('Category save error:', xhr.responseText);
            let errorMsg = 'Error saving category';
            try {
                let errorResponse = JSON.parse(xhr.responseText);
                if (errorResponse.message) {
                    errorMsg = errorResponse.message;
                }
            } catch(e) {
                errorMsg = xhr.responseText || 'Error saving category';
            }
            Alert(errorMsg);
        }
    });
}

// Delete category
function DeleteCategory(categoryId) {
    if (confirm('Are you sure you want to delete this category? This action cannot be undone.')) {
        $.ajax({
            url: '../blog/action/blog-action.php',
            type: 'POST',
            data: {
                action: 'delete_category',
                category_id: categoryId
            },
            dataType: 'json',
            success: function(response) {
                if (!response.error) {
                    Alert(response.message || 'Category deleted successfully!');
                    // Reload DataTable instead of full page reload
                    if (typeof reloadCategoryTable === 'function') {
                        reloadCategoryTable();
                    } else {
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    }
                } else {
                    Alert(response.message || 'Failed to delete category');
                }
            },
            error: function() {
                Alert('Error deleting category');
            }
        });
    }
}

// Reset form
function resetCategoryForm() {
    $('#categoryForm')[0].reset();
    $('#category_image_preview').html('');
    $('#category_image').val('');
    // Reset file input
    $('#category_image_file').val('');
}

// Image preview handler
$(document).ready(function() {
    $('#category_image_file').on('change', function() {
        if (this.files && this.files[0]) {
            let reader = new FileReader();
            reader.onload = function(e) {
                $('#category_image_preview').html('<img src="' + e.target.result + '" class="category-image-preview" />');
                // Clear hidden field when new file is selected (will be set after upload)
                $('#category_image').val('');
            };
            reader.readAsDataURL(this.files[0]);
        } else {
            // If file input is cleared, show existing image if any
            let existingImage = $('#category_image').val();
            if (existingImage) {
                $('#category_image_preview').html('<img src="' + existingImage + '" class="category-image-preview" />');
            } else {
                $('#category_image_preview').html('');
            }
        }
    });
});

