// Workshop Category Management JavaScript

// Add new category
window.AddCategory = function() {
    resetCategoryForm();
    $('#categoryModalLabel').text('Add New Category');
    $('#category_form_action').val('add');
    $('#category_form_id').val('');
    $('#categoryModal').modal('show');
};

// Edit category
window.EditCategory = function(categoryId) {
    $.ajax({
        url: '../workshop/action/workshop-action.php',
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
                    let imageUrl = category.category_image;
                    if (imageUrl.indexOf('http') !== 0) {
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
                alert(response.message || 'Failed to load category');
            }
        },
        error: function() {
            alert('Error loading category');
        }
    });
};

// Save category
window.SaveCategory = function() {
    // Validate required fields
    if (!$('#category_name').val().trim()) {
        alert('Category name is required');
        return;
    }
    
    // Prepare form data
    let formData = new FormData($('#categoryForm')[0]);
    formData.append('action', 'save_category');
    
    // Show loading
    let saveBtn = $('#categoryModal').find('button[onclick="SaveCategory()"]');
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
                alert(response.message || 'Category saved successfully!');
                $('#categoryModal').modal('hide');
                if (typeof window.reloadCategoryTable === 'function') {
                    window.reloadCategoryTable();
                } else {
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                }
            } else {
                alert(response.message || 'Failed to save category');
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
            } catch (e) {
                errorMsg = xhr.responseText;
            }
            alert(errorMsg);
        }
    });
};

// Delete category
window.DeleteCategory = function(categoryId) {
    if (confirm('Are you sure you want to delete this category?')) {
        $.ajax({
            url: '../workshop/action/workshop-action.php',
            type: 'POST',
            data: {
                action: 'delete_category',
                category_id: categoryId
            },
            dataType: 'json',
            success: function(response) {
                if (!response.error) {
                    alert(response.message || 'Category deleted successfully');
                    if (typeof window.reloadCategoryTable === 'function') {
                        window.reloadCategoryTable();
                    } else {
                        location.reload();
                    }
                } else {
                    alert(response.message || 'Failed to delete category');
                }
            },
            error: function() {
                alert('Error deleting category');
            }
        });
    }
};

// Reset category form
window.resetCategoryForm = function() {
    $('#categoryForm')[0].reset();
    $('#category_image_preview').html('');
    $('#category_form_action').val('add');
    $('#category_form_id').val('');
};

// Image preview handler
$(document).ready(function() {
    $('#category_image_file').on('change', function(e) {
        let file = e.target.files[0];
        if (file) {
            let reader = new FileReader();
            reader.onload = function(e) {
                $('#category_image_preview').html('<img src="' + e.target.result + '" class="category-image-preview" />');
            };
            reader.readAsDataURL(file);
        }
    });
});

