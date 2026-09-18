<?php
include("connection.php");

$secretKey = $recaptcha_secret_key;

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (empty($_POST['g-recaptcha-response'])) {
        die("Please verify that you are not a robot.");
    }

    $captcha = $_POST['g-recaptcha-response'];
    $verify = file_get_contents(
        "https://www.google.com/recaptcha/api/siteverify?secret=" . $secretKey . "&response=" . $captcha
    );
    $response = json_decode($verify);

    if (!$response->success) {
        die("CAPTCHA verification failed.");
    }

    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name  = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email      = mysqli_real_escape_string($conn, $_POST['email']);
    $phone      = mysqli_real_escape_string($conn, $_POST['phone']);
    $message    = mysqli_real_escape_string($conn, $_POST['message']);

    $query = "INSERT INTO contact_us
    (first_name,last_name,email,phone,message)
    VALUES
    ('$first_name','$last_name','$email','$phone','$message')";

    if (mysqli_query($conn, $query)) {
        echo "<script>
        alert('Message Sent Successfully');
        window.location='contact-us.php';
        </script>";
    } else {
        echo "Database Error : " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Aryadi Business</title>
    <?php include('./include/link.php'); ?>
    <!-- Custom Dedicated CSS for Contact Us Redesign -->
    <link rel="stylesheet" href="css/contact-us.css?v=<?php echo time(); ?>">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>

<body>
    <?php include('./include/header.php'); ?>

    <section class="contact-section-wrapper">
        <div class="container">
            <div class="row g-5 align-items-center">

                <!-- Left Column: Contact Details & Copy -->
                <div class="col-lg-5">
                    <span class="contact-subtitle">Get in Touch</span>
                    <h1 class="contact-title">Let's start a conversation</h1>
                    <p class="contact-lead">Have questions about our enterprise management services or need a tailored operations proposal? Connect with our experts today.</p>

                    <div class="contact-info-list">
                        <!-- Call block -->
                        <a href="tel:+8800904906" class="contact-info-card call-card-box">
                            <div class="contact-icon-circle">
                                <i class="bi bi-telephone-fill"></i>
                            </div>
                            <div class="contact-info-details">
                                <span class="contact-info-label">Call Us</span>
                                <span class="contact-info-val">+91 8800904906</span>
                            </div>
                        </a>

                        <!-- Email block -->
                        <a href="mailto:info@aryadibusiness.com" class="contact-info-card email-card-box">
                            <div class="contact-icon-circle">
                                <i class="bi bi-envelope-fill"></i>
                            </div>
                            <div class="contact-info-details">
                                <span class="contact-info-label">Email Address</span>
                                <span class="contact-info-val">info@aryadibusiness.com</span>
                            </div>
                        </a>

                        <!-- Address block -->
                        <div class="contact-info-card address-card-box">
                            <div class="contact-icon-circle">
                                <i class="bi bi-geo-alt-fill"></i>
                            </div>
                            <div class="contact-info-details">
                                <span class="contact-info-label">Corporate Address</span>
                                <span class="contact-info-val">
                                    Aryadi Business Private Limited,<br>
                                    13151, Gold Coast, Tower-12, GH-07,<br>
                                    Crossing Republic, Ghaziabad - 201016<br>
                                    <span style="font-size: 12px; font-weight: 500; opacity: 0.8; display: block; margin-top: 4px;">CIN - U74999UP2016PTC085365</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Contact Form -->
                <div class="col-lg-7">
                    <div class="contact-form-card">
                        <h2 class="contact-form-card-title">Send us a Message</h2>
                        <p class="contact-form-card-desc">Complete the form details and our team will get back to you shortly.</p>

                        <form action="" method="POST" id="contactForm">
                            <div class="row g-4">
                                <!-- First name -->
                                <div class="col-md-6">
                                    <div class="contact-input-group">
                                        <input type="text" class="contact-form-control" name="first_name" placeholder="First name" required>
                                        <i class="bi bi-person-fill contact-input-icon"></i>
                                    </div>
                                </div>
                                <!-- Last name -->
                                <div class="col-md-6">
                                    <div class="contact-input-group">
                                        <input type="text" class="contact-form-control" name="last_name" placeholder="Last name" required>
                                        <i class="bi bi-person-fill contact-input-icon"></i>
                                    </div>
                                </div>
                                <!-- Email -->
                                <div class="col-md-6">
                                    <div class="contact-input-group">
                                        <input type="email" class="contact-form-control" name="email" placeholder="Email Address" required>
                                        <i class="bi bi-envelope-fill contact-input-icon"></i>
                                    </div>
                                </div>
                                <!-- Phone Number -->
                                <div class="col-md-6">
                                    <div class="contact-input-group">
                                        <input type="tel" class="contact-form-control" name="phone" placeholder="Phone Number">
                                        <i class="bi bi-telephone-fill contact-input-icon"></i>
                                    </div>
                                </div>
                                <!-- Message -->
                                <div class="col-12">
                                    <div class="contact-input-group">
                                        <textarea class="contact-form-control" name="message" placeholder="Describe your request..." required></textarea>
                                        <i class="bi bi-pencil-fill contact-input-icon"></i>
                                    </div>
                                </div>
                                <!-- ReCAPTCHA -->
                                <div class="col-12">
                                    <div class="recaptcha-container">
                                        <div class="g-recaptcha" data-sitekey="<?php echo $recaptcha_site_key; ?>"></div>
                                    </div>
                                </div>
                                <!-- Submit Button -->
                                <div class="col-12">
                                    <button type="submit" name="submit" class="contact-submit-btn">
                                        Submit Request
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <?php include('./include/footer.php'); ?>
</body>

</html>