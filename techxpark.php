<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechXPark - Smart Parking Platform - Aryadi Business</title>
    <meta name="description" content="TechXPark eliminates parking friction with real-time slot booking, live availability tracking, and instant navigation.">
    <?php include('./include/link.php'); ?>
    <!-- Custom Dedicated CSS for TechXPark Redesign -->
    <link rel="stylesheet" href="css/techxpark.css?v=<?php echo time(); ?>">
</head>
<body>
    <?php include('./include/header.php'); ?>

    <!-- Hero Section -->
    <section class="txp-hero-section">
        <div class="container">
            <div class="row align-items-center g-5">
                <!-- Left: Content -->
                <div class="col-lg-7">
                    <span class="txp-badge">Smart Parking Platform</span>
                    <h1 class="txp-title">
                        Find Parking.<br>
                        <span>Park Smarter.</span><br>
                        Every Time.
                    </h1>
                    <p class="txp-desc">
                        TechXPark eliminates parking friction with real-time slot booking, live availability tracking, and instant navigation &mdash; built for modern Indian cities.
                    </p>
                    <div class="txp-actions">
                        <a href="contact-us.php" class="btn-txp-download text-decoration-none">
                            <i class="bi bi-phone"></i> Download Free App
                        </a>
                        <a href="contact-us.php" class="btn-txp-outline text-decoration-none">
                            <i class="bi bi-play-fill"></i> Watch Demo
                        </a>
                    </div>
                    <div class="txp-location">
                        <i class="bi bi-geo-alt-fill text-danger"></i> Available in Delhi NCR
                    </div>
                </div>

                <!-- Right: Phone Mockup -->
                <div class="col-lg-5">
                    <div class="txp-phone-wrapper">
                        <div class="txp-phone-frame">
                            <div class="txp-phone-content">
                                <div class="txp-phone-logo">P</div>
                                <h2 class="txp-phone-app-name">TechXPark</h2>
                                <p class="txp-phone-tagline">Real-Time Parking Reservation &amp; Smart Navigation Platform</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Steps Section -->
    <section class="txp-steps-section">
        <div class="container">
            <div class="row mb-5 text-center">
                <div class="col-lg-8 mx-auto">
                    <span class="txp-badge">How It Works</span>
                    <h2 class="txp-section-heading">Parking in 3 Simple Steps</h2>
                    <p class="txp-section-desc" style="max-width: 600px; margin: 0 auto;">From searching to parked in under 60 seconds.</p>
                </div>
            </div>

            <div class="row txp-steps-flow g-4">
                <!-- Step 1 -->
                <div class="col-md-4 txp-step-item">
                    <div class="txp-step-icon-wrapper">
                        <i class="bi bi-search"></i>
                    </div>
                    <span class="txp-step-number">Step 01</span>
                    <h3 class="txp-step-title">Find Nearby Parking</h3>
                    <p class="txp-step-desc">Open TechXPark and instantly see all available parking lots around you, sorted by distance.</p>
                </div>

                <!-- Step 2 -->
                <div class="col-md-4 txp-step-item">
                    <div class="txp-step-icon-wrapper">
                        <i class="bi bi-grid-3x3-gap"></i>
                    </div>
                    <span class="txp-step-number">Step 02</span>
                    <h3 class="txp-step-title">Select Your Slot</h3>
                    <p class="txp-step-desc">View the real-time parking grid. Choose your preferred slot on any floor &mdash; all live from Firebase.</p>
                </div>

                <!-- Step 3 -->
                <div class="col-md-4 txp-step-item">
                    <div class="txp-step-icon-wrapper">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <span class="txp-step-number">Step 03</span>
                    <h3 class="txp-step-title">Park &amp; Go</h3>
                    <p class="txp-step-desc">Confirm your booking, get navigation to the lot, and show your digital entry pass on arrival.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Everything You Need Section -->
    <section class="txp-features-section">
        <div class="container">
            <div class="row mb-5 text-center">
                <div class="col-lg-8 mx-auto">
                    <span class="txp-badge">Robust Features</span>
                    <h2 class="txp-section-heading">Everything You Need</h2>
                </div>
            </div>

            <div class="row g-4">
                <!-- Feature 1 -->
                <div class="col-lg-4 col-md-6">
                    <div class="txp-feature-card">
                        <div class="txp-feature-icon-box" style="color: #06b6d4; background-color: rgba(6, 182, 212, 0.1);">
                            <i class="bi bi-broadcast"></i>
                        </div>
                        <h3 class="txp-feature-card-title">Real-Time Availability</h3>
                        <p class="txp-feature-card-desc">See live slot availability updated every second via IoT sensors and booking engine.</p>
                    </div>
                </div>

                <!-- Feature 2 -->
                <div class="col-lg-4 col-md-6">
                    <div class="txp-feature-card">
                        <div class="txp-feature-icon-box" style="color: #3b82f6; background-color: rgba(59, 130, 246, 0.1);">
                            <i class="bi bi-send"></i>
                        </div>
                        <h3 class="txp-feature-card-title">GPS Navigation</h3>
                        <p class="txp-feature-card-desc">Turn-by-turn directions to your parking slot. Never get lost in a new area again.</p>
                    </div>
                </div>

                <!-- Feature 3 -->
                <div class="col-lg-4 col-md-6">
                    <div class="txp-feature-card">
                        <div class="txp-feature-icon-box" style="color: #eab308; background-color: rgba(234, 179, 8, 0.1);">
                            <i class="bi bi-lightning-charge"></i>
                        </div>
                        <h3 class="txp-feature-card-title">Instant Booking</h3>
                        <p class="txp-feature-card-desc">Book your slot in under 10 seconds. No calls. No queues. No hassle. Just tap and park.</p>
                    </div>
                </div>

                <!-- Feature 4 -->
                <div class="col-lg-4 col-md-6">
                    <div class="txp-feature-card">
                        <div class="txp-feature-icon-box" style="color: #10b981; background-color: rgba(16, 185, 129, 0.1);">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <h3 class="txp-feature-card-title">Vehicle Verification</h3>
                        <p class="txp-feature-card-desc">Automatic RTO vehicle verification with every booking for maximum security.</p>
                    </div>
                </div>

                <!-- Feature 5 -->
                <div class="col-lg-4 col-md-6">
                    <div class="txp-feature-card">
                        <div class="txp-feature-icon-box" style="color: #6366f1; background-color: rgba(99, 102, 241, 0.1);">
                            <i class="bi bi-bell"></i>
                        </div>
                        <h3 class="txp-feature-card-title">Smart Notifications</h3>
                        <p class="txp-feature-card-desc">Get alerts 30 minutes before expiry, booking confirmations, and special weekend offers.</p>
                    </div>
                </div>

                <!-- Feature 6 -->
                <div class="col-lg-4 col-md-6">
                    <div class="txp-feature-card">
                        <div class="txp-feature-icon-box" style="color: #d946ef; background-color: rgba(217, 70, 239, 0.1);">
                            <i class="bi bi-ticket-perforated"></i>
                        </div>
                        <h3 class="txp-feature-card-title">Live Ticket</h3>
                        <p class="txp-feature-card-desc">Digital entry pass with QR code. Extend your parking time directly from the app.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Real-Time Navigation Section -->
    <section class="txp-nav-section">
        <div class="container">
            <div class="row align-items-center g-5">
                <!-- Left: Content -->
                <div class="col-lg-6">
                    <span class="txp-badge txp-badge-light"><i class="bi bi-send-fill text-primary"></i> Real-Time Navigation</span>
                    <h2 class="txp-section-heading">
                        Find exactly where to park before you arrive.
                    </h2>
                    <p class="txp-section-desc">
                        No more circling the block. Our real-time GPS integration shows you available spots around your destination with live pricing. Compare options, reserve instantly, and navigate directly to your slot.
                    </p>
                    
                    <ul class="txp-checklist">
                        <li class="txp-checklist-item">
                            <span class="txp-checklist-icon"><i class="bi bi-check-circle-fill"></i></span>
                            <div class="txp-checklist-text">
                                Live availability and pricing for nearby spots
                            </div>
                        </li>
                        <li class="txp-checklist-item">
                            <span class="txp-checklist-icon"><i class="bi bi-check-circle-fill"></i></span>
                            <div class="txp-checklist-text">
                                Turn-by-turn navigation directly to the parking lot
                            </div>
                        </li>
                        <li class="txp-checklist-item">
                            <span class="txp-checklist-icon"><i class="bi bi-check-circle-fill"></i></span>
                            <div class="txp-checklist-text">
                                Filter by cheapest, nearest, and available
                            </div>
                        </li>
                    </ul>

                    <a href="contact-us.php" class="btn-txp-download text-decoration-none">
                        Explore nearby parking
                    </a>
                </div>

                <!-- Right: Phone Map Mockup -->
                <div class="col-lg-6">
                    <div class="txp-phone-mockup-shadow">
                        <img src="assets/images/parking_map_app.png" alt="Parking map mobile app screenshot">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- App Showcase Section -->
    <section class="txp-showcase-section">
        <div class="container">
            <div class="row mb-5 text-center">
                <div class="col-lg-8 mx-auto">
                    <span class="txp-badge">Mobile Experience</span>
                    <h2 class="txp-showcase-title">The App That Drivers Love</h2>
                    <p class="txp-showcase-subtitle">Beautiful, fast, and effortless.</p>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="txp-showcase-img-wrapper">
                        <img src="assets/images/three_phones_showcase.png" alt="TechXPark mobile screens showcase" class="img-fluid">
                    </div>
                </div>
            </div>

            <div class="txp-app-buttons">
                <a href="contact-us.php" class="txp-app-btn">
                    <i class="bi bi-google-play"></i>
                    <div class="txp-app-btn-text">
                        <span class="txp-app-btn-subtitle">Get it on</span>
                        <span class="txp-app-btn-title">Google Play</span>
                    </div>
                </a>
                <a href="contact-us.php" class="txp-app-btn">
                    <i class="bi bi-apple"></i>
                    <div class="txp-app-btn-text">
                        <span class="txp-app-btn-subtitle">Download on the</span>
                        <span class="txp-app-btn-title">App Store</span>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <!-- Owner Section -->
    <section class="txp-owner-section">
        <div class="container">
            <div class="row align-items-center g-5">
                <!-- Left Content -->
                <div class="col-lg-6">
                    <span class="txp-badge">For Parking Lot Owners</span>
                    <h2 class="txp-section-heading">Turn Your Parking Lot Into a Smart Revenue Machine</h2>
                    <p class="txp-section-desc">
                        List your parking facility on TechXPark and get access to thousands of drivers looking for parking every day. Manage slots, track revenue, and chat with customers — all from one dashboard.
                    </p>
                    
                    <ul class="txp-checklist">
                        <li class="txp-checklist-item">
                            <span class="txp-checklist-icon"><i class="bi bi-check-circle-fill text-primary"></i></span>
                            <div class="txp-checklist-text">Free to list your parking lot</div>
                        </li>
                        <li class="txp-checklist-item">
                            <span class="txp-checklist-icon"><i class="bi bi-check-circle-fill text-primary"></i></span>
                            <div class="txp-checklist-text">Real-time slot management</div>
                        </li>
                        <li class="txp-checklist-item">
                            <span class="txp-checklist-icon"><i class="bi bi-check-circle-fill text-primary"></i></span>
                            <div class="txp-checklist-text">Revenue analytics dashboard</div>
                        </li>
                        <li class="txp-checklist-item">
                            <span class="txp-checklist-icon"><i class="bi bi-check-circle-fill text-primary"></i></span>
                            <div class="txp-checklist-text">Direct customer messaging</div>
                        </li>
                        <li class="txp-checklist-item">
                            <span class="txp-checklist-icon"><i class="bi bi-check-circle-fill text-primary"></i></span>
                            <div class="txp-checklist-text">Instant booking notifications</div>
                        </li>
                    </ul>

                    <a href="contact-us.php" class="btn-txp-download text-decoration-none">
                        Get Started as Owner &rarr;
                    </a>
                </div>

                <!-- Right Dashboard Mockup -->
                <div class="col-lg-6">
                    <div class="txp-dashboard-wrapper">
                        <div class="txp-dashboard-window">
                            <div class="txp-dashboard-header">
                                <span class="txp-dashboard-title">Owner Dashboard</span>
                                <div class="txp-dashboard-dots">
                                    <span class="dot dot-red"></span>
                                    <span class="dot dot-yellow"></span>
                                    <span class="dot dot-green"></span>
                                </div>
                            </div>
                            <div class="txp-dashboard-body">
                                <div class="txp-dashboard-cards">
                                    <div class="txp-db-card"></div>
                                    <div class="txp-db-card"></div>
                                    <div class="txp-db-card"></div>
                                </div>
                                <div class="txp-dashboard-main-card"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="txp-testimonials-section">
        <div class="container">
            <div class="row mb-5 text-center">
                <div class="col-lg-8 mx-auto">
                    <span class="txp-badge">Testimonials</span>
                    <h2 class="txp-section-heading">Loved by Drivers Across Delhi</h2>
                </div>
            </div>

            <div class="row g-4 justify-content-center">
                <!-- Testimonial 1 -->
                <div class="col-lg-4 col-md-6">
                    <div class="txp-testimonial-card">
                        <div class="txp-testimonial-stars">
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                        </div>
                        <p class="txp-testimonial-text">
                            "TechXPark saved me 20 minutes every morning! I can see available slots before I even leave home."
                        </p>
                        <hr class="txp-testimonial-divider">
                        <div class="txp-testimonial-author">
                            <div class="txp-author-avatar">RS</div>
                            <div class="txp-author-info">
                                <h4 class="txp-author-name">Rahul S.</h4>
                                <span class="txp-author-location">Delhi NCR</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Testimonial 2 -->
                <div class="col-lg-4 col-md-6">
                    <div class="txp-testimonial-card">
                        <div class="txp-testimonial-stars">
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                        </div>
                        <p class="txp-testimonial-text">
                            "The live slot grid is amazing. I can see exactly which floor has space. Never wasted time circling!"
                        </p>
                        <hr class="txp-testimonial-divider">
                        <div class="txp-testimonial-author">
                            <div class="txp-author-avatar">PM</div>
                            <div class="txp-author-info">
                                <h4 class="txp-author-name">Priya M.</h4>
                                <span class="txp-author-location">Noida</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Testimonial 3 -->
                <div class="col-lg-4 col-md-6">
                    <div class="txp-testimonial-card">
                        <div class="txp-testimonial-stars">
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                        </div>
                        <p class="txp-testimonial-text">
                            "As a lot owner, the dashboard helps me track everything. Revenue is up 40% since listing on TechXPark."
                        </p>
                        <hr class="txp-testimonial-divider">
                        <div class="txp-testimonial-author">
                            <div class="txp-author-avatar">AK</div>
                            <div class="txp-author-info">
                                <h4 class="txp-author-name">Amit K.</h4>
                                <span class="txp-author-location">Gurgaon</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="txp-contact-section">
        <div class="container">
            <div class="row mb-4 text-center">
                <div class="col-lg-8 mx-auto">
                    <span class="txp-badge">Support Desk</span>
                    <h2 class="txp-section-heading mb-3">Get in Touch</h2>
                    <p class="txp-section-desc" style="max-width: 600px; margin: 0 auto;">
                        Have questions about TechXPark? Send us a message and we'll get back to you shortly.
                    </p>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="txp-contact-card">
                        <form action="#" method="POST" class="txp-contact-form">
                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <label for="contact-name" class="txp-form-label">Your Name</label>
                                    <input type="text" id="contact-name" class="form-control txp-form-input" placeholder="John Doe" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="contact-email" class="txp-form-label">Email Address</label>
                                    <input type="email" id="contact-email" class="form-control txp-form-input" placeholder="john@example.com" required>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="contact-message" class="txp-form-label">Message</label>
                                <textarea id="contact-message" rows="4" class="form-control txp-form-input" placeholder="How can we help you?" required></textarea>
                            </div>
                            <button type="submit" class="btn-txp-download text-decoration-none w-100 justify-content-center" style="border-radius: 8px; padding: 14px 30px;">
                                Send Message
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Final Download Banner Section -->
    <section class="txp-final-download-section">
        <div class="container">
            <div class="row text-center">
                <div class="col-lg-8 mx-auto">
                    <h2 class="txp-final-heading">Start Parking Smarter Today</h2>
                    <p class="txp-final-desc">Join thousands of drivers who have already eliminated parking stress.</p>
                    
                    <div class="txp-app-buttons justify-content-center mt-4">
                        <a href="contact-us.php" class="txp-app-btn">
                            <i class="bi bi-google-play"></i>
                            <div class="txp-app-btn-text">
                                <span class="txp-app-btn-subtitle">Get it on</span>
                                <span class="txp-app-btn-title">Google Play</span>
                            </div>
                        </a>
                        <a href="contact-us.php" class="txp-app-btn">
                            <i class="bi bi-apple"></i>
                            <div class="txp-app-btn-text">
                                <span class="txp-app-btn-subtitle">Download on the</span>
                                <span class="txp-app-btn-title">App Store</span>
                            </div>
                        </a>
                    </div>

                    <div class="txp-qr-wrapper mt-5">
                        <div class="txp-qr-code">
                            <div class="txp-qr-block"></div>
                            <div class="txp-qr-block"></div>
                            <div class="txp-qr-block"></div>
                            <div class="txp-qr-block"></div>
                        </div>
                        <span class="txp-qr-label">Or scan to download</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include('./include/footer.php'); ?>
</body>
</html>
