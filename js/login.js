/* =====================================================================
   Style Sync — log-in module (login.js)
   Style Haven Salon | IT 004 Web Systems and Technologies | Group 3
   Client-side demonstration only. A production build would verify the
   credentials on the server and start a secure session.
   ===================================================================== */

(function (window, document) {
  "use strict";

  var DEMO_USERS = {
    admin: "stylehaven2026",
    frontdesk: "salon123"
  };

  document.addEventListener("DOMContentLoaded", function () {
    var form = document.getElementById("loginForm");
    if (!form) { return; }

    var alertBox = document.getElementById("loginAlert");
    var pass = document.getElementById("loginPass");
    var toggle = document.getElementById("togglePass");

    if (toggle) {
      toggle.addEventListener("click", function () {
        var showing = pass.type === "text";
        pass.type = showing ? "password" : "text";
        toggle.innerHTML = showing ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
      });
    }

    function notify(kind, html) {
      alertBox.className = "alert alert-" + kind;
      alertBox.innerHTML = html;
      alertBox.classList.remove("d-none");
    }

    form.addEventListener("submit", function (e) {
      e.preventDefault();
      e.stopPropagation();

      var user = document.getElementById("loginUser").value.trim();
      var pw = pass.value;
      var ok = Object.prototype.hasOwnProperty.call(DEMO_USERS, user.toLowerCase()) &&
               DEMO_USERS[user.toLowerCase()] === pw;

      if (ok) {
        notify("success", '<i class="bi bi-check-circle me-2"></i>Welcome back, <strong>' +
          user + '</strong>! Opening the appointment dashboard…');
        window.setTimeout(function () {
          window.location.href = "appointments.html";
        }, 900);
      } else {
        alertBox.classList.add("d-none");
        form.classList.add("was-validated");
        notify("danger", '<i class="bi bi-exclamation-triangle me-2"></i><strong>Invalid credentials.</strong> ' +
          'Please check your username and password. (Demo: <code>admin</code> / <code>stylehaven2026</code>)');
      }
    });
  });
})(window, document);
