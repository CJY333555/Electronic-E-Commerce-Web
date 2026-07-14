<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

$navProfilePic = '';
if (isLoggedIn()) {
    $npStmt = mysqli_prepare($conn, "SELECT profile_pic FROM users WHERE user_id = ?");
    mysqli_stmt_bind_param($npStmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($npStmt);
    $npRow = mysqli_fetch_assoc(mysqli_stmt_get_result($npStmt));
    $navProfilePic = getProfilePic($npRow['profile_pic'] ?? null, isset($basePath) ? $basePath : '');
}

// Keep current search query in the bar if we're on search.php
$navSearchQuery = isset($_GET['q']) ? clean($_GET['q']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset($pageTitle) ? clean($pageTitle) . " | TechShop Leap" : "TechShop Leap"; ?></title>
<?php
$alwaysLoad = ['base.css', 'layout.css'];
$alwaysLast = ['responsive.css'];
$extraCSS   = isset($pageCSS) ? $pageCSS : [];
$bp         = isset($basePath) ? $basePath : '';
foreach (array_merge($alwaysLoad, $extraCSS, $alwaysLast) as $cssFile):
?>
<link rel="stylesheet" href="<?php echo $bp . 'assets/css/' . $cssFile; ?>">
<?php endforeach; ?>
</head>
<body>

<header class="site-header" id="siteHeader">

    <!-- Logo -->
    <div class="logo">
        <a href="<?php echo $bp; ?>index.php">TechShop<span> Leap</span></a>
    </div>

    <!-- ── Search bar (centre of header) ── -->
    <form method="GET" action="<?php echo $bp; ?>search.php" class="nav-search-form">
        <div class="nav-search-bar">
            <input
                type="text"
                name="q"
                class="nav-search-input"
                placeholder="Search products or brands..."
                value="<?php echo $navSearchQuery; ?>"
                autocomplete="off">
            <button type="submit" class="nav-search-btn" title="Search">
                <img src="<?php echo $bp; ?>assets/images/search-icon.png"
                     alt="Search"
                     class="nav-search-icon"
                     onerror="this.style.display='none';
                              this.nextElementSibling.style.display='inline';">
                <span class="search-icon-fallback" style="display:none;">&#128269;</span>
            </button>
        </div>
    </form>

    <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">&#9776;</button>

    <!-- Nav links -->
    <nav class="main-nav" id="mainNav">
        <ul>
            <li><a href="<?php echo $bp; ?>index.php">Home</a></li>
            <li><a href="<?php echo $bp; ?>products.php">Products</a></li>
            <li><a href="<?php echo $bp; ?>contact.php">Contact</a></li>

            <?php if (isLoggedIn()): ?>
                <!-- Cart icon -->
                <li>
                    <a href="<?php echo $bp; ?>cart.php" class="cart-nav-link" title="My Cart">
                        <img src="<?php echo $bp; ?>assets/images/cart-icon.png"
                             alt="Cart" class="nav-icon-img"
                             onerror="this.style.display='none';
                                      this.nextElementSibling.style.display='inline';">
                        <span class="nav-icon-fallback" style="display:none;">🛒</span>
                    </a>
                </li>
                <!-- Profile circle -->
                <li>
                    <a href="<?php echo $bp; ?>profile.php" class="profile-nav-link" title="My Profile">
                        <img src="<?php echo $navProfilePic; ?>"
                             alt="Profile" class="profile-circle-nav"
                             onerror="this.src='<?php echo $bp; ?>assets/images/default-user.png'">
                    </a>
                </li>
            <?php else: ?>
                <li><a href="<?php echo $bp; ?>login.php" class="btn btn-small">Log in</a></li>
                <li><a href="<?php echo $bp; ?>register.php">Register</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<?php if (isset($_SESSION['flash_message'])): ?>
    <div class="flash-message <?php echo clean($_SESSION['flash_type'] ?? 'success'); ?>">
        <?php echo clean($_SESSION['flash_message']);
              unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    </div>
<?php endif; ?>

<main class="site-main">
