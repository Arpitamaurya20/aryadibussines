<script src="../js/vendors.bundle.js"></script>
<script src="../js/app.bundle.js"></script>
<script src="../js/commonjs.js?v=<?php echo @filemtime(__DIR__ . '/../js/commonjs.js'); ?>"></script>
<script src="../js/modules/portal-notifications.js"></script>
<script src="../js/modules/employee-asset-ack-block.js"></script>

<script src="../js/formplugins/select2/select2.bundle.js"></script>

<!-- 3rd Party plugins by Prateek -->

<script src="../plugins/alertifyjs/alertify.min.js"></script>
<script type="text/javascript">
(function () {
    if (!window.alertify || !alertify.defaults || !alertify.defaults.glossary) {
        return;
    }
    var host = (window.location.hostname || '').split('.')[0];
    alertify.defaults.glossary.title = host.indexOf('innov') !== -1 ? 'Innov' : 'Aryadibusiness';
})();
</script>
<script type="text/javascript">
$(document).ready(function() {
    $('.navdata li span a').click(function() {

        $('.navdata li.active').removeClass('active');

        var $parent = $(this).parent();
        $parent.addClass('active');
        e.preventDefault();
    });
});

(function () {
  var preloader = document.getElementById("preloader");

  function showPageLoader() {
    if (!preloader) {
      return;
    }
    preloader.style.display = "block";
    document.body.classList.add("is-page-loading");
  }

  function hidePageLoader() {
    if (!preloader) {
      return;
    }
    preloader.style.display = "none";
    document.body.classList.remove("is-page-loading");
  }

  document.addEventListener("click", function (event) {
    if (!preloader) {
      return;
    }
    var isVisible = window.getComputedStyle(preloader).display !== "none";
    if (!isVisible) {
      return;
    }
    if (document.body.classList.contains("modal-open")) {
      return;
    }
    if (event.target.closest && event.target.closest(".modal, .modal-backdrop, .alertify, .ajs-modal")) {
      return;
    }
    event.preventDefault();
    event.stopPropagation();
  }, true);

  document.addEventListener("click", function (event) {
    var link = event.target.closest("#js-nav-menu a[href]");
    if (!link) {
      return;
    }
    var href = link.getAttribute("href");
    if (!href || href === "#") {
      return;
    }
    showPageLoader();
  }, true);

  window.addEventListener("load", hidePageLoader);
  window.addEventListener("pageshow", function (event) {
    if (event.persisted) {
      hidePageLoader();
    }
  });
  if (document.readyState === "complete") {
    hidePageLoader();
  }
})();

/* Load after theme CSS so button labels stay visible */
(function () {
  if (document.getElementById('admin-button-fix-css')) {
    return;
  }
  var link = document.createElement('link');
  link.id = 'admin-button-fix-css';
  link.rel = 'stylesheet';
  link.href = '../css/admin-button-fix.css?v=2';
  document.head.appendChild(link);
})();

</script>
<!-- Lucide Icons -->
<script src="https://unpkg.com/lucide@latest" defer></script>