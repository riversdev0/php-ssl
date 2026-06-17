# Email Notifications

php-ssl sends email notifications from two cron scripts:

- **`update_certificates.php`** — fired when a certificate changes (new cert detected on a host)
- **`expired_certificates.php`** — fired when certificates are expiring or have already expired

Both scripts build the same recipient model and send mail through the shared `mailer` class backed by PHPMailer.

---

## Recipient Model

For each tenant, every notification run collects a set of email addresses. The final delivery depends on whether a changed certificate belongs to a public zone or a private zone.

### 1. Tenant recipients (public zones only)

The tenant record has a **Recipients** field (`tenants.recipients`). This is a free-form, semicolon-separated list of email addresses. These are not necessarily app users — they can be any address (a team alias, a ticketing system, etc.).

> **Example:** `ops@example.com; monitoring@example.com`

All valid addresses from this field are collected into `$email_to_tenant_recipents`. A single email is sent to the whole list as multiple `To:` recipients, so all tenant recipients receive the same combined message.

### 2. Per-host / per-certificate recipients (public zones only)

Each host row has an `h_recipients` column. This is also a semicolon-separated list of email addresses. Certificates being monitored by `expired_certificates.php` use the same column (hosts share a certificate by FK, so the host's `h_recipients` drives which individual addresses get notified).

Each address found here that is **not already a tenant recipient** receives its own individual email (one `To:` per address). The message body contains only the entries relevant to that address.

### 3. Private zone creator (private zones only)

When a zone is marked private (`zones.private_zone_uid IS NOT NULL`), the zone creator's email address is used **instead of** tenant recipients and per-host recipients. The creator is looked up directly from the `users` table by their `id`.

Private zone notifications are never sent to tenant recipients, even as BCC. This ensures other users cannot infer the existence of private zones from notification traffic.

### 4. Global BCC (all sends)

`config.php` has a `$mail_sender_settings->bcc` setting. When non-empty, this address is silently added as BCC to **every** outgoing email — both the tenant-wide send and every individual per-host send. This is useful for a central audit address.

---

## Delivery Matrix

| Certificate belongs to | Recipient(s) | BCC |
|---|---|---|
| Public zone | Tenant recipients (combined) | Global BCC (if set) |
| Public zone | Per-host addresses (individual sends) | Tenant recipients + Global BCC |
| Private zone | Zone creator only (individual send) | Global BCC only — **no** tenant recipients |

---

## Where to Configure Each Setting

| Setting | Where |
|---|---|
| Tenant recipients | Tenants → edit tenant → **Recipients** field |
| Per-host recipients | Zones → expand zone → host row → **Recipients** column |
| Global BCC | `config.php` → `$mail_sender_settings->bcc` |
| SMTP server / auth | `config.php` → `$mail_settings` block |
| Sender name / address | `config.php` → `$mail_sender_settings->mail_from` / `mail_addr` |

---

## Email Format

Each notification email is self-contained HTML. The format (table vs. list layout) is controlled by the **Mail style** setting on the tenant (`tenants.mail_style`):

- **`table`** (default) — one row per host/certificate in an HTML table
- **`list`** — one block per host/certificate with labelled fields; used when `mail_style = "list"`

Tenant recipients always receive the format determined by the tenant's `mail_style` setting. Per-host recipients and private zone creators always receive the list format.

---

## Notification Triggers

### Certificate change notifications (`update_certificates.php`)

A notification is sent when a host's `last_change` timestamp matches the current cron execution time — meaning the SSL scan detected a new or different certificate on that host since the last scan. Muted hosts (`hosts.mute = 1`) are excluded.

Certificates whose issuer is in the tenant's ignored issuers list are silently skipped (no email, no log entry).

### Certificate expiry notifications (`expired_certificates.php`)

A notification is sent for any certificate where:

- The expiry date is within `$expired_days` days from now (configurable in `config.php`, default 20), **or**
- The certificate has already expired but less than `$expired_after_days` days ago (default 7)

Certificates whose issuer is marked as ignored for expiry in the tenant's ignored issuers list are skipped.

The expiry email groups certificates into two sections: **Expired** and **Certificates that will expire soon**.

---

## Audit Log

Every send attempt is recorded in the `logs` table. The log message distinguishes:

- `"Certificate change notification email sent to tenant recipients"` — tenant-wide send succeeded
- `"Certificate change notification email FAILED for tenant recipients"` — send attempted but PHPMailer reported an error
- `"Certificate change notification email sent to user {name} ({email})"` — individual per-host or private-zone send succeeded
- `"Certificate change notification email FAILED for user {name} ({email})"` — individual send failed

No log entry is written if there are no tenant recipients configured and no per-host/private-zone addresses to notify.

---

## Troubleshooting

**Connection appears in relay logs but no mail is delivered**  
PHPMailer must be initialized with exception mode enabled (`new PHPMailer(true)`). Without it, SMTP failures (e.g. RCPT rejection) return `false` silently and the code assumes success. Check `stderr` output from the cron run for `Mailer Error:` lines.

**Recipients field is empty / no sends attempted**  
Check `tenants.recipients` in the database or via the tenant edit form. The field is parsed by splitting on `;` and `,`, then each token is validated as a syntactically valid email address. Invalid or blank entries are silently dropped.

**Private zone owner not notified**  
Verify `zones.private_zone_uid` matches a valid `users.id` and that the user record has a valid `email`. Also confirm the cron is not running under an impersonation session — impersonation blocks private zone access entirely.
