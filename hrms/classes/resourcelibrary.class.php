<?php

class ResourceLibrary extends Core
{
    private $conn;
    
    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->setTimeZone();
    }

    // Generate slug from folder name
    public function generateFolderSlug($folderName, $parentId = null, $excludeId = null)
    {
        $slug = strtolower(trim($folderName));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        $originalSlug = $slug;
        $counter = 1;
        while ($this->checkFolderSlugExists($slug, $parentId, $excludeId)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    // Check if folder slug exists
    public function checkFolderSlugExists($slug, $parentId = null, $excludeId = null)
    {
        $where = "WHERE slug = '" . $this->conn->real_escape_string($slug) . "'";
        if ($parentId !== null) {
            $where .= " AND parent_id = " . (int)$parentId;
        } else {
            $where .= " AND (parent_id IS NULL OR parent_id = 0)";
        }
        if ($excludeId !== null) {
            $where .= " AND ID != " . (int)$excludeId;
        }
        $result = $this->_getTableRecords($this->conn, 'resource_folders', $where);
        return count($result) > 0;
    }

    // Detect file type from extension
    public function detectFileType($extension)
    {
        $extension = strtolower($extension);
        
        $documentTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv'];
        $imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'];
        $audioTypes = ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac'];
        $videoTypes = ['mp4', 'webm', 'ogg', 'avi', 'mov', 'wmv', 'flv', 'mkv'];
        $archiveTypes = ['zip', 'rar', '7z', 'tar', 'gz'];
        
        if (in_array($extension, $documentTypes)) {
            return 'document';
        } elseif (in_array($extension, $imageTypes)) {
            return 'image';
        } elseif (in_array($extension, $audioTypes)) {
            return 'audio';
        } elseif (in_array($extension, $videoTypes)) {
            return 'video';
        } elseif (in_array($extension, $archiveTypes)) {
            return 'archive';
        }
        
        return 'other';
    }

    // Format file size
    public function formatFileSize($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    // Folder CRUD Operations
    public function insertFolder($data)
    {
        if (empty($data['slug']) && !empty($data['folder_name'])) {
            $parentId = $data['parent_id'] ?? null;
            $data['slug'] = $this->generateFolderSlug($data['folder_name'], $parentId);
        }
        return $this->_InsertTableRecords_prepare($this->conn, 'resource_folders', $data);
    }

    public function updateFolder($data, $id)
    {
        if (!empty($data['folder_name']) && empty($data['slug'])) {
            $existing = $this->getFolderById($id);
            if ($existing && $existing['folder_name'] != $data['folder_name']) {
                $parentId = $data['parent_id'] ?? $existing['parent_id'];
                $data['slug'] = $this->generateFolderSlug($data['folder_name'], $parentId, $id);
            }
        }
        $where = "ID = " . (int)$id;
        return $this->_UpdateTableRecords_prepare($this->conn, 'resource_folders', $data, $where);
    }

    public function deleteFolder($id)
    {
        $where = "ID = " . (int)$id;
        $data = array('IsActive' => 0);
        return $this->_UpdateTableRecords_prepare($this->conn, 'resource_folders', $data, $where);
    }

    public function getFolderById($id)
    {
        $where = "WHERE ID = " . (int)$id . " AND IsActive = 1";
        return $this->_getTableDetails($this->conn, 'resource_folders', $where);
    }

    public function getFolderBySlug($slug)
    {
        $where = "WHERE slug = '" . $this->conn->real_escape_string($slug) . "' AND IsActive = 1";
        return $this->_getTableDetails($this->conn, 'resource_folders', $where);
    }

    public function getAllFolders($parentId = null, $visibility = null)
    {
        $where = "WHERE IsActive = 1";
        
        if ($parentId !== null) {
            $where .= " AND parent_id = " . (int)$parentId;
        } else {
            $where .= " AND (parent_id IS NULL OR parent_id = 0)";
        }
        
        if ($visibility !== null) {
            $where .= " AND visibility = '" . $this->conn->real_escape_string($visibility) . "'";
        }
        
        $where .= " ORDER BY display_order ASC, folder_name ASC";
        
        return $this->_getTableRecords($this->conn, 'resource_folders', $where);
    }

    public function getPublicFolders($parentId = null)
    {
        return $this->getAllFolders($parentId, 'public');
    }

    public function getFolderPath($folderId)
    {
        $path = array();
        $currentId = $folderId;
        
        while ($currentId) {
            $folder = $this->getFolderById($currentId);
            if ($folder) {
                array_unshift($path, $folder);
                $currentId = $folder['parent_id'];
            } else {
                break;
            }
        }
        
        return $path;
    }

    // File CRUD Operations
    public function insertFile($data)
    {
        // Auto-detect file type if not provided
        if (empty($data['file_type']) && !empty($data['file_extension'])) {
            $data['file_type'] = $this->detectFileType($data['file_extension']);
        }
        
        // Set audio files as non-downloadable by default
        if ($data['file_type'] == 'audio' && !isset($data['is_downloadable'])) {
            $data['is_downloadable'] = 0;
        }
        
        return $this->_InsertTableRecords_prepare($this->conn, 'resource_files', $data);
    }

    public function updateFile($data, $id)
    {
        $where = "ID = " . (int)$id;
        return $this->_UpdateTableRecords_prepare($this->conn, 'resource_files', $data, $where);
    }

    public function deleteFile($id)
    {
        $where = "ID = " . (int)$id;
        $data = array('IsActive' => 0);
        return $this->_UpdateTableRecords_prepare($this->conn, 'resource_files', $data, $where);
    }

    public function getFileById($id)
    {
        $where = "WHERE ID = " . (int)$id . " AND IsActive = 1";
        return $this->_getTableDetails($this->conn, 'resource_files', $where);
    }

    public function getFilesByFolder($folderId, $fileType = null, $limit = null, $offset = null)
    {
        $where = "WHERE folder_id = " . (int)$folderId . " AND IsActive = 1";
        
        if ($fileType !== null) {
            $where .= " AND file_type = '" . $this->conn->real_escape_string($fileType) . "'";
        }
        
        $where .= " ORDER BY CreatedDate DESC, ID DESC";
        
        if ($limit !== null) {
            $where .= " LIMIT " . (int)$limit;
            if ($offset !== null) {
                $where .= " OFFSET " . (int)$offset;
            }
        }
        
        return $this->_getTableRecords($this->conn, 'resource_files', $where);
    }

    public function searchFiles($searchTerm, $folderId = null, $fileType = null)
    {
        $searchTerm = $this->conn->real_escape_string($searchTerm);
        $where = "WHERE IsActive = 1 AND (
            title LIKE '%$searchTerm%' OR 
            description LIKE '%$searchTerm%' OR
            file_name LIKE '%$searchTerm%' OR
            tags LIKE '%$searchTerm%'
        )";
        
        if ($folderId !== null) {
            $where .= " AND folder_id = " . (int)$folderId;
        }
        
        if ($fileType !== null) {
            $where .= " AND file_type = '" . $this->conn->real_escape_string($fileType) . "'";
        }
        
        $where .= " ORDER BY CreatedDate DESC";
        
        return $this->_getTableRecords($this->conn, 'resource_files', $where);
    }

    // Check folder access permission
    public function canAccessFolder($folderId, $userId = null)
    {
        $folder = $this->getFolderById($folderId);
        if (!$folder) {
            return false;
        }
        
        // Public folders are accessible to everyone
        if ($folder['visibility'] == 'public') {
            return true;
        }
        
        // Private folders require user login
        if ($folder['visibility'] == 'private') {
            return $userId !== null;
        }
        
        return false;
    }

    // Check file access permission
    public function canAccessFile($fileId, $userId = null)
    {
        $file = $this->getFileById($fileId);
        if (!$file) {
            return false;
        }
        
        // Check folder access
        return $this->canAccessFolder($file['folder_id'], $userId);
    }

    // Generate signed URL for private files
    public function generateSignedUrl($fileId, $expiresInMinutes = 10, $maxAccess = null)
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + ($expiresInMinutes * 60));
        
        $data = array(
            'file_id' => $fileId,
            'token' => $token,
            'expires_at' => $expiresAt,
            'max_access_count' => $maxAccess,
            'created_by' => $_SESSION['pp_email'] ?? $_SESSION['UserID'] ?? 'system',
            'created_at' => date('Y-m-d H:i:s'),
            'IsActive' => 1
        );
        
        $result = $this->_InsertTableRecords_prepare($this->conn, 'resource_signed_urls', $data);
        
        if (!$result['error']) {
            return $token;
        }
        
        return null;
    }

    // Validate signed URL token
    public function validateSignedUrl($token)
    {
        $where = "WHERE token = '" . $this->conn->real_escape_string($token) . "' AND IsActive = 1 AND expires_at > NOW()";
        $signedUrl = $this->_getTableDetails($this->conn, 'resource_signed_urls', $where);
        
        if ($signedUrl) {
            // Check max access count
            if ($signedUrl['max_access_count'] !== null && $signedUrl['access_count'] >= $signedUrl['max_access_count']) {
                return null;
            }
            
            // Increment access count
            $updateData = array('access_count' => $signedUrl['access_count'] + 1);
            $updateWhere = "ID = " . (int)$signedUrl['ID'];
            $this->_UpdateTableRecords_prepare($this->conn, 'resource_signed_urls', $updateData, $updateWhere);
            
            return $signedUrl;
        }
        
        return null;
    }

    // Log file access
    public function logFileAccess($fileId, $folderId = null, $accessType = 'view', $userId = null)
    {
        $file = $this->getFileById($fileId);
        if (!$file && !$folderId) {
            return false;
        }
        
        if (!$folderId) {
            $folderId = $file['folder_id'];
        }
        
        $data = array(
            'file_id' => $fileId,
            'folder_id' => $folderId,
            'user_id' => $userId,
            'user_email' => $_SESSION['pp_email'] ?? null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'access_type' => $accessType,
            'access_date' => date('Y-m-d'),
            'access_time' => date('H:i:s'),
            'access_datetime' => date('Y-m-d H:i:s')
        );
        
        $this->_InsertTableRecords_prepare($this->conn, 'resource_access_logs', $data);
        
        // Update file access count
        if ($file) {
            $updateData = array(
                'access_count' => ($file['access_count'] ?? 0) + 1,
                'last_accessed_at' => date('Y-m-d H:i:s')
            );
            $updateWhere = "ID = " . (int)$fileId;
            $this->_UpdateTableRecords_prepare($this->conn, 'resource_files', $updateData, $updateWhere);
        }
        
        return true;
    }

    // Get folder statistics
    public function getFolderStats($folderId)
    {
        $stats = array(
            'total_files' => 0,
            'total_size' => 0,
            'by_type' => array(
                'document' => 0,
                'image' => 0,
                'audio' => 0,
                'video' => 0,
                'archive' => 0,
                'other' => 0
            )
        );
        
        $where = "WHERE folder_id = " . (int)$folderId . " AND IsActive = 1";
        $files = $this->_getTableRecords($this->conn, 'resource_files', $where);
        
        foreach ($files as $file) {
            $stats['total_files']++;
            $stats['total_size'] += $file['file_size'] ?? 0;
            $fileType = $file['file_type'] ?? 'other';
            if (isset($stats['by_type'][$fileType])) {
                $stats['by_type'][$fileType]++;
            }
        }
        
        return $stats;
    }
}

