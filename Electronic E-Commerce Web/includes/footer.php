</main>

<footer class="site-footer">
    <div class="footer-content">
        <div class="footer-col">
            <h3>TechMart</h3>
            <p>Your trusted online store for laptops, smartphones, audio gear, monitors, and accessories at honest prices.</p>
        </div>
        <div class="footer-col">
            <h3>Quick Links</h3>
            <ul>
                <li><a href="<?php echo isset($basePath) ? $basePath : ''; ?>index.php">Home</a></li>
                <li><a href="<?php echo isset($basePath) ? $basePath : ''; ?>products.php">Products</a></li>
                <li><a href="<?php echo isset($basePath) ? $basePath : ''; ?>contact.php">Contact</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h3>Contact</h3>
            <p>Email: support@techmart.test<br>Phone: +60 12-345 6789</p>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?php echo date("Y"); ?> TechMart. All rights reserved. | UECS2094/UECS2194/EECS2194 Group Assignment</p>
    </div>
</footer>

<script src="<?php echo isset($basePath) ? $basePath : ''; ?>assets/js/script.js"></script>
</body>
</html>
