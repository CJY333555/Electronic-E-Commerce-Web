<?php
$pageTitle = "Products";
$basePath  = "";
$pageCSS   = ['products.css'];
require_once __DIR__ . '/includes/header.php';

const PER_PAGE = 9;

// ---- Read filter params ----
$selectedCat   = isset($_GET['category']) ? $_GET['category'] : '';
$selectedBrand = isset($_GET['brand'])    ? $_GET['brand']    : '';
$sortPrice     = isset($_GET['sort'])     ? $_GET['sort']     : '';
$currentPage   = isset($_GET['page'])     ? max(1, (int)$_GET['page']) : 1;

// ---- Get distinct categories & brands for filters ----
$catResult   = mysqli_query($conn, "SELECT DISTINCT category FROM products ORDER BY category");
$brandResult = mysqli_query($conn, "SELECT DISTINCT brand FROM products ORDER BY brand");

// ---- Build WHERE clause ----
$where  = [];
$params = [];
$types  = "";

if ($selectedCat !== '') {
    $where[] = "category = ?";
    $params[] = $selectedCat;
    $types   .= "s";
}
if ($selectedBrand !== '') {
    $where[] = "brand = ?";
    $params[] = $selectedBrand;
    $types   .= "s";
}

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

// ---- ORDER BY ----
if ($sortPrice === 'asc')       $orderSQL = "ORDER BY price ASC";
elseif ($sortPrice === 'desc')  $orderSQL = "ORDER BY price DESC";
else                            $orderSQL = "ORDER BY created_at DESC";

// ---- Total count for pagination ----
$countStmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM products $whereSQL");
if ($types) { mysqli_stmt_bind_param($countStmt, $types, ...$params); }
mysqli_stmt_execute($countStmt);
$totalCount = mysqli_fetch_row(mysqli_stmt_get_result($countStmt))[0];
$totalPages = max(1, ceil($totalCount / PER_PAGE));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * PER_PAGE;

// ---- Fetch products ----
$dataStmt = mysqli_prepare($conn, "SELECT * FROM products $whereSQL $orderSQL LIMIT ? OFFSET ?");
$dataTypes = $types . "ii";
$dataParams = array_merge($params, [PER_PAGE, $offset]);
mysqli_stmt_bind_param($dataStmt, $dataTypes, ...$dataParams);
mysqli_stmt_execute($dataStmt);
$result = mysqli_stmt_get_result($dataStmt);

// ---- Build base query string for pagination links ----
$qBase  = http_build_query(array_filter([
    'category' => $selectedCat,
    'brand'    => $selectedBrand,
    'sort'     => $sortPrice,
]));
$qBase  = $qBase ? $qBase . '&' : '';
?>

<section class="page-heading">
    <h1><?php echo $selectedCat ? clean($selectedCat) : 'All Products'; ?></h1>
    <p><?php echo $totalCount; ?> product<?php echo $totalCount != 1 ? 's' : ''; ?> found</p>
</section>

<!-- Category filter pills -->
<div class="filter-bar">
    <a href="products.php" class="<?php echo $selectedCat === '' ? 'active' : ''; ?>">All</a>
    <?php while ($cat = mysqli_fetch_assoc($catResult)): ?>
        <a href="products.php?category=<?php echo urlencode($cat['category']); ?><?php echo $selectedBrand ? '&brand=' . urlencode($selectedBrand) : ''; ?><?php echo $sortPrice ? '&sort=' . $sortPrice : ''; ?>"
           class="<?php echo $selectedCat === $cat['category'] ? 'active' : ''; ?>">
            <?php echo clean($cat['category']); ?>
        </a>
    <?php endwhile; ?>
</div>

<!-- Brand + sort filters -->
<form method="GET" action="products.php" style="max-width:1100px; margin:0 auto 10px;">
    <input type="hidden" name="category" value="<?php echo clean($selectedCat); ?>">
    <div class="filter-row">
        <label>Brand:</label>
        <select name="brand" onchange="this.form.submit()">
            <option value="">All Brands</option>
            <?php while ($b = mysqli_fetch_assoc($brandResult)): ?>
                <option value="<?php echo clean($b['brand']); ?>" <?php echo $selectedBrand === $b['brand'] ? 'selected' : ''; ?>>
                    <?php echo clean($b['brand']); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Price:</label>
        <select name="sort" onchange="this.form.submit()">
            <option value=""       <?php echo $sortPrice === ''     ? 'selected' : ''; ?>>Default</option>
            <option value="asc"    <?php echo $sortPrice === 'asc'  ? 'selected' : ''; ?>>Low → High</option>
            <option value="desc"   <?php echo $sortPrice === 'desc' ? 'selected' : ''; ?>>High → Low</option>
        </select>
    </div>
</form>

<!-- Product grid -->
<div class="product-grid">
    <?php if (mysqli_num_rows($result) === 0): ?>
        <p class="empty-state" style="grid-column:1/-1;">No products found.</p>
    <?php endif; ?>
    <?php while ($product = mysqli_fetch_assoc($result)): ?>
        <div class="product-card">
            <img src="<?php echo clean($product['image_url']); ?>"
                 alt="<?php echo clean($product['name']); ?>"
                 onerror="this.src='assets/images/default-product.png'">
            <div class="product-card-body">
                <span class="product-category"><?php echo clean($product['category']); ?></span>
                <h3><?php echo clean($product['name']); ?></h3>
                <p><?php echo clean(substr($product['description'], 0, 80)) . '...'; ?></p>
                <div class="product-meta">
                    <span class="product-price">RM <?php echo number_format($product['price'], 2); ?></span>
                    <a href="product_details.php?id=<?php echo $product['product_id']; ?>" class="btn btn-small">View</a>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="pagination">
    <a href="products.php?<?php echo $qBase; ?>page=<?php echo max(1, $currentPage - 1); ?>"
       class="page-btn <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>"
       <?php echo $currentPage <= 1 ? 'aria-disabled="true"' : ''; ?>>&#8249;</a>

    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <a href="products.php?<?php echo $qBase; ?>page=<?php echo $p; ?>"
           class="page-btn <?php echo $p === $currentPage ? 'active' : ''; ?>"><?php echo $p; ?></a>
    <?php endfor; ?>

    <a href="products.php?<?php echo $qBase; ?>page=<?php echo min($totalPages, $currentPage + 1); ?>"
       class="page-btn <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>"
       <?php echo $currentPage >= $totalPages ? 'aria-disabled="true"' : ''; ?>>&#8250;</a>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
