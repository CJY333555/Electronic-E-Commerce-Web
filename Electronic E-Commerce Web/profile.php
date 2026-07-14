<?php
$pageTitle = "Profile";
$basePath  = "";
$pageCSS   = ['profile.css', 'forms.css', 'cart.css'];
require_once __DIR__ . '/includes/header.php';
requireLogin();

$userId  = $_SESSION['user_id'];
$errors  = [];
$success = false;

// ---- Fetch current user ----
$userStmt = mysqli_prepare($conn, "SELECT * FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($userStmt, "i", $userId);
mysqli_stmt_execute($userStmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($userStmt));

// ---- Handle profile update (User: email + password | Admin: email only) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email address is required.";
    }

    if (empty($errors)) {
        // Only regular users can change password
        if (!isAdmin()) {
            $newPassword = $_POST['new_password'] ?? '';
            if ($newPassword !== '') {
                if (strlen($newPassword) < 6) {
                    $errors[] = "New password must be at least 6 characters.";
                } else {
                    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
                    $upd = mysqli_prepare($conn, "UPDATE users SET email=?, password=? WHERE user_id=?");
                    mysqli_stmt_bind_param($upd, "ssi", $email, $hashed, $userId);
                    mysqli_stmt_execute($upd);
                    $success = true;
                }
            }
        }

        if (empty($errors) && !$success) {
            $upd = mysqli_prepare($conn, "UPDATE users SET email=? WHERE user_id=?");
            mysqli_stmt_bind_param($upd, "si", $email, $userId);
            mysqli_stmt_execute($upd);
            $success = true;
        }

        if ($success) {
            $user['email'] = $email;
        }
    }
}

// ---- Profile avatar ----
$avatarPath = getProfilePic($user['profile_pic'] ?? null, '');

// ============================================================
// ADMIN-ONLY: state variables for tabs, pagination, search
// ============================================================
$adminErrors = [];
$editProduct = null;
$activeTab   = 'products';

// Manage Products pagination + search
$prodPerPage = 10;
$prodPage    = 1;
$prodSearch  = '';

// Report pagination
$msgPerPage  = 10;
$msgPage     = 1;

if (isAdmin()) {
    $activeTab  = isset($_GET['tab'])      ? $_GET['tab']             : 'products';
    $prodPage   = isset($_GET['prodpage']) ? max(1,(int)$_GET['prodpage']) : 1;
    $prodSearch = isset($_GET['search'])   ? trim($_GET['search'])    : '';
    $msgPage    = isset($_GET['msgpage'])  ? max(1,(int)$_GET['msgpage'])  : 1;

    // ---- Delete product ----
    if (isset($_GET['delete_product'])) {
        $dId = (int) $_GET['delete_product'];
        $dStmt = mysqli_prepare($conn, "DELETE FROM products WHERE product_id = ?");
        mysqli_stmt_bind_param($dStmt, "i", $dId);
        mysqli_stmt_execute($dStmt);
        $_SESSION['flash_message'] = "Product deleted.";
        $_SESSION['flash_type']    = "success";
        header("Location: profile.php?tab=products");
        exit();
    }

    // ---- Load product for editing ----
    if (isset($_GET['edit_product'])) {
        $eId = (int) $_GET['edit_product'];
        $eStmt = mysqli_prepare($conn, "SELECT * FROM products WHERE product_id = ?");
        mysqli_stmt_bind_param($eStmt, "i", $eId);
        mysqli_stmt_execute($eStmt);
        $editProduct = mysqli_fetch_assoc(mysqli_stmt_get_result($eStmt));
        $activeTab   = 'products';
    }

    // ---- Save (create or update) product ----
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
        $pName  = trim($_POST['name']        ?? '');
        $pCat   = trim($_POST['category']    ?? '');
        $pDesc  = trim($_POST['description'] ?? '');
        $pBrand = trim($_POST['brand']       ?? '');
        $pStock = (int)   ($_POST['stock']   ?? 0);
        $pPrice = (float) ($_POST['price']   ?? 0);
        $pImage = trim($_POST['image_url']   ?? '') ?: 'assets/images/default-product.png';
        $pId    = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;

        if ($pName  === '') $adminErrors[] = "Product name is required.";
        if ($pCat   === '') $adminErrors[] = "Category is required.";
        if ($pBrand === '') $adminErrors[] = "Brand is required.";
        if ($pPrice <= 0)   $adminErrors[] = "Price must be greater than 0.";

        $ext = strtolower(pathinfo($pImage, PATHINFO_EXTENSION));
        if ($ext !== 'png' && $pImage !== 'assets/images/default-product.png') {
            $adminErrors[] = "Image URL must end in .png";
        }

        if (empty($adminErrors)) {
            if ($pId > 0) {
                $pStmt = mysqli_prepare($conn,
                    "UPDATE products SET name=?,category=?,description=?,brand=?,stock=?,price=?,image_url=? WHERE product_id=?");
                mysqli_stmt_bind_param($pStmt, "ssssidsi",
                    $pName,$pCat,$pDesc,$pBrand,$pStock,$pPrice,$pImage,$pId);
                mysqli_stmt_execute($pStmt);
                $_SESSION['flash_message'] = "Product updated successfully.";
            } else {
                $pStmt = mysqli_prepare($conn,
                    "INSERT INTO products (name,category,description,brand,stock,price,image_url) VALUES (?,?,?,?,?,?,?)");
                mysqli_stmt_bind_param($pStmt, "ssssids",
                    $pName,$pCat,$pDesc,$pBrand,$pStock,$pPrice,$pImage);
                mysqli_stmt_execute($pStmt);
                $_SESSION['flash_message'] = "Product added successfully.";
            }
            $_SESSION['flash_type'] = "success";
            header("Location: profile.php?tab=products");
            exit();
        }
        $activeTab = 'products';
    }

    // ---- Mark message as read ----
    if (isset($_GET['mark_read'])) {
        $mId = (int) $_GET['mark_read'];
        $mStmt = mysqli_prepare($conn, "UPDATE contact_messages SET is_read=1 WHERE message_id=?");
        mysqli_stmt_bind_param($mStmt, "i", $mId);
        mysqli_stmt_execute($mStmt);
        header("Location: profile.php?tab=report&msgpage=" . $msgPage);
        exit();
    }

    // ---- Products: total count + pagination query ----
    if ($prodSearch !== '') {
        $searchLike   = '%' . $prodSearch . '%';
        $pcStmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM products WHERE name LIKE ?");
        mysqli_stmt_bind_param($pcStmt, "s", $searchLike);
        mysqli_stmt_execute($pcStmt);
        $prodTotal = (int) mysqli_fetch_row(mysqli_stmt_get_result($pcStmt))[0];

        $prodTotalPages = max(1, ceil($prodTotal / $prodPerPage));
        $prodPage       = min($prodPage, $prodTotalPages);
        $prodOffset     = ($prodPage - 1) * $prodPerPage;

        $prodStmt = mysqli_prepare($conn,
            "SELECT * FROM products WHERE name LIKE ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
        mysqli_stmt_bind_param($prodStmt, "sii", $searchLike, $prodPerPage, $prodOffset);
        mysqli_stmt_execute($prodStmt);
        $prodResult = mysqli_stmt_get_result($prodStmt);
    } else {
        $pcStmt = mysqli_query($conn, "SELECT COUNT(*) FROM products");
        $prodTotal = (int) mysqli_fetch_row($pcStmt)[0];

        $prodTotalPages = max(1, ceil($prodTotal / $prodPerPage));
        $prodPage       = min($prodPage, $prodTotalPages);
        $prodOffset     = ($prodPage - 1) * $prodPerPage;

        $prodStmt = mysqli_prepare($conn,
            "SELECT * FROM products ORDER BY created_at DESC LIMIT ? OFFSET ?");
        mysqli_stmt_bind_param($prodStmt, "ii", $prodPerPage, $prodOffset);
        mysqli_stmt_execute($prodStmt);
        $prodResult = mysqli_stmt_get_result($prodStmt);
    }

    // ---- Messages: total count + pagination query ----
    $mcStmt  = mysqli_query($conn, "SELECT COUNT(*) FROM contact_messages");
    $msgTotal = (int) mysqli_fetch_row($mcStmt)[0];
    $msgTotalPages = max(1, ceil($msgTotal / $msgPerPage));
    $msgPage       = min($msgPage, $msgTotalPages);
    $msgOffset     = ($msgPage - 1) * $msgPerPage;

    $msgStmt = mysqli_prepare($conn,
        "SELECT * FROM contact_messages ORDER BY is_read ASC, sent_at DESC LIMIT ? OFFSET ?");
    mysqli_stmt_bind_param($msgStmt, "ii", $msgPerPage, $msgOffset);
    mysqli_stmt_execute($msgStmt);
    $msgResult = mysqli_stmt_get_result($msgStmt);

    // ---- Unread count for dot indicator ----
    $unreadRow   = mysqli_query($conn, "SELECT COUNT(*) FROM contact_messages WHERE is_read = 0");
    $unreadCount = (int) mysqli_fetch_row($unreadRow)[0];
}
?>

<div class="profile-page">

<?php if (isAdmin()): ?>
<!-- ============================================================
     ADMIN PROFILE LAYOUT
     Left = tabs (Manage Products / Report)
     Right = admin profile card (email only, no password change)
     ============================================================ -->
<div class="admin-profile-layout">

    <!-- ---- LEFT PANEL ---- -->
    <div class="admin-left-panel">
        <div class="admin-tab-bar">
            <button class="admin-tab-btn <?php echo $activeTab === 'products' ? 'active' : ''; ?>"
                    data-tab="tab-products">Manage Products</button>
            <button class="admin-tab-btn <?php echo $activeTab === 'report' ? 'active' : ''; ?>"
                    data-tab="tab-report">
                Report
                <?php if ($unreadCount > 0): ?>
                    <span class="unread-dot"></span>
                <?php endif; ?>
            </button>
        </div>

        <!-- ==================================================
             TAB: Manage Products
             ================================================== -->
        <div class="admin-tab-content admin-tab-pane" id="tab-products"
             style="display:<?php echo $activeTab === 'products' ? 'block' : 'none'; ?>">

            <?php if (!empty($adminErrors)): ?>
                <div class="flash-message error" style="margin-bottom:14px;">
                    <?php foreach ($adminErrors as $e) echo clean($e) . '<br>'; ?>
                </div>
            <?php endif; ?>

            <!-- Add / Edit form -->
            <h3 style="margin-bottom:16px;">
                <?php echo $editProduct ? 'Edit Product' : 'Add New Product'; ?>
            </h3>
            <form method="POST" action="profile.php?tab=products" data-validate>
                <?php if ($editProduct): ?>
                    <input type="hidden" name="product_id" value="<?php echo $editProduct['product_id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="name" required
                           value="<?php echo $editProduct ? clean($editProduct['name']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="category" required
                           placeholder="e.g. Laptops, Smartphones, Audio"
                           value="<?php echo $editProduct ? clean($editProduct['category']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3"><?php echo $editProduct ? clean($editProduct['description']) : ''; ?></textarea>
                </div>
                <div class="form-group">
                    <label>Brand</label>
                    <input type="text" name="brand" required
                           value="<?php echo $editProduct ? clean($editProduct['brand']) : ''; ?>">
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div class="form-group">
                        <label>Stock</label>
                        <input type="number" name="stock" min="0"
                               value="<?php echo $editProduct ? (int)$editProduct['stock'] : '0'; ?>">
                    </div>
                    <div class="form-group">
                        <label>Price (RM)</label>
                        <input type="number" name="price" min="0" step="0.01" required
                               value="<?php echo $editProduct ? $editProduct['price'] : ''; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Image URL <span style="font-weight:400;color:#9ca3af;">(must be .png)</span></label>
                    <input type="text" name="image_url" placeholder="assets/images/product.png"
                           value="<?php echo $editProduct ? clean($editProduct['image_url']) : ''; ?>">
                </div>

                <div style="display:flex; gap:8px;">
                    <button type="submit" name="save_product" class="btn btn-small">
                        <?php echo $editProduct ? 'Update Product' : 'Add Product'; ?>
                    </button>
                    <?php if ($editProduct): ?>
                        <a href="profile.php?tab=products" class="btn btn-secondary btn-small">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>

            <hr style="margin:22px 0; border:none; border-top:1px solid var(--border);">

            <!-- Search bar -->
            <form method="GET" action="profile.php" class="prod-search-form">
                <input type="hidden" name="tab" value="products">
                <div class="prod-search-wrap">
                    <input type="text" name="search" class="prod-search-input"
                           placeholder="Search product name..."
                           value="<?php echo clean($prodSearch); ?>">
                    <button type="submit" class="btn btn-small">Search</button>
                    <?php if ($prodSearch !== ''): ?>
                        <a href="profile.php?tab=products" class="btn btn-secondary btn-small">Clear</a>
                    <?php endif; ?>
                </div>
            </form>

            <!-- Products table -->
            <div style="overflow-x:auto; margin-top:14px;">
                <?php if (mysqli_num_rows($prodResult) === 0): ?>
                    <p style="color:#6b7280; padding:10px 0;">
                        No products found<?php echo $prodSearch ? ' for "' . clean($prodSearch) . '"' : ''; ?>.
                    </p>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Brand</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $rowNum = $prodOffset + 1;
                        while ($p = mysqli_fetch_assoc($prodResult)):
                        ?>
                        <tr>
                            <td data-label="#"><?php echo $rowNum++; ?></td>
                            <td data-label="Name"><?php echo clean($p['name']); ?></td>
                            <td data-label="Category"><?php echo clean($p['category']); ?></td>
                            <td data-label="Brand"><?php echo clean($p['brand']); ?></td>
                            <td data-label="Price">RM <?php echo number_format($p['price'], 2); ?></td>
                            <td data-label="Stock"><?php echo (int)$p['stock']; ?></td>
                            <td data-label="Actions">
                                <a href="profile.php?tab=products&edit_product=<?php echo $p['product_id']; ?><?php echo $prodSearch ? '&search=' . urlencode($prodSearch) : ''; ?>&prodpage=<?php echo $prodPage; ?>"
                                   class="btn btn-small btn-outline">Edit</a>
                                <a href="profile.php?tab=products&delete_product=<?php echo $p['product_id']; ?>"
                                   class="btn btn-small btn-danger"
                                   onclick="return confirm('Delete this product? This cannot be undone.');">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <!-- Products pagination -->
                <?php if ($prodTotalPages > 1): ?>
                <div class="admin-pagination">
                    <?php
                    $prodBase = 'profile.php?tab=products' . ($prodSearch ? '&search=' . urlencode($prodSearch) : '');
                    ?>
                    <?php if ($prodPage > 1): ?>
                        <a href="<?php echo $prodBase; ?>&prodpage=<?php echo $prodPage - 1; ?>" class="page-btn">&#8249;</a>
                    <?php else: ?>
                        <span class="page-btn" style="opacity:.35; cursor:not-allowed;">&#8249;</span>
                    <?php endif; ?>

                    <?php for ($p = 1; $p <= $prodTotalPages; $p++): ?>
                        <a href="<?php echo $prodBase; ?>&prodpage=<?php echo $p; ?>"
                           class="page-btn <?php echo $p === $prodPage ? 'active' : ''; ?>">
                            <?php echo $p; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($prodPage < $prodTotalPages): ?>
                        <a href="<?php echo $prodBase; ?>&prodpage=<?php echo $prodPage + 1; ?>" class="page-btn">&#8250;</a>
                    <?php else: ?>
                        <span class="page-btn" style="opacity:.35; cursor:not-allowed;">&#8250;</span>
                    <?php endif; ?>

                    <span class="page-info">
                        Page <?php echo $prodPage; ?> of <?php echo $prodTotalPages; ?>
                        (<?php echo $prodTotal; ?> product<?php echo $prodTotal !== 1 ? 's' : ''; ?>)
                    </span>
                </div>
                <?php endif; ?>

                <?php endif; // end if rows found ?>
            </div>
        </div><!-- end tab-products -->

        <!-- ==================================================
             TAB: Report (contact messages with pagination)
             ================================================== -->
        <div class="admin-tab-content admin-tab-pane" id="tab-report"
             style="display:<?php echo $activeTab === 'report' ? 'block' : 'none'; ?>">

            <h3 style="margin-bottom:16px;">
                Contact Messages
                <span style="font-size:13px; font-weight:400; color:#6b7280; margin-left:6px;">
                    (<?php echo $msgTotal; ?> total<?php echo $unreadCount > 0 ? ', ' . $unreadCount . ' unread' : ''; ?>)
                </span>
            </h3>

            <?php if ($msgTotal === 0): ?>
                <p style="color:#6b7280; padding:10px 0;">No messages received yet.</p>
            <?php else: ?>
                <?php while ($msg = mysqli_fetch_assoc($msgResult)): ?>
                <div class="message-card <?php echo $msg['is_read'] ? '' : 'unread'; ?>">
                    <div class="message-card-top">
                        <div>
                            <span class="message-sender"><?php echo clean($msg['name']); ?></span>
                            <span class="message-email">&lt;<?php echo clean($msg['email']); ?>&gt;</span>
                        </div>
                        <span class="message-date">
                            <?php echo date("d M Y, H:i", strtotime($msg['sent_at'])); ?>
                        </span>
                    </div>
                    <p class="message-body"><?php echo nl2br(clean($msg['message'])); ?></p>
                    <?php if (!$msg['is_read']): ?>
                        <a href="profile.php?tab=report&mark_read=<?php echo $msg['message_id']; ?>&msgpage=<?php echo $msgPage; ?>"
                           class="btn btn-small btn-outline" style="margin-top:10px;">Mark as Read</a>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>

                <!-- Messages pagination -->
                <?php if ($msgTotalPages > 1): ?>
                <div class="admin-pagination">
                    <?php if ($msgPage > 1): ?>
                        <a href="profile.php?tab=report&msgpage=<?php echo $msgPage - 1; ?>" class="page-btn">&#8249;</a>
                    <?php else: ?>
                        <span class="page-btn" style="opacity:.35; cursor:not-allowed;">&#8249;</span>
                    <?php endif; ?>

                    <?php for ($p = 1; $p <= $msgTotalPages; $p++): ?>
                        <a href="profile.php?tab=report&msgpage=<?php echo $p; ?>"
                           class="page-btn <?php echo $p === $msgPage ? 'active' : ''; ?>">
                            <?php echo $p; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($msgPage < $msgTotalPages): ?>
                        <a href="profile.php?tab=report&msgpage=<?php echo $msgPage + 1; ?>" class="page-btn">&#8250;</a>
                    <?php else: ?>
                        <span class="page-btn" style="opacity:.35; cursor:not-allowed;">&#8250;</span>
                    <?php endif; ?>

                    <span class="page-info">
                        Page <?php echo $msgPage; ?> of <?php echo $msgTotalPages; ?>
                    </span>
                </div>
                <?php endif; ?>
            <?php endif; ?>

        </div><!-- end tab-report -->
    </div><!-- end admin-left-panel -->

    <!-- ---- RIGHT PANEL: Admin profile card (email only, no password) ---- -->
    <div class="admin-profile-card">
        <div class="profile-card">
            <div class="profile-avatar-wrap">
                <img src="<?php echo clean($avatarPath); ?>" alt="Profile" class="profile-avatar"
                     onerror="this.src='assets/images/default-user.png'">
                <label class="upload-icon-btn" title="Upload PNG profile picture">
                    <img src="assets/images/upload-icon.png" alt="Upload"
                         onerror="this.style.display='none'">
                    <input type="file" id="profilePicInput" accept=".png,image/png">
                </label>
            </div>

            <h3><?php echo clean($user['username']); ?></h3>
            <span class="role-badge">Admin</span>

            <?php if ($success): ?>
                <p style="color:#1d8348; font-size:13px; margin-bottom:10px;">✓ Email updated.</p>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="flash-message error" style="margin-bottom:10px; font-size:13px;">
                    <?php foreach ($errors as $e) echo clean($e) . '<br>'; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="profile.php?tab=<?php echo $activeTab; ?>"
                  data-validate style="text-align:left; margin-top:12px; width:100%;">
                <div class="form-group">
                    <label style="font-size:13px;">Email</label>
                    <input type="email" name="email" required
                           value="<?php echo clean($user['email']); ?>">
                </div>
                <button type="submit" name="update_profile" class="btn btn-small"
                        style="width:100%;">Save Email</button>
            </form>

            <a href="logout.php" class="btn btn-secondary btn-small logout-btn">Logout</a>
        </div>
    </div>

</div><!-- end admin-profile-layout -->

<?php else: ?>
<!-- ============================================================
     USER PROFILE LAYOUT
     Left card and Right edit card are equal height
     ============================================================ -->
<?php
$txPerPage    = 10;
$txPage       = isset($_GET['txpage']) ? max(1, (int)$_GET['txpage']) : 1;

$txCountStmt  = mysqli_prepare($conn, "SELECT COUNT(*) FROM transactions WHERE user_id = ?");
mysqli_stmt_bind_param($txCountStmt, "i", $userId);
mysqli_stmt_execute($txCountStmt);
$txTotal      = (int) mysqli_fetch_row(mysqli_stmt_get_result($txCountStmt))[0];

$txTotalPages = $txTotal > 0 ? (int)ceil($txTotal / $txPerPage) : 1;
$txPage       = min($txPage, $txTotalPages);
$txOffset     = ($txPage - 1) * $txPerPage;

$txStmt = mysqli_prepare($conn,
    "SELECT * FROM transactions WHERE user_id = ? ORDER BY purchased_at DESC LIMIT ? OFFSET ?");
mysqli_stmt_bind_param($txStmt, "iii", $userId, $txPerPage, $txOffset);
mysqli_stmt_execute($txStmt);
$txResult = mysqli_stmt_get_result($txStmt);
?>

<div class="user-profile-layout">

    <!-- LEFT: Profile card (equal height with edit card) -->
    <div class="profile-card">
        <div class="profile-avatar-wrap">
            <img src="<?php echo clean($avatarPath); ?>" alt="Profile" class="profile-avatar"
                 onerror="this.src='assets/images/default-user.png'">
            <label class="upload-icon-btn" title="Upload PNG profile picture">
                <img src="assets/images/upload-icon.png" alt="Upload"
                     onerror="this.style.display='none'">
                <input type="file" id="profilePicInput" accept=".png,image/png">
            </label>
        </div>
        <h3><?php echo clean($user['username']); ?></h3>
        <span class="role-badge"><?php echo ucfirst(clean($user['role'])); ?></span>
        <p class="profile-email-display"><?php echo clean($user['email']); ?></p>
        <a href="logout.php" class="btn btn-secondary btn-small logout-btn">Logout</a>
    </div>

    <!-- RIGHT: Edit form -->
    <div class="profile-edit-card">
        <h2>Edit Profile</h2>

        <?php if ($success): ?>
            <div class="flash-message success" style="margin-bottom:16px;">
                ✓ Profile updated successfully.
            </div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="flash-message error" style="margin-bottom:16px;">
                <?php foreach ($errors as $e) echo clean($e) . '<br>'; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="profile.php" data-validate>
            <div class="form-group">
                <label>Username</label>
                <input type="text" value="<?php echo clean($user['username']); ?>" disabled>
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required
                       value="<?php echo clean($user['email']); ?>">
            </div>
            <div class="form-group">
                <label for="new_password">
                    New Password
                    <span style="font-weight:400; color:#9ca3af;">(leave blank to keep current)</span>
                </label>
                <input type="password" id="new_password" name="new_password">
            </div>
            <button type="submit" name="update_profile" class="btn">Save Changes</button>
        </form>
    </div>

    <!-- FULL WIDTH: Transaction history -->
    <div class="transactions-card">
        <h2>Transaction History
            <span style="font-size:14px; font-weight:400; color:#6b7280; margin-left:8px;">
                (<?php echo $txTotal; ?> record<?php echo $txTotal !== 1 ? 's' : ''; ?> total)
            </span>
        </h2>

        <?php if ($txTotal === 0): ?>
            <p style="color:#6b7280; padding:16px 0;">
                No purchases yet.
                <a href="products.php" style="color:var(--primary);">Start shopping!</a>
            </p>
        <?php else: ?>
            <?php
            $rowNum = $txOffset + 1;
            while ($tx = mysqli_fetch_assoc($txResult)):
                $txItems = json_decode($tx['items_json'], true);
            ?>
            <div class="transaction-row">
                <div class="transaction-row-top">
                    <span class="transaction-id">Order #<?php echo $rowNum++; ?></span>
                    <span class="transaction-date">
                        <?php echo date("d M Y, H:i", strtotime($tx['purchased_at'])); ?>
                    </span>
                    <span class="transaction-total">
                        RM <?php echo number_format($tx['total_amount'], 2); ?>
                    </span>
                </div>
                <div class="transaction-items">
                    <?php
                    $parts = [];
                    foreach ($txItems as $it) {
                        $parts[] = clean($it['name'])
                                 . ' &times;' . (int)$it['quantity']
                                 . ' (RM ' . number_format($it['subtotal'], 2) . ')';
                    }
                    echo implode(' &nbsp;|&nbsp; ', $parts);
                    ?>
                </div>
                <div class="transaction-address">
                    <span>Delivery:</span> <?php echo clean($tx['address']); ?>
                </div>
            </div>
            <?php endwhile; ?>

            <?php if ($txTotalPages > 1): ?>
            <div class="t-pagination">
                <?php if ($txPage > 1): ?>
                    <a href="profile.php?txpage=<?php echo $txPage - 1; ?>" class="page-btn">&#8249;</a>
                <?php else: ?>
                    <span class="page-btn" style="opacity:.35; cursor:not-allowed;">&#8249;</span>
                <?php endif; ?>

                <?php for ($p = 1; $p <= $txTotalPages; $p++): ?>
                    <a href="profile.php?txpage=<?php echo $p; ?>"
                       class="page-btn <?php echo $p === $txPage ? 'active' : ''; ?>">
                        <?php echo $p; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($txPage < $txTotalPages): ?>
                    <a href="profile.php?txpage=<?php echo $txPage + 1; ?>" class="page-btn">&#8250;</a>
                <?php else: ?>
                    <span class="page-btn" style="opacity:.35; cursor:not-allowed;">&#8250;</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

</div><!-- end user-profile-layout -->
<?php endif; ?>

</div><!-- end profile-page -->

<?php require_once __DIR__ . '/includes/footer.php'; ?>
