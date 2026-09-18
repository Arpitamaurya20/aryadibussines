```php
<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
<?php
include('../includes/autoloader.inc.php');
include('../controllers/common_controllers.php');

$UserType = SessionCheck();
$conn = _connectodb();
?>

<meta charset="utf-8">
<title>Employee Concerns - Aryadibusiness</title>

<?php include('../includes/common_head_content.php'); ?>

<link rel="stylesheet" href="../css/datagrid/datatables/datatables.bundle.css">

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

<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">

<?php include('../navigation/admin_navigation.php'); ?>

<div class="page-wrapper">
<div class="page-inner">

<div class="page-content-wrapper">

<?php include('../includes/common_header.php'); ?>

<main id="js-page-content" role="main" class="page-content">

<div class="d-flex justify-content-between mb-3 align-items-center">
    <ol class="breadcrumb page-breadcrumb">
        <li class="breadcrumb-item">Aryadibusiness</li>
        <li class="breadcrumb-item active">Employee Concerns</li>
    </ol>
</div>

<!-- TABLE -->
<div class="row">
<div class="col-xl-12">

<div class="panel">
<div class="panel-hdr">
    <h2>Employee Concerns List</h2>
</div>

<div class="panel-container show">
<div class="panel-content">

<table id="view-employee-concerns"
       class="table table-bordered table-hover table-striped w-100">
    <thead>
        <tr>
            <th>#</th>
            <th>Name</th>
            <th>Mobile</th>
            <th>Issue</th>
            <th>Attachment</th>
            <th>Anonymous</th>
            <th>Status</th>
            <th>Created At</th>
            <th>Action</th>
        </tr>
    </thead>
</table>

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

<?php
include('../includes/common_modules.php');
include('../includes/common_scripts.php');
?>

<script src="../js/datagrid/datatables/datatables.bundle.js"></script>

<script>
$(document).ready(function () {

    $('#view-employee-concerns').DataTable({
        processing: true,
        serverSide: true,
        ordering: false,
        ajax: {
            url: 'ajax/post-employee-concern.php',
            type: 'POST'
        },

        columns: [
            {
                data: "Id",
                render: function (data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            { data: 'Name' },
            { data: 'Mobile' },
            { data: 'Issue' },
            {
                data: 'Attachment',
                render: function (data) {
                    if (data) {
                        return '<a href="../uploads/' + data + '" target="_blank">View</a>';
                    }
                    return 'No File';
                }
            },
            {
                data: 'IsAnonymous',
                render: function (data) {
                    return data == 1 ? 'Yes' : 'No';
                }
            },
            {
                data: 'Status',
                render: function (data) {
                    if (data == 'New') {
                        return '<span class="badge badge-primary">New</span>';
                    } else if (data == 'Contacted') {
                        return '<span class="badge badge-warning">Contacted</span>';
                    } else {
                        return '<span class="badge badge-success">Closed</span>';
                    }
                }
            },
            { data: 'CreatedAt' },
            {
                data: 'Id',
                render: function (data) {
                    return '<button class="btn btn-sm btn-primary" onclick="openStatusModal('+data+')">Update</button>';
                }
            }
        ]
    });

});

// OPEN MODAL
function openStatusModal(id) {
    $('#concern_id').val(id);
    $('#status_modal').modal('show');
}

// UPDATE STATUS
function updateStatus() {

    var id = $('#concern_id').val();
    var status = $('#status').val();

    $.ajax({
        url: 'action/add-update-employee-concern.php',
        type: 'POST',
        data: {
            Id: id,
            Status: status
        },
        success: function () {
            alert('Status Updated Successfully');
            $('#status_modal').modal('hide');
            $('#view-employee-concerns').DataTable().ajax.reload();
        }
    });

    return false;
}
</script>

</body>
</html>

<!-- STATUS MODAL -->
<div class="modal fade" id="status_modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header modal_header">
                <h5 class="modal-title">Update Concern Status</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <div class="modal-body">
                <form id="status_form">

                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" id="status">
                            <option value="New">New</option>
                            <option value="Contacted">Contacted</option>
                            <option value="Closed">Closed</option>
                        </select>
                    </div>

                    <input type="hidden" id="concern_id">

                    <button class="btn btn-success" onclick="return updateStatus()">Update</button>

                </form>
            </div>

        </div>
    </div>
</div>
```
