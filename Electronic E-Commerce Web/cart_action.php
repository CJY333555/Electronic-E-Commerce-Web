<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

requireLogin();

$productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';
$userId = $_SESSION['user_id'];

if ($action === 'add' && $productId > 0) {
    // Create operation - add to cart (avoid duplicates via prepared statement + unique key)
    $stmt = mysqli_prepare($conn, "INSERT IGNORE INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, 1)");
    mysqli_stmt_bind_param($stmt, "ii", $userId, $productId);
    mysqli_stmt_execute($stmt);

    $_SESSION['flash_message'] = "Item added to your cart!";
    $_SESSION['flash_type'] = "success";
    header("Location: product_details.php?id=" . $productId);
    exit();
}

if ($action === 'remove' && $productId > 0) {
    // Delete operation - remove from cart
    $stmt = mysqli_prepare($conn, "DELETE FROM cart_items WHERE user_id = ? AND product_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $userId, $productId);
    mysqli_stmt_execute($stmt);

    $_SESSION['flash_message'] = "Item removed from your cart.";
    $_SESSION['flash_type'] = "success";
    header("Location: cart.php");
    exit();
}

if ($action === 'update_qty' && $productId > 0) {
    // Update operation - change quantity
    $qty = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 1;
    if ($qty < 1) $qty = 1;

    $stmt = mysqli_prepare($conn, "UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?");
    mysqli_stmt_bind_param($stmt, "iii", $qty, $userId, $productId);
    mysqli_stmt_execute($stmt);

    $_SESSION['flash_message'] = "Cart updated.";
    $_SESSION['flash_type'] = "success";
    header("Location: cart.php");
    exit();
}

header("Location: products.php");
exit();
