<?php
require 'db_connect.php';
error_log("Script directory: " . __DIR__);
$featured_products = [];
$reviews = [];
$has_errors = false;

try {
    $stmt = $pdo->query("SELECT p.id, p.name, p.price, p.rating, p.image, p.images, p.description, p.created_at 
                        FROM products p 
                        ORDER BY p.created_at DESC 
                        LIMIT 4");
    $featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $has_errors = true;
    error_log("Database error in index.php (featured products): " . $e->getMessage());
}

try {
    $stmt = $pdo->query("SELECT id, reviewer_name, reviewer_image, message, created_at FROM reviews ORDER BY created_at DESC");
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $has_errors = true;
    error_log("Database error in index.php (reviews): " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>WS Computer Suppliers - Home</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="apple-touch-icon" href="assets/img/apple-icon.png">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/templatemo.css">
    <link rel="stylesheet" href="assets/css/custom.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@100;200;300;400;500;700;900&display=swap">
    <link rel="stylesheet" href="assets/css/fontawesome.min.css">
    <style>
        .card-img-top { width: 100%; height: 200px; object-fit: cover; }
        .review-card { max-width: 300px; margin: 0 15px; text-align: center; }
        .review-image { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin-bottom: 15px; }
        .review-text { font-style: italic; color: #666; }
        .reviewer-name { font-weight: bold; margin-top: 10px; }
        .carousel .slide { display: flex; justify-content: center; }
        .error-message { color: #dc3545; text-align: center; margin: 20px 0; }
        .missing-image { border: 2px solid #dc3545; background-color: #f8d7da; text-align: center; padding: 10px; height: 200px; display: flex; align-items: center; justify-content: center; }
    </style>
</head>
<body>
    <!-- Start Top Nav -->
    <nav class="navbar navbar-expand-lg bg-dark navbar-light d-none d-lg-block" id="templatemo_nav_top">
        <div class="container text-light">
            <div class="w-100 d-flex justify-content-between">
                <div>
                    <i class="fa fa-envelope mx-2"></i>
                    <a class="navbar-sm-brand text-light text-decoration-none" href="mailto:wscomputersuppliers@gmail.com">wscomputersuppliers@gmail.com</a>
                    <i class="fa fa-phone mx-2"></i>
                    <a class="navbar-sm-brand text-light text-decoration-none" href="tel:+260-0979-303-059">+260-0979-303-059</a>
                </div>
                <div>
                    <a class="text-light" href="https://www.facebook.com/profile.php?id=100075856668294" target="_blank"><i class="fab fa-facebook-f fa-sm fa-fw me-2"></i></a>
                    <a class="text-light" href="https://www.instagram.com/" target="_blank"><i class="fab fa-instagram fa-sm fa-fw me-2"></i></a>
                    <a class="text-light" href="https://twitter.com/" target="_blank"><i class="fab fa-twitter fa-sm fa-fw me-2"></i></a>
                    <a class="text-light" href="https://www.linkedin.com/" target="_blank"><i class="fab fa-linkedin fa-sm fa-fw"></i></a>
                </div>
            </div>
        </div>
    </nav>
    <!-- Close Top Nav -->

    <!-- Header -->
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
                        <li class="nav-item"><a class="nav-link" href="blog.php">Blog</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    <!-- Close Header -->

    <!-- Modal -->
    <div class="modal fade bg-white" id="templatemo_search" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="w-100 pt-1 mb-5 text-right">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="get" class="modal-content modal-body border-0 p-0">
                <div class="input-group mb-2">
                    <input type="text" class="form-control" id="inputModalSearch" name="q" placeholder="Search ...">
                    <button type="submit" class="input-group-text bg-success text-light">
                        <i class="fa fa-fw fa-search text-white"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Start Banner Hero -->
    <div id="template-mo-zay-hero-carousel" class="carousel slide" data-bs-ride="carousel">
        <ol class="carousel-indicators">
            <li data-bs-target="#template-mo-zay-hero-carousel" data-bs-slide-to="0" class="active"></li>
            <li data-bs-target="#template-mo-zay-hero-carousel" data-bs-slide-to="1"></li>
            <li data-bs-target="#template-mo-zay-hero-carousel" data-bs-slide-to="2"></li>
        </ol>
        <div class="carousel-inner">
            <div class="carousel-item active">
                <div class="container">
                    <div class="row p-5">
                        <div class="mx-auto col-md-8 col-lg-6 order-lg-last">
                            <img class="img-fluid" src="./assets/img/ws (4).jpg" alt="">
                        </div>
                        <div class="col-lg-6 mb-0 d-flex align-items-center">
                            <div class="text-align-left align-self-center">
                                <h1 class="h1 text-success"><b>WS</b> computer suppliers</h1>
                                <h3 class="h2">Affordable Laptops, Global Quality</h3>
                                <p>
                                    Pre-owned laptops from the USA and UK at prices that fit your budget. Experience premium technology without overspending.<a rel="sponsored" class="text-success" href="https://templatemo.com" target="_blank">click here to see latest latops available</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="carousel-item">
                <div class="container">
                    <div class="row p-5">
                        <div class="mx-auto col-md-8 col-lg-6 order-lg-last">
                            <img class="img-fluid" src="./assets/img/ws (1).jpg" alt="">
                        </div>
                        <div class="col-lg-6 mb-0 d-flex align-items-center">
                            <div class="text-align-left">
                                <h1 class="h1">Quality You Can Count On</h1>
                                <p>
                                    Sourced from leading markets in the USA and UK, our laptops guarantee performance, durability, and satisfaction.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="carousel-item">
                <div class="container">
                    <div class="row p-5">
                        <div class="mx-auto col-md-8 col-lg-6 order-lg-last">
                            <img class="img-fluid" src="./assets/img/salePC (47).jpg" alt="">
                        </div>
                        <div class="col-lg-6 mb-0 d-flex align-items-center">
                            <div class="text-align-left">
                                <h1 class="h1">Trusted by Hundreds, Built for You</h1>
                                <p>
                                    With 387+ followers and counting, discover why WS Computer Suppliers is the top choice for reliable laptops.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <a class="carousel-control-prev text-decoration-none w-auto ps-3" href="#template-mo-zay-hero-carousel" role="button" data-bs-slide="prev">
            <i class="fas fa-chevron-left"></i>
        </a>
        <a class="carousel-control-next text-decoration-none w-auto pe-3" href="#template-mo-zay-hero-carousel" role="button" data-bs-slide="next">
            <i class="fas fa-chevron-right"></i>
        </a>
    </div>
    <!-- End Banner Hero -->

    <!-- Start Featured Product -->
    <section class="bg-light">
        <div class="container py-5">
            <div class="row text-center py-3">
                <div class="col-lg-6 m-auto">
                    <h1 class="h1">Featured Products</h1>
                    <p>Check out our latest additions to the shop!</p>
                </div>
            </div>
            <div class="row">
                <?php if ($has_errors || empty($featured_products)): ?>
                    <div class="col-12 error-message">
                        Unable to load featured products at this time. Please check back later or contact support.
                    </div>
                <?php else: ?>
                    <?php foreach ($featured_products as $product): 
                        $image_path = htmlspecialchars($product['image']);
                        $images = json_decode($product['images'], true) ?? ($product['image'] ? ["assets/img/products/{$product['id']}/" . $product['image']] : ['assets/img/default.jpg']);
                        $display_image = !empty($images) ? $images[0] : 'assets/img/default.jpg';
                        $base_path = realpath(__DIR__ . '/assets/img/products/' . $product['id'] . '/') ?: __DIR__ . '/assets/img/products/' . $product['id'] . '/';
                        $full_path = rtrim($base_path, '/') . '/' . basename($display_image);
                        $exists = file_exists($full_path) ? 'Yes' : 'No';
                        error_log("Featured image for {$product['name']}: Base path $base_path, Full path $full_path, Exists: $exists, Web path: $display_image, Check: " . (is_readable($full_path) ? 'Readable' : 'Not Readable'));
                    ?>
                    <div class="col-12 col-md-4 mb-4">
                        <div class="card h-100">
                            <a href="shop-single.php?id=<?php echo htmlspecialchars($product['id']); ?>">
                                <?php if (file_exists($full_path)): ?>
                                    <img src="<?php echo htmlspecialchars($display_image); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php else: ?>
                                    <div class="missing-image">Image not found: <?php echo basename($display_image); ?></div>
                                <?php endif; ?>
                            </a>
                            <div class="card-body">
                                <ul class="list-unstyled d-flex justify-content-between">
                                    <li>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="text-<?php echo $i <= ($product['rating'] ?? 0) ? 'warning' : 'muted'; ?> fa fa-star"></i>
                                        <?php endfor; ?>
                                    </li>
                                    <li class="text-muted text-right">K<?php echo number_format($product['price'] ?? 0, 2); ?></li>
                                </ul>
                                <a href="shop-single.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="h2 text-decoration-none text-dark"><?php echo htmlspecialchars($product['name'] ?: 'Unnamed Product'); ?></a>
                                <p class="card-text"><?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 100)); ?>...</p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <!-- End Featured Product -->

    <!-- Start Reviews Section -->
    <section class="bg-light">
        <div class="container py-5">
            <div class="row text-center py-3">
                <div class="col-lg-6 m-auto">
                    <h1 class="h1">Customer Reviews</h1>
                    <p>Hear what our customers have to say about us!</p>
                </div>
            </div>
            <div id="reviews-carousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <?php if (!empty($reviews)): ?>
                        <?php foreach (array_chunk($reviews, 3) as $index => $review_group): ?>
                            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                <div class="d-flex justify-content-center">
                                    <?php foreach ($review_group as $review): 
                                        $review_image_path = htmlspecialchars($review['reviewer_image']);
                                        $display_image = !empty($review_image_path) ? $review_image_path : 'assets/img/default-avatar.jpg';
                                        $base_path = realpath(__DIR__ . '/assets/img/reviews/') ?: __DIR__ . '/assets/img/reviews/';
                                        $full_path = rtrim($base_path, '/') . '/' . basename($display_image);
                                        $exists = file_exists($full_path) ? 'Yes' : 'No';
                                        error_log("Review image for {$review['reviewer_name']}: Base path $base_path, Full path $full_path, Exists: $exists, Web path: $display_image, Check: " . (is_readable($full_path) ? 'Readable' : 'Not Readable'));
                                    ?>
                                        <div class="review-card">
                                            <?php if (file_exists($full_path)): ?>
                                                <img src="<?php echo htmlspecialchars($display_image); ?>" alt="<?php echo htmlspecialchars($review['reviewer_name']); ?>" class="review-image">
                                            <?php else: ?>
                                                <div class="missing-image">Image not found: <?php echo basename($display_image); ?></div>
                                            <?php endif; ?>
                                            <p class="review-text"><?php echo htmlspecialchars($review['message']); ?></p>
                                            <p class="reviewer-name"><?php echo htmlspecialchars($review['reviewer_name']); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="carousel-item active">
                            <div class="text-center">
                                <p>No reviews available yet.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <a class="carousel-control-prev text-decoration-none w-auto ps-3" href="#reviews-carousel" role="button" data-bs-slide="prev">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <a class="carousel-control-next text-decoration-none w-auto pe-3" href="#reviews-carousel" role="button" data-bs-slide="next">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>
    </section>
    <!-- End Reviews Section -->

    <!-- Start Google Maps -->
    <section class="container py-5">
        <div class="row text-center pt-5 pb-3">
            <div class="col-lg-6 m-auto">
                <h2>Find Us on Google Maps</h2>
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3849.657282345678!2d28.34959931508512!3d-15.45505698928947!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMTXCsDI3JzE4LjIiUyAyOMKwMjEnMDYuNCJF!5e0!3m2!1sen!2szm!4v1698765432109!5m2!1sen!2szm" 
                    width="100%" 
                    height="450" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>
    </section>
    <!-- End Google Maps -->

    <!-- Start Brands of The Month -->
    <section class="container py-5">
        <div class="row text-center pt-3">
            <div class="col-lg-6 m-auto">
                <h1 class="h1">Brands of The Month</h1>
                <p>These are original branded laptops and computer accessories readily available</p>
            </div>
        </div>
        <div class="row">
            <div class="col-12 col-md-4 p-5 mt-3">
                <a href="#"><img src="./assets/img/brand (1).jpeg" class="rounded-circle img-fluid border"></a>
                <h5 class="text-center mt-3 mb-3">HP</h5>
                <p class="text-center"><a class="btn btn-success">Go Shop</a></p>
            </div>
            <div class="col-12 col-md-4 p-5 mt-3">
                <a href="#"><img src="./assets/img/brand (1).png" class="rounded-circle img-fluid border"></a>
                <h2 class="h5 text-center mt-3 mb-3">LENOVO</h2>
                <p class="text-center"><a class="btn btn-success">Go Shop</a></p>
            </div>
            <div class="col-12 col-md-4 p-5 mt-3">
                <a href="#"><img src="./assets/img/brand (2).jpg" class="rounded-circle img-fluid border"></a>
                <h2 class="h5 text-center mt-3 mb-3">ACER</h2>
                <p class="text-center"><a class="btn btn-success">Go Shop</a></p>
            </div>
            <div class="col-12 col-md-4 p-5 mt-3">
                <a href="#"><img src="./assets/img/brand (1).jpg" class="rounded-circle img-fluid border"></a>
                <h2 class="h5 text-center mt-3 mb-3">DELL</h2>
                <p class="text-center"><a class="btn btn-success">Go Shop</a></p>
            </div>
        </div>
    </section>
    <!-- End Brands of The Month -->

    <!-- Start Footer -->
    <footer class="bg-dark" id="tempaltemo_footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 pt-5">
                    <h2 class="h2 text-success border-bottom pb-3 border-light logo">WS Computer Suppliers</h2>
                    <ul class="list-unstyled text-light footer-link-list">
                        <li><i class="fas fa-map-marker-alt fa-fw"></i>Woodlands off Mosi Otunya Road</li>
                        <li><i class="fa fa-phone fa-fw"></i><a class="text-decoration-none" href="tel:+260-0979-303-059">+260-0979-303-059</a></li>
                        <li><i class="fa fa-envelope fa-fw"></i><a class="text-decoration-none" href="mailto:wscomputersuppliers@gmail.com">wscomputersuppliers@gmail.com</a></li>
                    </ul>
                </div>
                <div class="col-md-4 pt-5">
                    <h2 class="h2 text-light border-bottom pb-3 border-light">Products</h2>
                    <ul class="list-unstyled text-light footer-link-list">
                        <li><a class="text-decoration-none" href="shop.php">Lenovo Laptops</a></li>
                        <li><a class="text-decoration-none" href="shop.php">HP Laptops</a></li>
                        <li><a class="text-decoration-none" href="shop.php">DELL Laptops</a></li>
                        <li><a class="text-decoration-none" href="shop.php">APPLE MacBooks</a></li>
                        <li><a class="text-decoration-none" href="shop.php">ACER Laptops</a></li>
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
                            <a class="text-light text-decoration-none" target="_blank" href="https://www.facebook.com/profile.php?id=100075856668294"><i class="fab fa-facebook-f fa-lg fa-fw"></i></a>
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
                            Copyright © 2025 WS Computer Suppliers 
                            | Designed by <a rel="sponsored" href="" target="_blank">TechZED</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    <!-- End Footer -->

    <!-- Start Script -->
    <script src="assets/js/jquery-1.11.0.min.js"></script>
    <script src="assets/js/jquery-migrate-1.2.1.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/templatemo.js"></script>
    <script src="assets/js/custom.js"></script>
    <!-- End Script -->
</body>
</html>