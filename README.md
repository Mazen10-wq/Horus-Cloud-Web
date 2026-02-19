# Horus Cloud - Full System

## Project Structure
```
horus_cloud/                    ← Main website (horuscloud.edu.eg)
├── index.html                  ← Homepage
├── login.php                   ← Researcher Login / Register
├── database.sql                ← Full DB schema
│
├── css/
│   ├── style.css               ← Main styles
│   └── auth.css                ← Auth + Dashboard styles
│
├── js/script.js                ← Frontend logic
│
├── php/
│   ├── auth/
│   │   ├── config.php          ← DB + helpers (edit DB credentials here)
│   │   ├── register.php        ← POST: create researcher account
│   │   ├── login.php           ← POST: researcher login
│   │   ├── verify.php          ← GET:  email verification
│   │   └── logout.php          ← Logout (researcher or admin)
│   ├── submit_request.php      ← POST: submit access request
│   └── get_csrf.php            ← GET:  CSRF token
│
├── researcher/
│   └── dashboard.php           ← Researcher portal (after login)
│
└── admin/                      ← Admin Panel (admin.horuscloud.edu.eg)
    ├── login.php               ← Admin login page
    ├── index.php               ← Admin dashboard
    └── php/
        ├── update_request.php  ← POST: approve/reject request
        └── send_message.php    ← POST: message researcher
```

## Setup Instructions

### 1. Database
```bash
mysql -u root -p < database.sql
```

### 2. Configure Credentials
Edit `php/auth/config.php`:
```php
define('DB_USER', 'horus_user');
define('DB_PASS', 'your_strong_password');
define('SITE_URL',  'https://horuscloud.edu.eg');
define('ADMIN_URL', 'https://admin.horuscloud.edu.eg');
```

### 3. Create DB User
```sql
CREATE USER 'horus_user'@'localhost' IDENTIFIED BY 'StrongPass!2024';
GRANT SELECT,INSERT,UPDATE,DELETE ON horus_cloud.* TO 'horus_user'@'localhost';
FLUSH PRIVILEGES;
```

### 4. Set Admin Password
```bash
php -r "echo password_hash('YourNewPassword', PASSWORD_BCRYPT);"
```
Then update in database.sql and re-import, or run:
```sql
UPDATE admin_users SET password='THE_HASH' WHERE username='admin';
```

### 5. Subdomain Setup (cPanel/Apache)
- Point `admin.horuscloud.edu.eg` → `/horus_cloud/admin/` folder
- Point `horuscloud.edu.eg` → `/horus_cloud/` folder

### 6. Session Security (php.ini or .htaccess)
```
php_value session.cookie_httponly 1
php_value session.cookie_secure 1
php_value session.use_strict_mode 1
```

## User Flows

### Researcher
1. Visit site → Click **Sign In**
2. Register with email → Verify email
3. Login → Dashboard → Track requests & resources
4. Submit new requests from homepage form

### Admin
1. Visit `admin.horuscloud.edu.eg/login.php`
2. Login with admin credentials
3. Dashboard → See stats, approve/reject requests, message researchers
