# Specific Contributions of Each Member

**Style Haven Salon — Style Sync**
IT 004 — Web Systems and Technologies · **Project Part 4** · Group 3
Instructor: Ms. Roxanne A. Pagaduan

> **Note on the template.** The workspace did not contain a copy of the contribution
> template handed out in class, so the standard four-column format below is used
> (Member · Role · Specific contributions · Files/evidence). If your instructor's
> template has different columns, copy the values across — the content is the same.
> The Part 4 table is a **draft to be confirmed by the group**; edit any line so it
> matches what each member actually did before submitting.

---

## 1. The template (blank — duplicate this table per part)

| Member (Surname, First Name M.I.) | Role in the group | **Specific contributions** | Files, pages or evidence produced |
|---|---|---|---|
|  |  |  |  |
|  |  |  |  |
|  |  |  |  |
|  |  |  |  |
|  |  |  |  |

---

## 2. Part 4 — server-side scripting, maintenance and the working log-in

| Member | Role | **Specific contributions for Part 4** | Files / evidence |
|---|---|---|---|
| **Racasa, Renzo V.** | Group leader · systems integrator | Designed the MySQL database (six tables, keys, foreign-key rules) and wrote the import script with the demo records. Built the database connection layer and the shared CRUD helpers (`db_all`, `db_insert`, `db_update`, `db_delete`) used by every page. Implemented the **dashboard**, the **maintenance map** table and the **activity-log** viewer. Coordinated the file structure, ran the SQL import in phpMyAdmin and captured the phpMyAdmin structure screenshots. | `database/stylehaven.sql`, `admin/includes/config.php`, `admin/includes/db.php`, `admin/index.php`, `admin/activity-log.php`, `docs/phpmyadmin-structures.html` |
| **Abelo, Joachim Iñigo M.** | Booking module owner | Wrote **Appointment Maintenance** — add, edit and delete of appointment records, the joined record list, the status-change action and the server-side **double-booking check** that mirrors the rule already used by `js/booking.js`. Wrote the SQL conflict query and the reference-number generator (`SH-0001`). | `admin/appointments.php`, conflict and reference functions in `admin/includes/models.php` |
| **Abeson, Enrico Jose F.** | Authentication owner | Developed the **working log-in page** (User Maintenance): session start with hardened cookie flags, `password_verify()` credential checking, inactive-account refusal, idle log-out, the log-out page and the **change-password** page. Generated the bcrypt hashes used by the seed accounts. | `admin/login.php`, `admin/logout.php`, `admin/change-password.php`, `admin/includes/auth.php` |
| **Dichosa, Eisen John Laurenz S.** | Catalogue owner | Wrote the **File Maintenance** pages for the service menu and the retail product line: validation rules, duplicate-code checks, the price/duration/stock fields, the search-and-filter toolbar and the delete guard that stops a service still used by a booking from being removed. | `admin/services.php`, `admin/products.php`, service and product blocks in `admin/includes/models.php` |
| **Guariña, Jacob C.** | Records and reporting owner | Wrote **User Maintenance** (search, add, edit, delete of accounts, role and status control, protection of the last active administrator) and **Stylist Maintenance**. Built the audit-trail writer `log_activity()` and the dashboard statistics (status counts, booked value). Prepared the reference list. | `admin/users.php`, `admin/stylists.php`, `admin/includes/functions.php` (`log_activity`, formatting helpers), `docs/REFERENCES.md` |

**Shared contributions (all members).**

- Review of the Part 4 requirements and the database field list.
- Import of `database/stylehaven.sql` into phpMyAdmin and verification of the demo records.
- Manual testing: add → edit → delete on every maintenance page, wrong-password rejection,
  duplicate-code rejection and the double-booking refusal.
- Screenshots for the documentation (phpMyAdmin structures, each maintenance page, log-in page).

---

## 3. Module ownership carried over from Part 2 (for reference)

| Part 2 module | Owner |
|---|---|
| 1. Landing page and homepage | Racasa, Renzo V. |
| 2. Navigation, footer and site-wide layout | Racasa, Renzo V. |
| 3. Appointment booking and scheduling (core of Style Sync) | Abelo, Joachim Iñigo M. |
| 4. Authentication (log-in) | Abeson, Enrico Jose F. |
| 5. Services and products catalogue | Dichosa, Eisen John Laurenz S. |
| 6. Stylist and staff management | Guariña, Jacob C. |
| 7. Reports and dashboard | Guariña, Jacob C. |
| 8. Contact, map and inquiry | Abeson, Enrico Jose F. |

The Part 4 page owners above were assigned so that each member works again on the same
module they owned in Part 2 — the person who built the booking form also wrote the
appointment maintenance page, and so on.

---

## 4. Summary of what Part 4 added to the project

| Requirement | Where it is delivered |
|---|---|
| A server-side script that manages website contents/records | PHP 8 + MySQL through PDO, `admin/` folder |
| **User Maintenance** with add, edit and delete | `admin/users.php` (accounts, roles, status, passwords) |
| **File Maintenance** with add, edit and delete (at least one page) | `admin/services.php`, `admin/products.php`, `admin/stylists.php` |
| **Working log-in page (User Maintenance)** | `admin/login.php` + `admin/includes/auth.php` (session-guarded pages) |
| Screenshot of phpMyAdmin structures | Import `database/stylehaven.sql`, then screenshot the Structure tab — see `docs/phpmyadmin-structures.html` for the exact columns to expect |
| Specific contributions of each member | Tables 1–3 of this document |
| Acknowledgement of all references | `docs/REFERENCES.md` |

---

## 5. Group sign-off

| Member | Signature | Date |
|---|---|---|
| Racasa, Renzo V. | | |
| Abelo, Joachim Iñigo M. | | |
| Abeson, Enrico Jose F. | | |
| Dichosa, Eisen John Laurenz S. | | |
| Guariña, Jacob C. | | |
