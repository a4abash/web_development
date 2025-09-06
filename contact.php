<?php 
require 'config/auth.php';

include 'includes/header.php'; ?>
        <section class="contact-section">
            <h1 style="text-align:center;">Contact Us</h1>

            <form id="contactForm" aria-label="Contact Form">

                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required minlength="3" placeholder="Your Full Name" aria-required="true">

                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required placeholder="you@example.com">

                <label for="message">Message:</label>
                <textarea id="message" name="message" rows="5" required placeholder="Your Message"></textarea>

                <button type="submit">Send Message</button>
                <p class="form-message" id="formMessage" role="status" aria-live="polite"></p>
            </form>

            <div class="contact-info">
                <a href="mailto:a4abash@gmail.com"><i class="fas fa-envelope"></i>Email Us</a><br>
                <a href="https://www.facebook.com/a4abash" target="_blank" aria-label="Facebook"><i class="fab fa-facebook"></i>a4abash</a><br>
                <p><i class=" fa fa-solid fa-phone"></i><a href="tel:0456598567">Contact us</a></p>
            </div>
        </section>
<?php include 'includes/footer.php'; ?>