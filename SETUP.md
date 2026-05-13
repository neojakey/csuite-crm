# Setup Guide

## Prerequisites

- PHP 8.x with PDO, PDO_MySQL, and curl extensions enabled
- MySQL 8.x
- An Anthropic API key — [console.anthropic.com](https://console.anthropic.com)
- A local dev environment (Laragon, XAMPP, MAMP) or a VPS

---

## Step 1 — Create the database

In HeidiSQL, phpMyAdmin, or the MySQL CLI:

```sql
CREATE DATABASE csuite_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

---

## Step 2 — Run the schema

```bash
mysql -u root -p csuite_crm < sql/schema.sql
```

Or paste the contents of `sql/schema.sql` into HeidiSQL / phpMyAdmin.

---

## Step 3 — Configure environment

```bash
cp .env.example .env
```

Edit `.env`:

```ini
ANTHROPIC_API_KEY=sk-ant-your-key-here
DB_HOST=127.0.0.1
DB_NAME=csuite_crm
DB_USER=root
DB_PASS=your-mysql-password
```

---

## Step 4 — Configure company context

```bash
cp config/company.example.php config/company.php
```

Edit `config/company.php` and fill in your business details. This context is injected into every AI agent prompt. Do not include personal data (customer names, emails, phone numbers).

---

## Step 5 — Configure authentication

```bash
cp config/auth.example.php config/auth.php
```

Generate a bcrypt hash for your chosen password:

```bash
php -r "echo password_hash('your-chosen-password', PASSWORD_BCRYPT, ['cost' => 12]);"
```

Paste the output hash into `config/auth.php`:

```php
return [
    'password_hash' => '$2y$12$...',
];
```

---

## Step 6 — Build CSS

Download the Tailwind CSS standalone binary for your platform from [github.com/tailwindlabs/tailwindcss/releases](https://github.com/tailwindlabs/tailwindcss/releases).

Place it in the project root and run:

```bash
# macOS / Linux
chmod +x tailwindcss
./tailwindcss -i assets/css/input.css -o assets/css/output.css --minify

# Windows
tailwindcss.exe -i assets/css/input.css -o assets/css/output.css --minify
```

---

## Step 7 — Web server setup

**Laragon (Windows)**

1. In Laragon, click Menu → Apache → sites-enabled → Add
2. Create a vhost pointing to the project root, e.g. `csuite-crm.test`
3. Restart Apache

**XAMPP / MAMP**

Place the project in `htdocs` (XAMPP) or `htdocs` (MAMP) and access via `http://localhost/csuite-crm/`.

If using a subdirectory, update `BASE_URL` in `config/app.php`:

```php
define( 'BASE_URL', '/csuite-crm/' );
```

**VPS / shared hosting**

Upload the project to `public_html` or a subdirectory. Ensure `.htaccess` or your server config serves `index.php` as the entry point.

---

## Step 8 — First login

Visit your URL (e.g. `http://csuite-crm.test`) and log in with the password you set in Step 5.

Go to **Settings** to verify your API connection.

---

## Troubleshooting

**Blank white page**

- Check the PHP error log. On Laragon: `C:\laragon\logs\apache`
- Enable display_errors temporarily: add `ini_set('display_errors', 1);` at the top of `index.php`

**API connection fails**

- Verify `ANTHROPIC_API_KEY` in `.env` — it should start with `sk-ant-`
- Check that PHP's curl extension is enabled (`phpinfo()`)
- If behind a corporate proxy, configure curl to use it

**CSS not loading**

- Run the Tailwind build command (Step 6)
- Check the path: `assets/css/output.css` must exist
- Verify `BASE_URL` in `config/app.php` matches your install path

**Login not working**

- Regenerate the bcrypt hash: `php -r "echo password_hash('yourpassword', PASSWORD_BCRYPT, ['cost' => 12]);"`
- Paste the new hash into `config/auth.php`
- Ensure `config/auth.php` returns the hash correctly: `return ['password_hash' => '$2y$12$...'];`

**Database connection error**

- Verify DB credentials in `.env`
- Check that MySQL is running
- Try `DB_HOST=127.0.0.1` instead of `localhost` (some setups require the IP)
