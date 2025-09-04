//JS for mail handling
document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("contactForm");
  const formMessage = document.getElementById("formMessage");

  form.addEventListener("submit", (e) => {
    e.preventDefault();
    if (form.checkValidity()) {
      formMessage.textContent = "Thank you! Your message has been sent.";
      formMessage.style.color = "green";
      form.reset();
    } else {
      formMessage.textContent = "Please fill in all required fields correctly.";
      formMessage.style.color = "red";
    }
  });
});

//JS for image handling
document.addEventListener("DOMContentLoaded", () => {
  // Form handler already exists...

  const thumbnails = document.querySelectorAll(".thumbnail");
  const popup = document.getElementById("popup");
  const popupImg = document.getElementById("popupImg");
  const closeBtn = document.getElementById("closeBtn");

  thumbnails.forEach(img => {
    img.addEventListener("click", () => {
      popup.style.display = "flex";
      popupImg.src = img.src;
      popupImg.alt = img.alt;
    });
  });

  closeBtn.addEventListener("click", () => {
    popup.style.display = "none";
  });

  popup.addEventListener("click", (e) => {
    if (e.target === popup) popup.style.display = "none";
  });
});

// Jquery for navmenu
 $(document).ready(function () {
    const $navLinks = $('.nav-links');
    const $menuIcon = $('.menu-icon');

    function handleMobileNav() {
      if (window.innerWidth <= 768) {
        $navLinks.hide();
        $menuIcon.off('mouseenter').on('mouseenter', function () {
          $navLinks.stop(true, true).slideDown(200);
        });

        $('.navbar').off('mouseleave').on('mouseleave', function () {
          $navLinks.stop(true, true).slideUp(200);
        });
      } else {
        $menuIcon.off('mouseenter');
        $('.navbar').off('mouseleave');

        // ✅ Reset navLinks state and remove any inline styles set by jQuery
        $navLinks.removeAttr('style');
      }
    }

    handleMobileNav();
    $(window).on('resize', handleMobileNav);
  });