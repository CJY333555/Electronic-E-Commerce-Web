<?php
// Admin product management has moved to the Profile page (admin view).
// This file redirects for backward compatibility.
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
header("Location: ../profile.php?tab=products");
exit();
