/* =====================================================================
   Style Sync Admin — admin.js
   Small helpers for the control panel. All real work (validation, saving,
   deleting) happens on the server; this file only adds confirmation
   prompts and the print button.
   ===================================================================== */

(function (window, document) {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {

    /* Ask before running a destructive action. */
    document.addEventListener("click", function (event) {
      var target = event.target.closest("[data-confirm]");
      if (!target) { return; }
      var message = target.getAttribute("data-confirm") || "Are you sure?";
      if (!window.confirm(message)) {
        event.preventDefault();
        event.stopPropagation();
      }
    }, true);

    /* Reusable search box: clear button empties the field and re-submits. */
    document.querySelectorAll("[data-clear-search]").forEach(function (button) {
      button.addEventListener("click", function () {
        var field = document.getElementById(button.getAttribute("data-clear-search"));
        if (!field) { return; }
        field.value = "";
        var form = field.closest("form");
        if (form) { form.submit(); }
      });
    });

    /* Live character counter for description fields. */
    document.querySelectorAll("[data-counter-for]").forEach(function (hint) {
      var field = document.getElementById(hint.getAttribute("data-counter-for"));
      if (!field) { return; }
      var max = field.getAttribute("maxlength") || 0;
      var update = function () {
        hint.textContent = field.value.length + (max ? " / " + max : "") + " characters";
      };
      field.addEventListener("input", update);
      update();
    });

    /* Keep the current year in the footer correct. */
    document.querySelectorAll(".js-year").forEach(function (el) {
      el.textContent = new Date().getFullYear();
    });
  });
})(window, document);
