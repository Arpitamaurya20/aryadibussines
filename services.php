<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services - Aryadi Business</title>
    <?php include('./include/link.php'); ?>
    <style>
        /* Services Hero Section Styles */
        .services-hero {
            background: linear-gradient(135deg, #0a1e3f 0%, #15325b 100%);
            padding: 80px 0;
            color: #ffffff;
            position: relative;
            overflow: hidden;
        }
        
        .services-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 80% 20%, rgba(27, 160, 227, 0.15) 0%, transparent 50%);
            pointer-events: none;
        }
        
        .services-hero-title {
            font-size: 3.2rem;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 24px;
            color: #ffffff;
        }
        
        .services-hero-title span.highlight {
            color: #3d8ecf; /* Match the red/orange accent */
        }
        
        .services-hero-desc {
            font-size: 1.1rem;
            line-height: 1.8;
            color: rgba(255, 255, 255, 0.85);
            margin-bottom: 30px;
        }
        
        /* Interactive Tabs Form Card */
        .services-form-card {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            border: none;
        }
        
        .services-form-tabs {
            display: flex;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .form-tab-btn {
            flex: 1;
            padding: 16px;
            border: none;
            background: #f8fafc;
            font-size: 15px;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .form-tab-btn.active {
            background-color: #1555a6;
            color: #ffffff;
        }
        
        .services-form-body {
            padding: 30px;
        }
        
        .services-form-body .form-label {
            font-size: 13.5px;
            font-weight: 500;
            color: #475569;
            margin-bottom: 8px;
        }
        
        .services-form-body .form-control {
            border: 1px solid #cbd5e1;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            color: #1e293b;
            transition: all 0.3s ease;
        }
        
        .services-form-body .form-control:focus {
            border-color: #1555a6;
            box-shadow: 0 0 0 3px rgba(21, 85, 166, 0.15);
        }
        
        .services-submit-btn {
            background: linear-gradient(135deg, #1555a6 0%, #114383 100%);
            color: #ffffff;
            border: none;
            padding: 14px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .services-submit-btn:hover {
            background: linear-gradient(135deg, #1ba0e3 0%, #1555a6 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(27, 160, 227, 0.3);
        }
        
        /* Services List Section */
        .services-list-section {
            padding: 80px 0;
            background-color: #f8fafc;
        }
        
        .service-list-card {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            height: 100%;
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
        }
        
        .service-list-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            border-color: rgba(21, 85, 166, 0.2);
        }
        
        .service-list-icon {
            width: 54px;
            height: 54px;
            background-color: #1555a6;
            color: #ffffff;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .service-list-card:hover .service-list-icon {
            background-color: #3d8ecf;
        }
        
        .service-list-title {
            font-size: 19px;
            font-weight: 700;
            color: #0d2c5c;
            margin-bottom: 12px;
        }
        
        .service-list-desc {
            font-size: 14px;
            color: #64748b;
            line-height: 1.6;
        }
        
        @media (max-width: 991.98px) {
            .services-hero-title {
                font-size: 2.4rem;
            }
            .services-hero {
                padding: 60px 0;
            }
        }
    </style>
</head>
<body>
    <?php include('./include/header.php'); ?>
     
    <!-- Services Hero Banner with Tab Form -->
    <section class="services-hero">
        <div class="container">
            <div class="row align-items-center g-5">
                <!-- Left Column: Content -->
                <div class="col-lg-6">
                    <h1 class="services-hero-title">
                        Your One-Stop<br>
                        Facility Management<br>
                        <span class="highlight">Solution</span>
                    </h1>
                    <p class="services-hero-desc">
                        We deliver expert residential repairs and enterprise-grade facility management. From electrical and plumbing, to complex technical infrastructures, our experienced team ensures your operations run smoothly and efficiently.
                    </p>
                </div>
                
                <!-- Right Column: Interactive Form -->
                <div class="col-lg-6">
                    <div class="services-form-card">
                        <div class="services-form-tabs">
                            <button class="form-tab-btn active" id="tabVendor" onclick="switchForm('vendor')">Vendor</button>
                            <button class="form-tab-btn" id="tabOwner" onclick="switchForm('owner')">Business Owner</button>
                        </div>
                        <div class="services-form-body">
                            <form action="#" method="POST" id="inquiryForm">
                                <input type="hidden" name="user_type" id="formUserType" value="vendor">
                                
                                <div class="mb-3">
                                    <label class="form-label" id="labelName" for="inputName">Full Name / Organization</label>
                                    <input type="text" class="form-control" id="inputName" placeholder="Enter your full name or company" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label" for="inputAddress">Address</label>
                                    <input type="text" class="form-control" id="inputAddress" placeholder="Enter your address" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label" for="inputPhone">Phone Number</label>
                                    <input type="tel" class="form-control" id="inputPhone" placeholder="Enter your phone number" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label" for="inputSubject">Subject</label>
                                    <input type="text" class="form-control" id="inputSubject" placeholder="What are you inquiring about?" required>
                                </div>
                                
                                <button type="submit" class="services-submit-btn">Submit Request</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Grid List -->
    <section class="services-list-section">
        <div class="container">
            <div class="text-center mb-5">
                <span class="text-uppercase fw-bold text-primary" style="font-size: 13px; letter-spacing: 2px;">Our Expertise</span>
                <h2 class="display-5 fw-bold text-dark mt-2">Comprehensive Solutions</h2>
            </div>
            
            <div class="row g-4">
                <!-- Service 1 -->
                <div class="col-lg-3 col-md-6" id="repair-maintenance">
                    <a href="repair-maintenance.php" class="text-decoration-none d-block h-100">
                        <div class="service-list-card">
                            <div class="service-list-icon">
                                <i class="bi bi-wrench-adjustable"></i>
                            </div>
                            <h3 class="service-list-title">Repair & Maintenance</h3>
                            <p class="service-list-desc">Reliability-centered asset management, diagnostics, preventative checks, and routine repairs for residential and enterprise equipment.</p>
                        </div>
                    </a>
                </div>
                
                <!-- Service 2 -->
                <div class="col-lg-3 col-md-6" id="annual-maintenance-contracts">
                    <a href="annual-maintenance.php" class="text-decoration-none d-block h-100">
                        <div class="service-list-card">
                            <div class="service-list-icon">
                                <i class="bi bi-file-earmark-check"></i>
                            </div>
                            <h3 class="service-list-title">Annual Maintenance</h3>
                            <p class="service-list-desc">Performance-based asset governance contracts to secure year-round operations with scheduled engineering checkups.</p>
                        </div>
                    </a>
                </div>
                
                <!-- Service 3 -->
                <div class="col-lg-3 col-md-6">
                    <div class="service-list-card">
                        <div class="service-list-icon">
                            <i class="bi bi-cpu"></i>
                        </div>
                        <h3 class="service-list-title">Technical Facility</h3>
                        <p class="service-list-desc">Mission-critical engineering operations, server room maintenance, electrical grids, and hardware management systems.</p>
                    </div>
                </div>
                
                <!-- Service 4 -->
                <div class="col-lg-3 col-md-6">
                    <div class="service-list-card">
                        <div class="service-list-icon">
                            <i class="bi bi-building"></i>
                        </div>
                        <h3 class="service-list-title">Civil & Interior</h3>
                        <p class="service-list-desc">Structured project execution, space redesigns, structural renovations, drywalling, painting, and architectural builds.</p>
                    </div>
                </div>
                
                <!-- Service 5 -->
                <div class="col-lg-3 col-md-6">
                    <div class="service-list-card">
                        <div class="service-list-icon">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <h3 class="service-list-title">Supplies & Procurement</h3>
                        <p class="service-list-desc">Strategic sourcing, supply chain fulfillment, and high-quality parts procurement matching standard industry regulations.</p>
                    </div>
                </div>
                
                <!-- Service 6 -->
                <div class="col-lg-3 col-md-6">
                    <a href="Integrated-Facility.php" class="text-decoration-none d-block h-100">
                        <div class="service-list-card">
                            <div class="service-list-icon">
                                <i class="bi bi-diagram-3"></i>
                            </div>
                            <h3 class="service-list-title">Integrated Management</h3>
                            <p class="service-list-desc">One-team enterprise FM solutions managing security, janitorial, maintenance, and logistics support under one roof.</p>
                        </div>
                    </a>
                </div>
                
                <!-- Service 7 -->
                <div class="col-lg-3 col-md-6">
                    <a href="techxpark.php" class="text-decoration-none d-block h-100">
                        <div class="service-list-card">
                            <div class="service-list-icon">
                                <i class="bi bi-p-square"></i>
                            </div>
                            <h3 class="service-list-title">Techxpark</h3>
                            <p class="service-list-desc">Smart parking management software solutions utilizing optical detection and digital payments for seamless transits.</p>
                        </div>
                    </a>
                </div>
                
                <!-- Service 8 -->
                <div class="col-lg-3 col-md-6">
                    <div class="service-list-card">
                        <div class="service-list-icon">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <h3 class="service-list-title">mygate</h3>
                        <p class="service-list-desc">Automated visitor and gate authentication platforms providing secure gating controls for complexes and estates.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Tab Toggle Script -->
    <script>
        function switchForm(type) {
            const tabVendor = document.getElementById('tabVendor');
            const tabOwner = document.getElementById('tabOwner');
            const formUserType = document.getElementById('formUserType');
            const labelName = document.getElementById('labelName');
            
            if (type === 'vendor') {
                tabVendor.classList.add('active');
                tabOwner.classList.remove('active');
                formUserType.value = 'vendor';
                labelName.innerText = 'Full Name / Organization';
            } else {
                tabOwner.classList.add('active');
                tabVendor.classList.remove('active');
                formUserType.value = 'owner';
                labelName.innerText = 'Business / Owner Name';
            }
        }
    </script>

    <?php include('./include/footer.php'); ?>
</body>
</html>
