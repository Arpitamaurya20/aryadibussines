<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    include('../controllers/common_controllers.php');
    setNavigation($_SESSION['Roles']);
    $UserType = SessionCheck();
    ?>
    <meta charset="utf-8">
    <title>Manage company quote details - Aryadibusiness</title>
    <meta name="description" content="Quote company details">
    <?php include('../includes/common_head_content.php'); ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <style>
        .modal_header {
            background-color: #003f88;
            color: #fff;
        }
        .modal_header button {
            opacity: 1;
            color: #fff;
        }
        #modal_quote_company_details_view .qcd-view-label {
            font-weight: 600;
            color: #505050;
            margin-bottom: 0.15rem;
        }
        #modal_quote_company_details_view .qcd-view-value {
            margin-bottom: 0.85rem;
            white-space: pre-wrap;
            word-break: break-word;
        }
    </style>
</head>
<?php $filter_param = "?UserType=" . urlencode($UserType); ?>

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
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Configuration</a></li>
                        <li class="breadcrumb-item active">Manage company quote details</li>
                    </ol>

                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>Manage company quote details</h2>
                                    <a href="#" onclick="openQuoteCompanyDetailsModal('add'); return false;" class="btn btn-info" style="margin-right:20px;">Add</a>
                                </div>
                                <?php include('./include/quote-company-details-list-view.php'); ?>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="modal_quote_company_details" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header modal_header">
                                    <h5 class="modal-title" id="modal_quote_company_details_title">Add company quote details</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form id="form_quote_company_details" enctype="multipart/form-data">
                                        <input type="hidden" name="form_action" id="form_action_qcd" value="add">
                                        <input type="hidden" name="ID" id="qcd_id" value="">
                                        <input type="hidden" name="ExistingHeaderImage" id="qcd_existing_header_image" value="">
                                        <input type="hidden" name="ExistingStampImage" id="qcd_existing_stamp_image" value="">
                                        <div class="form-group">
                                            <label>Company name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="CompanyName" id="qcd_company_name" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Company address</label>
                                            <textarea class="form-control" name="CompanyAddress" id="qcd_company_address" rows="3"></textarea>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>GST number</label>
                                                <input type="text" class="form-control" name="GstNumber" id="qcd_gst">
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>PAN number</label>
                                                <input type="text" class="form-control" name="PanNumber" id="qcd_pan">
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>Email</label>
                                                <input type="email" class="form-control" name="Email" id="qcd_email">
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Phone</label>
                                                <input type="text" class="form-control" name="Phone" id="qcd_phone">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Header image (only image files)</label>
                                            <input type="file" class="form-control" name="HeaderImageFile" id="qcd_header_image" accept="image/*">
                                            <small class="form-text text-muted">Supported: JPG, JPEG, PNG, WEBP, GIF.</small>
                                            <div id="qcd_existing_header_image_wrap" class="mt-2" style="display:none;">
                                                <a id="qcd_existing_header_image_link" href="#" target="_blank">View current header image</a>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Stamp image (only image files)</label>
                                            <input type="file" class="form-control" name="StampImageFile" id="qcd_stamp_image" accept="image/*">
                                            <small class="form-text text-muted">Supported: JPG, JPEG, PNG, WEBP, GIF.</small>
                                            <div id="qcd_existing_stamp_image_wrap" class="mt-2" style="display:none;">
                                                <a id="qcd_existing_stamp_image_link" href="#" target="_blank">View current stamp image</a>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary" onclick="return submitQuoteCompanyDetailsForm();">Save</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="modal_quote_company_details_view" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header modal_header">
                                    <h5 class="modal-title">Company quote details</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="qcd-view-label">ID</div>
                                            <div class="qcd-view-value" id="qcd_view_id"></div>
                                            <div class="qcd-view-label">Company name</div>
                                            <div class="qcd-view-value" id="qcd_view_company_name"></div>
                                            <div class="qcd-view-label">Company address</div>
                                            <div class="qcd-view-value" id="qcd_view_company_address"></div>
                                            <div class="qcd-view-label">GST number</div>
                                            <div class="qcd-view-value" id="qcd_view_gst"></div>
                                            <div class="qcd-view-label">PAN number</div>
                                            <div class="qcd-view-value" id="qcd_view_pan"></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="qcd-view-label">Email</div>
                                            <div class="qcd-view-value" id="qcd_view_email"></div>
                                            <div class="qcd-view-label">Phone</div>
                                            <div class="qcd-view-value" id="qcd_view_phone"></div>
                                            <div class="qcd-view-label">Created at</div>
                                            <div class="qcd-view-value" id="qcd_view_created_at"></div>
                                            <div class="qcd-view-label">Status</div>
                                            <div class="qcd-view-value" id="qcd_view_status"></div>
                                            <div class="qcd-view-label">Header image</div>
                                            <div class="qcd-view-value" id="qcd_view_header_image"></div>
                                            <div class="qcd-view-label">Stamp image</div>
                                            <div class="qcd-view-value" id="qcd_view_stamp_image"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
                <div class="page-content-overlay" data-action="toggle" data-class="mobile-nav-on"></div>
                <?php include('../includes/common_footer.php'); ?>
            </div>
        </div>
    </div>
    <?php
    include('../includes/common_modules.php');
    include('../includes/common_scripts.php');
    ?>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/modules/quote-company-details.js"></script>
    <script>
        $(document).ready(function() {
            $('#quote-company-details-table').DataTable({
                responsive: true,
                processing: true,
                serverSide: true,
                ordering: false,
                serverMethod: 'post',
                ajax: {
                    url: 'include/quote-company-details-list-post.php<?php echo $filter_param; ?>'
                },
                columnDefs: [{ targets: [0], className: 'text-center' }],
                columns: [
                    {
                        data: null,
                        render: function(data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    { data: 'CompanyName' },
                    { data: 'Email' },
                    { data: 'Phone' },
                    { data: 'GstNumber' },
                    { data: 'Status' },
                    { data: 'Actions' }
                ]
            });
        });
    </script>
</body>

</html>
