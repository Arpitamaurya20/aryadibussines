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
    $filterCategory = isset($_GET['category_id']) ? (int) $_GET['category_id'] : 0;
    $filterSearch = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
    $checklists = getDynamicPPMChecklistMastersWithStats($conn, array(
        'CategoryID' => $filterCategory,
        'search' => $filterSearch,
        'IsActive' => '1',
    ));
    ?>
    <meta charset="utf-8">
    <title>Dynamic PPM Checklists - TechXpert</title>
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
                    <li class="breadcrumb-item"><a href="view-master-checklist.php">Dynamic PPM</a></li>
                    <li class="breadcrumb-item active">View Checklists</li>
                </ol>

                <div class="row">
                    <div class="col-xl-12">
                        <div class="panel">
                            <div class="panel-hdr">
                                <h2>Dynamic PPM Checklists</h2>
                                <div>
                                    <a href="view-master-checklist.php" class="btn btn-primary btn-sm">Master Setup</a>
                                </div>
                            </div>
                            <div class="panel-container show">
                                <div class="panel-content">
                                    <form method="get" class="mb-3">
                                        <div class="form-row">
                                            <div class="col-md-4 form-group">
                                                <label>Category</label>
                                                <select name="category_id" class="form-control dppm-select2">
                                                    <option value="0">All Categories</option>
                                                    <?php foreach ($categories as $cat) { ?>
                                                        <option value="<?php echo (int) $cat['ID']; ?>"<?php echo $filterCategory === (int) $cat['ID'] ? ' selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($cat['CategoriesName']); ?>
                                                        </option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                            <div class="col-md-5 form-group">
                                                <label>Search</label>
                                                <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($filterSearch); ?>" placeholder="Code, name, category">
                                            </div>
                                            <div class="col-md-3 form-group d-flex align-items-end">
                                                <button type="submit" class="btn btn-info btn-sm mr-2">Filter</button>
                                                <a href="view-checklists.php" class="btn btn-outline-secondary btn-sm">Reset</a>
                                            </div>
                                        </div>
                                    </form>

                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover table-sm">
                                            <thead class="bg-primary-600 text-white">
                                                <tr>
                                                    <th>Code</th>
                                                    <th>Checklist Name</th>
                                                    <th>Category</th>
                                                    <th>Version</th>
                                                    <th>Items</th>
                                                    <th>Mappings</th>
                                                    <th>Created</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php if (empty($checklists)) { ?>
                                                <tr><td colspan="8" class="text-center text-muted">No checklists found.</td></tr>
                                            <?php } ?>
                                            <?php foreach ($checklists as $row) { ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['ChecklistCode']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['ChecklistName']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['CategoryName']); ?></td>
                                                    <td><?php echo (int) $row['VersionNo']; ?></td>
                                                    <td><?php echo (int) $row['ItemCount']; ?></td>
                                                    <td><?php echo (int) $row['MappingCount']; ?></td>
                                                    <td><?php echo htmlspecialchars($row['CreatedDate'] . ' ' . $row['CreatedTime']); ?></td>
                                                    <td>
                                                        <a href="view-checklist-details.php?id=<?php echo (int) $row['ID']; ?>" class="btn btn-xs btn-primary">View Details</a>
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
            </main>
            <?php include('../includes/common_footer.php'); ?>
        </div>
    </div>
</div>
<?php include('../includes/common_modules.php'); include('../includes/common_scripts.php'); ?>
<script>
$(document).ready(function () {
    if ($.fn.select2) {
        $('.dppm-select2').select2({ width: '100%' });
    }
});
</script>
</body>
</html>
