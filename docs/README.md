# php-ssl Documentation

This directory contains the full documentation for **php-ssl**, a PHP 8.0+ SSL/TLS certificate monitoring web application.

## Contents

### Getting Started
- [Installation](getting-started/installation.md) — Clone, configure, database setup, web installer
- [Configuration](getting-started/configuration.md) — `config.php` reference and per-tenant overrides
- [Upgrading](getting-started/upgrading.md) — Applying database migrations after a git pull

### Architecture
- [Overview](architecture/overview.md) — Request flow, URL structure, globals, autoloader
- [Multi-Tenancy](architecture/multi-tenancy.md) — Tenant isolation, roles, private zones, impersonation
- [Database Schema](architecture/database-schema.md) — All tables, relationships, and key columns
- [Class Reference](architecture/class-reference.md) — Every class: role, key methods, inheritance

### Operations
- [Crontab Setup](operations/crontab-setup.md) — System crontab, in-app scheduling, manual runs
- [Web Server](operations/apache-nginx.md) — Apache and nginx configuration, BASE path, rewrites
- [Notifications](operations/notifications.md) — How recipient lists are built, delivery matrix, audit log
- [Troubleshooting](operations/troubleshooting.md) — Common problems and solutions

### Coming Soon
- Features — Certificate scanning, CA management, DNS AXFR, agents, testssl.sh, notifications, private zones, WebAuthn, AD sync
- Development — Coding conventions, adding routes, migrations, frontend
