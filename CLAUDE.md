# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**php-ssl** is a PHP 8+ SSL/TLS certificate monitoring web application. It scans predefined hostnames for certificate changes, supports DNS zone transfers (AXFR) to auto-discover hosts, remote scanning agents, and sends email notifications for changes and expirations. Multi-tenant architecture with full tenant isolation.

## Setup

**Configuration:**
```bash
cp config.dist.php config.php
# Edit config.php with DB credentials, mail settings, BASE path
```

**Database:**
```bash
mysql -u root -p -e "CREATE DATABASE \`php-ssl\`; CREATE USER 'phpssladmin'@'localhost' IDENTIFIED BY 'phpssladmin'; GRANT ALL ON \`php-ssl\`.* TO 'phpssladmin'@'localhost';"
mysql -u root -p php-ssl < db/SCHEMA.sql
```

**Git submodules** (Net_DNS2, PHPMailer in `functions/assets/`):
```bash
git submodule update --init --recursive
```

**Cron:**
```
*/5 * * * * /usr/bin/php /var/www/html/php-ssl/cron.php
```

**Run cron script manually:**
```bash
php cron.php <tenant_id> <script_name>
# e.g.: php cron.php 1 update_certificates
```

**PHP extensions required:** `curl`, `gettext`, `openssl`, `pcntl`, `PDO`, `pdo_mysql`, `session`

There is no build system — this is a traditional PHP app deployed directly to a web root.

## Architecture

### Request Flow

```
HTTP Request → index.php
  → functions/autoload.php   (instantiates all classes)
  → Session/user validation
  → route/content.php        (URL routing)
  → route/{feature}/index.php (feature handler)
  → HTML response using Tabler UI
```

Routing is URL-path based. The `$url_items` array in `functions/config.menu.php` defines all valid top-level routes. The `class.URL.php` handles parsing; `route/content.php` dispatches to the correct `route/` subdirectory.

### Key Classes (`functions/classes/`)

| Class | Role |
|-------|------|
| `class.PDO.php` | Database abstraction layer — all DB access goes through this |
| `class.SSL.php` | Core SSL scanning: connects to hosts, retrieves certificates |
| `class.Certificates.php` | Certificate CRUD, change detection logic |
| `class.Zones.php` | DNS zone and host management |
| `class.User.php` | Session-based authentication, role checks |
| `class.Tenants.php` | Multi-tenant isolation |
| `class.AXFR.php` | DNS zone transfer (uses Net_DNS2 submodule) |
| `class.Agent.php` | Remote scanning agent communication |
| `class.Cron.php` | Cronjob orchestration |
| `class.Thread.php` | Multi-process scanning via `pcntl_fork` |
| `class.Mail.php` | Email notifications via PHPMailer submodule |
| `class.Log.php` | Audit logging to the `logs` table |
| `class.Common.php` | Shared utility methods |
| `class.Validate.php` | Input validation |
| `class.Result.php` | JSON response formatting for AJAX endpoints |

### Route Structure (`route/`)

Each top-level feature has a directory matching its name. AJAX endpoints live under `route/ajax/`. Modal dialogs are in `route/modals/` (11 subdirectories). Shared header/menu rendering is in `route/common/`.

### Cron Scripts (`functions/cron/`)

- `update_certificates.php` — scans all hosts for new/changed certs, sends change notifications, uses `pcntl` forking for parallelism
- `axfr_transfer.php` — performs DNS AXFR zone transfers, auto-adds/removes hosts
- `expired_certificates.php` — identifies expiring certs, sends notifications
- `remove_orphaned.php` — cleans up orphaned certificate records

### Multi-Tenancy

All primary tables (`zones`, `hosts`, `certificates`, `users`, `agents`) have a `tenant_id` column. The `$user->tenant_id` on the session is used to scope all queries. Admins (`$user->admin == "1"`) can see the Tenants menu and manage all tenants.

### Database Schema (key tables)

`tenants` → `zones` → `hosts` → `certificates` (hierarchical ownership). `users` are scoped to tenants. `agents` handle remote scanning. `ssl_port_groups` / `_ssl_ports` define which ports to scan per zone.

### Frontend

Tabler 1.4.0 (Bootstrap-based admin UI) + jQuery 3.6.0 + Bootstrap-table 1.26.0. All JS/CSS libraries are bundled locally in `js/` and `css/`. Dark/light theme toggle is built in.

### Configuration (`config.php`)

Key settings beyond DB credentials:
- `$expired_days` — days before expiry to warn (default 20)
- `$expired_after_days` — days post-expiry to still report (default 7)
- `$log_object` — whether to write all object changes to the `logs` table
- `BASE` constant — set if app is not at web root
- `$mail_settings` / `$mail_sender_settings` — SMTP configuration
