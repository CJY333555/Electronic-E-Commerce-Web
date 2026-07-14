<?php
$pageTitle = "Search Results";
$basePath  = "";
$pageCSS   = ['home.css', 'products.css', 'search.css'];
require_once __DIR__ . '/includes/header.php';

// Read + sanitise the search query
$query = trim($_GET['q'] ?? '');

$results  = [];
$total    = 0;

if ($query !== '') {
    $like = '%' . $query . '%';

    // Count total matches (name OR brand)
    $countStmt = mysqli_prepare($conn,
        "SELECT COUNT(*) FROM products
         WHERE name LIKE ? OR brand LIKE ?");
    mysqli_stmt_bind_param($countStmt, "ss", $like, $like);
    mysqli_stmt_execute($countStmt);
    $total = (int) mysqli_fetch_row(mysqli_stmt_get_result($countStmt))[0];

    // Fetch all matching products, ordered by relevance:
    // exact name match first, then name LIKE, then brand LIKE
    $dataStmt = mysqli_prepare($conn,
        "SELECT *,
            CASE
                WHEN name = ?            THEN 0
                WHEN name LIKE ?         THEN 1
                WHEN brand = ?           THEN 2
                ELSE                          3
            END AS relevance_order
         FROM products
         WHERE name LIKE ? OR brand LIKE ?
         ORDER BY relevance_order ASC, name ASC");
    mysqli_stmt_bind_param($dataStmt, "sssss",
        $query, $like, $query, $like, $like);
    mysqli_stmt_execute($dataStmt);
    $results = mysqli_fetch_all(mysqli_stmt_get_result($dataStmt), MYSQLI_ASSOC);
}
?>

<!-- ============================================================
     SEARCH BAR (shown again at top of results page)
     ============================================================ -->
<section class="home-search-section search-page-bar">
    <div class="home-search-inner">
        <p class="search-label">Search products</p>
        <form method="GET" action="search.php" class="home-search-form">
            <div class="home-search-bar">
                <input
                    type="text"
                    name="q"
                    class="home-search-input"
                    placeholder="Search by product name or brand..."
                    value="<?php echo clean($query); ?>"
                    autocomplete="off">
                <button type="submit" class="home-search-btn" title="Search">
                    <img src="assets/images/search-icon.png"
                         alt="Search"
                         class="search-icon-img"
                         onerror="this.style.display='none';
                                  this.nextElementSibling.style.display='inline';">
                    <span class="search-icon-fallback" style="display:none;">&#128269;</span>
                </button>
            </div>
        </form>
    </div>
</section>

<!-- ============================================================
     RESULTS HEADING
     ============================================================ -->
<div class="search-results-heading">
    <?php if ($query === ''): ?>
        <h1>Enter a keyword above to search</h1>
    <?php elseif ($total === 0): ?>
        <h1>No results for &ldquo;<?php echo clean($query); ?>&rdquo;</h1>
        <p>Try a different keyword, or <a href="products.php">browse all products</a>.</p>
    <?php else: ?>
        <h1>
            <?php echo $total; ?> result<?php echo $total !== 1 ? 's' : ''; ?>
            for &ldquo;<?php echo clean($query); ?>&rdquo;
        </h1>
        <p>Showing matches in product name and brand.</p>
        <br>
        <br>
    <?php endif; ?>
</div>

<!-- ============================================================
     RESULTS GRID
     ============================================================ -->
<?php if (!empty($results)): ?>
<div class="product-grid search-results-grid">
    <?php foreach ($results as $product):
        // Highlight matching keyword in name and brand
        $hlName  = preg_replace(
            '/(' . preg_quote(clean($query), '/') . ')/i',
            '<mark>$1</mark>',
            clean($product['name'])
        );
        $hlBrand = preg_replace(
            '/(' . preg_quote(clean($query), '/') . ')/i',
            '<mark>$1</mark>',
            clean($product['brand'])
        );
    ?>
        <div class="product-card">
            <img src="<?php echo clean($product['image_url']); ?>"
                 alt="<?php echo clean($product['name']); ?>"
                 onerror="this.src='assets/images/default-product.png'">
            <div class="product-card-body">
                <span class="product-category"><?php echo clean($product['category']); ?></span>
                <h3><?php echo $hlName; ?></h3>
                <p class="search-brand">Brand: <?php echo $hlBrand; ?></p>
                <p><?php echo clean(substr($product['description'], 0, 75)) . '...'; ?></p>
                <div class="product-meta">
                    <span class="product-price">RM <?php echo number_format($product['price'], 2); ?></span>
                    <a href="product_details.php?id=<?php echo $product['product_id']; ?>"
                       class="btn btn-small">View</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>