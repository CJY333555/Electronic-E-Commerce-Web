<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

requireLogin();

$reviewId = isset($_POST['review_id']) ? (int) $_POST['review_id'] : 0;
$productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;

if ($reviewId > 0) {
    // A user may delete their own review; an admin may delete any review
    if (isAdmin()) {
        $stmt = mysqli_prepare($conn, "DELETE FROM reviews WHERE review_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $reviewId);
    } else {
        $stmt = mysqli_prepare($conn, "DELETE FROM reviews WHERE review_id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $reviewId, $_SESSION['user_id']);
    }
    mysqli_stmt_execute($stmt);

    $_SESSION['flash_message'] = "Review deleted.";
    $_SESSION['flash_type'] = "success";
}

header("Location: product_details.php?id=" . $productId);
exit();
