(function () {
  var consentKey = "portal_vagas_cookie_ok";
  var banner = document.getElementById("cookie-banner");
  var accept = document.getElementById("cookie-accept");

  if (banner && !localStorage.getItem(consentKey)) {
    banner.classList.add("show");
  }

  if (accept) {
    accept.addEventListener("click", function () {
      localStorage.setItem(consentKey, "1");
      if (banner) banner.classList.remove("show");
    });
  }

  var navButton = document.querySelector("[data-nav-toggle]");
  var header = document.querySelector(".site-header");
  var backdrop = document.getElementById("site-nav-backdrop");
  var mobileQuery = window.matchMedia("(max-width: 760px)");

  function isMobileNav() {
    return mobileQuery.matches;
  }

  function closeSiteNav() {
    if (header) {
      header.classList.remove("nav-open");
    }
    if (navButton) {
      navButton.setAttribute("aria-expanded", "false");
    }
    document.body.classList.remove("nav-open");
    document.body.style.removeProperty("overflow");
    if (backdrop) {
      backdrop.setAttribute("aria-hidden", "true");
    }
  }

  function openSiteNav() {
    if (!header || !navButton || !isMobileNav()) {
      return;
    }
    header.classList.add("nav-open");
    navButton.setAttribute("aria-expanded", "true");
    document.body.classList.add("nav-open");
    document.body.style.overflow = "hidden";
    if (backdrop) {
      backdrop.setAttribute("aria-hidden", "false");
    }
  }

  function resetSiteNav() {
    closeSiteNav();
  }

  resetSiteNav();
  window.addEventListener("pageshow", resetSiteNav);

  if (navButton && header) {
    navButton.addEventListener("click", function () {
      if (header.classList.contains("nav-open")) {
        closeSiteNav();
        return;
      }
      openSiteNav();
    });
  }

  if (backdrop) {
    backdrop.addEventListener("click", closeSiteNav);
  }

  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
      closeSiteNav();
    }
  });

  if (typeof mobileQuery.addEventListener === "function") {
    mobileQuery.addEventListener("change", function () {
      if (!isMobileNav()) {
        closeSiteNav();
      }
    });
  } else if (typeof mobileQuery.addListener === "function") {
    mobileQuery.addListener(function () {
      if (!isMobileNav()) {
        closeSiteNav();
      }
    });
  }

  var adNodes = document.querySelectorAll("ins.adsbygoogle");
  var adminNavBtn = document.querySelector("[data-admin-nav-toggle]");
  var adminNav = document.getElementById("admin-main-nav");
  var adminBackdrop = document.getElementById("admin-nav-backdrop");
  var adminBody = document.body;

  function closeAdminNav() {
    if (adminNav) adminNav.classList.remove("is-open");
    if (adminNavBtn) adminNavBtn.setAttribute("aria-expanded", "false");
    if (adminBody) adminBody.classList.remove("admin-nav-open");
  }

  if (adminNavBtn && adminNav) {
    adminNavBtn.addEventListener("click", function () {
      var open = adminNav.classList.toggle("is-open");
      adminNavBtn.setAttribute("aria-expanded", open ? "true" : "false");
      if (adminBody) adminBody.classList.toggle("admin-nav-open", open);
    });
  }

  if (adminBackdrop) {
    adminBackdrop.addEventListener("click", closeAdminNav);
  }

  if (adNodes.length > 0) {
    window.adsbygoogle = window.adsbygoogle || [];
    adNodes.forEach(function (node) {
      if (node.getAttribute("data-ads-init") === "1") return;
      try {
        window.adsbygoogle.push({});
        node.setAttribute("data-ads-init", "1");
      } catch (e) {}
    });
  }
})();
