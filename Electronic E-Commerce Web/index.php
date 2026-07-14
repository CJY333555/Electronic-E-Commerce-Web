<?php
$pageTitle = "Home";
$basePath  = "";
$pageCSS   = ['home.css', 'products.css'];
require_once __DIR__ . '/includes/header.php';

// Carousel: latest 5 products
$carouselResult = mysqli_query($conn, "SELECT * FROM products ORDER BY created_at DESC LIMIT 5");
$carouselItems  = mysqli_fetch_all($carouselResult, MYSQLI_ASSOC);

// Distinct categories for featured sections
$catResult  = mysqli_query($conn, "SELECT DISTINCT category FROM products ORDER BY category");
$categories = mysqli_fetch_all($catResult, MYSQLI_ASSOC);
?>

<!-- ============================================================
     CAROUSEL
     ============================================================ -->
<section class="hero-carousel">
    <div class="carousel-track">
        <?php foreach ($carouselItems as $i => $item): ?>
        <div class="carousel-slide <?php echo $i === 0 ? 'active' : ''; ?>">
            <div class="slide-content">
                <span class="slide-category"><?php echo clean($item['category']); ?></span>
                <h2><?php echo clean($item['name']); ?></h2>
                <p><?php echo clean(substr($item['description'], 0, 110)) . '...'; ?></p>
                <p class="slide-price">RM <?php echo number_format($item['price'], 2); ?></p>
                <a href="product_details.php?id=<?php echo $item['product_id']; ?>" class="btn">Learn More</a>
            </div>
            <div class="slide-image">
                <img src="<?php echo clean($item['image_url']); ?>"
                     alt="<?php echo clean($item['name']); ?>"
                     onerror="this.src='assets/images/default-product.png'">
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <button class="carousel-btn prev" id="carouselPrev">&#8249;</button>
    <button class="carousel-btn next" id="carouselNext">&#8250;</button>
    <div class="carousel-dots">
        <?php foreach ($carouselItems as $i => $item): ?>
            <button class="carousel-dot <?php echo $i === 0 ? 'active' : ''; ?>"
                    aria-label="Slide <?php echo $i + 1; ?>"></button>
        <?php endforeach; ?>
    </div>
</section>

<!-- ============================================================
     FEATURED PRODUCTS BY CATEGORY
     ============================================================ -->
<?php foreach ($categories as $cat):
    $catName = $cat['category'];
    $fpStmt  = mysqli_prepare($conn,
        "SELECT * FROM products WHERE category = ? ORDER BY created_at DESC LIMIT 3");
    mysqli_stmt_bind_param($fpStmt, "s", $catName);
    mysqli_stmt_execute($fpStmt);
    $fpResult = mysqli_stmt_get_result($fpStmt);
    if (mysqli_num_rows($fpResult) === 0) continue;
?>
    <section class="featured-section">
        <div class="category-header">
            <h2><?php echo clean($catName); ?></h2>
            <a href="products.php?category=<?php echo urlencode($catName); ?>"
               class="see-all-btn">See All &rsaquo;</a>
        </div>
        <div class="product-grid">
            <?php while ($p = mysqli_fetch_assoc($fpResult)): ?>
                <div class="product-card">
                    <img src="<?php echo clean($p['image_url']); ?>"
                         alt="<?php echo clean($p['name']); ?>"
                         onerror="this.src='assets/images/default-product.png'">
                    <div class="product-card-body">
                        <span class="product-category"><?php echo clean($p['category']); ?></span>
                        <h3><?php echo clean($p['name']); ?></h3>
                        <p><?php echo clean(substr($p['description'], 0, 80)) . '...'; ?></p>
                        <div class="product-meta">
                            <span class="product-price">RM <?php echo number_format($p['price'], 2); ?></span>
                            <a href="product_details.php?id=<?php echo $p['product_id']; ?>"
                               class="btn btn-small">View</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </section>
<?php endforeach; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>