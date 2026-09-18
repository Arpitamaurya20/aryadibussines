<?php
@session_start();
require_once('../include/autoloader.inc.php');

$dbh = new Dbh();
$conn = $dbh->_connectodb();
$resource = new ResourceLibrary($conn);

// Get slug from URL
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

if (empty($slug)) {
    header('HTTP/1.0 404 Not Found');
    die('Folder not found');
}

// Get folder by slug
$folder = $resource->getFolderBySlug($slug);

if (!$folder) {
    header('HTTP/1.0 404 Not Found');
    die('Folder not found');
}

// Check access permission
$userId = isset($_SESSION['UserID']) ? $_SESSION['UserID'] : null;

if (!$resource->canAccessFolder($folder['ID'], $userId)) {
    if ($folder['visibility'] == 'private') {
        // Redirect to login if private
        header('Location: ../authentication/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    } else {
        header('HTTP/1.0 403 Forbidden');
        die('Access denied');
    }
}

// Get files in folder
$fileType = isset($_GET['type']) ? $_GET['type'] : null;
$files = $resource->getFilesByFolder($folder['ID'], $fileType);

// Get subfolders
$subfolders = $resource->getAllFolders($folder['ID']);

// Get folder path
$folderPath = $resource->getFolderPath($folder['ID']);

// Log folder access
$resource->logFileAccess(null, $folder['ID'], 'view', $userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($folder['folder_name']) ?> - Resource Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .file-card {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .file-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .file-icon-large {
            font-size: 3rem;
        }
        .breadcrumb-item + .breadcrumb-item::before {
            content: ">";
        }
    </style>
</head>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6496901533964255"
     crossorigin="anonymous"></script>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/">Resource Library</a>
        </div>
    </nav>

    <div class="container my-5">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/">Home</a></li>
                <?php foreach ($folderPath as $pathFolder): ?>
                    <li class="breadcrumb-item"><a href="folder-view.php?slug=<?= htmlspecialchars($pathFolder['slug']) ?>"><?= htmlspecialchars($pathFolder['folder_name']) ?></a></li>
                <?php endforeach; ?>
                <li class="breadcrumb-item active"><?= htmlspecialchars($folder['folder_name']) ?></li>
            </ol>
        </nav>

        <!-- Folder Header -->
        <div class="card mb-4">
            <div class="card-body">
                <h1 class="card-title">
                    <i class="bi bi-folder-fill me-2"></i><?= htmlspecialchars($folder['folder_name']) ?>
                    <?php if ($folder['visibility'] == 'public'): ?>
                    <span class="badge bg-success">Public</span>
                    <?php endif; ?>
                </h1>
                <?php if (!empty($folder['description'])): ?>
                <p class="card-text text-muted"><?= htmlspecialchars($folder['description']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Subfolders -->
        <?php if (!empty($subfolders)): ?>
        <div class="mb-4">
            <h4>Subfolders</h4>
            <div class="row">
                <?php foreach ($subfolders as $subfolder): ?>
                    <?php if ($resource->canAccessFolder($subfolder['ID'], $userId)): ?>
                    <div class="col-md-3 mb-3">
                        <div class="card file-card" onclick="window.location.href='folder-view.php?slug=<?= htmlspecialchars($subfolder['slug']) ?>'">
                            <div class="card-body text-center">
                                <i class="bi bi-folder-fill file-icon-large text-warning"></i>
                                <h6 class="mt-2"><?= htmlspecialchars($subfolder['folder_name']) ?></h6>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <select class="form-control" id="fileTypeFilter" onchange="filterFiles()">
                            <option value="">All File Types</option>
                            <option value="document">Documents</option>
                            <option value="image">Images</option>
                            <option value="audio">Audio</option>
                            <option value="video">Videos</option>
                            <option value="archive">Archives</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <input type="text" class="form-control" id="searchInput" placeholder="Search files..." onkeyup="searchFiles()">
                    </div>
                </div>
            </div>
        </div>

        <!-- Files Grid -->
        <div id="filesGrid">
            <div class="row">
                <?php foreach ($files as $file): ?>
                    <div class="col-md-3 mb-4 file-item" data-type="<?= htmlspecialchars($file['file_type']) ?>" data-name="<?= htmlspecialchars(strtolower($file['title'] ?? $file['file_name'])) ?>">
                        <div class="card file-card h-100" onclick="viewFile(<?= $file['ID'] ?>)">
                            <div class="card-body text-center">
                                <?php
                                $iconClass = 'bi-file-earmark';
                                $iconColor = 'text-secondary';
                                switch ($file['file_type']) {
                                    case 'document':
                                        $iconClass = 'bi-file-earmark-pdf';
                                        $iconColor = 'text-danger';
                                        break;
                                    case 'image':
                                        $iconClass = 'bi-file-earmark-image';
                                        $iconColor = 'text-success';
                                        break;
                                    case 'audio':
                                        $iconClass = 'bi-file-earmark-music';
                                        $iconColor = 'text-info';
                                        break;
                                    case 'video':
                                        $iconClass = 'bi-file-earmark-play';
                                        $iconColor = 'text-danger';
                                        break;
                                    case 'archive':
                                        $iconClass = 'bi-file-earmark-zip';
                                        $iconColor = 'text-secondary';
                                        break;
                                }
                                ?>
                                <i class="bi <?= $iconClass ?> file-icon-large <?= $iconColor ?>"></i>
                                <h6 class="mt-2"><?= htmlspecialchars($file['title'] ?? $file['file_name']) ?></h6>
                                <p class="text-muted small mb-0"><?= $resource->formatFileSize($file['file_size']) ?></p>
                                <?php if ($file['file_type'] == 'audio'): ?>
                                <span class="badge bg-info mt-2">Stream Only</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (empty($files) && empty($subfolders)): ?>
        <div class="text-center py-5">
            <i class="bi bi-folder-x" style="font-size: 4rem; color: #ccc;"></i>
            <p class="text-muted mt-3">This folder is empty</p>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function filterFiles() {
            let type = document.getElementById('fileTypeFilter').value;
            let items = document.querySelectorAll('.file-item');
            
            items.forEach(function(item) {
                if (!type || item.getAttribute('data-type') === type) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
        function searchFiles() {
            let search = document.getElementById('searchInput').value.toLowerCase();
            let items = document.querySelectorAll('.file-item');
            
            items.forEach(function(item) {
                let name = item.getAttribute('data-name');
                if (name.includes(search)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
        function viewFile(fileId) {
            window.location.href = 'file-view.php?id=' + fileId;
        }
    </script>
</body>
</html>

