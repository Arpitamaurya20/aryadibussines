<?php
@session_start();
require_once('../include/autoloader.inc.php');

$dbh = new Dbh();
$conn = $dbh->_connectodb();
$blog = new Blog($conn);
$core = new Core();

// Get parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : null;
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$limit = 12;
$offset = ($page - 1) * $limit;

// Get blog posts
if (!empty($searchTerm)) {
    $allBlogs = $blog->searchBlogPosts($searchTerm, 100);
    $totalBlogs = count($allBlogs);
    $blogs = array_slice($allBlogs, $offset, $limit);
} else {
    $allBlogs = $blog->getPublishedBlogPosts(null, null, $categoryId);
    $totalBlogs = count($allBlogs);
    $blogs = $blog->getPublishedBlogPosts($limit, $offset, $categoryId);
}

// Get categories
$categories = $blog->getAllBlogCategories();

// Helper function to get image URL (handles both local and external)
function getBlogImageUrl($imagePath) {
    if (empty($imagePath)) {
        return '';
    }
    // If it's already a full URL, return as is
    if (strpos($imagePath, 'http') === 0) {
        return $imagePath;
    }
    // For local paths, return relative to blog-frontend/
    return '../' . ltrim($imagePath, './');
}

// Get featured posts
$featuredPosts = $blog->getFeaturedBlogPosts(3);

// Get trending posts
$trendingPosts = $blog->getTrendingBlogPosts(5);

// Pagination
$totalPages = ceil($totalBlogs / $limit);

// SEO Meta
$pageTitle = "Blog - Latest Articles & News";
$metaDescription = "Read our latest blog posts, articles, and news. Stay updated with trending topics and featured content.";
if ($categoryId) {
    $category = $blog->getBlogCategoryById($categoryId);
    if ($category) {
        $pageTitle = $category['category_name'] . " - Blog";
        $metaDescription = $category['category_description'] ?? $metaDescription;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta name="keywords" content="blog, articles, news, latest posts">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta property="og:type" content="website">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($metaDescription) ?>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        .blog-card {
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .blog-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .blog-card-img {
            height: 250px;
            object-fit: cover;
        }
        .blog-category-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .reading-time {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .featured-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 3rem;
        }
        .trending-sidebar {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .trending-item {
            padding: 1rem 0;
            border-bottom: 1px solid #dee2e6;
        }
        .trending-item:last-child {
            border-bottom: none;
        }
        .breadcrumb {
            background: transparent;
            padding: 1rem 0;
        }
    </style>
</head>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6496901533964255"
     crossorigin="anonymous"></script>
<body>
    <!-- Navigation (Add your navigation here) -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="/">ZeltoHub</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="/">Home</a></li>
                    <li class="nav-item"><a class="nav-link active" href="blog-listing.php">Blog</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Breadcrumbs -->
    <div class="container mt-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/">Home</a></li>
                <li class="breadcrumb-item active">Blog</li>
                <?php if ($categoryId): ?>
                    <li class="breadcrumb-item active"><?= htmlspecialchars($category['category_name'] ?? 'Category') ?></li>
                <?php endif; ?>
            </ol>
        </nav>
    </div>

    <!-- Featured Section -->
    <?php if (!empty($featuredPosts) && $page == 1 && empty($searchTerm) && !$categoryId): ?>
    <div class="featured-section">
        <div class="container">
            <h2 class="mb-4">Featured Posts</h2>
            <div class="row">
                <?php foreach ($featuredPosts as $featured): ?>
                <div class="col-md-4 mb-3">
                    <div class="card blog-card bg-transparent text-white border-0">
                        <?php if ($featured['featured_image']): ?>
                        <img src="<?= htmlspecialchars(getBlogImageUrl($featured['featured_image'])) ?>" class="card-img-top blog-card-img" alt="<?= htmlspecialchars($featured['title']) ?>">
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title">
                                <a href="blog-detail.php?slug=<?= htmlspecialchars($featured['slug']) ?>" class="text-white text-decoration-none">
                                    <?= htmlspecialchars($featured['title']) ?>
                                </a>
                            </h5>
                            <p class="card-text"><?= htmlspecialchars(substr($featured['short_description'] ?? '', 0, 100)) ?>...</p>
                            <small class="reading-time">
                                <i class="bi bi-clock"></i> <?= $featured['reading_time'] ?? 5 ?> min read
                            </small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="container my-5">
        <div class="row">
            <!-- Blog Posts -->
            <div class="col-lg-8">
                <!-- Search Bar -->
                <div class="mb-4">
                    <form method="GET" action="blog-listing.php">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="Search blog posts..." value="<?= htmlspecialchars($searchTerm) ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="bi bi-search"></i> Search
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Blog Posts Grid -->
                <?php if (!empty($blogs)): ?>
                    <div class="row">
                        <?php foreach ($blogs as $post): 
                            $category = null;
                            if ($post['category_id']) {
                                $category = $blog->getBlogCategoryById($post['category_id']);
                            }
                        ?>
                        <div class="col-md-6 mb-4">
                            <div class="card blog-card">
                                <?php if ($post['featured_image']): ?>
                                <div class="position-relative">
                                    <img src="<?= htmlspecialchars($post['featured_image']) ?>" class="card-img-top blog-card-img" alt="<?= htmlspecialchars($post['title']) ?>">
                                    <?php if ($category): ?>
                                    <span class="badge bg-primary blog-category-badge"><?= htmlspecialchars($category['category_name']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <a href="blog-detail.php?slug=<?= htmlspecialchars($post['slug']) ?>" class="text-decoration-none">
                                            <?= htmlspecialchars($post['title']) ?>
                                        </a>
                                    </h5>
                                    <p class="card-text text-muted">
                                        <?= htmlspecialchars(substr($post['short_description'] ?? '', 0, 150)) ?>...
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="bi bi-person"></i> <?= htmlspecialchars($post['author_name'] ?? 'Admin') ?>
                                            <span class="ms-2"><i class="bi bi-calendar"></i> <?= date('d M Y', strtotime($post['published_date'])) ?></span>
                                        </small>
                                        <small class="reading-time">
                                            <i class="bi bi-clock"></i> <?= $post['reading_time'] ?? 5 ?> min
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <nav aria-label="Blog pagination">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?><?= $categoryId ? '&category=' . $categoryId : '' ?><?= $searchTerm ? '&search=' . urlencode($searchTerm) : '' ?>">Previous</a>
                            </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?><?= $categoryId ? '&category=' . $categoryId : '' ?><?= $searchTerm ? '&search=' . urlencode($searchTerm) : '' ?>"><?= $i ?></a>
                            </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?><?= $categoryId ? '&category=' . $categoryId : '' ?><?= $searchTerm ? '&search=' . urlencode($searchTerm) : '' ?>">Next</a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        <h5>No blog posts found</h5>
                        <p>There are no blog posts available at the moment. Please check back later.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Categories -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Categories</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li><a href="blog-listing.php" class="text-decoration-none">All Categories</a></li>
                            <?php foreach ($categories as $cat): ?>
                            <li class="mt-2">
                                <a href="?category=<?= $cat['ID'] ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($cat['category_name']) ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <!-- Trending Posts -->
                <?php if (!empty($trendingPosts)): ?>
                <div class="trending-sidebar">
                    <h5 class="mb-3">Trending Posts</h5>
                    <?php foreach ($trendingPosts as $trending): ?>
                    <div class="trending-item">
                        <h6>
                            <a href="blog-detail.php?slug=<?= htmlspecialchars($trending['slug']) ?>" class="text-decoration-none">
                                <?= htmlspecialchars($trending['title']) ?>
                            </a>
                        </h6>
                        <small class="text-muted">
                            <i class="bi bi-eye"></i> <?= $trending['views_count'] ?? 0 ?> views
                        </small>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer (Add your footer here) -->
    <footer class="bg-dark text-white text-center py-4 mt-5">
        <div class="container">
            <p>&copy; <?= date('Y') ?> ZeltoHub. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

