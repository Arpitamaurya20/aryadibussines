<?php
@session_start();
require_once('../include/autoloader.inc.php');
$conf = new Conf();
$_ProductName = $conf->_ProductName;
$_ProductLogo = $conf->_ProductLogo;

// Session check MUST be done before any HTML output
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$session = new Session($conn);
$UserType = $session->SessionCheck_redirect();

$User_ID = '';
if (isset($_SESSION['UserID'])) {
    $User_ID = $_SESSION['UserID'];
}

$core = new Core();
$blog = new Blog($conn);
$allCategories = $blog->getAllBlogCategories();
$navigation = new Navigation();
$navigation->setNavigation($_SESSION['pp_UserType']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta name="keywords" content="">
    <title>Blog Management - <?= $_ProductName ?> Portal</title>
    <?php
    include("../include/common-head.php");
    ?>
    
    <!-- Quill.js - Free Rich Text Editor (No jQuery Required) -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    
    <style>
        .blog-image-preview {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
            border-radius: 5px;
        }
        .tag-input-container {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-height: 40px;
        }
        .tag-item {
            background: #007bff;
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .tag-item .remove-tag {
            cursor: pointer;
            font-weight: bold;
        }
        .tag-input {
            border: none;
            outline: none;
            flex: 1;
            min-width: 100px;
        }
        .card-header.bg-white {
            background-color: #fff !important;
            padding: 1.5rem;
        }
        .btn-lg {
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
        }
        #blogTable_wrapper .dataTables_filter input {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 0.375rem 0.75rem;
        }
        #blogTable_wrapper .dataTables_length select {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 0.375rem 2rem 0.375rem 0.75rem;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
        }
        .card.shadow-sm {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
        }
    </style>
</head>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6496901533964255"
     crossorigin="anonymous"></script>

<body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">
    <div class="app-wrapper">
        <?php
        include("../navigation/top-header.php");
        include("../navigation/side-navigation.php");
        ?>
        
        <main class="app-main">
            <div class="app-content-header">
                <div class="container-fluid">
                    <div class="row align-items-center">
                        <div class="col-sm-6">
                            <h3 class="mb-0">Blog Management</h3>
                            <p class="text-muted mb-0">Manage your blog posts, categories, and content</p>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex justify-content-end align-items-center gap-2">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Blog</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="app-content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow-sm">
                                <div class="card-header bg-white border-bottom">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h4 class="card-title mb-0">
                                                <i class="bi bi-journal-text me-2"></i>All Blog Posts
                                            </h4>
                                            <small class="text-muted">View and manage all your blog posts</small>
                                        </div>
                                        <button class="btn btn-primary btn-lg shadow-sm" onclick="AddBlog()">
                                            <i class="bi bi-plus-circle-fill me-2"></i>Add New Blog
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-striped" id="blogTable" style="width:100%">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Title</th>
                                                    <th>Category</th>
                                                    <th>Author</th>
                                                    <th>Status</th>
                                                    <th>Published Date</th>
                                                    <th>Views</th>
                                                    <th class="text-center">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Data will be loaded via AJAX -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <?php include("../include/common-footer.php"); ?>
    </div>
    
    <?php include("../include/common-script.php"); ?>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    
    <script>
        // Initialize DataTable
        $(document).ready(function() {
            $('#blogTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: '../blog/action/blog-action.php',
                    type: 'POST',
                    data: function(d) {
                        d.action = 'get_blogs_datatable';
                        d.draw = d.draw || 1;
                    },
                    dataSrc: function(json) {
                        if (json.data) {
                            return json.data;
                        }
                        return [];
                    },
                    error: function(xhr, error, thrown) {
                        console.error('DataTables error:', error);
                        console.error('Response:', xhr.responseText);
                    }
                },
                columns: [
                    { data: 'ID' },
                    { data: 'title' },
                    { data: 'category' },
                    { data: 'author' },
                    { data: 'status' },
                    { data: 'published_date' },
                    { data: 'views' },
                    { 
                        data: 'actions',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    }
                ],
                order: [[0, 'desc']],
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                responsive: true,
                language: {
                    processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    },
                    emptyTable: "No blog posts available",
                    zeroRecords: "No matching records found"
                },
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                drawCallback: function(settings) {
                    // Re-initialize tooltips if needed
                    $('[data-bs-toggle="tooltip"]').tooltip();
                }
            });
        });
        
        // Reload table after blog operations
        function reloadBlogTable() {
            $('#blogTable').DataTable().ajax.reload(null, false);
        }
    </script>
    
    <!-- Blog Modal -->
    <div class="modal fade" id="blogModal" tabindex="-1" aria-labelledby="blogModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="blogModalLabel">Add New Blog Post</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="blogForm" onsubmit="return false;">
                    <div class="modal-body">
                        <input type="hidden" name="blog_form_action" id="blog_form_action" value="add">
                        <input type="hidden" name="blog_form_id" id="blog_form_id" value="">
                        
                        <ul class="nav nav-tabs" id="blogTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic" type="button" role="tab">Basic Info</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="content-tab" data-bs-toggle="tab" data-bs-target="#content" type="button" role="tab">Content</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="seo-tab" data-bs-toggle="tab" data-bs-target="#seo" type="button" role="tab">SEO</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="social-tab" data-bs-toggle="tab" data-bs-target="#social" type="button" role="tab">Social Media</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button" role="tab">Settings</button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="blogTabContent">
                            <!-- Basic Info Tab -->
                            <div class="tab-pane fade show active" id="basic" role="tabpanel">
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Title <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="title" id="title" required>
                                            <small class="text-muted">Auto-generates slug from title</small>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Slug</label>
                                            <input type="text" class="form-control" name="slug" id="slug">
                                            <small class="text-muted">URL-friendly version (auto-generated)</small>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Short Description / Excerpt</label>
                                            <textarea class="form-control" name="short_description" id="short_description" rows="3"></textarea>
                                            <small class="text-muted">Used for preview cards & meta description</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Category</label>
                                            <select class="form-control" name="category_id" id="category_id">
                                                <option value="">Select Category</option>
                                                <?php foreach ($allCategories as $category): ?>
                                                    <option value="<?= $category['ID'] ?>"><?= htmlspecialchars($category['category_name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Tags</label>
                                            <div class="tag-input-container" id="tagsContainer">
                                                <input type="text" class="tag-input" id="tagInput" placeholder="Type and press Enter">
                                            </div>
                                            <input type="hidden" name="tags" id="tags">
                                            <small class="text-muted">Press Enter to add tags</small>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Featured Image</label>
                                            <input type="file" class="form-control" name="featured_image_file" id="featured_image_file" accept="image/*">
                                            <input type="hidden" name="featured_image" id="featured_image">
                                            <div id="featured_image_preview"></div>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">
                                                Featured Image (Mobile)
                                                <small class="text-muted">(Recommended: 1080×1350 px (4:5) or 1080×1080 px (1:1))</small>
                                            </label>
                                            <input type="file"
                                                class="form-control"
                                                name="featured_image_mobile_file"
                                                id="featured_image_mobile_file"
                                                accept="image/*">

                                            <input type="hidden"
                                                name="featured_image_mobile"
                                                id="featured_image_mobile">

                                            <div id="featured_image_mobile_preview" class="mt-2"></div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                            
                            <!-- Content Tab -->
                            <div class="tab-pane fade" id="content" role="tabpanel">
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Full Content <span class="text-danger">*</span></label>
                                            <div id="full_content_editor" style="height: 500px;"></div>
                                            <textarea class="form-control d-none" name="full_content" id="full_content" rows="15"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Author Name</label>
                                            <input type="text" class="form-control" name="author_name" id="author_name">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Author Image</label>
                                            <input type="file" class="form-control" name="author_image_file" id="author_image_file" accept="image/*">
                                            <input type="hidden" name="author_image" id="author_image">
                                            <div id="author_image_preview"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Author Bio</label>
                                            <textarea class="form-control" name="author_bio" id="author_bio" rows="3"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">YouTube Video URL</label>
                                            <input type="url" class="form-control" name="youtube_video_url" id="youtube_video_url" placeholder="https://www.youtube.com/watch?v=...">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Embedded Video (LinkedIn, etc.)</label>
                                            <textarea class="form-control" name="embedded_video" id="embedded_video" rows="4" placeholder="Paste your embedded iframe code here (e.g., LinkedIn embed)"></textarea>
                                            <small class="text-muted">Paste the complete iframe embed code (e.g., from LinkedIn, Vimeo, etc.)</small>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Gallery Images</label>
                                            <input type="file" class="form-control" name="gallery_images[]" id="gallery_images" multiple accept="image/*">
                                            <small class="text-muted">Select multiple images</small>
                                            <div id="gallery_preview" class="mt-2"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- SEO Tab -->
                            <div class="tab-pane fade" id="seo" role="tabpanel">
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Meta Title</label>
                                            <input type="text" class="form-control" name="meta_title" id="meta_title">
                                            <small class="text-muted">Appears in Google search results (50-60 characters recommended)</small>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Meta Description</label>
                                            <textarea class="form-control" name="meta_description" id="meta_description" rows="3"></textarea>
                                            <small class="text-muted">Google description snippet (150-160 characters recommended)</small>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Meta Keywords</label>
                                            <input type="text" class="form-control" name="meta_keywords" id="meta_keywords" placeholder="keyword1, keyword2, keyword3">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Focus Keyword</label>
                                            <input type="text" class="form-control" name="focus_keyword" id="focus_keyword">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Canonical URL</label>
                                            <input type="url" class="form-control" name="canonical_url" id="canonical_url">
                                            <small class="text-muted">Avoid duplicate content SEO issues</small>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Redirect URL (if slug changes)</label>
                                            <input type="url" class="form-control" name="redirect_url" id="redirect_url">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Social Media Tab -->
                            <div class="tab-pane fade" id="social" role="tabpanel">
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <h6>OpenGraph (Facebook, LinkedIn)</h6>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">OG Title</label>
                                            <input type="text" class="form-control" name="og_title" id="og_title">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">OG Description</label>
                                            <textarea class="form-control" name="og_description" id="og_description" rows="3"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">OG Image (1200x630 recommended)</label>
                                            <input type="file" class="form-control" name="og_image_file" id="og_image_file" accept="image/*">
                                            <input type="hidden" name="og_image" id="og_image">
                                            <div id="og_image_preview"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-12 mt-4">
                                        <h6>Twitter Card</h6>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Twitter Card Title</label>
                                            <input type="text" class="form-control" name="twitter_card_title" id="twitter_card_title">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Twitter Card Description</label>
                                            <textarea class="form-control" name="twitter_card_description" id="twitter_card_description" rows="3"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Twitter Card Image</label>
                                            <input type="file" class="form-control" name="twitter_card_image_file" id="twitter_card_image_file" accept="image/*">
                                            <input type="hidden" name="twitter_card_image" id="twitter_card_image">
                                            <div id="twitter_card_image_preview"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Settings Tab -->
                            <div class="tab-pane fade" id="settings" role="tabpanel">
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Status</label>
                                            <select class="form-control" name="status" id="status">
                                                <option value="draft">Draft</option>
                                                <option value="published">Published</option>
                                                <option value="scheduled">Scheduled</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Publish Date / Time</label>
                                            <input type="datetime-local" class="form-control" name="publish_date" id="publish_date">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="pin_to_top" id="pin_to_top" value="1">
                                                <label class="form-check-label" for="pin_to_top">Pin to Top</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="allow_comments" id="allow_comments" value="1" checked>
                                                <label class="form-check-label" for="allow_comments">Allow Comments</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="featured" id="featured" value="1">
                                                <label class="form-check-label" for="featured">Featured Post</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="trending" id="trending" value="1">
                                                <label class="form-check-label" for="trending">Trending Post</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" onclick="SaveBlog()">Save Blog Post</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../blog/blog.js"></script>
    <script>
        // Initialize Quill.js Rich Text Editor (Global variable)
        window.quillEditor = null;
        
        window.initializeQuillEditor = function() {
            // Check if editor container exists
            const editorContainer = document.getElementById('full_content_editor');
            if (!editorContainer) {
                console.error('Editor container not found');
                return;
            }
            
            // Destroy existing editor if any
            if (window.quillEditor) {
                try {
                    const editorElement = document.querySelector('#full_content_editor .ql-container');
                    if (editorElement) {
                        editorElement.innerHTML = '';
                    }
                    window.quillEditor = null;
                } catch(e) {
                    console.error('Error destroying editor:', e);
                }
            }
            
            // Clear container
            editorContainer.innerHTML = '';
            
            // Initialize Quill editor
            try {
                window.quillEditor = new Quill('#full_content_editor', {
                    theme: 'snow',
                    modules: {
                        toolbar: [
                            [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                            [{ 'size': ['small', false, 'large', 'huge'] }],
                            [{ 'font': [] }],
                            ['bold', 'italic', 'underline', 'strike'],
                            [{ 'color': [] }, { 'background': [] }],
                            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                            [{ 'align': [] }],
                            [{ 'indent': '-1'}, { 'indent': '+1' }],
                            ['link', 'image', 'video'],
                            ['blockquote', 'code-block'],
                            ['clean']
                        ]
                    },
                    placeholder: 'Start writing your blog content here...'
                });
                
                // Update hidden textarea when content changes
                window.quillEditor.on('text-change', function() {
                    const hiddenTextarea = document.getElementById('full_content');
                    if (hiddenTextarea) {
                        hiddenTextarea.value = window.quillEditor.root.innerHTML;
                    }
                });
                
                console.log('Quill editor initialized successfully');
                
                // If there's a function to set content, call it now
                if (typeof window.setBlogEditorContent === 'function') {
                    setTimeout(function() {
                        window.setBlogEditorContent();
                    }, 50);
                }
                
                // Trigger editor ready event
                $('#blogModal').trigger('editor.ready');
            } catch(e) {
                console.error('Error initializing Quill editor:', e);
            }
        };
        
        // Initialize editor when modal is shown
        document.addEventListener('DOMContentLoaded', function() {
            const blogModal = document.getElementById('blogModal');
            if (blogModal) {
                blogModal.addEventListener('shown.bs.modal', function() {
                    setTimeout(function() {
                        if (!window.quillEditor) {
                            window.initializeQuillEditor();
                        }
                        // Trigger custom event to notify that editor is ready
                        $(blogModal).trigger('editor.ready');
                    }, 200);
                });
            }
        });
        
        // Also initialize on page load if modal is already open
        $(document).ready(function() {
            if ($('#blogModal').hasClass('show')) {
                setTimeout(function() {
                    if (!window.quillEditor) {
                        window.initializeQuillEditor();
                    }
                }, 200);
            }
        });
    </script>

    <script>
$('#featured_image_mobile_file').on('change', function () {
    const file = this.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function (e) {
        $('#featured_image_mobile_preview').html(
            `<img src="${e.target.result}" 
                  style="max-width:150px;border-radius:6px;">`
        );
    };
    reader.readAsDataURL(file);
});
</script>

</body>
</html>

