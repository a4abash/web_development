<?php 
require 'config/auth.php';

include 'includes/header.php'; ?>
<section class="services-section">
    <h1>Our Services</h1>
    <p>List of services that we provide to the client:</p><br>
    <div class="service-grid">

        <div class="service-box" tabindex="0" role="region" aria-label="Website Development Services">
            <i class="fas fa-laptop-code"></i>
            <h3>Website Development</h3>
            <p>Custom, responsive websites tailored for your business with modern UI/UX design.</p>
        </div>
        <div class="service-box" tabindex="0" role="region" aria-label="Android Application Development Services">
            <i class="fab fa-android"></i>
            <h3>Android App Development</h3>
            <p>We develop efficient and scalable mobile applications for Android devices.</p>
        </div>
        <div class="service-box" tabindex="0" role="region" aria-label="Security Services">
            <i class="fas fa-shield-alt"></i>
            <h3>Security Services</h3>
            <p>Protect your systems with robust cybersecurity solutions and risk assessments.</p>
        </div>
        <div class="service-box" tabindex="0" role="region" aria-label="Cloud Related Services">
            <i class="fas fa-cloud"></i>
            <h3>Cloud Solutions</h3>
            <p>We provide cloud infrastructure support, migration, and maintenance services.</p>
        </div>

    </div>
</section>
<?php include 'includes/footer.php'; ?>