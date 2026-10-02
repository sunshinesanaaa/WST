/* =====================================================================
   Style Sync — appointment & scheduling engine (booking.js)
   Style Haven Salon | IT 004 Web Systems and Technologies | Group 3
   ---------------------------------------------------------------------
   Storage  : browser localStorage (no server required for the prototype)
   Features : service catalog, stylist selection, operating-hour slots,
              double-booking prevention, status tracking, simple reports.
   ===================================================================== */

(function (window, document) {
  "use strict";

  var STORAGE_KEY = "stylesync.appointments.v1";
  var OPEN_HOUR = 9;    // salon opens 9:00 AM
  var CLOSE_HOUR = 19;  // salon closes 7:00 PM
  var STEP_MIN = 30;    // 30-minute booking intervals

  /* ------------------------------------------------------------ catalog */
  var SERVICES = [
    { id: "haircut",   name: "Signature Haircut",   cat: "Hair",     price: 350,  mins: 45 },
    { id: "blowdry",   name: "Blow-dry & Styling",  cat: "Hair",     price: 250,  mins: 30 },
    { id: "color",     name: "Hair Color",          cat: "Hair",     price: 1500, mins: 120 },
    { id: "keratin",   name: "Keratin Treatment",   cat: "Hair",     price: 2500, mins: 180 },
    { id: "hairspa",   name: "Hair Spa",            cat: "Hair",     price: 800,  mins: 60 },
    { id: "manicure",  name: "Classic Manicure",    cat: "Nails",    price: 200,  mins: 40 },
    { id: "pedicure",  name: "Classic Pedicure",    cat: "Nails",    price: 250,  mins: 50 },
    { id: "gel",       name: "Gel Polish",          cat: "Nails",    price: 500,  mins: 60 },
    { id: "nailart",   name: "Nail Art",            cat: "Nails",    price: 600,  mins: 75 },
    { id: "facial",    name: "Classic Facial",      cat: "Skin",     price: 700,  mins: 60 },
    { id: "diamond",   name: "Diamond Peel",        cat: "Skin",     price: 900,  mins: 60 },
    { id: "threading", name: "Eyebrow Threading",   cat: "Skin",     price: 150,  mins: 20 },
    { id: "party",     name: "Party Makeup",        cat: "Makeup",   price: 800,  mins: 60 },
    { id: "bridal",    name: "Bridal Makeup",       cat: "Makeup",   price: 3500, mins: 150 },
    { id: "bridalpkg", name: "Bridal Package",      cat: "Packages", price: 5000, mins: 240 },
    { id: "pamper",    name: "Pamper Package",      cat: "Packages", price: 2000, mins: 180 }
  ];

  var STYLISTS = [
    { id: "ana",   name: "Ana Villanueva",  role: "Senior Hair Stylist" },
    { id: "ben",   name: "Ben Cruz",        role: "Barber & Grooming" },
    { id: "clara", name: "Clara Domingo",   role: "Nail Technician" },
    { id: "dana",  name: "Dana Reyes",      role: "Skin & Facial Therapist" },
    { id: "elle",  name: "Elle Santos",     role: "Makeup Artist" }
  ];

  /* ------------------------------------------------------------ helpers */
  function serviceById(id) {
    return SERVICES.filter(function (s) { return s.id === id; })[0] || null;
  }
  function stylistById(id) {
    return STYLISTS.filter(function (s) { return s.id === id; })[0] || null;
  }
  function peso(n) {
    return "\u20B1" + Number(n).toLocaleString("en-PH", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }
  function to12h(t) {
    var p = t.split(":");
    var h = parseInt(p[0], 10);
    var m = p[1];
    var ap = h >= 12 ? "PM" : "AM";
    var hh = h % 12 === 0 ? 12 : h % 12;
    return hh + ":" + m + " " + ap;
  }
  function displayDate(iso) {
    var d = new Date(iso + "T00:00:00");
    return d.toLocaleDateString("en-PH", { weekday: "short", year: "numeric", month: "short", day: "numeric" });
  }
  function slots() {
    var out = [];
    for (var h = OPEN_HOUR; h < CLOSE_HOUR; h++) {
      for (var m = 0; m < 60; m += STEP_MIN) {
        out.push((h < 10 ? "0" + h : h) + ":" + (m < 10 ? "0" + m : m));
      }
    }
    return out;
  }

  /* --------------------------------------------------------- repository */
  function load() {
    try {
      var raw = window.localStorage.getItem(STORAGE_KEY);
      return raw ? JSON.parse(raw) : [];
    } catch (e) {
      return [];
    }
  }
  function save(list) {
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(list));
  }
  function all() {
    return load().sort(function (a, b) {
      return (a.date + a.time).localeCompare(b.date + b.time);
    });
  }
  function nextRef() {
    var list = load();
    var seq = list.length + 1;
    return "SH-" + String(seq).padStart(4, "0");
  }

  /** Prevents the same stylist being booked twice on one date and time. */
  function hasConflict(stylistId, date, time, ignoreId) {
    return load().some(function (a) {
      return a.id !== ignoreId &&
        a.stylist === stylistId &&
        a.date === date &&
        a.time === time &&
        a.status !== "Cancelled";
    });
  }

  function add(data) {
    var list = load();
    var record = {
      id: nextRef(),
      customer: (data.customer || "").trim(),
      contact: (data.contact || "").trim(),
      email: (data.email || "").trim(),
      service: data.service,
      stylist: data.stylist,
      date: data.date,
      time: data.time,
      notes: (data.notes || "").trim(),
      status: data.status || "Pending",
      created: new Date().toISOString()
    };
    list.push(record);
    save(list);
    return record;
  }

  function updateStatus(id, status) {
    var list = load();
    list.forEach(function (a) { if (a.id === id) { a.status = status; } });
    save(list);
  }
  function remove(id) {
    save(load().filter(function (a) { return a.id !== id; }));
  }
  function clearAll() {
    save([]);
  }

  /* --------------------------------------------------------- statistics */
  function stats() {
    var list = load();
    var s = { total: list.length, Pending: 0, Confirmed: 0, Completed: 0, Cancelled: 0, revenue: 0 };
    list.forEach(function (a) {
      s[a.status] = (s[a.status] || 0) + 1;
      var svc = serviceById(a.service);
      if (svc && a.status !== "Cancelled") { s.revenue += svc.price; }
    });
    return s;
  }

  /* ------------------------------------------------------------ seeding */
  function seedIfEmpty() {
    if (load().length) { return; }
    var today = new Date();
    var d1 = new Date(today.getTime() + 86400000).toISOString().split("T")[0];
    var d2 = new Date(today.getTime() + 2 * 86400000).toISOString().split("T")[0];
    save([
      { id: "SH-0001", customer: "Mariel Bautista", contact: "0917 555 2210", email: "mariel@example.com",
        service: "color", stylist: "ana", date: d1, time: "10:00", notes: "Warm chestnut tone",
        status: "Confirmed", created: new Date().toISOString() },
      { id: "SH-0002", customer: "Kevin Ramos", contact: "0918 442 7781", email: "kevin@example.com",
        service: "haircut", stylist: "ben", date: d1, time: "13:30", notes: "Fade + beard trim",
        status: "Pending", created: new Date().toISOString() },
      { id: "SH-0003", customer: "Sofia Lim", contact: "0920 331 9902", email: "sofia@example.com",
        service: "gel", stylist: "clara", date: d2, time: "15:00", notes: "Nude French tips",
        status: "Confirmed", created: new Date().toISOString() }
    ]);
  }

  /* -------------------------------------------------------- form wiring */
  function fillSelect(el, items, valueKey, labelFn, placeholder) {
    if (!el) { return; }
    var html = placeholder ? '<option value="">' + placeholder + "</option>" : "";
    items.forEach(function (it) {
      html += '<option value="' + it[valueKey] + '">' + labelFn(it) + "</option>";
    });
    el.innerHTML = html;
  }

  function currentList(filters) {
    var list = all();
    if (filters.status && filters.status !== "all") {
      list = list.filter(function (a) { return a.status === filters.status; });
    }
    if (filters.date) {
      list = list.filter(function (a) { return a.date === filters.date; });
    }
    if (filters.q) {
      var q = filters.q.toLowerCase();
      list = list.filter(function (a) {
        return (a.customer + " " + a.contact + " " + a.id).toLowerCase().indexOf(q) > -1;
      });
    }
    return list;
  }

  function renderTable() {
    var tbody = document.getElementById("appointmentsBody");
    if (!tbody) { return; }
    var filters = {
      status: (document.getElementById("filterStatus") || {}).value || "all",
      date: (document.getElementById("filterDate") || {}).value || "",
      q: (document.getElementById("filterSearch") || {}).value || ""
    };
    var list = currentList(filters);
    var empty = document.getElementById("appointmentsEmpty");

    if (!list.length) {
      tbody.innerHTML = "";
      if (empty) { empty.classList.remove("d-none"); }
    } else {
      if (empty) { empty.classList.add("d-none"); }
      tbody.innerHTML = list.map(function (a) {
        var svc = serviceById(a.service) || { name: "—", price: 0 };
        var st = stylistById(a.stylist) || { name: "—" };
        var badge = "badge-" + a.status.toLowerCase();
        return "<tr>" +
          "<td><strong>" + a.id + "</strong><br><small class='text-muted-sh'>" + a.customer + "</small></td>" +
          "<td>" + svc.name + "<br><small class='text-muted-sh'>" + peso(svc.price) + "</small></td>" +
          "<td>" + st.name + "</td>" +
          "<td>" + displayDate(a.date) + "<br><small class='text-muted-sh'>" + to12h(a.time) + "</small></td>" +
          "<td><span class='badge-soft " + badge + "'>" + a.status + "</span></td>" +
          "<td class='text-nowrap'>" +
            "<button class='btn btn-sm btn-outline-plum me-1 js-ok' data-id='" + a.id + "' title='Mark confirmed'><i class='bi bi-check2'></i></button>" +
            "<button class='btn btn-sm btn-outline-plum me-1 js-done' data-id='" + a.id + "' title='Mark completed'><i class='bi bi-flag'></i></button>" +
            "<button class='btn btn-sm btn-outline-plum js-del' data-id='" + a.id + "' title='Cancel / delete'><i class='bi bi-x-lg'></i></button>" +
          "</td>" +
        "</tr>";
      }).join("");
    }
    renderStats();
    renderSlotsPanel();
  }

  function renderStats() {
    var s = stats();
    Object.keys(s).forEach(function (k) {
      var el = document.querySelector("[data-stat='" + k + "']");
      if (el) {
        el.textContent = k === "revenue" ? peso(s[k]) : s[k];
      }
    });
  }

  function renderSlotsPanel() {
    var wrap = document.getElementById("slotsPanel");
    if (!wrap) { return; }
    var dateEl = document.getElementById("apptDate");
    var stylistEl = document.getElementById("apptStylist");
    var date = dateEl ? dateEl.value : "";
    var stylist = stylistEl ? stylistEl.value : "";
    var booked = {};
    if (date && stylist) {
      load().forEach(function (a) {
        if (a.date === date && a.stylist === stylist && a.status !== "Cancelled") {
          booked[a.time] = true;
        }
      });
    }
    wrap.innerHTML = slots().map(function (t) {
      var taken = booked[t];
      return "<span class='badge-soft " + (taken ? "badge-cancelled" : "badge-confirmed") +
        "' style='display:inline-block;margin:0 6px 6px 0'>" + to12h(t) + (taken ? " • booked" : "") + "</span>";
    }).join("");
  }

  function initAppointmentForm() {
    var form = document.getElementById("appointmentForm");
    if (!form) { return; }

    fillSelect(document.getElementById("apptService"), SERVICES, "id",
      function (s) { return s.name + " — " + peso(s.price) + " (" + s.mins + " mins)"; }, "Select a service");
    fillSelect(document.getElementById("apptStylist"), STYLISTS, "id",
      function (s) { return s.name + " — " + s.role; }, "Select a stylist");
    fillSelect(document.getElementById("apptTime"),
      slots().map(function (t) { return { v: t }; }), "v",
      function (o) { return to12h(o.v); }, "Select a time");

    var dateEl = document.getElementById("apptDate");
    var alertBox = document.getElementById("apptAlert");

    function notify(kind, msg) {
      if (!alertBox) { return; }
      alertBox.className = "alert alert-" + kind;
      alertBox.innerHTML = msg;
      alertBox.classList.remove("d-none");
    }
    function hideAlert() { if (alertBox) { alertBox.classList.add("d-none"); } }

    [dateEl, document.getElementById("apptStylist")].forEach(function (el) {
      if (el) { el.addEventListener("change", renderSlotsPanel); }
    });

    form.addEventListener("submit", function (e) {
      e.preventDefault();
      e.stopPropagation();
      form.classList.add("was-validated");
      if (!form.checkValidity()) { return; }

      var data = {
        customer: document.getElementById("apptName").value,
        contact: document.getElementById("apptContact").value,
        email: document.getElementById("apptEmail").value,
        service: document.getElementById("apptService").value,
        stylist: document.getElementById("apptStylist").value,
        date: dateEl.value,
        time: document.getElementById("apptTime").value,
        notes: document.getElementById("apptNotes").value,
        status: "Pending"
      };

      if (hasConflict(data.stylist, data.date, data.time)) {
        notify("danger", "<i class='bi bi-exclamation-triangle me-2'></i><strong>Schedule conflict.</strong> " +
          (stylistById(data.stylist) || {}).name + " already has an appointment on " +
          displayDate(data.date) + " at " + to12h(data.time) + ". Please choose another slot or stylist.");
        return;
      }

      var rec = add(data);
      notify("success", "<i class='bi bi-check-circle me-2'></i><strong>Appointment saved.</strong> " +
        "Reference <strong>" + rec.id + "</strong> for " + data.customer + " on " + displayDate(data.date) +
        " at " + to12h(data.time) + " with " + (stylistById(data.stylist) || {}).name + ".");
      form.reset();
      form.classList.remove("was-validated");
      renderTable();
    });

    document.addEventListener("click", function (e) {
      var t = e.target.closest ? e.target.closest("button") : null;
      if (!t) { return; }
      var id = t.getAttribute("data-id");
      if (!id) { return; }
      if (t.classList.contains("js-ok")) { updateStatus(id, "Confirmed"); renderTable(); }
      if (t.classList.contains("js-done")) { updateStatus(id, "Completed"); renderTable(); }
      if (t.classList.contains("js-del")) { remove(id); renderTable(); }
    });

    ["filterStatus", "filterDate", "filterSearch"].forEach(function (fid) {
      var el = document.getElementById(fid);
      if (el) { el.addEventListener("input", renderTable); }
    });

    var clearBtn = document.getElementById("clearAllBtn");
    if (clearBtn) {
      clearBtn.addEventListener("click", function () {
        clearAll();
        renderTable();
        notify("warning", "<i class='bi bi-trash me-2'></i>All appointment records were cleared from this browser.");
      });
    }
  }

  /* ------------------------------------------------- homepage quick-book */
  function initQuickBook() {
    var form = document.getElementById("quickBookForm");
    if (!form) { return; }
    fillSelect(form.querySelector("[name='service']"), SERVICES, "id",
      function (s) { return s.name + " — " + peso(s.price); }, "Choose a service");
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var svc = form.querySelector("[name='service']").value;
      var d = form.querySelector("[name='date']").value;
      window.location.href = "appointments.html?service=" + encodeURIComponent(svc) + "&date=" + encodeURIComponent(d);
    });
  }

  /* ------------------------------------------------------ deep-linking */
  function applyQueryPrefill() {
    var form = document.getElementById("appointmentForm");
    if (!form) { return; }
    var q = new URLSearchParams(window.location.search);
    var svc = q.get("service");
    var date = q.get("date");
    if (svc) {
      var s = document.getElementById("apptService");
      if (s) { s.value = svc; }
    }
    if (date) {
      var d = document.getElementById("apptDate");
      if (d) { d.value = date; }
    }
    renderSlotsPanel();
  }

  /* ------------------------------------------------------------ startup */
  document.addEventListener("DOMContentLoaded", function () {
    seedIfEmpty();
    initAppointmentForm();
    initQuickBook();
    applyQueryPrefill();
    renderTable();
  });

  /* ------------------------------------------------------------ exports */
  window.StyleSync = {
    SERVICES: SERVICES,
    STYLISTS: STYLISTS,
    peso: peso,
    to12h: to12h,
    slots: slots,
    all: all,
    add: add,
    stats: stats,
    hasConflict: hasConflict,
    renderTable: renderTable
  };
})(window, document);
