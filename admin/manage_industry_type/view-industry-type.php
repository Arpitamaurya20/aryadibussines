<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
        include('../controllers/common_controllers.php');

        setNavigation($_SESSION['Roles']);
        $UserType = SessionCheck();
        $conn = _connectodb();
        ?>
    <meta charset="utf-8">
    <title>
        Manage Industry Type - Aryadibusiness
    </title>
    <meta name="description" content="Manage Industry Type">
    <?php
        include('../includes/common_head_content.php');
        ?>
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
    </style>
</head>
<?php
    $UserType = SessionCheck();
    $filter_param = "?UserType=" . $UserType;
    ?>

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
                        <li class="breadcrumb-item active">Manage Industry Type</li>
                    </ol>

                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>Manage Industry Type</h2>
                                    <?php if ($UserType == "Admin" || $UserType == "Sub Admin") { ?>
                                    <a href="#" onclick="ExportIndustryTypeData()" class="btn btn-info" style="margin-right:20px;">Export Data</a>
                                    <?php } ?>
                                    <a href="#" onclick="addIndustryType()" class="btn btn-info" style="margin-right:20px;">Add</a>
                                </div>

                                <?php include('./include/industry-type-list-view.php'); ?>

                            </div><!-- panel-1 -->
                        </div><!-- col-xl-12 -->
                    </div><!-- row -->


                    <!-- Add / Edit Modal -->
                    <div class="modal fade" id="addIndustryTypeModal" tabindex="-1" role="dialog" aria-labelledby="industryTypeModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header modal_header">
                                    <h5 class="modal-title" id="industryTypeModalLabel">Add Industry Type</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form METHOD="POST" id="add_industry_type_form">
                                        <div class="form-group">
                                            <label for="industry_type_name">Industry Type Name</label>
                                            <input type="text" class="form-control" name="industry_type_name" id="industry_type_name"
                                                placeholder="Enter Industry Type Name">
                                        </div>

                                        <div class="form-group">
                                            <label for="is_active">Status</label>
                                            <select class="form-control" name="is_active" id="is_active">
                                                <option value="1">Active</option>
                                                <option value="0">Inactive</option>
                                            </select>
                                        </div>

                                        <input type="hidden" name="industry_type_id" id="industry_type_id">
                                        <button type="submit" id="submit_industry_type" class="btn btn-primary"
                                            onclick="return save_industry_type()">Submit</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

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
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="./conf-industry-type-new.js"></script>

    <script>
        $(document).ready(function () {
            $('#view-industry-type-data').dataTable({
                responsive: true,
                'processing': true,
                'serverSide': true,
                'ordering': false,
                'serverMethod': 'post',
                'ajax': {
                    'url': 'ajax/industry-type-list-post.php<?php echo $filter_param; ?>'
                },
                'columnDefs': [{
                    "targets": [0],
                    "className": "text-center"
                }],
                "order": [[1, 'asc']],
                'columns': [
                    {
                        "data": "id",
                        render: function (data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    { data: 'IndustryTypeName' },
                    { data: 'IsActive' },
                    { data: 'CreatedDate' },
                    { data: 'Action' }
                ]
            });
        });
    </script>

</body>
</html>