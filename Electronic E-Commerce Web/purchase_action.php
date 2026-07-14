<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit();
}

$userId  = $_SESSION['user_id'];
$address = trim($_POST['address'] ?? '');

if ($address === '') {
    echo json_encode(['success' => false, 'message' => 'Address is required.']);
    exit();
}

// Fetch cart items
$stmt = mysqli_prepare($conn, "
    SELECT p.product_id, p.name, p.price, c.quantity
    FROM cart_items c
    JOIN products p ON c.product_id = p.product_id
    WHERE c.user_id = ?
");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$items  = mysqli_fetch_all($result, MYSQLI_ASSOC);

if (empty($items)) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty.']);
    exit();
}

// Build items JSON and calculate total
$itemsData = [];
$total     = 0;
foreach ($items as $item) {
    $sub = $item['price'] * $item['quantity'];
    $total += $sub;
    $itemsData[] = [
        'name'     => $item['name'],
        'quantity' => (int)$item['quantity'],
        'price'    => (float)$item['price'],
        'subtotal' => $sub,
    ];
}
$itemsJson = json_encode($itemsData);

// Auto-delete oldest transaction if user already has 50
$countStmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM transactions WHERE user_id = ?");
mysqli_stmt_bind_param($countStmt, "i", $userId);
mysqli_stmt_execute($countStmt);
$txCount = mysqli_fetch_row(mysqli_stmt_get_result($countStmt))[0];

if ($txCount >= 50) {
    $delStmt = mysqli_prepare($conn, "
        DELETE FROM transactions WHERE user_id = ?
        ORDER BY purchased_at ASC LIMIT 1
    ");
    mysqli_stmt_bind_param($delStmt, "i", $userId);
    mysqli_stmt_execute($delStmt);
}

// Insert transaction
$insStmt = mysqli_prepare($conn, "
    INSERT INTO transactions (user_id, items_json, total_amount, address)
    VALUES (?, ?, ?, ?)
");
mysqli_stmt_bind_param($insStmt, "isds", $userId, $itemsJson, $total, $address);
mysqli_stmt_execute($insStmt);

// Clear cart
$clearStmt = mysqli_prepare($conn, "DELETE FROM cart_items WHERE user_id = ?");
mysqli_stmt_bind_param($clearStmt, "i", $userId);
mysqli_stmt_execute($clearStmt);

echo json_encode(['success' => true]);
exit();
