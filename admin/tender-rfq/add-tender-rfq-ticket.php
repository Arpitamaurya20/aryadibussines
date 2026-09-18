    <?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    require_once('../includes/autoloader.inc.php');
    include('../controllers/common_controllers.php');
    include('../company/controller/company_controller.php');
    include('../branch/controller/branch_controller.php');
    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);
    $conn = _connectodb();
    if (!isset($_Nav_Tender_RFQ) || !$_Nav_Tender_RFQ) {
        header('HTTP/1.0 403 Forbidden');
        echo 'Access denied.';
        exit;
    }
    $AllCompany = getAllCompanies($conn);
    $AllBranch = getAllBranchesWithName($conn);
    $CorporateID = -1;
    $BranchID = -1;
    $TicketManager = ($UserType === 'Admin' || $UserType === 'Super Admin');
    if ($UserType === 'Corporate Admin') {
        $CorporateID = (int) ($_SESSION['Roles']['CorporateID'] ?? -1);
    }
    if ($UserType === 'Corporate Branch User') {
        $CorporateID = (int) ($_SESSION['Roles']['CorporateID'] ?? -1);
        $BranchID = (int) ($_SESSION['Roles']['BranchID'] ?? -1);
    }
    if (isset($_SESSION['Roles']['EmployeeRoles'])) {
        foreach ($_SESSION['Roles']['EmployeeRoles'] as $E_Role) {
            if ($E_Role === 'Ticket Manager') {
                $TicketManager = true;
            }
        }
    }
    ?>
    <meta charset="utf-8">
    <title>Raise Tender RFQ</title>
    <?php include('../includes/common_head_content.php'); ?>
    <style>.select2-container { z-index: 1; }</style>
</head>
<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
    <?php include('../js/theme_settings.js'); ?>
    <div class="page-wrapper">
        <div class="page-inner">
            <?php include('../navigation/admin_navigation.php'); ?>
            <div class="page-content-wrapper">
                <?php include('../includes/common_header.php'); ?>
                <main id="js-page-content" role="main" class="page-content">
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">Aryadibusiness</a></li>
                        <li class="breadcrumb-item"><a href="view-tender-rfq-tickets">Tender RFQ</a></li>
                        <li class="breadcrumb-item active">Raise ticket</li>
                    </ol>
                    <div class="panel">
                        <div class="panel-hdr">
                            <h2>Raise <span class="fw-300"><i>Tender RFQ</i></span></h2>
                        </div>
                        <div class="panel-container show">
                            <div class="panel-content">
                                <form method="post" action="action/save-ticket.php" id="trfq_form">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label">Corporate <?php if ($CorporateID === -1 || $TicketManager) { ?><span class="text-danger">*</span><?php } ?></label>
                                                <select name="corporate_id" class="select2 form-control" id="corporate_id" <?= ($CorporateID !== -1 && !$TicketManager) ? 'disabled' : ''; ?>>
                                                    <option value="">Select</option>
                                                    <?php foreach ($AllCompany as $CompanyValue) {
                                                        if (!$TicketManager && $CorporateID !== -1 && (int) $CompanyValue['ID'] !== $CorporateID) {
                                                            continue;
                                                        }
                                                        $sel = ($CorporateID !== -1 && (int) $CompanyValue['ID'] === $CorporateID) ? ' selected' : '';
                                                        ?>
                                                    <option value="<?= (int) $CompanyValue['ID']; ?>"<?= $sel; ?>><?= htmlspecialchars($CompanyValue['CompanyName']); ?></option>
                                                    <?php } ?>
                                                </select>
                                                <?php if ($CorporateID !== -1 && !$TicketManager) { ?>
                                                    <input type="hidden" name="corporate_id" value="<?= (int) $CorporateID; ?>">
                                                <?php } ?>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label">Branch</label>
                                                <select name="branch_id" class="select2 form-control" id="branch_id">
                                                    <option value="">Optional</option>
                                                    <?php foreach ($AllBranch as $BranchValue) {
                                                        if (!$TicketManager && $CorporateID !== -1 && (int) $BranchValue['CompanyID'] !== $CorporateID) {
                                                            continue;
                                                        }
                                                        if (!$TicketManager && $BranchID !== -1 && (int) $BranchValue['ID'] !== $BranchID) {
                                                            continue;
                                                        }
                                                        ?>
                                                    <option data-company="<?= (int) $BranchValue['CompanyID']; ?>" value="<?= (int) $BranchValue['ID']; ?>"><?= htmlspecialchars($BranchValue['BranchSite'] ?? ''); ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="form-group">
                                                <label class="form-label">Quotation ref</label>
                                                <input type="text" name="quotation_ref" class="form-control" placeholder="Leave blank for auto TECHX-EST-xxxxx">
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="form-group">
                                                <label class="form-label">Quotation date</label>
                                                <input type="date" name="quotation_date" class="form-control" value="<?= date('Y-m-d'); ?>">
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="form-group">
                                                <label class="form-label">Expiry date</label>
                                                <input type="date" name="expiry_date" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label">Client reference ticket</label>
                                                <input type="text" name="client_reference" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label">Place of supply</label>
                                                <input type="text" name="place_of_supply" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label">Sales person</label>
                                                <input type="text" name="sales_person" class="form-control" value="<?= htmlspecialchars($_SESSION['pb_username'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label">Customer name</label>
                                                <input type="text" name="customer_name" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label">Customer contact</label>
                                                <input type="text" name="customer_contact" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label class="form-label">RFQ title <span class="text-danger">*</span></label>
                                                <input type="text" name="title" class="form-control" required maxlength="500">
                                            </div>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label class="form-label">Description</label>
                                                <textarea name="description" class="form-control" rows="3"></textarea>
                                            </div>
                                        </div>
                                        <div class="col-lg-12">
                                            <h5 class="mb-2">Bill to / Ship to (for quotation PDF)</h5>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label">Bill to — entity</label>
                                                <input type="text" name="bill_to_entity" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label class="form-label">Bill to — GSTIN</label>
                                                <input type="text" name="bill_to_gstin" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label class="form-label">Bill to — address</label>
                                                <textarea name="bill_to_address" class="form-control" rows="2"></textarea>
                                            </div>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label class="form-label">Ship to</label>
                                                <textarea name="ship_to" class="form-control" rows="2"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Create ticket</button>
                                    <a href="view-tender-rfq-tickets" class="btn btn-secondary ml-2">Cancel</a>
                                </form>
                            </div>
                        </div>
                    </div>
                </main>
                <div class="page-content-overlay" data-action="toggle" data-class="mobile-nav-on"></div>
                <?php include('../includes/common_footer.php'); ?>
            </div>
        </div>
    </div>
    <?php include('../includes/common_modules.php'); ?>
    <?php include('../includes/common_scripts.php'); ?>
    <script>
        (function () {
            var select2Opts = {
                width: '100%',
                allowClear: true,
                dropdownParent: $('#trfq_form').closest('.panel-content')
            };

            function filterBranches() {
                var cid = $('#corporate_id').val();
                var $b = $('#branch_id');
                if ($b.data('select2')) {
                    $b.select2('destroy');
                }
                $b.find('option').each(function () {
                    var $o = $(this);
                    if ($o.val() === '') {
                        return;
                    }
                    var bc = $o.data('company');
                    $o.toggle(!cid || String(bc) === String(cid));
                });
                var sel = $b.val();
                if (sel) {
                    var $opt = $b.find('option').filter(function () {
                        return String($(this).val()) === String(sel);
                    });
                    if (!$opt.length || !$opt.is(':visible')) {
                        $b.val('');
                    }
                }
                $b.select2($.extend({}, select2Opts, {
                    placeholder: 'Search or select branch (optional)'
                }));
            }

            $(document).ready(function () {
                $('#corporate_id').select2($.extend({}, select2Opts, {
                    placeholder: 'Search or select corporate'
                }));
                filterBranches();
                $('#corporate_id').on('change', filterBranches);
            });
        })();
    </script>
</body>
</html>
