# Style Haven Salon — Style Sync

**IT 004 – Web Systems and Technologies · Group 3**
*A company website with a server-side record management module.*

A company website for **Style Haven Salon** together with **Style Sync**, its web-based
appointment and scheduling system. The public site is built with HTML5, CSS3, JavaScript
and Bootstrap 5; the maintenance module required by **Project Part 4** is built with
**PHP 8 + MySQL** and managed through the admin pages in `admin/`.

---

## Company

| | |
|---|---|
| Company | Style Haven Salon |
| System / Project | Style Sync — A Web-Based Salon Appointment and Scheduling System |
| Tagline | “Beauty, booked in sync.” |
| Address | 24 Aurora Boulevard, Cubao, Quezon City |
| Contact | (02) 8123 4567 · hello@stylehavensalon.ph |
| Team | Racasa, Renzo V. (Leader) · Abelo, Joachim Iñigo M. · Abeson, Enrico Jose F. · Dichosa, Eisen John Laurenz S. · Guariña, Jacob C. |
| Instructor | Ms. Roxanne A. Pagaduan |

---

## Files and folders

```
StyleHaven/
├── index.html            Homepage with the landing page
├── services.html         Services and Products — priced menu + retail products
├── appointments.html     Book an Appointment — the Style Sync booking page
├── login.html            Client-side log-in prototype (Part 2)
├── about.html            About Us — company background, values, milestones, team
├── contact.html          Contact — information tiles, inquiry form, map, FAQ
├── css/
│   └── style.css         The complete design system and responsive layout
├── js/
│   ├── main.js           Shared behaviour: navigation, scroll reveal, form validation
│   ├── booking.js        Style Sync engine: catalogue, slots, conflict detection, reports
│   └── login.js          Log-in prototype: credential checking and password toggle
├── assets/
│   ├── logo.svg          Company logo — vector source (master file)
│   ├── logo.png          Company logo — 1440 × 440 transparent PNG
│   ├── logo-mark.svg     Square emblem — vector source
│   ├── logo-mark.png     Square emblem — 512 × 512 transparent PNG
│   ├── favicon.png       Browser tab icon — 64 × 64 PNG
│   └── audio/
│       └── welcome-chime.wav   Welcome chime used on the Services page
│
├── database/
│   └── stylehaven.sql    ★ Part 4 — the whole MySQL database + demo records
├── admin/                ★ Part 4 — the PHP maintenance module
│   ├── login.php         WORKING LOG-IN PAGE (user maintenance / authentication)
│   ├── logout.php        Ends the session
│   ├── change-password.php  Any signed-in user can rotate their own password
│   ├── index.php         Dashboard: record counts, newest bookings, audit trail
│   ├── users.php         USER MAINTENANCE       — add / edit / delete accounts
│   ├── services.php      FILE MAINTENANCE       — add / edit / delete services
│   ├── products.php      FILE MAINTENANCE       — add / edit / delete retail products
│   ├── stylists.php      FILE MAINTENANCE       — add / edit / delete stylists
│   ├── appointments.php  RECORD MAINTENANCE     — add / edit / delete bookings
│   ├── activity-log.php  Audit trail of every add, edit and delete
│   ├── assets/
│   │   ├── admin.css     Control-panel layout (sidebar + main area)
│   │   └── admin.js      Delete confirmations and small UI helpers
│   └── includes/
│       ├── config.php    Database credentials and site constants
│       ├── db.php        PDO connection + prepared-statement helpers
│       ├── functions.php Escaping, CSRF, flash messages, formatting, audit log
│       ├── auth.php      Session start, log-in verification and page guards
│       ├── models.php    Every SQL statement used by the module
│       ├── header.php    Shared admin layout (top bar, navbar, sidebar)
│       └── footer.php    Shared admin footer + scripts
├── docs/
│   ├── phpmyadmin-structures.html  The six table structures, ready to screenshot
│   ├── MEMBER-CONTRIBUTIONS.md     Specific contributions of each member
│   └── REFERENCES.md               Acknowledgement of all references
└── tests/
    ├── selftest.php             Developer self-check (67 checks) — see below
    └── build-structures.php     Regenerates docs/phpmyadmin-structures.html
```

---

## How to run

### A. The public website (Parts 1–3)

1. Keep the folder structure exactly as above (`css`, `js` and `assets` inside the main folder).
2. Open `index.html` in any modern browser (Chrome, Edge or Firefox) by double-clicking it.
3. Use the navigation bar to move between the pages.
4. **Booking:** open *Book Appointment*, pick a service, stylist, date and time, and save.
   Try saving a second appointment for the same stylist, date and time — Style Sync refuses
   it and shows a conflict notice.
5. **Prototype log-in:** open *Log In* (`login.html`) and use `admin` / `stylehaven2026`,
   `frontdesk` / `salon123` or `mariel` / `customer123`. The **Sign In** button in the navigation bar
   opens the working PHP log-in instead (see part B below).

### B. The Part 4 maintenance module (PHP + MySQL)

The PHP pages need a web server, so they **cannot** be opened by double-clicking a file.

1. Install **XAMPP** and start **Apache** and **MySQL** in the XAMPP control panel.
2. Copy the `StyleHaven` folder into `C:\xampp\htdocs\`.
3. Open <http://localhost/phpmyadmin>, click **Import**, choose `database/stylehaven.sql`
   and press **Import**. This creates the `stylehaven_db` database with its six tables and
   the demo records.
4. Open <http://localhost/StyleHaven/admin/login.php> and sign in:

   | Username | Password | Role |
   |---|---|---|
   | `admin` | `stylehaven2026` | Administrator (full access, including User Maintenance) |
   | `frontdesk` | `salon123` | Front Desk |
   | `ana.stylist` | `stylist123` | Stylist |
   | `mariel` | `customer123` | Customer |

5. If your MySQL root account has a password, put it in `admin/includes/config.php`
   (`DB_USER` / `DB_PASS`).

> **Screenshot of the phpMyAdmin structures.** After step 3, click the `stylehaven_db`
> database, open each table's **Structure** tab and screenshot it. `docs/phpmyadmin-structures.html`
> lists exactly the columns, indexes and relations you should see (it is generated from the
> same SQL file by `tests/build-structures.php`), so it can also be printed or screenshotted
> as a backup if MySQL is unavailable when the documentation is written.

---

## Part 4 requirements → where they are delivered

| Requirement | Delivered by |
|---|---|
| Server-side script that manages website contents or records | PHP 8 pages in `admin/` reading and writing MySQL through PDO |
| **User Maintenance** — manage user accounts (add · edit · delete) | `admin/users.php` — username, full name, e-mail, contact, role, status and password |
| **File Maintenance** — at least one page with add · edit · delete | `admin/services.php` (service menu), `admin/products.php` (retail products) and `admin/stylists.php` (staff file) |
| **Working log-in page (User Maintenance)** | `admin/login.php` + `admin/includes/auth.php`: credentials checked against `users` with `password_verify()`, PHP session opened, every page guarded by `require_login()` |
| Screenshot of the phpMyAdmin structures | `database/stylehaven.sql` + `docs/phpmyadmin-structures.html` |
| Specific contributions of each member | `docs/MEMBER-CONTRIBUTIONS.md` |
| Acknowledgement of all references | `docs/REFERENCES.md` |

### Extra functions built into the module

- **Dashboard** (`admin/index.php`) — record counts, appointment status breakdown, booked
  value, newest bookings and the latest audit entries.
- **Appointment records** (`admin/appointments.php`) — the records themselves can be added,
  edited, deleted and moved through *Pending → Confirmed → Completed → Cancelled*.
- **Double-booking prevention on the server** — the same rule the JavaScript booking page
  uses is re-checked in SQL before a record is written, so the two views never disagree.
- **Referential integrity** — a service or a stylist that still has booking records cannot be
  deleted (`ON DELETE RESTRICT`); the page explains this and suggests *Unavailable* /
  *Inactive* instead.
- **Audit trail** (`admin/activity-log.php`) — every add, edit, delete and log-in is written
  to `activity_log` with the account, table and a short description.
- **Protection of the last administrator** — the only active Administrator account cannot be
  deleted, demoted or switched off.
- **Security** — prepared statements everywhere, `htmlspecialchars()` on all output, a CSRF
  token on every form, bcrypt password hashes, session-id regeneration on log-in and an
  idle log-out for the shared front-desk terminal.

---

## Verifying the module yourself

```bash
# 1. Every PHP file must be free of syntax errors
php -l admin/login.php      # repeat for the other files

# 2. Run the developer self-check (67 checks: CRUD, constraints, conflict rule, helpers)
php tests/selftest.php

# 3. Regenerate the phpMyAdmin structure reference from the SQL file
php tests/build-structures.php
```

`tests/selftest.php` reads `database/stylehaven.sql`, mirrors every `CREATE TABLE` into an
in-memory SQLite database (columns, `NOT NULL`, `DEFAULT`, `CHECK` for the ENUM values and
the real foreign keys), then performs the same add / edit / delete operations the pages use
and reports pass or fail. It never touches the real MySQL database.

---

## Notes

- The public pages use the browser's `localStorage` (key `stylesync.appointments.v1`) so that
  the Part 2 prototype works without a server. The Part 4 admin module replaces that storage
  with the MySQL `appointments` table.
- Bootstrap, Bootstrap Icons, jQuery and Google Fonts are loaded from public CDNs, so the
  browser needs an internet connection for the full styling. Photographs are loaded from Unsplash.
- All six public pages were validated with the **W3C Nu Html Checker** and return
  *“The document was successfully checked as HTML5 — no errors or warnings to show.”*
- The Part 4 pages reuse `css/style.css`, so the admin module keeps the salon's look and feel.

## Modules and owners

| Module | Owner |
|---|---|
| 1. Landing page and homepage | Racasa, Renzo V. |
| 2. Navigation, footer and site-wide layout | Racasa, Renzo V. |
| 3. Appointment booking and scheduling (core of Style Sync) | Abelo, Joachim Iñigo M. |
| 4. Authentication (log-in) | Abeson, Enrico Jose F. |
| 5. Services and products catalogue | Dichosa, Eisen John Laurenz S. |
| 6. Stylist and staff management | Guariña, Jacob C. |
| 7. Reports and dashboard | Guariña, Jacob C. |
| 8. Contact, map and inquiry | Abeson, Enrico Jose F. |

Part 4 page ownership follows the same split — see `docs/MEMBER-CONTRIBUTIONS.md`.

## References

Bootstrap · Bootstrap Icons · jQuery · Google Fonts · Unsplash · W3C Nu Html Checker ·
MDN Web Docs · W3Schools · OpenStreetMap · YouTube · PHP manual · MySQL reference manual ·
phpMyAdmin documentation · XAMPP · OWASP Cheat Sheet Series.
Full details, links and the assistance disclosure are in [docs/REFERENCES.md](docs/REFERENCES.md).
