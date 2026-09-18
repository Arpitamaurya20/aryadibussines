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

// Function to upload image to local storage
function uploadImageLocal($file, $subfolder = 'uploads', $prefix = 'IMG') {
    $response = array('error' => false, 'url' => '', 'message' => '');
    
    // Allowed image extensions
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Validate file extension
    if (!in_array($file_ext, $allowedExts)) {
        return ['error' => true, 'message' => 'Only ' . implode(', ', $allowedExts) . ' image files are allowed.'];
    }
    
    // Validate file size (max 5MB)
    $maxSize = 5 * 1024 * 1024; // 5MB in bytes
    if ($file['size'] > $maxSize) {
        return ['error' => true, 'message' => 'File size exceeds 5MB limit.'];
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
        $response['message'] = 'Image uploaded successfully';
    } else {
        $response['error'] = true;
        $response['message'] = 'Failed to move uploaded file.';
    }
    
    return $response;
}

function getImageUrl($imagePath) {
    if (empty($imagePath)) return '';

    // 1. If full URL, return as is
    if (preg_match('/^(http|https):\/\//', $imagePath)) {
        return $imagePath;
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
    $cleanPath = ltrim($imagePath, '/.');

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

$workshop = new Workshop($conn);

switch ($action) {
    case 'save_workshop':
        if (isset($_POST['workshop_title'])) {
            $data = array();
            
            // Basic fields
            $data['workshop_title'] = $_POST['workshop_title'] ?? '';
            $data['slug'] = $_POST['slug'] ?? '';
            $data['short_description'] = $_POST['short_description'] ?? '';
            $data['detailed_description'] = $_POST['detailed_description'] ?? '';
            $data['category_id'] = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
            $data['tags'] = $_POST['tags'] ?? '';
            
            // Session Type & Mode
            $data['workshop_mode'] = $_POST['workshop_mode'] ?? 'online';
            $data['session_type'] = $_POST['session_type'] ?? 'single';
            $data['language'] = $_POST['language'] ?? 'English';
            $data['level'] = $_POST['level'] ?? 'beginner';
            
            // Online Session Fields
            $data['meeting_platform'] = $_POST['meeting_platform'] ?? '';
            $data['meeting_link'] = $_POST['meeting_link'] ?? '';
            $data['meeting_id'] = $_POST['meeting_id'] ?? '';
            $data['meeting_passcode'] = $_POST['meeting_passcode'] ?? '';
            $data['auto_send_meeting_link'] = isset($_POST['auto_send_meeting_link']) ? 1 : 0;
            
            // Offline Session Fields
            $data['venue_name'] = $_POST['venue_name'] ?? '';
            $data['venue_address'] = $_POST['venue_address'] ?? '';
            $data['city'] = $_POST['city'] ?? '';
            $data['google_map_link'] = $_POST['google_map_link'] ?? '';
            
            // Schedule
            $data['session_date'] = !empty($_POST['session_date']) ? $_POST['session_date'] : null;
            $data['start_time'] = !empty($_POST['start_time']) ? $_POST['start_time'] : null;
            $data['end_time'] = !empty($_POST['end_time']) ? $_POST['end_time'] : null;
            $data['time_zone'] = $_POST['time_zone'] ?? 'IST';
            $data['is_recurring'] = isset($_POST['is_recurring']) ? 1 : 0;
            $data['recurring_pattern'] = $_POST['recurring_pattern'] ?? '';
            $data['recurring_end_date'] = !empty($_POST['recurring_end_date']) ? $_POST['recurring_end_date'] : null;
            
            // Capacity & Registration
            $data['total_seats'] = !empty($_POST['total_seats']) ? (int)$_POST['total_seats'] : null;
            $data['show_seat_availability'] = isset($_POST['show_seat_availability']) ? 1 : 0;
            $data['auto_close_registration'] = isset($_POST['auto_close_registration']) ? 1 : 0;
            $data['registration_deadline'] = !empty($_POST['registration_deadline']) ? date('Y-m-d H:i:s', strtotime($_POST['registration_deadline'])) : null;
            $data['allow_waiting_list'] = isset($_POST['allow_waiting_list']) ? 1 : 0;
            $data['waiting_list_capacity'] = !empty($_POST['waiting_list_capacity']) ? (int)$_POST['waiting_list_capacity'] : 0;
            
            // Pricing & Payment
            $data['pricing_type'] = $_POST['pricing_type'] ?? 'free';
            $data['price'] = !empty($_POST['price']) ? (float)$_POST['price'] : 0.00;
            $data['discount_type'] = (!empty($_POST['discount_type']) && in_array($_POST['discount_type'], ['percent', 'flat'])) ? $_POST['discount_type'] : null;
            $data['discount_value'] = !empty($_POST['discount_value']) ? (float)$_POST['discount_value'] : 0.00;
            $data['discount_start_date'] = !empty($_POST['discount_start_date']) ? $_POST['discount_start_date'] : null;
            $data['discount_end_date'] = !empty($_POST['discount_end_date']) ? $_POST['discount_end_date'] : null;
            $data['coupon_code_support'] = isset($_POST['coupon_code_support']) ? 1 : 0;
            $data['payment_gateway'] = $_POST['payment_gateway'] ?? '';
            
            // Certification
            $data['provide_certificate'] = isset($_POST['provide_certificate']) ? 1 : 0;
            $data['auto_generate_certificate'] = isset($_POST['auto_generate_certificate']) ? 1 : 0;
            
            // Automation & Notifications
            $data['email_notification'] = isset($_POST['email_notification']) ? 1 : 0;
            $data['sms_notification'] = isset($_POST['sms_notification']) ? 1 : 0;
            $data['whatsapp_notification'] = isset($_POST['whatsapp_notification']) ? 1 : 0;
            $data['reminder_before_session'] = !empty($_POST['reminder_before_session']) ? (int)$_POST['reminder_before_session'] : null;
            $data['reminder_unit'] = $_POST['reminder_unit'] ?? 'hours';
            $data['post_session_feedback'] = isset($_POST['post_session_feedback']) ? 1 : 0;
            
            // SEO fields
            $data['meta_title'] = $_POST['meta_title'] ?? '';
            $data['meta_description'] = $_POST['meta_description'] ?? '';
            $data['meta_keywords'] = $_POST['meta_keywords'] ?? '';
            
            // Status and settings
            $data['status'] = $_POST['status'] ?? 'draft';
            $data['show_on_homepage'] = isset($_POST['show_on_homepage']) ? 1 : 0;
            $data['featured'] = isset($_POST['featured']) ? 1 : 0;
            
            // Handle file uploads
            if (isset($_FILES['thumbnail_image_file']) && !empty($_FILES['thumbnail_image_file']['name']) && $_FILES['thumbnail_image_file']['error'] == 0) {
                $uploadResult = uploadImageLocal($_FILES['thumbnail_image_file'], 'workshop/images', 'WORKSHOP');
                if (!$uploadResult['error'] && !empty($uploadResult['url'])) {
                    $data['thumbnail_image'] = $uploadResult['url'];
                }
            } elseif (!empty($_POST['thumbnail_image'])) {
                $data['thumbnail_image'] = $_POST['thumbnail_image'];
            }
            
            if (isset($_FILES['og_image_file']) && !empty($_FILES['og_image_file']['name']) && $_FILES['og_image_file']['error'] == 0) {
                $uploadResult = uploadImageLocal($_FILES['og_image_file'], 'workshop/og', 'OG');
                if (!$uploadResult['error'] && !empty($uploadResult['url'])) {
                    $data['og_image'] = $uploadResult['url'];
                }
            } elseif (!empty($_POST['og_image'])) {
                $data['og_image'] = $_POST['og_image'];
            }
            
            // Handle gallery images
            if (isset($_FILES['gallery_images']) && count($_FILES['gallery_images']['name']) > 0) {
                $galleryUrls = array();
                foreach ($_FILES['gallery_images']['name'] as $key => $name) {
                    if (!empty($name) && $_FILES['gallery_images']['error'][$key] == 0) {
                        $file = array(
                            'name' => $_FILES['gallery_images']['name'][$key],
                            'type' => $_FILES['gallery_images']['type'][$key],
                            'tmp_name' => $_FILES['gallery_images']['tmp_name'][$key],
                            'error' => $_FILES['gallery_images']['error'][$key],
                            'size' => $_FILES['gallery_images']['size'][$key]
                        );
                        $uploadResult = uploadImageLocal($file, 'workshop/gallery', 'GALLERY');
                        if (!$uploadResult['error'] && !empty($uploadResult['url'])) {
                            $galleryUrls[] = $uploadResult['url'];
                        }
                    }
                }
                if (!empty($galleryUrls)) {
                    $existingGallery = !empty($_POST['gallery_images_existing']) ? json_decode($_POST['gallery_images_existing'], true) : array();
                    $data['gallery_images'] = json_encode(array_merge($existingGallery, $galleryUrls));
                } elseif (!empty($_POST['gallery_images_existing'])) {
                    $data['gallery_images'] = $_POST['gallery_images_existing'];
                }
            } elseif (!empty($_POST['gallery_images_existing'])) {
                $data['gallery_images'] = $_POST['gallery_images_existing'];
            }
            
            // Handle certificate template upload
            if (isset($_FILES['certificate_template_file']) && !empty($_FILES['certificate_template_file']['name']) && $_FILES['certificate_template_file']['error'] == 0) {
                // Allow PDF files for certificate template
                $allowedExts = ['pdf'];
                $file_ext = strtolower(pathinfo($_FILES['certificate_template_file']['name'], PATHINFO_EXTENSION));
                if (in_array($file_ext, $allowedExts)) {
                    $uploadDir = __DIR__ . '/../../uploads/workshop/certificates/';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $filename = 'CERT_' . time() . '_' . uniqid() . '.' . $file_ext;
                    $targetPath = $uploadDir . $filename;
                    if (move_uploaded_file($_FILES['certificate_template_file']['tmp_name'], $targetPath)) {
                        $data['certificate_template'] = 'uploads/workshop/certificates/' . $filename;
                    }
                }
            } elseif (!empty($_POST['certificate_template'])) {
                $data['certificate_template'] = $_POST['certificate_template'];
            }
            
            // Set created/updated info
            $formAction = $_POST['workshop_form_action'] ?? 'add';
            $workshopId = $_POST['workshop_form_id'] ?? '';
            
            if ($formAction == 'add') {
                $data['CreatedDate'] = date('Y-m-d');
                $data['CreatedTime'] = date('H:i:s');
                $data['CreatedBy'] = $_SESSION['pp_email'] ?? $_SESSION['UserID'];
                
                $result = $workshop->insertWorkshop($data);
                if ($result['error'] == false) {
                    $response['message'] = 'Workshop created successfully!';
                    $response['workshop_id'] = $result['last_insert_id'] ?? null;
                } else {
                    $response['error'] = true;
                    $response['message'] = $result['message'] ?? 'Failed to create workshop';
                }
            } else {
                // For update, preserve existing images if no new images are uploaded
                $existingWorkshop = $workshop->getWorkshopById($workshopId);
                if ($existingWorkshop) {
                    if (empty($data['thumbnail_image']) && !empty($existingWorkshop['thumbnail_image'])) {
                        $data['thumbnail_image'] = $existingWorkshop['thumbnail_image'];
                    }
                    if (empty($data['og_image']) && !empty($existingWorkshop['og_image'])) {
                        $data['og_image'] = $existingWorkshop['og_image'];
                    }
                    if (empty($data['gallery_images']) && !empty($existingWorkshop['gallery_images'])) {
                        $data['gallery_images'] = $existingWorkshop['gallery_images'];
                    }
                    if (empty($data['certificate_template']) && !empty($existingWorkshop['certificate_template'])) {
                        $data['certificate_template'] = $existingWorkshop['certificate_template'];
                    }
                }
                
                $data['ModifiedDate'] = date('Y-m-d');
                $data['ModifiedTime'] = date('H:i:s');
                $data['ModifiedBy'] = $_SESSION['pp_email'] ?? $_SESSION['UserID'];
                
                $result = $workshop->updateWorkshop($data, $workshopId);
                if ($result['error'] == false) {
                    $response['message'] = 'Workshop updated successfully!';
                } else {
                    $response['error'] = true;
                    $response['message'] = $result['message'] ?? 'Failed to update workshop';
                }
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'Workshop title is required';
        }
        break;
        
    case 'get_workshop':
        $workshopId = $_POST['workshop_id'] ?? 0;
        if ($workshopId > 0) {
            $workshopData = $workshop->getWorkshopById($workshopId);
            if ($workshopData) {
                $response['data'] = $workshopData;
                $response['message'] = 'Workshop retrieved successfully';
            } else {
                $response['error'] = true;
                $response['message'] = 'Workshop not found';
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'Invalid workshop ID';
        }
        break;
        
    case 'delete_workshop':
        $workshopId = $_POST['workshop_id'] ?? 0;
        if ($workshopId > 0) {
            $result = $workshop->deleteWorkshop($workshopId);
            if ($result) {
                $response['message'] = 'Workshop deleted successfully';
            } else {
                $response['error'] = true;
                $response['message'] = 'Failed to delete workshop';
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'Invalid workshop ID';
        }
        break;
        
    case 'get_workshops_datatable':
        // Get workshops for DataTables AJAX
        $allWorkshops = $workshop->getAllWorkshops();
        $data = array();
        
        foreach ($allWorkshops as $workshopItem) {
            $categoryName = 'Uncategorized';
            if ($workshopItem['category_id']) {
                $category = $workshop->getWorkshopCategoryById($workshopItem['category_id']);
                if ($category) {
                    $categoryName = $category['category_name'];
                }
            }
            
            $statusBadge = '';
            switch ($workshopItem['status']) {
                case 'published':
                    $statusBadge = '<span class="badge bg-success">Published</span>';
                    break;
                case 'draft':
                    $statusBadge = '<span class="badge bg-secondary">Draft</span>';
                    break;
                case 'unlisted':
                    $statusBadge = '<span class="badge bg-warning">Unlisted</span>';
                    break;
            }
            
            $modeBadge = '';
            switch ($workshopItem['workshop_mode']) {
                case 'online':
                    $modeBadge = '<span class="badge bg-info">Online</span>';
                    break;
                case 'offline':
                    $modeBadge = '<span class="badge bg-primary">Offline</span>';
                    break;
                case 'hybrid':
                    $modeBadge = '<span class="badge bg-warning">Hybrid</span>';
                    break;
            }
            
            $sessionDate = $workshopItem['session_date'] ? date('d M Y', strtotime($workshopItem['session_date'])) : 'Not Scheduled';
            
            $titleHtml = htmlspecialchars($workshopItem['workshop_title']);
            if ($workshopItem['featured']) {
                $titleHtml .= ' <span class="badge bg-primary">Featured</span>';
            }
            
            $actionsHtml = '
                <button class="btn btn-sm btn-info me-1" onclick="EditWorkshop(' . $workshopItem['ID'] . ')" title="Edit">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-danger me-1" onclick="DeleteWorkshop(' . $workshopItem['ID'] . ')" title="Delete">
                    <i class="bi bi-trash"></i>
                </button>
                <a href="../workshop-frontend/workshop-detail.php?slug=' . htmlspecialchars($workshopItem['slug']) . '" target="_blank" class="btn btn-sm btn-success" title="View">
                    <i class="bi bi-eye"></i>
                </a>
            ';
            
            $data[] = array(
                'ID' => $workshopItem['ID'],
                'title' => $titleHtml,
                'category' => htmlspecialchars($categoryName),
                'mode' => $modeBadge,
                'status' => $statusBadge,
                'session_date' => $sessionDate,
                'registrations' => $workshopItem['total_registrations'] ?? 0,
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
        
    case 'save_category':
        if (isset($_POST['category_name'])) {
            $data = array();
            $data['category_name'] = $_POST['category_name'] ?? '';
            $data['category_description'] = $_POST['category_description'] ?? '';
            
            $formAction = $_POST['category_form_action'] ?? 'add';
            $categoryId = $_POST['category_form_id'] ?? '';
            
            // Handle category image upload
            $imageUploaded = false;
            if (isset($_FILES['category_image_file']) && !empty($_FILES['category_image_file']['name']) && $_FILES['category_image_file']['error'] == 0) {
                $uploadResult = uploadImageLocal($_FILES['category_image_file'], 'workshop/categories', 'CATEGORY');
                if (!$uploadResult['error'] && !empty($uploadResult['url'])) {
                    $data['category_image'] = $uploadResult['url'];
                    $imageUploaded = true;
                }
            }
            
            // Preserve existing image if updating and no new image uploaded
            if (!$imageUploaded && $formAction == 'edit') {
                $existingCategory = $workshop->getWorkshopCategoryById($categoryId);
                if ($existingCategory && !empty($existingCategory['category_image'])) {
                    $data['category_image'] = $existingCategory['category_image'];
                } elseif (!empty($_POST['category_image'])) {
                    $data['category_image'] = $_POST['category_image'];
                }
            }
            
            if (!isset($data['category_image'])) {
                $data['category_image'] = null;
            }
            
            if ($formAction == 'add') {
                $data['IsActive'] = 1;
                $data['CreatedDate'] = date('Y-m-d');
                $data['CreatedTime'] = date('H:i:s');
                $data['CreatedBy'] = $_SESSION['pp_email'] ?? $_SESSION['UserID'];
                
                $result = $workshop->insertWorkshopCategory($data);
                if ($result['error'] == false) {
                    $response['message'] = 'Category created successfully!';
                    $response['category_id'] = $result['last_insert_id'] ?? null;
                } else {
                    $response['error'] = true;
                    $response['message'] = $result['message'] ?? 'Failed to create category';
                }
            } else {
                $data['ModifiedDate'] = date('Y-m-d');
                $data['ModifiedTime'] = date('H:i:s');
                $data['ModifiedBy'] = $_SESSION['pp_email'] ?? $_SESSION['UserID'];
                
                $result = $workshop->updateWorkshopCategory($data, $categoryId);
                if ($result['error'] == false) {
                    $response['message'] = 'Category updated successfully!';
                } else {
                    $response['error'] = true;
                    $response['message'] = $result['message'] ?? 'Failed to update category';
                }
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'Category name is required';
        }
        break;
        
    case 'get_category':
        $categoryId = $_POST['category_id'] ?? 0;
        if ($categoryId > 0) {
            $categoryData = $workshop->getWorkshopCategoryById($categoryId);
            if ($categoryData) {
                $response['data'] = $categoryData;
                $response['message'] = 'Category retrieved successfully';
            } else {
                $response['error'] = true;
                $response['message'] = 'Category not found';
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'Invalid category ID';
        }
        break;
        
    case 'delete_category':
        $categoryId = $_POST['category_id'] ?? 0;
        if ($categoryId > 0) {
            $result = $workshop->deleteWorkshopCategory($categoryId);
            if ($result) {
                $response['message'] = 'Category deleted successfully';
            } else {
                $response['error'] = true;
                $response['message'] = 'Failed to delete category';
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'Invalid category ID';
        }
        break;
        
    case 'get_categories_datatable':
        // Get categories for DataTables AJAX
        $allCategories = $workshop->getAllWorkshopCategories();
        $data = array();
        
        foreach ($allCategories as $category) {
            $imageHtml = '';
            $categoryImage = $category['category_image'] ?? '';
            if (!empty($categoryImage)) {
                $imageUrl = getImageUrl($categoryImage);
                $imageHtml = '<img src="' . htmlspecialchars($imageUrl) . '" alt="' . htmlspecialchars($category['category_name']) . '" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px; cursor: pointer;" onclick="window.open(\'' . htmlspecialchars($imageUrl) . '\', \'_blank\')">';
            } else {
                $imageHtml = '<span class="badge bg-secondary">No Image</span>';
            }
            
            $actionsHtml = '
                <button class="btn btn-sm btn-info me-1" onclick="EditCategory(' . $category['ID'] . ')" title="Edit">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="DeleteCategory(' . $category['ID'] . ')" title="Delete">
                    <i class="bi bi-trash"></i>
                </button>
            ';
            
            $data[] = array(
                'ID' => $category['ID'],
                'image' => $imageHtml,
                'category_name' => htmlspecialchars($category['category_name']),
                'category_slug' => htmlspecialchars($category['category_slug']),
                'category_description' => htmlspecialchars(substr($category['category_description'] ?? '', 0, 50)) . '...',
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
        
    case 'get_registrations_datatable':
        // Get registrations for DataTables AJAX
        $workshopId = $_POST['workshop_id'] ?? null;
        $paymentStatus = $_POST['payment_status'] ?? null;
        $attended = $_POST['attended'] ?? null;
        
        $allRegistrations = array();
        
        if ($workshopId) {
            $allRegistrations = $workshop->getWorkshopRegistrations($workshopId);
        } else {
            // Get all registrations
            $where = "WHERE IsActive = 1";
            if ($paymentStatus) {
                $where .= " AND payment_status = '" . $conn->real_escape_string($paymentStatus) . "'";
            }
            if ($attended !== null && $attended !== '') {
                $where .= " AND attended = " . (int)$attended;
            }
            $where .= " ORDER BY registration_time DESC";
            $allRegistrations = $core->_getTableRecords($conn, 'workshop_registrations', $where);
        }
        
        $data = array();
        
        foreach ($allRegistrations as $registration) {
            // Get workshop details
            $workshopDetails = $workshop->getWorkshopById($registration['workshop_id']);
            $workshopTitle = $workshopDetails ? $workshopDetails['workshop_title'] : 'Unknown Workshop';
            
            // Payment status badge
            $paymentStatusBadge = '';
            switch ($registration['payment_status']) {
                case 'pending':
                    $paymentStatusBadge = '<span class="badge bg-warning">Pending</span>';
                    break;
                case 'paid':
                    $paymentStatusBadge = '<span class="badge bg-success">Paid</span>';
                    break;
                case 'failed':
                    $paymentStatusBadge = '<span class="badge bg-danger">Failed</span>';
                    break;
                case 'refunded':
                    $paymentStatusBadge = '<span class="badge bg-secondary">Refunded</span>';
                    break;
            }
            
            // Attendance badge
            $attendedBadge = $registration['attended'] == 1 
                ? '<span class="badge bg-success">Yes</span>' 
                : '<span class="badge bg-secondary">No</span>';
            
            // Amount
            $amount = '₹0.00';
            if (!empty($registration['final_amount'])) {
                $amount = '₹' . number_format($registration['final_amount'], 2);
            } elseif (!empty($registration['payment_amount'])) {
                $amount = '₹' . number_format($registration['payment_amount'], 2);
            }
            
            // Registration date
            $regDate = $registration['registration_time'] 
                ? date('d M Y, h:i A', strtotime($registration['registration_time'])) 
                : 'N/A';
            
            $actionsHtml = '
                <button class="btn btn-sm btn-info me-1" onclick="ViewRegistration(' . $registration['ID'] . ')" title="View Details">
                    <i class="bi bi-eye"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="DeleteRegistration(' . $registration['ID'] . ')" title="Delete">
                    <i class="bi bi-trash"></i>
                </button>
            ';
            
            $data[] = array(
                'ID' => $registration['ID'],
                'workshop_title' => htmlspecialchars($workshopTitle),
                'participant_name' => htmlspecialchars($registration['participant_name']),
                'participant_email' => htmlspecialchars($registration['participant_email']),
                'participant_mobile' => htmlspecialchars($registration['participant_mobile'] ?? ''),
                'participant_city' => htmlspecialchars($registration['participant_city'] ?? ''),
                'payment_status' => $paymentStatusBadge,
                'amount' => $amount,
                'registration_date' => $regDate,
                'attended' => $attendedBadge,
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
        
    case 'get_registration':
        $registrationId = $_POST['registration_id'] ?? 0;
        if ($registrationId > 0) {
            $registrationData = $workshop->getRegistrationById($registrationId);
            if ($registrationData) {
                // Get workshop details
                $workshopDetails = $workshop->getWorkshopById($registrationData['workshop_id']);
                if ($workshopDetails) {
                    $registrationData['workshop_title'] = $workshopDetails['workshop_title'];
                }
                $response['data'] = $registrationData;
                $response['message'] = 'Registration retrieved successfully';
            } else {
                $response['error'] = true;
                $response['message'] = 'Registration not found';
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'Invalid registration ID';
        }
        break;
        
    case 'update_registration':
        $registrationId = $_POST['registration_id'] ?? 0;
        if ($registrationId > 0) {
            $data = array();
            
            if (isset($_POST['payment_status'])) {
                $data['payment_status'] = $_POST['payment_status'];
            }
            if (isset($_POST['transaction_id'])) {
                $data['transaction_id'] = $_POST['transaction_id'];
            }
            if (isset($_POST['payment_amount'])) {
                $data['payment_amount'] = (float)$_POST['payment_amount'];
                $data['final_amount'] = (float)$_POST['payment_amount'];
            }
            if (isset($_POST['payment_date']) && !empty($_POST['payment_date'])) {
                $data['payment_date'] = $_POST['payment_date'];
            }
            if (isset($_POST['attended'])) {
                $data['attended'] = (int)$_POST['attended'];
                if ($data['attended'] == 1 && empty($data['attendance_time'])) {
                    $data['attendance_time'] = date('Y-m-d H:i:s');
                }
            }
            if (isset($_POST['feedback_rating'])) {
                $data['feedback_rating'] = !empty($_POST['feedback_rating']) ? (int)$_POST['feedback_rating'] : null;
            }
            if (isset($_POST['feedback_comment'])) {
                $data['feedback_comment'] = $_POST['feedback_comment'];
            }
            if (isset($_POST['certificate_issued'])) {
                $data['certificate_issued'] = (int)$_POST['certificate_issued'];
                if ($data['certificate_issued'] == 1 && empty($data['certificate_issued_at'])) {
                    $data['certificate_issued_at'] = date('Y-m-d H:i:s');
                }
            }
            
            $result = $workshop->updateWorkshopRegistration($data, $registrationId);
            if ($result) {
                $response['message'] = 'Registration updated successfully';
            } else {
                $response['error'] = true;
                $response['message'] = 'Failed to update registration';
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'Invalid registration ID';
        }
        break;
        
    case 'delete_registration':
        $registrationId = $_POST['registration_id'] ?? 0;
        if ($registrationId > 0) {
            $where = "ID = " . (int)$registrationId;
            $data = array('IsActive' => 0);
            $result = $core->_UpdateTableRecords_prepare($conn, 'workshop_registrations', $data, $where);
            if ($result) {
                $response['message'] = 'Registration deleted successfully';
            } else {
                $response['error'] = true;
                $response['message'] = 'Failed to delete registration';
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'Invalid registration ID';
        }
        break;
        
    case 'register_workshop':
        // Handle workshop registration from frontend
        $workshopId = isset($_POST['workshop_id']) ? (int)$_POST['workshop_id'] : 0;
        
        if ($workshopId <= 0) {
            $response['error'] = true;
            $response['message'] = 'Invalid workshop ID';
            break;
        }
        
        // Check if registration is open
        if (!$workshop->isRegistrationOpen($workshopId)) {
            $response['error'] = true;
            $response['message'] = 'Registration is closed for this workshop.';
            break;
        }
        
        // Get workshop details for pricing
        $workshopDetails = $workshop->getWorkshopById($workshopId);
        if (!$workshopDetails) {
            $response['error'] = true;
            $response['message'] = 'Workshop not found.';
            break;
        }
        
        // Prepare registration data
        $registrationData = array(
            'workshop_id' => $workshopId,
            'participant_name' => trim($_POST['participant_name'] ?? ''),
            'participant_email' => trim($_POST['participant_email'] ?? ''),
            'participant_mobile' => trim($_POST['participant_mobile'] ?? ''),
            'participant_city' => trim($_POST['participant_city'] ?? ''),
            'participant_age' => !empty($_POST['participant_age']) ? (int)$_POST['participant_age'] : null,
            'registration_source' => 'website',
            'payment_status' => $workshopDetails['pricing_type'] == 'free' ? 'paid' : 'pending',
            'payment_amount' => $workshopDetails['pricing_type'] == 'paid' ? (float)$workshopDetails['price'] : 0.00,
            'final_amount' => $workshopDetails['pricing_type'] == 'paid' ? (float)$workshopDetails['price'] : 0.00
        );
        
        // Validate required fields
        if (empty($registrationData['participant_name']) || empty($registrationData['participant_email'])) {
            $response['error'] = true;
            $response['message'] = 'Name and email are required fields.';
            break;
        }
        
        // Validate email format
        if (!filter_var($registrationData['participant_email'], FILTER_VALIDATE_EMAIL)) {
            $response['error'] = true;
            $response['message'] = 'Please enter a valid email address.';
            break;
        }
        
        // Check if already registered
        $where = "WHERE workshop_id = " . $workshopId . " AND participant_email = '" . $conn->real_escape_string($registrationData['participant_email']) . "' AND IsActive = 1";
        $existing = $core->_getTableRecords($conn, 'workshop_registrations', $where);
        if (!empty($existing)) {
            $response['error'] = true;
            $response['message'] = 'You are already registered for this workshop.';
            break;
        }
        
        // Insert registration
        $result = $workshop->insertWorkshopRegistration($registrationData);
        
        if ($result && !$result['error']) {
            $response['error'] = false;
            $response['message'] = 'Registration successful! We will contact you soon with further details.';
            $response['registration_id'] = $result['insert_id'] ?? null;
            
            // If it's a paid workshop, you might want to redirect to payment gateway here
            if ($workshopDetails['pricing_type'] == 'paid') {
                $response['requires_payment'] = true;
                $response['payment_amount'] = $workshopDetails['price'];
            }
        } else {
            $response['error'] = true;
            $response['message'] = $result['message'] ?? 'Registration failed. Please try again.';
        }
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

