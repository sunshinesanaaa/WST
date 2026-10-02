# Style Haven Salon — Style Sync

**IT 004 – Web Systems and Technologies · Project Part 2 · Group 3**

A company website for **Style Haven Salon** together with **Style Sync**, its web-based
appointment and scheduling system. The site is built with HTML5, CSS3, JavaScript and
Bootstrap 5.

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
├── index.html            Homepage with the landing page (Web Page 1)
├── services.html         Services and Products — priced menu + retail products (Web Page 2)
├── appointments.html     Book an Appointment — the Style Sync booking page (Web Page 3)
├── login.html            Staff Log-in — authentication module (Web Page 4)
├── about.html            About Us — company background, values, milestones, team
├── contact.html          Contact — information tiles, inquiry form, map, FAQ
├── css/
│   └── style.css         The complete design system and responsive layout
├── js/
│   ├── main.js           Shared behaviour: navigation, scroll reveal, form validation
│   ├── booking.js        Style Sync engine: catalogue, slots, conflict detection, reports
│   └── login.js          Log-in module: credential checking and password toggle
└── assets/
    ├── logo.svg          Company logo — vector source (master file)
    ├── logo.png          Company logo — 1440 × 440 transparent PNG
    ├── logo-mark.svg     Square emblem — vector source
    ├── logo-mark.png     Square emblem — 512 × 512 transparent PNG
    ├── favicon.png       Browser tab icon — 64 × 64 PNG
    └── audio/
        └── welcome-chime.wav   Welcome chime used on the Services page
```

## How to run

1. Keep the folder structure exactly as above (`css`, `js` and `assets` inside the main folder).
2. Open `index.html` in any modern browser (Chrome, Edge or Firefox) by double-clicking it.
3. Use the navigation bar to move between the pages.
4. **Booking:** open *Book Appointment*, pick a service, stylist, date and time, and save.
   Try saving a second appointment for the same stylist, date and time — Style Sync refuses
   it and shows a conflict notice.
5. **Staff log-in:** open *Staff Log-in* and use the prototype credentials
   `admin` / `stylehaven2026` (or `frontdesk` / `salon123`).
6. You can also serve the folder from any web server, e.g. `http://localhost/StyleHaven/index.html`.

## Notes

- Appointment records are stored in the browser's `localStorage` (key `stylesync.appointments.v1`),
  so they persist between visits on the same browser. Use *Clear all records* on the booking page
  to reset the demo data.
- Bootstrap, Bootstrap Icons, jQuery and Google Fonts are loaded from public CDNs, so the browser
  needs an internet connection for the full styling. Photographs are loaded from Unsplash.
- All six pages were validated with the **W3C Nu Html Checker** and return
  *“The document was successfully checked as HTML5 — no errors or warnings to show.”*

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

## References

Bootstrap · Bootstrap Icons · jQuery · Google Fonts · Unsplash · W3C Nu Html Checker ·
MDN Web Docs · W3Schools · OpenStreetMap · YouTube
