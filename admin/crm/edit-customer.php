<?php 
session_start();
include('../controllers/common_controllers.php');
include('controller/crm_controller.php');
$UserType = SessionCheck();
setNavigation($_SESSION['Roles']);
$conn = _connectodb();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id === 0) {
    header("Location: view-customers.php");
    exit;
}

$sql = "SELECT * FROM crm_accounts WHERE ID = $id";
$res = mysqli_query($conn, $sql);
$customer = mysqli_fetch_assoc($res);
if (!$customer) {
    echo "Customer not found."; exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Edit Customer - <?= htmlspecialchars($customer['AccountName']) ?></title>
    <?php include('../includes/common_head_content.php'); ?>
</head>
<body class="mod-bg-1">
    <div class="page-wrapper">
        <div class="page-inner">
            <?php include('../navigation/admin_navigation.php'); ?>
            <div class="page-content-wrapper">
                <?php include('../includes/common_header.php'); ?>
                <main id="js-page-content" role="main" class="page-content">
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="view-customers.php">CRM Customers</a></li>
                        <li class="breadcrumb-item active">Edit Customer</li>
                    </ol>
                    
                    <div class="row">
                        <div class="col-xl-8">
                            <div class="panel">
                                <div class="panel-hdr">
                                    <h2>Edit <?= htmlspecialchars($customer['AccountName']) ?></h2>
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <form action="action/update_customer.php" method="POST">
                                            <input type="hidden" name="ID" value="<?= $id ?>">
                                            
                                            <div class="form-group row">
                                                <div class="col-md-6">
                                                    <label class="form-label">Company / Account Name <span class="text-danger">*</span></label>
                                                    <input type="text" name="AccountName" class="form-control" value="<?= htmlspecialchars($customer['AccountName']) ?>" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Contact Person</label>
                                                    <input type="text" name="ContactPerson" class="form-control" value="<?= htmlspecialchars($customer['ContactPerson'] ?? '') ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="form-group row">
                                                <div class="col-md-6">
                                                    <label class="form-label">Email</label>
                                                    <input type="email" name="Email" class="form-control" value="<?= htmlspecialchars($customer['Email']) ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Phone / Mobile</label>
                                                    <input type="text" name="Mobile" class="form-control" value="<?= htmlspecialchars($customer['Mobile'] ?? '') ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="form-group row">
                                                <div class="col-md-6">
                                                    <label class="form-label">GST Number</label>
                                                    <input type="text" name="GST" class="form-control" value="<?= htmlspecialchars($customer['GST'] ?? '') ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Customer Type</label>
                                                    <select name="CustomerType" class="form-control">
                                                        <option value="B2B" <?= $customer['CustomerType'] == 'B2B' ? 'selected' : '' ?>>B2B</option>
                                                        <option value="B2C" <?= $customer['CustomerType'] == 'B2C' ? 'selected' : '' ?>>B2C</option>
                                                        <option value="Enterprise" <?= $customer['CustomerType'] == 'Enterprise' ? 'selected' : '' ?>>Enterprise</option>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="form-group mt-4">
                                                <button type="submit" class="btn btn-primary">Update Customer</button>
                                                <a href="view-customers.php" class="btn btn-secondary">Cancel</a>
                                            </div>
                                        </form>
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
    <?php include('../includes/common_scripts.php'); ?>
</body>
</html>
