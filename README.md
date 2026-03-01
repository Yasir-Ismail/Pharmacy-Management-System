# Pharmacy Management System

A practical, production-ready Pharmacy / Medical Store Management System built with PHP, MySQL, HTML, Bootstrap, and JavaScript. Designed for daily use in real pharmacies — focused on data accuracy, stock control, expiry safety, and clear reporting.

---

## Features

- **Authentication** — Admin/Staff login with password hashing and session protection
- **Medicine Management** — Full CRUD with batch tracking, expiry dates, pricing, and supplier links
- **Stock Control** — Automatic stock reduction on sales, low stock warnings, out-of-stock tracking
- **Expiry Protection** — Expired medicines highlighted in red, expiring-soon warnings, **hard block on selling expired stock**
- **Sales Module** — Multi-item sale form with instant stock deduction and auto-calculation
- **Sales History** — Date-filterable sales records with full detail view and print support
- **Reports** — Daily, monthly, date-range sales reports + expired medicines + low stock reports with CSV export
- **Dashboard** — At-a-glance summary: today's sales, alerts, stock value, recent transactions
- **Supplier Management** — Track suppliers linked to medicines

---

## Tech Stack

| Layer    | Technology                      |
|----------|---------------------------------|
| Backend  | Core PHP (no frameworks)        |
| Database | MySQL with PDO prepared statements |
| Frontend | HTML5, Bootstrap 5, Bootstrap Icons |
| JS       | Vanilla JavaScript              |
| Server   | XAMPP / WAMP compatible         |

---

## Folder Structure

```
pharmacy-system/
├── config/
│   └── db.php              # Database config & helper functions
├── auth/
│   ├── login.php            # Login page
│   └── logout.php           # Session destroy & redirect
├── admin/
│   ├── dashboard.php        # Main dashboard with alerts
│   ├── medicines.php        # Medicine CRUD
│   ├── new_sale.php         # Create new sale
│   ├── sales.php            # Sales history
│   ├── sale_detail.php      # Individual sale view
│   ├── reports.php          # All reports (daily/monthly/expired/lowstock)
│   ├── expiry_alerts.php    # Expiry alert page
│   ├── low_stock.php        # Low stock alert page
│   └── suppliers.php        # Supplier management
├── includes/
│   ├── header.php           # Common header with navigation
│   └── footer.php           # Common footer
├── assets/
│   ├── css/
│   │   └── style.css        # Custom styles
│   └── js/
│       └── app.js           # Common JavaScript
├── database/
│   └── schema.sql           # Database schema + sample data
├── index.php                # Root redirect
└── README.md
```

---

## Setup Instructions (XAMPP)

### 1. Install XAMPP
Download and install [XAMPP](https://www.apachefriends.org/) with Apache and MySQL enabled.

### 2. Clone / Copy Project
Copy the entire project folder into your XAMPP `htdocs` directory:
```
C:\xampp\htdocs\pharmacy-system\
```

**Important:** The folder must be named `pharmacy-system` for all internal links to work correctly.

### 3. Create Database
1. Start Apache and MySQL from the XAMPP Control Panel
2. Open **phpMyAdmin**: http://localhost/phpmyadmin
3. Click **Import** tab (or create database `pharmacy_db` first)
4. Select the file `database/schema.sql` and click **Go**

This creates the database, all tables, a default admin user, sample suppliers, and sample medicines.

### 4. Configure Database Connection
Edit `config/db.php` if your MySQL settings differ from defaults:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'pharmacy_db');
define('DB_USER', 'root');      // Default XAMPP user
define('DB_PASS', '');          // Default XAMPP has no password
```

### 5. Access the System
Open your browser and go to:
```
http://localhost/pharmacy-system/
```

### 6. Login
| Field    | Value               |
|----------|---------------------|
| Email    | admin@pharmacy.com  |
| Password | admin123            |

---

## Configuration

Edit `config/db.php` to change these system constants:

| Constant              | Default | Description                           |
|-----------------------|---------|---------------------------------------|
| `LOW_STOCK_THRESHOLD` | 50      | Units at/below which low stock alerts trigger |
| `EXPIRY_WARNING_DAYS` | 30      | Days before expiry to show warnings   |

---

## Security Features

- **Password Hashing** — bcrypt via `password_hash()` / `password_verify()`
- **Prepared Statements** — All queries use PDO with parameterized bindings
- **Session Protection** — `session_regenerate_id()` on login
- **Input Validation** — Server-side validation on all forms
- **Role-Based Access** — Admin vs Staff permission checks
- **Expiry Sale Block** — Hard server-side prevention of selling expired medicines
- **XSS Protection** — All output escaped with `htmlspecialchars()`

---

## Sample Data

The schema includes 12 sample medicines with various scenarios:
- Medicines with future expiry dates (safe)
- Medicines expiring soon (within 30 days of March 2026)
- Medicines already expired (for testing alerts)
- Low stock items (for testing warnings)
- 3 sample suppliers

---

## License

This project is open source and available for use in real pharmacy operations.