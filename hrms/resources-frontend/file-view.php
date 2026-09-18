<?php
@session_start();
require_once('../include/autoloader.inc.php');

$dbh = new Dbh();
$conn = $dbh->_connectodb();
$resource = new ResourceLibrary($conn);

// Get file ID from URL
$fileId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($fileId <= 0) {
    header('HTTP/1.0 404 Not Found');
    die('File not found');
}

// Get file details
$file = $resource->getFileById($fileId);

if (!$file) {
    header('HTTP/1.0 404 Not Found');
    die('File not found');
}

// Check access permission
$userId = isset($_SESSION['UserID']) ? $_SESSION['UserID'] : null;

if (!$resource->canAccessFile($file['ID'], $userId)) {
    // Get folder to check visibility
    $folder = $resource->getFolderById($file['folder_id']);
    if ($folder && $folder['visibility'] == 'private') {
        // Redirect to login if private
        header('Location: ../authentication/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    } else {
        header('HTTP/1.0 403 Forbidden');
        die('Access denied');
    }
}

// Get folder details
$folder = $resource->getFolderById($file['folder_id']);
$folderPath = $resource->getFolderPath($file['folder_id']);

// Log file access
$resource->logFileAccess($file['ID'], $file['folder_id'], 'view', $userId);

// Generate file URL
$fileUrl = '../' . $file['file_path'];
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$fullFileUrl = $baseUrl . '/' . ltrim($file['file_path'], './');

// For audio files, use streaming endpoint
if ($file['file_type'] == 'audio') {
    // Generate signed URL for audio streaming
    $token = $resource->generateSignedUrl($file['ID'], 60); // 60 minutes expiry
    if ($token) {
        $fileUrl = '../resources/stream.php?token=' . $token;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($file['title'] ?? $file['file_name']) ?> - Resource Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .file-preview-container {
            min-height: 400px;
            background: #f8f9fa;
            border-radius: 0.5rem;
            padding: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .audio-player {
            width: 100%;
            max-width: 600px;
        }
        .video-player {
            width: 100%;
            max-width: 800px;
        }
        .pdf-viewer {
            width: 100%;
            height: 600px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6496901533964255"
     crossorigin="anonymous"></script>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/">Resource Library</a>
            <?php if ($folder): ?>
            <a href="folder-view.php?slug=<?= htmlspecialchars($folder['slug']) ?>" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-left"></i> Back to Folder
            </a>
            <?php endif; ?>
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
                <li class="breadcrumb-item active"><?= htmlspecialchars($file['title'] ?? $file['file_name']) ?></li>
            </ol>
        </nav>

        <!-- File Header -->
        <div class="card mb-4">
            <div class="card-body">
                <h1 class="card-title"><?= htmlspecialchars($file['title'] ?? $file['file_name']) ?></h1>
                <?php if (!empty($file['description'])): ?>
                <p class="card-text text-muted"><?= htmlspecialchars($file['description']) ?></p>
                <?php endif; ?>
                <div class="mt-3">
                    <span class="badge bg-primary me-2"><?= ucfirst($file['file_type']) ?></span>
                    <span class="badge bg-secondary me-2"><?= strtoupper($file['file_extension']) ?></span>
                    <span class="badge bg-info"><?= $resource->formatFileSize($file['file_size']) ?></span>
                </div>
            </div>
        </div>

        <!-- File Preview -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="file-preview-container">
                    <?php if ($file['file_type'] == 'image'): ?>
                        <img src="<?= htmlspecialchars($fullFileUrl) ?>" alt="<?= htmlspecialchars($file['title'] ?? $file['file_name']) ?>" class="img-fluid" style="max-height: 600px;">
                    
                    <?php elseif ($file['file_type'] == 'audio'): ?>
                        <audio controls controlsList="nodownload" class="audio-player">
                            <source src="<?= htmlspecialchars($fileUrl) ?>" type="<?= htmlspecialchars($file['mime_type']) ?>">
                            Your browser does not support the audio element.
                        </audio>
                    
                    <?php elseif ($file['file_type'] == 'video'): ?>
                        <video controls class="video-player">
                            <source src="<?= htmlspecialchars($fullFileUrl) ?>" type="<?= htmlspecialchars($file['mime_type']) ?>">
                            Your browser does not support the video element.
                        </video>
                    
                    <?php elseif ($file['file_type'] == 'document' && $file['file_extension'] == 'pdf'): ?>
                        <iframe src="<?= htmlspecialchars($fullFileUrl) ?>" class="pdf-viewer"></iframe>
                    
                    <?php else: ?>
                        <div class="text-center">
                            <i class="bi bi-file-earmark" style="font-size: 5rem; color: #ccc;"></i>
                            <p class="mt-3 text-muted">Preview not available for this file type</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- File Actions -->
        <div class="card">
            <div class="card-body">
                <h5>File Actions</h5>
                <div class="btn-group" role="group">
                    <?php if ($file['file_type'] != 'audio' && $file['is_downloadable'] == 1): ?>
                    <a href="<?= htmlspecialchars($fullFileUrl) ?>" download class="btn btn-primary">
                        <i class="bi bi-download"></i> Download
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($file['file_type'] == 'audio'): ?>
                    <button class="btn btn-info" disabled>
                        <i class="bi bi-info-circle"></i> Audio files are stream-only (no download)
                    </button>
                    <?php endif; ?>
                    
                    <?php if ($folder): ?>
                    <a href="folder-view.php?slug=<?= htmlspecialchars($folder['slug']) ?>" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Folder
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Prevent right-click download on audio
        document.addEventListener('contextmenu', function(e) {
            if (e.target.tagName === 'AUDIO') {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>

