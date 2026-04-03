# Voudou Ropes

A membership checklist web app for a skills-based rope course. Built on a LAMP stack.

## Features

- **SMS Magic Link Auth** - Register with phone number and username, log in via SMS link (no passwords)
- **9-Level Checklist** - Track progress with "I've seen it" and "I've done it" checkboxes per skill
- **Level Gating** - Complete all items in a level before the next unlocks
- **Payment Wall** - Level 1 is free; $0.99 one-time payment via Square unlocks levels 2-9
- **Admin Panel** - Manage users, grant/revoke access, promote admins
- **Video Ready** - Schema supports video URLs and thumbnails per checklist item
- **Dark Theme** - Responsive design with crimson accent

## Requirements

- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+
- Apache with mod_rewrite (or any web server)
- [SMSAlert.co.in](https://www.smsalert.co.in/) account for SMS
- [Square Developer](https://developer.squareup.com/) account for payments

## Setup

1. **Clone and configure:**
   ```bash
   git clone git@github.com:EmilGH/voudou-ropes.git
   cd voudou-ropes
   cp voudou-ropes/config.example.php voudou-ropes/config.php
   # Edit config.php with your DB, SMS, and Square credentials
   ```

2. **Create database tables:**
   ```bash
   mysql -u USER -p DATABASE < voudou-ropes/sql/schema.sql
   mysql -u USER -p DATABASE < voudou-ropes/sql/vdr_log.sql
   ```

3. **Seed checklist data:**
   ```bash
   php voudou-ropes/sql/seed.php
   ```

4. **Create your admin account:**
   Register through the app, then promote yourself:
   ```sql
   UPDATE vdr_users SET role = 'admin' WHERE phone = 'YOUR_PHONE';
   ```

5. **Point your web server** document root to the `voudou-ropes/` directory.

## Project Structure

```
voudou-ropes/
  config.php          # Credentials (not committed - copy from config.example.php)
  db.php              # PDO connection
  sms.php             # SMSAlert wrapper + logging
  auth.php            # Session management, magic links, access control
  square.php          # Square Payments API helper
  index.php           # Landing page
  register.php        # Registration (phone + username)
  login.php           # Send magic link
  verify.php          # Magic link verification
  checklist.php       # Main checklist UI
  pay.php             # Square payment form
  pay-confirm.php     # Payment processing
  admin.php           # User management
  logout.php          # Session destroy
  api/
    toggle.php        # AJAX: toggle checkbox state
    progress.php      # AJAX: level completion stats
  assets/
    style.css         # Dark theme
    app.js            # Checkbox & accordion logic
  sql/
    schema.sql        # Core tables (vdr_ prefix)
    vdr_log.sql       # Logging table
    seed.php          # Populate levels/items from JSON
```

## Database Tables

All tables use the `vdr_` prefix for shared database environments.

| Table | Purpose |
|-------|---------|
| `vdr_users` | Accounts with role and payment status |
| `vdr_login_tokens` | Single-use magic link tokens |
| `vdr_levels` | Course levels (1-9) |
| `vdr_items` | Checklist items per level |
| `vdr_user_progress` | Per-user seen/done state per item |
| `vdr_log` | Application event log |
