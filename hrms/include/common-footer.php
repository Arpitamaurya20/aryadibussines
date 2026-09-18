<!--begin::Footer-->
<footer class="app-footer text-muted small py-3">

  <!--begin::Right Text-->
  <div class="float-end d-none d-sm-inline">
      Powered by <b>Techxpert</b>
  </div>
  <!--end::Right Text-->

  <!--begin::Left Text-->
  <strong>
      © <?= date('Y'); ?> 
      <span class="text-primary">Techxpert</span>
  </strong>
  | All Rights Reserved.
  <!--end::Left Text-->

</footer>
<!--end::Footer-->
<?php if (empty($GLOBALS['hrms_adminlte_loaded'])) { $GLOBALS['hrms_adminlte_loaded'] = true; ?>
<script src="../js/adminlte.js"></script>
<?php } ?>
