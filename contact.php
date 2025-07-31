<?php
session_start();
require_once 'db_connect.php';

$pageTitle = "Contact Us";
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars(trim($_POST['name'] ?? ''));
    $email = htmlspecialchars(trim($_POST['email'] ?? ''));
    $subject = htmlspecialchars($_POST['subject'] ?? '');
    $messageText = htmlspecialchars(trim($_POST['message'] ?? ''));

    if ($name && $email && $subject && $messageText) {
        try {
            $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $subject, $messageText]);
            $message = 'Thank you for your message! We will get back to you soon.';
            $messageType = 'success';
        } catch (PDOException $e) {
            error_log("Message insertion error: " . $e->getMessage());
            $message = 'Sorry, there was an error sending your message. Please try again.';
            $messageType = 'danger';
        }
    } else {
        $message = 'Please fill in all fields.';
        $messageType = 'danger';
    }
}

// Fetch contact details
$stmt = $pdo->query("SELECT contact_phone, whatsapp_number, location FROM settings LIMIT 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
    'contact_phone' => '+260-0979-303-059',
    'whatsapp_number' => '+260-0979-303-059',
    'location' => 'Woodlands off Mosi Otunya Road'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Contact Us - WS Computer Suppliers</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="apple-touch-icon" href="assets/img/apple-icon.png">
   
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/templatemo.css">
    <link rel="stylesheet" href="assets/css/custom.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@100;200;300;400;500;700;900&display=swap">
    <link rel="stylesheet" href="assets/css/fontawesome.min.css">
    <style>
        .contact-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .contact-method { display: flex; align-items: center; margin-bottom: 15px; }
        .contact-method i { font-size: 1.5em; margin-right: 10px; color: #28a745; }
        .contact-method div { flex: 1; }
        .contact-method h4 { margin: 0; font-size: 1.1em; }
        .contact-method p { margin: 5px 0 0; }
        .map-container { margin-top: 10px; }
        .map-container iframe { width: 100%; height: 200px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { font-weight: bold; }
        .form-group input, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .form-group textarea { height: 150px; }
        @media (max-width: 768px) { .contact-grid { grid-template-columns: 1fr; } }
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
                
            </div>
        </div>
    </nav>
    <!-- Close Header -->

    <!-- Modal Search -->
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

    <!-- Start Content -->
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <h1 class="h1 text-center mb-4">Contact WS Computer Suppliers</h1>
                <p class="text-center mb-5">Get in touch with us for inquiries, orders, or support. We're here to help!</p>
                <div class="contact-grid">
                    <div class="contact-info">
                        <h2>Get in Touch</h2>
                        <p>We're always ready to assist you with your technology needs. Reach out via email, phone, or visit us in Lusaka.</p>
                        <div class="contact-methods">
                            <div class="contact-method">
                                <i class="fas fa-envelope"></i>
                                <div>
                                    <h4>Email</h4>
                                    <p><a href="mailto:wscomputersuppliers@gmail.com">wscomputersuppliers@gmail.com</a></p>
                                </div>
                            </div>
                            <div class="contact-method">
                                <i class="fas fa-phone"></i>
                                <div>
                                    <h4>Phone/WhatsApp</h4>
                                    <p><a href="tel:<?php echo htmlspecialchars($settings['contact_phone']); ?>"><?php echo htmlspecialchars($settings['contact_phone']); ?></a></p>
                                </div>
                            </div>
                            <div class="contact-method">
                                <i class="fas fa-map-marker-alt"></i>
                                <div>
                                    <h4>Location</h4>
                                    <p><?php echo htmlspecialchars($settings['location']); ?>, Lusaka, Zambia</p>
                                    <div class="map-container">
                                        <iframe src="https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d961.3880084791964!2d28.35086603628954!3d-15.454710802900246!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sen!2szm!4v1752655047881!5m2!1sen!2szm" width="400" height="200" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="contact-form-container">
                        <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                        <?php endif; ?>
                        <form class="contact-form" method="POST" action="">
                            <div class="form-group">
                                <label for="name">Name</label>
                                <input type="text" id="name" name="name" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="subject">Subject</label>
                                <input type="text" id="subject" name="subject" required>
                            </div>
                            <div class="form-group">
                                <label for="message">Message</label>
                                <textarea id="message" name="message" rows="5" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Send Message</button>
                        </form>
                    </div>
                </div>
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
                        <li><a class="text-decoration-none" href="shop.php">Lenovo Laptops</a></li>
                        <li><a class="text-decoration-none" href="shop.php">HP Laptops</a></li>
                        <li><a class="text-decoration-none" href="shop.php">DELL Laptops</a></li>
                        <li><a class="text-decoration-none" href="shop.php">APPLE macbooks Laptops</a></li>
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
    <script src="assets/js/jquery-1.11.0.min.js"></script>
    <script src="assets/js/jquery-migrate-1.2.1.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/templatemo.js"></script>
    <script src="assets/js/custom.js"></script>
    <!-- End Script -->
</body>
</html>