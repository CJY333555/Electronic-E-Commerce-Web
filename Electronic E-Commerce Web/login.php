<?php
$pageTitle = "Login";
$basePath = "";
$pageCSS   = ['forms.css'];
require_once __DIR__ . "/includes/header.php";

if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $errors[] = "Please enter both username and password.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT user_id, username, password, role FROM users WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            $_SESSION['flash_message'] = "Welcome back, " . $user['username'] . "!";
            $_SESSION['flash_type'] = "success";
            header("Location: index.php");
            exit();
        } else {
            $errors[] = "Invalid username or password.";
        }
    }
}
?>

<div class="form-wrap">
    <h1>Login to TechMart</h1>

    <?php if (!empty($errors)): ?>
        <div class="flash-message error" style="margin-bottom:16px;">
            <?php foreach ($errors as $err) echo clean($err) . "<br>"; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php" data-validate>
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn" style="width:100%;">Login</button>
    </form>

    <p class="form-footer-text">Don't have an account? <a href="register.php">Register here</a></p>
    <p class="form-footer-text" style="color:#9ca3af; font-size:12px;">Demo: admin / admin123 (admin) &nbsp;|&nbsp; john / user123 (user)</p>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
