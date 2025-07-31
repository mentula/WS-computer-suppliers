<?php
session_start();
require 'db_connect.php';

// Fetch product
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
try {
    $stmt = $pdo->prepare("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
} catch (PDOException $e) {
    die("Error fetching product: " . htmlspecialchars($e->getMessage()));
}

if (!$product) {
    http_response_code(404);
    die("Product not found.");
}

// Decode images JSON, fallback to single image or default
$images = json_decode($product['images'], true) ?? ($product['image'] ? ['assets/img/' . $product['image']] : ['assets/img/default.jpg']);

// Fetch contact details
try {
    $stmt = $pdo->query("SELECT contact_phone, whatsapp_number, location FROM settings LIMIT 1");
    $settings = $stmt->fetch() ?: [
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

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle Buy Now
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_now'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token.";
    } else {
        $user_name = trim($_POST['user_name'] ?? '');
        $user_email = trim($_POST['user_email'] ?? '');
        $user_phone = trim($_POST['user_phone'] ?? '');
        $quantity = isset($_POST['product_quantity']) ? (int)$_POST['product_quantity'] : 1;
        $total_price = $product['price'] * $quantity;

        if (empty($user_name) || empty($user_email) || empty($user_phone) || $quantity < 1) {
            $error = "Please fill in all fields and select a valid quantity.";
        } elseif (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email address.";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id FROM orders WHERE user_email = ? AND product_id = ? AND created_at >= NOW() - INTERVAL 5 MINUTE");
                $stmt->execute([$user_email, $product_id]);
                if ($stmt->fetch()) {
                    $error = "You recently placed an order for this product. Please check your order history or contact us.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO orders (product_id, quantity, user_name, user_email, user_phone, total_price, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())");
                    $stmt->execute([$product_id, $quantity, $user_name, $user_email, $user_phone, $total_price]);
                    $order_id = $pdo->lastInsertId();
                    $_SESSION['order_success'] = "Order placed successfully! Your order ID is #$order_id. Please contact us to complete your purchase.";
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    header("Location: order-confirmation.php?order_id=$order_id");
                    exit;
                }
            } catch (PDOException $e) {
                $error = "Failed to place order: " . htmlspecialchars($e->getMessage());
            }
        }
    }
}

if (isset($_SESSION['order_success'])) {
    $success = $_SESSION['order_success'];
    unset($_SESSION['order_success']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>WS Computer Suppliers - <?php echo htmlspecialchars($product['name']); ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?php echo htmlspecialchars(substr($product['description'], 0, 160)); ?>">
    <link rel="apple-touch-icon" href="assets/img/apple-icon.png">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/templatemo.css">
    <link rel="stylesheet" href="assets/css/custom.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@100;200;300;400;500;700;900&display=swap">
    <link rel="stylesheet" href="assets/css/fontawesome.min.css">
    <style>
        .product-image-carousel .carousel-item img {
            max-height: 400px;
            object-fit: contain;
            margin: auto;
        }
        .carousel-control-prev, .carousel-control-next {
            width: 5%;
            background-color: rgba(0, 0, 0, 0.2);
        }
        .carousel-indicators {
            bottom: -50px;
        }
        .carousel-indicators button {
            background-color: #000 !important;
        }
        .product-details {
            padding: 20px;
        }
        .product-details h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .product-details .price {
            font-size: 1.5rem;
            color: #28a745;
            font-weight: bold;
        }
        .product-details .rating {
            color: #ffc107;
        }
        .product-details .form-group {
            margin-bottom: 15px;
        }
        .product-details label {
            font-weight: bold;
        }
        .product-details input, .product-details select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .product-details .btn-buy {
            background-color: #28a745;
            color: #fff;
            padding: 10px 20px;
            font-size: 1.2rem;
        }
        .product-details .btn-buy:hover {
            background-color: #218838;
        }
        .specs-list {
            list-style: none;
            padding: 0;
        }
        .specs-list li {
            padding: 5px 0;
        }
        @media (max-width: 768px) {
            .product-image-carousel .carousel-item img {
                max-height: 300px;
            }
            .product-details h1 {
                font-size: 1.5rem;
            }
            .product-details .price {
                font-size: 1.2rem;
            }
        }
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
                    <a class="navbar-sm-brand text-light text-decoration-none" href="tel:<?php echo htmlspecialchars($settings['contact_phone']); ?>"><?php echo htmlspecialchars($settings['contact_phone']); ?></a>
                </div>
                <div>
                    <a class="text-light" href="https://fb.com/templatemo" target="_blank" rel="noopener noreferrer"><i class="fab fa-facebook-f fa-sm fa-fw me-2"></i></a>
                    <a class="text-light" href="https://www.instagram.com/" target="_blank" rel="noopener noreferrer"><i class="fab fa-instagram fa-sm fa-fw me-2"></i></a>
                    <a class="text-light" href="https://twitter.com/" target="_blank" rel="noopener noreferrer"><i class="fab fa-twitter fa-sm fa-fw me-2"></i></a>
                    <a class="text-light" href="https://www.linkedin.com/" target="_blank" rel="noopener noreferrer"><i class="fab fa-linkedin fa-sm fa-fw"></i></a>
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
                        <li class="nav-item"><a class="nav-link active" href="shop.php">Shop</a></li>
                        <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                        <li class="nav-item"><a class="nav-link" href="blog.php">Blog</a></li>
                    </ul>
                </div>
                <div class="navbar align-self-center d-flex">
                    <a class="nav-icon position-relative text-decoration-none" href="admin.php">Admin</a>
                </div>
            </div>
        </div>
    </nav>
    <!-- Close Header -->

    <!-- Start Content -->
    <div class="container py-5">
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="row">
            <!-- Product Images Carousel -->
            <div class="col-lg-6">
                <div id="productCarousel" class="carousel slide product-image-carousel" data-bs-ride="carousel">
                    <div class="carousel-indicators">
                        <?php foreach ($images as $index => $image): ?>
                            <button type="button" data-bs-target="#productCarousel" data-bs-slide-to="<?php echo $index; ?>" class="<?php echo $index === 0 ? 'active' : ''; ?>" aria-current="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-label="Slide <?php echo $index + 1; ?>"></button>
                        <?php endforeach; ?>
                    </div>
                    <div class="carousel-inner">
                        <?php foreach ($images as $index => $image): ?>
                            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                <img src="<?php echo htmlspecialchars($image); ?>" class="d-block w-100" alt="<?php echo htmlspecialchars($product['name']); ?> Image <?php echo $index + 1; ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>
            </div>

            <!-- Product Details -->
            <div class="col-lg-6 product-details">
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="text-muted">Category: <?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></p>
                <p class="price">K<?php echo number_format($product['price'], 2); ?></p>
                <div class="rating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star <?php echo $i <= $product['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                    <?php endfor; ?>
                    (<?php echo htmlspecialchars($product['rating']); ?> / 5)
                </div>
                <p class="mt-3"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>

                <?php if (!empty($product['specs'])): ?>
                    <h4>Specifications</h4>
                    <ul class="specs-list">
                        <?php foreach (explode(',', $product['specs']) as $spec): ?>
                            <li><?php echo htmlspecialchars(trim($spec)); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <form method="post" class="mt-4">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <div class="form-group">
                        <label for="product_quantity">Quantity</label>
                        <input type="number" id="product_quantity" name="product_quantity" min="1" value="1" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="user_name">Full Name</label>
                        <input type="text" id="user_name" name="user_name" class="form-control" value="<?php echo htmlspecialchars($_POST['user_name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="user_email">Email Address</label>
                        <input type="email" id="user_email" name="user_email" class="form-control" value="<?php echo htmlspecialchars($_POST['user_email'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="user_phone">Phone Number</label>
                        <input type="tel" id="user_phone" name="user_phone" class="form-control" value="<?php echo htmlspecialchars($_POST['user_phone'] ?? ''); ?>" required>
                    </div>
                    <button type="submit" name="buy_now" class="btn btn-buy">Buy Now</button>
                </form>
            </div>
        </div>
    </div>
    <!-- End Content -->

    <!-- Start Footer -->
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
                            <a class="text-light text-decoration-none" target="_blank" href="http://facebook.com/" rel="noopener noreferrer"><i class="fab fa-facebook-f fa-lg fa-fw"></i></a>
                        </li>
                        <li class="list-inline-item border border-light rounded-circle text-center">
                            <a class="text-light text-decoration-none" target="_blank" href="https://www.instagram.com/" rel="noopener noreferrer"><i class="fab fa-instagram fa-lg fa-fw"></i></a>
                        </li>
                        <li class="list-inline-item border border-light rounded-circle text-center">
                            <a class="text-light text-decoration-none" target="_blank" href="https://twitter.com/" rel="noopener noreferrer"><i class="fab fa-twitter fa-lg fa-fw"></i></a>
                        </li>
                        <li class="list-inline-item border border-light rounded-circle text-center">
                            <a class="text-light text-decoration-none" target="_blank" href="https://www.linkedin.com/" rel="noopener noreferrer"><i class="fab fa-linkedin fa-lg fa-fw"></i></a>
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
                            | Designed by <a rel="sponsored" href="" target="_blank" rel="noopener noreferrer">TechZED</a>
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