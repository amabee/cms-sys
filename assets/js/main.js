/**
 * Main
 */

"use strict";

let menu, animate;
document.addEventListener("DOMContentLoaded", function () {
  // class for ios specific styles
  if (navigator.userAgent.match(/iPhone|iPad|iPod/i)) {
    document.body.classList.add("ios");
  }
});

(function () {
  // Wait for both layout AND Helpers to be ready before initializing menu
  const initializeMenu = function() {
    let layoutMenuEl = document.querySelectorAll("#layout-menu");
    if (layoutMenuEl.length === 0) {
      console.warn('No layout-menu found, waiting for layout...');
      return;
    }
    
    // Check if Helpers is available
    if (typeof window.Helpers === 'undefined') {
      console.error('Helpers not loaded! Make sure helpers.js is loaded before main.js');
      return;
    }
    
    layoutMenuEl.forEach(function (element) {
      // Prevent double initialization
      if (element.classList.contains('menu-initialized')) {
        return;
      }
      
      menu = new Menu(element, {
        orientation: "vertical",
        closeChildren: false,
      });
      element.classList.add('menu-initialized');
      
      // Change parameter to true if you want scroll animation
      window.Helpers.scrollToActive((animate = false));
      window.Helpers.mainMenu = menu;
    });

    // Initialize menu togglers and bind click on each
    let menuToggler = document.querySelectorAll(".layout-menu-toggle");
    menuToggler.forEach((item) => {
      item.addEventListener("click", (event) => {
        event.preventDefault();
        window.Helpers.toggleCollapsed();
      });
    });
  };
  
  // Listen for layout ready event
  window.addEventListener('layoutReady', function() {
    console.log('Layout ready, initializing menu...');
    initializeMenu();
  });
  
  // Fallback: also try on DOMContentLoaded (in case layout already loaded)
  document.addEventListener('DOMContentLoaded', function() {
    if (window.layoutLoaded) {
      initializeMenu();
    }
  });

  // Display menu toggle (layout-menu-toggle) on hover with delay
  let delay = function (elem, callback) {
    let timeout = null;
    elem.onmouseenter = function () {
      // Set timeout to be a timer which will invoke callback after 300ms (not for small screen)
      if (!Helpers.isSmallScreen()) {
        timeout = setTimeout(callback, 300);
      } else {
        timeout = setTimeout(callback, 0);
      }
    };

    elem.onmouseleave = function () {
      // Clear any timers set to timeout
      document.querySelector(".layout-menu-toggle").classList.remove("d-block");
      clearTimeout(timeout);
    };
  };
  if (document.getElementById("layout-menu")) {
    delay(document.getElementById("layout-menu"), function () {
      // not for small screen
      if (!Helpers.isSmallScreen()) {
        document.querySelector(".layout-menu-toggle").classList.add("d-block");
      }
    });
  }

  // Display in main menu when menu scrolls
  let menuInnerContainer = document.getElementsByClassName("menu-inner"),
    menuInnerShadow = document.getElementsByClassName("menu-inner-shadow")[0];
  if (menuInnerContainer.length > 0 && menuInnerShadow) {
    menuInnerContainer[0].addEventListener("ps-scroll-y", function () {
      if (this.querySelector(".ps__thumb-y").offsetTop) {
        menuInnerShadow.style.display = "block";
      } else {
        menuInnerShadow.style.display = "none";
      }
    });
  }

  // Init helpers & misc
  // --------------------

  // Init BS Tooltip
  const tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
  );
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });

  // Accordion active class
  const accordionActiveFunction = function (e) {
    if (e.type == "show.bs.collapse" || e.type == "show.bs.collapse") {
      e.target.closest(".accordion-item").classList.add("active");
    } else {
      e.target.closest(".accordion-item").classList.remove("active");
    }
  };

  const accordionTriggerList = [].slice.call(
    document.querySelectorAll(".accordion")
  );
  const accordionList = accordionTriggerList.map(function (accordionTriggerEl) {
    accordionTriggerEl.addEventListener(
      "show.bs.collapse",
      accordionActiveFunction
    );
    accordionTriggerEl.addEventListener(
      "hide.bs.collapse",
      accordionActiveFunction
    );
  });

  // Auto update layout based on screen size
  window.Helpers.setAutoUpdate(true);

  // Toggle Password Visibility
  window.Helpers.initPasswordToggle();

  // Speech To Text
  window.Helpers.initSpeechToText();

  // Manage menu expanded/collapsed with templateCustomizer & local storage
  //------------------------------------------------------------------

  // If current layout is horizontal OR current window screen is small (overlay menu) than return from here
  if (window.Helpers.isSmallScreen()) {
    return;
  }

  // If current layout is vertical and current window screen is > small

  // Auto update menu collapsed/expanded based on the themeConfig
  window.Helpers.setCollapsed(true, false);
})();
// Utils
function isMacOS() {
  return /Mac|iPod|iPhone|iPad/.test(navigator.userAgent);
}
