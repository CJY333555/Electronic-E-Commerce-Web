<?php
$pageTitle = "Product Details";
$basePath  = "";
$pageCSS   = ['products.css', 'details.css', 'forms.css'];   // category badge + details layout + review form
require_once __DIR__ . '/includes/header.php';

$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE product_id = ?");
mysqli_stmt_bind_param($stmt, "i", $productId);
mysqli_stmt_execute($stmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$product) {
    echo "<div class='empty-state'><p>Product not found.</p><a href='products.php' class='btn' style='margin-top:14px;'>Back to Products</a></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit();
}

$reviewErrors = [];

// ---------------- Handle new review submission (Create) ----------------
if (isLoggedIn() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $rating = isset($_POST['rating']) ? (int) $_POST['rating'] : 0;
    $comment = trim($_POST['comment'] ?? '');

    if ($rating < 1 || $rating > 5) $reviewErrors[] = "Please select a star rating.";
    if ($comment === '') $reviewErrors[] = "Please write a comment.";

    if (empty($reviewErrors)) {
        $insertStmt = mysqli_prepare($conn, "INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($insertStmt, "iiis", $productId, $_SESSION['user_id'], $rating, $comment);
        mysqli_stmt_execute($insertStmt);

        $_SESSION['flash_message'] = "Your review has been posted.";
        $_SESSION['flash_type'] = "success";
        header("Location: product_details.php?id=" . $productId);
        exit();
    }
}

// Check cart status
$inCart = false;
if (isLoggedIn()) {
    $checkStmt = mysqli_prepare($conn, "SELECT * FROM cart_items WHERE user_id = ? AND product_id = ?");
    mysqli_stmt_bind_param($checkStmt, "ii", $_SESSION['user_id'], $productId);
    mysqli_stmt_execute($checkStmt);
    $inCart = mysqli_num_rows(mysqli_stmt_get_result($checkStmt)) > 0;
}

// ---------------- Fetch reviews (Read) ----------------
$reviewStmt = mysqli_prepare($conn, "
    SELECT r.*, u.username FROM reviews r
    JOIN users u ON r.user_id = u.user_id
    WHERE r.product_id = ?
    ORDER BY r.created_at DESC
");
mysqli_stmt_bind_param($reviewStmt, "i", $productId);
mysqli_stmt_execute($reviewStmt);
$reviewResult = mysqli_stmt_get_result($reviewStmt);
$reviewCount = mysqli_num_rows($reviewResult);

// Average rating
$avgStmt = mysqli_prepare($conn, "SELECT AVG(rating) as avg_rating FROM reviews WHERE product_id = ?");
mysqli_stmt_bind_param($avgStmt, "i", $productId);
mysqli_stmt_execute($avgStmt);
$avgRow = mysqli_fetch_assoc(mysqli_stmt_get_result($avgStmt));
$avgRating = $avgRow['avg_rating'] ? round($avgRow['avg_rating'], 1) : null;
?>

<div class="details-wrap">
    <img src="<?php echo clean($product['image_url']); ?>" alt="<?php echo clean($product['name']); ?>"
         onerror="this.src='assets/images/default-product.png'">
    <div class="details-body">
        <span class="product-category"><?php echo clean($product['category']); ?></span>
        <h1><?php echo clean($product['name']); ?></h1>

        <?php if ($avgRating): ?>
            <p class="review-rating" style="margin:6px 0;"><?php echo str_repeat('★', round($avgRating)) . str_repeat('☆', 5 - round($avgRating)); ?>
                <span style="color:#6b7280; font-size:13px;">(<?php echo $avgRating; ?> / 5 from <?php echo $reviewCount; ?> review<?php echo $reviewCount != 1 ? 's' : ''; ?>)</span>
            </p>
        <?php endif; ?>

        <div class="details-meta-row">
            <span><strong>Brand:</strong> <span style="color:black;"><?php echo clean($product['brand']); ?></span></span>
            <span><strong>Stock:</strong> <span style="color:red;"><?php echo (int) $product['stock']; ?> units available</span></span>
            <span><strong>Price:</strong> <span style="color:#2d5fff;">RM <?php echo number_format($product['price'], 2); ?></span></span>
        </div>

        <div class="desc">
            <h3>Description</h3>
            <p><?php echo nl2br(clean($product['description'])); ?></p>
        </div>

        <?php if (!isLoggedIn()): ?>
            <a href="login.php" class="btn">Login to Add to Cart</a>
        <?php elseif ($inCart): ?>
            <p style="color:#1d8348; font-weight:600; margin-bottom:12px;">✓ This item is in your cart.</p>
            <a href="cart.php" class="btn btn-secondary">View Cart</a>
        <?php else: ?>
            <form method="POST" action="cart_action.php">
                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                <input type="hidden" name="action" value="add">
                <button type="submit" class="btn">Add to Cart</button>
            </form>
        <?php endif; ?>

        <p style="margin-top:20px;"><a href="products.php">&larr; Back to all products</a></p>

        <!-- ---------------- Reviews Section ---------------- -->
        <div class="reviews-section">
            <h3>Customer Reviews (<?php echo $reviewCount; ?>)</h3>

            <?php if (isLoggedIn()): ?>
                <?php if (!empty($reviewErrors)): ?>
                    <div class="flash-message error" style="margin-bottom:16px;">
                        <?php foreach ($reviewErrors as $err) echo clean($err) . "<br>"; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="product_details.php?id=<?php echo $productId; ?>" style="margin-bottom:24px;">
                    <div class="form-group">
                        <label>Your Rating</label>
                        <div class="star-select">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
                                <label for="star<?php echo $i; ?>">★</label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="comment">Your Review</label>
                        <textarea id="comment" name="comment" rows="3" required placeholder="Share your experience with this product..."></textarea>
                    </div>
                    <button type="submit" name="submit_review" class="btn btn-small">Post Review</button>
                </form>
            <?php else: ?>
                <p style="margin-bottom:20px;"><a href="login.php">Login</a> to leave a review.</p>
            <?php endif; ?>

            <?php if ($reviewCount === 0): ?>
                <p class="empty-state" style="padding:20px;">No reviews yet. Be the first to review this product!</p>
            <?php else: ?>
                <?php while ($review = mysqli_fetch_assoc($reviewResult)): ?>
                    <div class="review-card">
                        <div class="review-card-top">
                            <div>
                                <span class="review-author"><?php echo clean($review['username']); ?></span>
                                <span class="review-rating"><?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?></span>
                            </div>
                            <span class="review-date"><?php echo date("d M Y", strtotime($review['created_at'])); ?></span>
                        </div>
                        <p class="review-comment"><?php echo nl2br(clean($review['comment'])); ?></p>

                        <?php if (isLoggedIn() && ($review['user_id'] == $_SESSION['user_id'] || isAdmin())): ?>
                            <form method="POST" action="review_action.php" class="review-delete" data-confirm="Delete this review?">
                                <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
                                <button type="submit" class="btn btn-danger btn-small">Delete</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
