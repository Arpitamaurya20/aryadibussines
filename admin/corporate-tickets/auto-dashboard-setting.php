<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php
  // Existing includes from your file (keeps them intact)
  include('../controllers/common_controllers.php');
  include('controller/corporate_tickets_controller.php');
  include('../company/controller/company_controller.php');
  include('../branch/controller/branch_controller.php');
  include('../branch-assets/controller/branch_assets_controller.php');
  require_once('../includes/autoloader.inc.php');
  $UserType = SessionCheck();
  setTimeZone();
  $conn = _connectodb();
  setNavigation($_SESSION['Roles']);
 
?>
    <meta charset="utf-8">
    <title>Corporate Tickets - Master Settings</title>
    <meta name="description" content="Master Ticket Status Settings">
    <?php include('../includes/common_head_content.php'); ?>

    <!-- Extra CSS for datatable / daterange -->
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.css">

    <style>
        .panel-hdr { padding: 12px; }
        .panel-container { padding: 16px; }
        .form-row { margin-bottom: 12px; }
        .small-muted { font-size: 12px; color: #666; }
        .select2-container { z-index: 9999; }
        .img-preview { max-width: 220px; display:block; margin-bottom:12px; }
    </style>
</head>

<?php
// --- Data for selects (using your existing objects) ---
$branch_object = new Branch($conn);
if(isset($city_lead) && $city_lead == "yes") {
    $branch_array = $branch_object->setBranchesInCityArray('All',$sql_in_string);
} else {
    if($UserType == "Corporate Admin") {
        $branch_array = $branch_object->setBranchArrayByCorporateID($CorporateID,'All');
    } else {
        $branch_array = $branch_object->setBranchArray('All');
    }
}
$state_object = new State($conn);
if(isset($city_lead) && $city_lead == "yes") {
    $state_array = $state_object->getStateArrayInCityArray($sql_in_string);
} else {
    $state_array = $state_object->setStateArray('Active');
}
$corporateticket = new Corporateticket($conn);
$status_array = $corporateticket->getCorporateTicketStatusArray($conn);
$company_object = new Company($conn);
$company_array = $company_object->setCompanyArray('All');

// Fetch existing settings
$settings = [];
$res = $conn->query("SELECT * FROM ticket_status_setting ORDER BY CreatedAt DESC");
if($res) {
    while($row = $res->fetch_assoc()) $settings[] = $row;
}
?>

<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
    <?php include('../js/theme_settings.js'); ?>
    <div class="page-wrapper">
        <div class="page-inner">
            <?php include('../navigation/admin_navigation.php'); ?>
            <div class="page-content-wrapper">
                <?php include('../includes/common_header.php'); ?>

                <main id="js-page-content" role="main" class="page-content">

                    <!-- Master Setting Panel -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="panel">
                                <div class="panel-hdr bg-primary-700 text-white">
                                    <h2>Master: Ticket Status Report Settings</h2>
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">

                                        <form id="ticket_setting_form" class="needs-validation" novalidate>
                                            <input type="hidden" name="ID" id="setting_ID" value="0">

                                            <div class="row">
                                                <div class="col-md-3 form-group">
                                                    <label>Corporate Account</label>
                                                    <select class="form-control" id="CorporateID" name="CorporateID" required>
                                                        <option value="">Select Corporate</option>
                                                        <?php foreach($company_array as $cid => $corp) {
                                                            $CompanyName = is_array($corp) && isset($corp['CompanyName']) ? $corp['CompanyName'] : $corp;
                                                        ?>
                                                            <option value="<?= intval($cid) ?>"><?= htmlspecialchars($CompanyName) ?></option>
                                                        <?php } ?>
                                                    </select>
                                                    <div class="invalid-feedback">Select Corporate</div>
                                                </div>

                                                <div class="col-md-3 form-group">
                                                    <label>State</label>
                                                    <select class="form-control" id="State" name="State">
                                                        <option value="">All States</option>
                                                        <?php foreach ($state_array as $state) {
                                                            $sname = isset($state['StateName']) ? $state['StateName'] : $state;
                                                        ?>
                                                            <option value="<?= htmlspecialchars($sname) ?>"><?= htmlspecialchars($sname) ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>

                                                <div class="col-md-3 form-group d-none">
                                                    <label>City</label>
                                                    <select class="form-control" id="City" name="City">
                                                        <option value="">All Cities</option>
                                                        <?php
                                                        // Use city data that you have available (if present)
                                                        $city_array_raw = isset($city_array_raw) ? $city_array_raw : [];
                                                        foreach($city_array_raw as $c) {
                                                            $cname = isset($c['CityName']) ? $c['CityName'] : (is_string($c) ? $c : '');
                                                            echo "<option>".htmlspecialchars($cname)."</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>

                                                <div class="col-md-3 form-group d-none">
                                                    <label>Branch</label>
                                                    <select class="form-control" id="BranchID" name="BranchID">
                                                        <option value="">All Branches</option>
                                                        <?php foreach($branch_array as $branch) {
                                                            $bID = isset($branch['ID']) ? $branch['ID'] : (isset($branch['BranchID']) ? $branch['BranchID'] : '');
                                                            $bName = isset($branch['BranchName']) ? $branch['BranchName'] : (is_string($branch) ? $branch : 'Branch');
                                                            echo "<option value=\"".htmlspecialchars($bID)."\">".htmlspecialchars($bName)."</option>";
                                                        } ?>
                                                    </select>
                                                </div>

                                                <div class="col-md-3 form-group">
                                                    <label>Status Filter</label>
                                                    <select class="form-control" id="StatusFilter" name="StatusFilter[]" multiple>
                                                        <?php foreach($status_array as $st) {
                                                            $s = isset($st['Status']) ? $st['Status'] : (is_string($st) ? $st : '');
                                                            echo "<option value=\"".htmlspecialchars($s)."\">".htmlspecialchars($s)."</option>";
                                                        } ?>
                                                    </select>
                                                    <div class="small-muted">(Hold Ctrl / Cmd to multi-select)</div>
                                                </div>

                                                <div class="col-md-3 form-group">
                                                    <label>Type (Optional)</label>
                                                    <select class="form-control" id="TypeFilter" name="TypeFilter[]" multiple>
                                                        <?php
                                                        $ticket_types = ["AMC","R&M","Projects","Supply"];
                                                        foreach($ticket_types as $t) echo "<option>".htmlspecialchars($t)."</option>";
                                                        ?>
                                                    </select>
                                                    <div class="small-muted">(Optional - multi-select)</div>
                                                </div>

                                                <div class="col-md-3 form-group">
                                                    <label>Date Range</label>
                                                    <input type="text" class="form-control" id="DateRange" name="DateRange" placeholder="YYYY-MM-DD - YYYY-MM-DD" required>
                                                    <div class="small-muted">Select date range for the report</div>
                                                </div>

                                                <div class="col-md-3 form-group d-none">
                                                    <label>Is Active?</label>
                                                    <select class="form-control" id="IsActive" name="IsActive">
                                                        <option value="1">Active</option>
                                                        <option value="0">Inactive</option>
                                                    </select>
                                                </div>

                                                <div class="col-md-6 form-group">
                                                    <label>Email To (comma separated)</label>
                                                    <input type="text" class="form-control" id="EmailTo" name="EmailTo" placeholder="to@example.com, another@example.com">
                                                </div>

                                                <div class="col-md-6 form-group">
                                                    <label>Email CC (comma separated)</label>
                                                    <input type="text" class="form-control" id="EmailCC" name="EmailCC" placeholder="cc@example.com">
                                                </div>

                                                <div class="col-md-12 text-right mt-2">
                                                    <button type="button" id="btn_reset" class="btn btn-secondary">Reset</button>
                                                    <button type="submit" class="btn btn-primary" id="btn_save">Save Setting</button>
                                                </div>
                                            </div>
                                        </form>

                                        <hr>

                                        

                                        <!-- Existing settings table -->
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered" id="settings_table">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Corporate</th>
                                                        <th>State</th>
                                                        
                                                        <th>Status</th>
                                                        <th>Type</th>
                                                        <th>Range</th>
                                                        <th>Emails</th>
                                                        <th>Active</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php $i=1;
                                                    foreach($settings as $s) {
                                                        // corporate name
                                                        $corpName = isset($company_array[$s['CorporateID']]['CompanyName']) ? $company_array[$s['CorporateID']]['CompanyName'] : $s['CorporateID'];
                                                        $branchNameDisplay = $s['BranchID'];
                                                        // try to map branch name
                                                        foreach($branch_array as $b) {
                                                            if(isset($b['ID']) && $b['ID'] == $s['BranchID']) {
                                                                $branchNameDisplay = $b['BranchName'];
                                                                break;
                                                            }
                                                        }
                                                        // ensure data-row is JSON-safe
                                                        $row_json = json_encode($s, JSON_HEX_QUOT|JSON_HEX_APOS);
                                                        ?>
                                                        <tr id="row_<?=intval($s['ID'])?>">
                                                            <td><?= $i++ ?></td>
                                                            <td><?= htmlspecialchars($corpName) ?></td>
                                                            <td><?= htmlspecialchars($s['State']) ?></td>
                                                           
                                                            <td><?= htmlspecialchars($s['StatusFilter']) ?></td>
                                                            <td><?= htmlspecialchars($s['TypeFilter']) ?></td>
                                                            <td><?= htmlspecialchars($s['DateFrom'].' - '.$s['DateTo']) ?></td>
                                                            <td><?= htmlspecialchars($s['EmailTo'].' '.($s['EmailCC']? ' / CC: '.$s['EmailCC'] : '')) ?></td>
                                                            <td><?= $s['IsActive'] ? 'Yes' : 'No' ?></td>
                                                            <td>
                                                                <button class="btn btn-sm btn-info btn-edit" data-row='<?= htmlspecialchars($row_json) ?>'>Edit</button>
                                                                <button class="mt-4 btn btn-sm btn-danger btn-delete" data-id="<?= intval($s['ID']) ?>">Delete</button>
                                                            </td>
                                                        </tr>
                                                    <?php } ?>
                                                    <?php if(count($settings) == 0) { ?>
                                                        <tr><td colspan="11" class="text-center">No settings found</td></tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- End Main -->
                </main>

                <div class="page-content-overlay" data-action="toggle" data-class="mobile-nav-on"></div>

                <?php include('../includes/common_footer.php') ?>
            </div>
        </div>
    </div>

    <?php
        include('../includes/common_modules.php');
        include('../includes/common_scripts.php');
    ?>

    <!-- Dependencies -->
    <script src="../js/dependency/moment/moment.js"></script>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.js"></script>
    <script src="../js/dependency/select2/select2.full.min.js"></script>

    <script>
    (function($){
        // init selects (multi-select where applicable)
        $('#CorporateID, #State, #City, #BranchID').select2({ width: '100%' });
        $('#StatusFilter, #TypeFilter').select2({ width: '100%', placeholder: 'Select options', allowClear: true });

        // date range
        $('#DateRange').daterangepicker({
            locale: { format: 'YYYY-MM-DD' },
            startDate: moment().subtract(30, 'days').format('YYYY-MM-DD'),
            endDate: moment().format('YYYY-MM-DD')
        });

        // reset form
        function resetForm() {
            $('#setting_ID').val(0);
            $('#ticket_setting_form')[0].reset();
            $('#CorporateID, #State, #City, #BranchID, #StatusFilter, #TypeFilter').val('').trigger('change');
            // reset daterange to last 30 days
            $('#DateRange').data('daterangepicker').setStartDate(moment().subtract(30, 'days'));
            $('#DateRange').data('daterangepicker').setEndDate(moment());
            $('#IsActive').val('1');
            $('#btn_save').text('Save Setting').removeClass('btn-warning').addClass('btn-primary');
        }

        $('#btn_reset').on('click', function(){ resetForm(); });

        // form submit (save & update)
        $('#ticket_setting_form').on('submit', function(e){
            e.preventDefault();

            // basic validation
            if($('#CorporateID').val() === '') {
                alert('Select Corporate');
                $('#CorporateID').focus();
                return;
            }
            if($('#DateRange').val() === '') {
                alert('Select Date Range');
                return;
            }

            var formData = $(this).serializeArray();
            formData.push({name:'ajax_action', value:'save'});

            $('#btn_save').prop('disabled', true).text('Saving...');

            $.post('./action/add_update_auto_dashboard_setting.php', $.param(formData), function(resp){
                try {
                    if(resp.success) {
                        // reload the page to reflect changes (or you could dynamically update table)
                        location.reload();
                    } else {
                        alert('Error saving: ' + (resp.error || 'Unknown error'));
                    }
                } catch(err) {
                    alert('Unexpected response from server.');
                }
            }, 'json').fail(function(xhr){
                alert('Request failed: ' + xhr.responseText);
            }).always(function(){ $('#btn_save').prop('disabled', false).text('Save Setting'); });
        });

        // Edit button populate
        $('.btn-edit').on('click', function(){
            var dataStr = $(this).attr('data-row');
            var data = {};
            try {
                data = JSON.parse(dataStr);
            } catch(e) {
                alert('Failed to parse data for edit');
                return;
            }

            $('#setting_ID').val(data.ID);
            $('#CorporateID').val(data.CorporateID).trigger('change');
            $('#State').val(data.State).trigger('change');
            $('#City').val(data.City).trigger('change');
            $('#BranchID').val(data.BranchID).trigger('change');

            // multi-select values (stored as comma separated strings)
            if(data.StatusFilter) {
                $('#StatusFilter').val(data.StatusFilter.split(',')).trigger('change');
            } else {
                $('#StatusFilter').val([]).trigger('change');
            }
            if(data.TypeFilter) {
                $('#TypeFilter').val(data.TypeFilter.split(',')).trigger('change');
            } else {
                $('#TypeFilter').val([]).trigger('change');
            }

            $('#EmailTo').val(data.EmailTo);
            $('#EmailCC').val(data.EmailCC);
            $('#IsActive').val(data.IsActive ? '1' : '0');

            // set daterange
            if(data.DateFrom && data.DateTo) {
                try {
                    $('#DateRange').data('daterangepicker').setStartDate(data.DateFrom);
                    $('#DateRange').data('daterangepicker').setEndDate(data.DateTo);
                } catch(e) { /* ignore */ }
            }

            $('#btn_save').text('Update Setting').removeClass('btn-primary').addClass('btn-warning');
            // scroll to top of form
            $('html, body').animate({ scrollTop: $('#ticket_setting_form').offset().top - 100 }, 400);
        });

        // Delete
        $('.btn-delete').on('click', function(){
            var id = $(this).data('id');
            if(!confirm('Delete this setting?')) return;

            $.post('./action/add_update_auto_dashboard_setting.php', { ajax_action: 'delete', ID: id }, function(resp){
                if(resp.success) {
                    $('#row_' + id).fadeOut(300, function(){ $(this).remove(); });
                } else {
                    alert('Delete failed: ' + (resp.error || 'Unknown'));
                }
            }, 'json').fail(function(xhr){ alert('Request failed: ' + xhr.responseText); });
        });

        // highlight nav
        $(document).ready(function(){
            $("#nav_corporate_tickets").addClass("active");
        });
    })(jQuery);
    </script>

</body>
</html>