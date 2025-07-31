<?php
session_start();
require 'db_connect.php';
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$stmt = $pdo->prepare("SELECT o.*, p.name AS product_name FROM orders o JOIN products p ON o.product_id = p.id WHERE o.id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt = $pdo->query("SELECT contact_phone, whatsapp_number, location FROM settings LIMIT 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['contact_phone' => '+260-0979-303-059', 'whatsapp_number' => '', 'location' => 'Woodlands off Mosi Otunya Road'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Order Confirmation - WS Computer Suppliers</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
</head>
<body>
    <div class="container py-5">
        <h1>Order Confirmation</h1>
        <?php if ($order): ?>
            <p class="text-success">Order placed successfully! Your order ID is #<?php echo htmlspecialchars($order['id']); ?>.</p>
            <p><strong>Product:</strong> <?php echo htmlspecialchars($order['product_name']); ?></p>
            <p><strong>Quantity:</strong> <?php echo htmlspecialchars($order['quantity']); ?></p>
            <p><strong>Total:</strong> K<?php echo number_format($order['total_price'], 2); ?></p>
            <p>Please contact us to complete your purchase:</p>
            <p>dont forget to take a screenshot of these order confirmations:</p>
            <ul>
                <li><strong>Call:</strong> <a href="tel:<?php echo htmlspecialchars($settings['contact_phone']); ?>"><?php echo htmlspecialchars($settings['contact_phone']); ?></a></li>
                <?php if ($settings['whatsapp_number']): ?>
                    <li><strong>WhatsApp:</strong> <a href="https://wa.me/<?php echo str_replace('+', '', htmlspecialchars($settings['whatsapp_number'])); ?>" target="_blank"><?php echo htmlspecialchars($settings['whatsapp_number']); ?></a></li>
                <?php endif; ?>
                <li><strong>Visit:</strong> <?php echo htmlspecialchars($settings['location']); ?></li>
            </ul>
        <?php else: ?>
            <p class="text-danger">Invalid order ID.</p>
        <?php endif; ?>
        <a href="shop.php" class="btn btn-primary">Continue Shopping</a>
    </div>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>