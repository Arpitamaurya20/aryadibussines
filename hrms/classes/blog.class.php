<?php

class Blog extends Core
{
    private $conn;
    
    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->setTimeZone();
    }

    // Generate slug from title
    public function generateSlug($title)
    {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Check if slug exists, append number if needed
        $originalSlug = $slug;
        $counter = 1;
        while ($this->checkSlugExists($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    // Check if slug exists (for blog posts)
    public function checkSlugExists($slug, $excludeId = null)
    {
        $where = "WHERE slug = '" . $this->conn->real_escape_string($slug) . "'";
        if ($excludeId !== null) {
            $where .= " AND ID != " . (int)$excludeId;
        }
        $result = $this->_getTableRecords($this->conn, 'blog_posts', $where);
        return count($result) > 0;
    }

    // Check if category slug exists
    public function checkCategorySlugExists($slug, $excludeId = null)
    {
        $where = "WHERE category_slug = '" . $this->conn->real_escape_string($slug) . "'";
        if ($excludeId !== null) {
            $where .= " AND ID != " . (int)$excludeId;
        }
        $result = $this->_getTableRecords($this->conn, 'blog_categories', $where);
        return count($result) > 0;
    }

    // Generate category slug from name
    public function generateCategorySlug($categoryName, $excludeId = null)
    {
        $slug = strtolower(trim($categoryName));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Check if category slug exists, append number if needed
        $originalSlug = $slug;
        $counter = 1;
        while ($this->checkCategorySlugExists($slug, $excludeId)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    // Calculate reading time
    public function calculateReadingTime($content)
    {
        $wordCount = str_word_count(strip_tags($content));
        $readingTime = ceil($wordCount / 200); // Average reading speed: 200 words per minute
        return max(1, $readingTime); // Minimum 1 minute
    }

    // Generate Schema Markup (JSON-LD)
    public function generateSchemaMarkup($blogData)
    {
        // Safely get values with proper null coalescing
        $title = $blogData['title'] ?? '';
        $description = $blogData['short_description'] ?? ($blogData['meta_description'] ?? '');
        $featuredImage = $blogData['featured_image'] ?? '';
        $ogImage = $blogData['og_image'] ?? '';
        $image = !empty($featuredImage) ? $featuredImage : $ogImage;
        $publishedDate = $blogData['published_date'] ?? date('Y-m-d H:i:s');
        $updatedDate = $blogData['updated_date'] ?? $publishedDate;
        $authorName = $blogData['author_name'] ?? "Admin";
        
        $schema = [
            "@context" => "https://schema.org",
            "@type" => "BlogPosting",
            "headline" => $title,
            "author" => [
                "@type" => "Person",
                "name" => $authorName
            ],
            "publisher" => [
                "@type" => "Organization",
                "name" => "ZeltoHub"
            ]
        ];
        
        // Add optional fields only if they have values
        if (!empty($description)) {
            $schema["description"] = $description;
        }
        
        if (!empty($image)) {
            $schema["image"] = $image;
        }
        
        if (!empty($publishedDate)) {
            $schema["datePublished"] = $publishedDate;
        }
        
        if (!empty($updatedDate)) {
            $schema["dateModified"] = $updatedDate;
        }
        
        return json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    // Insert Blog Post
    public function insertBlogPost($data)
    {
        // Generate slug if not provided
        if (empty($data['slug']) && !empty($data['title'])) {
            $data['slug'] = $this->generateSlug($data['title']);
        }
        
        // Calculate reading time
        if (!empty($data['full_content'])) {
            $data['reading_time'] = $this->calculateReadingTime($data['full_content']);
        }
        
        // Generate schema markup
        if (empty($data['schema_markup']) && !empty($data['title'])) {
            $data['schema_markup'] = $this->generateSchemaMarkup($data);
        }
        
        // Set published date if status is published and publish_date is not set
        if ($data['status'] == 'published' && empty($data['published_date'])) {
            $data['published_date'] = date('Y-m-d H:i:s');
        }
        
        // Set updated date
        $data['updated_date'] = date('Y-m-d H:i:s');
        
        return $this->_InsertTableRecords_prepare($this->conn, 'blog_posts', $data);
    }

    // Update Blog Post
    public function updateBlogPost($data, $id)
    {
        // Generate slug if title changed
        if (!empty($data['title'])) {
            $existingPost = $this->getBlogPostById($id);
            if ($existingPost && $existingPost['title'] != $data['title']) {
                $data['slug'] = $this->generateSlug($data['title']);
            }
        }
        
        // Recalculate reading time if content changed
        if (!empty($data['full_content'])) {
            $data['reading_time'] = $this->calculateReadingTime($data['full_content']);
        }
        
        // Regenerate schema markup if needed
        if (!empty($data['title']) || !empty($data['short_description'])) {
            $existingPost = $this->getBlogPostById($id);
            if ($existingPost) {
                $mergedData = array_merge($existingPost, $data);
                $data['schema_markup'] = $this->generateSchemaMarkup($mergedData);
            }
        }
        
        // Set updated date
        $data['updated_date'] = date('Y-m-d H:i:s');
        
        // Set published date if status changed to published
        if (isset($data['status']) && $data['status'] == 'published') {
            $existingPost = $this->getBlogPostById($id);
            if ($existingPost && empty($existingPost['published_date'])) {
                $data['published_date'] = date('Y-m-d H:i:s');
            }
        }
        
        return $this->_UpdateTableRecords_prepare($this->conn, 'blog_posts', $data, "ID = " . (int)$id);
    }

    // Delete Blog Post
    public function deleteBlogPost($id)
    {
        return $this->delete_identity_filter($this->conn, 'blog_posts', "WHERE ID = " . (int)$id);
    }

    // Get Blog Post by ID
    public function getBlogPostById($id)
    {
        $where = "WHERE ID = " . (int)$id;
        return $this->_getTableDetails($this->conn, 'blog_posts', $where);
    }

    // Get Blog Post by Slug
    public function getBlogPostBySlug($slug)
    {
        $where = "WHERE slug = '" . $this->conn->real_escape_string($slug) . "' AND status = 'published'";
        return $this->_getTableDetails($this->conn, 'blog_posts', $where);
    }

    // Get All Blog Posts
    public function getAllBlogPosts($where = "")
    {
        if (empty($where)) {
            $where = "WHERE IsActive = 1 ORDER BY pin_to_top DESC, published_date DESC, ID DESC";
        }
        return $this->_getTableRecords($this->conn, 'blog_posts', $where);
    }

    // Get Published Blog Posts
    public function getPublishedBlogPosts($limit = null, $offset = 0, $categoryId = null)
    {
        $where = "WHERE status = 'published' AND IsActive = 1";
        
        if ($categoryId !== null) {
            $where .= " AND category_id = " . (int)$categoryId;
        }
        
        $where .= " ORDER BY pin_to_top DESC, published_date DESC, ID DESC";
        
        if ($limit !== null) {
            $where .= " LIMIT " . (int)$offset . ", " . (int)$limit;
        }
        
        return $this->_getTableRecords($this->conn, 'blog_posts', $where);
    }

    // Get Featured Blog Posts
    public function getFeaturedBlogPosts($limit = 5)
    {
        $where = "WHERE status = 'published' AND featured = 1 AND IsActive = 1 ORDER BY published_date DESC LIMIT " . (int)$limit;
        return $this->_getTableRecords($this->conn, 'blog_posts', $where);
    }

    // Get Trending Blog Posts
    public function getTrendingBlogPosts($limit = 5)
    {
        $where = "WHERE status = 'published' AND trending = 1 AND IsActive = 1 ORDER BY views_count DESC, published_date DESC LIMIT " . (int)$limit;
        return $this->_getTableRecords($this->conn, 'blog_posts', $where);
    }

    // Get Related Blog Posts
    public function getRelatedBlogPosts($blogId, $categoryId, $limit = 5)
    {
        $where = "WHERE status = 'published' AND IsActive = 1 AND ID != " . (int)$blogId;
        
        if ($categoryId !== null) {
            $where .= " AND category_id = " . (int)$categoryId;
        }
        
        $where .= " ORDER BY published_date DESC LIMIT " . (int)$limit;
        
        return $this->_getTableRecords($this->conn, 'blog_posts', $where);
    }

    // Increment View Count
    public function incrementViewCount($id)
    {
        $sql = "UPDATE blog_posts SET views_count = views_count + 1 WHERE ID = " . (int)$id;
        return mysqli_query($this->conn, $sql);
    }

    // Blog Categories Methods
    public function insertBlogCategory($data)
    {
        // Generate category slug if not provided
        if (empty($data['category_slug']) && !empty($data['category_name'])) {
            $data['category_slug'] = $this->generateCategorySlug($data['category_name']);
        }
        return $this->_InsertTableRecords_prepare($this->conn, 'blog_categories', $data);
    }

    public function updateBlogCategory($data, $id)
    {
        // Generate category slug if name changed
        if (!empty($data['category_name'])) {
            $existingCategory = $this->getBlogCategoryById($id);
            if ($existingCategory && $existingCategory['category_name'] != $data['category_name']) {
                $data['category_slug'] = $this->generateCategorySlug($data['category_name'], $id);
            }
        }
        return $this->_UpdateTableRecords_prepare($this->conn, 'blog_categories', $data, "ID = " . (int)$id);
    }

    public function deleteBlogCategory($id)
    {
        return $this->delete_identity_filter($this->conn, 'blog_categories', "WHERE ID = " . (int)$id);
    }

    public function getBlogCategoryById($id)
    {
        $where = "WHERE ID = " . (int)$id;
        return $this->_getTableDetails($this->conn, 'blog_categories', $where);
    }

    public function getAllBlogCategories()
    {
        $where = "WHERE IsActive = 1 ORDER BY category_name ASC";
        return $this->_getTableRecords($this->conn, 'blog_categories', $where);
    }

    // Blog Comments Methods
    public function insertBlogComment($data)
    {
        return $this->_InsertTableRecords_prepare($this->conn, 'blog_comments', $data);
    }

    public function updateBlogComment($data, $id)
    {
        return $this->_UpdateTableRecords_prepare($this->conn, 'blog_comments', $data, "ID = " . (int)$id);
    }

    public function deleteBlogComment($id)
    {
        return $this->delete_identity_filter($this->conn, 'blog_comments', "WHERE ID = " . (int)$id);
    }

    public function getBlogComments($blogId, $status = 'approved')
    {
        $where = "WHERE blog_id = " . (int)$blogId . " AND status = '" . $this->conn->real_escape_string($status) . "' AND IsActive = 1 ORDER BY CreatedDate DESC, CreatedTime DESC";
        return $this->_getTableRecords($this->conn, 'blog_comments', $where);
    }

    // Search Blog Posts
    public function searchBlogPosts($searchTerm, $limit = 20)
    {
        $searchTerm = $this->conn->real_escape_string($searchTerm);
        $where = "WHERE status = 'published' AND IsActive = 1 AND (title LIKE '%" . $searchTerm . "%' OR short_description LIKE '%" . $searchTerm . "%' OR full_content LIKE '%" . $searchTerm . "%' OR tags LIKE '%" . $searchTerm . "%') ORDER BY published_date DESC LIMIT " . (int)$limit;
        return $this->_getTableRecords($this->conn, 'blog_posts', $where);
    }
}

