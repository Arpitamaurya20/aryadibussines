<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aryadi Business</title>
    <?php include('./include/link.php'); ?>
</head>
<body>
    <?php include('./include/header.php'); ?>
    
    <!-- ==========================================
         HERO SLIDER CAROUSEL SECTION
         ========================================== -->
    <div id="heroCarousel" class="carousel slide hero-carousel" data-bs-ride="carousel" data-bs-interval="6000">
        <div class="carousel-inner">
            <!-- Slide 1 -->
            <div class="carousel-item active">
                <div class="hero-slide" style="background-image: url('assets/images/hero_facility1.png');">
                    <div class="container">
                        <div class="hero-content">
                            <span class="hero-subtitle">YOUR TRUSTED FACILITY PARTNER</span>
                            <h1 class="hero-title">Building Reliable Infrastructure <br>Through Expert Facility Solutions</h1>
                            <p class="hero-desc">Our certified professionals provide end-to-end facility management services designed to maximize uptime, reduce maintenance costs, and ensure long-term operational excellence across commercial and industrial properties.</p>
                            <div class="hero-btn-group">
                                <a href="#" class="btn btn-hero-orange">Explore More</a>
                                <a href="contact-us.php" class="btn btn-hero-outline">Get In Touch</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Slide 2 -->
            <div class="carousel-item">
                <div class="hero-slide" style="background-image: url('assets/images/project_industrial.png');">
                    <div class="container">
                        <div class="hero-content">
                            <span class="hero-subtitle">TRUSTED FACILITY MANAGEMENT PARTNER.</span>
                            <h1 class="hero-title">Reliable Engineering & Infrastructure <br> Solutions Across India</h1>
                            <p class="hero-desc">From preventive maintenance and technical facility management to civil projects and procurement services, we provide reliable solutions that enhance safety, reduce downtime, and extend asset life.</p>
                            <div class="hero-btn-group">
                                <a href="#" class="btn btn-hero-orange">Explore More</a>
                                <a href="contact-us.php" class="btn btn-hero-dark">Get In Touch</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Carousel Controls -->
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <i class="bi bi-chevron-left"></i>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <i class="bi bi-chevron-right"></i>
            <span class="visually-hidden">Next</span>
        </button>
    </div>

    <!-- ==========================================
         SERVICES SECTION
         ========================================== -->
    <section class="services-section py-5">
        <div class="container">
            <!-- Section Header -->
            <div class="text-center position-relative mb-5 section-header">
                <span class="watermark-text">PROCESS</span>
                <span class="section-subtitle text-uppercase">WHAT WE DELIVER</span>
                <h2 class="section-title">Integrated Facility Management & Engineering Services</h2>
                <div class="section-desc mx-auto mt-3">
                    Delivering reliable facility management, engineering support, maintenance solutions, and infrastructure services that help businesses operate efficiently, safely, and without interruption.
            </div>
            <div class="row g-4">
            
                <div class="col-lg-4 col-md-6">
                    <div class="service-card">
                        <div class="service-img-wrapper">
                            <img src="assets/images/hero_facility_banner.png" alt="Integrated Facility Management" class="img-fluid service-img">
                           
                        </div>
                        <div class="service-content">
                            <h3 class="service-title">Integrated Facility Management</h3>
                            <p class="service-text">Comprehensive facility management solutions covering housekeeping, security, technical support, and daily operations to ensure seamless business performance.</p>
                        </div>
                    </div>
                </div>

                <!-- Service 2: Expert Mechanical -->
                <div class="col-lg-4 col-md-6">
                    <div class="service-card">
                        <div class="service-img-wrapper">
                            <img src="assets/images/project_industrial.png" alt="Technical Facility Management" class="img-fluid service-img">
                            
                        </div>
                        <div class="service-content">
                            <h3 class="service-title">Technical Facility Management</h3>
                            <p class="service-text">Professional electrical, HVAC, plumbing, fire safety, and mechanical maintenance services delivered by experienced engineers and certified technicians.</p>
                        </div>
                    </div>
                </div>

                <!-- Service 3: Architecture & Building -->
                <div class="col-lg-4 col-md-6">
                    <div class="service-card">
                        <div class="service-img-wrapper">
                            <img src="assets/images/civil1.png" alt="Civil & Building" class="img-fluid service-img">
                            
                        </div>
                        <div class="service-content">
                            <h3 class="service-title">Civil & Interior Projects</h3>
                            <p class="service-text">End-to-end civil construction, renovation, interior fit-outs, and infrastructure improvement services tailored for commercial and industrial spaces.</p>
                        </div>
                    </div>
                </div>

                <!-- Service 4: Tiling & Painting -->
                <div class="col-lg-4 col-md-6">
                    <div class="service-card">
                        <div class="service-img-wrapper">
                            <img src="assets/images/project_warehouse.png" alt="Supplies & Procurement" class="img-fluid service-img">
                           
                        </div>
                        <div class="service-content">
                            <h3 class="service-title">Supplies & Procurement</h3>
                            <p class="service-text">Reliable sourcing and procurement of engineering materials, maintenance supplies, and facility equipment to support uninterrupted operations</p>
                        </div>
                    </div>
                </div>

                <!-- Service 5: Apartment Design -->
                <div class="col-lg-4 col-md-6">
                    <div class="service-card">
                        <div class="service-img-wrapper">
                            <img src="assets/images/project_hvac.png" alt="Repair & Maintenance" class="img-fluid service-img">
                            
                        </div>
                        <div class="service-content">
                            <h3 class="service-title">Repair & Maintenance</h3>
                            <p class="service-text">Preventive and corrective maintenance solutions that improve equipment reliability, reduce downtime, and extend asset life</p>
                        </div>
                    </div>
                </div>

                <!-- Service 6: Facade Engineering -->
                <div class="col-lg-4 col-md-6">
                    <div class="service-card">
                        <div class="service-img-wrapper">
                            <img src="assets/images/main.png" alt="Annual Maintenance Contract" class="img-fluid service-img">
                            
                        </div>
                        <div class="service-content">
                            <h3 class="service-title">Annual Maintenance Contract</h3>
                            <p class="service-text">Preventive maintenance programs designed to reduce downtime and extend equipment life.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ==========================================
         COMMITMENT SECTION
         ========================================== -->
    <section class="commitment-section py-5">
        <div class="container-fluid p-0">
            <div class="row align-items-center g-0">
                <!-- Left: Tablet frame aligned to absolute left edge of screen -->
                <div class="col-lg-6">
                    <div class="tablet-device-wrapper">
                        <div class="tablet-device">
                            <div class="tablet-screen">
                                <img src="assets/images/engineers_blueprint.png" alt="Engineers reviewing blueprints" class="img-fluid tablet-img">
                            </div>
                            <div class="tablet-home-button"></div>
                        </div>
                    </div>
                </div>

                <!-- Right: Content -->
                <div class="col-lg-6">
                    <div class="commitment-content-wrapper">
                        <div class="commitment-content">
                            <div class="commitment-subtitle-wrapper mb-3">
                                <span class="commitment-subtitle text-uppercase">Delivering Excellence Through Integrated Facility Solutions</span>
                            </div>
                            <h2 class="commitment-title mb-4">YOUR TRUSTED FACILITY MANAGEMENT PARTNER</h2>
                            <p class="commitment-desc mb-4">
                                At Aryadi Business Pvt. Ltd., we deliver comprehensive facility management and engineering solutions tailored to commercial, industrial, corporate, healthcare, and residential properties. From technical maintenance and civil interior projects to procurement, repair services, and Annual Maintenance Contracts, our experienced professionals ensure operational efficiency, safety, and long-term asset performance.
                            </p>
                            <div class="mt-4">
                                <a href="#" class="btn-commitment-orange">
                                    Read More </span><i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ==========================================
         SPECIAL PROJECTS SECTION
         ========================================== -->
    <section class="projects-section py-5">
        <div class="container py-4">
            <!-- Section Header -->
            <div class="text-center position-relative mb-5 section-header">
                <span class="watermark-text">OUR WORK</span>
                <span class="section-subtitle text-uppercase">OUR SERVICE PORTFOLIO</span>
                <h2 class="section-title">Proven Facility Management Solutions Across Industries</h2>
                <div class="section-desc mx-auto mt-3">
                    Explore some of our successfully executed facility management projects, technical maintenance services, civil & interior works, procurement solutions, and Annual Maintenance Contracts. Aryadi Business Pvt. Ltd. delivers reliable, safe, and efficient solutions for commercial, industrial, healthcare, educational, and corporate facilities across India.
                </div>
            </div>

            <!-- Project Filter Navigation -->
            <div class="project-filters-wrapper mb-5">
                <ul class="nav justify-content-center project-filter-nav gap-2 gap-md-4">
                    <li class="nav-item">
                        <button class="filter-btn active" data-filter="all">All</button>
                    </li>
                    <li class="nav-item">
                        <button class="filter-btn" data-filter="commercial">Commercial</button>
                    </li>
                    <li class="nav-item">
                        <button class="filter-btn" data-filter="healthcare">Healthcare</button>
                    </li>
                    <li class="nav-item">
                        <button class="filter-btn" data-filter="hvac">HVAC</button>
                    </li>
                    <li class="nav-item">
                        <button class="filter-btn" data-filter="industrial">Industrial</button>
                    </li>
                    <li class="nav-item">
                        <button class="filter-btn" data-filter="interior">Interior</button>
                    </li>
                </ul>
            </div>

            <!-- Projects Grid -->
            <div class="row g-4 project-grid">
                <!-- Project 1: Commercial -->
                <div class="col-lg-4 col-md-6 project-item" data-category="commercial">
                    <div class="project-card">
                        <div class="project-img-wrapper">
                            <img src="assets/images/commercial.png" alt="Corporate Office Facility Management" class="img-fluid project-img">
                            <div class="project-overlay">
                                <span class="project-category text-uppercase">Commercial</span>
                                <h4 class="project-name">Corporate Office Complex — Noida</h4>
                                <a href="#" class="project-link-icon"><i class="bi bi-plus"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Project 2: Healthcare -->
                <div class="col-lg-4 col-md-6 project-item" data-category="healthcare">
                    <div class="project-card">
                        <div class="project-img-wrapper">
                            <img src="assets/images/project_hospital.png" alt="Hospital Facility Management" class="img-fluid project-img">
                            <div class="project-overlay">
                                <span class="project-category text-uppercase">Healthcare</span>
                                <h4 class="project-name">Multi-Specialty Hospital FM — Delhi NCR</h4>
                                <a href="#" class="project-link-icon"><i class="bi bi-plus"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Project 3: HVAC -->
                <div class="col-lg-4 col-md-6 project-item" data-category="hvac">
                    <div class="project-card">
                        <div class="project-img-wrapper">
                            <img src="assets/images/HVAC.png" alt="HVAC System Overhaul" class="img-fluid project-img">
                            <div class="project-overlay">
                                <span class="project-category text-uppercase">HVAC</span>
                                <h4 class="project-name">HVAC System Overhaul — Industrial Plant</h4>
                                <a href="#" class="project-link-icon"><i class="bi bi-plus"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Project 4: Industrial -->
                <div class="col-lg-4 col-md-6 project-item" data-category="industrial">
                    <div class="project-card">
                        <div class="project-img-wrapper">
                            <img src="assets/images/supp.png" alt="Warehouse Facility Management" class="img-fluid project-img">
                            <div class="project-overlay">
                                <span class="project-category text-uppercase">Industrial</span>
                                <h4 class="project-name">Warehouse Facility Management — Ghaziabad</h4>
                                <a href="#" class="project-link-icon"><i class="bi bi-plus"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Project 5: Interior -->
                <div class="col-lg-4 col-md-6 project-item" data-category="interior">
                    <div class="project-card">
                        <div class="project-img-wrapper">
                            <img src="assets/images/project_interior.png" alt="Corporate Interior Fit-Out" class="img-fluid project-img">
                            <div class="project-overlay">
                                <span class="project-category text-uppercase">Interior</span>
                                <h4 class="project-name">Corporate Office Interior Fit-Out — Gurugram</h4>
                                <a href="#" class="project-link-icon"><i class="bi bi-plus"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Project 6: Industrial Plant -->
                <div class="col-lg-4 col-md-6 project-item" data-category="industrial">
                    <div class="project-card">
                        <div class="project-img-wrapper">
                            <img src="assets/images/NOIDA.png" alt="Industrial Plant Maintenance" class="img-fluid project-img">
                            <div class="project-overlay">
                                <span class="project-category text-uppercase">Industrial</span>
                                <h4 class="project-name">Manufacturing Plant Maintenance — Greater Noida</h4>
                                <a href="#" class="project-link-icon"><i class="bi bi-plus"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Project 7: Commercial 1 -->
                <div class="col-lg-4 col-md-6 project-item" data-category="commercial" data-exclude-all="true" style="display: none; opacity: 0; transform: scale(0.8);">
                    <div class="project-card">
                        <div class="project-img-wrapper">
                            <img src="assets/images/commercial1.png" alt="IT Park & Tech Hub Facility Management" class="img-fluid project-img">
                            <div class="project-overlay">
                                <span class="project-category text-uppercase">Commercial</span>
                                <h4 class="project-name">IT Park & Tech Hub — Bengaluru</h4>
                                <a href="#" class="project-link-icon"><i class="bi bi-plus"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Project 8: Commercial 2 -->
                <div class="col-lg-4 col-md-6 project-item" data-category="commercial" data-exclude-all="true" style="display: none; opacity: 0; transform: scale(0.8);">
                    <div class="project-card">
                        <div class="project-img-wrapper">
                            <img src="assets/images/commercial2.png" alt="Commercial Retail Center Management" class="img-fluid project-img">
                            <div class="project-overlay">
                                <span class="project-category text-uppercase">Commercial</span>
                                <h4 class="project-name">Commercial Retail Center — Mumbai</h4>
                                <a href="#" class="project-link-icon"><i class="bi bi-plus"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Project 9: Commercial 3 -->
                <div class="col-lg-4 col-md-6 project-item" data-category="commercial" data-exclude-all="true" style="display: none; opacity: 0; transform: scale(0.8);">
                    <div class="project-card">
                        <div class="project-img-wrapper">
                            <img src="assets/images/commercial3.png" alt="High-Rise Business Tower Maintenance" class="img-fluid project-img">
                            <div class="project-overlay">
                                <span class="project-category text-uppercase">Commercial</span>
                                <h4 class="project-name">High-Rise Business Tower — Pune</h4>
                                <a href="#" class="project-link-icon"><i class="bi bi-plus"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Project 10: Commercial 4 -->
                <div class="col-lg-4 col-md-6 project-item" data-category="commercial" data-exclude-all="true" style="display: none; opacity: 0; transform: scale(0.8);">
                    <div class="project-card">
                        <div class="project-img-wrapper">
                            <img src="assets/images/commercial4.png" alt="Premium Workspace Facility Solutions" class="img-fluid project-img">
                            <div class="project-overlay">
                                <span class="project-category text-uppercase">Commercial</span>
                                <h4 class="project-name">Premium Workspace Facility — Gurugram</h4>
                                <a href="#" class="project-link-icon"><i class="bi bi-plus"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Project 11: Commercial 5 -->
                <div class="col-lg-4 col-md-6 project-item" data-category="commercial" data-exclude-all="true" style="display: none; opacity: 0; transform: scale(0.8);">
                    <div class="project-card">
                        <div class="project-img-wrapper">
                            <img src="assets/images/commercial5.png" alt="Corporate Business Park Services" class="img-fluid project-img">
                            <div class="project-overlay">
                                <span class="project-category text-uppercase">Commercial</span>
                                <h4 class="project-name">Corporate Business Park — Chennai</h4>
                                <a href="#" class="project-link-icon"><i class="bi bi-plus"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Project 12: Commercial 6 -->
                <!--<div class="col-lg-4 col-md-6 project-item" data-category="commercial" data-exclude-all="true" style="display: none; opacity: 0; transform: scale(0.8);">
                    <div class="project-card">
                        <div class="project-img-wrapper">
                            <img src="assets/images/commercial6.png" alt="Modern Tech Center Facility Management" class="img-fluid project-img">
                            <div class="project-overlay">
                                <span class="project-category text-uppercase">Commercial</span>
                                <h4 class="project-name">Modern Tech Center — Hyderabad</h4>
                                <a href="#" class="project-link-icon"><i class="bi bi-plus"></i></a>
                            </div>
                        </div>
                    </div>
                </div>-->

                <!-- Project 13: Healthcare 1 -->
                <div class="col-lg-4 col-md-6 project-item" data-category="healthcare" data-exclude-all="true" style="display: none; opacity: 0; transform: scale(0.8);">
                    <div class="project-card">
                        <div class="project-img-wrapper">
                            <img src="assets/images/healthcare1.png" alt="Medical Cleanroom Sterilization" class="img-fluid project-img">
                            <div class="project-overlay">
                                <span class="project-category text-uppercase">Healthcare</span>
                                <h4 class="project-name">Medical Cleanroom Sterilization</h4>
                                <a href="#" class="project-link-icon"><i class="bi bi-plus"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Project 14: Healthcare 2 -->
                <div class="col-lg-4 col-md-6 project-item" data-category="healthcare" data-exclude-all="true" style="display: none; opacity: 0; transform: scale(0.8);">
                    <div class="project-card">
                        <div class="project-img-wrapper">
                            <img src="assets/images/healthcare2.png" alt="Oxygen Supply Pipelines & Gas Management" class="img-fluid project-img">
                            <div class="project-overlay">
                                <span class="project-category text-uppercase">Healthcare</span>
                                <h4 class="project-name">Oxygen Pipelines & Gas Management</h4>
                                <a href="#" class="project-link-icon"><i class="bi bi-plus"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Project 15: Healthcare 3 -->
                <div class="col-lg-4 col-md-6 project-item" data-category="healthcare" data-exclude-all="true" style="display: none; opacity: 0; transform: scale(0.8);">
                    <div class="project-card">
                        <div class="project-img-wrapper">
                            <img src="assets/images/healthcare3.png" alt="Critical Power Backup Generators" class="img-fluid project-img">
                            <div class="project-overlay">
                                <span class="project-category text-uppercase">Healthcare</span>
                                <h4 class="project-name">Critical Power Backup Generators</h4>
                                <a href="#" class="project-link-icon"><i class="bi bi-plus"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Project 16: Healthcare 4 -->
                <div class="col-lg-4 col-md-6 project-item" data-category="healthcare" data-exclude-all="true" style="display: none; opacity: 0; transform: scale(0.8);">
                    <div class="project-card">
                        <div class="project-img-wrapper">
                            <img src="assets/images/healthcare4.png" alt="HEPA Air Filtration & HVAC Service" class="img-fluid project-img">
                            <div class="project-overlay">
                                <span class="project-category text-uppercase">Healthcare</span>
                                <h4 class="project-name">HEPA Air Filtration & HVAC Service</h4>
                                <a href="#" class="project-link-icon"><i class="bi bi-plus"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Project 17: Healthcare 5 -->
                <div class="col-lg-4 col-md-6 project-item" data-category="healthcare" data-exclude-all="true" style="display: none; opacity: 0; transform: scale(0.8);">
                    <div class="project-card">
                        <div class="project-img-wrapper">
                            <img src="assets/images/healthcare5.png" alt="Patient Monitor Calibration & Service" class="img-fluid project-img">
                            <div class="project-overlay">
                                <span class="project-category text-uppercase">Healthcare</span>
                                <h4 class="project-name">Patient Monitor Calibration & Service</h4>
                                <a href="#" class="project-link-icon"><i class="bi bi-plus"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                
                <!-- Project 19: HVAC 1 -->
                <div class="col-lg-4 col-md-6 project-item" data-category="hvac" data-exclude-all="true" style="display: none; opacity: 0; transform: scale(0.8);">
                    <div class="project-card">
                        <div class="project-img-wrapper">
                            <img src="assets/images/hvac1.png" alt="Industrial Chiller System Maintenance" class="img-fluid project-img">
                            <div class="project-overlay">
                                <span class="project-category text-uppercase">HVAC</span>
                                <h4 class="project-name">Industrial Chiller System Maintenance</h4>
                                <a href="#" class="project-link-icon"><i class="bi bi-plus"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Project 20: HVAC 2 -->
                <div class="col-lg-4 col-md-6 project-item" data-category="hvac" data-exclude-all="true" style="display: none; opacity: 0; transform: scale(0.8);">
                    <div class="project-card">
                        <div class="project-img-wrapper">
                            <img src="assets/images/hvac2.png" alt="Centralized AC Duct Inspection" class="img-fluid project-img">
                            <div class="project-overlay">
                                <span class="project-category text-uppercase">HVAC</span>
                                <h4 class="project-name">Centralized AC Duct Inspection</h4>
                                <a href="#" class="project-link-icon"><i class="bi bi-plus"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Project 21: HVAC 3 -->
                <div class="col-lg-4 col-md-6 project-item" data-category="hvac" data-exclude-all="true" style="display: none; opacity: 0; transform: scale(0.8);">
                    <div class="project-card">
                        <div class="project-img-wrapper">
                            <img src="assets/images/hvac3.png" alt="HVAC Control Systems Calibration" class="img-fluid project-img">
                            <div class="project-overlay">
                                <span class="project-category text-uppercase">HVAC</span>
                                <h4 class="project-name">HVAC Control Systems Calibration</h4>
                                <a href="#" class="project-link-icon"><i class="bi bi-plus"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Project 22: HVAC 4 -->
                <div class="col-lg-4 col-md-6 project-item" data-category="hvac" data-exclude-all="true" style="display: none; opacity: 0; transform: scale(0.8);">
                    <div class="project-card">
                        <div class="project-img-wrapper">
                            <img src="assets/images/hvac4.png" alt="Site Engineering & Ventilation Check" class="img-fluid project-img">
                            <div class="project-overlay">
                                <span class="project-category text-uppercase">HVAC</span>
                                <h4 class="project-name">Site Engineering & Ventilation Check</h4>
                                <a href="#" class="project-link-icon"><i class="bi bi-plus"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Project 23: HVAC 5 -->
                <div class="col-lg-4 col-md-6 project-item" data-category="hvac" data-exclude-all="true" style="display: none; opacity: 0; transform: scale(0.8);">
                    <div class="project-card">
                        <div class="project-img-wrapper">
                            <img src="assets/images/hvac5.png" alt="Rooftop Cooling Tower AMC Service" class="img-fluid project-img">
                            <div class="project-overlay">
                                <span class="project-category text-uppercase">HVAC</span>
                                <h4 class="project-name">Rooftop Cooling Tower AMC Service</h4>
                                <a href="#" class="project-link-icon"><i class="bi bi-plus"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Project: Industrial 1 -->
                <div class="col-lg-4 col-md-6 project-item" data-category="industrial" data-exclude-all="true" style="display: none; opacity: 0; transform: scale(0.8);">
                    <div class="project-card">
                        <div class="project-img-wrapper">
                            <img src="assets/images/industrial1.png" alt="Warehouse Operations & Logistics FM" class="img-fluid project-img">
                            <div class="project-overlay">
                                <span class="project-category text-uppercase">Industrial</span>
                                <h4 class="project-name">Warehouse Operations & Logistics FM</h4>
                                <a href="#" class="project-link-icon"><i class="bi bi-plus"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Project: Industrial 2 -->
                <div class="col-lg-4 col-md-6 project-item" data-category="industrial" data-exclude-all="true" style="display: none; opacity: 0; transform: scale(0.8);">
                    <div class="project-card">
                        <div class="project-img-wrapper">
                            <img src="assets/images/industrial2.png" alt="Industrial Site Supervision & Management" class="img-fluid project-img">
                            <div class="project-overlay">
                                <span class="project-category text-uppercase">Industrial</span>
                                <h4 class="project-name">Industrial Site Supervision</h4>
                                <a href="#" class="project-link-icon"><i class="bi bi-plus"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Project: Industrial 3 -->
                <div class="col-lg-4 col-md-6 project-item" data-category="industrial" data-exclude-all="true" style="display: none; opacity: 0; transform: scale(0.8);">
                    <div class="project-card">
                        <div class="project-img-wrapper">
                            <img src="assets/images/industrial3.png" alt="High-Rise Scaffolding & Exterior Maintenance" class="img-fluid project-img">
                            <div class="project-overlay">
                                <span class="project-category text-uppercase">Industrial</span>
                                <h4 class="project-name">High-Rise Structural Maintenance</h4>
                                <a href="#" class="project-link-icon"><i class="bi bi-plus"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Project: Industrial 4 -->
                <div class="col-lg-4 col-md-6 project-item" data-category="industrial" data-exclude-all="true" style="display: none; opacity: 0; transform: scale(0.8);">
                    <div class="project-card">
                        <div class="project-img-wrapper">
                            <img src="assets/images/industrial4.png" alt="Industrial Engineering Operations Team" class="img-fluid project-img">
                            <div class="project-overlay">
                                <span class="project-category text-uppercase">Industrial</span>
                                <h4 class="project-name">Industrial Engineering Support Team</h4>
                                <a href="#" class="project-link-icon"><i class="bi bi-plus"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Project Filter JS -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterButtons = document.querySelectorAll('.filter-btn');
            const projectItems = document.querySelectorAll('.project-item');

            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    // Add active class to clicked button
                    this.classList.add('active');

                    const filterValue = this.getAttribute('data-filter');

                    projectItems.forEach(item => {
                        const isExcludedFromAll = item.getAttribute('data-exclude-all') === 'true';
                        if ((filterValue === 'all' && !isExcludedFromAll) || (filterValue !== 'all' && item.getAttribute('data-category') === filterValue)) {
                            item.style.display = 'block';
                            // Trigger fade in animation
                            setTimeout(() => {
                                item.style.opacity = '1';
                                item.style.transform = 'scale(1)';
                            }, 50);
                        } else {
                            item.style.opacity = '0';
                            item.style.transform = 'scale(0.8)';
                            setTimeout(() => {
                                item.style.display = 'none';
                            }, 300);
                        }
                    });
                });
            });
        });
    </script>

    <!-- ==========================================
         PROFITABLE SOLUTIONS SECTION
         ========================================== -->
   <!-- ================================
     Building Smarter Workplaces
================================== -->
<section class="smart-workplace-section py-5">
    <div class="container">
        <div class="row align-items-center">

            <!-- Left Content -->
            <div class="col-lg-6 mb-5 mb-lg-0">

                <span class="section-tag">
                    BUILDING EXCELLENCE
                </span>

                <h2 class="section-title mt-3">
                    Building Smarter <br>
                    Workplaces
                </h2>

                <p class="section-text mt-4">
                    At Aryadi Business Pvt. Ltd., we deliver reliable,
                    end-to-end Facility Management solutions that help
                    businesses operate safely, efficiently, and without
                    interruption.
                </p>

                <p class="section-text">
                    From Technical Facility Management and Civil &
                    Interior Projects to Repair & Maintenance,
                    Supplies & Procurement, Integrated Facility
                    Management, and Annual Maintenance Contracts,
                    our experienced professionals ensure quality,
                    safety, and long-term value for every client.
                </p>

                <a href="services.php" class="btn btn-primary px-4 py-3 mt-3">
                    Explore Services →
                </a>

            </div>

            <!-- Right Image -->
            <div class="col-lg-6 text-center">

                <div class="image-box">

                    <img src="assets/images/workplace.png"
                         class="img-fluid rounded-4 shadow-lg"
                         alt="Aryadi Business Facility Management">

                </div>

            </div>

        </div>
    </div>
</section>
    <!-- ==========================================
         ABOUT SOLUTIONS SECTION
         ========================================== -->
    <section class="about-solutions-section py-5">
        <div class="container py-4">
            <div class="row align-items-center g-5">
                <!-- left Image -->
                <div class="col-lg-6">
                    <div class="about-solutions-img-wrapper">
                        <img src="assets/images/indian_workers.png" alt="Indian Facility Management Workers" class="img-fluid about-solutions-img">
                    </div>
                </div>
                <!-- right Content -->
                <div class="col-lg-6">
                    <div class="about-solutions-content">
                        <div class="about-solutions-subtitle-wrapper mb-3">
                            <span class="about-solutions-subtitle text-uppercase">Delivering Excellence Across India</span>
                        </div>
                        <h2 class="about-solutions-title mb-4">Comprehensive Facility<br>Management Solutions.</h2>
                        <p class="about-solutions-desc mb-4">
                            Aryadi Business Pvt. Ltd. provides end-to-end facility management and engineering services that keep your operations running smoothly. Our skilled professionals deliver quality workmanship, timely execution, and long-term value across every project.
                        </p>
                        
                        <!-- Checklist -->
                        <ul class="about-solutions-list mb-5">
                            <li>
                                <i class="bi bi-check-lg list-check-icon"></i>
                                <span>Integrated facility management including housekeeping, security & daily operations.</span>
                            </li>
                            <li>
                                <i class="bi bi-check-lg list-check-icon"></i>
                                <span>Technical maintenance for electrical, HVAC, plumbing & fire safety systems.</span>
                            </li>
                            <li>
                                <i class="bi bi-check-lg list-check-icon"></i>
                                <span>Civil construction, renovation & interior fit-out projects for commercial spaces.</span>
                            </li>
                            <li>
                                <i class="bi bi-check-lg list-check-icon"></i>
                                <span>Reliable procurement, repair services & Annual Maintenance Contracts (AMC).</span>
                            </li>
                        </ul>

                        <!-- Button -->
                        <div>
                            <a href="services.php" class="btn-solutions-orange">
                                View More</span><i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>

                
            </div>
        </div>
    </section>

    <!-- ==========================================
         VIDEO PROMO SECTION
         ========================================== -->
         
    <section class="video-promo-section py-5">
         <!-- Carousel Custom Controls -->
                <div class="carousel-controls-wrapper d-flex gap-2">
                    <button class="btn btn-carousel-nav btn-carousel-prev" type="button">
                        <i class="bi bi-chevron-left me-1"></i> Prev
                    </button>
                    <button class="btn btn-carousel-nav btn-carousel-next" type="button">
                        Next <i class="bi bi-chevron-right ms-1"></i>
                    </button>
                </div>
        <div class="container py-5 text-center position-relative z-index-2">
            <span class="watermark-text">WATCH OUR VIDEO</span>
            
            <div class="video-promo-content mx-auto max-width-700">
                <div class="video-subtitle-wrapper mb-3">
                    <span class="video-subtitle text-uppercase">See How We Work</span>
                </div>
                <h2 class="video-title mb-4">Discover Our Facility Management Expertise</h2>
                <p class="video-desc mb-5 text-white-50">
                    Watch how Aryadi Business Pvt. Ltd. delivers reliable facility management, technical maintenance, and infrastructure solutions across commercial and industrial properties. From concept to completion, our team ensures quality at every step.
                </p>
                
                <!-- Play Button Triggering Bootstrap Modal -->
                <!--<div class="play-btn-wrapper">
                    <button class="video-play-btn" data-bs-toggle="modal" data-bs-target="#videoModal" aria-label="Play Video">
                        <i class="bi bi-play-fill"></i>
                    </button>
                </div>-->
            </div>
        </div>
    </section>

    <!-- Video Modal -->
    <div class="modal fade video-modal-container" id="videoModal" tabindex="-1" aria-labelledby="videoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content bg-dark border-0">
                <div class="modal-header border-0 p-2 d-flex justify-content-end">
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="ratio ratio-16x9">
                        <iframe id="promoVideoFrame" src="https://www.youtube.com/embed/dQw4w9WgXcQ?enablejsapi=1" title="Watch our video" allowfullscreen></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Video Stop Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const videoModal = document.getElementById('videoModal');
            const videoFrame = document.getElementById('promoVideoFrame');
            
            videoModal.addEventListener('hide.bs.modal', function() {
                // Reset src to stop the video playback when modal closes
                const currentSrc = videoFrame.src;
                videoFrame.src = '';
                videoFrame.src = currentSrc;
            });
        });
    </script>

    <!-- ==========================================
         TESTIMONIALS SECTION (OWL CAROUSEL)
         ========================================== -->
    <section class="testimonials-section py-5">
        <div class="container py-4">
            <!-- Section Header + Controls -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-5 gap-4">
                <div class="position-relative section-header text-start max-width-600">
                    <span class="testimonials-subtitle text-uppercase">Client Testimonials</span>
                    <h2 class="testimonials-title">What Our Clients Say</h2>
                    <div class="testimonials-desc mt-3">
                        Trusted by leading businesses across India, our clients value our commitment to quality workmanship, timely delivery, and reliable facility management solutions.
                    </div>
                </div>
                
            </div>

            <!-- Testimonials Owl Carousel -->
            <div id="testimonialOwlCarousel" class="owl-carousel owl-theme">
                <!-- Item 1: Maggie Architek -->
                <div class="item">
                    <div class="testimonial-card">
                        <div class="testimonial-header d-flex justify-content-between align-items-center mb-4">
                            <div class="client-info-wrapper d-flex align-items-center gap-3">
                                <!--<div class="client-img-box">
                                    <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&h=150&fit=crop" alt="Rajesh Sharma" class="client-img">
                                </div>-->
                                <div class="client-meta">
                                    <h4 class="client-name">Sanjay Rai</h4>
                                    <span class="client-role">Techxpert Facilities India Private Limited</span>
                                </div>
                            </div>
                            <div class="client-rating">
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                            </div>
                        </div>
                        <div class="testimonial-body d-flex gap-3">
                            <div class="quote-icon-box">
                                <i class="bi bi-quote"></i>
                            </div>
                            <p class="testimonial-text">
                                Aryadi Business has been managing our facility for over 2 years now. Their technical team is highly responsive, and the AMC coverage ensures zero downtime for our HVAC and electrical systems. Truly reliable.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Item 2: Cindy Wilkins -->
                <div class="item">
                    <div class="testimonial-card">
                        <div class="testimonial-header d-flex justify-content-between align-items-center mb-4">
                            <div class="client-info-wrapper d-flex align-items-center gap-3">
                               <!-- <div class="client-img-box">
                                    <img src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150&h=150&fit=crop" alt="Amit Verma" class="client-img">
                                </div>-->
                                <div class="client-meta">
                                    <h4 class="client-name">Dinesh Lal Yadav</h4>
                                    <span class="client-role">Techxpert Facilities India Private Limited</span>
                                </div>
                            </div>
                            <div class="client-rating">
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                            </div>
                        </div>
                        <div class="testimonial-body d-flex gap-3">
                            <div class="quote-icon-box">
                                <i class="bi bi-quote"></i>
                            </div>
                            <p class="testimonial-text">
                                We hired Aryadi for a complete office renovation and interior fit-out project. The civil work quality was outstanding, they delivered on time, and the procurement support saved us significant costs. Highly recommended.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Item 3: Jane Smith -->
                <div class="item">
                    <div class="testimonial-card">
                        <div class="testimonial-header d-flex justify-content-between align-items-center mb-4">
                            <div class="client-info-wrapper d-flex align-items-center gap-3">
                                <!--<div class="client-img-box">
                                    <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&h=150&fit=crop" alt="Priya Nair" class="client-img">
                                </div>-->
                                <div class="client-meta">
                                    <h4 class="client-name">Ajit Pandey</h4>
                                    <span class="client-role">Techxpert Facilities India Private Limited</span>
                                </div>
                            </div>
                            <div class="client-rating">
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                            </div>
                        </div>
                        <div class="testimonial-body d-flex gap-3">
                            <div class="quote-icon-box">
                                <i class="bi bi-quote"></i>
                            </div>
                            <p class="testimonial-text">
                                Their integrated facility management team handles our housekeeping, security, and technical maintenance seamlessly. The plumbing and fire safety systems are always in top condition. Excellent service quality.
                            </p>
                        </div>
                    </div>
                </div>

                <!--<!-- Item 4: Sarah Jenkins 
                <div class="item">
                    <div class="testimonial-card">
                        <div class="testimonial-header d-flex justify-content-between align-items-center mb-4">
                            <div class="client-info-wrapper d-flex align-items-center gap-3">
                            <!--<div class="client-img-box">
                                    <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&h=150&fit=crop" alt="Sunita Patel" class="client-img">
                                </div>
                                <div class="client-meta">
                                    <h4 class="client-name">Sunita Patel</h4>
                                    <span class="client-role">Property Manager, Skyline Realty</span>
                                </div>
                            </div>
                            <div class="client-rating">
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                            </div>
                        </div>
                        <div class="testimonial-body d-flex gap-3">
                            <div class="quote-icon-box">
                                <i class="bi bi-quote"></i>
                            </div>
                            <p class="testimonial-text">
                                We signed an AMC with Aryadi for our residential complex and the results have been phenomenal. Preventive maintenance, quick repairs, and their procurement team sources quality materials at competitive prices.
                            </p>
                        </div>
                    </div>
                </div>-->

           
        </div>
    </section>

    <!-- ==========================================
         COUNTERS / STATISTICS SECTION
         ========================================== -->
    <section class="counters-section py-5 position-relative">
        <div class="container py-4 z-index-2 position-relative">
            <div class="row text-center text-white g-4">
                <!-- Counter 1 -->
                <div class="col-lg-3 col-md-6 counter-item">
                    <div class="counter-icon-box">
                        <svg width="60" height="60" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 54h52" />
                            <path d="M12 54V28l12 8V22l12 8V16h16v38H12z" />
                            <rect x="18" y="42" width="6" height="6" />
                            <rect x="28" y="42" width="6" height="6" />
                            <rect x="38" y="42" width="6" height="6" />
                            <circle cx="48" cy="24" r="5" />
                            <path d="M48 16v3M48 29v3M41 24h3M53 24h3" />
                        </svg>
                    </div>
                    <div class="counter-number mt-2" data-target="500">0</div>
                    <div class="counter-label text-uppercase">Projects Completed</div>
                </div>

                <!-- Counter 2 -->
                <div class="col-lg-3 col-md-6 counter-item">
                    <div class="counter-icon-box">
                        <svg width="60" height="60" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="10" y="44" width="30" height="10" rx="5" />
                            <circle cx="16" cy="49" r="2" fill="currentColor" />
                            <circle cx="25" cy="49" r="2" fill="currentColor" />
                            <circle cx="34" cy="49" r="2" fill="currentColor" />
                            <path d="M16 44V26h16v18" />
                            <path d="M24 26V32h8" />
                            <path d="M32 32l16-16 10 8" />
                            <path d="M58 24l-6 6h-6v-6z" />
                        </svg>
                    </div>
                    <div class="counter-number mt-2" data-target="200">0</div>
                    <div class="counter-label text-uppercase">Skilled Workers</div>
                </div>

                <!-- Counter 3 -->
                <div class="col-lg-3 col-md-6 counter-item">
                    <div class="counter-icon-box">
                        <svg width="60" height="60" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="10" y="14" width="44" height="30" rx="2" />
                            <path d="M14 20h36v4H14z" fill="currentColor" fill-opacity="0.2" />
                            <path d="M20 38h16V22L20 38z" />
                            <path d="M24 34h8v-8L24 34z" />
                            <path d="M14 48h36M18 48v4M26 48v4M34 48v4M42 48v4" />
                        </svg>
                    </div>
                    <div class="counter-number mt-2" data-target="150">0</div>
                    <div class="counter-label text-uppercase">Satisfied Clients</div>
                </div>

                <!-- Counter 4 -->
                <div class="col-lg-3 col-md-6 counter-item">
                    <div class="counter-icon-box">
                        <svg width="60" height="60" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="32" cy="32" r="8" />
                            <path d="M32 20v4M32 40v4M20 32h4M40 32h4M23 23l3 3M41 41l3 3M23 41l3-3M41 23l3-3" />
                            <circle cx="18" cy="18" r="5" />
                            <circle cx="46" cy="46" r="5" />
                        </svg>
                    </div>
                    <div class="counter-number mt-2" data-target="100">0</div>
                    <div class="counter-label text-uppercase">Active AMC Contracts</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ==========================================
         OUR CLIENTS SECTION
         ========================================== -->
    <section class="clients-grid-section py-5">
        <div class="container text-center">
            <span class="section-subtitle">| Trusted Partnerships Across Industries</span>
            <h2 class="section-title text-uppercase">Our Clients</h2>
            <p class="section-desc"><center>
                We proudly serve leading corporates, IT parks, healthcare facilities, manufacturing units, and commercial complexes with reliable facility management and engineering solutions.
    </center> </p>

            <div class="row g-4 justify-content-center">
                <!-- Client 1 -->
                <!--<div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="client-logo-card">
                        <div class="client-logo-svg-wrapper">
                            <!-- Circular Building Logo 
                            <svg class="client-logo-svg" width="160" height="90" viewBox="0 0 160 90" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="80" cy="30" r="24" fill="none" stroke="#0d2c5c" stroke-width="2.5"/>
                                <path d="M72 45h16V22l-6-4-10 6v21z" />
                                <rect x="74" y="26" width="3" height="15" />
                                <rect x="80" y="22" width="3" height="19" />
                                <rect x="84" y="30" width="2" height="10" />
                                <path class="accent-fill" d="M64 32h6v12h-6z" />
                                <text x="80" y="68" font-family="'Poppins', sans-serif" font-size="11" font-weight="700" text-anchor="middle" letter-spacing="1">TECHPARK</text>
                                <text x="80" y="78" font-family="'Poppins', sans-serif" font-size="5.5" font-weight="600" text-anchor="middle" letter-spacing="0.5" fill="#64748b">IT PARK &amp; COMMERCIAL SPACES</text>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Client 2 
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="client-logo-card">
                        <div class="client-logo-svg-wrapper">
                            <!-- Roof/Mountain Peak Style Logo 
                            <svg class="client-logo-svg" width="160" height="90" viewBox="0 0 160 90" xmlns="http://www.w3.org/2000/svg">
                                <path d="M56 38l24-18 14 10.5M80 20l24 18M70 38v8h20v-8" fill="none" stroke="#0d2c5c" stroke-width="3" stroke-linecap="round"/>
                                <path d="M78 30h4v16h-4z" />
                                <circle cx="70" cy="24" r="2.5" class="accent-fill" />
                                <circle cx="90" cy="24" r="2.5" class="accent-fill" />
                                <text x="80" y="68" font-family="'Poppins', sans-serif" font-size="11" font-weight="700" text-anchor="middle" letter-spacing="1">GREENFIELD</text>
                                <text x="80" y="78" font-family="'Poppins', sans-serif" font-size="5.5" font-weight="600" text-anchor="middle" letter-spacing="0.5" fill="#64748b">INDUSTRIAL MANUFACTURING</text>
                            </svg>
                        </div>
                    </div>
                </div>-->

                <!-- Client 3 -->
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="client-logo-card">
                        <div class="client-logo-svg-wrapper">
                            <!-- Official GeBBS Healthcare Solutions Logo -->
                            <svg class="original-client-logo" style="max-width: 140px; height: 60px;" viewBox="0 0 95 61" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M11.907 27.526v-1.4h9.29v1.4h-1.555c-.61 0-1.015.098-1.217.294-.199.195-.302.572-.302 1.124v7.379h-1.088l-1.113-2.752a5.656 5.656 0 0 1-2.34 2.057c-.973.463-2.109.694-3.425.694-2.975 0-5.39-1.023-7.244-3.068C1.058 31.21.131 28.535.13 25.229c0-3.3.902-5.986 2.708-8.06 1.806-2.074 4.136-3.11 6.991-3.11a9.058 9.058 0 0 1 3.102.522 7.228 7.228 0 0 1 2.516 1.537l1.09-1.844h1.141l.318 8.334h-1.163c-.52-2.43-1.322-4.23-2.407-5.39-1.085-1.161-2.498-1.743-4.24-1.743-2.119 0-3.691.799-4.716 2.397-1.025 1.598-1.538 4.05-1.538 7.357 0 3.33.52 5.787 1.559 7.37 1.037 1.587 2.654 2.383 4.836 2.383 1.507 0 2.675-.388 3.505-1.165.83-.777 1.245-1.876 1.246-3.296v-1.461c0-.61-.104-1.02-.314-1.224-.21-.205-.625-.309-1.243-.309h-1.614zm31.186-2.038v7.552c0 .563.108.932.324 1.111.216.18.639.273 1.277.273h2.088c1.774 0 3.053-.343 3.834-1.028.782-.686 1.174-1.805 1.176-3.355 0-1.617-.469-2.793-1.402-3.49-.933-.696-2.494-1.063-4.666-1.063h-2.63zm-6.209 10.31v-1.375h1.517c.623 0 1.037-.097 1.248-.294.21-.197.313-.572.313-1.121V17.43c0-.54-.105-.913-.313-1.108-.209-.196-.625-.294-1.248-.294h-1.517V14.64h11.072c2.058 0 3.625.44 4.703 1.322 1.078.882 1.617 2.164 1.617 3.845 0 1.268-.413 2.311-1.238 3.131-.825.82-2.05 1.408-3.674 1.764 2.014.256 3.516.836 4.51 1.728.995.892 1.492 2.128 1.492 3.69 0 1.802-.675 3.199-2.026 4.19-1.352.992-3.262 1.488-5.732 1.489l-10.724-.002zm6.21-18.54v6.938h2.343c2.04 0 3.457-.31 4.25-.929.794-.62 1.193-1.688 1.197-3.208 0-1.458-.346-2.498-1.045-3.138-.7-.639-1.844-.953-3.445-.953h-1.841c-.57 0-.959.083-1.158.254-.2.17-.302.513-.302 1.036zm20.475 8.23v7.552c0 .563.11.932.325 1.111.215.18.638.273 1.28.273h2.088c1.764 0 3.053-.344 3.835-1.028.782-.685 1.168-1.81 1.168-3.355 0-1.617-.469-2.793-1.398-3.49-.928-.696-2.498-1.059-4.664-1.059l-2.634-.004zM57.36 35.797v-1.374h1.52c.62 0 1.037-.097 1.246-.294.209-.197.312-.572.312-1.121V17.43c0-.54-.105-.913-.312-1.108-.207-.196-.626-.294-1.246-.294h-1.52V14.64h11.074c2.057 0 3.625.44 4.703 1.322 1.077.882 1.616 2.164 1.616 3.845 0 1.268-.412 2.311-1.234 3.131-.823.82-2.05 1.408-3.682 1.764 2.01.256 3.514.836 4.509 1.728.995.892 1.496 2.128 1.496 3.69 0 1.802-.677 3.199-2.031 4.19-1.354.992-3.265 1.488-5.731 1.489l-10.72-.003zm6.21-18.54v6.939h2.35c2.04 0 3.458-.31 4.254-.929.795-.62 1.194-1.688 1.197-3.208 0-1.458-.348-2.504-1.046-3.138-.69-.634-1.844-.953-3.448-.953h-1.845c-.57 0-.957.083-1.158.254-.202.17-.305.513-.305 1.036zm17.473 16.859-1.23 2.034h-1.029l-.147-8.493h1.2c.447 2.402 1.27 4.226 2.468 5.473 1.2 1.246 2.743 1.87 4.631 1.873 1.57 0 2.78-.371 3.648-1.11.867-.74 1.3-1.764 1.3-3.08a3.727 3.727 0 0 0-.333-1.616 3.086 3.086 0 0 0-.953-1.167c-.541-.38-1.706-.8-3.492-1.26a57.126 57.126 0 0 1-1.312-.342c-2.793-.752-4.65-1.578-5.571-2.476-.923-.897-1.383-2.17-1.383-3.833 0-1.809.57-3.258 1.694-4.338 1.125-1.08 2.646-1.617 4.556-1.617a8.572 8.572 0 0 1 2.992.512c.92.342 1.764.863 2.483 1.533l1.134-1.764h1.09l.325 7.607H91.97c-.555-2.172-1.409-3.808-2.561-4.906-1.152-1.098-2.591-1.649-4.316-1.652-1.283 0-2.286.306-2.99.922-.703.616-1.06 1.489-1.06 2.626 0 1.734 1.77 3.037 5.31 3.908.331.072.587.135.768.183 1.651.41 2.816.77 3.492 1.08a6.734 6.734 0 0 1 1.747 1.128 5.229 5.229 0 0 1 1.314 1.96 6.8 6.8 0 0 1 .457 2.547c0 2.057-.628 3.649-1.893 4.784-1.265 1.134-3.052 1.693-5.36 1.693a9.11 9.11 0 0 1-3.233-.58 7.324 7.324 0 0 1-2.598-1.625" fill="#2C2A29"/><path fill-rule="evenodd" clip-rule="evenodd" d="M25.584 27.971h5.438c.413 0 .685-.066.827-.2.143-.133.205-.374.205-.727 0-1.329-.267-2.345-.801-3.048-.532-.707-1.302-1.058-2.303-1.058-1.107 0-1.933.414-2.488 1.245-.556.83-.842 2.09-.87 3.788h-.008zm-.04 1.26c0 .067 0 .164-.011.294-.012.129-.016.23-.016.294 0 1.506.357 2.7 1.058 3.578.701.879 1.655 1.323 2.85 1.323a4.69 4.69 0 0 0 2.677-.787c.776-.526 1.434-1.322 1.967-2.377l1.028.587c-.64 1.37-1.47 2.406-2.51 3.101-1.04.695-2.256 1.029-3.667 1.029-1.879 0-3.407-.668-4.583-2.003-1.177-1.335-1.765-3.081-1.764-5.238 0-2.162.588-3.912 1.764-5.25 1.175-1.339 2.704-2.01 4.586-2.014 1.904 0 3.41.62 4.517 1.858 1.108 1.241 1.66 2.93 1.66 5.077v.528h-9.555zM63.815 2.582c-1.953-.185-5.525-.054-8.956.315-1.23.106-2.45.302-3.65.588-.685.147-2.196.532-2.94.786C45.28 5.3 43.242 5.964 40.745 7.16c-8.912 4.275-15.188 9.643-15.882 11.596-.044.13 4.332-5.22 15.348-8.964 5.85-1.993 12.639-3.98 20.134-3.755 6.613.201 14.424 1.146 19.888 6.584l9.279.132 1.542.107c.576.041-3.116-2.376-6.345-4.169a40.888 40.888 0 0 0-4.328-2.066c-4.094-1.91-9.055-3.327-16.566-4.042z" fill="#398FCC"/><path fill-rule="evenodd" clip-rule="evenodd" d="M3.689 46.71h.32a.382.382 0 0 0 .253-.062.397.397 0 0 0 .065-.267v-1.495H2.64v1.498c0 .132.019.223.067.261.074.055.166.08.257.068h.316v.337H.854v-.337h.322a.391.391 0 0 0 .266-.063.4.4 0 0 0 .062-.268v-3.26a.408.408 0 0 0-.062-.271.378.378 0 0 0-.257-.063H.854v-.334H3.28v.334h-.316a.39.39 0 0 0-.257.063.379.379 0 0 0-.067.272v1.353h1.687v-1.353a.395.395 0 0 0-.066-.272.37.37 0 0 0-.252-.063h-.32v-.334h2.428v.334h-.325a.372.372 0 0 0-.254.063.39.39 0 0 0-.065.272v3.262a.38.38 0 0 0 .065.268c.074.05.164.072.254.062h.325v.336H3.689v-.34zm2.7.337v-.337h.32a.362.362 0 0 0 .257-.068.372.372 0 0 0 .066-.261v-3.258a.395.395 0 0 0-.066-.272.38.38 0 0 0-.257-.063h-.32v-.334h4.15l.054 1.658h-.317c-.11-.491-.294-.833-.537-1.029-.242-.195-.62-.294-1.115-.294a.736.736 0 0 0-.39.063.36.36 0 0 0-.076.278v1.383h.09a.863.863 0 0 0 .617-.197c.166-.194.268-.434.294-.688h.317v2.106H9.16a1.236 1.236 0 0 0-.294-.682.856.856 0 0 0-.617-.197h-.09v1.538a.359.359 0 0 0 .082.276.768.768 0 0 0 .403.068c.54 0 .945-.106 1.214-.322.269-.216.461-.588.588-1.105l.316.026-.083 1.722-4.29-.011zm7.399 0v-.33l.314-.023a.225.225 0 0 0 .139-.037.135.135 0 0 0 .04-.108l-.013-.067a.9.9 0 0 0-.033-.11l-.285-.713h-1.633l-.147.335a.936.936 0 0 0-.066.328.31.31 0 0 0 .147.294c.163.074.34.108.519.102v.329h-1.805v-.33a.797.797 0 0 0 .44-.233c.162-.184.288-.395.374-.623l1.498-3.473h.474l1.764 4.06a.44.44 0 0 0 .147.205.578.578 0 0 0 .27.069v.33l-2.144-.005zm-.628-3.336-.667 1.547h1.293l-.626-1.547zm7.143 3.334H16.05v-.336h.325a.368.368 0 0 0 .259-.068.372.372 0 0 0 .066-.261v-3.257a.388.388 0 0 0-.066-.273.374.374 0 0 0-.253-.062h-.33v-.334h2.518v.334h-.403a.263.263 0 0 0-.323.335v3.233a.356.356 0 0 0 .078.269c.127.058.267.08.405.063.516 0 .901-.103 1.16-.316.259-.213.454-.579.578-1.1l.322.032-.084 1.741zm3.863-2.866h.315l-.105-1.73h-4.19l-.102 1.73h.316c.035-.373.16-.733.366-1.047a.804.804 0 0 1 .667-.381.297.297 0 0 1 .223.063c.046.079.066.17.056.26v3.306a.38.38 0 0 1-.065.267.386.386 0 0 1-.252.062h-.32v.336h2.41v-.336h-.308a.354.354 0 0 1-.258-.068.365.365 0 0 1-.067-.261v-3.304a.397.397 0 0 1 .06-.259.3.3 0 0 1 .226-.065.805.805 0 0 1 .665.381c.205.314.33.674.363 1.048v-.002zm3.373 2.531h.32c.09.01.18-.012.255-.062a.39.39 0 0 0 .061-.267v-1.495h-1.684v1.498c0 .132.02.223.068.261.073.055.166.08.257.068h.316v.337H24.71v-.337h.323c.09.01.181-.012.258-.062a.409.409 0 0 0 .061-.267v-3.261a.417.417 0 0 0-.061-.272.383.383 0 0 0-.258-.063h-.323v-.334h2.422v.334h-.316a.393.393 0 0 0-.257.063.386.386 0 0 0-.068.272v1.353h1.684v-1.353a.38.38 0 0 0-.066-.272.352.352 0 0 0-.25-.063h-.32v-.334h2.428v.334h-.325a.372.372 0 0 0-.253.063.39.39 0 0 0-.064.272v3.262a.256.256 0 0 0 .178.332.258.258 0 0 0 .139-.002h.325v.336h-2.428v-.34zm7.157-1.223a2.12 2.12 0 0 1-.683 1.25c-.38.299-.854.451-1.336.43-.26.001-.52-.036-.769-.11a2.177 2.177 0 0 1-.646-.332 2.29 2.29 0 0 1-.69-.852 2.644 2.644 0 0 1-.233-1.123 2.609 2.609 0 0 1 .146-.903c.104-.279.258-.536.455-.76.214-.24.48-.428.776-.552a2.593 2.593 0 0 1 1-.18c.217-.003.432.026.64.087.196.057.38.148.545.268l.354-.33h.267l.092 1.852-.327.024a2.718 2.718 0 0 0-.577-1.176 1.198 1.198 0 0 0-.905-.398.98.98 0 0 0-.912.482c-.195.32-.29.839-.285 1.555 0 .727.097 1.252.294 1.562.197.31.53.466.988.466.337.009.663-.117.908-.348.275-.282.466-.636.55-1.02l.348.108zm2.948 1.56v-.33l.315-.023a.219.219 0 0 0 .135-.037.138.138 0 0 0 .04-.108l-.01-.067a.696.696 0 0 0-.033-.11l-.285-.713h-1.633l-.147.335c-.02.055-.037.111-.05.168a.813.813 0 0 0-.016.16.312.312 0 0 0 .147.294c.163.074.34.108.519.102v.329h-1.808v-.33a.806.806 0 0 0 .448-.233c.161-.183.287-.394.37-.623l1.496-3.473h.474l1.763 4.06a.44.44 0 0 0 .147.205.588.588 0 0 0 .27.069v.33l-2.142-.005zm-.628-3.336-.667 1.547h1.295l-.628-1.547zm4.67 1.125v1.54a.39.39 0 0 0 .066.26.337.337 0 0 0 .255.073h.343v.336h-2.458v-.336h.347a.342.342 0 0 0 .26-.074.365.365 0 0 0 .068-.258v-3.254a.397.397 0 0 0-.068-.266.362.362 0 0 0-.26-.07h-.34v-.333h2.803c.562 0 .99.1 1.28.294a.986.986 0 0 1 .432.869.97.97 0 0 1-.373.802c-.304.218-.67.333-1.045.326v.041c.22.002.431.078.601.215.181.154.312.359.377.588.055.175.098.353.129.533.062.334.147.496.28.496a.213.213 0 0 0 .196-.147c.05-.153.075-.314.07-.475h.322v.047a1.138 1.138 0 0 1-.28.829 1.03 1.03 0 0 1-.784.294 1.37 1.37 0 0 1-.573-.108.734.734 0 0 1-.347-.323A1.677 1.677 0 0 1 42.86 46v-.252c-.023-.378-.097-.625-.22-.735-.121-.11-.352-.17-.692-.17h-.147l-.114-.007zm0-1.707v1.37l.11.011h.157c.483 0 .818-.064 1.008-.197.19-.132.285-.357.285-.682 0-.325-.08-.533-.236-.663-.158-.129-.431-.188-.83-.188a.91.91 0 0 0-.41.058c-.057.042-.083.138-.083.294v-.003zm3.403 3.918v-.337h.319a.369.369 0 0 0 .258-.068.364.364 0 0 0 .067-.261v-3.258a.263.263 0 0 0-.325-.335h-.319v-.334h4.147l.056 1.658h-.315c-.111-.491-.293-.833-.537-1.029-.244-.195-.622-.294-1.116-.294a.755.755 0 0 0-.392.063.374.374 0 0 0-.08.27v1.384h.093a.862.862 0 0 0 .617-.197c.163-.195.263-.435.287-.688h.326v2.106h-.316a1.247 1.247 0 0 0-.286-.682.855.855 0 0 0-.618-.197h-.102v1.537a.356.356 0 0 0 .083.277.777.777 0 0 0 .404.067c.54 0 .944-.106 1.213-.322.269-.216.464-.587.588-1.105l.317.027-.082 1.722-4.287-.004zm7.04.078-.058-1.848h.326c.138.438.385.833.717 1.15.282.255.65.395 1.03.39.234.013.466-.057.655-.198a.677.677 0 0 0 .236-.545.603.603 0 0 0-.233-.485 3.02 3.02 0 0 0-.8-.259c-.154-.037-.278-.07-.361-.092-.57-.14-.976-.325-1.22-.553a1.192 1.192 0 0 1-.363-.917 1.304 1.304 0 0 1 .456-1.029 1.714 1.714 0 0 1 1.176-.407c.209 0 .417.028.618.085.201.063.393.152.57.265l.32-.31h.28l.078 1.68h-.294a3.176 3.176 0 0 0-.71-1.059 1.284 1.284 0 0 0-.865-.321.976.976 0 0 0-.628.18.62.62 0 0 0-.223.505c0 .316.363.55 1.086.71l.308.067c.254.052.502.127.743.225.146.064.28.154.395.266.131.13.232.287.294.461.071.19.107.393.104.597a1.37 1.37 0 0 1-.448 1.077c-.294.268-.703.398-1.216.398a2.417 2.417 0 0 1-.718-.098 2.011 2.011 0 0 1-.601-.313l-.375.38-.278-.002zm5.43-2.375c0 .7.092 1.215.277 1.549a.967.967 0 0 0 1.7 0c.19-.343.283-.859.283-1.548 0-.69-.093-1.214-.278-1.549a.969.969 0 0 0-1.703 0c-.187.337-.28.854-.28 1.548zm-1.269 0a2.678 2.678 0 0 1 .163-.948c.106-.288.271-.55.485-.769.215-.228.478-.407.769-.523a2.872 2.872 0 0 1 1.974 0c.294.12.559.302.774.535.208.22.37.479.475.763.11.301.166.62.163.942.007.315-.043.628-.147.925a2.108 2.108 0 0 1-.478.735 2.35 2.35 0 0 1-.8.559 2.685 2.685 0 0 1-1.945.009 2.342 2.342 0 0 1-.79-.554 2.245 2.245 0 0 1-.488-.744 2.616 2.616 0 0 1-.147-.93h-.008zm9.422 2.297h-4.258v-.337h.322a.263.263 0 0 0 .328-.19.265.265 0 0 0-.003-.139v-3.258a.399.399 0 0 0-.063-.273.388.388 0 0 0-.257-.062h-.327v-.334h2.518v.334h-.403a.389.389 0 0 0-.256.063.395.395 0 0 0-.066.272v3.233a.348.348 0 0 0 .077.269c.05.044.185.063.405.063.515 0 .898-.103 1.161-.316.263-.213.453-.579.578-1.1l.32.032-.076 1.743zm2.627-4.597v.333h-.324a.39.39 0 0 0-.259.064.4.4 0 0 0-.065.271v2.324c0 .425.089.734.265.926.18.192.46.294.85.294.159.001.316-.023.467-.07a.968.968 0 0 0 .358-.198.813.813 0 0 0 .246-.395c.058-.269.082-.544.07-.819v-1.674c0-.279-.047-.465-.147-.568a.688.688 0 0 0-.514-.155h-.044v-.333h1.753v.333a.734.734 0 0 0-.49.18c-.09.098-.134.282-.134.543v1.726c.007.264-.01.528-.05.789-.034.15-.094.294-.178.423a1.398 1.398 0 0 1-.653.541 2.712 2.712 0 0 1-1.042.175c-.624 0-1.095-.129-1.412-.387-.319-.26-.476-.642-.476-1.153v-2.497a.263.263 0 0 0-.325-.335h-.325v-.334l2.43-.004zm6.93 1.731a2.298 2.298 0 0 0-.362-1.048.81.81 0 0 0-.667-.38.309.309 0 0 0-.226.064.407.407 0 0 0-.06.259v3.305a.363.363 0 0 0 .068.261c.074.055.165.08.256.068h.31v.337h-2.417v-.337h.323a.385.385 0 0 0 .251-.062.39.39 0 0 0 .064-.267v-3.305a.415.415 0 0 0-.058-.26.294.294 0 0 0-.222-.064.805.805 0 0 0-.665.381c-.206.315-.331.674-.366 1.048h-.318l.099-1.731h4.197l.1 1.731h-.307zm3.077-1.731v.333h-.363a.372.372 0 0 0-.253.064.397.397 0 0 0-.064.271v3.263a.397.397 0 0 0 .063.267.38.38 0 0 0 .254.062h.363v.337h-2.498v-.337h.35a.368.368 0 0 0 .261-.068.371.371 0 0 0 .065-.261v-3.258a.396.396 0 0 0-.065-.272.386.386 0 0 0-.26-.063h-.351v-.334l2.498-.004zm1.665 2.3c0 .7.094 1.217.276 1.549a.97.97 0 0 0 1.702 0c.188-.343.282-.86.28-1.548 0-.689-.092-1.205-.275-1.549a.97.97 0 0 0-1.707 0c-.182.337-.276.854-.276 1.548zm-1.271 0a2.714 2.714 0 0 1 .164-.948 2.262 2.262 0 0 1 1.255-1.292 2.872 2.872 0 0 1 1.974 0 a2.24 2.24 0 0 1 1.248 1.298c.212.603.217 1.26.014 1.867a2.15 2.15 0 0 1-.477.735 2.37 2.37 0 0 1-.803.559 2.68 2.68 0 0 1-1.942.009a2.301 2.301 0 0 1-.79-.554 2.22 2.22 0 0 1-.493-.744 2.55 2.55 0 0 1-.147-.93h-.003zm5.146 2.297v-.33a.818.818 0 0 0 .537-.196c.094-.108.147-.315.147-.628V43.13l-.122-.123a.638.638 0 0 0-.23-.184.654.654 0 0 0-.257-.04h-.069v-.333H85.5l2.423 2.864v-1.717c0-.332-.053-.546-.154-.653-.102-.108-.306-.16-.603-.16v-.334h1.872v.333c-.255.012-.43.07-.52.177-.09.107-.135.317-.135.637v3.513h-.401l-3.016-3.546v2.334c0 .318.053.533.156.645.103.111.294.172.588.179v.33l-1.823-.005zm5.745.078-.06-1.848h.326c.139.438.386.833.719 1.15.28.256.648.396 1.028.39a.999.999 0 0 0 .656-.198.682.682 0 0 0 .237-.545.662.662 0 0 0-.062-.28.588.588 0 0 0-.175-.205 2.937 2.937 0 0 0-.797-.259c-.154-.037-.276-.07-.363-.092-.571-.14-.975-.325-1.22-.553a1.202 1.202 0 0 1-.364-.917 1.298 1.298 0 0 1 .46-1.029c.326-.28.746-.426 1.176-.407.21-.001.418.028.62.085.2.061.392.148.57.26l.32-.311h.283l.078 1.68h-.305a3.177 3.177 0 0 0-.708-1.058 1.282 1.282 0 0 0-.864-.322.983.983 0 0 0-.63.18.62.62 0 0 0-.221.505c0 .316.362.55 1.085.71l.308.067c.255.051.505.127.745.225.14.067.268.158.378.269.131.13.232.287.294.461.072.19.107.393.104.597a1.37 1.37 0 0 1-.45 1.077c-.293.268-.7.398-1.21.398a2.41 2.41 0 0 1-.72-.098 2.025 2.025 0 0 1-.605-.313l-.374.38h-.259zM87.98 2.99c0-.285.05-.567.148-.834a2.34 2.34 0 0 1 .414-.735A2.483 2.483 0 0 1 90.48.504c.279.002.555.051.817.147a2.488 2.488 0 0 1 1.417 1.272c.16.335.243.701.244 1.072a2.418 2.418 0 0 1-.196.945 2.46 2.46 0 0 1-.543.819c-.226.23-.496.41-.794.532a2.482 2.482 0 0 1-3.443-2.301zm2.5-2.236a2.232 2.232 0 0 0-2.244 2.237 2.156 2.156 0 0 0 .647 1.578 2.183 2.183 0 0 0 1.589.653 2.235 2.235 0 0 0 1.58-3.81 2.235 2.235 0 0 0-1.577-.658h.004zm-1.086.748V4.39h.525v-1.05h.512c.283 0 .484.08.604.241.119.162.192.428.197.809h.568v-.147a1.37 1.37 0 0 0-.164-.745.921.921 0 0 0-.548-.368c.212-.041.407-.143.562-.294a.692.692 0 0 0 .194-.497.699.699 0 0 0-.316-.621 1.677 1.677 0 0 0-.931-.215h-1.203zm.525.463v.911h.504c.217.014.433-.03.628-.126a.421.421 0 0 0 .207-.387.318.318 0 0 0-.184-.302 1.359 1.359 0 0 0-.588-.096h-.567z" fill="#2C2A29"/><path d="M18.414 54.418v.06l-.008.084-.025.094-.037.088-.053.066-.066.026h-1.76l-.327 1.634h1.664l.066.03.019.087v.07l-.013.082-.025.09-.037.086-.053.062-.066.023h-1.664l-.401 2.003-.024.05-.06.036a.576.576 0 0 1-.101.023 1.163 1.163 0 0 1-.304 0 .412.412 0 0 1-.094-.023l-.046-.036v-.05l.89-4.451a.293.293 0 0 1 .292-.26h2.147l.07.035.016.091zM21.686 56.771a3.075 3.075 0 0 1-.209 1.111 2.651 2.651 0 0 1-.27.501 1.848 1.848 0 0 1-.885.685 1.878 1.878 0 0 1-.628.1 1.927 1.927 0 0 1-.611-.084 1.044 1.044 0 0 1-.416-.248 1 1 0 0 1-.242-.401 1.748 1.748 0 0 1-.08-.54c.001-.188.018-.375.052-.56a2.583 2.583 0 0 1 .426-1.049 1.86 1.86 0 0 1 .383-.411 1.763 1.763 0 0 1 1.128-.38c.207-.005.413.026.61.09.157.05.302.134.421.248.112.114.196.252.244.403.054.173.08.354.077.535zm-.647.037a1.233 1.233 0 0 0-.04-.335.603.603 0 0 0-.127-.25.612.612 0 0 0-.238-.159 1.139 1.139 0 0 0-.762.024c-.119.053-.228.126-.323.216-.099.093-.183.2-.25.319a2.14 2.14 0 0 0-.175.386 2.75 2.75 0 0 0-.135.85 1.31 1.31 0 0 0 .04.335c.025.093.07.179.133.252.065.071.146.125.236.158a.96.96 0 0 0 .354.057.975.975 0 0 0 .405-.08c.12-.053.23-.127.324-.217a1.48 1.48 0 0 0 .248-.319 2.13 2.13 0 0 0 .176-.39c.043-.14.077-.28.1-.424a2.62 2.62 0 0 0 .034-.423zM24.761 55.667v.056l-.013.099-.02.116-.039.108-.048.086-.053.034-.065-.015-.078-.031-.098-.03a.569.569 0 0 0-.13-.016.542.542 0 0 0-.293.112 1.52 1.52 0 0 0-.325.301 2.5 2.5 0 0 0-.294.457c-.09.182-.156.375-.195.573l-.294 1.49-.025.05-.056.035a.362.362 0 0 1-.097.022 1.47 1.47 0 0 1-.294 0l-.09-.022-.04-.036v-.05l.663-3.348.022-.047.056-.038.093-.019c.042-.002.085-.002.128 0a.996.996 0 0 1 .129 0l.076.02.036.038v.046l-.124.61c.06-.103.128-.201.205-.294.074-.09.158-.174.25-.246.092-.069.19-.125.295-.17a.746.746 0 0 1 .423-.052c.043 0 .084.013.12.022l.1.03.067.039.008.07zM30.081 55.634l-.01.083-.02.072a16.138 16.138 0 0 1-.68 1.645c-.135.275-.283.54-.442.807-.153.262-.32.517-.5.762l-.064.062-.09.035a.592.592 0 0 1-.113.018h-.147c-.054 0-.108-.003-.162-.009a.4.4 0 0 1-.104-.022l-.056-.04-.016-.064-.248-2.451-1.127 2.439-.049.07-.075.043a.508.508 0 0 1-.122.025c-.05 0-.108.009-.176.009-.068 0-.13 0-.18-.009a.566.566 0 0 1-.12-.022l-.069-.04-.02-.064-.355-3.174-.007-.067v-.054l.015-.072.05-.044a.308.308 0 0 1 .097-.022h.314l.087.019.04.035.016.059.263 2.763v.034l.013-.034 1.265-2.763.035-.054.053-.04.089-.02h.294l.09.02.05.034.017.051.272 2.753v.03l.019-.03a9.27 9.27 0 0 0 .672-1.271 15.485 15.485 0 0 0 .564-1.47l.029-.059.06-.035a.444.444 0 0 1 .103-.02h.307l.09.014.038.026.01.042zM33.059 59.006a.117.117 0 0 1-.081.088.899.899 0 0 1-.347.02l-.078-.02-.038-.033v-.055l.12-.63a.881.881 0 0 1-.176.259 1.551 1.551 0 0 1-.294.26c-.12.081-.248.148-.385.197a1.206 1.206 0 0 1-.43.077c-.17.006-.34-.03-.494-.104a.833.833 0 0 1-.309-.275 1.003 1.003 0 0 1-.154-.383 2.29 2.29 0 0 1-.043-.441 3.403 3.403 0 0 1 .391-1.546c.09-.17.204-.327.337-.466.131-.136.286-.248.457-.33.184-.085.385-.127.588-.124.197-.007.392.04.566.132.163.094.306.22.421.369l.072-.34a.12.12 0 0 1 .084-.085.714.714 0 0 1 .219-.026h.125l.08.019.036.038v.047l-.667 3.352zm-.115-2.47a1.35 1.35 0 0 0-.378-.385.83.83 0 0 0-.454-.128.706.706 0 0 0-.363.095c-.112.065-.212.15-.293.25-.086.11-.158.23-.215.356-.06.133-.11.27-.147.41a3.103 3.103 0 0 0-.112.783c0 .087.007.173.02.259.014.082.04.161.079.235a.44.44 0 0 0 .417.235.862.862 0 0 0 .412-.113c.144-.083.274-.189.383-.314a2.24 2.24 0 0 0 .316-.472c.094-.19.163-.39.204-.598l.131-.613zM36.755 55.667v.056l-.015.099-.022.116-.038.108-.046.086-.053.034-.066-.015-.076-.031-.099-.03a.576.576 0 0 0-.13-.016.535.535 0 0 0-.294.112 1.425 1.425 0 0 0-.325.301c-.242.3-.409.652-.488 1.03l-.294 1.488-.028.05-.054.035a.378.378 0 0 1-.099.022 1.412 1.412 0 0 1-.294 0l-.09-.022-.04-.035v-.05l.664-3.348.022-.047.056-.038.09-.02c.044-.002.087-.002.13 0a.987.987 0 0 1 .127 0l.079.02.034.038v.047l-.124.61c.059-.104.127-.203.203-.294.075-.092.16-.175.254-.247.088-.069.184-.125.287-.169a.75.75 0 0 1 .422-.053c.04.005.08.013.12.022l.099.03.064.04.024.071zM39.581 59.006a.107.107 0 0 1-.078.088.625.625 0 0 1-.223.028.899.899 0 0 1-.125-.009l-.078-.019-.04-.034v-.054l.12-.623a.905.905 0 0 1-.176.26 1.66 1.66 0 0 1-.678.451 1.29 1.29 0 0 1-.812.018.838.838 0 0 1-.272-.154.758.758 0 0 1-.177-.225 1.454 1.454 0 0 1-.16-.536 2.609 2.609 0 0 1-.012-.253 3.346 3.346 0 0 1 .396-1.546c.091-.168.204-.322.337-.459.133-.132.287-.24.456-.32.184-.085.385-.127.587-.122a1.143 1.143 0 0 1 .763.28c.062.054.12.114.174.177l.38-1.895.02-.048.057-.038.095-.022a1.078 1.078 0 0 1 .294 0l.091.022.043.038v.048l-.982 4.947zm-.117-2.459a1.479 1.479 0 0 0-.377-.39.824.824 0 0 0-.457-.134.702.702 0 0 0-.354.091 1.05 1.05 0 0 0-.284.242c-.087.107-.16.224-.216.35-.06.132-.11.268-.147.407a3.33 3.33 0 0 0-.088.415 2.51 2.51 0 0 0-.03.373c-.012.192.03.384.123.553a.432.432 0 0 0 .394.192.85.85 0 0 0 .41-.112 1.47 1.47 0 0 0 .385-.308 2.331 2.331 0 0 0 .52-1.055l.12-.623z" fill="#398FCC"/><path d="M57.006 54.568a.622.622 0 0 1 0 .122l-.023.084-.042.047-.053.015h-1.365v4.167l-.013.05-.053.036a.504.504 0 0 1-.098.023 1.147 1.147 0 0 1-.303 0 .506.506 0 0 1-.099-.023l-.053-.036-.014-.05v-4.167h-1.352l-.055-.015-.038-.047-.025-.084a.81.81 0 0 1 0-.248l.025-.087.038-.047.055-.016h3.343l.053.016.041.047.024.087a.651.651 0 0 1 0 .126M60.51 59.006l-.015.05-.049.035-.09.022a1.389 1.389 0 0 1-.295 0l-.092-.022-.049-.035-.014-.05V57.05a1.927 1.927 0 0 0-.045-.46.98.98 0 0 0-.13-.303.568.568 0 0 0-.22-.193.712.712 0 0 0-.315-.067.8.8 0 0 0-.462.164c-.18.139-.343.3-.483.48v2.336l-.014.05-.047.035-.092.022a1.416 1.416 0 0 1-.294 0l-.094-.022-.049-.035-.011-.05v-4.963l.011-.053.049-.036.094-.02c.049-.007.098-.01.147-.008.049-.001.098.002.147.007l.093.02.046.037.014.053v2.002c.154-.166.333-.305.532-.413.166-.088.351-.134.54-.135.193-.006.386.032.563.111.145.072.27.176.368.305.098.13.167.28.204.44.044.202.064.408.06.615l-.008 2.038zM62.311 54.534a.401.401 0 0 1-.082.294.441.441 0 0 1-.301.08.425.425 0 0 1-.294-.078.385.385 0 0 1-.081-.287.393.393 0 0 1 .084-.294.43.43 0 0 1 .294-.08.442.442 0 0 1 .294.077c.063.084.092.19.079.294l.007-.006zm-.078 4.478-.016.05-.047.035-.092.022a1.363 1.363 0 0 1-.294 0l-.094-.022-.052-.035-.011-.05v-3.35l.011-.048.052-.036.094-.02a1.017 1.017 0 0 1 .294 0l.092.02.047.036.017.049v3.35zM66.251 59.006l-.013.05-.049.036-.092.022a1.387 1.387 0 0 1-.294 0l-.094-.022-.047-.036-.015-.05V57.05a1.834 1.834 0 0 0-.047-.46.98.98 0 0 0-.128-.303.588.588 0 0 0-.22-.193.723.723 0 0 0-.316-.067.793.793 0 0 0-.46.164 2.534 2.534 0 0 0-.482.48v2.336l-.016.05-.049.036-.09.022a1.36 1.36 0 0 1-.295 0l-.094-.022-.047-.036-.015-.05v-3.343l.012-.05.044-.037.087-.022h.272l.084.022.042.037.014.05v.44a2.1 2.1 0 0 1 .562-.462 1.21 1.21 0 0 1 .566-.147c.194-.007.386.031.563.112.14.072.26.176.353.302.098.131.168.282.204.441.043.201.063.406.06.612v2.045zM70.115 59.003l-.015.05-.05.038a.34.34 0 0 1-.097.023 1.587 1.587 0 0 1-.16.007h-.166a.521.521 0 0 1-.11-.018l-.072-.038-.052-.056-1.412-1.85v1.853l-.012.05-.05.035-.092.022a1.36 1.36 0 0 1-.294 0l-.096-.022-.048-.035-.013-.05v-4.969l.013-.053.048-.036.096-.022c.049-.003.098-.003.147 0a1.44 1.44 0 0 1 .147 0l.092.022.05.036.012.053v3.025l1.265-1.39.062-.062.08-.04a.41.41 0 0 1 .111-.022 1.44 1.44 0 0 1 .147 0c.052-.003.105-.003.157 0a.366.366 0 0 1 .102.02l.054.032.016.052-.023.09-.078.106-1.211 1.21 1.36 1.764a.685.685 0 0 1 .067.103l.025.072zM71.513 54.534a.41.41 0 0 1-.083.294.441.441 0 0 1-.294.08.427.427 0 0 1-.294-.078.392.392 0 0 1-.08-.287.397.397 0 0 1 .082-.294.61.61 0 0 1 .597 0c.064.084.093.19.08.294l-.008-.009zm-.064 4.481-.016.05-.045.035-.095.022a1.313 1.313 0 0 1-.293 0l-.094-.022-.049-.035-.016-.05v-3.352l.016-.049.049-.036.094-.021a1.017 1.017 0 0 1 .293 0l.095.02.045.037.016.049v3.352zM75.452 59.006l-.014.05-.049.036-.092.022a1.29 1.29 0 0 1-.294 0l-.093-.022-.048-.036-.014-.05V57.05a1.907 1.907 0 0 0-.045-.46.978.978 0 0 0-.13-.303.587.587 0 0 0-.221-.193.703.703 0 0 0-.315-.067.798.798 0 0 0-.46.164 2.536 2.536 0 0 0-.483.48v2.336l-.017.05-.047.036-.092.022a1.336 1.336 0 0 1-.294 0l-.094-.022-.049-.036-.011-.05v-3.343l.01-.05.044-.037.085-.022h.275l.082.022.043.037.013.05v.44c.159-.186.35-.343.563-.462.174-.095.368-.146.566-.147.193-.007.385.032.561.112.146.07.273.175.37.304.099.13.168.281.205.44.043.201.063.407.06.612l-.015 2.044zM79.365 55.817a.365.365 0 0 1-.037.185l-.09.059h-.48a.767.767 0 0 1 .183.294 1.322 1.322 0 0 1-.043.85c-.057.144-.148.274-.265.377a1.177 1.177 0 0 1-.409.235c-.17.057-.35.084-.529.083-.13 0-.26-.019-.386-.055a.86.86 0 0 1-.286-.135.598.598 0 0 0-.11.147.471.471 0 0 0-.042.197.255.255 0 0 0 .12.214.588.588 0 0 0 .322.094l.882.037c.153.005.306.028.454.07.13.036.252.095.361.174a.79.79 0 0 1 .324.663.996.996 0 0 1-.39.807c-.152.115-.324.2-.508.25-.24.065-.487.096-.735.09a3.007 3.007 0 0 1-.69-.068 1.567 1.567 0 0 1-.467-.188.751.751 0 0 1-.263-.284.816.816 0 0 1-.08-.359.816.816 0 0 1-.08-.359a.871.871 0 0 1 .122-.457c.04-.072.09-.139.146-.2.066-.066.136-.129.21-.186a.666.666 0 0 1-.27-.233.588.588 0 0 1-.087-.305.859.859 0 0 1 .093-.404c.06-.118.138-.225.23-.32a1.108 1.108 0 0 1-.181-.312 1.127 1.127 0 0 1-.067-.416c-.003-.176.03-.35.096-.513a1.085 1.085 0 0 1 .68-.623c.167-.057.343-.085.52-.082a2.1 2.1 0 0 1 .531.061h1.011a.103.103 0 0 1 .094.062c.025.06.036.125.033.19l.003.001zm-.654 3.526a.374.374 0 0 0-.177-.331.892.892 0 0 0-.476-.125l-.866-.03c-.07.053-.136.113-.196.178-.045.05-.086.103-.121.16a.566.566 0 0 0-.078.3.392.392 0 0 0 .247.365c.215.094.449.137.683.125.157.003.314-.015.466-.053a.882.882 0 0 0 .302-.147.531.531 0 0 0 .215-.447v.005zm-.308-2.65a.736.736 0 0 0-.188-.533.713.713 0 0 0-.532-.191.65.65 0 0 0-.53.222.71.71 0 0 0-.131.24.947.947 0 0 0-.043.284.654.654 0 0 0 .713.708.822.822 0 0 0 .316-.045.604.604 0 0 0 .351-.397.882.882 0 0 0 .044-.278" fill="#36312F"/><path d="M50.797 55.954a4.054 4.054 0 0 0-7.953 1.563c.6-.66 3.299-3.22 7.953-1.563z" fill="#398FCC"/><path d="M42.854 57.576a4.055 4.055 0 0 0 8.038-.757v-.008c-.285-.136-4.163-1.877-8.038.765z" fill="#398FCC"/></svg>
                        </div>
                    </div>
                </div>


                <!-- Client 4 
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="client-logo-card">
                        <div class="client-logo-svg-wrapper">
                            <!-- Arch with Roof Line Inside 
                            <svg class="client-logo-svg" width="160" height="90" viewBox="0 0 160 90" xmlns="http://www.w3.org/2000/svg">
                                <path d="M52 42c0-15.5 12.5-28 28-28s28 12.5 28 28" fill="none" stroke="#0d2c5c" stroke-width="3" stroke-linecap="round"/>
                                <path d="M62 42l18-12 18 12" fill="none" stroke="#0d2c5c" stroke-width="2.5" stroke-linecap="round"/>
                                <path class="accent-fill" d="M76 34h8v8h-8z" />
                                <text x="80" y="68" font-family="'Poppins', sans-serif" font-size="11" font-weight="700" text-anchor="middle" letter-spacing="1">SKYLINE</text>
                                <text x="80" y="78" font-family="'Poppins', sans-serif" font-size="6" font-weight="500" text-anchor="middle" fill="#64748b">Commercial Real Estate</text>
                            </svg>
                        </div>
                    </div>
                </div>-->

                <!-- Client 5 -->
                <!--<div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="client-logo-card">
                        <div class="client-logo-svg-wrapper">
                            <!-- Official Techxpert Logo 
                            <img src="assets/images/techxpert_logo.png?v=2" class="original-client-logo" style="max-width: 170px; height: 38px; object-fit: contain;" alt="Techxpert Facilities Private Limited">
                        </div>
                    </div>
                </div>-->

                <!-- Client 6 -->
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="client-logo-card">
                        <div class="client-logo-svg-wrapper">
                            <!-- Official Cushman & Wakefield Logo -->
                            <img src="assets/images/cushman_wakefield.svg?v=2" class="original-client-logo" style="max-width: 180px; height: 38px; object-fit: contain;" alt="Cushman & Wakefield">
                        </div>
                    </div>
                </div>

                <!-- Client 7 -->
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="client-logo-card">
                        <div class="client-logo-svg-wrapper">
                            <!-- Official Innovsource Logo -->
                            <img src="assets/images/innovsource_logo.png?v=2" class="original-client-logo" style="max-width: 160px; height: 50px; object-fit: contain;" alt="Innovsource Facilities Private Limited">
                        </div>
                    </div>
                </div>

                <!-- Client 8 
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="client-logo-card">
                        <div class="client-logo-svg-wrapper">
                            <!-- Modern High-Rise Line Outline 
                            <svg class="client-logo-svg" width="160" height="90" viewBox="0 0 160 90" xmlns="http://www.w3.org/2000/svg">
                                <path d="M68 45V18l14-6 12 10v23" fill="none" stroke="#0d2c5c" stroke-width="2.5" />
                                <path d="M74 45V24h6v21" fill="none" stroke="#0d2c5c" stroke-width="2.5" />
                                <line x1="82" y1="28" x2="82" y2="45" stroke="#0d2c5c" stroke-width="2.5" />
                                <rect x="88" y="24" width="4" height="21" class="accent-fill" />
                                <text x="80" y="68" font-family="'Poppins', sans-serif" font-size="11" font-weight="700" text-anchor="middle" letter-spacing="1">URBANEDGE</text>
                                <text x="80" y="78" font-family="'Poppins', sans-serif" font-size="5.5" font-weight="600" text-anchor="middle" letter-spacing="0.5" fill="#64748b">RESIDENTIAL COMPLEXES</text>
                            </svg>
                        </div>
                    </div>
                </div>
                -->
            </div>
        </div>
    </section>

    <!-- ==========================================
         REQUEST A QUOTE SECTION
         ========================================== -->
    <section class="quote-section">
        <div class="container">
            <div class="row align-items-center">
                <!-- Left: Google Map -->
                <div class="col-lg-6">
                    <div class="quote-map-wrapper">
                        <!-- Embed Google Maps pointing to Crossing Republic, Ghaziabad -->
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3502.0!2d77.47!3d28.63!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x390cf15!2sCrossing%20Republic%20Ghaziabad!5e0!3m2!1sen!2sin!4v1680000000000!5m2!1sen!2sin" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>

                <!-- Right: Content & Info -->
                <div class="col-lg-6">
                    <div class="quote-content">
                        <span class="section-subtitle">| Get Quote</span>
                        <h2 class="section-title">Request A Quote</h2>
                        <p class="section-desc">
                            Have a facility management requirement? Get in touch with our team for a customized quote for IFM, AMC, civil works, procurement, or technical maintenance services.
                        </p>

                        <div class="quote-contact-list">
                            <!-- Address -->
                            <div class="quote-contact-item">
                                <div class="quote-contact-icon">
                                    <i class="bi bi-geo-alt-fill"></i>
                                </div>
                                <div class="quote-contact-info">
                                    <h4>Our Office Address:</h4>
                                    <p>13151, Gold Coast, Tower-12, GH-07,<br>Crossing Republic, Ghaziabad – 201016</p>
                                </div>
                            </div>

                            <!-- Phone -->
                            <div class="quote-contact-item">
                                <div class="quote-contact-icon">
                                    <i class="bi bi-telephone-fill"></i>
                                </div>
                                <div class="quote-contact-info">
                                    <h4>Call Us:</h4>
                                    <p><a href="tel:+918800904906">+91 8800904906</a></p>
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="quote-contact-item">
                                <div class="quote-contact-icon">
                                    <i class="bi bi-envelope-fill"></i>
                                </div>
                                <div class="quote-contact-info">
                                    <h4>Email Us</h4>
                                    <p><a href="mailto:info@aryadibusiness.com">info@aryadibusiness.com</a></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ==========================================
         NEWSLETTER SIGNUP BAR
         ========================================== -->
    <section class="newsletter-section">
        <div class="container">
            <div class="newsletter-wrapper">
                <!-- Left: Icon & Text -->
                <div class="newsletter-content-box">
                    <div class="newsletter-icon-wrapper">
                        <!-- Premium Envelope with Letter SVG to match mockup -->
                        <svg width="60" height="50" viewBox="0 0 60 50" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <!-- Paper sticking out -->
                            <rect x="15" y="4" width="30" height="22" rx="2" fill="none" />
                            <line x1="22" y1="10" x2="38" y2="10" />
                            <line x1="22" y1="15" x2="34" y2="15" />
                            <line x1="22" y1="20" x2="38" y2="20" />
                            <!-- Envelope Body -->
                            <path d="M6,22 L54,22 L54,46 L6,46 Z" />
                            <!-- Envelope Fold -->
                            <path d="M6,22 L30,36 L54,22" />
                        </svg>
                    </div>
                    <div class="newsletter-text">
                        <h3>Sign Up to Get Latest Updates</h3>
                        <p>Subscribe for facility management tips, AMC offer updates, and exclusive industry insights.</p>
                    </div>
                </div>

                <!-- Right: Input & Submit Form -->
                <div class="newsletter-form-box">
                    <form action="#" method="POST" class="d-flex w-100 m-0">
                        <input type="email" name="email" class="newsletter-input" placeholder="Your email address" required autocomplete="off">
                        <button type="submit" class="newsletter-btn">Sign Up</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <?php include('./include/footer.php'); ?>

    <!-- Owl Carousel Init and Autoplay JS + Count Up JS -->
    <script>
        $(document).ready(function(){
            const owl = $("#testimonialOwlCarousel");
            
            // Initialize Owl Carousel
            owl.owlCarousel({
                loop: true,
                margin: 24,
                nav: false, // Custom controls used
                dots: false, // Dots disabled
                autoplay: true,
                autoplayTimeout: 5000,
                autoplayHoverPause: true,
                responsive: {
                    0: {
                        items: 1
                    },
                    768: {
                        items: 2
                    }
                }
            });

            // Bind Custom controls to Owl events
            $(".btn-carousel-prev").click(function(){
                owl.trigger("prev.owl.carousel");
            });
            
            $(".btn-carousel-next").click(function(){
                owl.trigger("next.owl.carousel");
            });

            // JS Count-Up animation triggered on scroll
            const counters = document.querySelectorAll('.counter-number');
            
            const startCounter = (counter) => {
                const target = +counter.getAttribute('data-target');
                const updateCount = () => {
                    const count = +counter.innerText;
                    const speed = 40; // Animation speed
                    const inc = Math.ceil(target / speed);

                    if (count < target) {
                        counter.innerText = count + inc > target ? target : count + inc;
                        setTimeout(updateCount, 25);
                    } else {
                        counter.innerText = target;
                    }
                };
                updateCount();
            };

            const observerOptions = {
                threshold: 0.2
            };

            const observer = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        startCounter(entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);

            counters.forEach(counter => {
                observer.observe(counter);
            });
        });
    </script>
</body>
</html>