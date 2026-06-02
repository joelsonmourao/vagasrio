(function () {
  var body = document.body;
  var toggle = document.querySelector(".admin-nav-toggle");
  var nav = document.getElementById("admin-nav");
  var backdrop = document.getElementById("admin-nav-backdrop");
  var mobileQuery = window.matchMedia("(max-width: 760px)");

  function isMobileNav() {
    return mobileQuery.matches;
  }

  function closeAdminNav() {
    if (nav) {
      nav.classList.remove("is-open");
    }
    if (toggle) {
      toggle.setAttribute("aria-expanded", "false");
    }
    if (body) {
      body.classList.remove("admin-nav-open");
      body.style.removeProperty("overflow");
    }
    if (backdrop) {
      backdrop.setAttribute("aria-hidden", "true");
    }
  }

  function openAdminNav() {
    if (!nav || !toggle || !isMobileNav()) {
      return;
    }
    nav.classList.add("is-open");
    toggle.setAttribute("aria-expanded", "true");
    if (body) {
      body.classList.add("admin-nav-open");
      body.style.overflow = "hidden";
    }
    if (backdrop) {
      backdrop.setAttribute("aria-hidden", "false");
    }
  }

  function resetAdminNav() {
    closeAdminNav();
  }

  resetAdminNav();
  window.addEventListener("pageshow", resetAdminNav);

  if (toggle && nav) {
    toggle.addEventListener("click", function () {
      if (nav.classList.contains("is-open")) {
        closeAdminNav();
        return;
      }
      openAdminNav();
    });
  }

  if (backdrop) {
    backdrop.addEventListener("click", closeAdminNav);
  }

  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
      closeAdminNav();
    }
  });

  if (typeof mobileQuery.addEventListener === "function") {
    mobileQuery.addEventListener("change", function () {
      if (!isMobileNav()) {
        closeAdminNav();
      }
    });
  } else if (typeof mobileQuery.addListener === "function") {
    mobileQuery.addListener(function () {
      if (!isMobileNav()) {
        closeAdminNav();
      }
    });
  }
})();
