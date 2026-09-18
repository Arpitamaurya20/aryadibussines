<?php
// Get user information from session
@session_start();
$userID = $_SESSION['UserID'] ?? null;
$userEmail = $_SESSION['pp_email'] ?? '';
$userType = $_SESSION['pp_UserType'] ?? '';

// Initialize database connection and get user details
$userDetails = null;
$userName = 'User';
$userImage = '../assets/img/avatar5.png'; // Default image
$userRole = '';
$memberSince = '';

if ($userID) {
    require_once('../include/autoloader.inc.php');
    $dbh = new Dbh();
    $conn = $dbh->_connectodb();
    $users = new Users($conn);
    
    // Get user details
    $userDetails = $users->GetUserDetailsByID($userID);
    
    if ($userDetails) {
        $userName = $userDetails['Name'] ?? $userEmail;
        $userRole = $userDetails['UserType'] ?? '';
        $createdDate = $userDetails['CreatedDate'] ?? '';
        
        // Format member since date
        if ($createdDate) {
            $dateObj = DateTime::createFromFormat('Y-m-d', $createdDate);
            if ($dateObj) {
                $memberSince = $dateObj->format('M. Y');
            }
        }
        
        // Get user image if available (assuming there's an image field in user_details)
        // For now, using default image path
        if (isset($userDetails['Image']) && !empty($userDetails['Image'])) {
            $userImage = '../' . ltrim($userDetails['Image'], './');
        }
    }
}

// Get unread messages count (placeholder - can be replaced with actual query)
$unreadMessagesCount = 0;
$messages = array(); // Placeholder for messages

// Get unread notifications count (placeholder - can be replaced with actual query)
$unreadNotificationsCount = 0;
$notifications = array(); // Placeholder for notifications

// Helper function to get time ago
function timeAgo($datetime) {
    if (empty($datetime)) return '';
    
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' mins ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    if ($diff < 2592000) return floor($diff / 604800) . ' weeks ago';
    if ($diff < 31536000) return floor($diff / 2592000) . ' months ago';
    return floor($diff / 31536000) . ' years ago';
}
?>

<!--begin::Header-->
<nav class="app-header navbar navbar-expand bg-body">
<!--begin::Container-->
<div class="container-fluid">
  <!--begin::Start Navbar Links-->
  <ul class="navbar-nav">
    <li class="nav-item">
      <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button" aria-label="Toggle navigation menu">
        <i class="bi bi-list"></i>
      </a>
    </li>
    <li class="nav-item d-none d-md-block"><a href="../dashboard/admin-dashboard.php" class="nav-link">Home</a></li>
    <li class="nav-item d-none d-md-block"><a href="#" class="nav-link">Contact</a></li>
  </ul>
  <!--end::Start Navbar Links-->
  <!--begin::End Navbar Links-->
  <ul class="navbar-nav ms-auto">
    <!--begin::Navbar Search-->
    <li class="nav-item">
      <a class="nav-link" data-widget="navbar-search" href="#" role="button">
        <i class="bi bi-search"></i>
      </a>
    </li>
    <!--end::Navbar Search-->
    <!--begin::Messages Dropdown Menu-->
    <?php if ($unreadMessagesCount > 0 || !empty($messages)): ?>
    <li class="nav-item dropdown">
      <a class="nav-link" data-bs-toggle="dropdown" href="#">
        <i class="bi bi-chat-text"></i>
        <?php if ($unreadMessagesCount > 0): ?>
        <span class="navbar-badge badge text-bg-danger"><?= $unreadMessagesCount ?></span>
        <?php endif; ?>
      </a>
      <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
        <?php if (!empty($messages)): ?>
          <?php foreach (array_slice($messages, 0, 3) as $message): ?>
          <a href="#" class="dropdown-item">
            <!--begin::Message-->
            <div class="d-flex">
              <div class="flex-shrink-0">
                <img
                  src="<?= htmlspecialchars($message['sender_image'] ?? '../assets/img/user1-128x128.jpg') ?>"
                  alt="User Avatar"
                  class="img-size-50 rounded-circle me-3"
                />
              </div>
              <div class="flex-grow-1">
                <h3 class="dropdown-item-title">
                  <?= htmlspecialchars($message['sender_name'] ?? 'Unknown') ?>
                  <?php if (isset($message['is_important']) && $message['is_important']): ?>
                  <span class="float-end fs-7 text-danger"><i class="bi bi-star-fill"></i></span>
                  <?php endif; ?>
                </h3>
                <p class="fs-7"><?= htmlspecialchars($message['subject'] ?? $message['message'] ?? '') ?></p>
                <p class="fs-7 text-secondary">
                  <i class="bi bi-clock-fill me-1"></i> <?= timeAgo($message['created_at'] ?? '') ?>
                </p>
              </div>
            </div>
            <!--end::Message-->
          </a>
          <div class="dropdown-divider"></div>
          <?php endforeach; ?>
        <?php else: ?>
          <a href="#" class="dropdown-item text-center text-muted">
            <p class="mb-0">No messages</p>
          </a>
          <div class="dropdown-divider"></div>
        <?php endif; ?>
        <a href="#" class="dropdown-item dropdown-footer">See All Messages</a>
      </div>
    </li>
    <?php endif; ?>
    <!--end::Messages Dropdown Menu-->
    <!--begin::Notifications Dropdown Menu-->
    <li class="nav-item dropdown">
      <a class="nav-link" data-bs-toggle="dropdown" href="#">
        <i class="bi bi-bell-fill"></i>
        <?php if ($unreadNotificationsCount > 0): ?>
        <span class="navbar-badge badge text-bg-warning"><?= $unreadNotificationsCount ?></span>
        <?php endif; ?>
      </a>
      <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
        <span class="dropdown-item dropdown-header"><?= $unreadNotificationsCount > 0 ? $unreadNotificationsCount : 'No' ?> Notifications</span>
        <div class="dropdown-divider"></div>
        <?php if (!empty($notifications)): ?>
          <?php foreach (array_slice($notifications, 0, 3) as $notification): ?>
          <a href="<?= htmlspecialchars($notification['link'] ?? '#') ?>" class="dropdown-item">
            <i class="<?= htmlspecialchars($notification['icon'] ?? 'bi bi-bell') ?> me-2"></i> <?= htmlspecialchars($notification['title'] ?? '') ?>
            <span class="float-end text-secondary fs-7"><?= timeAgo($notification['created_at'] ?? '') ?></span>
          </a>
          <div class="dropdown-divider"></div>
          <?php endforeach; ?>
        <?php else: ?>
          <a href="#" class="dropdown-item text-center text-muted">
            <p class="mb-0">No notifications</p>
          </a>
          <div class="dropdown-divider"></div>
        <?php endif; ?>
        <a href="#" class="dropdown-item dropdown-footer">See All Notifications</a>
      </div>
    </li>
    <!--end::Notifications Dropdown Menu-->
    <!--begin::Fullscreen Toggle-->
    <li class="nav-item">
      <a class="nav-link" href="#" data-lte-toggle="fullscreen">
        <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
        <i data-lte-icon="minimize" class="bi bi-fullscreen-exit" style="display: none"></i>
      </a>
    </li>
    <!--end::Fullscreen Toggle-->
    <!--begin::User Menu Dropdown-->
    <li class="nav-item dropdown user-menu">
      <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
        <img
          src="<?= htmlspecialchars($userImage) ?>"
          class="user-image rounded-circle shadow"
          alt="User Image"
        />
        <span class="d-none d-md-inline"><?= htmlspecialchars($userName) ?></span>
      </a>
      <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
        <!--begin::User Image-->
        <li class="user-header text-bg-primary">
          <img
            src="<?= htmlspecialchars($userImage) ?>"
            class="rounded-circle shadow"
            alt="User Image"
          />
          <p>
            <?= htmlspecialchars($userName) ?><?= $userRole ? ' - ' . htmlspecialchars($userRole) : '' ?>
            <?php if ($memberSince): ?>
            <small>Member since <?= htmlspecialchars($memberSince) ?></small>
            <?php endif; ?>
          </p>
        </li>
        <!--end::User Image-->
        <!--begin::Menu Body-->
        <li class="user-body">
          <!--begin::Row-->
          <div class="row">
            <div class="col-4 text-center"><a href="#">Followers</a></div>
            <div class="col-4 text-center"><a href="#">Sales</a></div>
            <div class="col-4 text-center"><a href="#">Friends</a></div>
          </div>
          <!--end::Row-->
        </li>
        <!--end::Menu Body-->
        <!--begin::Menu Footer-->
        <li class="user-footer">
          <a href="#" class="btn btn-default btn-flat">Profile</a>
          <a href="#" onclick="logout()" class="btn btn-default btn-flat float-end">Sign out</a>
          <a href="../../admin/dashboard/my_kpi_dashboard" class="btn btn-default btn-flat ms-1" title="Switch to Main Admin">
            <i class="bi bi-arrow-repeat me-1"></i> Switch Admin
          </a>
        </li>
        <!--end::Menu Footer-->
      </ul>
    </li>
    <!--end::User Menu Dropdown-->
  </ul>
  <!--end::End Navbar Links-->
</div>
<!--end::Container-->
</nav>
<!--end::Header-->
