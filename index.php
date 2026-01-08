<?php
// Provide correct common CSS path for root pages and landing page CSS
$default_common_css = 'assets/css/common.css';
$page_css = 'assets/css/home.css';
$page_class = 'landing-page';

require_once __DIR__ . '/includes/header.php';
?>
<main class="hero">
  <div class="container hero-inner">
    <div class="hero-top">
     
      <!-- removed in-page auth actions; header provides Login/Register -->
    </div>

    <section class="hero-content">
      <h1>Rent Your Ride, Instantly</h1>
      <p class="lead">Welcome to DriveNow – the modern car rental platform that makes booking your perfect vehicle effortless and transparent.</p>
      <div class="hero-cta">
        <a class="btn-primary large" href="search.php">Get Started</a>
        <a class="btn-ghost" href="#features">Learn more</a>
      </div>
    </section>

    <section class="features" id="features">
      <div class="feature">
        <div class="icon">🚙</div>
        <h4>Wide Selection</h4>
        <p>Choose from a diverse fleet of vehicles to suit your needs</p>
      </div>
      <div class="feature">
        <div class="icon">✅</div>
        <h4>Easy Booking</h4>
        <p>Simple and transparent booking process in just a few clicks</p>
      </div>
      <div class="feature">
        <div class="icon">🕒</div>
        <h4>24/7 Support</h4>
        <p>Our team is always here to help you with your rental needs</p>
      </div>
    </section>
  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
