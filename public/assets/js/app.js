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
  if (navButton && header) {
    navButton.addEventListener("click", function () {
      var isOpen = header.classList.toggle("nav-open");
      navButton.setAttribute("aria-expanded", isOpen ? "true" : "false");
    });
  }

  var adNodes = document.querySelectorAll("ins.adsbygoogle");
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
