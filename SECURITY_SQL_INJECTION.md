# SQL Injection Security Implementation

## What is SQL Injection?
SQL Injection (SQLi) is a web security vulnerability that allows an attacker to interfere with the queries that an application makes to its database. This can allow an attacker to view data they are not normally able to retrieve, such as data belonging to other users, hidden data, or even database internal schema.

## The 4 Handled Attack Scenarios

### 1. SQL Injection in WHERE Clause (Hidden Data Retrieval)
**Attack Concept:** By appending `' OR 1=1--` to a URL parameter (like `?category=Gifts' OR 1=1--`), an attacker attempts to break out of the string literal constraint and append an always-true condition (`1=1`), dropping the rest of the query (like `AND released = 1`) via the SQL comment `--`.
**Why this project is safe:** We use **PDO Prepared Statements**. Instead of directly merging strings, PDO sends the payload explicitly as a string literal parameter. The database reads `' OR 1=1--` strictly as the category name itself (i.e. finding a category literally named "Gifts' OR 1=1--"), meaning the `WHERE released = 1` condition is strictly preserved.

### 2. Login Bypass
**Attack Concept:** Inputting `admin'--` as the username to drop the password checking routine from the query statement.
**Why this project is safe:** `login.php` utilizes PDO prepared statements for lookup (`WHERE email = :email LIMIT 1`), and it pulls the database record *first*, and then uses PHP's secure `password_verify()` API against the hashed password. Merely matching an email is impossible via injection, thus bypassing the password check is impossible.

### 3 & 4. UNION Attacks (Determining Columns & Extracting DB Schema)
**Attack Concept:** Using `' UNION SELECT NULL, NULL--` or `' UNION SELECT table_name, NULL FROM information_schema.tables--` to extract data or database structures by tacking additional `SELECT` statements onto the existing query.
**Why this project is safe:** Again, because of prepared statements, the input `' UNION SELECT NULL...` is processed exclusively as a literal string for the `category` column filtering, not as a SQL command wrapper. It simply yields "No products found." Additionally, error logging genericizes exceptions, preventing feedback-based exploitation.

## Defense Implementation Breakdown

1. **Prepared Statements:** The most vital defense mechanism. Used in `products.php`, `login.php`, `register.php`, and `admin/dashboard.php`.
2. **Validation:** `config/security.php` provides strict type casting and length/validation limits.
3. **Generic Error Handling:** Catch blocks log standard errors quietly using `error_log()` and display generic "Server Error" UI strings, shutting down Error-Based SQLi.
4. **Least Privilege (Recommended):** The database connection config should be tied to a standard application user `hospital_user` within MySQL that lacks `CREATE`, `DROP`, `ALTER`, or `SUPER` privileges.

## Safe Code vs Unsafe Code
**Unsafe:**
```php
$sql = "SELECT * FROM products WHERE category = '" . $_GET['category'] . "' AND released = 1";
$conn->query($sql);
```

**Safe:**
```php
$stmt = $conn->prepare("SELECT * FROM products WHERE category = :cat AND released = 1");
$stmt->execute([':cat' => $_GET['category']]);
```

## How to Test
1. Make sure XAMPP is running.
2. Run database setup: `mysql -u root < database.sql`
3. Hit `test_sqli.php` via CLI: `php test_sqli.php` or open it in a browser to view real-time payload rejection validations.