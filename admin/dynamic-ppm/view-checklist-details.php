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

    $checklistID = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $checklist = getDynamicPPMChecklistMasterDetail($conn, $checklistID);
    if (!$checklist) {
        header('Location: view-checklists.php?status=error&msg=' . urlencode('Checklist not found.'));
        exit;
    }
    $items = getDynamicPPMChecklistItemsByChecklist($conn, $checklistID);
    $mappings = getDynamicPPMMappingsByChecklist($conn, $checklistID);
    $inputTypes = dynamicPPMInputTypes();
    ?>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($checklist['ChecklistCode']); ?> - Checklist Details</title>
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
                    <li class="breadcrumb-item"><a href="view-checklists.php">View Checklists</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($checklist['ChecklistCode']); ?></li>
                </ol>

                <div class="row">
                    <div class="col-xl-12 mb-3">
                        <?php if (isset($_GET['msg']) && trim((string) $_GET['msg']) !== '') { ?>
                            <div class="alert <?php echo (isset($_GET['status']) && $_GET['status'] === 'success') ? 'alert-success' : 'alert-danger'; ?>">
                                <?php echo htmlspecialchars($_GET['msg']); ?>
                            </div>
                        <?php } ?>
                        <a href="view-checklists.php" class="btn btn-outline-secondary btn-sm">Back to List</a>
                        <a href="view-master-checklist.php" class="btn btn-primary btn-sm">Master Setup</a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-12">
                        <div class="panel">
                            <div class="panel-hdr"><h2>Checklist Details</h2></div>
                            <div class="panel-container show">
                                <div class="panel-content">
                                    <div class="row">
                                        <div class="col-md-3"><strong>Code:</strong> <?php echo htmlspecialchars($checklist['ChecklistCode']); ?></div>
                                        <div class="col-md-5"><strong>Name:</strong> <?php echo htmlspecialchars($checklist['ChecklistName']); ?></div>
                                        <div class="col-md-4"><strong>Category:</strong> <?php echo htmlspecialchars($checklist['CategoryName']); ?></div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-md-3"><strong>Version:</strong> <?php echo (int) $checklist['VersionNo']; ?></div>
                                        <div class="col-md-3"><strong>Status:</strong> <?php echo htmlspecialchars($checklist['Status']); ?></div>
                                        <div class="col-md-3"><strong>Created By:</strong> <?php echo htmlspecialchars($checklist['CreatedBy']); ?></div>
                                        <div class="col-md-3"><strong>Created On:</strong> <?php echo htmlspecialchars($checklist['CreatedDate'] . ' ' . $checklist['CreatedTime']); ?></div>
                                    </div>
                                    <?php if (trim((string) $checklist['Description']) !== '') { ?>
                                        <div class="row mt-2">
                                            <div class="col-md-12"><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($checklist['Description'])); ?></div>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-12">
                        <div class="panel">
                            <div class="panel-hdr"><h2>Add Checklist Items</h2></div>
                            <div class="panel-container show">
                                <div class="panel-content">
                                    <?php
                                    $dppmBulkChecklistID = (int) $checklistID;
                                    $dppmBulkChecklistLabel = $checklist['ChecklistCode'] . ' - ' . $checklist['ChecklistName'];
                                    $dppmBulkChecklists = array();
                                    $dppmBulkFormId = 'details_bulk_items_form';
                                    $dppmBulkRedirect = 'view-checklist-details.php?id=' . (int) $checklistID;
                                    include('include/checklist-items-bulk-form.php');
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-12">
                        <div class="panel">
                            <div class="panel-hdr"><h2>Checklist Items (<?php echo count($items); ?>)</h2></div>
                            <div class="panel-container show">
                                <div class="panel-content">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm">
                                            <thead class="bg-primary-600 text-white">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Item Code</th>
                                                    <th>Item Name</th>
                                                    <th>Input Type</th>
                                                    <th>Unit</th>
                                                    <th>Default Value</th>
                                                    <th>Mandatory</th>
                                                    <th>Options</th>
                                                    <th>Help Text</th>
                                                    <th>Updated By</th>
                                                    <th>Updated On</th>
                                                    <th style="width:70px;">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php if (empty($items)) { ?>
                                                <tr><td colspan="12" class="text-center text-muted">No checklist items added yet.</td></tr>
                                            <?php } ?>
                                            <?php foreach ($items as $idx => $item) {
                                                $options = '';
                                                if (!empty($item['OptionsJson'])) {
                                                    $decoded = json_decode($item['OptionsJson'], true);
                                                    if (is_array($decoded)) {
                                                        $options = implode(', ', $decoded);
                                                    }
                                                }
                                                $updatedBy = trim((string) (isset($item['UpdatedBy']) ? $item['UpdatedBy'] : ''));
                                                $updatedDate = trim((string) (isset($item['UpdatedDate']) ? $item['UpdatedDate'] : ''));
                                                $updatedTime = trim((string) (isset($item['UpdatedTime']) ? $item['UpdatedTime'] : ''));
                                                if ($updatedBy === '') {
                                                    $updatedBy = trim((string) (isset($item['CreatedBy']) ? $item['CreatedBy'] : ''));
                                                    $updatedDate = trim((string) (isset($item['CreatedDate']) ? $item['CreatedDate'] : ''));
                                                    $updatedTime = trim((string) (isset($item['CreatedTime']) ? $item['CreatedTime'] : ''));
                                                }
                                                $updatedOn = ($updatedDate !== '') ? trim($updatedDate . ' ' . $updatedTime) : '-';
                                                $itemPayload = array(
                                                    'ID' => (int) $item['ID'],
                                                    'ItemCode' => $item['ItemCode'],
                                                    'ItemName' => $item['ItemName'],
                                                    'InputType' => $item['InputType'],
                                                    'UnitName' => $item['UnitName'],
                                                    'DefaultValue' => $item['DefaultValue'],
                                                    'IsMandatory' => (int) $item['IsMandatory'],
                                                    'OptionsJson' => $options,
                                                    'SortOrder' => (int) $item['SortOrder'] ?: ($idx + 1),
                                                    'HelpText' => $item['HelpText'],
                                                );
                                            ?>
                                                <tr>
                                                    <td><?php echo (int) $item['SortOrder'] ?: ($idx + 1); ?></td>
                                                    <td><?php echo htmlspecialchars($item['ItemCode']); ?></td>
                                                    <td><?php echo htmlspecialchars($item['ItemName']); ?></td>
                                                    <td><?php echo htmlspecialchars($item['InputType']); ?></td>
                                                    <td><?php echo htmlspecialchars($item['UnitName']); ?></td>
                                                    <td><?php echo htmlspecialchars($item['DefaultValue']); ?></td>
                                                    <td><?php echo (int) $item['IsMandatory'] === 1 ? 'Yes' : 'No'; ?></td>
                                                    <td><?php echo htmlspecialchars($options); ?></td>
                                                    <td><?php echo htmlspecialchars($item['HelpText']); ?></td>
                                                    <td><?php echo htmlspecialchars($updatedBy !== '' ? $updatedBy : '-'); ?></td>
                                                    <td><?php echo htmlspecialchars($updatedOn); ?></td>
                                                    <td>
                                                        <button type="button"
                                                                class="btn btn-xs btn-outline-primary dppm-edit-item-btn"
                                                                data-item="<?php echo htmlspecialchars(json_encode($itemPayload), ENT_QUOTES, 'UTF-8'); ?>"
                                                                title="Edit item">
                                                            <i class="fal fa-edit"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-12">
                        <div class="panel">
                            <div class="panel-hdr"><h2>Company Mappings (<?php echo count($mappings); ?>)</h2></div>
                            <div class="panel-container show">
                                <div class="panel-content">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm">
                                            <thead class="bg-primary-600 text-white">
                                                <tr>
                                                    <th>Company</th>
                                                    <th>Category</th>
                                                    <th>Validity</th>
                                                    <th>Mapped On</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php if (empty($mappings)) { ?>
                                                <tr><td colspan="5" class="text-center text-muted">No company mapping for this checklist.</td></tr>
                                            <?php } ?>
                                            <?php foreach ($mappings as $map) { ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($map['CompanyName']); ?></td>
                                                    <td><?php echo htmlspecialchars($map['CategoryName']); ?></td>
                                                    <td><?php echo htmlspecialchars(dynamicPPMFormatMappingValidity($map['EffectiveFrom'], $map['EffectiveTo'])); ?></td>
                                                    <td><?php echo htmlspecialchars($map['CreatedDate'] . ' ' . $map['CreatedTime']); ?></td>
                                                </tr>
                                            <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <?php include('../includes/common_footer.php'); ?>
        </div>
    </div>
</div>

<div class="modal fade" id="dppmEditItemModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="post" action="action/update_checklist_item.php" id="dppm_edit_item_form">
                <input type="hidden" name="ItemID" id="dppm_edit_item_id" value="0">
                <input type="hidden" name="ChecklistID" value="<?php echo (int) $checklistID; ?>">
                <input type="hidden" name="redirect_to" value="view-checklist-details.php?id=<?php echo (int) $checklistID; ?>">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Edit Checklist Item</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label>Item Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="ItemName" id="dppm_edit_item_name" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Item Code</label>
                            <input type="text" class="form-control" name="ItemCode" id="dppm_edit_item_code" placeholder="P-001">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Input Type</label>
                            <select class="form-control" name="InputType" id="dppm_edit_input_type">
                                <?php foreach ($inputTypes as $type) { ?>
                                    <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Unit</label>
                            <input type="text" class="form-control" name="UnitName" id="dppm_edit_unit">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Sort Order</label>
                            <input type="number" class="form-control" name="SortOrder" id="dppm_edit_sort" min="1">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Default Value</label>
                            <input type="text" class="form-control" name="DefaultValue" id="dppm_edit_default_value">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Mandatory</label>
                            <select class="form-control" name="IsMandatory" id="dppm_edit_mandatory">
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Options (for dropdown)</label>
                            <input type="text" class="form-control" name="OptionsJson" id="dppm_edit_options" placeholder="OK,Not OK,N/A">
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label>Help Text</label>
                        <input type="text" class="form-control" name="HelpText" id="dppm_edit_help_text">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
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
    if (typeof dppmInitBulkItemForm === 'function') {
        dppmInitBulkItemForm('#details_bulk_items_form', <?php echo json_encode(dynamicPPMInputTypes()); ?>);
    }
    if (typeof dppmInitEditItemModal === 'function') {
        dppmInitEditItemModal('#dppmEditItemModal', <?php echo json_encode($inputTypes); ?>);
    }
});
</script>
</body>
</html>
