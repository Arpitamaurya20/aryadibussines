<?php
// ── Active-nav detection ─────────────────────────────────────────────────
$currentPage = basename($_SERVER['PHP_SELF']); // e.g. "services.php"

// Pages that belong to the "About Us" section
$aboutPages  = ['about-us.php', 'about-me.php', 'team.php', 'leadership.php', 'process.php', 'clients.php', 'pricing.php', 'faq.php'];

// Pages that belong to the "Services" section
$servicePages = ['services.php', 'repair-maintenance.php', 'annual-maintenance.php', 'technical-facility-management.php'];

$isHome     = ($currentPage === 'index.php');
$isAbout    = in_array($currentPage, $aboutPages);
$isServices = in_array($currentPage, $servicePages);
$isContact  = ($currentPage === 'contact-us.php');
?>
<!-- ==========================================
     TOP BAR SECTION
     ========================================== -->
<div class="top-bar d-none d-md-block">
    <div class="container d-flex justify-content-between align-items-center">
        <!-- Social Links Left -->
        <div class="top-bar-social d-flex gap-3">
            <a href="https://www.instagram.com/aryadi_business/?hl=en" target="_blank" class="text-white"><i class="bi bi-instagram"></i></a>
            <a href="https://www.facebook.com/profile.php?id=61592766645081" target="_blank" class="text-white"><i class="bi bi-facebook"></i></a>
            <a href="https://www.linkedin.com/in/aryadi-business-57818a426/" target="_blank" class="text-white"><i class="bi bi-linkedin"></i></a>
        </div>
        
        <!-- Contact Info Right -->
        <div class="top-bar-contact">
            <span class="top-bar-text" style="color: #e2e8f0;">Your Trusted 24 Hours Service Provider!</span>
            
            <a href="mailto:info@aryadibusiness.com"><i class="bi bi-envelope-fill"></i> info@aryadibusiness.com</a>
            <a href="tel:+8800904906"><i class="bi bi-telephone-fill"></i> +91 8800904906</a>
        </div>
    </div>
</div>

<!-- ==========================================
     MAIN HEADER & NAVIGATION SECTION
     ========================================== -->
<div class="main-header-wrapper" id="mainHeader">
    <div class="container">
        <nav class="navbar navbar-expand-lg custom-navbar">
            <!-- Brand / Logo -->
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="assets/logo.png" alt="Aryadi Business" class="navbar-brand-logo">
                <div class="brand-text-wrapper">
                    <span class="brand-name">ARYADI BUSINESS</span>
                </div>
            </a>

            <!-- Mobile Hamburger Button -->
            <button class="navbar-toggler custom-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation" id="navbarTogglerBtn">
                <span class="toggler-icon-bar"></span>
                <span class="toggler-icon-bar"></span>
                <span class="toggler-icon-bar"></span>
            </button>

            <!-- Navigation Links -->
            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <!-- Home -->
                    <li class="nav-item">
                        <a class="nav-link <?= $isHome ? 'active' : '' ?>" href="index.php">Home</a>
                    </li>
                    
                    <!-- About Us Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link <?= $isAbout ? 'active' : '' ?>" href="about-us.php" >
                            About Us
                        </a>
                        
                    </li>

                    <!-- Services Dropdown -->
                    <li class="nav-item dropdown dropdown-mega">
                        <a class="nav-link dropdown-toggle <?= $isServices ? 'active' : '' ?>" href="services.php" id="servicesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Services
                        </a>
                        <div class="dropdown-menu mega-menu-content" aria-labelledby="servicesDropdown">
                            <div class="row g-3">
                                <!-- Column 1 -->
                                <div class="col-lg-6 d-flex flex-column gap-1">
                                    <a href="repair-maintenance.php" class="mega-menu-item">
                                        <div class="mega-menu-icon-box">
                                            <i class="bi bi-wrench"></i>
                                        </div>
                                        <div class="mega-menu-text">
                                            <span class="mega-menu-title">Repair & Maintenance</span>
                                            <span class="mega-menu-desc">Reliability-centered asset management</span>
                                        </div>
                                    </a>
                                    <a href="technical-facility-management.php" class="mega-menu-item">
                                        <div class="mega-menu-icon-box">
                                            <i class="bi bi-cpu"></i>
                                        </div>
                                        <div class="mega-menu-text">
                                            <span class="mega-menu-title">Technical Facility Management</span>
                                            <span class="mega-menu-desc">Mission-critical engineering operations</span>
                                        </div>
                                    </a>
                                    <a href="supplies-procurement.php" class="mega-menu-item">
                                        <div class="mega-menu-icon-box">
                                            <i class="bi bi-box-seam"></i>
                                        </div>
                                        <div class="mega-menu-text">
                                            <span class="mega-menu-title">Supplies & Procurement</span>
                                            <span class="mega-menu-desc">Strategic sourcing & supply chain</span>
                                        </div>
                                    </a>
                                    <!--<a href="techxpark.php" class="mega-menu-item">
                                         <div class="mega-menu-icon-box">
                                             <i class="bi bi-p-square-fill"></i>
                                         </div>
                                         <div class="mega-menu-text">
                                             <span class="mega-menu-title">Techxpark</span>
                                             <span class="mega-menu-desc">Techxpark is a platform for parking management.</span>
                                         </div>
                                     </a>-->
                                </div>
                                <!-- Column 2 -->
                                <div class="col-lg-6 d-flex flex-column gap-1">
                                    <a href="annual-maintenance.php" class="mega-menu-item">
                                        <div class="mega-menu-icon-box">
                                            <i class="bi bi-file-earmark-check"></i>
                                        </div>
                                        <div class="mega-menu-text">
                                            <span class="mega-menu-title">Annual Maintenance Contracts</span>
                                            <span class="mega-menu-desc">Performance-based asset governance</span>
                                        </div>
                                    </a>
                                    <a href="civil-interiorproject.php" class="mega-menu-item">
                                        <div class="mega-menu-icon-box">
                                            <i class="bi bi-building"></i>
                                        </div>
                                        <div class="mega-menu-text">
                                            <span class="mega-menu-title">Civil & Interior Projects</span>
                                            <span class="mega-menu-desc">Structured project execution</span>
                                        </div>
                                    </a>
                                    <a href="Integrated-Facility.php" class="mega-menu-item">
                                        <div class="mega-menu-icon-box">
                                            <i class="bi bi-diagram-3"></i>
                                        </div>
                                        <div class="mega-menu-text">
                                            <span class="mega-menu-title">Integrated Facility Management</span>
                                            <span class="mega-menu-desc">One-team enterprise FM solution</span>
                                        </div>
                                    </a>
                                    <!--<a href="mygate.php" class="mega-menu-item">
                                        <div class="mega-menu-icon-box"></div>
                                        <div class="mega-menu-text">
                                            <span class="mega-menu-title">mygate</span>
                                            <span class="mega-menu-desc">mygate is a platform for automated gate management.</span>
                                        </div>
                                    </a>-->
                                </div>
                            </div>
                        </div>
                    </li>


                    <!-- Achievements -->
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage === 'achievements.php') ? 'active' : '' ?>" href="achievements.php">Achievements</a>
                    </li>

                    <!-- Projects -->
                    <!--<li class="nav-item">
                        <a class="nav-link" href="#">Projects</a>
                    </li>-->

                    <!-- Contact Us -->
                    <li class="nav-item">
                        <a class="nav-link <?= $isContact ? 'active' : '' ?>" href="contact-us.php">Contact Us</a>
                    </li>
                </ul>

                <!-- Right Action Icons & Buttons -->
                <div class="header-right-action">
                    <!-- Search Icon (Toggles Overlay) -->
                    <!--<button class="search-icon-btn" id="searchOpenBtn" aria-label="Open Search">
                        <i class="bi bi-search"></i>
                    </button>-->
                    <!-- Corporate Login Button -->
                    <a href="corporate-login.php" class="corporate-login-btn" >
                        Corporate Login
                    </a>
                    <!-- Header Social Icons -->
                    
                </div>
            </div>
        </nav>
    </div>
</div>

<!-- ==========================================
     SEARCH OVERLAY MODAL
     ========================================== -->
<div class="search-overlay" id="searchOverlay">
    <button class="search-overlay-close" id="searchCloseBtn" aria-label="Close Search">&times;</button>
    <div class="search-form-container">
        <form action="#" method="GET">
            <input type="text" class="search-input" placeholder="Type here to search..." autocomplete="off" id="searchInputField">
            <span class="search-hint">Press Enter to Search / Esc to Close</span>
        </form>
    </div>
</div>

<!-- ==========================================
     HEADER SCRIPTS (SCROLL & MENU INTERACTION)
     ========================================== -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const mainHeader = document.getElementById('mainHeader');
        const searchOverlay = document.getElementById('searchOverlay');
        const searchOpenBtn = document.getElementById('searchOpenBtn');
        const searchCloseBtn = document.getElementById('searchCloseBtn');
        const searchInputField = document.getElementById('searchInputField');
        const navbarTogglerBtn = document.getElementById('navbarTogglerBtn');

        // 1. Sticky Navbar on Scroll
        window.addEventListener('scroll', function() {
            if (window.scrollY > 100) {
                mainHeader.classList.add('sticky-active');
            } else {
                mainHeader.classList.remove('sticky-active');
            }
        });

        // 2. Search Overlay Toggle
        if (searchOpenBtn && searchOverlay) {
            searchOpenBtn.addEventListener('click', function(e) {
                e.preventDefault();
                searchOverlay.classList.add('active');
                setTimeout(() => {
                    if (searchInputField) searchInputField.focus();
                }, 300);
            });
        }

        if (searchCloseBtn && searchOverlay) {
            searchCloseBtn.addEventListener('click', function(e) {
                e.preventDefault();
                searchOverlay.classList.remove('active');
            });
        }

        // Close search on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && searchOverlay.classList.contains('active')) {
                searchOverlay.classList.remove('active');
            }
        });

        // 3. Hamburger Menu Animation Toggler
        if (navbarTogglerBtn) {
            navbarTogglerBtn.addEventListener('click', function() {
                navbarTogglerBtn.classList.toggle('custom-navbar-collapsed');
            });
        }
        
        // Auto-close bootstrap mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const navbarCollapse = document.getElementById('mainNavbar');
            const isClickInside = mainHeader.contains(event.target);
            if (!isClickInside && navbarCollapse.classList.contains('show')) {
                const bootstrap = window.bootstrap;
                if (bootstrap) {
                    const bsCollapse = bootstrap.Collapse.getInstance(navbarCollapse);
                    if (bsCollapse) {
                        bsCollapse.hide();
                        if (navbarTogglerBtn) {
                            navbarTogglerBtn.classList.remove('custom-navbar-collapsed');
                        }
                    }
                }
            }
        });
    });
</script>