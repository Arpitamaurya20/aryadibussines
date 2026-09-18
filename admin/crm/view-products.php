<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php
        include('../controllers/common_controllers.php');
        include('controller/crm_controller.php');
        $UserType = SessionCheck();
        setNavigation($_SESSION['Roles']);
        $conn = _connectodb();
        $products = getAllProducts($conn);
    ?>
    <meta charset="utf-8">
    <title>Manage CRM Products</title>
    <meta name="description" content="View CRM Products">
    <?php include('../includes/common_head_content.php'); ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <style>
        .modal_header { background-color: #003f88; color: #fff; }
        .modal_header button { opacity: 1; color: #fff; }
    </style>
</head>
<body class="mod-bg-1">
    <div class="page-wrapper">
        <div class="page-inner">
            <?php include('../navigation/admin_navigation.php'); ?>
            <div class="page-content-wrapper">
                <?php include('../includes/common_header.php'); ?>
                <main id="js-page-content" role="main" class="page-content">
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">CRM</a></li>
                        <li class="breadcrumb-item active">Manage Products</li>
                    </ol>
                    <div class="subheader">
                        <h1 class="subheader-title">
                            <i class='subheader-icon fal fa-box'></i> Manage Products & Services
                        </h1>
                    </div>
                    
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="panel">
                                <div class="panel-hdr">
                                    <h2>Product Catalog</h2>
                                    <div class="panel-toolbar">
                                        <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addProductModal">Add New Product</button>
                                    </div>
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <table id="dt-basic-example" class="table table-bordered table-hover table-striped w-100">
                                            <thead class="bg-primary-600">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Product Name</th>
                                                    <th>SKU / Code</th>
                                                    <th>HSN/SAC</th>
                                                    <th>Price</th>
                                                    <th>GST %</th>
                                                    <th>Unit</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($products as $p): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($p['ID']) ?></td>
                                                    <td><?= htmlspecialchars($p['ProductName']) ?> <br> <small class="text-muted"><?= htmlspecialchars($p['Brand']) ?></small></td>
                                                    <td><?= htmlspecialchars($p['SKU']) ?></td>
                                                    <td><?= htmlspecialchars($p['HSN_SAC']) ?></td>
                                                    <td>&#8377; <?= number_format($p['UnitPrice'], 2) ?></td>
                                                    <td><?= number_format($p['GST_Percent'], 2) ?>%</td>
                                                    <td><?= htmlspecialchars($p['Unit']) ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
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

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header modal_header">
                    <h5 class="modal-title">Add New Product/Service</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fal fa-times"></i></span>
                    </button>
                </div>
                <form action="action/add_product.php" method="POST">
                    <div class="modal-body">
                        <div class="form-group row">
                            <div class="col-md-6">
                                <label class="form-label">Product/Service Name <span class="text-danger">*</span></label>
                                <input type="text" name="ProductName" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Brand</label>
                                <input type="text" name="Brand" class="form-control">
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-6">
                                <label class="form-label">SKU / Item Code</label>
                                <input type="text" name="SKU" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">HSN/SAC Code</label>
                                <input type="text" name="HSN_SAC" class="form-control">
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="form-label">Unit Price (&#8377;) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="UnitPrice" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">GST %</label>
                                <select name="GST_Percent" class="form-control">
                                    <option value="0">0%</option>
                                    <option value="5">5%</option>
                                    <option value="12">12%</option>
                                    <option value="18" selected>18%</option>
                                    <option value="28">28%</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Unit of Measurement</label>
                                <select name="Unit" class="form-control">
                                    <option value="Nos">Nos</option>
                                    <option value="Kg">Kg</option>
                                    <option value="Ltr">Ltr</option>
                                    <option value="Mtr">Mtr</option>
                                    <option value="Hrs">Hrs</option>
                                    <option value="Service">Service</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php 
        include('../includes/common_modules.php');
        include('../includes/common_scripts.php'); 
    ?>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script>
        $(document).ready(function() {
            $('#dt-basic-example').dataTable({
                responsive: true
            });
        });
    </script>
</body>
</html>
