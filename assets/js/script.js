// JS for mail handling using jQuery
$(document).ready(function () {
    const $form = $("#contactForm");
    const $formMessage = $("#formMessage");

    $form.on("submit", function (e) {
        e.preventDefault();

        if (this.checkValidity()) {
            $formMessage.text("Thank you! Your message has been sent.").css("color", "green");
            $form[0].reset();
        } else {
            $formMessage.text("Please fill in all required fields correctly.").css("color", "red");
        }
    });

    // JS for image handling
    const $thumbnails = $(".thumbnail");
    const $popup = $("#popup");
    const $popupImg = $("#popupImg");
    const $closeBtn = $("#closeBtn");

    $thumbnails.on("click", function () {
        $popup.css("display", "flex");
        $popupImg.attr({
            src: $(this).attr("src"),
            alt: $(this).attr("alt")
        });
    });

    $closeBtn.on("click", function () {
        $popup.css("display", "none");
    });

    $popup.on("click", function (e) {
        if (e.target === this) $popup.css("display", "none");
    });

    // jQuery for nav menu (unchanged)
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
            $navLinks.removeAttr('style');
        }
    }

    handleMobileNav();
    $(window).on('resize', handleMobileNav);
});
