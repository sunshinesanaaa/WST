/* =====================================================================
   Style Haven Salon — main.js
   Shared behaviour for every page (IT 004 — Project Part 2, Group 3)
   Depends on: jQuery 3.7 (loaded from CDN on every page)
   ===================================================================== */

$(function () {
  "use strict";

  /* ---------------------------------------------------- sticky navbar */
  var $nav = $(".navbar-sh");
  function onScroll() {
    var y = $(window).scrollTop();
    $nav.toggleClass("scrolled", y > 40);
    $(".to-top").toggleClass("show", y > 500);
  }
  onScroll();
  $(window).on("scroll", onScroll);

  /* --------------------------------------------- highlight active link */
  var page = (location.pathname.split("/").pop() || "index.html").toLowerCase();
  $(".navbar-sh .nav-link").each(function () {
    var href = ($(this).attr("href") || "").toLowerCase();
    if (href && href.split("#")[0] === page) {
      $(this).addClass("active");
    }
  });

  /* -------------------------------------------------- close mobile nav */
  $(".navbar-sh .nav-link").on("click", function () {
    var $c = $("#mainNav");
    if ($c.hasClass("show")) {
      bootstrap.Collapse.getOrCreateInstance($c[0]).hide();
    }
  });

  /* ------------------------------------------------- reveal on scroll */
  var reveals = document.querySelectorAll(".reveal");
  function showAll() {
    Array.prototype.forEach.call(reveals, function (el) { el.classList.add("visible"); });
  }
  if ("IntersectionObserver" in window) {
    var io = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add("visible");
            io.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.12 }
    );
    reveals.forEach(function (el) { io.observe(el); });
    /* Fail-safe: never leave content hidden if the observer does not fire. */
    window.setTimeout(showAll, 700);
  } else {
    showAll();
  }

  /* --------------------------------------------------- back to the top */
  $(".to-top").on("click", function () {
    window.scrollTo({ top: 0, behavior: "smooth" });
  });

  /* ------------------------------------------------------ current year */
  $(".js-year").text(new Date().getFullYear());

  /* ---------------------------------------- Bootstrap form validation */
  var forms = document.querySelectorAll(".needs-validation");
  Array.prototype.slice.call(forms).forEach(function (form) {
    form.addEventListener(
      "submit",
      function (event) {
        event.preventDefault();
        event.stopPropagation();
        form.classList.add("was-validated");
        if (form.checkValidity()) {
          var ok = form.querySelector(".js-form-success");
          if (ok) { ok.classList.remove("d-none"); }
          form.reset();
          form.classList.remove("was-validated");
        }
      },
      false
    );
  });

  /* ------------------------------------- minimum date = today (booking) */
  var today = new Date().toISOString().split("T")[0];
  $('input[type="date"]').attr("min", today);
});
