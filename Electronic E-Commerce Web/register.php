<?php
$pageTitle = "Register";
$basePath = "";
$pageCSS   = ["forms.css"];
require_once __DIR__ . "/includes/header.php";

if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // ---- Server-side validation ----
    if ($username === '' || strlen($username) < 3) $errors[] = "Username must be at least 3 characters.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Please enter a valid email address.";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";
    if ($password !== $confirmPassword) $errors[] = "Passwords do not match.";

    if (empty($errors)) {
        // Check for duplicate username/email
        $checkStmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE username = ? OR email = ?");
        mysqli_stmt_bind_param($checkStmt, "ss", $username, $email);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);

        if (mysqli_num_rows($checkResult) > 0) {
            $errors[] = "Username or email is already registered.";
        } else {
            // Create operation - insert new user with hashed password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $insertStmt = mysqli_prepare($conn, "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
            mysqli_stmt_bind_param($insertStmt, "sss", $username, $email, $hashedPassword);

            if (mysqli_stmt_execute($insertStmt)) {
                $_SESSION['flash_message'] = "Registration successful! Please log in.";
                $_SESSION['flash_type'] = "success";
                header("Location: login.php");
                exit();
            } else {
                $errors[] = "Something went wrong. Please try again.";
            }
        }
    }
}
?>

<div class="form-wrap">
    <h1>Create an Account</h1>

    <?php if (!empty($errors)): ?>
        <div class="flash-message error" style="margin-bottom:16px;">
            <?php foreach ($errors as $err) echo clean($err) . "<br>"; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="register.php" data-validate>
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required value="<?php echo isset($_POST['username']) ? clean($_POST['username']) : ''; ?>">
        </div>
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? clean($_POST['email']) : ''; ?>">
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required data-minlength="6">
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>
        <button type="submit" class="btn" style="width:100%;">Register</button>
    </form>

    <p class="form-footer-text">Already have an account? <a href="login.php">Login here</a></p>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
