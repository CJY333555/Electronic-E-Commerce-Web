# TechShop – Electronics Online Store

A dynamic e-commerce web application built with **HTML, CSS, JavaScript, PHP, and MySQL** for the
Web Application Development.

No external frameworks or libraries are used (no Bootstrap, jQuery, React, Laravel, etc.) —
everything is built with vanilla HTML/CSS/JS/PHP/MySQL.

---

## 1. Features Overview

| Requirement                     | Implementation |
|----------------------------------|-----------------|
| Home Page                        | `index.php` – hero banner + featured products |
| Navigation Menu (with dropdown)  | `includes/header.php` – responsive nav with user dropdown |
| Contact Page                     | `contact.php` – contact form, Google Map embed, social links |
| Item Listing Page                 | `products.php` – product list with category filter |
| Item Details Page                 | `product_details.php` – full product info, add-to-cart, and customer reviews |
| Cart / Wishlist Page              | `cart.php` – shopping cart with quantity update and remove |
| User Login System                 | `login.php`, `register.php`, `logout.php`, PHP sessions |
| Responsive Design                 | `assets/css/ (split into base, layout, home, products, details, cart, forms, contact, responsive) |
| CRUD (Create/Read/Update/Delete)  | See table below |

### CRUD Operations Mapping

| Operation | Where it happens |
|-----------|-------------------|
| **Create** | Register account (`register.php`), Add to cart (`cart_action.php`), Post a review (`product_details.php`), Admin add product (`admin/products.php`) |
| **Read**   | Product listing (`products.php`), product details + reviews (`product_details.php`), Cart (`cart.php`) |
| **Update** | Edit profile/password (`profile.php`), Update cart quantity (`cart_action.php`), Admin edit product (`admin/products.php`) |
| **Delete** | Remove cart item (`cart.php` → `cart_action.php`), Delete own/any review (`review_action.php`), Admin delete product (`admin/products.php`) |

### Product Reviews

Logged-in users can leave a star rating (1–5) and a comment on any product's details page.
Reviews are listed below the product description with an average rating shown at the top.
Users can delete their own reviews; admins can delete any review.

---

## 2. Requirements

- **WAMPServer** (Apache + MySQL + PHP) — tested with PHP 7.4+ / 8.x
- A web browser

---

## 3. Installation Instructions (WAMPServer)

1. **Copy project files**
   - Extract the ZIP file.
   - Copy the entire `techmart` folder into your WAMPServer's `www` directory.
     Example: `C:\wamp64\www\techmart`

2. **Start WAMPServer**
   - Launch WAMPServer and wait until the icon turns green (all services running).

3. **Create the database**
   - Open your browser and go to: `http://localhost/phpmyadmin`
   - Login (default: username `root`, no password — unless you've changed it).
   - Click **Import** in the top menu.
   - Choose the file `database.sql` (included in this submission).
   - Click **Go**. This will create the `techmart` database with all tables and sample data.

4. **Check database configuration**
   - Open `includes/db.php` and confirm the credentials match your WAMP MySQL setup:
     ```php
     $DB_HOST = "localhost";
     $DB_USER = "root";
     $DB_PASS = "";       // change if your MySQL root has a password
     $DB_NAME = "Electronic E-Commerce Web";
     ```

5. **Run the project**
   - Open your browser and visit: `http://localhost/techmart/`
   - You should see the TechMart home page.

---

## 4. Demo Accounts

| Role  | Username | Password  |
|-------|----------|-----------|
| Admin | admin    | admin123  |
| User  | john     | user123   |

Admin accounts can access **Manage Products** (full CRUD) from the user dropdown menu in the navbar.
Regular users can browse products, add items to cart, manage their cart, leave reviews, and update their profile.

You may also register a new account via the **Register** page (new accounts default to "user" role).

---

## 5. Folder Structure

```
techmart/
│
├── index.php                 # Home page
├── products.php                # Item listing page (products)
├── product_details.php         # Item details page (+ reviews)
├── cart.php                    # Cart page
├── contact.php                 # Contact page
├── login.php / register.php / logout.php / profile.php
├── cart_action.php             # Handles add/remove/update cart (Create/Update/Delete)
├── review_action.php           # Handles review deletion (Delete)
│
├── admin/
│   └── products.php            # Admin CRUD panel for managing products
│
├── includes/
│   ├── db.php                  # Database connection
│   ├── auth.php                 # Session/auth helper functions
│   ├── header.php               # Shared header + navigation
│   └── footer.php               # Shared footer
│
├── assets/
│   ├── css/base.css            # Variables, reset, buttons, flash, utility
│   ├── css/layout.css          # Header, navbar, dropdown, footer
│   ├── css/home.css            # Hero banner, features section
│   ├── css/products.css        # Filter bar, product grid, product cards
│   ├── css/details.css         # Product details page, reviews section
│   ├── css/cart.css            # Cart table, admin tables
│   ├── css/forms.css           # All form wrappers and inputs
│   ├── css/contact.css         # Contact page layout
│   └── css/responsive.css      # All @media queries (loaded last on every page)
│   ├── js/script.js            # Nav toggle, dropdown, form validation
│   └── images/                 # Product images (placeholders)
│
└── database.sql                # Database schema + sample data
```

---

## 6. Notes for Demonstration

- All SQL queries use **prepared statements** (`mysqli_prepare`) to prevent SQL injection.
- Passwords are hashed using PHP's `password_hash()` / verified with `password_verify()`.
- Client-side validation (JavaScript) is paired with server-side validation (PHP) on all forms.
- Responsive design relies solely on CSS `@media` queries (no framework grid systems).
- Each group member should be ready to explain the section(s) of code they implemented during
  the demo, per the assignment's AI usage policy.

---

## 7. Customization / Naming

"TechMart" is a working title — choose your own unique project title before registering it
on the WBLE Google Sheet (per the assignment's title uniqueness requirement). To rename:
- `includes/header.php` (logo + navigation)
- `index.php` (hero text)
- `database.sql` (database name, sample product data, categories, brands)
- `includes/db.php` (`$DB_NAME` to match your renamed database)

To change product categories, simply edit/add rows in the `products` table — the category
filter on `products.php` is generated dynamically from whatever categories exist in the database.
