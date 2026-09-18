<?php
@session_start();
require_once('../include/autoloader.inc.php');

$dbh = new Dbh();
$conn = $dbh->_connectodb();
$blog = new Blog($conn);
$core = new Core();

// Get slug from URL
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

if (empty($slug)) {
    header('HTTP/1.0 404 Not Found');
    die('Blog post not found');
}

// Get blog post by slug
$post = $blog->getBlogPostBySlug($slug);

if (!$post) {
    header('HTTP/1.0 404 Not Found');
    die('Blog post not found');
}

// Increment view count
$blog->incrementViewCount($post['ID']);

// Get category
$category = null;
if ($post['category_id']) {
    $category = $blog->getBlogCategoryById($post['category_id']);
}

// Get related posts
$relatedPosts = $blog->getRelatedBlogPosts($post['ID'], $post['category_id'], 3);

// Parse gallery images
$galleryImages = array();
if (!empty($post['gallery_images'])) {
    try {
        $galleryImages = json_decode($post['gallery_images'], true);
    } catch (Exception $e) {
        $galleryImages = array();
    }
}

// Parse tags
$tags = array();
if (!empty($post['tags'])) {
    $tags = array_map('trim', explode(',', $post['tags']));
}

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

// SEO Meta
$pageTitle = $post['meta_title'] ?: $post['title'];
$metaDescription = $post['meta_description'] ?: $post['short_description'] ?: substr(strip_tags($post['full_content']), 0, 160);
$metaKeywords = $post['meta_keywords'] ?: $post['tags'];
$ogImage = $post['og_image'] ?: $post['featured_image'];
// Convert local paths to full URLs for meta tags
if (!empty($ogImage) && strpos($ogImage, 'http') !== 0) {
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
    $ogImage = $baseUrl . '/' . ltrim($ogImage, './');
}
$canonicalUrl = $post['canonical_url'] ?: 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

// Generate schema markup if not exists
$schemaMarkup = $post['schema_markup'];
if (empty($schemaMarkup)) {
    $schemaMarkup = $blog->generateSchemaMarkup($post);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    <?php if ($metaKeywords): ?>
    <meta name="keywords" content="<?= htmlspecialchars($metaKeywords) ?>">
    <?php endif; ?>
    
    <!-- Canonical URL -->
    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= htmlspecialchars($post['og_title'] ?: $post['title']) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($post['og_description'] ?: $metaDescription) ?>">
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?= htmlspecialchars($canonicalUrl) ?>">
    <?php if ($ogImage): ?>
    <meta property="og:image" content="<?= htmlspecialchars($ogImage) ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <?php endif; ?>
    <meta property="article:published_time" content="<?= date('c', strtotime($post['published_date'])) ?>">
    <meta property="article:modified_time" content="<?= date('c', strtotime($post['updated_date'] ?: $post['published_date'])) ?>">
    <?php if ($post['author_name']): ?>
    <meta property="article:author" content="<?= htmlspecialchars($post['author_name']) ?>">
    <?php endif; ?>
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($post['twitter_card_title'] ?: $post['title']) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($post['twitter_card_description'] ?: $metaDescription) ?>">
    <?php if ($post['twitter_card_image'] ?: $ogImage): ?>
    <meta name="twitter:image" content="<?= htmlspecialchars($post['twitter_card_image'] ?: $ogImage) ?>">
    <?php endif; ?>
    
    <!-- Schema Markup (JSON-LD) -->
    <script type="application/ld+json">
    <?= $schemaMarkup ?>
    </script>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        .blog-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0;
            margin-bottom: 3rem;
        }
        .blog-content {
            font-size: 1.1rem;
            line-height: 1.8;
        }
        .blog-content img {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            margin: 2rem 0;
        }
        .author-box {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 10px;
            margin: 3rem 0;
        }
        .author-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
        }
        .social-share {
            display: flex;
            gap: 10px;
            margin: 2rem 0;
        }
        .social-share a {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: white;
            text-decoration: none;
        }
        .related-post-card {
            transition: transform 0.3s;
        }
        .related-post-card:hover {
            transform: translateY(-5px);
        }
        .tag-badge {
            display: inline-block;
            margin: 5px 5px 5px 0;
        }
    </style>
</head>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6496901533964255"
     crossorigin="anonymous"></script>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="/">ZeltoHub</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="/">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="blog-listing.php">Blog</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Breadcrumbs -->
    <div class="container mt-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/">Home</a></li>
                <li class="breadcrumb-item"><a href="blog-listing.php">Blog</a></li>
                <?php if ($category): ?>
                <li class="breadcrumb-item"><a href="blog-listing.php?category=<?= $category['ID'] ?>"><?= htmlspecialchars($category['category_name']) ?></a></li>
                <?php endif; ?>
                <li class="breadcrumb-item active"><?= htmlspecialchars($post['title']) ?></li>
            </ol>
        </nav>
    </div>

    <!-- Blog Header -->
    <div class="blog-header">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <?php if ($category): ?>
                    <span class="badge bg-light text-dark mb-3"><?= htmlspecialchars($category['category_name']) ?></span>
                    <?php endif; ?>
                    <h1 class="display-4 mb-3"><?= htmlspecialchars($post['title']) ?></h1>
                    <p class="lead"><?= htmlspecialchars($post['short_description'] ?? '') ?></p>
                    <div class="mt-4">
                        <span><i class="bi bi-person"></i> <?= htmlspecialchars($post['author_name'] ?? 'Admin') ?></span>
                        <span class="ms-3"><i class="bi bi-calendar"></i> <?= date('d M Y', strtotime($post['published_date'])) ?></span>
                        <span class="ms-3"><i class="bi bi-clock"></i> <?= $post['reading_time'] ?? 5 ?> min read</span>
                        <span class="ms-3"><i class="bi bi-eye"></i> <?= $post['views_count'] ?? 0 ?> views</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container my-5">
        <div class="row">
            <!-- Blog Content -->
            <div class="col-lg-8">
                <!-- Featured Image -->
                <?php if ($post['featured_image']): ?>
                <div class="mb-4">
                    <img src="<?= htmlspecialchars(getBlogImageUrl($post['featured_image'])) ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="img-fluid rounded">
                </div>
                <?php endif; ?>

                <!-- Blog Content -->
                <div class="blog-content">
                    <?= $post['full_content'] ?>
                </div>

                <!-- Tags -->
                <?php if (!empty($tags)): ?>
                <div class="mt-4">
                    <h5>Tags:</h5>
                    <?php foreach ($tags as $tag): ?>
                    <span class="badge bg-secondary tag-badge"><?= htmlspecialchars($tag) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Social Share Buttons -->
                <div class="social-share">
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($canonicalUrl) ?>" target="_blank" class="bg-primary" title="Share on Facebook">
                        <i class="bi bi-facebook"></i>
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=<?= urlencode($canonicalUrl) ?>&text=<?= urlencode($post['title']) ?>" target="_blank" class="bg-info" title="Share on Twitter">
                        <i class="bi bi-twitter"></i>
                    </a>
                    <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($canonicalUrl) ?>" target="_blank" class="bg-primary" style="background-color: #0077b5 !important;" title="Share on LinkedIn">
                        <i class="bi bi-linkedin"></i>
                    </a>
                    <a href="https://wa.me/?text=<?= urlencode($post['title'] . ' ' . $canonicalUrl) ?>" target="_blank" class="bg-success" title="Share on WhatsApp">
                        <i class="bi bi-whatsapp"></i>
                    </a>
                </div>

                <!-- Author Box -->
                <?php if ($post['author_name']): ?>
                <div class="author-box">
                    <div class="row">
                        <div class="col-md-2">
                            <?php if ($post['author_image']): ?>
                            <img src="<?= htmlspecialchars(getBlogImageUrl($post['author_image'])) ?>" alt="<?= htmlspecialchars($post['author_name']) ?>" class="author-image">
                            <?php else: ?>
                            <div class="author-image bg-secondary d-flex align-items-center justify-content-center text-white">
                                <?= strtoupper(substr($post['author_name'], 0, 1)) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-10">
                            <h5><?= htmlspecialchars($post['author_name']) ?></h5>
                            <?php if ($post['author_bio']): ?>
                            <p class="text-muted"><?= htmlspecialchars($post['author_bio']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- YouTube Video -->
                <?php if ($post['youtube_video_url']): 
                    $videoId = '';
                    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?#]+)/', $post['youtube_video_url'], $matches)) {
                        $videoId = $matches[1];
                    }
                    if ($videoId):
                ?>
                <div class="mb-4">
                    <div class="ratio ratio-16x9">
                        <iframe src="https://www.youtube.com/embed/<?= $videoId ?>" allowfullscreen></iframe>
                    </div>
                </div>
                <?php endif; endif; ?>

                <!-- Gallery Images -->
                <?php if (!empty($galleryImages)): ?>
                <div class="row mb-4">
                    <?php foreach ($galleryImages as $img): ?>
                    <div class="col-md-4 mb-3">
                        <img src="<?= htmlspecialchars(getBlogImageUrl($img)) ?>" alt="Gallery Image" class="img-fluid rounded">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Comments Section (Optional - can integrate Disqus or custom) -->
                <?php if ($post['allow_comments']): ?>
                <div class="mt-5">
                    <h4>Comments</h4>
                    <div id="disqus_thread"></div>
                    <!-- Uncomment to use Disqus -->
                    <!--
                    <script>
                        (function() {
                            var d = document, s = d.createElement('script');
                            s.src = 'https://YOUR_DISQUS_SHORTNAME.disqus.com/embed.js';
                            s.setAttribute('data-timestamp', +new Date());
                            (d.head || d.body).appendChild(s);
                        })();
                    </script>
                    -->
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Related Posts -->
                <?php if (!empty($relatedPosts)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Related Posts</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($relatedPosts as $related): ?>
                        <div class="mb-3 related-post-card">
                            <h6>
                                <a href="blog-detail.php?slug=<?= htmlspecialchars($related['slug']) ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($related['title']) ?>
                                </a>
                            </h6>
                            <small class="text-muted">
                                <i class="bi bi-calendar"></i> <?= date('d M Y', strtotime($related['published_date'])) ?>
                            </small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-4 mt-5">
        <div class="container">
            <p>&copy; <?= date('Y') ?> ZeltoHub. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

