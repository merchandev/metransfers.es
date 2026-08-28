document.addEventListener("DOMContentLoaded", () => {
  const body = document.body;
  const header = document.getElementById("masthead");
  const burger = document.getElementById("burger-btn");
  const menu = document.getElementById("mob-menu");
  const overlay = document.getElementById("mob-overlay");
  const desktopMenuList = document.querySelector("#main-nav .nav-menu");
  const prefersReducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const desktopQuery = window.matchMedia("(min-width: 1025px)");
  const menuLimitClasses = ["menu-limit-rest", "menu-limit-expanded", "menu-limit-return"];
  let hasExpandedMenuAtLeastOnce = window.scrollY > 60;
  const clearMenuLimitClasses = () => {
    if (!header) {
      return;
    }

    menuLimitClasses.forEach((className) => {
      header.classList.remove(className);
    });
  };

  const setDesktopMenuLimitState = () => {
    if (!header) {
      return;
    }

    if (!desktopMenuList || !desktopQuery.matches) {
      clearMenuLimitClasses();
      return;
    }

    const isScrolled = window.scrollY > 60;
    let nextClass = "menu-limit-rest";

    if (isScrolled) {
      nextClass = "menu-limit-expanded";
      hasExpandedMenuAtLeastOnce = true;
    } else if (hasExpandedMenuAtLeastOnce) {
      nextClass = "menu-limit-return";
    }

    clearMenuLimitClasses();
    header.classList.add(nextClass);
  };

  const syncHeaderState = () => {
    if (!header) {
      return;
    }

    header.classList.toggle("scrolled", window.scrollY > 60);
    setDesktopMenuLimitState();
  };

  syncHeaderState();
  window.addEventListener("scroll", syncHeaderState, { passive: true });

  const syncOnViewportChange = () => {
    syncHeaderState();
  };

  if (desktopQuery.addEventListener) {
    desktopQuery.addEventListener("change", syncOnViewportChange);
  } else if (desktopQuery.addListener) {
    desktopQuery.addListener(syncOnViewportChange);
  }

  if (burger && menu && overlay) {
    const setMenuState = (isOpen) => {
      burger.classList.toggle("open", isOpen);
      burger.setAttribute("aria-expanded", String(isOpen));
      menu.classList.toggle("open", isOpen);
      menu.setAttribute("aria-hidden", String(!isOpen));
      overlay.classList.toggle("open", isOpen);
      overlay.setAttribute("aria-hidden", String(!isOpen));
      body.classList.toggle("menu-open", isOpen);
      body.style.overflow = isOpen ? "hidden" : "";
    };

    burger.addEventListener("click", () => {
      setMenuState(!menu.classList.contains("open"));
    });

    overlay.addEventListener("click", () => {
      setMenuState(false);
    });

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape") {
        setMenuState(false);
      }
    });

    menu.querySelectorAll("a").forEach((link) => {
      link.addEventListener("click", () => {
        setMenuState(false);
      });
    });

    const closeOnDesktop = (event) => {
      if (event.matches) {
        setMenuState(false);
      }
    };

    if (desktopQuery.addEventListener) {
      desktopQuery.addEventListener("change", closeOnDesktop);
    } else if (desktopQuery.addListener) {
      desktopQuery.addListener(closeOnDesktop);
    }

    setMenuState(false);
  }

  initHeroBookingFormState();

  if (prefersReducedMotion || !window.gsap || !window.ScrollTrigger) {
    return;
  }

  window.gsap.registerPlugin(window.ScrollTrigger);

  initHeroAnimations();
  initRevealAnimations();
  initBannerAnimations();
});

function initHeroBookingFormState() {
  const host = document.querySelector(".hero-booking-card");

  if (!host) {
    return;
  }

  const syncState = () => {
    const hasForm = Boolean(host.querySelector("form"));
    host.classList.toggle("hero-booking-card--loaded", hasForm);
    host.classList.remove("hero-booking-card--enhanced");
  };

  syncState();

  const observer = new MutationObserver(syncState);
  observer.observe(host, {
    childList: true,
    subtree: true,
  });
}

function initHeroAnimations() {
  const hero = document.querySelector(".hero-section");

  if (!hero) {
    return;
  }

  const timeline = window.gsap.timeline({
    defaults: {
      ease: "power3.out",
    },
  });

  if (document.querySelector(".site-header")) {
    timeline.from(".site-header", {
      y: -28,
      opacity: 0,
      duration: 0.7,
    });
  }

  const heroItems = hero.querySelectorAll(
    ".hero-badge, .hero-title, .hero-subtitle, .hero-actions > *"
  );

  if (heroItems.length) {
    timeline.from(
      heroItems,
      {
        y: 36,
        opacity: 0,
        duration: 0.8,
        stagger: 0.1,
      },
      "-=0.35"
    );
  }

  const glow = hero.querySelector(".hero-glow");

  if (glow) {
    timeline.from(
      glow,
      {
        scale: 0.85,
        opacity: 0,
        duration: 1.4,
        ease: "power2.out",
      },
      0
    );
  }
}

function initRevealAnimations() {
  const revealElements = Array.from(
    document.querySelectorAll(".gs-reveal, .gs-reveal-up")
  ).filter((element) => !element.closest(".hero-single-col"));

  revealElements.forEach((element) => {
    window.gsap.fromTo(
      element,
      {
        y: 40,
        opacity: 0,
      },
      {
        y: 0,
        opacity: 1,
        duration: 0.9,
        ease: "power2.out",
        scrollTrigger: {
          trigger: element,
          start: "top 85%",
          toggleActions: "play none none reverse",
        },
      }
    );
  });

  document.querySelectorAll(".services-grid, .tours-grid").forEach((container) => {
    const items = container.querySelectorAll(".gs-stagger");

    if (!items.length) {
      return;
    }

    window.gsap.fromTo(
      items,
      {
        y: 36,
        opacity: 0,
      },
      {
        y: 0,
        opacity: 1,
        duration: 0.75,
        stagger: 0.12,
        ease: "power2.out",
        scrollTrigger: {
          trigger: container,
          start: "top 82%",
          toggleActions: "play none none reverse",
        },
      }
    );
  });
}

function initBannerAnimations() {
  const bannerSection = document.querySelector(".banner-section");
  const bannerContent = document.querySelector(".banner-content");

  if (!bannerSection || !bannerContent) {
    return;
  }

  window.gsap.fromTo(
    bannerContent,
    {
      y: 40,
      opacity: 0.5,
      scale: 0.98,
    },
    {
      y: 0,
      opacity: 1,
      scale: 1,
      duration: 1,
      ease: "power2.out",
      scrollTrigger: {
        trigger: bannerSection,
        start: "top bottom",
        end: "top 65%",
        scrub: 1,
      },
    }
  );
}

function initDestinationRequestForm() {
    const form = document.getElementById('mt-request-form');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const ajaxConfig = window.meTransfersPublic || window.meTransfers || null;
        const i18n = (ajaxConfig && ajaxConfig.i18n) ? ajaxConfig.i18n : {};

        const submitBtn = form.querySelector('.destination-request-submit');
        const defaultText = submitBtn.textContent;
        const msgContainer = document.createElement('div');
        msgContainer.style.marginTop = '1rem';
        msgContainer.style.fontSize = '0.9rem';
        msgContainer.style.fontWeight = '500';

        // Remove previous message if exists
        const prevMsg = form.querySelector('.request-msg');
        if (prevMsg) prevMsg.remove();

        msgContainer.className = 'request-msg';

        try {
            submitBtn.textContent = i18n.sending || 'Enviando...';
            submitBtn.disabled = true;

            const formData = new FormData(form);

            if (!ajaxConfig || !ajaxConfig.ajaxUrl) {
                throw new Error('AJAX URL not found.');
            }

            const response = await fetch(ajaxConfig.ajaxUrl, {
                method: 'POST',
                body: new URLSearchParams(formData), // Standard text-only form serialization
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                }
            });

            if ( ! response.ok ) {
                throw new Error( 'Server error: ' + response.status );
            }
            const result = await response.json();

            if (result.success) {
                msgContainer.style.color = '#10b981'; // success green
                msgContainer.textContent = result.data.message || i18n.sent || 'Solicitud enviada correctamente.';
                form.reset();
            } else {
                throw new Error(result.data || i18n.errorSend || 'Error al enviar la solicitud.');
            }

        } catch (error) {
            msgContainer.style.color = '#ef4444'; // error red
            msgContainer.textContent = error.message;
        } finally {
            submitBtn.textContent = defaultText;
            submitBtn.disabled = false;
            form.appendChild(msgContainer);
        }
    });
}
document.addEventListener('DOMContentLoaded', initDestinationRequestForm);
