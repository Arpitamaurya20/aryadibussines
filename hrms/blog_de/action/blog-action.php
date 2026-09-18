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

    $host = $_SERVER['HTTP_HOST']; // localhost or domain.com
    
    // Get folder after DOCUMENT_ROOT
    $rootPath = realpath($_SERVER['DOCUMENT_ROOT']);   // C:/wamp64/www
    $currentPath = realpath(__DIR__);                  // e.g. C:/wamp64/www/projects/ZeltoHub/admin/blog/action
    $projectFolder = trim(str_replace($rootPath, '', $currentPath), '\\/');

    // Extract only main folder (projects/ZeltoHub)
    $parts = explode(DIRECTORY_SEPARATOR, $projectFolder);
    $topFolder = $parts[0];         // projects
    $secondFolder = $parts[1] ?? ''; // ZeltoHub

    $baseUrl = $protocol . $host . "/" . $topFolder . "/" . $secondFolder . "/";

    // 3. Clean the image path
    $cleanPath = ltrim($imagePath, '/.');

    // 4. Return final URL
    return $baseUrl . $cleanPath;
}


if (!isset($_SESSION['UserID'])) {
    $response['error'] = true;
    $response['message'] = 'Unauthorized access';
    echo json_encode($response);
    exit;
}

$action = $_POST['action'] ?? '';

$blog = new Blog($conn);

switch ($action) {
    case 'save_blog':
        if (isset($_POST['title'])) {
            $data = array();
            
            // Basic fields
            $data['title'] = $_POST['title'] ?? '';
            $data['slug'] = $_POST['slug'] ?? '';
            $data['short_description'] = $_POST['short_description'] ?? '';
            $data['full_content'] = $_POST['full_content'] ?? '';
            $data['category_id'] = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
            $data['tags'] = $_POST['tags'] ?? '';
            $data['author_name'] = $_POST['author_name'] ?? '';
            $data['author_bio'] = $_POST['author_bio'] ?? '';
            $data['youtube_video_url'] = $_POST['youtube_video_url'] ?? '';
            $data['embedded_video'] = $_POST['embedded_video'] ?? '';
            
            // Status and settings
            $data['status'] = $_POST['status'] ?? 'draft';
            $data['publish_date'] = !empty($_POST['publish_date']) ? date('Y-m-d H:i:s', strtotime($_POST['publish_date'])) : null;
            $data['pin_to_top'] = isset($_POST['pin_to_top']) ? 1 : 0;
            $data['allow_comments'] = isset($_POST['allow_comments']) ? 1 : 0;
            $data['featured'] = isset($_POST['featured']) ? 1 : 0;
            $data['trending'] = isset($_POST['trending']) ? 1 : 0;
            
            // SEO fields
            $data['meta_title'] = $_POST['meta_title'] ?? '';
            $data['meta_description'] = $_POST['meta_description'] ?? '';
            $data['meta_keywords'] = $_POST['meta_keywords'] ?? '';
            $data['focus_keyword'] = $_POST['focus_keyword'] ?? '';
            $data['canonical_url'] = $_POST['canonical_url'] ?? '';
            $data['redirect_url'] = $_POST['redirect_url'] ?? '';
            
            // Social media fields
            $data['og_title'] = $_POST['og_title'] ?? '';
            $data['og_description'] = $_POST['og_description'] ?? '';
            $data['twitter_card_title'] = $_POST['twitter_card_title'] ?? '';
            $data['twitter_card_description'] = $_POST['twitter_card_description'] ?? '';
            
            // Handle file uploads (Local Storage)
            if (isset($_FILES['featured_image_file']) && !empty($_FILES['featured_image_file']['name']) && $_FILES['featured_image_file']['error'] == 0) {
                $uploadResult = uploadImageLocal($_FILES['featured_image_file'], 'blog/images', 'BLOG');
                if (!$uploadResult['error'] && !empty($uploadResult['url'])) {
                    $data['featured_image'] = $uploadResult['url'];
                }
            } elseif (!empty($_POST['featured_image'])) {
                $data['featured_image'] = $_POST['featured_image'];
            }

            // Featured Image - Mobile
                if (
                    isset($_FILES['featured_image_mobile_file']) &&
                    !empty($_FILES['featured_image_mobile_file']['name']) &&
                    $_FILES['featured_image_mobile_file']['error'] == 0
                ) {
                    $uploadResult = uploadImageLocal(
                        $_FILES['featured_image_mobile_file'],
                        'blog/images/mobile',
                        'BLOG_MOBILE'
                    );

                    if (!$uploadResult['error'] && !empty($uploadResult['url'])) {
                        $data['featured_image_mobile'] = $uploadResult['url'];
                    }
                } elseif (!empty($_POST['featured_image_mobile'])) {
                    $data['featured_image_mobile'] = $_POST['featured_image_mobile'];
                }

            
            if (isset($_FILES['author_image_file']) && !empty($_FILES['author_image_file']['name']) && $_FILES['author_image_file']['error'] == 0) {
                $uploadResult = uploadImageLocal($_FILES['author_image_file'], 'blog/authors', 'AUTHOR');
                if (!$uploadResult['error'] && !empty($uploadResult['url'])) {
                    $data['author_image'] = $uploadResult['url'];
                }
            } elseif (!empty($_POST['author_image'])) {
                $data['author_image'] = $_POST['author_image'];
            }
            
            if (isset($_FILES['og_image_file']) && !empty($_FILES['og_image_file']['name']) && $_FILES['og_image_file']['error'] == 0) {
                $uploadResult = uploadImageLocal($_FILES['og_image_file'], 'blog/og', 'OG');
                if (!$uploadResult['error'] && !empty($uploadResult['url'])) {
                    $data['og_image'] = $uploadResult['url'];
                }
            } elseif (!empty($_POST['og_image'])) {
                $data['og_image'] = $_POST['og_image'];
            }
            
            if (isset($_FILES['twitter_card_image_file']) && !empty($_FILES['twitter_card_image_file']['name']) && $_FILES['twitter_card_image_file']['error'] == 0) {
                $uploadResult = uploadImageLocal($_FILES['twitter_card_image_file'], 'blog/twitter', 'TWITTER');
                if (!$uploadResult['error'] && !empty($uploadResult['url'])) {
                    $data['twitter_card_image'] = $uploadResult['url'];
                }
            } elseif (!empty($_POST['twitter_card_image'])) {
                $data['twitter_card_image'] = $_POST['twitter_card_image'];
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
                        $uploadResult = uploadImageLocal($file, 'blog/gallery', 'GALLERY');
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
            
            // Set created/updated info
            $formAction = $_POST['blog_form_action'] ?? 'add';
            $blogId = $_POST['blog_form_id'] ?? '';
            
            if ($formAction == 'add') {
                $data['CreatedDate'] = date('Y-m-d');
                $data['CreatedTime'] = date('H:i:s');
                $data['CreatedBy'] = $_SESSION['pp_email'] ?? $_SESSION['UserID'];
                
                $result = $blog->insertBlogPost($data);
                if ($result['error'] == false) {
                    $response['message'] = 'Blog post created successfully!';
                    $response['blog_id'] = $result['last_insert_id'] ?? null;
                } else {
                    $response['error'] = true;
                    $response['message'] = $result['message'] ?? 'Failed to create blog post';
                }
            } else {
                // For update, preserve existing images if no new images are uploaded
                $existingPost = $blog->getBlogPostById($blogId);
                if ($existingPost) {
                    // Preserve featured image if not uploaded
                    if (empty($data['featured_image']) && !empty($existingPost['featured_image'])) {
                        $data['featured_image'] = $existingPost['featured_image'];
                    }
                    if (
                            empty($data['featured_image_mobile']) &&
                            !empty($existing['featured_image_mobile'])
                        ) {
                            $data['featured_image_mobile'] = $existing['featured_image_mobile'];
                        }
                    // Preserve author image if not uploaded
                    if (empty($data['author_image']) && !empty($existingPost['author_image'])) {
                        $data['author_image'] = $existingPost['author_image'];
                    }
                    // Preserve OG image if not uploaded
                    if (empty($data['og_image']) && !empty($existingPost['og_image'])) {
                        $data['og_image'] = $existingPost['og_image'];
                    }
                    // Preserve Twitter card image if not uploaded
                    if (empty($data['twitter_card_image']) && !empty($existingPost['twitter_card_image'])) {
                        $data['twitter_card_image'] = $existingPost['twitter_card_image'];
                    }
                    // Preserve gallery images if not uploaded
                    if (empty($data['gallery_images']) && !empty($existingPost['gallery_images'])) {
                        $data['gallery_images'] = $existingPost['gallery_images'];
                    }
                }
                
                $data['ModifiedDate'] = date('Y-m-d');
                $data['ModifiedTime'] = date('H:i:s');
                $data['ModifiedBy'] = $_SESSION['pp_email'] ?? $_SESSION['UserID'];
                
                $result = $blog->updateBlogPost($data, $blogId);
                if ($result['error'] == false) {
                    $response['message'] = 'Blog post updated successfully!';
                } else {
                    $response['error'] = true;
                    $response['message'] = $result['message'] ?? 'Failed to update blog post';
                }
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'Title is required';
        }
        break;
        
    case 'get_blog':
        $blogId = $_POST['blog_id'] ?? 0;
        if ($blogId > 0) {
            $blogData = $blog->getBlogPostById($blogId);
            if ($blogData) {
                $response['data'] = $blogData;
                $response['message'] = 'Blog post retrieved successfully';
            } else {
                $response['error'] = true;
                $response['message'] = 'Blog post not found';
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'Invalid blog ID';
        }
        break;
        
    case 'delete_blog':
        $blogId = $_POST['blog_id'] ?? 0;
        if ($blogId > 0) {
            $result = $blog->deleteBlogPost($blogId);
            if ($result) {
                $response['message'] = 'Blog post deleted successfully';
            } else {
                $response['error'] = true;
                $response['message'] = 'Failed to delete blog post';
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'Invalid blog ID';
        }
        break;
        
    case 'upload_image':
        // Handle image upload from Quill editor (Local Storage)
        if (isset($_FILES['file']) && !empty($_FILES['file']['name']) && $_FILES['file']['error'] == 0) {
            $uploadResult = uploadImageLocal($_FILES['file'], 'blog/editor', 'EDITOR');
            if (!$uploadResult['error'] && !empty($uploadResult['url'])) {
                // Return full URL for editor (relative to blog/action/)
                $response['url'] = getImageUrl($uploadResult['url']);
                $response['message'] = 'Image uploaded successfully';
            } else {
                $response['error'] = true;
                $response['message'] = $uploadResult['message'] ?? 'Failed to upload image';
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'No file uploaded or upload error';
        }
        break;
        
    case 'get_blogs_datatable':
        // Get blogs for DataTables AJAX
        $allBlogs = $blog->getAllBlogPosts();
        $data = array();
        
        foreach ($allBlogs as $blogPost) {
            $categoryName = 'Uncategorized';
            if ($blogPost['category_id']) {
                $category = $blog->getBlogCategoryById($blogPost['category_id']);
                if ($category) {
                    $categoryName = $category['category_name'];
                }
            }
            
            $statusBadge = '';
            switch ($blogPost['status']) {
                case 'published':
                    $statusBadge = '<span class="badge bg-success">Published</span>';
                    break;
                case 'draft':
                    $statusBadge = '<span class="badge bg-secondary">Draft</span>';
                    break;
                case 'scheduled':
                    $statusBadge = '<span class="badge bg-warning">Scheduled</span>';
                    break;
            }
            
            $publishedDate = $blogPost['published_date'] ? date('d M Y', strtotime($blogPost['published_date'])) : 'Not Published';
            
            $titleHtml = htmlspecialchars($blogPost['title']);
            if ($blogPost['pin_to_top']) {
                $titleHtml .= ' <span class="badge bg-info">Pinned</span>';
            }
            if ($blogPost['featured']) {
                $titleHtml .= ' <span class="badge bg-primary">Featured</span>';
            }
            
            $actionsHtml = '
                <button class="btn btn-sm btn-info me-1" onclick="EditBlog(' . $blogPost['ID'] . ')" title="Edit">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-danger me-1" onclick="DeleteBlog(' . $blogPost['ID'] . ')" title="Delete">
                    <i class="bi bi-trash"></i>
                </button>
                <a href="../blog-frontend/blog-detail.php?slug=' . htmlspecialchars($blogPost['slug']) . '" target="_blank" class="btn btn-sm btn-success" title="View">
                    <i class="bi bi-eye"></i>
                </a>
            ';
            
            $data[] = array(
                'ID' => $blogPost['ID'],
                'title' => $titleHtml,
                'category' => htmlspecialchars($categoryName),
                'author' => htmlspecialchars($blogPost['author_name'] ?? 'Admin'),
                'status' => $statusBadge,
                'published_date' => $publishedDate,
                'views' => $blogPost['views_count'] ?? 0,
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
            
            // Handle category image upload (Local Storage)
            $imageUploaded = false;
            if (isset($_FILES['category_image_file']) && !empty($_FILES['category_image_file']['name'])) {
                // Check for upload errors
                if ($_FILES['category_image_file']['error'] == 0) {
                    $uploadResult = uploadImageLocal($_FILES['category_image_file'], 'blog/categories', 'CATEGORY');
                    if (!$uploadResult['error'] && !empty($uploadResult['url'])) {
                        $data['category_image'] = $uploadResult['url'];
                        $imageUploaded = true;
                    } else {
                        // Log upload error
                        $uploadError = $uploadResult['message'] ?? 'Unknown upload error';
                        error_log('Category image upload failed: ' . $uploadError);
                        $response['upload_error'] = $uploadError;
                    }
                } else {
                    // File upload error
                    $uploadErrorMsg = 'File upload error code: ' . $_FILES['category_image_file']['error'];
                    error_log('Category image file error: ' . $uploadErrorMsg);
                    $response['upload_error'] = $uploadErrorMsg;
                }
            }
            
            // If no new image uploaded and updating, preserve existing image
            if (!$imageUploaded && $formAction == 'edit') {
                $existingCategory = $blog->getBlogCategoryById($categoryId);
                if ($existingCategory && !empty($existingCategory['category_image'])) {
                    $data['category_image'] = $existingCategory['category_image'];
                } elseif (!empty($_POST['category_image'])) {
                    // Fallback to POST value if database doesn't have it
                    $data['category_image'] = $_POST['category_image'];
                }
            } elseif (!$imageUploaded && $formAction == 'add' && !empty($_POST['category_image'])) {
                // For new category, use POST value if provided (shouldn't happen normally)
                $data['category_image'] = $_POST['category_image'];
            }
            
            // Ensure category_image is set (even if empty) to avoid issues
            if (!isset($data['category_image'])) {
                $data['category_image'] = null;
            }
            
            if ($formAction == 'add') {
                $data['IsActive'] = 1;
                $data['CreatedDate'] = date('Y-m-d');
                $data['CreatedTime'] = date('H:i:s');
                $data['CreatedBy'] = $_SESSION['pp_email'] ?? $_SESSION['UserID'];
                
                $result = $blog->insertBlogCategory($data);
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
                
                $result = $blog->updateBlogCategory($data, $categoryId);
                if ($result['error'] == false) {
                    $response['message'] = 'Category updated successfully!';
                    if ($imageUploaded) {
                        $response['message'] .= ' Image updated successfully.';
                    }
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
            $categoryData = $blog->getBlogCategoryById($categoryId);
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
            $result = $blog->deleteBlogCategory($categoryId);
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
        $allCategories = $blog->getAllBlogCategories();
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
                <button class="btn btn-sm btn-danger me-1" onclick="DeleteCategory(' . $category['ID'] . ')" title="Delete">
                    <i class="bi bi-trash"></i>
                </button>
            ';
            
            $data[] = array(
                'ID' => $category['ID'],
                'image' => $imageHtml,
                'category_name' => htmlspecialchars($category['category_name']),
                'category_slug' => htmlspecialchars($category['category_slug'] ?? ''),
                'category_description' => htmlspecialchars(substr($category['category_description'] ?? '', 0, 100)) . (strlen($category['category_description'] ?? '') > 100 ? '...' : ''),
                'created_date' => $category['CreatedDate'] ? date('d M Y', strtotime($category['CreatedDate'])) : '-',
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
        
    default:
        $response['error'] = true;
        $response['message'] = 'Invalid action';
        break;
}

// Clean any output before sending JSON
ob_clean();
echo json_encode($response);
ob_end_flush();
exit;
?>

