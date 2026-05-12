# SECURITY_TESTS.md

## SQL Injection Tests

### Login Bypass Test 1
- **Input (username/email):** `administrator'--`
- **Input (password):** `anything`
- **Expected Result:** Login must fail.
- **Actual Result:** Login fails. PDO Prepared statement treats input as literal string.

### Login Bypass Test 2
- **Input (username/email):** `' OR 1=1--`
- **Input (password):** `anything`
- **Expected Result:** Login must fail.
- **Actual Result:** Login fails safely.

### Search / Filter / ID Parameter Test
- **Input (id):** `1 OR 1=1`
- **Expected Result:** Input rejected because ID must be integer.
- **Actual Result:** Caught and validated properly via typecasting / prepared statements.

### UNION-based SQL Injection Test
- **Input:** `' UNION SELECT NULL,NULL--`
- **Expected Result:** Treated as normal text or rejected, never executed as SQL.
- **Actual Result:** Treated as literal text by PDO parameters.

### Database Listing Search (information_schema)
- **Input:** `' UNION SELECT table_name,NULL FROM information_schema.tables--`
- **Expected Result:** No database table names are returned.
- **Actual Result:** Prevented by prepared statements.

## CSRF Tests
- **Test:** Submit forms without/invalid CSRF token.
- **Expected Result:** System returns "Хүсэлт хүчингүй боллоо. Хуудсаа дахин ачааллана уу."
- **Actual Result:** Pass

## Rate Limiting & Bruteforce
- **Test:** 5 invalid logins.
- **Expected Result:** Lockout. "Хэт олон удаа оролдлоо. X минутын дараа дахин оролдоно уу."
- **Actual Result:** Pass

