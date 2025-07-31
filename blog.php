<?php
require_once 'db_connect.php';

// Fetch settings for contact info
try {
    $stmt = $pdo->query("SELECT contact_phone, whatsapp_number, location FROM settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
        'contact_phone' => '+260-0979-303-059',
        'whatsapp_number' => '+260-0979-303-059',
        'location' => 'Woodlands off Mosi Otunya Road'
    ];
} catch (PDOException $e) {
    $settings = [
        'contact_phone' => '+260-0979-303-059',
        'whatsapp_number' => '+260-0979-303-059',
        'location' => 'Woodlands off Mosi Otunya Road'
    ];
}

// Handle single post view
$single_post = null;
if (isset($_GET['post_id']) && is_numeric($_GET['post_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT id, title, content, author, created_at, image FROM blog_posts WHERE id = ?");
        $stmt->execute([$_GET['post_id']]);
        $single_post = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Error fetching post: " . htmlspecialchars($e->getMessage());
    }
}

// Fetch all posts for list view or recent posts
try {
    $stmt = $pdo->query("SELECT id, title, content, author, created_at, image FROM blog_posts ORDER BY created_at DESC LIMIT 10");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $posts = [];
    $error = "Error fetching posts: " . htmlspecialchars($e->getMessage());
}

// Fetch categories for sidebar
try {
    $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}

// Estimate reading time
function reading_time($content) {
    $word_count = str_word_count(strip_tags($content));
    $minutes = ceil($word_count / 200); // Average reading speed: 200 words/min
    return $minutes . ' min read';
}

// Generate SEO-friendly URL slug (for future use)
function generate_slug($title) {
    return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $single_post ? htmlspecialchars($single_post['title']) . ' - ' : ''; ?>WS Computer Suppliers - Blog</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?php echo $single_post ? htmlspecialchars(substr(strip_tags($single_post['content']), 0, 160)) : 'Read the latest tech insights and updates from WS Computer Suppliers, Zambia’s leading importer of laptops and accessories from the UK and USA.'; ?>">
    <meta name="keywords" content="laptops, computer accessories, Zambia, UK, USA, tech blog, WS Computer Suppliers">
    <meta name="author" content="<?php echo $single_post ? htmlspecialchars($single_post['author'] ?: 'WS Computer Suppliers') : 'WS Computer Suppliers'; ?>">
    <meta property="og:title" content="<?php echo $single_post ? htmlspecialchars($single_post['title']) : 'WS Computer Suppliers Blog'; ?>">
    <meta property="og:description" content="<?php echo $single_post ? htmlspecialchars(substr(strip_tags($single_post['content']), 0, 160)) : 'Discover tech tips, news, and updates from WS Computer Suppliers in Zambia.'; ?>">
    <meta property="og:image" content="<?php echo $single_post && $single_post['image'] ? 'http://localhost/WS%20trial/' . htmlspecialchars($single_post['image']) : 'http://localhost/WS%20trial/assets/img/custom-apple-icon.png'; ?>">
    <meta property="og:url" content="<?php echo $single_post ? 'http://localhost/WS%20trial/blog.php?post_id=' . $single_post['id'] : 'http://localhost/WS%20trial/blog.php'; ?>">
    <meta property="og:type" content="article">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo $single_post ? htmlspecialchars($single_post['title']) : 'WS Computer Suppliers Blog'; ?>">
    <meta name="twitter:description" content="<?php echo $single_post ? htmlspecialchars(substr(strip_tags($single_post['content']), 0, 160)) : 'Discover tech tips, news, and updates from WS Computer Suppliers in Zambia.'; ?>">
    <meta name="twitter:image" content="<?php echo $single_post && $single_post['image'] ? 'http://localhost/WS%20trial/' . htmlspecialchars($single_post['image']) : 'http://localhost/WS%20trial/assets/img/custom-apple-icon.png'; ?>">
    <link rel="apple-touch-icon" href="assets/img/custom-apple-icon.png">
    <link rel="shortcut icon" type="image/x-icon" href="assets/img/custom-favicon.ico">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/templatemo.css">
    <link rel="stylesheet" href="assets/css/custom.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@100;200;300;400;500;700;900&display=swap">
    <link rel="stylesheet" href="assets/css/fontawesome.min.css">
    <style>
        .blog-post { margin-bottom: 2rem; }
        .blog-post img { max-height: 300px; object-fit: cover; width: 100%; }
        .blog-post .card-body { padding: 1.5rem; }
        .blog-post .card-title { font-size: 1.8rem; margin-bottom: 1rem; }
        .blog-post .card-text { font-size: 1.1rem; line-height: 1.8; color: #555; }
        .blog-meta { font-size: 0.9rem; color: #888; margin-bottom: 1rem; }
        .blog-sidebar { padding-left: 1rem; }
        .blog-sidebar .card { margin-bottom: 1.5rem; }
        .blog-sidebar .list-group-item { border: none; padding: 0.5rem 0; }
        .social-share a { margin-right: 0.5rem; font-size: 1.2rem; color: #333; }
        .social-share a:hover { color: #28a745; }
        @media (max-width: 991px) { .blog-sidebar { padding-left: 0; margin-top: 2rem; } }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg bg-dark navbar-light d-none d-lg-block" id="templatemo_nav_top">
        <div class="container text-light">
            <div class="w-100 d-flex justify-content-between">
                <div>
                    <i class="fa fa-envelope mx-2"></i>
                    <a class="navbar-sm-brand text-light text-decoration-none" href="mailto:wscomputersuppliers@gmail.com">wscomputersuppliers@gmail.com</a>
                    <i class="fa fa-phone mx-2"></i>
                    <a class="navbar-sm-brand text-light text-decoration-none" href="tel:<?php echo htmlspecialchars($settings['contact_phone']); ?>"><?php echo htmlspecialchars($settings['contact_phone']); ?></a>
                </div>
                <div>
                    <a class="text-light" href="https://www.facebook.com/profile.php?id=100075856668294
" target="_blank"><i class="fab fa-facebook-f fa-sm fa-fw me-2"></i></a>
                    <a class="text-light" href="https://www.instagram.com/" target="_blank"><i class="fab fa-instagram fa-sm fa-fw me-2"></i></a>
                    <a class="text-light" href="https://twitter.com/" target="_blank"><i class="fab fa-twitter fa-sm fa-fw me-2"></i></a>
                    <a class="text-light" href="https://www.linkedin.com/" target="_blank"><i class="fab fa-linkedin fa-sm fa-fw"></i></a>
                </div>
            </div>
        </div>
    </nav>
    <nav class="navbar navbar-expand-lg navbar-light shadow">
        <div class="container d-flex justify-content-between align-items-center">
            <a class="navbar-brand text-success logo h1 align-self-center" href="index.php">WS Computer Suppliers</a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#templatemo_main_nav" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="align-self-center collapse navbar-collapse flex-fill d-lg-flex justify-content-lg-between" id="templatemo_main_nav">
                <div class="flex-fill">
                    <ul class="nav navbar-nav d-flex justify-content-between mx-lg-auto">
                        <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
                        <li class="nav-item"><a class="nav-link" href="shop.php">Shop</a></li>
                        <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                        <li class="nav-item"><a class="nav-link active" href="blog.php">Blog</a></li>
                    </ul>
                </div>
                <div class="navbar align-self-center d-flex">
                    <a class="nav-icon position-relative text-decoration-none" href="admin.php">Admin</a>
                    <a class="nav-icon position-relative text-decoration-none" href="admin_logout.php">Logout</a>
                </div>
            </div>
        </div>
    </nav>
    <div class="container py-5">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <div class="row">
            <div class="col-lg-8">
                <h1 class="h1 mb-4"><?php echo $single_post ? htmlspecialchars($single_post['title']) : 'Tech Blog'; ?></h1>
                <?php if ($single_post): ?>
                    <article class="blog-post" itemscope itemtype="http://schema.org/BlogPosting">
                        <header>
                            <?php if ($single_post['image']): ?>
                                <img src="<?php echo htmlspecialchars($single_post['image']); ?>" alt="<?php echo htmlspecialchars($single_post['title']); ?>" class="img-fluid mb-3">
                            <?php endif; ?>
                            <h2 class="card-title" itemprop="headline"><?php echo htmlspecialchars($single_post['title']); ?></h2>
                            <div class="blog-meta">
                                <span itemprop="author" itemscope itemtype="http://schema.org/Person">
                                    By <span itemprop="name"><?php echo htmlspecialchars($single_post['author'] ?: 'Admin'); ?></span>
                                </span> | 
                                <time datetime="<?php echo date('c', strtotime($single_post['created_at'])); ?>" itemprop="datePublished">
                                    <?php echo date('F j, Y', strtotime($single_post['created_at'])); ?>
                                </time> | 
                                <?php echo reading_time($single_post['content']); ?>
                            </div>
                        </header>
                        <div class="card-text" itemprop="articleBody">
                            <?php echo htmlspecialchars_decode($single_post['content']); ?>
                        </div>
                        <div class="social-share mt-3">
                            <strong>Share:</strong>
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('http://localhost/WS%20trial/blog.php?post_id=' . $single_post['id']); ?>" target="_blank"><i class="fab fa-facebook-f"></i></a>
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('http://localhost/WS%20trial/blog.php?post_id=' . $single_post['id']); ?>&text=<?php echo urlencode($single_post['title']); ?>" target="_blank"><i class="fab fa-twitter"></i></a>
                            <a href="https://api.whatsapp.com/send?text=<?php echo urlencode($single_post['title'] . ' ' . 'http://localhost/WS%20trial/blog.php?post_id=' . $single_post['id']); ?>" target="_blank"><i class="fab fa-whatsapp"></i></a>
                        </div>
                        <a href="blog.php" class="btn btn-success mt-3">Back to Blog</a>
                    </article>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <article class="blog-post card mb-4" itemscope itemtype="http://schema.org/BlogPosting">
                            <?php if ($post['image']): ?>
                                <img src="<?php echo htmlspecialchars($post['image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="card-img-top">
                            <?php endif; ?>
                            <div class="card-body">
                                <h2 class="card-title" itemprop="headline"><a href="blog.php?post_id=<?php echo $post['id']; ?>" class="text-success"><?php echo htmlspecialchars($post['title']); ?></a></h2>
                                <div class="blog-meta">
                                    <span itemprop="author" itemscope itemtype="http://schema.org/Person">
                                        By <span itemprop="name"><?php echo htmlspecialchars($post['author'] ?: 'Admin'); ?></span>
                                    </span> | 
                                    <time datetime="<?php echo date('c', strtotime($post['created_at'])); ?>" itemprop="datePublished">
                                        <?php echo date('F j, Y', strtotime($post['created_at'])); ?>
                                    </time> | 
                                    <?php echo reading_time($post['content']); ?>
                                </div>
                                <p class="card-text" itemprop="description">
                                    <?php echo htmlspecialchars(substr(strip_tags($post['content']), 0, 200)); ?>...
                                    <a href="blog.php?post_id=<?php echo $post['id']; ?>" class="text-success">Read More</a>
                                </p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                    <?php if (empty($posts)): ?>
                        <p>No blog posts available.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="col-lg-4 blog-sidebar">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Search</h5>
                        <form action="blog.php" method="get" class="input-group">
                            <input type="text" name="q" class="form-control" placeholder="Search posts..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                            <button type="submit" class="btn btn-success"><i class="fa fa-search"></i></button>
                        </form>
                    </div>
                </div>
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Recent Posts</h5>
                        <ul class="list-group list-group-flush">
                            <?php foreach (array_slice($posts, 0, 5) as $post): ?>
                                <li class="list-group-item"><a href="blog.php?post_id=<?php echo $post['id']; ?>" class="text-success"><?php echo htmlspecialchars($post['title']); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Categories</h5>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($categories as $category): ?>
                                <li class="list-group-item"><a href="blog.php?category_id=<?php echo $category['id']; ?>" class="text-success"><?php echo htmlspecialchars($category['name']); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <footer class="bg-dark" id="tempaltemo_footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 pt-5">
                    <h2 class="h2 text-success border-bottom pb-3 border-light logo">WS Computer Suppliers</h2>
                    <ul class="list-unstyled text-light footer-link-list">
                        <li><i class="fas fa-map-marker-alt fa-fw"></i><?php echo htmlspecialchars($settings['location']); ?></li>
                        <li><i class="fa fa-phone fa-fw"></i><a class="text-decoration-none" href="tel:<?php echo htmlspecialchars($settings['contact_phone']); ?>"><?php echo htmlspecialchars($settings['contact_phone']); ?></a></li>
                        <li><i class="fa fa-envelope fa-fw"></i><a class="text-decoration-none" href="mailto:wscomputersuppliers@gmail.com">wscomputersuppliers@gmail.com</a></li>
                    </ul>
                </div>
                <div class="col-md-4 pt-5">
                    <h2 class="h2 text-light border-bottom pb-3 border-light">Products</h2>
                    <ul class="list-unstyled text-light footer-link-list">
                        <li><a class="text-decoration-none" href="shop.php">100% Neat Laptops</a></li>
                        <li><a class="text-decoration-none" href="shop.php">80% Neat Laptops</a></li>
                        <li><a class="text-decoration-none" href="shop.php">60% Neat Laptops</a></li>
                        <li><a class="text-decoration-none" href="shop.php">50% Neat Laptops</a></li>
                    </ul>
                </div>
                <div class="col-md-4 pt-5">
                    <h2 class="h2 text-light border-bottom pb-3 border-light">Further Info</h2>
                    <ul class="list-unstyled text-light footer-link-list">
                        <li><a class="text-decoration-none" href="index.php">Home</a></li>
                        <li><a class="text-decoration-none" href="about.php">About Us</a></li>
                        <li><a class="text-decoration-none" href="shop.php">Shop Locations</a></li>
                        <li><a class="text-decoration-none" href="#">FAQs</a></li>
                        <li><a class="text-decoration-none" href="contact.php">Contact</a></li>
                    </ul>
                </div>
            </div>
            <div class="row text-light mb-4">
                <div class="col-12 mb-3">
                    <div class="w-100 my-3 border-top border-light"></div>
                </div>
                <div class="col-auto me-auto">
                    <ul class="list-inline text-left footer-icons">
                        <li class="list-inline-item border border-light rounded-circle text-center">
                            <a class="text-light text-decoration-none" target="_blank" href="https://www.facebook.com/profile.php?id=100075856668294
/"><i class="fab fa-facebook-f fa-lg fa-fw"></i></a>
                        </li>
                        <li class="list-inline-item border border-light rounded-circle text-center">
                            <a class="text-light text-decoration-none" target="_blank" href="https://www.instagram.com/"><i class="fab fa-instagram fa-lg fa-fw"></i></a>
                        </li>
                        <li class="list-inline-item border border-light rounded-circle text-center">
                            <a class="text-light text-decoration-none" target="_blank" href="https://twitter.com/"><i class="fab fa-twitter fa-lg fa-fw"></i></a>
                        </li>
                        <li class="list-inline-item border border-light rounded-circle text-center">
                            <a class="text-light text-decoration-none" target="_blank" href="https://www.linkedin.com/"><i class="fab fa-linkedin fa-lg fa-fw"></i></a>
                        </li>
                    </ul>
                </div>
                <div class="col-auto">
                    <label class="sr-only" for="subscribeEmail">Email address</label>
                    <div class="input-group mb-2">
                        <input type="text" class="form-control bg-dark border-light" id="subscribeEmail" placeholder="Email address">
                        <div class="input-group-text btn-success text-light">Subscribe</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="w-100 bg-black py-3">
            <div class="container">
                <div class="row pt-2">
                    <div class="col-12">
                        <p class="text-left text-light">
                            Copyright &copy; 2025 WS Computer Suppliers 
                            | Designed by <a rel="sponsored" href="" target="_blank">TechZED</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    <script src="assets/js/jquery-1.11.0.min.js"></script>
    <script src="assets/js/jquery-migrate-1.2.1.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/templatemo.js"></script>
    <script src="assets/js/custom.js"></script>
    <script type="application/ld+json">
    {
        "@context": "http://schema.org",
        "@type": "Blog",
        "name": "WS Computer Suppliers Blog",
        "url": "http://localhost/WS%20trial/blog.php",
        "publisher": {
            "@type": "Organization",
            "name": "WS Computer Suppliers",
            "logo": {
                "@type": "ImageObject",
                "url": "http://localhost/WS%20trial/assets/img/custom-apple-icon.png"
            }
        },
        <?php if ($single_post): ?>
        "mainEntityOfPage": {
            "@type": "WebPage",
            "@id": "http://localhost/WS%20trial/blog.php?post_id=<?php echo $single_post['id']; ?>"
        },
        "blogPost": {
            "@type": "BlogPosting",
            "headline": "<?php echo htmlspecialchars($single_post['title']); ?>",
            "datePublished": "<?php echo date('c', strtotime($single_post['created_at'])); ?>",
            "author": {
                "@type": "Person",
                "name": "<?php echo htmlspecialchars($single_post['author'] ?: 'Admin'); ?>"
            },
            "image": "<?php echo $single_post['image'] ? 'http://localhost/WS%20trial/' . htmlspecialchars($single_post['image']) : 'http://localhost/WS%20trial/assets/img/custom-apple-icon.png'; ?>",
            "description": "<?php echo htmlspecialchars(substr(strip_tags($single_post['content']), 0, 160)); ?>"
        }
        <?php else: ?>
        "blogPost": [
            <?php foreach ($posts as $index => $post): ?>
            {
                "@type": "BlogPosting",
                "headline": "<?php echo htmlspecialchars($post['title']); ?>",
                "datePublished": "<?php echo date('c', strtotime($post['created_at'])); ?>",
                "author": {
                    "@type": "Person",
                    "name": "<?php echo htmlspecialchars($post['author'] ?: 'Admin'); ?>"
                },
                "image": "<?php echo $post['image'] ? 'http://localhost/WS%20trial/' . htmlspecialchars($post['image']) : 'http://localhost/WS%20trial/assets/img/custom-apple-icon.png'; ?>",
                "description": "<?php echo htmlspecialchars(substr(strip_tags($post['content']), 0, 160)); ?>",
                "url": "http://localhost/WS%20trial/blog.php?post_id=<?php echo $post['id']; ?>"
            }<?php echo $index < count($posts) - 1 ? ',' : ''; ?>
            <?php endforeach; ?>
        ]
        <?php endif; ?>
    }
    </script>
</body>
</html>