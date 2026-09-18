<!-- ==========================================
     MAIN 4-COLUMN FOOTER
     ========================================== -->
<footer class="main-footer">
    <div class="container">
        <div class="row">
            <!-- Column 1: Brand & Desc -->
            <div class="col-lg-3 col-md-6 footer-column">
                <a class="footer-brand d-flex align-items-center mb-3 text-decoration-none" href="index.php">
                    <img src="assets/logo.png" alt="Aryadi Business" class="footer-brand-logo me-2">
                    <div class="brand-text-wrapper">
                        <span class="brand-name">ARYADI BUSINESS</span>
                    </div>
                </a>
                <p class="footer-desc mt-3">
                    Aryadi Business Pvt. Ltd. is a premier solutions provider, offering high-quality Technical Facility Management, Annual Maintenance Contracts, and Civil & Interior solutions to elevate corporate and residential spaces.
                </p>
            </div>

            <!-- Column 2: Useful Links -->
            <div class="col-lg-3 col-md-6 footer-column d-flex justify-content-lg-center">
                <div>
                    <h2 class="footer-title">Useful links</h2>
                    <ul class="footer-links-list">
                        <li><a href="contact-us.php">Contact Us</a></li>
                        <li><a href="clients.php">Our Clients</a></li>
                        <li><a href="about-us.php">About Us</a></li>
                    </ul>
                </div>
            </div>

            <!-- Column 3: Our Services -->
            <div class="col-lg-3 col-md-6 footer-column">
                <h2 class="footer-title">Our Services</h2>
                <ul class="footer-links-list">
                    <li><a href="Integrated-Facility.php">Integrated Facility Management</a></li>
                    <li><a href="technical-facility-management.php">Technical Facility Management</a></li>
                    <li><a href="annual-maintenance.php">Annual Maintenance Contracts</a></li>
                    <li><a href="repair-maintenance.php">Repair &amp; Maintenance</a></li>
                    <li><a href="civil-interiorproject.php">Civil &amp; Interior Projects</a></li>
                    <li><a href="supplies-procurement.php">Supplies &amp; Procurement</a></li>
                   <!-- <li><a href="techxpark.php">TechXPark Platform</a></li>-->
                   <!-- <li><a href="mygate.php">MyGate Platform</a></li>-->
                </ul>
            </div>

            <!-- Column 4: Our Contact -->
            <div class="col-lg-3 col-md-6 footer-column">
                <h2 class="footer-title">Our Contact</h2>
                <ul class="footer-contact-list">
                    <li>
                        <i class="bi bi-geo-alt-fill"></i>
                        <span>13151 , Gold Coast , Tower-12 , GH-07, 
Crossing Republic , Ghaziabad - 201016,
CIN - U74999UP2016PTC085365</span>
                    </li>
                    <li>
                        <i class="bi bi-telephone-fill"></i>
                        <a href="tel:+918800904906">+91 8800904906</a>
                    </li>
                    <li>
                        <i class="bi bi-envelope-fill"></i>
                        <a href="mailto:info@aryadibusiness.com">info@aryadibusiness.com</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- ==========================================
         COPYRIGHT BAR
         ========================================== -->
    <div class="copyright-bar">
        <div class="container">
            <div class="row align-items-center">
                <!-- Social Links Left -->
                <div class="col-md-6">
                    <div class="copyright-bar-social">
                        <a href="https://www.facebook.com/profile.php?id=61592766645081" target="_blank" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                         <a href="https://www.linkedin.com/in/aryadi-business-57818a426/" target="_blank" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
                        <a href="https://www.instagram.com/aryadi_business/?hl=en" target="_blank" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                    </div>
                </div>
                <!-- Copyright Text Right -->
                <div class="col-md-6 copyright-text">
                    <span>Copyright &copy; <?php echo date('Y'); ?> Aryadi Business Pvt. Ltd. All Rights Reserved.</span>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- ==========================================
     SCROLL TO TOP BUTTON
     ========================================== -->
<button class="scroll-to-top" id="scrollTopBtn" aria-label="Scroll to Top">
    <i class="bi bi-chevron-up"></i>
</button>

<!-- ==========================================
     BOOTSTRAP JS AND SCROLL SCRIPTS
     ========================================== -->
<!-- jQuery (Required for Owl Carousel) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Owl Carousel 2 JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>

<!-- Bootstrap 5 JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<!-- Scroll to Top functionality script -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const scrollTopBtn = document.getElementById('scrollTopBtn');
        
        // Toggle visibility of the button on scroll
        window.addEventListener('scroll', function() {
            if (window.scrollY > 300) {
                scrollTopBtn.classList.add('active');
            } else {
                scrollTopBtn.classList.remove('active');
            }
        });
        
        // Scroll smoothly to top on click
        scrollTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    });
</script>