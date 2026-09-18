<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    include('../controllers/common_controllers.php');

    include('../corporate-tickets-status/controller/corporate_tickets_status_controller.php');
    include('../employees/controller/employee_controller.php');
    require_once('../includes/autoloader.inc.php');
    $conn = _connectodb();
    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);

    //$total_projects = getTotatProjects($conn,$Enterpriseid);
    ?>
    <meta charset="utf-8">
    
    <meta name="description" content="View Schema">
    <?php
    include('../includes/common_head_content.php');
    ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <link rel="stylesheet" media="screen, print"
        href="../css/formplugins/bootstrap-datepicker/bootstrap-datepicker.css">
       
    <title>
        View Auto PPM Assets - <?=$ProductName;?>
    </title>
        <?php 
        if(isset($product_configuration['favicon']))
        {
            ?>
            <link rel="icon" type="image/png" sizes="32x32" href="../img/favicon/<?=$product_configuration['favicon'];?>">
            <?php
        }
        if($ProductName != "Aryadibusiness")
        {
            include("../css/client_generated_css.php");
        }
        ?>
</head>

<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
    <?php include('../js/theme_settings.js'); ?>
    <!-- BEGIN Page Wrapper -->
    <div class="page-wrapper">
        <div class="page-inner">
            <?php
            include('../navigation/admin_navigation.php');
            ?>
            <div class="page-content-wrapper">
                <!-- BEGIN Page Header -->
                <?php
                include('../includes/common_header.php');
                ?>
                <!-- END Page Header -->
                <!-- BEGIN Page Content -->
                <!-- the #js-page-content id is needed for some plugins to initialize -->
                <main id="js-page-content" role="main" class="page-content">
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard/analytics_dashboard"><?=$ProductName;?></a></li>
                        <li class="breadcrumb-item active">Auto PPM Assets</li>
                    </ol>

                    <!-- Page Container -->
                    <div class="row" style="z-index: 100;">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>Auto PPM Assets Management</h2>
                                    <div class="panel-toolbar">
                                        <button type="button" class="btn btn-primary btn-sm" onclick="openAddAutoPPMAssetModal()">
                                            <i class="fa fa-plus"></i> Add Asset for Auto PPM
                                        </button>
                                    </div>
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <div class="row mb-3">
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label>Corporate</label>
                                                    <select class="form-control" id="filter_corporate" onchange="loadAutoPPMAssets();">
                                                        <option value="">All Corporates</option>
                                                        <?php
                                                        $where = " where IsActive = 1";
                                                        $corporates = _getTableRecords($conn, 'company', $where);
                                                        if (is_array($corporates)) {
                                                            foreach ($corporates as $corporate) {
                                                                echo '<option value="' . $corporate['ID'] . '">' . $corporate['CompanyName'] . '</option>';
                                                            }
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label>Branch</label>
                                                    <select class="form-control" id="filter_branch" onchange="loadAutoPPMAssets();">
                                                        <option value="">All Branches</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label>Equipment Name</label>
                                                    <select class="form-control" id="filter_equipment">
                                                        <option value="">All Equipment</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label>Make</label>
                                                    <select class="form-control" id="filter_make">
                                                        <option value="">All Makes</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label>Model</label>
                                                    <select class="form-control" id="filter_model">
                                                        <option value="">All Models</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label>Interval</label>
                                                    <select class="form-control" id="filter_interval">
                                                        <option value="">All Intervals</option>
                                                        <option value="monthly">Monthly</option>
                                                        <option value="quarterly">Quarterly</option>
                                                        <option value="halfyearly">Half Yearly</option>
                                                        <option value="yearly">Yearly</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <table id="auto_ppm_assets_table" class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Corporate</th>
                                                    <th>Branch</th>
                                                    <th>Equipment Name</th>
                                                    <th>Make</th>
                                                    <th>Model</th>
                                                    <th>AMC Start Date</th>
                                                    <th>AMC End Date</th>
                                                    <th>Interval</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="auto_ppm_assets_tbody">
                                                <!-- Data will be loaded via AJAX -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Add/Edit Modal -->
                    <div class="modal fade" id="addAutoPPMAssetModal" tabindex="-1" role="dialog" aria-labelledby="addAutoPPMAssetModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                    <h4 class="modal-title" id="addAutoPPMAssetModalLabel">Add Asset for Auto PPM</h4>
                                </div>
                                <div class="modal-body">
                                    <!-- Tabs for Manual Entry and CSV Upload -->
                                    <ul class="nav nav-tabs" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active" id="manual-tab" data-toggle="tab" href="#manual-entry" role="tab" aria-controls="manual-entry" aria-selected="true">Manual Entry</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" id="csv-tab" data-toggle="tab" href="#csv-upload" role="tab" aria-controls="csv-upload" aria-selected="false">CSV Upload</a>
                                        </li>
                                    </ul>
                                    
                                    <div class="tab-content" style="margin-top: 20px;">
                                        <!-- Manual Entry Tab -->
                                        <div class="tab-pane fade show active" id="manual-entry" role="tabpanel" aria-labelledby="manual-tab">
                                            <form id="auto_ppm_asset_form">
                                                <input type="hidden" id="form_action" name="form_action" value="add">
                                                <input type="hidden" id="temp_asset_id" name="ID">
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>Corporate <span class="text-danger">*</span></label>
                                                            <select class="form-control" id="corporate_id" name="CorporateID" required onchange="loadBranchesForAutoPPM()">
                                                                <option value="">Select Corporate</option>
                                                                <?php
                                                                $where = " where IsActive = 1";
                                                                $corporates = _getTableRecords($conn, 'company', $where);
                                                                if (is_array($corporates)) {
                                                                    foreach ($corporates as $corporate) {
                                                                        echo '<option value="' . $corporate['ID'] . '">' . $corporate['CompanyName'] . '</option>';
                                                                    }
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>Branch <span class="text-danger">*</span></label>
                                                            <select class="form-control" id="branch_id" name="BranchID" required onchange="loadAssetsForAutoPPM()">
                                                                <option value="">Select Branch</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>
                                                                <input type="checkbox" id="bulk_mode" onchange="toggleBulkMode()" style="margin-right: 5px;">
                                                                <strong>Bulk Selection Mode</strong>
                                                            </label>
                                                            <select class="form-control" id="branch_asset_id" name="BranchAssetID" required>
                                                                <option value="">Select Asset</option>
                                                            </select>
                                                            <small class="text-muted" id="bulk_mode_hint" style="display: none;">You can select multiple assets at once</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>Interval <span class="text-danger">*</span></label>
                                                            <select class="form-control" id="interval" name="Interval" required>
                                                                <option value="">Select Interval</option>
                                                                <option value="monthly">Monthly</option>
                                                                <option value="quarterly">Quarterly</option>
                                                                <option value="halfyearly">Half Yearly</option>
                                                                <option value="yearly">Yearly</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>AMC Start Date <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control datepicker" id="amc_start_date" name="AMCStartDate" required autocomplete="off">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>AMC End Date <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control datepicker" id="amc_end_date" name="AMCEndDate" required autocomplete="off">
                                                        </div>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                        
                                        <!-- CSV Upload Tab -->
                                        <div class="tab-pane fade" id="csv-upload" role="tabpanel" aria-labelledby="csv-tab">
                                            <div class="alert alert-info">
                                                <strong>Instructions:</strong>
                                                <ul style="margin-bottom: 0;">
                                                    <li>Download the example CSV file to see the required format</li>
                                                    <li>Fill in the CSV with your asset data</li>
                                                    <li>Upload the CSV file to bulk import assets</li>
                                                    <li><strong>Required columns:</strong> Corporate (or CorporateID), Branch (or BranchID), Equipment Name (or BranchAssetID), Make, AMC Start Date, AMC End Date, Interval</li>
                                                    <li><strong>Optional columns:</strong> Model (can be left empty)</li>
                                                    <li><strong>Date formats supported:</strong> YYYY-MM-DD, MM/DD/YYYY, DD/MM/YYYY</li>
                                                    <li><strong>You can use either Names or IDs:</strong> Use Corporate/Branch/Equipment Name for names, or CorporateID/BranchID/BranchAssetID for IDs</li>
                                                </ul>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Download Example CSV File</label>
                                                <div>
                                                    <button type="button" class="btn btn-info btn-sm" onclick="downloadExampleCSV()">
                                                        <i class="fa fa-download"></i> Download Example CSV
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Upload CSV File <span class="text-danger">*</span></label>
                                                <input type="file" class="form-control" id="csv_file" accept=".csv" onchange="validateCSVFile(this)">
                                                <small class="text-muted">Only CSV files are allowed. Maximum file size: 5MB</small>
                                            </div>
                                            
                                            <div id="csv_upload_progress" style="display: none;">
                                                <div class="progress">
                                                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%">Uploading and processing...</div>
                                                </div>
                                            </div>
                                            
                                            <div id="csv_upload_result" style="display: none; margin-top: 15px;"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-primary" id="save_manual_btn" onclick="saveAutoPPMAsset()">Save</button>
                                    <button type="button" class="btn btn-success" id="upload_csv_btn" onclick="uploadCSVFile()" style="display: none;">
                                        <i class="fa fa-upload"></i> Upload CSV
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- View PPM Dates Modal -->
                    <div class="modal fade" id="viewPPMDatesModal" tabindex="-1" role="dialog" aria-labelledby="viewPPMDatesModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                    <h4 class="modal-title" id="viewPPMDatesModalLabel">PPM Dates Schedule</h4>
                                </div>
                                <div class="modal-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>PPM Date</th>
                                                    <th>Interval Identifier</th>
                                                    <th>Ticket Raised</th>
                                                </tr>
                                            </thead>
                                            <tbody id="ppm_dates_tbody">
                                                <!-- Data will be loaded via AJAX -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>

                </main>

                <!-- this overlay is activated only when mobile menu is triggered -->
                <div class="page-content-overlay" data-action="toggle" data-class="mobile-nav-on"></div>
                <!-- END Page Content -->
                <!-- BEGIN Page Footer -->
                <?php
                include('../includes/common_footer.php')
                ?>
                <!-- END Page Footer -->

            </div>
        </div>
    </div>
    <!-- END Page Wrapper -->

    <?php
    include('../includes/common_modules.php');
    include('../includes/common_scripts.php');
    ?>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/modules/ppm-ticket.js"></script>
    <script src="../js/formplugins/bootstrap-datepicker/bootstrap-datepicker.js"></script>

    <style>
        /* Ensure modals don't overlap */
        .modal {
            z-index: 1050;
        }
        .modal-backdrop {
            z-index: 1040;
        }
        /* Ensure Select2 dropdowns appear above modals */
        .select2-container {
            z-index: 9999;
        }
        .select2-dropdown {
            z-index: 9999;
        }
    </style>

    <script type="text/javascript">
        $(document).ready(function() {
            $("#nav_ppm_tickets").addClass("active");
            
            // Initialize Select2 for filter dropdowns
            $('#filter_corporate').select2({
                placeholder: "All Corporates",
                allowClear: true
            });
            
            $('#filter_branch').select2({
                placeholder: "All Branches",
                allowClear: true
            });
            
            $('#filter_equipment').select2({
                placeholder: "All Equipment",
                allowClear: true
            });
            
            $('#filter_make').select2({
                placeholder: "All Makes",
                allowClear: true
            });
            
            $('#filter_model').select2({
                placeholder: "All Models",
                allowClear: true
            });
            
            $('#filter_interval').select2({
                placeholder: "All Intervals",
                allowClear: true
            });
            
            // Initialize Select2 for modal dropdowns
            $('#corporate_id').select2({
                placeholder: "Select Corporate",
                allowClear: true,
                dropdownParent: $('#addAutoPPMAssetModal')
            });
            
            $('#branch_id').select2({
                placeholder: "Select Branch",
                allowClear: true,
                dropdownParent: $('#addAutoPPMAssetModal')
            });
            
            $('#branch_asset_id').select2({
                placeholder: "Select Asset",
                allowClear: true,
                multiple: false,
                dropdownParent: $('#addAutoPPMAssetModal')
            });
            
            $('#interval').select2({
                placeholder: "Select Interval",
                allowClear: true,
                dropdownParent: $('#addAutoPPMAssetModal')
            });
            
            // Initialize Datepicker
            $('.datepicker').datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true,
                todayHighlight: true,
                orientation: 'bottom auto'
            });
            
            // Handle filter dropdown changes with Select2
            $('#filter_equipment').on('select2:select select2:clear', function() {
                updateColumnFilter('filter_equipment', 3);
            });
            
            $('#filter_make').on('select2:select select2:clear', function() {
                updateColumnFilter('filter_make', 4);
            });
            
            $('#filter_model').on('select2:select select2:clear', function() {
                updateColumnFilter('filter_model', 5);
            });
            
            $('#filter_interval').on('select2:select select2:clear', function() {
                updateColumnFilter('filter_interval', 8);
            });
        });
    </script>

<script>
var table;
var allData = [];

function loadAutoPPMAssets() {
    var CorporateID = $('#filter_corporate').val();
    var BranchID = $('#filter_branch').val();
    
    $.ajax({
        url: '../auto-ppm/action/get_temp_assets.php',
        type: 'GET',
        data: {
            CorporateID: CorporateID,
            BranchID: BranchID
        },
        dataType: 'json',
        success: function(response) {
            if (response.error == false) {
                allData = response.data || [];
                
                // Destroy existing DataTable if it exists
                if ($.fn.DataTable.isDataTable('#auto_ppm_assets_table')) {
                    table.destroy();
                }
                
                // Clear and populate table
                var tbody = $('#auto_ppm_assets_tbody');
                tbody.empty();
                
                if (allData.length > 0) {
                    $.each(allData, function(index, item) {
                        var row = '<tr>' +
                            '<td>' + item.ID + '</td>' +
                            '<td>' + (item.CompanyName || '') + '</td>' +
                            '<td>' + (item.BranchSite || '') + '</td>' +
                            '<td>' + (item.EquipmentName || '') + '</td>' +
                            '<td>' + (item.Make || '') + '</td>' +
                            '<td>' + (item.Model || '') + '</td>' +
                            '<td>' + item.AMCStartDate + '</td>' +
                            '<td>' + item.AMCEndDate + '</td>' +
                            '<td>' + item.Interval + '</td>' +
                            '<td>' +
                            '<button class="btn btn-info btn-sm" onclick="viewPPMDates(' + item.ID + ')" title="View PPM Dates"><i class="fa fa-calendar"></i></button> ' +
                            '<button class="btn btn-danger btn-sm" onclick="deleteAutoPPMAsset(' + item.ID + ')" title="Delete"><i class="fa fa-trash"></i></button>' +
                            '</td>' +
                            '</tr>';
                        tbody.append(row);
                    });
                    
                    // Initialize DataTable
                    initializeDataTable();
                    
                    // Populate filter dropdowns
                    populateFilterDropdowns();
                } else {
                    tbody.append('<tr><td colspan="10" class="text-center">No records found</td></tr>');
                    // Initialize empty DataTable
                    initializeDataTable();
                }
            }
        }
    });
}

function initializeDataTable() {
    table = $('#auto_ppm_assets_table').DataTable({
        "pageLength": 25,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        "order": [[0, "asc"]],
        "columnDefs": [
            { "orderable": false, "targets": 9 } // Disable sorting on Action column
        ]
    });
}

function populateFilterDropdowns() {
    // Get unique values for each column
    var equipmentNames = [];
    var makes = [];
    var models = [];
    
    $.each(allData, function(index, item) {
        if (item.EquipmentName && equipmentNames.indexOf(item.EquipmentName) === -1) {
            equipmentNames.push(item.EquipmentName);
        }
        if (item.Make && makes.indexOf(item.Make) === -1) {
            makes.push(item.Make);
        }
        if (item.Model && models.indexOf(item.Model) === -1) {
            models.push(item.Model);
        }
    });
    
    // Sort arrays
    equipmentNames.sort();
    makes.sort();
    models.sort();
    
    // Populate Equipment Name dropdown
    var equipmentSelect = $('#filter_equipment');
    var currentEquipmentValue = equipmentSelect.val();
    equipmentSelect.find('option:not(:first)').remove();
    $.each(equipmentNames, function(index, name) {
        equipmentSelect.append('<option value="' + name + '">' + name + '</option>');
    });
    equipmentSelect.val(currentEquipmentValue).trigger('change.select2');
    
    // Populate Make dropdown
    var makeSelect = $('#filter_make');
    var currentMakeValue = makeSelect.val();
    makeSelect.find('option:not(:first)').remove();
    $.each(makes, function(index, make) {
        makeSelect.append('<option value="' + make + '">' + make + '</option>');
    });
    makeSelect.val(currentMakeValue).trigger('change.select2');
    
    // Populate Model dropdown
    var modelSelect = $('#filter_model');
    var currentModelValue = modelSelect.val();
    modelSelect.find('option:not(:first)').remove();
    $.each(models, function(index, model) {
        modelSelect.append('<option value="' + model + '">' + model + '</option>');
    });
    modelSelect.val(currentModelValue).trigger('change.select2');
}

function updateColumnFilter(filterId, columnIndex) {
    if (!$.fn.DataTable.isDataTable('#auto_ppm_assets_table')) {
        return;
    }
    
    var filterValue = $('#' + filterId).val();
    
    // For exact match, use regex with start and end anchors
    if (filterValue) {
        table.column(columnIndex).search('^' + filterValue + '$', true, false).draw();
    } else {
        table.column(columnIndex).search('').draw();
    }
}

function openAddAutoPPMAssetModal() {
    // Close any other open modals first
    $('.modal').modal('hide');
    
    // Reset form
    $('#auto_ppm_asset_form')[0].reset();
    $('#form_action').val('add');
    $('#temp_asset_id').val('');
    
    // Update modal title
    $('#addAutoPPMAssetModalLabel').text('Add Asset for Auto PPM');
    
    // Reset to manual entry tab
    $('#manual-tab').tab('show');
    $('#save_manual_btn').show();
    $('#upload_csv_btn').hide();
    
    // Reset bulk mode
    $('#bulk_mode').prop('checked', false);
    $('#bulk_mode_hint').hide();
    
    // Reset CSV upload section
    $('#csv_file').val('');
    $('#csv_upload_result').hide().html('');
    $('#csv_upload_progress').hide();
    
    // Reset Select2 dropdowns
    $('#corporate_id').val(null).trigger('change');
    $('#branch_id').html('<option value="">Select Branch</option>').val(null).trigger('change');
    $('#branch_asset_id').html('<option value="">Select Asset</option>').val(null).trigger('change');
    $('#interval').val(null).trigger('change');
    
    // Clear datepicker fields
    $('#amc_start_date').val('');
    $('#amc_end_date').val('');
    
    // Re-initialize Select2 for branch dropdowns after clearing (single mode by default)
    setTimeout(function() {
        $('#branch_id').select2({
            placeholder: "Select Branch",
            allowClear: true,
            dropdownParent: $('#addAutoPPMAssetModal')
        });
        $('#branch_asset_id').select2({
            placeholder: "Select Asset",
            allowClear: true,
            multiple: false,
            dropdownParent: $('#addAutoPPMAssetModal')
        });
    }, 100);
    
    // Show only the Add Asset modal
    $('#addAutoPPMAssetModal').modal('show');
}

function toggleBulkMode() {
    var isBulkMode = $('#bulk_mode').is(':checked');
    var assetSelect = $('#branch_asset_id');
    var currentValue = assetSelect.val();
    
    // Destroy existing Select2 instance
    if ($.fn.DataTable && assetSelect.hasClass('select2-hidden-accessible')) {
        assetSelect.select2('destroy');
    }
    
    // Clear current selection
    assetSelect.html('<option value="">Select Asset</option>');
    
    // Show/hide hint
    if (isBulkMode) {
        $('#bulk_mode_hint').show();
    } else {
        $('#bulk_mode_hint').hide();
    }
    
    // Re-initialize Select2 with appropriate settings
    setTimeout(function() {
        if (isBulkMode) {
            // Multiple selection mode
            assetSelect.select2({
                placeholder: "Select Multiple Assets",
                allowClear: true,
                multiple: true,
                dropdownParent: $('#addAutoPPMAssetModal')
            });
        } else {
            // Single selection mode
            assetSelect.select2({
                placeholder: "Select Asset",
                allowClear: true,
                multiple: false,
                dropdownParent: $('#addAutoPPMAssetModal')
            });
        }
        
        // Reload assets if branch is already selected
        if ($('#branch_id').val()) {
            loadAssetsForAutoPPM();
        }
    }, 100);
}

function loadBranchesForAutoPPM() {
    var CorporateID = $('#corporate_id').val();
    $('#branch_id').html('<option value="">Select Branch</option>');
    $('#branch_asset_id').html('<option value="">Select Asset</option>');
    
    // Re-initialize Select2 for branch_asset_id
    $('#branch_asset_id').select2({
        placeholder: "Select Asset",
        allowClear: true,
        dropdownParent: $('#addAutoPPMAssetModal')
    });
    
    if (CorporateID) {
        $.ajax({
            url: '../auto-ppm/ajax/get_branches_list.php',
            type: 'GET',
            data: { CompanyID: CorporateID },
            dataType: 'json',
            success: function(response) {
                if (response.error == false && response.data) {
                    $.each(response.data, function(index, branch) {
                        $('#branch_id').append('<option value="' + branch.ID + '">' + branch.BranchSite + '</option>');
                    });
                    // Re-initialize Select2 for branch_id
                    $('#branch_id').select2({
                        placeholder: "Select Branch",
                        allowClear: true,
                        dropdownParent: $('#addAutoPPMAssetModal')
                    });
                }
            }
        });
    } else {
        // Re-initialize Select2 for branch_id even if no corporate selected
        $('#branch_id').select2({
            placeholder: "Select Branch",
            allowClear: true,
            dropdownParent: $('#addAutoPPMAssetModal')
        });
    }
}

function loadAssetsForAutoPPM() {
    var BranchID = $('#branch_id').val();
    var isBulkMode = $('#bulk_mode').is(':checked');
    var currentValues = $('#branch_asset_id').val(); // Preserve current selection if any
    
    $('#branch_asset_id').html('<option value="">Select Asset</option>');
    
    if (BranchID) {
        $.ajax({
            url: '../auto-ppm/ajax/get_branch_assets_list.php',
            type: 'GET',
            data: { BranchID: BranchID },
            dataType: 'json',
            success: function(response) {
                if (response.error == false && response.data) {
                    $.each(response.data, function(index, asset) {
                        $('#branch_asset_id').append('<option value="' + asset.ID + '">' + asset.EquipmentName + ' - ' + asset.Make + ' ' + asset.Model + '</option>');
                    });
                    
                    // Re-initialize Select2 for branch_asset_id based on bulk mode
                    var placeholder = isBulkMode ? "Select Multiple Assets" : "Select Asset";
                    var select2Options = {
                        placeholder: placeholder,
                        allowClear: true,
                        multiple: isBulkMode,
                        dropdownParent: $('#addAutoPPMAssetModal')
                    };
                    
                    $('#branch_asset_id').select2(select2Options);
                    
                    // Restore previous selection if any
                    if (currentValues) {
                        $('#branch_asset_id').val(currentValues).trigger('change');
                    }
                }
            }
        });
    } else {
        // Re-initialize Select2 even if no branch selected
        var placeholder = isBulkMode ? "Select Multiple Assets" : "Select Asset";
        $('#branch_asset_id').select2({
            placeholder: placeholder,
            allowClear: true,
            multiple: isBulkMode,
            dropdownParent: $('#addAutoPPMAssetModal')
        });
    }
}

function saveAutoPPMAsset() {
    var isBulkMode = $('#bulk_mode').is(':checked');
    var formData = $('#auto_ppm_asset_form').serializeArray();
    var assetIds = $('#branch_asset_id').val();
    
    // Validate required fields
    if (!$('#corporate_id').val()) {
        alert('Please select Corporate');
        return;
    }
    if (!$('#branch_id').val()) {
        alert('Please select Branch');
        return;
    }
    if (!assetIds || (Array.isArray(assetIds) && assetIds.length === 0) || (!Array.isArray(assetIds) && assetIds === '')) {
        alert('Please select at least one Asset');
        return;
    }
    if (!$('#interval').val()) {
        alert('Please select Interval');
        return;
    }
    if (!$('#amc_start_date').val()) {
        alert('Please select AMC Start Date');
        return;
    }
    if (!$('#amc_end_date').val()) {
        alert('Please select AMC End Date');
        return;
    }
    
    // Prepare form data
    var dataToSend = {};
    $.each(formData, function(i, field) {
        if (field.name !== 'BranchAssetID') {
            dataToSend[field.name] = field.value;
        }
    });
    
    // Handle asset IDs - convert to array if single selection
    if (isBulkMode) {
        dataToSend['BranchAssetIDs'] = Array.isArray(assetIds) ? assetIds : [assetIds];
        dataToSend['is_bulk'] = true;
    } else {
        dataToSend['BranchAssetID'] = Array.isArray(assetIds) ? assetIds[0] : assetIds;
        dataToSend['is_bulk'] = false;
    }
    
    $.ajax({
        url: '../auto-ppm/action/add_update_temp_assets.php',
        type: 'POST',
        data: dataToSend,
        dataType: 'json',
        success: function(response) {
            if (response.error == false) {
                alert(response.message);
                $('#addAutoPPMAssetModal').modal('hide');
                loadAutoPPMAssets();
            } else {
                alert(response.message);
            }
        },
        error: function() {
            alert('Error occurred while saving');
        }
    });
}

function viewPPMDates(TempAssetInfoID) {
    // Close any other open modals first
    $('.modal').modal('hide');
    
    $.ajax({
        url: '../auto-ppm/action/get_temp_ppm_dates.php',
        type: 'GET',
        data: { TempAssetInfoID: TempAssetInfoID },
        dataType: 'json',
        success: function(response) {
            if (response.error == false) {
                var tbody = $('#ppm_dates_tbody');
                tbody.empty();
                
                if (response.data && response.data.length > 0) {
                    $.each(response.data, function(index, item) {
                        var ticketRaised = item.IsTicketRaised == 1 ? '<span class="label label-success">Yes</span>' : '<span class="label label-warning">No</span>';
                        var row = '<tr>' +
                            '<td>' + item.PPMDate + '</td>' +
                            '<td>' + item.IntervalIdentifier + '</td>' +
                            '<td>' + ticketRaised + '</td>' +
                            '</tr>';
                        tbody.append(row);
                    });
                } else {
                    tbody.append('<tr><td colspan="3" class="text-center">No PPM dates found</td></tr>');
                }
                
                // Show only the View PPM Dates modal
                $('#viewPPMDatesModal').modal('show');
            } else {
                alert('Error loading PPM dates: ' + (response.message || 'Unknown error'));
            }
        },
        error: function() {
            alert('Error occurred while loading PPM dates');
        }
    });
}

function deleteAutoPPMAsset(ID) {
    if (confirm('Are you sure you want to delete this auto PPM asset configuration?')) {
        $.ajax({
            url: '../auto-ppm/action/delete_temp_assets.php',
            type: 'POST',
            data: { ID: ID },
            dataType: 'json',
            success: function(response) {
                if (response.error == false) {
                    alert(response.message);
                    loadAutoPPMAssets();
                } else {
                    alert(response.message);
                }
            }
        });
    }
}

// CSV Upload Functions
function downloadExampleCSV() {
    window.location.href = '../auto-ppm/action/download_example_csv.php';
}

function validateCSVFile(input) {
    var file = input.files[0];
    if (file) {
        var fileName = file.name.toLowerCase();
        var fileSize = file.size;
        var maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!fileName.endsWith('.csv')) {
            alert('Please select a CSV file');
            input.value = '';
            return false;
        }
        
        if (fileSize > maxSize) {
            alert('File size exceeds 5MB. Please select a smaller file.');
            input.value = '';
            return false;
        }
        
        return true;
    }
    return false;
}

function uploadCSVFile() {
    var fileInput = document.getElementById('csv_file');
    var file = fileInput.files[0];
    
    if (!file) {
        alert('Please select a CSV file to upload');
        return;
    }
    
    if (!validateCSVFile(fileInput)) {
        return;
    }
    
    var formData = new FormData();
    formData.append('csvFile', file);
    
    // Show progress
    $('#csv_upload_progress').show();
    $('#csv_upload_result').hide();
    $('#upload_csv_btn').prop('disabled', true);
    
    $.ajax({
        url: '../auto-ppm/action/upload_csv.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            $('#csv_upload_progress').hide();
            $('#upload_csv_btn').prop('disabled', false);
            
            var resultHtml = '';
            if (response.error == false) {
                resultHtml = '<div class="alert alert-success">' +
                    '<strong>Success!</strong><br>' +
                    response.message;
                
                if (response.insert_count !== undefined) {
                    resultHtml += '<br>Inserted: ' + response.insert_count;
                }
                if (response.update_count !== undefined) {
                    resultHtml += '<br>Updated: ' + response.update_count;
                }
                if (response.skipped_count !== undefined && response.skipped_count > 0) {
                    resultHtml += '<br>Skipped: ' + response.skipped_count;
                }
                if (response.errors && response.errors.length > 0) {
                    resultHtml += '<br><strong>Errors:</strong><ul>';
                    $.each(response.errors, function(index, error) {
                        resultHtml += '<li>' + error + '</li>';
                    });
                    resultHtml += '</ul>';
                }
                resultHtml += '</div>';
                
                // Reload the table
                loadAutoPPMAssets();
                
                // Clear file input
                fileInput.value = '';
            } else {
                resultHtml = '<div class="alert alert-danger">' +
                    '<strong>Error!</strong><br>' +
                    (response.message || 'An error occurred during upload');
                
                if (response.errors && response.errors.length > 0) {
                    resultHtml += '<br><strong>Errors:</strong><ul>';
                    $.each(response.errors, function(index, error) {
                        resultHtml += '<li>' + error + '</li>';
                    });
                    resultHtml += '</ul>';
                }
                resultHtml += '</div>';
            }
            
            $('#csv_upload_result').html(resultHtml).show();
        },
        error: function(xhr, status, error) {
            $('#csv_upload_progress').hide();
            $('#upload_csv_btn').prop('disabled', false);
            $('#csv_upload_result').html(
                '<div class="alert alert-danger">' +
                '<strong>Error!</strong><br>An error occurred while uploading the file. Please try again.' +
                '</div>'
            ).show();
        }
    });
}

// Handle tab switching
$('#manual-tab, #csv-tab').on('click', function() {
    if ($(this).attr('id') === 'csv-tab') {
        $('#save_manual_btn').hide();
        $('#upload_csv_btn').show();
    } else {
        $('#save_manual_btn').show();
        $('#upload_csv_btn').hide();
    }
});

// Load data on page load
$(document).ready(function() {
    loadAutoPPMAssets();
    
    // Load branches when corporate filter changes
    $('#filter_corporate').on('change', function() {
        var CorporateID = $(this).val();
        $('#filter_branch').html('<option value="">All Branches</option>');
        
        if (CorporateID) {
            $.ajax({
                url: '../auto-ppm/ajax/get_branches_list.php',
                type: 'GET',
                data: { CompanyID: CorporateID },
                dataType: 'json',
                success: function(response) {
                    if (response.error == false && response.data) {
                        $.each(response.data, function(index, branch) {
                            $('#filter_branch').append('<option value="' + branch.ID + '">' + branch.BranchSite + '</option>');
                        });
                        // Re-initialize Select2 for filter_branch
                        $('#filter_branch').select2({
                            placeholder: "All Branches",
                            allowClear: true
                        });
                    }
                }
            });
        } else {
            // Re-initialize Select2 even if no corporate selected
            $('#filter_branch').select2({
                placeholder: "All Branches",
                allowClear: true
            });
        }
    });
    
    // Reset all column filters when Corporate or Branch filter changes
    $('#filter_corporate, #filter_branch').on('change', function() {
        // Reset other column filters
        $('#filter_equipment').val('');
        $('#filter_make').val('');
        $('#filter_model').val('');
        $('#filter_interval').val('');
        
        // Clear DataTable column filters
        if ($.fn.DataTable.isDataTable('#auto_ppm_assets_table')) {
            table.columns().search('').draw();
        }
    });
});
</script>
</body>
</html>