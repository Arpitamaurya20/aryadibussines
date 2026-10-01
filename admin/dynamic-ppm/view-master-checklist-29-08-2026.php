<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    include('../controllers/common_controllers.php');
    include('controller/dynamic_ppm_controller.php');
    setNavigation($_SESSION['Roles']);
    SessionCheck();
    $conn = _connectodb();
    setTimeZone();

    $categories = getDynamicPPMCategories($conn);
    $companies = getDynamicPPMCompanies($conn);
    $checklists = getDynamicPPMChecklistMasters($conn, false);
    $mappings = getDynamicPPMCompanyChecklistMappings($conn);
    ?>
    <meta charset="utf-8">
    <title>Dynamic PPM Master Setup - TechXpert</title>
    <?php include('../includes/common_head_content.php'); ?>
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
                    <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">TechXpert</a></li>
                    <li class="breadcrumb-item">PPM</li>
                    <li class="breadcrumb-item active">Dynamic PPM Master Setup</li>
                </ol>

                <div class="row">
                    <div class="col-xl-12">
                        <?php if (isset($_GET['msg']) && trim((string) $_GET['msg']) !== '') { ?>
                            <div class="alert <?php echo (isset($_GET['status']) && $_GET['status'] === 'success') ? 'alert-success' : 'alert-danger'; ?>">
                                <?php echo htmlspecialchars($_GET['msg']); ?>
                            </div>
                        <?php } ?>
                        <div class="alert alert-info">
                            <strong>Setup Order:</strong> 1) Create Checklist Master, 2) Add Checklist Items, 3) Map Company to Checklist.
                            <a href="view-checklists.php" class="btn btn-sm btn-outline-primary ml-2">View All Checklists</a>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-5">
                        <div class="panel">
                            <div class="panel-hdr"><h2>Step 1: Checklist Master</h2></div>
                            <div class="panel-container show"><div class="panel-content">
                                <form method="post" action="action/save_checklist_master.php">
                                    <div class="form-group">
                                        <label>Category *</label>
                                        <select name="CategoryID" id="master_category_id" class="form-control dppm-select2" required>
                                            <option value="">Select Category</option>
                                            <?php foreach ($categories as $cat) { ?>
                                                <option value="<?php echo (int) $cat['ID']; ?>"><?php echo htmlspecialchars($cat['CategoriesName']); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Checklist Code</label>
                                        <input type="text" id="checklist_code_preview" class="form-control" placeholder="Next code preview">
                                        <small class="text-muted">Preview of next code for selected category. Each saved checklist gets the next code automatically.</small>
                                    </div>
                                    <div class="form-group">
                                        <label>Checklist Name(s) *</label>
                                        <textarea name="ChecklistNames" class="form-control" rows="5" placeholder="Enter one checklist name per line&#10;Plumbing Preventive Checklist V1&#10;Plumbing Monthly Checklist&#10;Plumbing Quarterly Checklist" required></textarea>
                                        <small class="text-muted">You can add multiple checklist names at once (one per line). Each gets its own auto-generated code.</small>
                                    </div>
                                    <div class="form-group">
                                        <label>Version</label>
                                        <input type="number" name="VersionNo" class="form-control" value="1" min="1">
                                    </div>
                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea name="Description" class="form-control" rows="2"></textarea>
                                    </div>
                                    <input type="hidden" name="CreatedBy" value="<?php echo isset($_SESSION['Name']) ? htmlspecialchars($_SESSION['Name']) : ''; ?>">
                                    <button type="submit" class="btn btn-primary">Save Checklist Master(s)</button>
                                </form>
                            </div></div>
                        </div>
                    </div>

                    <div class="col-xl-7">
                        <div class="panel">
                            <div class="panel-hdr">
                                <h2>Checklist Masters</h2>
                                <a href="view-checklists.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="panel-container show"><div class="panel-content">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm">
                                        <thead><tr><th>Code</th><th>Name</th><th>Category</th><th>Version</th><th>Action</th></tr></thead>
                                        <tbody>
                                        <?php foreach ($checklists as $row) { ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['ChecklistCode']); ?></td>
                                                <td><?php echo htmlspecialchars($row['ChecklistName']); ?></td>
                                                <td><?php echo htmlspecialchars($row['CategoryName']); ?></td>
                                                <td><?php echo (int) $row['VersionNo']; ?></td>
                                                <td>
                                                    <a href="view-checklist-details.php?id=<?php echo (int) $row['ID']; ?>" class="btn btn-xs btn-primary">View</a>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div></div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-6">
                        <div class="panel">
                            <div class="panel-hdr"><h2>Step 2: Checklist Items</h2></div>
                            <div class="panel-container show"><div class="panel-content">
                                <?php
                                $dppmBulkChecklists = $checklists;
                                $dppmBulkChecklistID = 0;
                                $dppmBulkFormId = 'master_bulk_items_form';
                                $dppmBulkRedirect = '';
                                include('include/checklist-items-bulk-form.php');
                                ?>
                            </div></div>
                        </div>
                    </div>

                    <div class="col-xl-6">
                        <div class="panel">
                            <div class="panel-hdr"><h2>Step 3: Company Mapping</h2></div>
                            <div class="panel-container show"><div class="panel-content">
                                <form method="post" action="action/save_company_checklist_mapping.php">
                                    <div class="form-group">
                                        <label>Company *</label>
                                        <select name="CorporateID" class="form-control dppm-select2" required>
                                            <option value="">Select Company</option>
                                            <?php foreach ($companies as $company) { ?>
                                                <option value="<?php echo (int) $company['ID']; ?>"><?php echo htmlspecialchars($company['CompanyName']); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Category *</label>
                                        <select name="CategoryID" class="form-control dppm-select2" required>
                                            <option value="">Select Category</option>
                                            <?php foreach ($categories as $cat) { ?>
                                                <option value="<?php echo (int) $cat['ID']; ?>"><?php echo htmlspecialchars($cat['CategoriesName']); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Checklist *</label>
                                        <select name="ChecklistID" class="form-control dppm-select2" required>
                                            <option value="">Select Checklist</option>
                                            <?php foreach ($checklists as $row) { ?>
                                                <option value="<?php echo (int) $row['ID']; ?>"><?php echo htmlspecialchars($row['ChecklistCode'] . ' - ' . $row['ChecklistName']); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Validity *</label>
                                        <select name="MappingValidity" id="mapping_validity" class="form-control dppm-select2">
                                            <option value="always" selected>Always (active until you change mapping)</option>
                                            <option value="date_range">Custom Date Range</option>
                                        </select>
                                    </div>
                                    <div id="mapping_always_note" class="alert alert-light border py-2">
                                        Mapping will stay active with no end date. You can change it anytime by saving a new mapping.
                                    </div>
                                    <div id="mapping_date_range_fields" class="form-row" style="display:none;">
                                        <div class="col-md-6 form-group">
                                            <label>Effective From</label>
                                            <input type="date" name="EffectiveFrom" id="mapping_effective_from" class="form-control">
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label>Effective To</label>
                                            <input type="date" name="EffectiveTo" id="mapping_effective_to" class="form-control">
                                        </div>
                                    </div>
                                    <div id="mapping_always_from_fields" class="form-row">
                                        <div class="col-md-6 form-group">
                                            <label>Start From (optional)</label>
                                            <input type="date" name="EffectiveFromAlways" id="mapping_effective_from_always" class="form-control">
                                            <small class="text-muted">Leave blank to start immediately.</small>
                                        </div>
                                    </div>
                                    <input type="hidden" name="CreatedBy" value="<?php echo isset($_SESSION['Name']) ? htmlspecialchars($_SESSION['Name']) : ''; ?>">
                                    <button type="submit" class="btn btn-info">Save Mapping</button>
                                </form>

                                <hr>
                                <h5>Current Active Mappings</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm">
                                        <thead><tr><th>Company</th><th>Category</th><th>Checklist</th><th>Validity</th></tr></thead>
                                        <tbody>
                                        <?php foreach ($mappings as $map) { ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($map['CompanyName']); ?></td>
                                                <td><?php echo htmlspecialchars($map['CategoryName']); ?></td>
                                                <td><?php echo htmlspecialchars($map['ChecklistCode'] . ' - ' . $map['ChecklistName']); ?></td>
                                                <td><?php echo htmlspecialchars(dynamicPPMFormatMappingValidity($map['EffectiveFrom'], $map['EffectiveTo'])); ?></td>
                                            </tr>
                                        <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div></div>
                        </div>
                    </div>
                </div>
            </main>
            <?php include('../includes/common_footer.php'); ?>
        </div>
    </div>
</div>
<?php include('../includes/common_modules.php'); include('../includes/common_scripts.php'); ?>
<script src="../js/modules/dynamic-ppm-checklist-items.js"></script>
<script>
$(document).ready(function () {
    if ($.fn.select2) {
        $('.dppm-select2').select2({ width: '100%' });
    }

    function loadNextChecklistCode() {
        var categoryID = $('#master_category_id').val();
        if (!categoryID) {
            $('#checklist_code_preview').val('');
            return;
        }
        $.post('action/get_next_checklist_code.php', { CategoryID: categoryID }, function (res) {
            if (res && !res.error && res.ChecklistCode) {
                $('#checklist_code_preview').val(res.ChecklistCode);
            } else {
                $('#checklist_code_preview').val('');
            }
        }, 'json');
    }

    $('#master_category_id').on('change', loadNextChecklistCode);
    loadNextChecklistCode();

    if (typeof dppmInitBulkItemForm === 'function') {
        dppmInitBulkItemForm('#master_bulk_items_form', <?php echo json_encode(dynamicPPMInputTypes()); ?>);
    }

    function toggleMappingValidityFields() {
        var mode = $('#mapping_validity').val();
        if (mode === 'date_range') {
            $('#mapping_date_range_fields').show();
            $('#mapping_always_note').hide();
            $('#mapping_always_from_fields').hide();
        } else {
            $('#mapping_date_range_fields').hide();
            $('#mapping_always_note').show();
            $('#mapping_always_from_fields').show();
            $('#mapping_effective_from').val('');
            $('#mapping_effective_to').val('');
        }
    }

    $('#mapping_validity').on('change', toggleMappingValidityFields);
    toggleMappingValidityFields();

    $('form[action="action/save_company_checklist_mapping.php"]').on('submit', function () {
        if ($('#mapping_validity').val() === 'always') {
            var startFrom = $('#mapping_effective_from_always').val();
            if (startFrom) {
                $('<input>').attr({ type: 'hidden', name: 'EffectiveFrom', value: startFrom }).appendTo(this);
            }
        }
    });
});
</script>
</body>
</html>
