<?php
include("../controllers/common_controllers.php");
include('../branch-assets/controller/branch_assets_controller.php');
$conn = _connectodb();
$uniqueCode = $_GET['code'];
$sql = "SELECT * FROM branchassets_qr_code WHERE UniqueCode = '$uniqueCode'";
$res = $conn->query($sql);
$data = $res->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Assignment</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex justify-content-center align-items-center vh-100">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8 col-12">

            <?php if ($data['Assigned'] == 0) { ?>
                <div class="card shadow-lg border-0 rounded-3">
                    <div class="card-body p-4">
                        <h3 class="card-title text-center mb-4 text-primary">Assign QR Code</h3>
                        <form method="post" action="assign_qr.php">
                            <input type="hidden" name="code" value="<?= $uniqueCode ?>">

                            <div class="mb-3">
                                <label for="asset_id" class="form-label fw-semibold">Select Asset</label>
                                <select name="asset_id" id="asset_id" class="form-select" required>
                                    <option value="" disabled selected>-- Choose Asset --</option>
                                    <?php
                                    $assets = $conn->query("SELECT ID, EquipmentName FROM branch_assets");
                                    while ($a = $assets->fetch_assoc()) {
                                        echo "<option value='".$a['ID']."'>".$a['EquipmentName']."</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Assign</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php } else {
                $assetId = $data['AssetID'];
                $asset = $conn->query("SELECT * FROM branch_assets WHERE ID = $assetId")->fetch_assoc();
            ?>
                <div class="card shadow-lg border-0 rounded-3">
                    <div class="card-body p-4">
                        <h3 class="card-title text-center mb-4 text-success">Asset Details</h3>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item"><strong>Name:</strong> <?= $asset['EquipmentName'] ?></li>
                            <li class="list-group-item"><strong>Unique Code:</strong> <?= $data['UniqueCode'] ?></li>
                        </ul>
                        <div class="mt-3 text-center">
                            <a href="index.php" class="btn btn-outline-secondary">Back</a>
                        </div>
                    </div>
                </div>
            <?php } ?>

        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
