<?php
@session_start();
require_once('../../include/autoloader.inc.php');

// Start output buffering to catch any warnings/errors before JSON output
ob_start();

$dbh = new Dbh();
$core = new Core();
$conn = $dbh->_connectodb();
$core->setTimeZone();

header('Content-Type: application/json');

$response = array('error' => false, 'message' => '');

// Function to upload file to local storage
function uploadFileLocal($file, $subfolder = 'resources', $prefix = 'FILE') {
    $response = array('error' => false, 'url' => '', 'message' => '', 'file_size' => 0, 'mime_type' => '');
    
    // Allowed file extensions
    $allowedExts = [
        // Documents
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv',
        // Images
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp',
        // Audio
        'mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac',
        // Video
        'mp4', 'webm', 'ogg', 'avi', 'mov', 'wmv', 'flv', 'mkv',
        // Archives
        'zip', 'rar', '7z', 'tar', 'gz'
    ];
    
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Validate file extension
    if (!in_array($file_ext, $allowedExts)) {
        return ['error' => true, 'message' => 'File type not allowed. Allowed types: ' . implode(', ', $allowedExts)];
    }
    
    // Validate file size (max 100MB)
    $maxSize = 100 * 1024 * 1024; // 100MB in bytes
    if ($file['size'] > $maxSize) {
        return ['error' => true, 'message' => 'File size exceeds 100MB limit.'];
    }
    
    // Create upload directory if it doesn't exist
    $uploadDir = __DIR__ . '/../../uploads/' . $subfolder . '/';
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            return ['error' => true, 'message' => 'Failed to create upload directory.'];
        }
    }
    
    // Generate unique filename with prefix
    $filename = $prefix . '_' . time() . '_' . uniqid() . '.' . $file_ext;
    $targetPath = $uploadDir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // Return relative URL path (from web root) - stored in database
        $relativeUrl = 'uploads/' . $subfolder . '/' . $filename;
        $response['url'] = $relativeUrl;
        $response['file_size'] = $file['size'];
        $response['mime_type'] = $file['type'];
        $response['message'] = 'File uploaded successfully';
    } else {
        $response['error'] = true;
        $response['message'] = 'Failed to move uploaded file.';
    }
    
    return $response;
}

function getFileUrl($filePath) {
    if (empty($filePath)) return '';

    // 1. If full URL, return as is
    if (preg_match('/^(http|https):\/\//', $filePath)) {
        return $filePath;
    }

    // 2. Auto-detect base URL
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                ? "https://" : "http://";

    $host = $_SERVER['HTTP_HOST'];
    $rootPath = realpath($_SERVER['DOCUMENT_ROOT']);
    $currentPath = realpath(__DIR__);
    $projectFolder = trim(str_replace($rootPath, '', $currentPath), '\\/');
    $parts = explode(DIRECTORY_SEPARATOR, $projectFolder);
    $topFolder = $parts[0];
    $secondFolder = $parts[1] ?? '';

    $baseUrl = $protocol . $host . "/" . $topFolder . "/" . $secondFolder . "/";
    $cleanPath = ltrim($filePath, '/.');

    return $baseUrl . $cleanPath;
}

if (!isset($_SESSION['UserID'])) {
    $response['error'] = true;
    $response['message'] = 'Unauthorized access';
    ob_clean();
    echo json_encode($response);
    ob_end_flush();
    exit;
}

$action = $_POST['action'] ?? '';

$resource = new ResourceLibrary($conn);

switch ($action) {
    case 'save_folder':
        if (isset($_POST['folder_name'])) {
            $data = array();
            $data['folder_name'] = $_POST['folder_name'] ?? '';
            $data['description'] = $_POST['description'] ?? '';
            $data['visibility'] = $_POST['visibility'] ?? 'private';
            $data['parent_id'] = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
            $data['display_order'] = !empty($_POST['display_order']) ? (int)$_POST['display_order'] : 0;
            
            $formAction = $_POST['folder_form_action'] ?? 'add';
            $folderId = $_POST['folder_form_id'] ?? '';
            
            if ($formAction == 'add') {
                $data['CreatedDate'] = date('Y-m-d');
                $data['CreatedTime'] = date('H:i:s');
                $data['CreatedBy'] = $_SESSION['pp_email'] ?? $_SESSION['UserID'];
                
                $result = $resource->insertFolder($data);
                if ($result['error'] == false) {
                    $response['message'] = 'Folder created successfully!';
                    $response['folder_id'] = $result['last_insert_id'] ?? null;
                } else {
                    $response['error'] = true;
                    $response['message'] = $result['message'] ?? 'Failed to create folder';
                }
            } else {
                $data['ModifiedDate'] = date('Y-m-d');
                $data['ModifiedTime'] = date('H:i:s');
                $data['ModifiedBy'] = $_SESSION['pp_email'] ?? $_SESSION['UserID'];
                
                $result = $resource->updateFolder($data, $folderId);
                if ($result['error'] == false) {
                    $response['message'] = 'Folder updated successfully!';
                } else {
                    $response['error'] = true;
                    $response['message'] = $result['message'] ?? 'Failed to update folder';
                }
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'Folder name is required';
        }
        break;
        
    case 'get_folder':
        $folderId = $_POST['folder_id'] ?? 0;
        if ($folderId > 0) {
            $folderData = $resource->getFolderById($folderId);
            if ($folderData) {
                $response['data'] = $folderData;
                $response['message'] = 'Folder retrieved successfully';
            } else {
                $response['error'] = true;
                $response['message'] = 'Folder not found';
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'Invalid folder ID';
        }
        break;
        
    case 'delete_folder':
        $folderId = $_POST['folder_id'] ?? 0;
        if ($folderId > 0) {
            $result = $resource->deleteFolder($folderId);
            if ($result) {
                $response['message'] = 'Folder deleted successfully';
            } else {
                $response['error'] = true;
                $response['message'] = 'Failed to delete folder';
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'Invalid folder ID';
        }
        break;
        
    case 'get_folders_datatable':
        $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $allFolders = $resource->getAllFolders($parentId);
        $data = array();
        
        foreach ($allFolders as $folder) {
            $visibilityBadge = $folder['visibility'] == 'public' 
                ? '<span class="badge bg-success">Public</span>' 
                : '<span class="badge bg-warning">Private</span>';
            
            $stats = $resource->getFolderStats($folder['ID']);
            
            // Get parent folder name
            $parentFolderName = '-';
            if (!empty($folder['parent_id'])) {
                $parentFolder = $resource->getFolderById($folder['parent_id']);
                if ($parentFolder) {
                    $parentFolderName = htmlspecialchars($parentFolder['folder_name']);
                }
            }
            
            // Format description
            $description = $folder['description'] ?? '';
            if (strlen($description) > 50) {
                $description = substr($description, 0, 50) . '...';
            }
            
            // Format created date
            $createdDate = '-';
            if (!empty($folder['CreatedDate'])) {
                $createdDate = date('d M Y', strtotime($folder['CreatedDate']));
            }
            
            $actionsHtml = '
                <button class="btn btn-sm btn-info me-1" onclick="EditFolder(' . $folder['ID'] . ')" title="Edit">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-primary me-1" onclick="ViewFolderFiles(' . $folder['ID'] . ')" title="View Files">
                    <i class="bi bi-files"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="DeleteFolder(' . $folder['ID'] . ')" title="Delete">
                    <i class="bi bi-trash"></i>
                </button>
            ';
            
            $data[] = array(
                'ID' => $folder['ID'],
                'folder_name' => htmlspecialchars($folder['folder_name']),
                'slug' => htmlspecialchars($folder['slug']),
                'description' => htmlspecialchars($description),
                'visibility' => $visibilityBadge,
                'parent_folder' => $parentFolderName,
                'files_count' => $stats['total_files'],
                'total_size' => $resource->formatFileSize($stats['total_size']),
                'created_date' => $createdDate,
                'actions' => $actionsHtml
            );
        }
        
        $response = array(
            'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 1,
            'recordsTotal' => count($data),
            'recordsFiltered' => count($data),
            'data' => $data
        );
        break;
        
    case 'upload_file':
        if (isset($_POST['folder_id']) && isset($_FILES['file'])) {
            $folderId = (int)$_POST['folder_id'];
            
            // Check if folder exists
            $folder = $resource->getFolderById($folderId);
            if (!$folder) {
                $response['error'] = true;
                $response['message'] = 'Folder not found';
                break;
            }
            
            $uploadResult = uploadFileLocal($_FILES['file'], 'resources/files', 'RESOURCE');
            
            if (!$uploadResult['error']) {
                $fileExtension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
                $fileType = $resource->detectFileType($fileExtension);
                
                $data = array();
                $data['folder_id'] = $folderId;
                $data['title'] = $_POST['file_title'] ?? pathinfo($_FILES['file']['name'], PATHINFO_FILENAME);
                $data['description'] = $_POST['file_description'] ?? '';
                $data['file_name'] = basename($uploadResult['url']);
                $data['original_file_name'] = $_FILES['file']['name'];
                $data['file_path'] = $uploadResult['url'];
                $data['file_type'] = $fileType;
                $data['file_extension'] = $fileExtension;
                $data['file_size'] = $uploadResult['file_size'];
                $data['mime_type'] = $uploadResult['mime_type'];
                $data['tags'] = $_POST['tags'] ?? '';
                
                // Audio files are non-downloadable by default
                if ($fileType == 'audio') {
                    $data['is_downloadable'] = 0;
                } else {
                    $data['is_downloadable'] = isset($_POST['is_downloadable']) ? (int)$_POST['is_downloadable'] : 1;
                }
                
                $data['CreatedDate'] = date('Y-m-d');
                $data['CreatedTime'] = date('H:i:s');
                $data['CreatedBy'] = $_SESSION['pp_email'] ?? $_SESSION['UserID'];
                
                $result = $resource->insertFile($data);
                if ($result['error'] == false) {
                    $response['message'] = 'File uploaded successfully!';
                    $response['file_id'] = $result['last_insert_id'] ?? null;
                } else {
                    $response['error'] = true;
                    $response['message'] = $result['message'] ?? 'Failed to save file';
                }
            } else {
                $response['error'] = true;
                $response['message'] = $uploadResult['message'] ?? 'Failed to upload file';
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'Folder ID and file are required';
        }
        break;
        
    case 'get_files':
        $folderId = $_POST['folder_id'] ?? 0;
        $fileType = $_POST['file_type'] ?? null;
        
        if ($folderId > 0) {
            $files = $resource->getFilesByFolder($folderId, $fileType);
            $data = array();
            
            foreach ($files as $file) {
                $data[] = array(
                    'ID' => $file['ID'],
                    'title' => $file['title'] ?? $file['file_name'],
                    'file_name' => $file['file_name'],
                    'file_type' => $file['file_type'],
                    'file_size' => $file['file_size'],
                    'file_size_formatted' => $resource->formatFileSize($file['file_size']),
                    'is_downloadable' => $file['is_downloadable'],
                    'access_count' => $file['access_count'] ?? 0,
                    'created_date' => $file['CreatedDate'] ? date('d M Y', strtotime($file['CreatedDate'])) : '',
                    'file_path' => $file['file_path'],
                    'mime_type' => $file['mime_type']
                );
            }
            
            $response['data'] = $data;
            $response['message'] = 'Files retrieved successfully';
        } else {
            $response['error'] = true;
            $response['message'] = 'Folder ID is required';
        }
        break;
        
    case 'get_file':
        $fileId = $_POST['file_id'] ?? 0;
        if ($fileId > 0) {
            $fileData = $resource->getFileById($fileId);
            if ($fileData) {
                $response['data'] = $fileData;
                $response['message'] = 'File retrieved successfully';
            } else {
                $response['error'] = true;
                $response['message'] = 'File not found';
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'Invalid file ID';
        }
        break;
        
    case 'update_file':
        $fileId = $_POST['file_id'] ?? 0;
        if ($fileId > 0) {
            $data = array();
            
            if (isset($_POST['title'])) {
                $data['title'] = $_POST['title'];
            }
            if (isset($_POST['description'])) {
                $data['description'] = $_POST['description'];
            }
            if (isset($_POST['tags'])) {
                $data['tags'] = $_POST['tags'];
            }
            if (isset($_POST['is_downloadable'])) {
                $data['is_downloadable'] = (int)$_POST['is_downloadable'];
            }
            
            $data['ModifiedDate'] = date('Y-m-d');
            $data['ModifiedTime'] = date('H:i:s');
            $data['ModifiedBy'] = $_SESSION['pp_email'] ?? $_SESSION['UserID'];
            
            $result = $resource->updateFile($data, $fileId);
            if ($result) {
                $response['message'] = 'File updated successfully';
            } else {
                $response['error'] = true;
                $response['message'] = 'Failed to update file';
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'Invalid file ID';
        }
        break;
        
    case 'delete_file':
        $fileId = $_POST['file_id'] ?? 0;
        if ($fileId > 0) {
            $result = $resource->deleteFile($fileId);
            if ($result) {
                $response['message'] = 'File deleted successfully';
            } else {
                $response['error'] = true;
                $response['message'] = 'Failed to delete file';
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'Invalid file ID';
        }
        break;
        
    case 'generate_signed_url':
        $fileId = $_POST['file_id'] ?? 0;
        $expiresIn = !empty($_POST['expires_in']) ? (int)$_POST['expires_in'] : 10;
        $maxAccess = !empty($_POST['max_access']) ? (int)$_POST['max_access'] : null;
        
        if ($fileId > 0) {
            $token = $resource->generateSignedUrl($fileId, $expiresIn, $maxAccess);
            if ($token) {
                $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
                $response['signed_url'] = $baseUrl . '/resources/stream.php?token=' . $token;
                $response['message'] = 'Signed URL generated successfully';
            } else {
                $response['error'] = true;
                $response['message'] = 'Failed to generate signed URL';
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'Invalid file ID';
        }
        break;
        
    case 'get_access_logs_datatable':
        // Get access logs for DataTables AJAX
        $folderId = !empty($_POST['folder_id']) ? (int)$_POST['folder_id'] : null;
        $accessType = $_POST['access_type'] ?? null;
        $dateFrom = $_POST['date_from'] ?? null;
        $dateTo = $_POST['date_to'] ?? null;
        
        $where = "WHERE 1=1";
        
        if ($folderId !== null) {
            $where .= " AND folder_id = " . (int)$folderId;
        }
        
        if ($accessType) {
            $where .= " AND access_type = '" . $conn->real_escape_string($accessType) . "'";
        }
        
        if ($dateFrom) {
            $where .= " AND access_date >= '" . $conn->real_escape_string($dateFrom) . "'";
        }
        
        if ($dateTo) {
            $where .= " AND access_date <= '" . $conn->real_escape_string($dateTo) . "'";
        }
        
        $where .= " ORDER BY access_datetime DESC";
        
        $allLogs = $core->_getTableRecords($conn, 'resource_access_logs', $where);
        $data = array();
        
        foreach ($allLogs as $log) {
            // Get file name
            $fileName = '-';
            if (!empty($log['file_id'])) {
                $file = $resource->getFileById($log['file_id']);
                if ($file) {
                    $fileName = htmlspecialchars($file['title'] ?? $file['file_name']);
                }
            }
            
            // Get folder name
            $folderName = '-';
            if (!empty($log['folder_id'])) {
                $folder = $resource->getFolderById($log['folder_id']);
                if ($folder) {
                    $folderName = htmlspecialchars($folder['folder_name']);
                }
            }
            
            // User info
            $userInfo = 'Guest';
            if (!empty($log['user_email'])) {
                $userInfo = htmlspecialchars($log['user_email']);
            } elseif (!empty($log['user_id'])) {
                $userInfo = 'User ID: ' . $log['user_id'];
            }
            
            // Access type badge
            $accessTypeBadge = '';
            switch ($log['access_type']) {
                case 'view':
                    $accessTypeBadge = '<span class="badge bg-info">View</span>';
                    break;
                case 'download':
                    $accessTypeBadge = '<span class="badge bg-success">Download</span>';
                    break;
                case 'stream':
                    $accessTypeBadge = '<span class="badge bg-primary">Stream</span>';
                    break;
                default:
                    $accessTypeBadge = '<span class="badge bg-secondary">' . htmlspecialchars($log['access_type']) . '</span>';
            }
            
            // Format datetime
            $accessDateTime = '-';
            if (!empty($log['access_datetime'])) {
                $accessDateTime = date('d M Y, h:i A', strtotime($log['access_datetime']));
            } elseif (!empty($log['access_date']) && !empty($log['access_time'])) {
                $accessDateTime = date('d M Y, h:i A', strtotime($log['access_date'] . ' ' . $log['access_time']));
            }
            
            // Parse user agent
            $userAgent = htmlspecialchars(substr($log['user_agent'] ?? 'Unknown', 0, 50));
            if (strlen($log['user_agent'] ?? '') > 50) {
                $userAgent .= '...';
            }
            
            $data[] = array(
                'ID' => $log['ID'],
                'file_name' => $fileName,
                'folder_name' => $folderName,
                'user' => $userInfo,
                'ip_address' => htmlspecialchars($log['ip_address'] ?? '-'),
                'access_type' => $accessTypeBadge,
                'access_datetime' => $accessDateTime,
                'user_agent' => $userAgent
            );
        }
        
        $response = array(
            'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 1,
            'recordsTotal' => count($data),
            'recordsFiltered' => count($data),
            'data' => $data
        );
        break;
        
    default:
        $response['error'] = true;
        $response['message'] = 'Invalid action';
        break;
}

ob_clean();
echo json_encode($response);
ob_end_flush();
?>

