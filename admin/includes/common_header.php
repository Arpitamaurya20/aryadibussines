<?php
$today = date('m-d');
$isChristmas = ($today === '12-24');
?>

<div id="preloader">
  <div id="loader">
    <img width="80px" src="../img/setting-icon.png" alt="">
  </div>
</div>

<?php if ($isChristmas): ?>
<div class="ribbon-fall-container"></div>
<?php endif; ?>

<style>
    /* ===== Falling Ribbons Effect ===== */
.ribbon-fall-container {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    overflow: hidden;
    z-index: 9998;
}

.ribbon {
    position: absolute;
    top: -50px;
    width: 8px;
    height: 60px;
    border-radius: 2px;
    opacity: 0.85;
    animation: ribbonFall linear infinite;
}

@keyframes ribbonFall {
    0% {
        transform: translateY(-60px) rotate(0deg);
    }
    100% {
        transform: translateY(110vh) rotate(360deg);
    }
}

/* ===== Falling Santa Icons ===== */
.santa {
    position: absolute;
    top: -60px;
    font-size: 22px;
    opacity: 0.9;
    animation: santaFall linear infinite;
}

@keyframes santaFall {
    0% {
        transform: translateY(-60px) rotate(0deg);
    }
    100% {
        transform: translateY(110vh) rotate(360deg);
    }
}
/* Snowflakes */
.snowflake {
    position: absolute;
    top: -20px;
    font-size: 14px;
    opacity: 0.6;
    animation: snowFall linear infinite;
}

@keyframes snowFall {
    0% { transform: translateY(-20px); }
    100% { transform: translateY(110vh); }
}

.bell {
    position: absolute;
    top: -30px;
    font-size: 18px;
    opacity: 0.8;
    animation: bellFall linear infinite;
}

@keyframes bellFall {
    0% { transform: translateY(-30px) rotate(0deg); }
    100% { transform: translateY(110vh) rotate(-360deg); }
}

    
</style>

<header class="page-header" role="banner">
                        <!-- we need this logo when user switches to nav-function-top
                        <div class="page-logo">
                            <a href="#" class="page-logo-link press-scale-down d-flex align-items-center position-relative" data-toggle="modal" data-target="#modal-shortcut">
                                <span class="page-logo-text mr-1">TechXpert WebApp</span>
                                <span class="position-absolute text-white opacity-50 small pos-top pos-right mr-2 mt-n2"></span>
                                <i class="fal fa-angle-down d-inline-block ml-1 fs-lg color-primary-300"></i>
                            </a>
                        </div>
                        -->
                        <!-- DOC: nav menu layout change shortcut -->
                        <div class="hidden-md-down dropdown-icon-menu position-relative">
                            <a href="#" class="header-btn btn js-waves-off" data-action="toggle" data-class="nav-function-hidden" title="Hide Navigation">
                                <i class="ni ni-menu"></i>
                            </a>
                            <ul>
                                <li>
                                    <a href="#" class="btn js-waves-off" data-action="toggle" data-class="nav-function-minify" title="Minify Navigation">
                                        <i class="ni ni-minify-nav"></i>
                                    </a>
                                </li>
                                <li>
                                    <a href="#" class="btn js-waves-off" data-action="toggle" data-class="nav-function-fixed" title="Lock Navigation">
                                        <i class="ni ni-lock-nav"></i>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <!-- DOC: mobile button appears during mobile width -->
                        <div class="hidden-lg-up">
                            <a href="#" class="header-btn btn press-scale-down" data-action="toggle" data-class="mobile-nav-on">
                                <i class="ni ni-menu"></i>
                            </a>
                        </div>
                        <!--div class="search">
                            <form class="app-forms hidden-xs-down" role="search" action="page_search.html" autocomplete="off">
                                <input type="text" id="search-field" placeholder="Search for anything" class="form-control" tabindex="1">
                                <a href="#" onclick="return false;" class="btn-danger btn-search-close js-waves-off d-none" data-action="toggle" data-class="mobile-search-on">
                                    <i class="fal fa-times"></i>
                                </a>
                            </form>
                        </div-->

						<div class="d-flex top-navigation">
							<?php
							include("../includes/_top_nav_structure.php");
							?>
						</div>



                        <div class="ml-auto d-flex">



                            <div>

                                <a href="#" class="header-icon" data-toggle="dropdown" title="My Apps">
                                    <i class="fal fa-cube"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-animated w-auto h-auto">
                                    <div class="dropdown-header bg-trans-gradient d-flex justify-content-center align-items-center rounded-top">
                                        <h4 class="m-0 text-center color-white">
                                            Quick Shortcut
                                            <small class="mb-0 opacity-80">User Applications & Addons</small>
                                        </h4>
                                    </div>
                                    <div class="custom-scroll h-100">
                                        <ul class="app-list">
                                            <li>
                                                <a href="../enterprises/view_enterprises.php" class="app-list-item hover-white">
                                                    <span class="icon-stack">
                                                        <i class="base-7 icon-stack-3x color-primary-600"></i>
                                                        <i class="base-3 icon-stack-2x color-primary-700"></i>
                                                        <i class="ni ni-settings icon-stack-1x text-white fs-lg"></i>
                                                    </span>
                                                    <span class="app-list-name">
                                                        Enterprises
                                                    </span>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="../projects/view_user_projects.php" class="app-list-item hover-white">
                                                    <span class="icon-stack">
                                                        <i class="base-19 icon-stack-3x color-primary-400"></i>
                                                        <i class="base-7 text-white icon-stack-1x"></i>
                                                        <i class="ni ni-settings color-primary-800 icon-stack-2x"></i>
                                                    </span>
                                                    <span class="app-list-name">
                                                        Projects
                                                    </span>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="../users/user_documents.php" class="app-list-item hover-white">
                                                    <span class="icon-stack">
                                                         <i class="base-4 icon-stack-3x color-danger-500"></i>
                                                        <i class="base-4 icon-stack-1x color-danger-400"></i>
                                                        <i class="ni ni-envelope icon-stack-1x text-white"></i>
                                                    </span>
                                                    <span class="app-list-name">
                                                        Documents
                                                    </span>
                                                </a>
                                            </li>
                                            <!--li>
                                                <a href="#" class="app-list-item hover-white">
                                                    <span class="icon-stack">
                                                        <i class="base-18 icon-stack-3x color-info-700"></i>
                                                        <span class="position-absolute pos-top pos-left pos-right color-white fs-md mt-2 fw-400">28</span>
                                                    </span>
                                                    <span class="app-list-name">
                                                        Calendar
                                                    </span>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="#" class="app-list-item hover-white">
                                                    <span class="icon-stack">
                                                        <i class="base-7 icon-stack-3x color-info-500"></i>
                                                        <i class="base-7 icon-stack-2x color-info-700"></i>
                                                        <i class="ni ni-graph icon-stack-1x text-white"></i>
                                                    </span>
                                                    <span class="app-list-name">
                                                        Stats
                                                    </span>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="#" class="app-list-item hover-white">
                                                    <span class="icon-stack">
                                                        <i class="base-4 icon-stack-3x color-danger-500"></i>
                                                        <i class="base-4 icon-stack-1x color-danger-400"></i>
                                                        <i class="ni ni-envelope icon-stack-1x text-white"></i>
                                                    </span>
                                                    <span class="app-list-name">
                                                        Messages
                                                    </span>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="#" class="app-list-item hover-white">
                                                    <span class="icon-stack">
                                                        <i class="base-4 icon-stack-3x color-fusion-400"></i>
                                                        <i class="base-5 icon-stack-2x color-fusion-200"></i>
                                                        <i class="base-5 icon-stack-1x color-fusion-100"></i>
                                                        <i class="fal fa-keyboard icon-stack-1x color-info-50"></i>
                                                    </span>
                                                    <span class="app-list-name">
                                                        Notes
                                                    </span>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="#" class="app-list-item hover-white">
                                                    <span class="icon-stack">
                                                        <i class="base-16 icon-stack-3x color-fusion-500"></i>
                                                        <i class="base-10 icon-stack-1x color-primary-50 opacity-30"></i>
                                                        <i class="base-10 icon-stack-1x fs-xl color-primary-50 opacity-20"></i>
                                                        <i class="fal fa-dot-circle icon-stack-1x text-white opacity-85"></i>
                                                    </span>
                                                    <span class="app-list-name">
                                                        Photos
                                                    </span>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="#" class="app-list-item hover-white">
                                                    <span class="icon-stack">
                                                        <i class="base-19 icon-stack-3x color-primary-400"></i>
                                                        <i class="base-7 icon-stack-2x color-primary-300"></i>
                                                        <i class="base-7 icon-stack-1x fs-xxl color-primary-200"></i>
                                                        <i class="base-7 icon-stack-1x color-primary-500"></i>
                                                        <i class="fal fa-globe icon-stack-1x text-white opacity-85"></i>
                                                    </span>
                                                    <span class="app-list-name">
                                                        Maps
                                                    </span>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="#" class="app-list-item hover-white">
                                                    <span class="icon-stack">
                                                        <i class="base-5 icon-stack-3x color-success-700 opacity-80"></i>
                                                        <i class="base-12 icon-stack-2x color-success-700 opacity-30"></i>
                                                        <i class="fal fa-comment-alt icon-stack-1x text-white"></i>
                                                    </span>
                                                    <span class="app-list-name">
                                                        Chat
                                                    </span>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="#" class="app-list-item hover-white">
                                                    <span class="icon-stack">
                                                        <i class="base-5 icon-stack-3x color-warning-600"></i>
                                                        <i class="base-7 icon-stack-2x color-warning-800 opacity-50"></i>
                                                        <i class="fal fa-phone icon-stack-1x text-white"></i>
                                                    </span>
                                                    <span class="app-list-name">
                                                        Phone
                                                    </span>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="#" class="app-list-item hover-white">
                                                    <span class="icon-stack">
                                                        <i class="base-6 icon-stack-3x color-danger-600"></i>
                                                        <i class="fal fa-chart-line icon-stack-1x text-white"></i>
                                                    </span>
                                                    <span class="app-list-name">
                                                        Projects
                                                    </span>
                                                </a>
                                            </li-->
                                            <li class="w-100">
                                                <a href="#" class="btn btn-default mt-4 mb-2 pr-5 pl-5"> Add more apps </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>


                            <!-- app message -->
                            <!--a href="#" class="header-icon" data-toggle="modal" data-target=".js-modal-messenger">
                                <i class="fal fa-globe"></i>
                                <span class="badge badge-icon">!</span>
                            </a-->
                            <?php include __DIR__ . '/portal_notification_header.php'; ?>
                            <!-- app user menu -->
                            <div>
                                <span class="header-icon d-flex align-items-center justify-content-center ml-2 js-get-date"></span>
                            </div>
                            <div>

                                <a href="#" data-toggle="dropdown" title="<?php echo $_SESSION['pb_username']; ?>" class="header-icon d-flex align-items-center justify-content-center ml-2">

                                    <!-- you can also add username next to the avatar with the codes below:
									<span class="ml-1 mr-1 text-truncate text-truncate-header hidden-xs-down">Me</span>
									<i class="ni ni-chevron-down hidden-xs-down"></i> -->
                                    <i class="ni ni-user"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-animated dropdown-lg">
                                    <div class="dropdown-header bg-trans-gradient d-flex flex-row py-4 rounded-top">
                                        <div class="d-flex flex-row align-items-center mt-1 mb-1 color-white">

                                            <div class="info-card-text">
                                                <div class="fs-lg text-truncate text-truncate-lg"><?php echo $_SESSION['pb_username'];?></div>
                                                <span class="text-truncate text-truncate-md opacity-80"><?php echo $_SESSION['UserType'];?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="dropdown-divider m-0"></div>


                                    <div class="dropdown-divider m-0"></div>
                                    <a class="dropdown-item fw-500 pt-3 pb-3" href="../authentication/logout.php">
                                        <span data-i18n="drpdwn.page-logout">Logout</span>
                                        <!--span class="float-right fw-n">&commat;codexlantern</span-->
                                    </a>
                                    <?php 
                                    if($UserType != "Admin" && $UserType != 'Corporate Admin' && $UserType != 'Corporate Branch User' && $UserType != 'Vendor')
                                    {
                                    ?>
                                    <a class="dropdown-item fw-500 pt-3 pb-3" href="../work-zone/">
                                        <span>My Work Zone</span>
                                    </a>
                                    <a class="dropdown-item fw-500 pt-3 pb-3" href="../employees/view_profile_details.php">
                                        <span data-i18n="drpdwn.page-logout">My Profile</span>
                                        <!--span class="float-right fw-n">&commat;codexlantern</span-->
                                    </a>
                                    <?php
                                    }
                                    ?>
                                    
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center" style="display:none;">

                            <!--a target="_blank" href="https://www.store.techxpertgroup.in/store/index.php" class="btn btn-primary">TechXpert Store</a-->

                            <?php

                            if($_SESSION['UserType'] == "Corporate Admin" || $_SESSION['UserType'] == "Corporate Branch User" )
                            {
                            ?>

                                <!--a target="_blank" href="../cart/view-cart" class="ml-3 btn btn-primary">Your Cart</a-->

                            <?php 
                            }  
                            ?>
                            </div>
                    </header>

<?php if ($isChristmas): ?>
<script>
    const isMobile = window.innerWidth < 768;
      
      const ribbonCount = isMobile ? 15 : 30;
const santaCount  = isMobile ? 5  : 10;

(function () {
    const container = document.querySelector('.ribbon-fall-container');

    const ribbonColors = ['#e53935', '#43a047', '#1e88e5', '#fdd835', '#8e24aa'];
    const santaIcons = ['ðŸŽ…']; // emoji fallback safe

    // Create ribbons
    for (let i = 0; i < 30; i++) {
        const ribbon = document.createElement('div');
        ribbon.className = 'ribbon';
        ribbon.style.left = Math.random() * 100 + 'vw';
        ribbon.style.backgroundColor = ribbonColors[Math.floor(Math.random() * ribbonColors.length)];
        ribbon.style.animationDuration = (4 + Math.random() * 6) + 's';
        ribbon.style.animationDelay = Math.random() * 5 + 's';
        container.appendChild(ribbon);
    }

    // Create Santa icons
    for (let i = 0; i < 10; i++) {
        const santa = document.createElement('div');
        santa.className = 'santa';
        santa.innerText = santaIcons[0];
        santa.style.left = Math.random() * 100 + 'vw';
        santa.style.animationDuration = (6 + Math.random() * 8) + 's';
        santa.style.animationDelay = Math.random() * 6 + 's';
        container.appendChild(santa);
    }

    // Snowflakes
for (let i = 0; i < 15; i++) {
    const snow = document.createElement('div');
    snow.className = 'snowflake';
    snow.innerHTML = 'â„';
    snow.style.left = Math.random() * 100 + 'vw';
    snow.style.animationDuration = (8 + Math.random() * 6) + 's';
    snow.style.animationDelay = Math.random() * 5 + 's';
    container.appendChild(snow);
}

// Bells (rare)
for (let i = 0; i < 5; i++) {
    const bell = document.createElement('div');
    bell.className = 'bell';
    bell.innerHTML = 'ðŸ””';
    bell.style.left = Math.random() * 100 + 'vw';
    bell.style.animationDuration = (10 + Math.random() * 8) + 's';
    bell.style.animationDelay = Math.random() * 10 + 's';
    container.appendChild(bell);
}

setTimeout(() => {
    if (container) container.innerHTML = '';
}, 20000);


})();
</script>
<?php endif; ?>
