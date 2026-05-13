# Hospital Appointment Management System

A role-based (Admin, Doctor, Patient) hospital appointment management application built with PHP and MySQL. Features secure authentication, appointment scheduling, and defenses against common web vulnerabilities.

## System Requirements

- PHP 8.0+
- MySQL 8.0+ or MariaDB 10.4+
- PDO PHP Extension
- XAMPP (or equivalent Apache + MySQL stack)

## Setup Instructions

### 1. Database Setup

Import the schema and seed data in order:

```bash
sudo /opt/lampp/bin/mysql -u root < schema.sql
sudo /opt/lampp/bin/mysql -u root < seed.sql
```

This will:
- Create the `hospital_db` database with all required tables.
- Create a dedicated least-privilege user `hospital_app` (password: `StrongPassword123!`).
- Insert default admin, doctor, and patient accounts.

If your schema was created before the `phone`/`notifications`/`doctor_reviews` columns were added, run the migration instead:

```bash
sudo /opt/lampp/bin/mysql -u root < migrations/2026_05_14_fix_patient_features.sql
```

### 2. Deploy to XAMPP

```bash
sudo rm -rf /opt/lampp/htdocs/biydaalt && sudo cp -r ~/Downloads/sql/biydaalt /opt/lampp/htdocs/
```

### 3. Configuration

`config/db.php` uses hardcoded credentials for XAMPP local development:
- Host: `localhost`
- Database: `hospital_db`
- User: `hospital_app`
- Password: `StrongPassword123!`

No environment variables are required for local setup.

### 4. Access the Application

Start XAMPP and open: `http://localhost/biydaalt/`

Default accounts (password: `password` for all):
- `admin@medicare.mn`
- `doctor@medicare.mn`
- `patient@medicare.mn`

## Architecture & Security Highlights

### 1. SQL Injection Prevention
- **PDO Prepared Statements** used exclusively across all database queries. Mitigates Union-based, Error-based, and Blind injection attacks.
- **Type casting**: Integer IDs are cast with `(int)` before any query logic.
- **Manual testing**: Use Burp Suite to intercept POST requests and test payloads such as `' OR 1=1--`, `' UNION SELECT NULL--`, or `admin'--` against login and search forms.

### 2. Transactional Atomic Operations
- `beginTransaction()` / `commit()` used during appointment booking, cancellation, and rescheduling.
- `FOR UPDATE` row locks prevent TOCTOU races in concurrent booking scenarios.
- `rowCount() === 1` check on the slot UPDATE guarantees exactly one patient books a slot even under concurrent requests.

### 3. Least Privilege Principle
- Application connects via `hospital_app` (SELECT, INSERT, UPDATE, DELETE only — no DROP, CREATE, or FILE).

### 4. CSRF Prevention
- All state-changing forms use `verify_csrf_token()` with per-session tokens.

### 5. Session Security
- 30-minute inactivity timeout.
- `HttpOnly`, `SameSite=Strict` cookie flags.
- Role-based access control via `require_auth($role)`.

### 6. Rate Limiting
- Login attempts limited to 5 per 15 minutes per email + IP via `login_attempts` table.

### 7. Content Security Policy
- `script-src 'self'` blocks inline scripts and event handlers.
- Inline `onclick` and `<script>` blocks are disallowed; all JS is served from `assets/js/`.

## Troubleshooting

- **CSS not updating**: Force-refresh with `Ctrl+F5`.
- **500 error**: Check `/opt/lampp/logs/php_error_log` for details.
- **Database connection error**: Verify XAMPP MySQL is running and credentials in `config/db.php` match.
