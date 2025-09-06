<?php
session_start();
require 'config/auth.php';
include 'includes/header.php'; 

?>

<!-- Hero Section with Background Image -->
<section class="hero-bg">
    <div class="hero-content">
        <h1>RTS <br />A Tech company with vision to help you !!!</h1>

        <p>We design and develop modern website, mobile application, cloud solution and security services.
            From fast secured hosting to responsive and interactive websites, we turn your dream into reality through digital transformation.</p>
        <a href="services.html" class="btn">Explore Services</a>
    </div>
</section>

<!-- Services Overview -->
<section class="services-overview">
    <h2>Our Key Solutions</h2>
    <br />
    <div class="cards">
        <div class="card">
            <h3>Website Development</h3>
            <p>We deliver the best of what you have expected.</p>
        </div>
        <div class="card">
            <h3>App Development</h3>
            <p>
                Custom mobile apps for you according to your requirement and needs.
            </p>
        </div>
        <div class="card">
            <h3>Cloud Solutions</h3>
            <p>
                We provide different sort of cloud solutions as per the requirement
                of the customer.
            </p>
        </div>
        <div class="card">
            <h3>Security</h3>
            <p>
                We provide the best security tools and technology to prevent
                yourself from getting hacked or scammed.
            </p>
        </div>
    </div>
</section>
<?php include 'includes/footer.php'; ?>