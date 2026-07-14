-- ==========================================================
-- TechShop Leap — Complete Database Setup
-- Import this ONE file via phpMyAdmin to set up everything.
-- Steps: phpMyAdmin → Import → Choose File → Go
--
-- Demo accounts:
--   Admin  → username: admin  / password: admin123
--   User 1 → username: john   / password: user123
--   User 2 → username: sarah  / password: user123
-- ==========================================================

CREATE DATABASE IF NOT EXISTS techmart;
USE techmart;

-- Drop in reverse FK order so there are no constraint errors
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS cart_items;
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS users;

-- ==========================================================
-- TABLE: users
-- ==========================================================
CREATE TABLE users (
    user_id     INT          AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    email       VARCHAR(100) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('user','admin') NOT NULL DEFAULT 'user',
    profile_pic VARCHAR(255) DEFAULT NULL,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================================
-- TABLE: products
-- ==========================================================
CREATE TABLE products (
    product_id  INT           AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150)  NOT NULL,
    category    VARCHAR(50)   NOT NULL,
    description TEXT          NOT NULL,
    brand       VARCHAR(100)  NOT NULL,
    stock       INT           NOT NULL DEFAULT 0,
    price       DECIMAL(8,2)  NOT NULL,
    image_url   VARCHAR(255)  DEFAULT 'assets/images/default-product.png',
    created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================================
-- TABLE: cart_items
-- ==========================================================
CREATE TABLE cart_items (
    cart_id    INT  AUTO_INCREMENT PRIMARY KEY,
    user_id    INT  NOT NULL,
    product_id INT  NOT NULL,
    quantity   INT  NOT NULL DEFAULT 1,
    added_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)    REFERENCES users(user_id)       ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (user_id, product_id)
);

-- ==========================================================
-- TABLE: reviews
-- ==========================================================
CREATE TABLE reviews (
    review_id  INT     AUTO_INCREMENT PRIMARY KEY,
    product_id INT     NOT NULL,
    user_id    INT     NOT NULL,
    rating     TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment    TEXT    NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)    REFERENCES users(user_id)       ON DELETE CASCADE
);

-- ==========================================================
-- TABLE: transactions  (purchase history shown in profile)
-- ==========================================================
CREATE TABLE transactions (
    transaction_id INT           AUTO_INCREMENT PRIMARY KEY,
    user_id        INT           NOT NULL,
    items_json     TEXT          NOT NULL,
    total_amount   DECIMAL(10,2) NOT NULL,
    address        TEXT          NOT NULL,
    purchased_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ==========================================================
-- TABLE: contact_messages  (shown in admin → Report tab)
-- ==========================================================
CREATE TABLE contact_messages (
    message_id INT          AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(100) NOT NULL,
    message    TEXT         NOT NULL,
    is_read    TINYINT(1)   DEFAULT 0,
    sent_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================================
-- SAMPLE DATA — Users
-- Passwords are bcrypt hashes (PASSWORD_DEFAULT):
--   admin123  →  admin account
--   user123   →  john & sarah accounts
-- ==========================================================
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@techshopleap.test', '$2y$10$ODBvLqraIOl68llNDA9XgeBTQk.0JbjBt.lKVbUZVSqrHwm1Aqj7G', 'admin'),
('john',  'john@techshopleap.test',  '$2y$10$MK.2ThGddJ6YpipzaBnSreb4TCpsbQVHzZfrvN/LnYzl/ZPFhfmEi', 'user'),
('sarah', 'sarah@techshopleap.test', '$2y$10$MK.2ThGddJ6YpipzaBnSreb4TCpsbQVHzZfrvN/LnYzl/ZPFhfmEi', 'user');

-- ==========================================================
-- SAMPLE DATA — Products
-- 6 categories × multiple products = 20 products total
-- (Enough to test pagination: 9 per page on Products page)
-- Images: place product1.png–product20.png in assets/images/
-- ==========================================================

-- ---- Laptops (4 products) ----
INSERT INTO products (name, category, description, brand, stock, price, image_url) VALUES
(
    'UltraBook Pro 14"',
    'Laptops',
    'A featherlight 14-inch laptop powered by the latest 12-core processor and 16 GB RAM. The 2.8 K OLED display delivers vivid colour accuracy for both creative professionals and everyday users. Rated for 18 hours of battery life.',
    'NovaTech', 25, 3499.00, 'assets/images/product1.png'
),
(
    'ProDesk Elite 15',
    'Laptops',
    'A 15.6-inch business laptop with a Full HD anti-glare display, fingerprint reader, and enterprise-grade security features. Comes with 32 GB RAM and a 1 TB NVMe SSD for blazing performance.',
    'DellStar', 18, 4299.00, 'assets/images/product2.png'
),
(
    'SlimAir X360',
    'Laptops',
    'Ultra-slim convertible laptop with a 360-degree hinge, 13-inch touchscreen, and stylus support. Weighs only 1.1 kg — perfect for students and travellers.',
    'SurfPro', 30, 2799.00, 'assets/images/product3.png'
),
(
    'GamerForce RTX 16',
    'Laptops',
    'High-performance 16-inch gaming laptop featuring an RTX 4070 GPU, 165 Hz QHD display, and a per-key RGB mechanical keyboard. Engineered to handle AAA titles at maximum settings.',
    'RazerX', 12, 6299.00, 'assets/images/product4.png'
);

-- ---- Smartphones (4 products) ----
INSERT INTO products (name, category, description, brand, stock, price, image_url) VALUES
(
    'AeroPhone X12',
    'Smartphones',
    'A flagship smartphone featuring a 6.5-inch AMOLED display with 120 Hz refresh rate, triple rear camera system (108 MP + 12 MP + 10 MP), and 65 W fast charging from flat to full in 35 minutes.',
    'AeroTech', 40, 2599.00, 'assets/images/product5.png'
),
(
    'NovaStar S22 Ultra',
    'Smartphones',
    'Premium Android smartphone with a 6.8-inch Dynamic AMOLED display, built-in S Pen stylus, 200 MP main camera, and 5000 mAh battery supporting 45 W wired and 15 W wireless charging.',
    'NovaTech', 35, 4599.00, 'assets/images/product6.png'
),
(
    'MiniPhone Lite 6',
    'Smartphones',
    'Compact 5.4-inch smartphone that fits perfectly in one hand. Despite its small size, it packs a powerful A16 chip, dual cameras, and MagSafe wireless charging support.',
    'ApexMobile', 50, 1799.00, 'assets/images/product7.png'
),
(
    'BudgetX Pro 5G',
    'Smartphones',
    'Affordable 5G smartphone with a 6.6-inch IPS LCD display, quad-camera setup, and a massive 6000 mAh battery. Ideal for users who need reliability without breaking the bank.',
    'Xiotech', 80, 799.00, 'assets/images/product8.png'
);

-- ---- Audio (3 products) ----
INSERT INTO products (name, category, description, brand, stock, price, image_url) VALUES
(
    'Pulse Pro Wireless Earbuds',
    'Audio',
    'True wireless earbuds with industry-leading active noise cancellation, custom 11 mm drivers, IPX4 water resistance, and 30 hours total playtime with the charging case. Spatial audio supported.',
    'SoundWave', 60, 399.00, 'assets/images/product9.png'
),
(
    'BassHead Over-Ear Headphones',
    'Audio',
    'Premium over-ear headphones with 40 mm drivers, 30 dB hybrid active noise cancellation, and a lush faux-leather headband. Up to 40 hours battery life over Bluetooth 5.3.',
    'AudioMax', 45, 599.00, 'assets/images/product10.png'
),
(
    'MiniSpeaker 360',
    'Audio',
    'Portable 360-degree Bluetooth speaker with rich bass, IP67 waterproof rating, and 20-hour battery. Drop-proof and dustproof — your ideal outdoor companion.',
    'SoundWave', 70, 249.00, 'assets/images/product11.png'
);

-- ---- Monitors (3 products) ----
INSERT INTO products (name, category, description, brand, stock, price, image_url) VALUES
(
    'Visionary 27" 4K Monitor',
    'Monitors',
    'A 27-inch 4K UHD IPS monitor with HDR400, 144 Hz refresh rate, 1 ms response time, and factory-calibrated colour accuracy (Delta E < 2). Thin bezels for multi-monitor setups.',
    'PixelView', 18, 1399.00, 'assets/images/product12.png'
),
(
    'CurveVision 34" Ultrawide',
    'Monitors',
    '34-inch 1800R curved ultrawide QHD display at 100 Hz. Eliminates screen tearing with Adaptive Sync. Built-in USB-C hub with 65 W Power Delivery — one cable to rule them all.',
    'PixelView', 10, 1899.00, 'assets/images/product13.png'
),
(
    'EyeCare 24" FHD',
    'Monitors',
    '24-inch Full HD IPS monitor with TÜV Rheinland Eye Comfort certification, flicker-free backlight, and blue-light filter. Affordable everyday display for home offices.',
    'ClearView', 40, 599.00, 'assets/images/product14.png'
);

-- ---- Accessories (6 products) ----
INSERT INTO products (name, category, description, brand, stock, price, image_url) VALUES
(
    'ClickMaster Mech Keyboard',
    'Accessories',
    'Compact TKL mechanical keyboard with hot-swappable switches (Brown, Red, or Blue), per-key RGB backlighting, and an aircraft-grade aluminium frame. Dual-mode: USB-C or Bluetooth 5.0.',
    'KeyForge', 50, 259.00, 'assets/images/product15.png'
),
(
    'PowerBank Max 20000 mAh',
    'Accessories',
    'High-capacity portable power bank with two USB-C ports (65 W + 45 W) and one USB-A port. Charges a laptop, phone, and tablet simultaneously. LED indicator shows remaining charge.',
    'ChargeUp', 80, 89.00, 'assets/images/product16.png'
),
(
    'ErgoMouse Pro Wireless',
    'Accessories',
    'Ergonomic vertical wireless mouse designed to reduce wrist strain during long work sessions. 4000 DPI optical sensor, 6 programmable buttons, and 90-day battery life on a single AA cell.',
    'ComfortTech', 65, 129.00, 'assets/images/product17.png'
),
(
    'SlimHub USB-C 7-in-1',
    'Accessories',
    '7-in-1 USB-C hub: HDMI 4K, 3× USB-A 3.0, SD/microSD card reader, and 100 W pass-through charging. Plug-and-play — no drivers needed. Compact aluminium shell.',
    'ConnectPro', 90, 79.00, 'assets/images/product18.png'
),
(
    'WebCam HD 1080p',
    'Accessories',
    'Full HD 1080p webcam with built-in noise-cancelling dual microphones, auto-focus, and a 90-degree wide-angle lens. Privacy cover included. Works with Zoom, Teams, and Google Meet.',
    'VisionTech', 55, 149.00, 'assets/images/product19.png'
),
(
    'Desk Pad XL',
    'Accessories',
    'Extra-large (90 × 40 cm) mouse and desk pad with stitched anti-fray edges, non-slip rubber base, and a smooth micro-textured surface optimised for both optical and laser mice.',
    'KeyForge', 100, 49.00, 'assets/images/product20.png'
);

-- ==========================================================
-- SAMPLE DATA — Reviews (so product pages look populated)
-- ==========================================================
INSERT INTO reviews (product_id, user_id, rating, comment) VALUES
(1, 2, 5, 'Absolutely love this laptop! Battery life is incredible and the OLED screen is stunning. Worth every ringgit.'),
(1, 3, 4, 'Great build quality and very fast. Only wish it had more USB-A ports. Other than that, perfect daily driver.'),
(2, 2, 4, 'Solid business laptop. The keyboard is comfortable for long typing sessions. Fingerprint reader works great.'),
(5, 3, 5, 'Best phone I have ever owned. Camera quality in low light is outstanding and the charging speed is insane.'),
(5, 2, 5, 'Upgraded from an older AeroPhone model — huge improvement. Screen is gorgeous and performance is buttery smooth.'),
(9, 2, 5, 'Noise cancellation blocks out my entire office. Sound quality rivals headphones costing twice the price.'),
(9, 3, 4, 'Comfortable fit and great sound. The only minor gripe is the touch controls take a bit of getting used to.'),
(10, 3, 5, 'Bass is powerful without being muddy. 40-hour battery is no joke — I charge it once a week.'),
(12, 2, 5, 'Colours are accurate right out of the box. The 144 Hz makes everything look silky smooth.'),
(15, 3, 5, 'Hot-swap switches are a game changer. Tried all three switch types and settled on browns. Love the RGB too.'),
(16, 2, 4, 'Charged my laptop and phone at the same time no problem. Very compact for its capacity.'),
(17, 3, 4, 'My wrist pain is completely gone after switching to this mouse. Took a day to get used to the angle.');

-- ==========================================================
-- SAMPLE DATA — Transactions (shows in john's profile page)
-- items_json stores product name, qty, unit price, subtotal
-- ==========================================================
INSERT INTO transactions (user_id, items_json, total_amount, address, purchased_at) VALUES
(
    2,
    '[{"name":"Pulse Pro Wireless Earbuds","quantity":1,"price":399.00,"subtotal":399.00},{"name":"PowerBank Max 20000 mAh","quantity":2,"price":89.00,"subtotal":178.00}]',
    577.00,
    '12, Jalan Sungai Long 3, Bandar Sungai Long, 43000 Kajang, Selangor',
    '2026-05-10 14:32:00'
),
(
    2,
    '[{"name":"AeroPhone X12","quantity":1,"price":2599.00,"subtotal":2599.00}]',
    2599.00,
    '12, Jalan Sungai Long 3, Bandar Sungai Long, 43000 Kajang, Selangor',
    '2026-06-01 09:15:00'
),
(
    2,
    '[{"name":"Desk Pad XL","quantity":1,"price":49.00,"subtotal":49.00},{"name":"SlimHub USB-C 7-in-1","quantity":1,"price":79.00,"subtotal":79.00},{"name":"ErgoMouse Pro Wireless","quantity":1,"price":129.00,"subtotal":129.00}]',
    257.00,
    '12, Jalan Sungai Long 3, Bandar Sungai Long, 43000 Kajang, Selangor',
    '2026-06-18 16:50:00'
);

-- ==========================================================
-- SAMPLE DATA — Contact Messages (shows in admin Report tab)
-- ==========================================================
INSERT INTO contact_messages (name, email, message, is_read) VALUES
(
    'John Doe',
    'john@techshopleap.test',
    'Hi, I placed an order for the AeroPhone X12 last week but have not received a shipping update yet. Could you please check the status for me? Thank you.',
    0
),
(
    'Sarah Lim',
    'sarah@techshopleap.test',
    'Hello! I am interested in bulk purchasing 10 units of the ClickMaster Mech Keyboard for our office. Is there a corporate discount available?',
    0
),
(
    'Ahmad Faiz',
    'ahmad.faiz@email.com',
    'The Visionary 27" monitor I received has a stuck pixel near the top-left corner. I would like to request a replacement under warranty. Order date was 5 June 2026.',
    1
);

-- ==========================================================
-- SAMPLE DATA — Cart items pre-loaded for john (user_id = 2)
-- So the cart page is not empty during demo
-- ==========================================================
INSERT INTO cart_items (user_id, product_id, quantity) VALUES
(2, 4,  1),   -- GamerForce RTX 16
(2, 11, 1),   -- MiniSpeaker 360
(2, 18, 2);   -- SlimHub USB-C 7-in-1 × 2
