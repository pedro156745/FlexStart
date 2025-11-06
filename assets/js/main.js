/**
 * ========================================================
 * Anpha Web - main.js (versão otimizada)
 * Base: FlexStart Bootstrap Template
 * Autor: Pedro Lapa (Anpha Web)
 * Atualizado: 06/11/2025
 * ========================================================
 */

document.addEventListener("DOMContentLoaded", () => {
  "use strict";

  /* ===========================
   * Funções utilitárias
   * =========================== */
  const select = (el, all = false) => {
    el = el.trim();
    return all ? [...document.querySelectorAll(el)] : document.querySelector(el);
  };

  const on = (type, el, listener, all = false) => {
    const selectEl = select(el, all);
    if (!selectEl) return;
    (all ? selectEl : [selectEl]).forEach(e => e.addEventListener(type, listener));
  };

  const onscroll = (el, listener) => el.addEventListener("scroll", listener);

  /* ===========================
   * Header fixo ao rolar
   * =========================== */
  const header = select("#header");
  if (header) {
    const headerFixed = () => {
      window.scrollY > 100
        ? header.classList.add("sticked")
        : header.classList.remove("sticked");
    };
    window.addEventListener("load", headerFixed);
    onscroll(document, headerFixed);
  }

  /* ===========================
   * Scroll Top
   * =========================== */
  const scrollTop = select("#scroll-top");
  if (scrollTop) {
    const toggleScrollTop = () => {
      scrollTop.classList.toggle("active", window.scrollY > 200);
    };
    window.addEventListener("load", toggleScrollTop);
    onscroll(document, toggleScrollTop);

    scrollTop.addEventListener("click", e => {
      e.preventDefault();
      window.scrollTo({ top: 0, behavior: "smooth" });
    });
  }

  /* ===========================
   * Menu Mobile
   * =========================== */
  const mobileNavToggle = select(".mobile-nav-toggle");
  const navmenu = select("#navmenu");
  if (mobileNavToggle && navmenu) {
    on("click", ".mobile-nav-toggle", () => {
      navmenu.classList.toggle("navmenu-active");
      mobileNavToggle.classList.toggle("bi-list");
      mobileNavToggle.classList.toggle("bi-x");
    });
  }

  /* ===========================
   * Scroll suave para âncoras
   * =========================== */
  on("click", '#navmenu a[href^="#"]', function (e) {
    const target = select(this.hash);
    if (target) {
      e.preventDefault();
      navmenu.classList.remove("navmenu-active");
      window.scrollTo({ top: target.offsetTop - 60, behavior: "smooth" });
    }
  }, true);

  /* ===========================
   * Inicializações de bibliotecas
   * =========================== */
  window.addEventListener("load", () => {
    // AOS (animações no scroll)
    if (typeof AOS !== "undefined") {
      AOS.init({
        duration: 800,
        easing: "ease-in-out",
        once: true,
        mirror: false,
      });
    }

    // GLightbox (galeria de imagens)
    if (typeof GLightbox !== "undefined") {
      GLightbox({ selector: ".glightbox" });
    }

    // PureCounter (números animados)
    if (typeof PureCounter !== "undefined") {
      new PureCounter();
    }

    // Isotope (filtro de portfólio)
    const portfolioContainer = select(".isotope-container");
    if (portfolioContainer && typeof Isotope !== "undefined") {
      const iso = new Isotope(portfolioContainer, {
        itemSelector: ".isotope-item",
        layoutMode: "masonry",
      });

      const filters = select(".isotope-filters li", true);
      filters.forEach(filter => {
        filter.addEventListener("click", e => {
          e.preventDefault();
          filters.forEach(f => f.classList.remove("filter-active"));
          filter.classList.add("filter-active");
          iso.arrange({ filter: filter.getAttribute("data-filter") });
        });
      });
    }

    // Swiper (depoimentos)
    const swiperEls = select(".init-swiper", true);
    if (swiperEls.length && typeof Swiper !== "undefined") {
      swiperEls.forEach(swiperEl => {
        const configEl = swiperEl.querySelector(".swiper-config");
        let config = {};
        if (configEl) config = JSON.parse(configEl.innerHTML.trim());
        new Swiper(swiperEl, config);
      });
    }
  });

  /* ===========================
   * FAQ interativo
   * =========================== */
  const faqItems = select(".faq-item", true);
  faqItems.forEach(item => {
    item.querySelector(".faq-toggle").addEventListener("click", () => {
      item.classList.toggle("faq-active");
    });
  });
});
