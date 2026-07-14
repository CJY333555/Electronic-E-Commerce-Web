<?php
$pageTitle = "Contact Us";
$basePath  = "";
$pageCSS   = ['contact.css', 'forms.css'];
require_once __DIR__ . '/includes/header.php';

$formSubmitted = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '')                                    $errors[] = "Name is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))      $errors[] = "A valid email is required.";
    if ($message === '')                                 $errors[] = "Message is required.";

    if (empty($errors)) {
        // Save to contact_messages table — admin sees it in Report tab
        $stmt = mysqli_prepare($conn, "INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sss", $name, $email, $message);
        mysqli_stmt_execute($stmt);
        $formSubmitted = true;
    }
}
?>

<section class="page-heading">
    <h1>Contact Us</h1>
    <p>Questions or feedback? We'd love to hear from you.</p>
</section>

<div class="contact-wrap">
    <div class="contact-info">
        <h2>Get in Touch</h2>
        <p><strong>Email:</strong> support@techshopleap.test</p>
        <p><strong>Phone:</strong> +60 12-345 6789</p>
        <p><strong>Address:</strong> Universiti Tunku Abdul Rahman, Jalan Sungai Long, Bandar Sungai Long, 43000 Kajang, Selangor, Malaysia</p>

        <iframe src="https://www.google.com/maps?q=UTAR+Sungai+Long&output=embed"
                loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
    </div>

    <div class="contact-info">
        <h2>Send Us a Message</h2>

        <?php if ($formSubmitted): ?>
            <p style="color:#1d8348; font-weight:600; font-size:15px;">
                ✓ Thank you! Your message has been sent. We will get back to you soon.
            </p>
        <?php else: ?>
            <?php if (!empty($errors)): ?>
                <div class="flash-message error" style="margin:0 0 16px;">
                    <?php foreach ($errors as $err) echo clean($err) . "<br>"; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="contact.php" data-validate>
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" required
                           value="<?php echo isset($_POST['name']) ? clean($_POST['name']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required
                           value="<?php echo isset($_POST['email']) ? clean($_POST['email']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" rows="5" required><?php echo isset($_POST['message']) ? clean($_POST['message']) : ''; ?></textarea>
                </div>
                <button type="submit" class="btn">Send Message</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
