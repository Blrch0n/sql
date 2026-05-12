# Hospital Appointment Management System

This project is a role-based (Admin, Doctor, Patient) hospital appointment management application developed in PHP and MySQL. It features robust user authentication, dashboard interfaces, secure appointment scheduling logic, and strong defenses against web vulnerabilities like SQL injection.

## System Requirements

- PHP 8.0+
- MySQL 8.0+ or MariaDB 10.4+
- PDO PHP Extension

## Setup Instructions

1. **Database Setup:**
   Run the `database.sql` script in your MySQL server. This will:
   - Create the `hospital_db` database.
   - Set up the required tables (`users`, `doctors`, `appointments`, `products`, `doctor_slots`, etc.).
   - Create a dedicated least-privilege user `'hospital_app'@'localhost'` with the password `StrongPassword123!`.
   - Insert initial seed and test data.
   
   *Note:* Ensure you run this directly via the MySQL command line, phpMyAdmin, or another client.
   
2. **Configuration:**
   Ensure `config/db.php` is properly pointing to the database by setting environment variables in XAMPP.
   For Linux XAMPP, edit /opt/lampp/etc/httpd.conf or create a wrapper script to export `DB_USER` and `DB_PASS` before starting Apache:
   ```bash
   export DB_USER=hospital_app
   export DB_PASS=StrongPassword123!
   /opt/lampp/lampp start
   ```
   Using an .env file or SetEnv in Apache config is also supported.
   Ensure `config/db.php` is properly pointing to the newly created database with the least-privilege credentials:
   ```php
   ```

3. **Start the Application:**
   Host the directory using a web server like Apache (XAMPP), Nginx, or the PHP built-in server:
   ```bash
   php -S localhost:8000
   ```
   Access the application in your browser: `http://localhost:8000`.

## Architecture & Security Highlights

This application implements several foundational security layers:

### 1. SQL Injection (SQLi) Prevention
- **PDO Prepared Statements:** Used exclusively across all database queries. This effectively mitigates all traditional SQL injection attacks (Union-based, Error-based, Blind).
- **Type Casting:** Integer IDs are strictly cast using `(int)` before logic begins.
- **PortSwigger Lab Defenses:** Tested against standard injection payloads like `' OR 1=1--` to ensure secure schema boundaries and prevent data or login bypass. 

### 2. Transactional Atomic Operations
- The application uses `beginTransaction()` and `commit()` during appointment scheduling (`patient/book_appointment.php`).
- A `rowCount()` check prevents **Race Conditions**, guaranteeing that two patients cannot book the exact same `doctor_slot` simultaneously. 
- When an appointment is canceled by a doctor, the system re-opens the exact same slot atomically.

### 3. Least Privilege Principle
- The application connects to MySQL using a specifically scoped database user (`hospital_app`), rather than relying on `root`. This prevents external command execution and limits damage scope in the event of an arbitrary code execution vulnerability.

### 4. Cross-Site Request Forgery (CSRF) Prevention
- Every state-changing form (creating logic, changing appointment statuses) relies on securely generated CSRF tokens validated by `verify_csrf_token()`.

### 5. Error Handling
- Raw database exceptions (`$e->getMessage()`) are sanitized or logged securely to prevent exposing system schema topology to standard users.

## Automated Security Testing
You can run the built-in vulnerability test suite to ensure SQL injection protections are working manually:
```bash
php test_sqli.php
```
It returns an exit code of `0` on success and `1` on failure, allowing integration with CI/CD runners if required.

### Database Setup
To cleanly import the demo database with the correct seeding:
```bash
sudo /opt/lampp/bin/mysql -u root < database.sql
```

Default Accounts:
- admin@medicare.mn (password: password)
- doctor@medicare.mn (password: password)
- patient@medicare.mn (password: password)

### Troubleshooting
Make sure you use XAMPP's PHP for running tests:
```bash
/opt/lampp/bin/php test_sqli.php
```
If you experience UI issues, force refresh your browser (Ctrl+F5) to break the CSS cache.
