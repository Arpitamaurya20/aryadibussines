// Blog Management JavaScript

let tagsArray = [];

// Initialize on page load
$(document).ready(function() {
    // Auto-generate slug from title
    $('#title').on('blur', function() {
        if ($('#slug').val() == '' || $('#blog_form_action').val() == 'add') {
            generateSlug();
        }
    });
    
    // Tag input handler
    $('#tagInput').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            addTag($(this).val().trim());
            $(this).val('');
        }
    });
    
    // Image preview handlers
    $('#featured_image_file').on('change', function() {
        previewImage(this, 'featured_image_preview', 'featured_image');
    });
    
    $('#author_image_file').on('change', function() {
        previewImage(this, 'author_image_preview', 'author_image');
    });
    
    $('#og_image_file').on('change', function() {
        previewImage(this, 'og_image_preview', 'og_image');
    });
    
    $('#twitter_card_image_file').on('change', function() {
        previewImage(this, 'twitter_card_image_preview', 'twitter_card_image');
    });
    
    // Gallery images preview
    $('#gallery_images').on('change', function() {
        previewGalleryImages(this);
    });
});

// Generate slug from title
function generateSlug() {
    let title = $('#title').val();
    if (title) {
        let slug = title.toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
        $('#slug').val(slug);
    }
}

// Add tag
function addTag(tag) {
    if (tag && !tagsArray.includes(tag)) {
        tagsArray.push(tag);
        updateTagsDisplay();
        updateTagsInput();
    }
}

// Remove tag
function removeTag(tag) {
    tagsArray = tagsArray.filter(t => t !== tag);
    updateTagsDisplay();
    updateTagsInput();
}

// Update tags display
function updateTagsDisplay() {
    let container = $('#tagsContainer');
    let input = $('#tagInput');
    
    // Remove all tag items
    container.find('.tag-item').remove();
    
    // Add tag items
    tagsArray.forEach(function(tag) {
        let tagItem = $('<div>').addClass('tag-item').html(
            '<span>' + tag + '</span>' +
            '<span class="remove-tag" onclick="removeTag(\'' + tag + '\')">×</span>'
        );
        container.prepend(tagItem);
    });
    
    // Ensure input is at the end
    if (!container.find('#tagInput').length) {
        container.append(input);
    }
}

// Update tags hidden input
function updateTagsInput() {
    $('#tags').val(tagsArray.join(', '));
}

// Preview image
function previewImage(input, previewId, hiddenInputId) {
    if (input.files && input.files[0]) {
        let reader = new FileReader();
        reader.onload = function(e) {
            let preview = $('#' + previewId);
            preview.html('<img src="' + e.target.result + '" class="blog-image-preview" />');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Preview gallery images
function previewGalleryImages(input) {
    let preview = $('#gallery_preview');
    preview.html('');
    
    if (input.files && input.files.length > 0) {
        Array.from(input.files).forEach(function(file) {
            let reader = new FileReader();
            reader.onload = function(e) {
                let img = $('<img>').attr('src', e.target.result)
                    .addClass('blog-image-preview')
                    .css({'margin': '5px', 'display': 'inline-block'});
                preview.append(img);
            };
            reader.readAsDataURL(file);
        });
    }
}

// Add new blog
function AddBlog() {
    resetForm();
    $('#blogModalLabel').text('Add New Blog Post');
    $('#blog_form_action').val('add');
    $('#blog_form_id').val('');
    $('#blogModal').modal('show');
}

// Edit blog
function EditBlog(blogId) {
    
    $.ajax({
        url: '../blog/action/blog-action.php',
        type: 'POST',
        data: {
            action: 'get_blog',
            blog_id: blogId
        },
        dataType: 'json',
        success: function(response) {
            if (!response.error && response.data) {
                let blog = response.data;
                
                // Basic fields
                $('#title').val(blog.title || '');
                $('#slug').val(blog.slug || '');
                $('#short_description').val(blog.short_description || '');
                $('#category_id').val(blog.category_id || '');
                $('#author_name').val(blog.author_name || '');
                $('#author_bio').val(blog.author_bio || '');
                $('#youtube_video_url').val(blog.youtube_video_url || '');
                $('#embedded_video').val(blog.embedded_video || '');
                
                // Tags
                tagsArray = blog.tags ? blog.tags.split(',').map(t => t.trim()).filter(t => t) : [];
                updateTagsDisplay();
                updateTagsInput();
                
                // Status and settings
                $('#status').val(blog.status || 'draft');
                if (blog.publish_date) {
                    let publishDate = new Date(blog.publish_date);
                    $('#publish_date').val(publishDate.toISOString().slice(0, 16));
                }
                $('#pin_to_top').prop('checked', blog.pin_to_top == 1);
                $('#allow_comments').prop('checked', blog.allow_comments == 1);
                $('#featured').prop('checked', blog.featured == 1);
                $('#trending').prop('checked', blog.trending == 1);
                
                // SEO fields
                $('#meta_title').val(blog.meta_title || '');
                $('#meta_description').val(blog.meta_description || '');
                $('#meta_keywords').val(blog.meta_keywords || '');
                $('#focus_keyword').val(blog.focus_keyword || '');
                $('#canonical_url').val(blog.canonical_url || '');
                $('#redirect_url').val(blog.redirect_url || '');
                
                // Social media fields
                $('#og_title').val(blog.og_title || '');
                $('#og_description').val(blog.og_description || '');
                $('#twitter_card_title').val(blog.twitter_card_title || '');
                $('#twitter_card_description').val(blog.twitter_card_description || '');
                
                // Images - Handle both local paths and full URLs
                function getImageDisplayUrl(imagePath) {
                    if (!imagePath) return '';
                    if (imagePath.indexOf('http') === 0) {
                        return imagePath; // Full URL
                    }
                    // For admin panel: if path doesn't start with 'admin/', add it
                    // Local paths stored as 'uploads/...' should become 'admin/uploads/...'
                    if (imagePath.indexOf('admin/') !== 0) {
                        return 'admin/' + imagePath.replace(/^\.\.\//, '').replace(/^\.\//, '');
                    }
                    return imagePath.replace(/^\.\.\//, '').replace(/^\.\//, '');
                }
                
                if (blog.featured_image) {
                    $('#featured_image').val(blog.featured_image);
                    $('#featured_image_preview').html('<img src="' + getImageDisplayUrl(blog.featured_image) + '" class="blog-image-preview" />');
                }
                if (blog.author_image) {
                    $('#author_image').val(blog.author_image);
                    $('#author_image_preview').html('<img src="' + getImageDisplayUrl(blog.author_image) + '" class="blog-image-preview" />');
                }
                if (blog.og_image) {
                    $('#og_image').val(blog.og_image);
                    $('#og_image_preview').html('<img src="' + getImageDisplayUrl(blog.og_image) + '" class="blog-image-preview" />');
                }
                if (blog.twitter_card_image) {
                    $('#twitter_card_image').val(blog.twitter_card_image);
                    $('#twitter_card_image_preview').html('<img src="' + getImageDisplayUrl(blog.twitter_card_image) + '" class="blog-image-preview" />');
                }
                
                // Gallery images
                if (blog.gallery_images) {
                    try {
                        let galleryImages = JSON.parse(blog.gallery_images);
                        let galleryPreview = $('#gallery_preview');
                        galleryPreview.html('');
                        galleryImages.forEach(function(imgUrl) {
                            let displayUrl = getImageDisplayUrl(imgUrl);
                            let img = $('<img>').attr('src', displayUrl)
                                .addClass('blog-image-preview')
                                .css({'margin': '5px', 'display': 'inline-block'});
                            galleryPreview.append(img);
                        });
                    } catch (e) {
                        console.error('Error parsing gallery images:', e);
                    }
                }
                
                // Store content for later use
                let blogContent = blog.full_content || '';
                $('#full_content').val(blogContent);
                
                // Set form action
                $('#blog_form_action').val('edit');
                $('#blog_form_id').val(blog.ID);
                $('#blogModalLabel').text('Edit Blog Post');
                
                // Show modal first
                $('#blogModal').modal('show');
                
                // Function to set editor content - will be called after editor initializes
                window.setBlogEditorContent = function() {
                    if (window.quillEditor && blogContent) {
                        try {
                            window.quillEditor.root.innerHTML = blogContent;
                            document.getElementById('full_content').value = blogContent;
                            console.log('Blog content loaded into editor successfully');
                        } catch(e) {
                            console.error('Error setting editor content:', e);
                        }
                    }
                };
                
                // Try to set content after modal is shown and editor is initialized
                $('#blogModal').one('shown.bs.modal', function() {
                    // Wait for editor to initialize, then set content
                    let attempts = 0;
                    let checkEditor = setInterval(function() {
                        attempts++;
                        if (window.quillEditor) {
                            clearInterval(checkEditor);
                            setTimeout(function() {
                                if (window.setBlogEditorContent) {
                                    window.setBlogEditorContent();
                                }
                            }, 100);
                        } else if (attempts > 20) {
                            // Stop trying after 2 seconds
                            clearInterval(checkEditor);
                            console.warn('Editor not initialized after timeout');
                        }
                    }, 100);
                });
            } else {
                Alert(response.message || 'Failed to load blog post');
            }
        },
        error: function() {
            Alert('Error loading blog post');
        }
    });
}

// Save blog
function SaveBlog() {
    // Validate required fields
    if (!$('#title').val().trim()) {
        Alert('Title is required');
        return;
    }
    
    // Get Quill editor content
    let fullContent = '';
    if (typeof window.quillEditor !== 'undefined' && window.quillEditor) {
        fullContent = window.quillEditor.root.innerHTML;
    } else {
        fullContent = $('#full_content').val();
    }
    
    // Update hidden textarea
    $('#full_content').val(fullContent);
    
    if (!fullContent.trim()) {
        Alert('Full content is required');
        return;
    }
    
    // Prepare form data
    let formData = new FormData($('#blogForm')[0]);
    formData.append('action', 'save_blog');
    formData.append('full_content', fullContent);
    
    // Show loading
    let saveBtn = $('#blogModal').find('button[onclick="SaveBlog()"]');
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
                Alert(response.message || 'Blog post saved successfully!');
                $('#blogModal').modal('hide');
                // Reload DataTable instead of full page reload
                if (typeof reloadBlogTable === 'function') {
                    reloadBlogTable();
                } else {
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                }
            } else {
                Alert(response.message || 'Failed to save blog post');
            }
        },
        error: function() {
            saveBtn.prop('disabled', false).html(originalText);
            Alert('Error saving blog post');
        }
    });
}

// Delete blog
function DeleteBlog(blogId) {
    if (confirm('Are you sure you want to delete this blog post?')) {
        $.ajax({
            url: '../blog/action/blog-action.php',
            type: 'POST',
            data: {
                action: 'delete_blog',
                blog_id: blogId
            },
            dataType: 'json',
            success: function(response) {
                if (!response.error) {
                    Alert(response.message || 'Blog post deleted successfully!');
                    // Reload DataTable instead of full page reload
                    if (typeof reloadBlogTable === 'function') {
                        reloadBlogTable();
                    } else {
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    }
                } else {
                    Alert(response.message || 'Failed to delete blog post');
                }
            },
            error: function() {
                Alert('Error deleting blog post');
            }
        });
    }
}

// Reset form
function resetForm() {
    $('#blogForm')[0].reset();
    tagsArray = [];
    updateTagsDisplay();
    updateTagsInput();
    
    // Clear image previews
    $('#featured_image_preview').html('');
    $('#author_image_preview').html('');
    $('#og_image_preview').html('');
    $('#twitter_card_image_preview').html('');
    $('#gallery_preview').html('');
    
    // Clear Quill editor
    if (typeof window.quillEditor !== 'undefined' && window.quillEditor) {
        window.quillEditor.root.innerHTML = '';
        document.getElementById('full_content').value = '';
    } else {
        $('#full_content').val('');
    }
    
    // Reset hidden fields
    $('#featured_image').val('');
    $('#author_image').val('');
    $('#og_image').val('');
    $('#twitter_card_image').val('');
    $('#embedded_video').val('');
    
    // Reset tabs to first
    $('#basic-tab').tab('show');
}

