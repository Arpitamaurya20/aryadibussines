<!--APP-SIDEBAR-->
<?php
$nav = new Navigation();
$nav->setNavigation($_SESSION['pp_UserType']);
?>
<style>
  .app-sidebar {
    background: linear-gradient(180deg, #027dc1 0%, #d42d24 100%) !important;
  }
  .app-sidebar .nav-link,
  .app-sidebar .nav-icon,
  .app-sidebar .brand-link,
  .app-sidebar .brand-link .brand-text {
    color: #ffffff !important;
  }
  .app-sidebar .nav-link.active,
  .app-sidebar .nav-link:hover {
    background: rgba(255,255,255,0.16) !important;
    border-radius: 8px;
  }
  .app-sidebar .sidebar-brand {
    background: #ffffff !important;
    border-bottom: 1px solid rgba(0,0,0,0.1);
    padding: 8px 10px;
  }
  .app-sidebar .sidebar-brand .brand-link {
    border-radius: 8px;
    justify-content: center;
  }
  .app-sidebar .sidebar-brand .brand-image {
    max-height: 46px;
    width: auto;
    object-fit: contain;
    filter: none;
    opacity: 1 !important;
  }

  @media (max-width: 991.98px) {
    .app-header {
      z-index: 1040 !important;
    }
    .app-sidebar {
      width: min(85vw, 280px);
    }
    .app-sidebar .nav-link p {
      white-space: normal;
      overflow: visible;
      text-overflow: unset;
    }
    .app-header [data-lte-toggle="sidebar"] {
      font-size: 1.35rem;
      padding: 0.35rem 0.65rem;
    }
  }
</style>
<script>
(function () {
  var mobileMq = window.matchMedia('(max-width: 991.98px)');

  function syncMobileSidebar() {
    if (!document.body) {
      return;
    }
    if (mobileMq.matches) {
      document.body.classList.remove('sidebar-open');
      document.body.classList.add('sidebar-collapse');
      return;
    }
    if (!document.body.classList.contains('sidebar-mini')) {
      document.body.classList.remove('sidebar-collapse');
      document.body.classList.add('sidebar-open');
    }
  }

  syncMobileSidebar();
  if (mobileMq.addEventListener) {
    mobileMq.addEventListener('change', syncMobileSidebar);
  } else if (mobileMq.addListener) {
    mobileMq.addListener(syncMobileSidebar);
  }
})();
</script>

<!--begin::Sidebar-->
<!--begin::Sidebar-->
<aside class="app-sidebar shadow" data-bs-theme="dark">
  <!-- Sidebar Brand -->
  <div class="sidebar-brand">
    <a href="../dashboard/admin-dashboard" class="brand-link">
      <img src="https://techxpertindia.in/admin/img/tech-logo.jpg" alt="Techxpert Logo" class="brand-image opacity-100 shadow" />
    </a>
  </div>

  <!-- Sidebar Menu -->
  <div class="sidebar-wrapper">
    <nav class="mt-2">
      <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="navigation">

        <!-- DASHBOARD -->
        <?php if ($nav->_Nav_Dashboard) { ?>
        <li class="nav-item">
          <a href="../dashboard/admin-dashboard" class="nav-link">
            <i class="nav-icon bi bi-speedometer"></i>
            <p>Dashboard</p>
          </a>
        </li>
        <?php } ?>

        <!-- HRMS INDIVIDUAL LINKS -->
        <?php if ($nav->_Nav_HRMS) { ?>
        <li class="nav-item">
          <a href="../hrms-onboarding/view-onboarding" class="nav-link">
            <i class="nav-icon bi bi-person-plus-fill"></i>
            <p>Employee Onboarding</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="../hrms-employees/view-employees" class="nav-link">
            <i class="nav-icon bi bi-people-fill"></i>
            <p>Employee Directory</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="../hrms-attendance/view-attendance" class="nav-link">
            <i class="nav-icon bi bi-calendar3"></i>
            <p>Attendance Calendar</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="../hrms-salary/view-payroll" class="nav-link">
            <i class="nav-icon bi bi-cash-stack"></i>
            <p>Payroll &amp; Slips</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="../hrms-salary/view-rules" class="nav-link">
            <i class="nav-icon bi bi-sliders2"></i>
            <p>Salary Rules</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="../hrms-kpi/view-kpi" class="nav-link">
            <i class="nav-icon bi bi-bar-chart-line-fill"></i>
            <p>Employee KPI</p>
          </a>
        </li>
        <?php } ?>

        <!-- RESOURCE LIBRARY -->
        <?php if ($nav->_Nav_Blog) { // Using same permission check, can be changed later ?>
        <li class="nav-item">
          <a href="../resources/view-resources" class="nav-link">
            <i class="nav-icon bi bi-folder-fill"></i>
            <p>Resource Library</p>
          </a>
        </li>
        <?php } ?>

      </ul>
    </nav>
  </div>
</aside>
