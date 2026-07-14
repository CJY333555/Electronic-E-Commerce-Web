<?php
$pageTitle = "My Cart";
$basePath  = "";
$pageCSS   = ['cart.css'];
require_once __DIR__ . '/includes/header.php';
requireLogin();

$userId = $_SESSION['user_id'];

$stmt = mysqli_prepare($conn, "
    SELECT p.product_id, p.name, p.category, p.brand, p.price, p.image_url, c.quantity, c.added_at
    FROM cart_items c
    JOIN products p ON c.product_id = p.product_id
    WHERE c.user_id = ?
    ORDER BY c.added_at DESC
");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$rows = [];
$total = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $row['subtotal'] = $row['price'] * $row['quantity'];
    $total += $row['subtotal'];
    $rows[] = $row;
}
?>

<!-- Cart page heading with cart icon -->
<div class="cart-page-heading">
    <h1>My Cart</h1>
    <img src="assets/images/cart-icon.png" alt="Cart" class="cart-heading-icon"
         onerror="this.style.display='none'">
</div>

<div class="table-wrap">
    <?php if (empty($rows)): ?>
        <div class="empty-state">
            <p>Your cart is empty.</p>
            <a href="products.php" class="btn" style="margin-top:14px;">Browse Products</a>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Brand</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td data-label="Product">
                            <a href="product_details.php?id=<?php echo $row['product_id']; ?>">
                                <?php echo clean($row['name']); ?>
                            </a>
                        </td>
                        <td data-label="Brand"><?php echo clean($row['brand']); ?></td>
                        <td data-label="Price">RM <?php echo number_format($row['price'], 2); ?></td>
                        <td data-label="Quantity">
                            <form method="POST" action="cart_action.php" style="display:flex;gap:6px;align-items:center;">
                                <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                                <input type="hidden" name="action"     value="update_qty">
                                <input type="number" name="quantity"   value="<?php echo (int)$row['quantity']; ?>"
                                       min="1" style="width:56px;padding:6px;">
                                <button type="submit" class="btn btn-outline btn-small">Update</button>
                            </form>
                        </td>
                        <td data-label="Subtotal">RM <?php echo number_format($row['subtotal'], 2); ?></td>
                        <td data-label="Action">
                            <form method="POST" action="cart_action.php" data-confirm="Remove this item from your cart?">
                                <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                                <input type="hidden" name="action"     value="remove">
                                <button type="submit" class="btn btn-danger btn-small">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="cart-total">Total: RM <?php echo number_format($total, 2); ?></div>

        <div class="cart-actions">
            <button class="btn" id="openPurchaseBtn">Purchase</button>
        </div>
    <?php endif; ?>
</div>

<!-- ============================================================
     PURCHASE MODAL
     ============================================================ -->
<div class="modal-overlay" id="purchaseModal">
    <div class="modal-box">
        <button class="modal-close modal-cancel">&times;</button>
        <h2>Confirm Purchase</h2>

        <!-- Items list -->
        <?php foreach ($rows as $r): ?>
            <div class="modal-item-row">
                <span><?php echo clean($r['name']); ?> &times; <?php echo (int)$r['quantity']; ?></span>
                <span>RM <?php echo number_format($r['subtotal'], 2); ?></span>
            </div>
        <?php endforeach; ?>

        <div class="modal-total-row">
            <span>Total</span>
            <span>RM <?php echo number_format($total, 2); ?></span>
        </div>

        <div class="form-group">
            <label for="purchaseAddress">Delivery Address</label>
            <textarea id="purchaseAddress" placeholder="Enter your full delivery address..."></textarea>
        </div>

        <div class="modal-btn-row">
            <button class="btn btn-secondary modal-cancel">Cancel</button>
            <button class="btn" id="confirmPurchaseBtn">Purchase</button>
        </div>
    </div>
</div>

<!-- ============================================================
     SUCCESS MODAL
     ============================================================ -->
<div class="modal-overlay" id="successModal">
    <div class="modal-box success-modal-box">
        <div class="success-check">&#10003;</div>
        <h2>Successfully Purchase</h2>
        <p>Your order has been placed. You can check your transaction history in your profile page.</p>
        <button class="btn" id="successOkBtn">OK</button>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
