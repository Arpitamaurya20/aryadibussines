<?php
include("connection.php");

$secretKey = $recaptcha_secret_key;

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    if (empty($_POST['cf-turnstile-response'])) {
        die("Please verify that you are not a robot.");
    }

    $captcha = $_POST['cf-turnstile-response'];

    // Replace this with your actual Turnstile Secret Key
    // $turnstileSecretKey = "1x0000000000000000000000000000000AA";
    // TURNSTILE_SITE_KEY=0x4AAAAAAEVT0bItB9Wuxabz
    $turnstileSecretKey = "0x4AAAAAAEVT0QR-fGVBYmIEtJcQYQrLQro";

    $data = array(
        'secret' => $turnstileSecretKey,
        'response' => $captcha
    );

    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        )
    );

    $context  = stream_context_create($options);
    $verify = file_get_contents("https://challenges.cloudflare.com/turnstile/v0/siteverify", false, $context);
    $response = json_decode($verify);

    if (!$response->success) {
        die("Turnstile verification failed.");
    }


    $role = $_POST['selected_role'];

    $name = $_POST['name'];
    $email = $_POST['email'];
    $contact = $_POST['contact'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];

    if ($role == "Agent") {
        $query = "INSERT INTO agent(name,email,contact,subject,message)
        VALUES('$name','$email','$contact','$subject','$message')";
    } elseif ($role == "Vendor") {
        $query = "INSERT INTO vendor(Vender_name,email,contact,subject,message)
        VALUES('$name','$email','$contact','$subject','$message')";
    } elseif ($role == "Business Owner") {
        $query = "INSERT INTO business_owner(business_owner,email,contact,subject,message)
        VALUES('$name','$email','$contact','$subject','$message')";
    }

    $result = mysqli_query($conn, $query);

    if ($result) {
        echo "<script>
        alert('Data Saved Successfully');
        window.location='achievements.php';
        </script>";
    } else {
        die("Insert Error : " . mysqli_error($conn));
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Achievements - Aryadi Business</title>
    <?php include('./include/link.php'); ?>

    <!-- Custom Dedicated CSS for Achievements Page Redesign -->
    <link rel="stylesheet" href="css/achievements.css?v=<?php echo time(); ?>">
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</head>

<body>
    <?php include('./include/header.php'); ?>

    <!-- ==========================================
         HERO / JOIN US SECTION
         ========================================== -->
    <section class="hero-achievements">
        <div class="hero-decor-1"></div>
        <div class="hero-decor-2"></div>

        <div class="container position-relative z-3">
            <div class="row align-items-center justify-content-between g-5">

                <!-- Left Column: Typography & Introduction -->
                <div class="col-lg-6">
                    <div class="pe-lg-4 text-start">
                        <span class="section-label">Milestones & Scale</span>
                        <h1 class="display-4 fw-bold mb-4 line-height-sm" style="color: var(--dark-blue) !important; font-weight: 800 !important;">
                            Aryadi Business <br>Scale, Trust & <br><span class="text-brand-blue">Engineering Excellence</span>
                        </h1>
                        <p class="lead mb-4 text-muted" style="font-size: 1.15rem; line-height: 1.6;">
                            We bridge residential technical support and enterprise-grade facility management. From complex electrical grids to full-cycle HVAC maintenance, our certified technicians ensure seamless, 24/7 operations.
                        </p>
                        <div class="d-flex flex-wrap gap-3">
                            <a href="services.php" class="btn btn-brand-primary py-3 px-4 text-uppercase">
                                Our Services
                            </a>
                            <a href="#explore-achievements" class="btn btn-brand-outline py-3 px-4 text-uppercase">
                                Explore Reach
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Join Us Form Card -->
                <div class="col-lg-5 col-xl-5">
                    <div class="join-card-premium">
                        <div class="form-title-wrapper">
                            <h2 class="h3 mb-1 text-uppercase">Join Us</h2>
                            <p class="text-muted small mb-0">Connect with our support and vendor network today.</p>
                        </div>

                        <!-- Role Selector Tabs -->
                        <div class="d-flex mb-4 role-tabs-premium">
                            <button type="button" class="btn flex-fill role-tab-btn-premium" data-role="Agent">Agent</button>
                            <button type="button" class="btn flex-fill role-tab-btn-premium active" data-role="Vendor">Vendor</button>
                            <button type="button" class="btn flex-fill role-tab-btn-premium" data-role="Business Owner">Business Owner</button>
                        </div>

                        <!-- Form -->
                        <form id="joinUsForm" action="achievements.php" method="POST" class="d-flex flex-column gap-3">
                            <input type="hidden" name="selected_role" id="selectedRole" value="Vendor">

                            <!-- Dynamic Name Field -->
                            <div class="form-group-premium">
                                <div class="input-group-premium">
                                    <span class="input-icon-premium"><i class="bi bi-person-fill"></i></span>
                                    <input type="text" id="roleName" name="name" class="form-control-premium" placeholder="Vendor's Name" required>
                                </div>
                            </div>

                            <!-- Email Field -->
                            <div class="form-group-premium">
                                <div class="input-group-premium">
                                    <span class="input-icon-premium"><i class="bi bi-envelope-fill"></i></span>
                                    <input type="email" name="email" class="form-control-premium" placeholder="Email Address" required>
                                </div>
                            </div>

                            <!-- Contact Field -->
                            <div class="form-group-premium">
                                <div class="input-group-premium">
                                    <span class="input-icon-premium"><i class="bi bi-telephone-fill"></i></span>
                                    <input type="text" name="contact" class="form-control-premium" placeholder="Contact Number" required>
                                </div>
                            </div>

                            <!-- Subject Field -->
                            <div class="form-group-premium">
                                <div class="input-group-premium">
                                    <span class="input-icon-premium"><i class="bi bi-bookmark-star-fill"></i></span>
                                    <input type="text" name="subject" class="form-control-premium" placeholder="Subject" required>
                                </div>
                            </div>

                            <!-- Message Field -->
                            <div class="form-group-premium">
                                <div class="input-group-premium textarea-group">
                                    <span class="input-icon-premium"><i class="bi bi-chat-square-text-fill"></i></span>
                                    <textarea name="message" class="form-control-premium textarea-control" rows="3" placeholder="Write your message here..." required></textarea>
                                </div>
                            </div>

                            <!-- Turnstile -->
                            <div class="mb-2">
                                <div class="cf-turnstile" data-sitekey="0x4AAAAAAEVT0bItB9Wuxabz"></div>
                            </div>

                            <button type="submit" class="btn btn-submit-premium text-uppercase w-100 mt-2">
                                Submit Inquiry
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ==========================================
         WHO WE ARE / ENGINEERING EXCELLENCE SECTION
         ========================================== -->
    <section id="explore-achievements" class="who-we-are-premium">
        <div class="container">
            <div class="row align-items-center g-5">

                <!-- Left Column: Content -->
                <div class="col-lg-6">
                    <div class="who-we-are-content-box">
                        <span class="section-label">Who We Are</span>
                        <h2 class="who-title-premium mb-4">
                            Engineering Reliability for <br><span class="text-brand-blue">Homes & Corporate Facilities</span>
                        </h2>
                        <p class="text-muted mb-4" style="line-height: 1.7;">
                            Aryadi Business has grown from a home service provider to an infrastructure maintenance partner. We bridge the gap between residential requirements and enterprise-grade facility management, ensuring your spaces operate at peak efficiency.
                        </p>

                        <!-- Features Grid (2x2) -->
                        <div class="row g-4 mb-5">
                            <div class="col-sm-6">
                                <div class="feature-card-premium">
                                    <div class="feature-icon-premium">
                                        <i class="bi bi-shield-check"></i>
                                    </div>
                                    <div>
                                        <h4 class="h6 fw-bold mb-1 text-dark">Certified Experts</h4>
                                        <span class="text-muted small">Vetted professionals</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="feature-card-premium">
                                    <div class="feature-icon-premium">
                                        <i class="bi bi-clock-history"></i>
                                    </div>
                                    <div>
                                        <h4 class="h6 fw-bold mb-1 text-dark">24/7 Response</h4>
                                        <span class="text-muted small">Always at your service</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="feature-card-premium">
                                    <div class="feature-icon-premium">
                                        <i class="bi bi-gear-fill"></i>
                                    </div>
                                    <div>
                                        <h4 class="h6 fw-bold mb-1 text-dark">Integrated FM</h4>
                                        <span class="text-muted small">Complete maintenance</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="feature-card-premium">
                                    <div class="feature-icon-premium">
                                        <i class="bi bi-currency-dollar"></i>
                                    </div>
                                    <div>
                                        <h4 class="h6 fw-bold mb-1 text-dark">Cost-Efficient</h4>
                                        <span class="text-muted small">Budget-friendly plans</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- CTA Buttons -->
                        <div class="d-flex flex-wrap gap-3">
                            <a href="services.php" class="btn btn-brand-primary py-3 px-4 text-uppercase">
                                Our Services
                            </a>
                            <a href="contact-us.php" class="btn btn-brand-outline py-3 px-4 text-uppercase">
                                Partner With Us
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Overlapping Images -->
                <div class="col-lg-6">
                    <div class="overlapping-images-premium ps-lg-4">
                        <!-- Main Background Image -->
                        <div class="main-img-card">
                            <img src="assets/images/indian_electrical_engineer.png" alt="Indian Electrical Engineer" class="img-fluid w-100">
                        </div>
                        <!-- Overlay/Floating Image -->
                        <div class="overlay-img-card">
                            <img src="assets/images/indian_engineers_blueprints.png" alt="Indian Engineers Reviewing Blueprints" class="img-fluid">
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ==========================================
         WHY WE ARE THE PREFERRED CHOICE SECTION
         ========================================== -->
    <section class="preferred-choice-premium">
        <div class="container">
            <div class="choice-grid-premium">
                <div class="row align-items-center g-5">

                    <!-- Left: Text content -->
                    <div class="col-lg-6">
                        <span class="section-label bg-brand-blue text-white">Why Choose Us</span>
                        <h2 class="choice-title fw-bold text-white mb-4 mt-2 display-6">
                            The Preferred Partner for Infrastructure Maintenance
                        </h2>
                        <p class="text-white opacity-75 mb-5" style="line-height: 1.7;">
                            Aryadi Business combines technical expertise with a service-first mindset. Whether you need rapid residential repairs or comprehensive facility management for your business, we deliver reliability, transparency, and engineering excellence.
                        </p>

                        <div class="d-flex flex-column gap-3">
                            <div class="checklist-premium-item">
                                <div class="check-badge-premium"><i class="bi bi-check-lg"></i></div>
                                <div>One-Stop Solution for Home & Business</div>
                            </div>
                            <div class="checklist-premium-item">
                                <div class="check-badge-premium"><i class="bi bi-check-lg"></i></div>
                                <div>24/7 Rapid Emergency Response</div>
                            </div>
                            <div class="checklist-premium-item">
                                <div class="check-badge-premium"><i class="bi bi-check-lg"></i></div>
                                <div>Focus on Long-Term Asset Reliability</div>
                            </div>
                            <div class="checklist-premium-item">
                                <div class="check-badge-premium"><i class="bi bi-check-lg"></i></div>
                                <div>100% Certified & Vetted Professionals</div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Stats Grid -->
                    <div class="col-lg-6">
                        <div class="row g-4">
                            <div class="col-sm-6">
                                <div class="stat-box-premium">
                                    <span class="stat-icon-premium"><i class="bi bi-people-fill"></i></span>
                                    <div class="stat-num-premium">150+</div>
                                    <div class="text-white opacity-75 small text-uppercase">Happy Clients</div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="stat-box-premium">
                                    <span class="stat-icon-premium"><i class="bi bi-person-badge-fill"></i></span>
                                    <div class="stat-num-premium">200+</div>
                                    <div class="text-white opacity-75 small text-uppercase">Skilled Professionals</div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="stat-box-premium">
                                    <span class="stat-icon-premium"><i class="bi bi-briefcase-fill"></i></span>
                                    <div class="stat-num-premium">500+</div>
                                    <div class="text-white opacity-75 small text-uppercase">Projects Completed</div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="stat-box-premium">
                                    <span class="stat-icon-premium"><i class="bi bi-shield-fill-check"></i></span>
                                    <div class="stat-num-premium">100%</div>
                                    <div class="text-white opacity-75 small text-uppercase">Safety Guaranteed</div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>

    <!-- ==========================================
         OUR ENTERPRISE SOLUTIONS SECTION
         ========================================== -->
    <section class="solutions-section-premium">
        <div class="container">

            <!-- Section Header -->
            <div class="text-center mb-5 pb-3">
                <span class="section-label">Working With Excellence</span>
                <h2 class="fw-bold text-brand-blue mb-3 display-5" style="color: var(--dark-blue) !important; font-weight: 800 !important;">Our Enterprise Solutions</h2>
                <p class="text-muted max-width-md mx-auto">Custom facility management and repair models for organizations</p>
            </div>

            <!-- Grid Layout -->
            <div class="row g-4 justify-content-center">

                <!-- Card 1: Repair & Maintenance -->
                <div class="col-lg-4 col-md-6">
                    <div class="solution-card-premium">
                        <div class="solution-img-wrapper">
                            <img src="assets/images/indian_hvac_technician.png" alt="Repair & Maintenance" class="solution-img-premium">
                            <div class="solution-badge-premium">
                                <i class="bi bi-wrench"></i>
                            </div>
                        </div>
                        <div class="solution-body-premium">
                            <h3 class="solution-title-premium">Repair & Maintenance (R&M)</h3>
                            <p class="text-muted small mb-4">On-demand response for quick repairs and preventive machinery preservation.</p>
                            <a href="repair-maintenance.php" class="solution-link-premium">Learn More <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Featured B2B Spotlight -->
                <div class="col-lg-4 col-md-6">
                    <div class="solution-card-premium spotlight-card-premium rounded shadow text-white">
                        <div>
                            <div class="spotlight-badge-icon">
                                <i class="bi bi-building"></i>
                            </div>
                            <h3 class="fw-bold mb-3 h4" style="font-weight: 800 !important;">Enterprise & B2B<br>Facility Solutions</h3>
                            <p class="text-white opacity-75 small mb-4">
                                Integrated services tailored for corporate hubs, IT parks, and multi-site enterprise portfolios across India.
                            </p>
                        </div>

                        <div>
                            <!-- Stats inside B2B Card -->
                            <div class="row g-2 mb-4 py-2 spotlight-stats-row">
                                <div class="col-6 border-end border-white-50">
                                    <span class="d-block fw-bold h5 mb-0 text-white">150+</span>
                                    <span class="text-uppercase text-white-50" style="font-size: 10px;">Corporate Clients</span>
                                </div>
                                <div class="col-6">
                                    <span class="d-block fw-bold h5 mb-0 text-white">500+</span>
                                    <span class="text-uppercase text-white-50" style="font-size: 10px;">Projects Run</span>
                                </div>
                            </div>
                            <a href="contact-us.php" class="btn btn-brand-primary w-100 text-uppercase py-3" style="background-color: var(--white) !important; color: var(--primary-blue) !important; box-shadow: none;">Request a Proposal</a>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Annual Maintenance Contracts -->
                <div class="col-lg-4 col-md-6">
                    <div class="solution-card-premium">
                        <div class="solution-img-wrapper">
                            <img src="assets/images/indian_electrical_engineer.png" alt="Annual Maintenance Contracts" class="solution-img-premium">
                            <div class="solution-badge-premium">
                                <i class="bi bi-file-earmark-check"></i>
                            </div>
                        </div>
                        <div class="solution-body-premium">
                            <h3 class="solution-title-premium">Annual Maintenance Contracts (AMC)</h3>
                            <p class="text-muted small mb-4">Full calendar infrastructure protection and SLA-driven maintenance checkups.</p>
                            <a href="annual-maintenance.php" class="solution-link-premium">Learn More <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Technical Facility Management -->
                <div class="col-lg-4 col-md-6">
                    <div class="solution-card-premium">
                        <div class="solution-img-wrapper">
                            <img src="assets/images/indian_hero_workers.png" alt="Technical Facility Management" class="solution-img-premium">
                            <div class="solution-badge-premium">
                                <i class="bi bi-gear-fill"></i>
                            </div>
                        </div>
                        <div class="solution-body-premium">
                            <h3 class="solution-title-premium">Technical Facility Management</h3>
                            <p class="text-muted small mb-4">Management of corporate utilities, power distribution grids, server cooling and machinery uptime.</p>
                            <a href="technical-facility-management.php" class="solution-link-premium">Learn More <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                </div>

                <!-- Card 5: Civil & Interior Projects -->
                <div class="col-lg-4 col-md-6">
                    <div class="solution-card-premium">
                        <div class="solution-img-wrapper">
                            <img src="assets/images/indian_engineers_blueprints.png" alt="Civil & Interior Projects" class="solution-img-premium">
                            <div class="solution-badge-premium">
                                <i class="bi bi-bank"></i>
                            </div>
                        </div>
                        <div class="solution-body-premium">
                            <h3 class="solution-title-premium">Civil & Interior Projects</h3>
                            <p class="text-muted small mb-4">Office fit-outs, space design, retail renovations, plumbing upgrades and structural repairs.</p>
                            <a href="civil-interiorproject.php" class="solution-link-premium">Learn More <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                </div>

                <!-- Card 6: Supplies & Procurement -->
                <div class="col-lg-4 col-md-6">
                    <div class="solution-card-premium">
                        <div class="solution-img-wrapper">
                            <img src="assets/images/indian_warehouse_worker.png" alt="Supplies & Procurement" class="solution-img-premium">
                            <div class="solution-badge-premium">
                                <i class="bi bi-box-seam-fill"></i>
                            </div>
                        </div>
                        <div class="solution-body-premium">
                            <h3 class="solution-title-premium">Supplies & Procurement</h3>
                            <p class="text-muted small mb-4">Consumables supply chain, spare procurement, electrical parts logistics and hardware management.</p>
                            <a href="supplies-procurement.php" class="solution-link-premium">Learn More <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ==========================================
         KEY ACHIEVEMENTS SECTION (GRID MATRIX)
         ========================================== -->
    <section class="key-achievements-premium">
        <div class="container">

            <!-- Section Header -->
            <div class="mb-5 text-start">
                <span class="section-label">Our Reach & Impact</span>
                <h2 class="fw-bold mb-3 display-6 text-brand-blue" style="color: var(--dark-blue) !important; font-weight: 800 !important;">Key Achievements & Milestones</h2>
                <p class="text-muted lead max-width-md">
                    Milestones built on scale, trust, and round-the-clock commitment.
                </p>
            </div>

            <!-- Grid Layout (3 columns, 2 rows) -->
            <div class="row g-4">

                <!-- Card 1: State Capitals -->
                <div class="col-lg-4 col-md-6">
                    <div class="achievement-matrix-card">
                        <div class="ach-icon-badge">
                            <i class="bi bi-building"></i>
                        </div>
                        <div class="ach-val-text mb-2">30</div>
                        <div class="ach-title-text">State Capitals Covered</div>
                        <p class="text-muted small m-0">
                            Offices and hubs in major capital cities across Indian states.
                        </p>
                    </div>
                </div>

                <!-- Card 2: Pan-India Reach -->
                <div class="col-lg-4 col-md-6">
                    <div class="achievement-matrix-card">
                        <div class="ach-icon-badge">
                            <i class="bi bi-geo-alt-fill"></i>
                        </div>
                        <div class="ach-val-text mb-2">200+</div>
                        <div class="ach-title-text">Cities Connected</div>
                        <p class="text-muted small m-0">
                            Active maintenance network serving clients in Tier-1, Tier-2, and Tier-3 urban centers.
                        </p>
                    </div>
                </div>

                <!-- Card 3: Engineering Staff -->
                <div class="col-lg-4 col-md-6">
                    <div class="achievement-matrix-card">
                        <div class="ach-icon-badge">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div class="ach-val-text mb-2">300+</div>
                        <div class="ach-title-text">Certified Personnel</div>
                        <p class="text-muted small m-0">
                            A dedicated, trained workforce of facility engineers and repair technicians.
                        </p>
                    </div>
                </div>

                <!-- Card 4: Vendor Network -->
                <div class="col-lg-4 col-md-6">
                    <div class="achievement-matrix-card">
                        <div class="ach-icon-badge">
                            <i class="bi bi-diagram-3-fill"></i>
                        </div>
                        <div class="ach-val-text mb-2">250+</div>
                        <div class="ach-title-text">Partners & Vendors</div>
                        <p class="text-muted small m-0">
                            Strong supplier ecosystem providing materials and high-speed execution support.
                        </p>
                    </div>
                </div>

                <!-- Card 5: Digital Escalation -->
                <div class="col-lg-4 col-md-6">
                    <div class="achievement-matrix-card">
                        <div class="ach-icon-badge">
                            <i class="bi bi-headset"></i>
                        </div>
                        <div class="ach-val-text mb-2">24*7</div>
                        <div class="ach-title-text">Operations Center</div>
                        <p class="text-muted small m-0">
                            A dynamic digital dashboard and desk tracking complaints and escalations live.
                        </p>
                    </div>
                </div>

                <!-- Card 6: Global Standards -->
                <div class="col-lg-4 col-md-6">
                    <div class="achievement-matrix-card">
                        <div class="ach-icon-badge">
                            <i class="bi bi-patch-check-fill"></i>
                        </div>
                        <div class="ach-val-text mb-2">ISO</div>
                        <div class="ach-title-text">Certified Systems</div>
                        <p class="text-muted small mb-3">
                            Working in adherence to top global systems for quality and safety.
                        </p>
                        <div class="small text-muted">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge bg-brand-blue" style="font-size: 9px; background-color: var(--primary-blue) !important;">ISO 9001:2015</span> Quality Standards
                            </div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge bg-brand-blue" style="font-size: 9px; background-color: var(--primary-blue) !important;">ISO 14001:2015</span> Environment System
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-brand-blue" style="font-size: 9px; background-color: var(--primary-blue) !important;">ISO 45001:2018</span> Safety Protocols
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ==========================================
         TECHNICAL EXCELLENCE SECTION
         ========================================== -->
    <section class="technical-excellence-premium">
        <div class="container">
            <div class="row align-items-center g-5">

                <!-- Left Column: Large Image of Engineer -->
                <div class="col-lg-6">
                    <div class="excellence-img-card">
                        <img src="assets/images/indian_electrical_engineer.png" alt="Technical Excellence Engineer" class="img-fluid">
                    </div>
                </div>

                <!-- Right Column: List of Core Expertises -->
                <div class="col-lg-6">
                    <div class="ps-lg-4 text-start">
                        <span class="section-label">Core Technical Skill</span>
                        <h2 class="fw-bold mb-4 display-6 text-brand-blue" style="color: var(--dark-blue) !important; font-weight: 800 !important;">
                            Specialized Competencies for Commercial Operations
                        </h2>

                        <div class="d-flex flex-column gap-4">

                            <!-- Item 1 -->
                            <div class="excellence-step-card">
                                <div class="excellence-step-num">1</div>
                                <div>
                                    <h3 class="excellence-step-title">Electrical & Critical Systems</h3>
                                    <p class="text-muted small m-0">
                                        Substation management, HT/LT breakers, diesel generators setup, synchronization, and compliance checks.
                                    </p>
                                </div>
                            </div>

                            <!-- Item 2 -->
                            <div class="excellence-step-card">
                                <div class="excellence-step-num">2</div>
                                <div>
                                    <h3 class="excellence-step-title">Plumbing & Water Management</h3>
                                    <p class="text-muted small m-0">
                                        WTP & STP plant operations, commercial water lines, pump setups, filter systems, and supply management.
                                    </p>
                                </div>
                            </div>

                            <!-- Item 3 -->
                            <div class="excellence-step-card">
                                <div class="excellence-step-num">3</div>
                                <div>
                                    <h3 class="excellence-step-title">HVAC & Climate Control</h3>
                                    <p class="text-muted small m-0">
                                        VRV/VRF cooling systems, industrial chiller setups, building AHU controls, ducting systems, and performance tuning.
                                    </p>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ==========================================
         STRATEGIC VALUE / CASE STUDIES SECTION
         ========================================== -->
    <section class="case-studies-premium">
        <div class="container">

            <!-- Section Header -->
            <div class="row align-items-center mb-5 g-4 text-start">
                <div class="col-lg-6">
                    <span class="section-label bg-brand-blue text-white" style="background-color: var(--primary-blue) !important;">Visual Showcase</span>
                    <h2 class="fw-bold text-white m-0 display-6" style="font-weight: 800 !important;">Strategic Work & <br>Projects Portfolio</h2>
                </div>
                <div class="col-lg-6">
                    <p class="text-white opacity-75 lead m-0">
                        Visualizing our maintenance operations, site works, and completed space interior milestones.
                    </p>
                </div>
            </div>

            <!-- Case Studies Carousel -->
            <div class="owl-carousel owl-theme case-study-carousel mb-5">
                <div class="case-study-card-premium">
                    <img src="assets/images/indian_electrical_engineer.png" alt="Indian Electrical Engineer inspecting panel">
                </div>
                <div class="case-study-card-premium">
                    <img src="assets/images/indian_engineers_blueprints.png" alt="Engineers reading blueprints">
                </div>
                <div class="case-study-card-premium">
                    <img src="assets/images/indian_hvac_technician.png" alt="HVAC technician doing repair">
                </div>
                <div class="case-study-card-premium">
                    <img src="assets/images/indian_warehouse_worker.png" alt="Warehouse worker monitoring inventory">
                </div>
                <div class="case-study-card-premium">
                    <img src="assets/images/indian_hero_workers.png" alt="Facility management team">
                </div>
            </div>

            <!-- Bottom CTA Text Link -->
            <div class="text-center mt-4">
                <p class="text-white opacity-75 m-0">
                    Ready to optimize your facility's performance? <a href="contact-us.php" class="text-white fw-bold text-decoration-underline ms-2">Request Capability Deck</a>
                </p>
            </div>

        </div>
    </section>

    <?php include('./include/footer.php'); ?>

    <!-- Interactive JS Script for Tab Switching & Solution Click -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Case Study Owl Carousel
            $('.case-study-carousel').owlCarousel({
                loop: true,
                margin: 20,
                nav: false,
                dots: true,
                autoplay: true,
                autoplayTimeout: 5000,
                autoplayHoverPause: true,
                responsive: {
                    0: {
                        items: 1
                    },
                    576: {
                        items: 2
                    },
                    768: {
                        items: 3
                    },
                    992: {
                        items: 4
                    },
                    1200: {
                        items: 5
                    }
                }
            });

            const tabButtons = document.querySelectorAll('.role-tab-btn-premium');
            const nameInput = document.getElementById('roleName');
            const selectedRoleHidden = document.getElementById('selectedRole');

            tabButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    // Remove active class from all tabs
                    tabButtons.forEach(b => b.classList.remove('active'));

                    // Add active class to clicked tab
                    this.classList.add('active');

                    // Get Selected Role
                    const role = this.getAttribute('data-role');
                    selectedRoleHidden.value = role;

                    // Dynamically update the placeholder of the Name input
                    nameInput.placeholder = `${role}'s Name`;
                });
            });

            // Make entire solution card clickable if it contains a link
            const solutionCards = document.querySelectorAll('.solution-card-premium:not(.spotlight-card-premium)');
            solutionCards.forEach(card => {
                card.addEventListener('click', function() {
                    const link = this.querySelector('.solution-link-premium');
                    if (link) {
                        window.location.href = link.getAttribute('href');
                    }
                });
            });
        });
    </script>
</body>

</html>