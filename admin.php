<?php
session_start();
require_once 'db_connect.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize variables
$error = '';
$success = '';

// Fetch categories
try {
    $stmt = $pdo->query("SELECT id, name FROM categories");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
    $error = "Failed to fetch categories: " . htmlspecialchars($e->getMessage());
}

// Fetch products with category names
try {
    $stmt = $pdo->query("SELECT p.id, p.name, c.name AS category_name, p.images, p.image FROM products p LEFT JOIN categories c ON p.category_id = c.id");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $products = [];
    $error = "Failed to fetch products: " . htmlspecialchars($e->getMessage());
}

// Fetch blog posts
try {
    $stmt = $pdo->query("SELECT id, title FROM blog_posts");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $posts = [];
    $error = "Failed to fetch blog posts: " . htmlspecialchars($e->getMessage());
}

// Fetch existing admins
try {
    $stmt = $pdo->query("SELECT id, username FROM admins");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $admins = [];
    $error = "Failed to fetch admins: " . htmlspecialchars($e->getMessage());
}

// Fetch orders
try {
    $stmt = $pdo->query("SELECT o.id, o.user_name, o.user_email, o.user_phone, o.quantity, o.total_price, o.status, o.created_at, p.name AS product_name 
                         FROM orders o LEFT JOIN products p ON o.product_id = p.id");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $orders = [];
    $error = "Failed to fetch orders: " . htmlspecialchars($e->getMessage());
}

// Fetch settings
try {
    $stmt = $pdo->query("SELECT contact_phone, whatsapp_number, location FROM settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
        'contact_phone' => '+260-0979-303-059',
        'whatsapp_number' => '+260-0979-303-059',
        'location' => 'Woodlands off Mosi Otunya Road'
    ];
} catch (PDOException $e) {
    $settings = [];
    $error = "Failed to fetch settings: " . htmlspecialchars($e->getMessage());
}

// Fetch about page content
try {
    $stmt = $pdo->query("SELECT content FROM about_page LIMIT 1");
    $about_content = $stmt->fetch(PDO::FETCH_ASSOC)['content'] ?? '';
} catch (PDOException $e) {
    $about_content = '';
    $error = "Failed to fetch about page content: " . htmlspecialchars($e->getMessage());
}

// Fetch contact messages
try {
    $stmt = $pdo->query("SELECT id, name, email, subject, message, status, created_at FROM contact_messages ORDER BY created_at DESC");
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $messages = [];
    $error = "Failed to fetch contact messages: " . htmlspecialchars($e->getMessage());
}

// Fetch reviews
try {
    $stmt = $pdo->query("SELECT id, reviewer_name, reviewer_image, message, created_at FROM reviews ORDER BY created_at DESC");
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $reviews = [];
    $error = "Failed to fetch reviews: " . htmlspecialchars($e->getMessage());
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token.";
    } else {
        // Add new admin
        if (isset($_POST['add_admin'])) {
            $new_username = trim($_POST['username']);
            $new_password = trim($_POST['password']);
            if (!empty($new_username) && !empty($new_password)) {
                try {
                    $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
                    $stmt->execute([$new_username]);
                    if ($stmt->fetch()) {
                        $error = "Username already exists.";
                    } else {
                        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                        $stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
                        if ($stmt->execute([$new_username, $hashed_password])) {
                            $success = "New admin added successfully!";
                            header('Location: admin.php');
                            exit;
                        } else {
                            $error = "Failed to add admin.";
                        }
                    }
                } catch (PDOException $e) {
                    $error = "Database error: " . htmlspecialchars($e->getMessage());
                }
            } else {
                $error = "Please fill in both username and password.";
            }
        }

        // Reset admin password
        if (isset($_POST['reset_password'])) {
            $admin_id = $_POST['admin_id'];
            $new_password = trim($_POST['new_password']);
            if (!empty($new_password)) {
                try {
                    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
                    if ($stmt->execute([$hashed_password, $admin_id])) {
                        $success = "Password reset successfully!";
                        header('Location: admin.php');
                        exit;
                    } else {
                        $error = "Failed to reset password.";
                    }
                } catch (PDOException $e) {
                    $error = "Database error: " . htmlspecialchars($e->getMessage());
                }
            } else {
                $error = "Please enter a new password.";
            }
        }

        // Add category
        if (isset($_POST['add_category'])) {
            $category_name = trim($_POST['category_name']);
            if (!empty($category_name)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
                    $stmt->execute([$category_name]);
                    $success = "Category added successfully!";
                    header('Location: admin.php');
                    exit;
                } catch (PDOException $e) {
                    $error = "Failed to add category: " . htmlspecialchars($e->getMessage());
                }
            } else {
                $error = "Category name cannot be empty.";
            }
        }

        // Add product
        if (isset($_POST['add_product'])) {
            $category_id = $_POST['category_id'];
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $price = $_POST['price'];
            $rating = $_POST['rating'];
            $specs = trim($_POST['specs']);
            $images = [];

            try {
                // Insert product to get ID
                $stmt = $pdo->prepare("INSERT INTO products (category_id, name, description, price, rating, specs, images) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$category_id, $name, $description, $price, $rating, $specs, json_encode($images)]);
                $product_id = $pdo->lastInsertId();

                // Create product-specific directory
                $target_dir = "assets/img/products/$product_id/";
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0755, true);
                }

                // Handle multiple image uploads
                if (!empty($_FILES['images']['name'][0])) {
                    foreach ($_FILES['images']['name'] as $key => $image_name) {
                        if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                            $target = $target_dir . basename($image_name);
                            if (move_uploaded_file($_FILES['images']['tmp_name'][$key], $target)) {
                                $images[] = $target;
                            } else {
                                error_log("Failed to move file: " . $image_name . " to $target");
                            }
                        }
                    }
                    // Update product with image paths
                    $stmt = $pdo->prepare("UPDATE products SET images = ? WHERE id = ?");
                    $stmt->execute([json_encode($images), $product_id]);
                }

                $success = "Product added successfully!";
                header('Location: admin.php');
                exit;
            } catch (PDOException $e) {
                $error = "Failed to add product: " . htmlspecialchars($e->getMessage());
            }
        }

        // Update product
        if (isset($_POST['update_product'])) {
            $product_id = $_POST['product_id'];
            $category_id = $_POST['category_id'];
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $price = $_POST['price'];
            $rating = $_POST['rating'];
            $specs = trim($_POST['specs']);
            $new_images = [];

            try {
                // Fetch existing images
                $stmt = $pdo->prepare("SELECT images FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $current_images = json_decode($stmt->fetchColumn(), true) ?? [];

                // Create product-specific directory if it doesn't exist
                $target_dir = "assets/img/products/$product_id/";
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0755, true);
                }

                // Handle new image uploads
                if (!empty($_FILES['images']['name'][0])) {
                    foreach ($_FILES['images']['name'] as $key => $image_name) {
                        if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                            $target = $target_dir . basename($image_name);
                            if (move_uploaded_file($_FILES['images']['tmp_name'][$key], $target)) {
                                $new_images[] = $target;
                            } else {
                                error_log("Failed to move file: " . $image_name . " to $target");
                            }
                        }
                    }
                    // Merge new images with existing ones
                    $images = array_merge($current_images, $new_images);
                } else {
                    $images = $current_images;
                }

                // Update product
                $stmt = $pdo->prepare("UPDATE products SET category_id = ?, name = ?, description = ?, price = ?, rating = ?, specs = ?, images = ? WHERE id = ?");
                $stmt->execute([$category_id, $name, $description, $price, $rating, $specs, json_encode($images), $product_id]);

                $success = "Product updated successfully!";
                header('Location: admin.php');
                exit;
            } catch (PDOException $e) {
                $error = "Failed to update product: " . htmlspecialchars($e->getMessage());
            }
        }

        // Delete product image
        if (isset($_POST['delete_image'])) {
            $product_id = $_POST['product_id'];
            $image_path = $_POST['image_path'];

            try {
                // Fetch current images
                $stmt = $pdo->prepare("SELECT images FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $images = json_decode($stmt->fetchColumn(), true) ?? [];

                // Remove the image from the array and file system
                if (($key = array_search($image_path, $images)) !== false) {
                    unset($images[$key]);
                    if (file_exists($image_path)) {
                        unlink($image_path);
                    }
                    // Update the database
                    $stmt = $pdo->prepare("UPDATE products SET images = ? WHERE id = ?");
                    $stmt->execute([json_encode(array_values($images)), $product_id]);
                    $success = "Image deleted successfully!";
                } else {
                    $error = "Image not found.";
                }
                header('Location: admin.php');
                exit;
            } catch (PDOException $e) {
                $error = "Failed to delete image: " . htmlspecialchars($e->getMessage());
            }
        }

        // Add blog post
        if (isset($_POST['add_post'])) {
            $title = trim($_POST['title']);
            $content = trim($_POST['content']);
            $author = trim($_POST['author']);
            $image = '';
            if (!empty($_FILES['image']['name'])) {
                $image = 'assets/img/' . basename($_FILES['image']['name']);
                move_uploaded_file($_FILES['image']['tmp_name'], $image);
            }
            if (!empty($title) && !empty($content) && !empty($author)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO blog_posts (title, content, author, image) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$title, $content, $author, $image]);
                    $success = "Blog post added successfully!";
                    header('Location: admin.php');
                    exit;
                } catch (PDOException $e) {
                    $error = "Failed to add blog post: " . htmlspecialchars($e->getMessage());
                }
            } else {
                $error = "All blog post fields are required.";
            }
        }

        // Update order status
        if (isset($_POST['update_order'])) {
            $order_id = $_POST['order_id'];
            $status = $_POST['status'];
            try {
                $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
                $stmt->execute([$status, $order_id]);
                $success = "Order status updated!";
                header('Location: admin.php');
                exit;
            } catch (PDOException $e) {
                $error = "Failed to update order: " . htmlspecialchars($e->getMessage());
            }
        }

        // Update settings
        if (isset($_POST['update_settings'])) {
            $contact_phone = trim($_POST['contact_phone']);
            $whatsapp_number = trim($_POST['whatsapp_number']);
            $location = trim($_POST['location']);
            try {
                $stmt = $pdo->prepare("UPDATE settings SET contact_phone = ?, whatsapp_number = ?, location = ? WHERE id = 1");
                $stmt->execute([$contact_phone, $whatsapp_number, $location]);
                $success = "Settings updated!";
                header('Location: admin.php');
                exit;
            } catch (PDOException $e) {
                $error = "Failed to update settings: " . htmlspecialchars($e->getMessage());
            }
        }

        // Update about page
        if (isset($_POST['update_about'])) {
            $new_content = htmlspecialchars(trim($_POST['about_content']));
            if ($new_content) {
                try {
                    $pdo->exec("TRUNCATE TABLE about_page");
                    $stmt = $pdo->prepare("INSERT INTO about_page (content) VALUES (?)");
                    $stmt->execute([$new_content]);
                    $success = "About page updated successfully!";
                    header('Location: admin.php');
                    exit;
                } catch (PDOException $e) {
                    $error = "Failed to update about page: " . htmlspecialchars($e->getMessage());
                }
            } else {
                $error = "About content cannot be empty.";
            }
        }

        // Add review
        if (isset($_POST['add_review'])) {
            $reviewer_name = trim($_POST['reviewer_name']);
            $message = trim($_POST['message']);
            $image = $_FILES['reviewer_image']['name'];
            $target = 'assets/img/reviews/' . basename($image);
            if (!empty($reviewer_name) && !empty($message) && !empty($image)) {
                if (!is_dir('assets/img/reviews')) {
                    mkdir('assets/img/reviews', 0755, true);
                }
                if (move_uploaded_file($_FILES['reviewer_image']['tmp_name'], $target)) {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO reviews (reviewer_name, reviewer_image, message) VALUES (?, ?, ?)");
                        $stmt->execute([$reviewer_name, $target, $message]);
                        $success = "Review added successfully!";
                        header('Location: admin.php');
                        exit;
                    } catch (PDOException $e) {
                        $error = "Failed to add review: " . htmlspecialchars($e->getMessage());
                    }
                } else {
                    $error = "Failed to upload reviewer image.";
                }
            } else {
                $error = "All review fields are required.";
            }
        }

        // Update review
        if (isset($_POST['update_review'])) {
            $review_id = $_POST['review_id'];
            $reviewer_name = trim($_POST['reviewer_name']);
            $message = trim($_POST['message']);
            $image = !empty($_FILES['reviewer_image']['name']) ? $_FILES['reviewer_image']['name'] : null;
            try {
                if ($image) {
                    $target = 'assets/img/reviews/' . basename($image);
                    if (!is_dir('assets/img/reviews')) {
                        mkdir('assets/img/reviews', 0755, true);
                    }
                    if (move_uploaded_file($_FILES['reviewer_image']['tmp_name'], $target)) {
                        $stmt = $pdo->prepare("UPDATE reviews SET reviewer_name = ?, reviewer_image = ?, message = ? WHERE id = ?");
                        $stmt->execute([$reviewer_name, $target, $message, $review_id]);
                    } else {
                        $error = "Failed to upload new reviewer image.";
                    }
                } else {
                    $stmt = $pdo->prepare("UPDATE reviews SET reviewer_name = ?, message = ? WHERE id = ?");
                    $stmt->execute([$reviewer_name, $message, $review_id]);
                }
                if (!$error) {
                    $success = "Review updated successfully!";
                    header('Location: admin.php');
                    exit;
                }
            } catch (PDOException $e) {
                $error = "Failed to update review: " . htmlspecialchars($e->getMessage());
            }
        }

        // Delete review
        if (isset($_POST['delete_review'])) {
            $review_id = $_POST['review_id'];
            try {
                $stmt = $pdo->prepare("SELECT reviewer_image FROM reviews WHERE id = ?");
                $stmt->execute([$review_id]);
                $review = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($review && file_exists($review['reviewer_image'])) {
                    unlink($review['reviewer_image']);
                }
                $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
                $stmt->execute([$review_id]);
                $success = "Review deleted successfully!";
                header('Location: admin.php');
                exit;
            } catch (PDOException $e) {
                $error = "Failed to delete review: " . htmlspecialchars($e->getMessage());
            }
        }
    }
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    try {
        if ($_GET['action'] === 'getMessage') {
            $id = $_GET['id'] ?? 0;
            $stmt = $pdo->prepare("SELECT id, name, email, subject, message, status FROM contact_messages WHERE id = ?");
            $stmt->execute([$id]);
            $message = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($message) {
                echo json_encode(['success' => true, 'message' => $message]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Message not found']);
            }
        } elseif ($_GET['action'] === 'updateMessageStatus' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("UPDATE contact_messages SET status = ? WHERE id = ?");
            $stmt->execute([$input['status'], $input['id']]);
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        } elseif ($_GET['action'] === 'deleteMessage' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
            $stmt->execute([$input['id']]);
            echo json_encode(['success' => true, 'message' => 'Message deleted successfully']);
        } elseif ($_GET['action'] === 'getProduct') {
            $id = $_GET['id'] ?? 0;
            $stmt = $pdo->prepare("SELECT p.id, p.category_id, p.name, p.description, p.price, p.rating, p.specs, p.images FROM products p WHERE id = ?");
            $stmt->execute([$id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($product) {
                $product['images'] = json_decode($product['images'], true) ?? [];
                echo json_encode(['success' => true, 'product' => $product]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Product not found']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . htmlspecialchars($e->getMessage())]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>WS Computer Suppliers - Admin Panel</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="apple-touch-icon" href="assets/img/apple-icon.png">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/templatemo.css">
    <link rel="stylesheet" href="assets/css/custom.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@100;200;300;400;500;700;900&display=swap">
    <link rel="stylesheet" href="assets/css/fontawesome.min.css">
    <style>
        .admin-container { max-width: 1200px; margin: 20px auto; }
        .nav-tabs { margin-bottom: 20px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .status-pending { background: #ffc107; color: #000; padding: 4px 8px; border-radius: 4px; }
        .status-read { background: #007bff; color: #fff; padding: 4px 8px; border-radius: 3px; }
        .status-replied { background: #28a745; color: #fff; padding: 4px 8px; border-radius: 3px; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
        .modal-content { background: #fff; margin: 10% auto; padding: 20px; width: 80%; max-width: 600px; border-radius: 5px; }
        .modal.show { display: flex; justify-content: center; align-items: center; }
        .form-group { margin-bottom: 15px; }
        .form-group label { font-weight: bold; }
        .form-group textarea, .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .form-group textarea { height: 150px; }
        .list-group-item { word-break: break-word; }
        .review-image img { max-width: 100px; height: auto; }
        .product-images img { max-width: 50px; height: auto; margin-right: 5px; }
    </style>
</head>
<body>
    <!-- Start Top Nav -->
    <nav class="navbar navbar-expand-lg bg-dark navbar-light d-none d-lg-block" id="templatemo_nav_top">
        <div class="container">
            <div class="w-100 d-flex justify-content-between">
                <div>
                    <i class="fa fa-envelope mx-2"></i>
                    <a class="navbar-sm-brand text-light text-decoration-none" href="mailto:wscomputersuppliers@gmail.com">wscomputersuppliers@gmail.com</a>
                    <i class="fa fa-phone mx-2"></i>
                    <a class="navbar-sm-brand text-light text-decoration-none" href="tel:<?php echo htmlspecialchars($settings['contact_phone']); ?>"><?php echo htmlspecialchars($settings['contact_phone']); ?></a>
                </div>
                <div>
                    <a class="text-light" href="https://fb.com/templatemo" target="_blank"><i class="fab fa-facebook-f fa-sm fa-fw me-2"></i></a>
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
                <div class="navbar align-self-center d-flex">
                    <a class="nav-icon position-relative text-decoration-none" href="admin.php">Admin</a>
                    <a class="nav-icon position-relative text-decoration-none" href="admin_logout.php">Logout</a>
                </div>
            </div>
        </div>
    </nav>
    <!-- Close Header -->

    <!-- Start Content -->
    <div class="container py-5">
        <h1 class="h1 text-center">WS Computer Suppliers Admin Panel</h1>

        <!-- Display Errors or Success -->
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- Tab Navigation -->
        <ul class="nav nav-tabs" id="adminTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="orders-tab" data-bs-toggle="tab" href="#manage-orders" role="tab">Manage Orders</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="products-tab" data-bs-toggle="tab" href="#manage-products" role="tab">Manage Products</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="categories-tab" data-bs-toggle="tab" href="#manage-categories" role="tab">Manage Categories</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="posts-tab" data-bs-toggle="tab" href="#manage-posts" role="tab">Manage Blog Posts</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="admins-tab" data-bs-toggle="tab" href="#manage-admins" role="tab">Manage Admins</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="settings-tab" data-bs-toggle="tab" href="#manage-settings" role="tab">Manage Settings</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="about-tab" data-bs-toggle="tab" href="#manage-about" role="tab">Manage About Page</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="messages-tab" data-bs-toggle="tab" href="#manage-messages" role="tab">Manage Messages</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="reviews-tab" data-bs-toggle="tab" href="#manage-reviews" role="tab">Manage Reviews</a>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="adminTabsContent">
            <!-- Manage Orders -->
            <div class="tab-pane fade show active" id="manage-orders" role="tabpanel">
                <h2>Manage Orders</h2>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product</th>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Quantity</th>
                            <th>Total (ZMW)</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['id']); ?></td>
                                <td><?php echo htmlspecialchars($order['product_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($order['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['user_email']); ?></td>
                                <td><?php echo htmlspecialchars($order['user_phone']); ?></td>
                                <td><?php echo htmlspecialchars($order['quantity']); ?></td>
                                <td><?php echo number_format($order['total_price'], 2); ?></td>
                                <td><span class="status-<?php echo htmlspecialchars($order['status']); ?>"><?php echo ucfirst($order['status']); ?></span></td>
                                <td><?php echo date('M j, Y H:i', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <form method="post">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['id']); ?>">
                                        <select name="status" class="form-control d-inline w-auto">
                                            <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                        <button type="submit" name="update_order" class="btn btn-primary btn-sm">Update</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Manage Products -->
            <div class="tab-pane fade" id="manage-products" role="tabpanel">
                <h2>Manage Products</h2>
                <h3>Add New Product</h3>
                <form method="post" enctype="multipart/form-data" class="mb-4">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <div class="form-group">
                        <label for="category_id" class="form-label">Category</label>
                        <select class="form-control" id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['id']); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="name" class="form-label">Product Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="price" class="form-label">Price (ZMW)</label>
                        <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                    </div>
                    <div class="form-group">
                        <label for="rating" class="form-label">Rating (1-5)</label>
                        <input type="number" min="1" max="5" class="form-control" id="rating" name="rating" required>
                    </div>
                    <div class="form-group">
                        <label for="images" class="form-label">Images (Select multiple)</label>
                        <input type="file" class="form-control" id="images" name="images[]" accept="image/*" multiple required>
                    </div>
                    <div class="form-group">
                        <label for="specs" class="form-label">Specifications (comma-separated)</label>
                        <input type="text" class="form-control" id="specs" name="specs" required>
                    </div>
                    <button type="submit" name="add_product" class="btn btn-success">Add Product</button>
                </form>

                <h3>Existing Products</h3>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Images</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['id']); ?></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                                <td class="product-images">
                                    <?php
                                    $images = json_decode($product['images'], true) ?? ($product['image'] ? [$product['image']] : []);
                                    foreach ($images as $image): ?>
                                        <img src="<?php echo htmlspecialchars($image); ?>" alt="Product Image">
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); ?>">
                                            <input type="hidden" name="image_path" value="<?php echo htmlspecialchars($image); ?>">
                                            <button type="submit" name="delete_image" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this image?')">Delete</button>
                                        </form>
                                    <?php endforeach; ?>
                                </td>
                                <td>
                                    <button class="btn btn-primary btn-sm" onclick="editProduct(<?php echo htmlspecialchars($product['id']); ?>)">Edit</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Manage Categories -->
            <div class="tab-pane fade" id="manage-categories" role="tabpanel">
                <h2>Manage Categories</h2>
                <form method="post" class="mb-4">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <div class="form-group">
                        <label for="category_name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="category_name" name="category_name" required>
                    </div>
                    <button type="submit" name="add_category" class="btn btn-success">Add Category</button>
                </form>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($category['id']); ?></td>
                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Manage Blog Posts -->
            <div class="tab-pane fade" id="manage-posts" role="tabpanel">
                <h2>Manage Blog Posts</h2>
                <form method="post" enctype="multipart/form-data" class="mb-4">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <div class="form-group">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="content" class="form-label">Content</label>
                        <textarea class="form-control" id="content" name="content" rows="10" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="image" class="form-label">Featured Image</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label for="author" class="form-label">Author</label>
                        <input type="text" class="form-control" id="author" name="author" required>
                    </div>
                    <button type="submit" name="add_post" class="btn btn-success">Add Post</button>
                </form>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $post): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($post['id']); ?></td>
                                <td><?php echo htmlspecialchars($post['title']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Manage Admins -->
            <div class="tab-pane fade" id="manage-admins" role="tabpanel">
                <h2>Add New Admin</h2>
                <form method="post" class="col-md-6 m-auto mb-4">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" name="add_admin" class="btn btn-success">Add Admin</button>
                </form>

                <h2>Reset Admin Password</h2>
                <form method="post" class="col-md-6 m-auto mb-4">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <div class="form-group">
                        <label for="admin_id" class="form-label">Select Admin</label>
                        <select class="form-control" id="admin_id" name="admin_id" required>
                            <option value="">Select Admin</option>
                            <?php foreach ($admins as $admin): ?>
                                <option value="<?php echo htmlspecialchars($admin['id']); ?>"><?php echo htmlspecialchars($admin['username']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                    <button type="submit" name="reset_password" class="btn btn-warning">Reset Password</button>
                </form>

                <h2>Existing Admins</h2>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $admin): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($admin['id']); ?></td>
                                <td><?php echo htmlspecialchars($admin['username']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Manage Settings -->
            <div class="tab-pane fade" id="manage-settings" role="tabpanel">
                <h2>Manage Contact Settings</h2>
                <form method="post" class="col-md-6 m-auto">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <div class="form-group">
                        <label for="contact_phone" class="form-label">Contact Phone</label>
                        <input type="text" class="form-control" id="contact_phone" name="contact_phone" value="<?php echo htmlspecialchars($settings['contact_phone']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="whatsapp_number" class="form-label">WhatsApp Number</label>
                        <input type="text" class="form-control" id="whatsapp_number" name="whatsapp_number" value="<?php echo htmlspecialchars($settings['whatsapp_number']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="location" class="form-label">Location</label>
                        <textarea class="form-control" id="location" name="location" required><?php echo htmlspecialchars($settings['location']); ?></textarea>
                    </div>
                    <button type="submit" name="update_settings" class="btn btn-primary">Update</button>
                </form>
            </div>

            <!-- Manage About Page -->
            <div class="tab-pane fade" id="manage-about" role="tabpanel">
                <h2>Manage About Page Content</h2>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <div class="form-group">
                        <label for="about_content" class="form-label">About Page Content</label>
                        <textarea class="form-control" id="about_content" name="about_content" rows="10"><?php echo htmlspecialchars($about_content); ?></textarea>
                    </div>
                    <button type="submit" name="update_about" class="btn btn-primary">Update</button>
                </form>
            </div>

            <!-- Manage Messages -->
            <div class="tab-pane fade" id="manage-messages" role="tabpanel">
                <h2>Contact Messages</h2>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $message): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($message['id']); ?></td>
                                <td><?php echo htmlspecialchars($message['name']); ?></td>
                                <td><?php echo htmlspecialchars($message['email']); ?></td>
                                <td><?php echo htmlspecialchars($message['subject']); ?></td>
                                <td><span class="status-<?php echo htmlspecialchars($message['status']); ?>"><?php echo ucfirst($message['status']); ?></span></td>
                                <td><?php echo date('M j, Y H:i', strtotime($message['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="viewMessage(<?php echo htmlspecialchars($message['id']); ?>)">View</button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteMessage(<?php echo htmlspecialchars($message['id']); ?>)">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Manage Reviews -->
            <div class="tab-pane fade" id="manage-reviews" role="tabpanel">
                <h2>Manage Reviews</h2>
                <!-- Add Review Form -->
                <h3>Add New Review</h3>
                <form method="POST" enctype="multipart/form-data" class="mb-4">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <div class="form-group">
                        <label for="reviewer_name" class="form-label">Reviewer Name</label>
                        <input type="text" class="form-control" id="reviewer_name" name="reviewer_name" required>
                    </div>
                    <div class="form-group">
                        <label for="reviewer_image" class="form-label">Reviewer Image</label>
                        <input type="file" class="form-control" id="reviewer_image" name="reviewer_image" accept="image/*" required>
                    </div>
                    <div class="form-group">
                        <label for="message" class="form-label">Review Message</label>
                        <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                    </div>
                    <button type="submit" name="add_review" class="btn btn-success">Add Review</button>
                </form>

                <!-- Existing Reviews -->
                <h3>Existing Reviews</h3>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Reviewer Name</th>
                            <th>Image</th>
                            <th>Message</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reviews as $review): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($review['id']); ?></td>
                                <td><?php echo htmlspecialchars($review['reviewer_name']); ?></td>
                                <td class="review-image"><img src="<?php echo htmlspecialchars($review['reviewer_image']); ?>" alt="Reviewer"></td>
                                <td><?php echo htmlspecialchars(substr($review['message'], 0, 100)); ?>...</td>
                                <td><?php echo date('M j, Y', strtotime($review['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="editReview(<?php echo htmlspecialchars($review['id']); ?>, '<?php echo htmlspecialchars($review['reviewer_name']); ?>', '<?php echo htmlspecialchars($review['message']); ?>')">Edit</button>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="review_id" value="<?php echo htmlspecialchars($review['id']); ?>">
                                        <button type="submit" name="delete_review" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this review?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
                            <a class="text-light text-decoration-none" target="_blank" href="http://facebook.com/"><i class="fab fa-facebook-f fa-lg fa-fw"></i></a>
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
    <script src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
    <script src="assets/js/jquery-migrate-1.2.1.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/templatemo.js"></script>
    <script src="assets/js/custom.js"></script>
    <script>
        function viewMessage(id) {
            fetch('admin.php?action=getMessage&id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const message = data.message;
                        const modal = document.createElement('div');
                        modal.className = 'modal';
                        modal.innerHTML = `
                            <div class="modal-content">
                                <h2>View Message</h2>
                                <form id="modal-form">
                                    <div class="form-group">
                                        <label for="name">Name</label>
                                        <input type="text" id="name" value="${message.name}" readonly class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input type="email" id="email" value="${message.email}" readonly class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label for="subject">Subject</label>
                                        <input type="text" id="subject" value="${message.subject}" readonly class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label for="message">Message</label>
                                        <textarea id="message" readonly class="form-control">${message.message}</textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="status">Status</label>
                                        <select id="status" name="status" class="form-control">
                                            <option value="pending" ${message.status === 'pending' ? 'selected' : ''}>Pending</option>
                                            <option value="read" ${message.status === 'read' ? 'selected' : ''}>Read</option>
                                            <option value="replied" ${message.status === 'replied' ? 'selected' : ''}>Replied</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Update Status</button>
                                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
                                </form>
                            </div>
                        `;
                        modal.querySelector('form').addEventListener('submit', (e) => {
                            e.preventDefault();
                            const status = document.getElementById('status').value;
                            fetch('admin.php?action=updateMessageStatus', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ id: id, status: status })
                            })
                            .then(res => res.json())
                            .then(result => {
                                alert(result.message);
                                if (result.success) location.reload();
                            });
                        });
                        document.body.appendChild(modal);
                        modal.classList.add('show');
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => alert('Error fetching message: ' + error));
        }

        function deleteMessage(id) {
            if (confirm('Are you sure you want to delete this message?')) {
                fetch('admin.php?action=deleteMessage', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                })
                .then(res => res.json())
                .then(result => {
                    alert(result.message);
                    if (result.success) location.reload();
                })
                .catch(error => alert('Error deleting message: ' + error));
            }
        }

        function editReview(id, name, message) {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <h2>Edit Review</h2>
                    <form id="edit-review-form" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="review_id" value="${id}">
                        <div class="form-group">
                            <label for="reviewer_name">Reviewer Name</label>
                            <input type="text" id="reviewer_name" name="reviewer_name" value="${name}" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="reviewer_image">New Image (Optional)</label>
                            <input type="file" id="reviewer_image" name="reviewer_image" accept="image/*" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" class="form-control" rows="5" required>${message}</textarea>
                        </div>
                        <button type="submit" name="update_review" class="btn btn-primary">Update Review</button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    </form>
                </div>
            `;
            document.body.appendChild(modal);
            modal.classList.add('show');
        }

        function editProduct(id) {
            fetch('admin.php?action=getProduct&id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const product = data.product;
                        const modal = document.createElement('div');
                        modal.className = 'modal';
                        modal.innerHTML = `
                            <div class="modal-content">
                                <h2>Edit Product</h2>
                                <form id="edit-product-form" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="product_id" value="${id}">
                                    <div class="form-group">
                                        <label for="category_id">Category</label>
                                        <select class="form-control" id="category_id" name="category_id" required>
                                            <option value="">Select Category</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo htmlspecialchars($category['id']); ?>" ${product.category_id == <?php echo $category['id']; ?> ? 'selected' : ''}><?php echo htmlspecialchars($category['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Product Name</label>
                                        <input type="text" id="name" name="name" value="${product.name}" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="description">Description</label>
                                        <textarea id="description" name="description" class="form-control" required>${product.description}</textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="price">Price (ZMW)</label>
                                        <input type="number" step="0.01" id="price" name="price" value="${product.price}" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="rating">Rating (1-5)</label>
                                        <input type="number" min="1" max="5" id="rating" name="rating" value="${product.rating}" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="images">Add New Images (Select multiple)</label>
                                        <input type="file" id="images" name="images[]" accept="image/*" class="form-control" multiple>
                                    </div>
                                    <div class="form-group">
                                        <label for="specs">Specifications (comma-separated)</label>
                                        <input type="text" id="specs" name="specs" value="${product.specs}" class="form-control" required>
                                    </div>
                                    <button type="submit" name="update_product" class="btn btn-primary">Update Product</button>
                                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                                </form>
                            </div>
                        `;
                        document.body.appendChild(modal);
                        modal.classList.add('show');
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => alert('Error fetching product: ' + error));
        }

        function closeModal() {
            const modal = document.querySelector('.modal');
            if (modal) modal.remove();
        }
    </script>
    <!-- End Script -->
</body>
</html>