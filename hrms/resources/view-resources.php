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
$resource = new ResourceLibrary($conn);
$allFolders = $resource->getAllFolders();
$navigation = new Navigation();
$navigation->setNavigation($_SESSION['pp_UserType']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta name="keywords" content="">
    <title>Resource Library - <?= $_ProductName ?> Portal</title>
    <?php
    include("../include/common-head.php");
    ?>
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    
    <style>
        .card-header.bg-white {
            background-color: #fff !important;
            padding: 1.5rem;
        }
        .btn-lg {
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
        }
        .folder-tree {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
            max-height: 400px;
            overflow-y: auto;
        }
        .folder-item {
            padding: 0.5rem;
            cursor: pointer;
            border-radius: 0.25rem;
            margin-bottom: 0.25rem;
        }
        .folder-item:hover {
            background: #e9ecef;
        }
        .folder-item.active {
            background: #0d6efd;
            color: white;
        }
        .file-icon {
            font-size: 2rem;
            margin-right: 0.5rem;
        }
        .file-preview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 5px;
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
                            <h3 class="mb-0">Resource Library</h3>
                            <p class="text-muted mb-0">Manage folders and files with public/private access control</p>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex justify-content-end align-items-center gap-2">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Resource Library</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="app-content">
                <div class="container-fluid">
                    <div class="row">
                        <!-- Folders Sidebar -->
                        <div class="col-md-3">
                            <div class="card shadow-sm">
                                <div class="card-header bg-white border-bottom">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">Folders</h5>
                                        <button class="btn btn-sm btn-primary" onclick="AddFolder()">
                                            <i class="bi bi-plus-circle"></i> New
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <div class="folder-tree" id="folderTree">
                                        <div class="folder-item" onclick="loadFolderFiles(null)">
                                            <i class="bi bi-folder-fill me-2"></i> All Folders
                                        </div>
                                        <?php foreach ($allFolders as $folder): ?>
                                        <div class="folder-item" onclick="loadFolderFiles(<?= $folder['ID'] ?>, '<?= htmlspecialchars($folder['folder_name']) ?>')" data-folder-id="<?= $folder['ID'] ?>">
                                            <i class="bi bi-folder<?= $folder['visibility'] == 'public' ? '-open' : '' ?> me-2"></i>
                                            <?= htmlspecialchars($folder['folder_name']) ?>
                                            <?php if ($folder['visibility'] == 'public'): ?>
                                            <span class="badge bg-success float-end">Public</span>
                                            <?php else: ?>
                                            <span class="badge bg-warning float-end">Private</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Files Area -->
                        <div class="col-md-9">
                            <div class="card shadow-sm">
                                <div class="card-header bg-white border-bottom">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="mb-0" id="currentFolderTitle">
                                                <i class="bi bi-files me-2"></i>All Files
                                            </h5>
                                            <small class="text-muted" id="currentFolderDesc">Select a folder to view files</small>
                                        </div>
                                        <div>
                                            <button class="btn btn-primary btn-lg shadow-sm" onclick="UploadFile()" id="uploadBtn" style="display: none;">
                                                <i class="bi bi-cloud-upload-fill me-2"></i>Upload File
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <!-- Filters -->
                                    <div class="row mb-3" id="fileFilters" style="display: none;">
                                        <div class="col-md-4">
                                            <select class="form-control" id="filterFileType" onchange="loadFiles()">
                                                <option value="">All File Types</option>
                                                <option value="document">Documents</option>
                                                <option value="image">Images</option>
                                                <option value="audio">Audio</option>
                                                <option value="video">Videos</option>
                                                <option value="archive">Archives</option>
                                            </select>
                                        </div>
                                        <div class="col-md-8">
                                            <input type="text" class="form-control" id="searchFiles" placeholder="Search files..." onkeyup="searchFiles()">
                                        </div>
                                    </div>
                                    
                                    <!-- Files Grid -->
                                    <div id="filesContainer">
                                        <div class="text-center text-muted py-5">
                                            <i class="bi bi-folder-x" style="font-size: 3rem;"></i>
                                            <p class="mt-3">Select a folder to view files</p>
                                        </div>
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
    
    <script src="../resources/resources.js"></script>
    
    <script>
        let currentFolderId = null;
        let currentFolderName = '';
        
        // Load folder files
        window.loadFolderFiles = function(folderId, folderName) {
            currentFolderId = folderId;
            currentFolderName = folderName || 'All Files';
            
            // Update UI
            $('#currentFolderTitle').html('<i class="bi bi-folder-fill me-2"></i>' + currentFolderName);
            $('.folder-item').removeClass('active');
            if (folderId) {
                $('.folder-item[data-folder-id="' + folderId + '"]').addClass('active');
            } else {
                $('.folder-item').first().addClass('active');
            }
            
            $('#uploadBtn').show();
            $('#fileFilters').show();
            
            loadFiles();
        };
        
        // Load files
        function loadFiles() {
            if (currentFolderId === null) {
                $('#filesContainer').html('<div class="text-center text-muted py-5"><i class="bi bi-folder-x" style="font-size: 3rem;"></i><p class="mt-3">Select a folder to view files</p></div>');
                $('#uploadBtn').hide();
                $('#fileFilters').hide();
                return;
            }
            
            let fileType = $('#filterFileType').val() || '';
            
            $.ajax({
                url: '../resources/action/resource-action.php',
                type: 'POST',
                data: {
                    action: 'get_files',
                    folder_id: currentFolderId,
                    file_type: fileType
                },
                dataType: 'json',
                success: function(response) {
                    if (!response.error && response.data) {
                        displayFiles(response.data);
                    } else {
                        $('#filesContainer').html('<div class="text-center text-muted py-5">No files found</div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading files:', xhr.responseText);
                    $('#filesContainer').html('<div class="alert alert-danger">Error loading files</div>');
                }
            });
        }
        
        // Display files in grid
        function displayFiles(files) {
            if (files.length === 0) {
                $('#filesContainer').html('<div class="text-center text-muted py-5">No files in this folder</div>');
                return;
            }
            
            let html = '<div class="row">';
            files.forEach(function(file) {
                let icon = getFileIcon(file.file_type);
                html += `
                    <div class="col-md-3 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <div class="file-icon">${icon}</div>
                                <h6 class="card-title">${file.title}</h6>
                                <p class="text-muted small">${file.file_size}</p>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-info" onclick="ViewFile(${file.ID})" title="View">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button class="btn btn-warning" onclick="EditFile(${file.ID})" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-danger" onclick="DeleteFile(${file.ID})" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            $('#filesContainer').html(html);
        }
        
        // Get file icon based on type
        function getFileIcon(fileType) {
            const icons = {
                'document': '<i class="bi bi-file-earmark-pdf text-danger"></i>',
                'image': '<i class="bi bi-file-earmark-image text-success"></i>',
                'audio': '<i class="bi bi-file-earmark-music text-info"></i>',
                'video': '<i class="bi bi-file-earmark-play text-danger"></i>',
                'archive': '<i class="bi bi-file-earmark-zip text-secondary"></i>'
            };
            return icons[fileType] || '<i class="bi bi-file-earmark"></i>';
        }
        
        // Search files
        function searchFiles() {
            // Implement search functionality
            loadFiles();
        }
    </script>
    
    <!-- Folder Modal -->
    <div class="modal fade" id="folderModal" tabindex="-1" aria-labelledby="folderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="folderModalLabel">Add New Folder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="folderForm" onsubmit="return false;">
                    <div class="modal-body">
                        <input type="hidden" name="folder_form_action" id="folder_form_action" value="add">
                        <input type="hidden" name="folder_form_id" id="folder_form_id" value="">
                        
                        <div class="mb-3">
                            <label class="form-label">Folder Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="folder_name" id="folder_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="folder_description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Visibility <span class="text-danger">*</span></label>
                            <select class="form-control" name="visibility" id="folder_visibility" required>
                                <option value="private">Private (Admin Only)</option>
                                <option value="public">Public (Anyone Can Access)</option>
                            </select>
                            <small class="text-muted">Public folders are accessible without login. Private folders require admin login.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Parent Folder (Optional)</label>
                            <select class="form-control" name="parent_id" id="folder_parent_id">
                                <option value="">Root (No Parent)</option>
                                <?php foreach ($allFolders as $folder): ?>
                                    <option value="<?= $folder['ID'] ?>"><?= htmlspecialchars($folder['folder_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" onclick="SaveFolder()">Save Folder</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- File Upload Modal -->
    <div class="modal fade" id="fileUploadModal" tabindex="-1" aria-labelledby="fileUploadModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="fileUploadModalLabel">Upload File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="fileUploadForm" onsubmit="return false;" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="folder_id" id="upload_folder_id" value="">
                        
                        <div class="mb-3">
                            <label class="form-label">Select File <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" name="file" id="upload_file" required>
                            <small class="text-muted">Supported: PDF, Images, Audio, Video, Documents, Archives (Max 100MB)</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">File Title (Optional)</label>
                            <input type="text" class="form-control" name="file_title" id="upload_file_title">
                            <small class="text-muted">Leave empty to use filename</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description (Optional)</label>
                            <textarea class="form-control" name="file_description" id="upload_file_description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tags (Optional)</label>
                            <input type="text" class="form-control" name="tags" id="upload_tags" placeholder="tag1, tag2, tag3">
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_downloadable" id="upload_is_downloadable" value="1" checked>
                                <label class="form-check-label" for="upload_is_downloadable">
                                    Allow Download
                                </label>
                                <small class="text-muted d-block">Note: Audio files are stream-only by default</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" onclick="SaveFileUpload()">Upload File</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

