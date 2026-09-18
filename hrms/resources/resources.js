// Resource Library Management JavaScript

// Folder Management
window.AddFolder = function() {
    resetFolderForm();
    $('#folderModalLabel').text('Add New Folder');
    $('#folder_form_action').val('add');
    $('#folder_form_id').val('');
    $('#folderModal').modal('show');
};

window.EditFolder = function(folderId) {
    $.ajax({
        url: '../resources/action/resource-action.php',
        type: 'POST',
        data: {
            action: 'get_folder',
            folder_id: folderId
        },
        dataType: 'json',
        success: function(response) {
            if (!response.error && response.data) {
                let folder = response.data;
                
                $('#folder_name').val(folder.folder_name || '');
                $('#folder_description').val(folder.description || '');
                $('#folder_visibility').val(folder.visibility || 'private');
                $('#folder_parent_id').val(folder.parent_id || '');
                
                $('#folder_form_action').val('edit');
                $('#folder_form_id').val(folder.ID);
                $('#folderModalLabel').text('Edit Folder');
                $('#folderModal').modal('show');
            } else {
                alert(response.message || 'Failed to load folder');
            }
        },
        error: function() {
            alert('Error loading folder');
        }
    });
};

window.SaveFolder = function() {
    if (!$('#folder_name').val().trim()) {
        alert('Folder name is required');
        return;
    }
    
    let formData = {
        action: 'save_folder',
        folder_name: $('#folder_name').val(),
        description: $('#folder_description').val(),
        visibility: $('#folder_visibility').val(),
        parent_id: $('#folder_parent_id').val() || null,
        folder_form_action: $('#folder_form_action').val(),
        folder_form_id: $('#folder_form_id').val()
    };
    
    let saveBtn = $('#folderModal').find('button[onclick="SaveFolder()"]');
    let originalText = saveBtn.html();
    saveBtn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i> Saving...');
    
    $.ajax({
        url: '../resources/action/resource-action.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            saveBtn.prop('disabled', false).html(originalText);
            
            if (!response.error) {
                alert(response.message || 'Folder saved successfully!');
                $('#folderModal').modal('hide');
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                alert(response.message || 'Failed to save folder');
            }
        },
        error: function(xhr, status, error) {
            saveBtn.prop('disabled', false).html(originalText);
            console.error('Folder save error:', xhr.responseText);
            alert('Error saving folder');
        }
    });
};

window.DeleteFolder = function(folderId) {
    if (confirm('Are you sure you want to delete this folder? Files inside will not be deleted but will be orphaned.')) {
        $.ajax({
            url: '../resources/action/resource-action.php',
            type: 'POST',
            data: {
                action: 'delete_folder',
                folder_id: folderId
            },
            dataType: 'json',
            success: function(response) {
                if (!response.error) {
                    alert(response.message || 'Folder deleted successfully');
                    location.reload();
                } else {
                    alert(response.message || 'Failed to delete folder');
                }
            },
            error: function() {
                alert('Error deleting folder');
            }
        });
    }
};

window.resetFolderForm = function() {
    $('#folderForm')[0].reset();
    $('#folder_form_action').val('add');
    $('#folder_form_id').val('');
};

// File Management
window.UploadFile = function() {
    if (!currentFolderId) {
        alert('Please select a folder first');
        return;
    }
    
    resetFileUploadForm();
    $('#upload_folder_id').val(currentFolderId);
    $('#fileUploadModalLabel').text('Upload File to: ' + currentFolderName);
    $('#fileUploadModal').modal('show');
};

window.SaveFileUpload = function() {
    if (!$('#upload_file')[0].files.length) {
        alert('Please select a file to upload');
        return;
    }
    
    let formData = new FormData($('#fileUploadForm')[0]);
    formData.append('action', 'upload_file');
    
    let saveBtn = $('#fileUploadModal').find('button[onclick="SaveFileUpload()"]');
    let originalText = saveBtn.html();
    saveBtn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i> Uploading...');
    
    $.ajax({
        url: '../resources/action/resource-action.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            saveBtn.prop('disabled', false).html(originalText);
            
            if (!response.error) {
                alert(response.message || 'File uploaded successfully!');
                $('#fileUploadModal').modal('hide');
                loadFiles();
            } else {
                alert(response.message || 'Failed to upload file');
            }
        },
        error: function(xhr, status, error) {
            saveBtn.prop('disabled', false).html(originalText);
            console.error('File upload error:', xhr.responseText);
            alert('Error uploading file');
        }
    });
};

window.ViewFile = function(fileId) {
    // Open file in new tab
    window.open('../resources-frontend/file-view.php?id=' + fileId, '_blank');
};

window.EditFile = function(fileId) {
    $.ajax({
        url: '../resources/action/resource-action.php',
        type: 'POST',
        data: {
            action: 'get_file',
            file_id: fileId
        },
        dataType: 'json',
        success: function(response) {
            if (!response.error && response.data) {
                let file = response.data;
                
                // Show edit modal (to be implemented)
                alert('Edit file: ' + file.title);
            } else {
                alert(response.message || 'Failed to load file');
            }
        },
        error: function() {
            alert('Error loading file');
        }
    });
};

window.DeleteFile = function(fileId) {
    if (confirm('Are you sure you want to delete this file?')) {
        $.ajax({
            url: '../resources/action/resource-action.php',
            type: 'POST',
            data: {
                action: 'delete_file',
                file_id: fileId
            },
            dataType: 'json',
            success: function(response) {
                if (!response.error) {
                    alert(response.message || 'File deleted successfully');
                    loadFiles();
                } else {
                    alert(response.message || 'Failed to delete file');
                }
            },
            error: function() {
                alert('Error deleting file');
            }
        });
    }
};

window.resetFileUploadForm = function() {
    $('#fileUploadForm')[0].reset();
    $('#upload_folder_id').val('');
};

