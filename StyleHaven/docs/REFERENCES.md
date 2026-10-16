# References and Acknowledgements

**Style Haven Salon — Style Sync**
IT 004 — Web Systems and Technologies · Project Part 4 · Group 3
Racasa, Renzo V. · Abelo, Joachim Iñigo M. · Abeson, Enrico Jose F. · Dichosa, Eisen John Laurenz S. · Guariña, Jacob C.
Instructor: Ms. Roxanne A. Pagaduan

We thank and acknowledge every source, library and tool listed below. All third-party
material keeps its original licence and copyright; the salon's own content, design,
code and photographs-in-layout are the work of Group 3.

---

## 1. Front-end libraries and frameworks

| Reference | Used for | Link |
|---|---|---|
| Bootstrap 5.3.3 | Responsive grid, navbar, forms, alerts, modal-free layout | https://getbootstrap.com |
| Bootstrap Icons 1.11.3 | Every icon on the public pages and in the admin module | https://icons.getbootstrap.com |
| jQuery 3.7.1 | Shared page behaviour in `js/main.js` | https://jquery.com |
| Google Fonts — Playfair Display, Poppins | Headings and body typefaces | https://fonts.google.com |

Bootstrap, Bootstrap Icons and jQuery are loaded from the jsDelivr CDN; the fonts are
loaded from the Google Fonts CDN. Both services are used under their own terms.

## 2. Server-side technologies (Part 4)

| Reference | Used for | Link |
|---|---|---|
| PHP 8 manual — PDO and prepared statements | Database access layer in `admin/includes/db.php`; every query in the module is prepared and parameterised | https://www.php.net/manual/en/book.pdo.php |
| PHP manual — `password_hash()` / `password_verify()` | Storing and checking log-in credentials as bcrypt hashes | https://www.php.net/manual/en/function.password-hash.php |
| PHP manual — Sessions | The log-in session and its guards (`require_login()`) | https://www.php.net/manual/en/book.session.php |
| PHP manual — `htmlspecialchars()`, `random_bytes()`, `hash_equals()` | Output escaping, CSRF tokens, constant-time token comparison | https://www.php.net/manual/en/ |
| MySQL 8 / MariaDB reference manual | Table design, ENUM columns, indexes, `ON DELETE RESTRICT` / `SET NULL` foreign keys | https://dev.mysql.com/doc/ |
| phpMyAdmin documentation | Importing `database/stylehaven.sql`, reading the Structure and Indexes tabs for the required screenshot | https://www.phpmyadmin.net/docs/ |
| XAMPP (Apache Friends) | Apache + MySQL + PHP stack used to run the project locally | https://www.apachefriends.org |
| OWASP Cheat Sheet Series — SQL Injection Prevention, Cross-Site Request Forgery Prevention, Password Storage | Basis for the parameterised queries, CSRF tokens and password hashing in this module | https://cheatsheetseries.owasp.org |

## 3. Learning, validation and content references

| Reference | Used for |
|---|---|
| MDN Web Docs (HTML, CSS, JavaScript) — https://developer.mozilla.org | Semantic HTML5 markup, CSS custom properties, form validation attributes, `localStorage` API used by the Part 2 prototype |
| W3Schools — https://www.w3schools.com | Quick syntax reference for HTML tables, CSS flexbox/grid and PHP form handling |
| W3C Nu Html Checker — https://validator.w3.org/nu/ | Every page was validated and returns "The document was successfully checked as HTML5 — no errors or warnings" |
| Unsplash — https://unsplash.com | Photographs used in the hero, gallery, testimonial and team sections (free-to-use licence) |
| OpenStreetMap — https://www.openstreetmap.org | Salon location map embedded on the Contact page |
| YouTube — https://www.youtube.com | Promotional video embedded on the homepage |
| Canva — https://www.canva.com | Drafting the logo and the visual identity of Style Haven Salon (vector redraw exported in `assets/`) |
| Stack Overflow community — https://stackoverflow.com | Troubleshooting discussions on PDO error handling, PHP sessions and MySQL ENUM columns |
| Figma / FigJam — https://www.figma.com | Wireframes and the database entity sketch used to plan the six tables |

## 4. Tools used while developing

| Tool | Purpose |
|---|---|
| Visual Studio Code | Code editor for every HTML, CSS, JavaScript, PHP and SQL file |
| XAMPP control panel + phpMyAdmin | Local Apache/PHP/MySQL environment, database import and structure screenshots |
| Google Chrome DevTools | Responsive checks, console and network inspection |
| Git / GitHub | Version control of the group's submissions (if used by the group) |

## 5. Assistance acknowledgement (disclosure)

In line with academic honesty requirements, we disclose that the Part 4 PHP module
(the database connection layer, the maintenance pages, the log-in module and this
documentation set) was drafted with the help of **Freebuff / Codebuff**, an AI coding
assistant, and then reviewed, tested and adapted by the group. Every design decision,
database field, validation rule and line of output shown during the defence can be
explained by the member responsible for that module, as listed in
`docs/MEMBER-CONTRIBUTIONS.md`. If your instructor requires a different wording or a
signed disclosure, replace this section with their form.

## 6. Original work

The following are the original work of Group 3 and were not copied from any source:

- The Style Haven Salon brand, story, service menu, price list, staff names and demo records.
- The design system in `css/style.css` and the admin layout in `admin/assets/admin.css`.
- The Style Sync booking engine in `js/booking.js` and every PHP page in `admin/`.
- The database design in `database/stylehaven.sql`.
- All written content on the website and in the documentation.

## 7. How to cite this project

> Racasa, R. V., Abelo, J. I. M., Abeson, E. J. F., Dichosa, E. J. L. S., & Guariña, J. C. (2026).
> *Style Haven Salon — Style Sync: A Web-Based Salon Appointment and Scheduling System*
> (Project Part 4). IT 004 Web Systems and Technologies, [School Name].
