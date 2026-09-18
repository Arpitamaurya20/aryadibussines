<?php

// ============================================================
//  Database Connection
// ============================================================
$conn = mysqli_connect("localhost", "root", "", "aryadibussines");

if (!$conn) {
    die("Connection Failed : " . mysqli_connect_error());
}


$recaptcha_site_key   = "6LdZaWstAAAAAASA-eyTK5ld9GMxGIfbMvwC5DEM";
$recaptcha_secret_key = "6LcFbGstAAAAALK_JzgGEk0nPdIHjaP0SFq-uE_U";


?>
