<?php 
session_start();
include('../controllers/common_controllers.php');
include('controller/crm_controller.php');
$UserType = SessionCheck();
setNavigation($_SESSION['Roles']);
$conn = _connectodb();

$lead_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($lead_id === 0) {
    header("Location: view-leads.php");
    exit;
}

$sql = "SELECT * FROM crm_leads WHERE ID = $lead_id";
$res = mysqli_query($conn, $sql);
$lead = mysqli_fetch_assoc($res);
if (!$lead) {
    echo "Lead not found."; exit;
}

// Get Follow-ups
$f_sql = "SELECT * FROM crm_followups WHERE LeadID = $lead_id ORDER BY FollowupDate DESC, FollowupTime DESC";
$f_res = mysqli_query($conn, $f_sql);
$followups = [];
if($f_res) {
    while($r = mysqli_fetch_assoc($f_res)) $followups[] = $r;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Lead Details - <?= htmlspecialchars($lead['LeadName']) ?></title>
    <?php include('../includes/common_head_content.php'); ?>
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
                        <li class="breadcrumb-item"><a href="view-leads.php">CRM Leads</a></li>
                        <li class="breadcrumb-item active"><?= htmlspecialchars($lead['LeadName']) ?></li>
                    </ol>
                    
                    <div class="row">
                        <div class="col-xl-4">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h4 class="card-title"><?= htmlspecialchars($lead['LeadName']) ?></h4>
                                    <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($lead['CompanyName']) ?></h6>
                                    <hr>
                                    <p><strong>Email:</strong> <?= htmlspecialchars($lead['Email']) ?></p>
                                    <p><strong>Phone:</strong> <?= htmlspecialchars($lead['Phone']) ?></p>
                                    <p><strong>Status:</strong> <span class="badge badge-info"><?= htmlspecialchars($lead['LeadStatus']) ?></span></p>
                                    <p><strong>Source:</strong> <?= htmlspecialchars($lead['LeadSource']) ?></p>
                                    <p><strong>Notes:</strong> <?= nl2br(htmlspecialchars($lead['Notes'])) ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-8">
                            <div class="panel">
                                <div class="panel-hdr">
                                    <h2>Tracking & Follow-ups</h2>
                                    <div class="panel-toolbar">
                                        <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addFollowupModal">Log Follow-up</button>
                                    </div>
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <table class="table table-bordered">
                                            <thead class="bg-primary-600">
                                                <tr>
                                                    <th>Date & Time</th>
                                                    <th>Type</th>
                                                    <th>Status</th>
                                                    <th>Notes</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($followups as $f): ?>
                                                <tr>
                                                    <td><?= $f['FollowupDate'] ?> <?= $f['FollowupTime'] ?></td>
                                                    <td><?= htmlspecialchars($f['Type']) ?></td>
                                                    <td><?= htmlspecialchars($f['Status']) ?></td>
                                                    <td><?= htmlspecialchars($f['Notes']) ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                                <?php if(empty($followups)): ?>
                                                    <tr><td colspan="4" class="text-center">No follow-ups logged yet.</td></tr>
                                                <?php endif; ?>
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

    <!-- Add Followup Modal -->
    <div class="modal fade" id="addFollowupModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header modal_header">
                    <h5 class="modal-title">Log Follow-up</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fal fa-times"></i></span>
                    </button>
                </div>
                <form action="action/add_followup.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="LeadID" value="<?= $lead_id ?>">
                        <div class="form-group">
                            <label class="form-label">Type</label>
                            <select name="Type" class="form-control">
                                <option value="Call">Call</option>
                                <option value="Meeting">Meeting</option>
                                <option value="Email">Email</option>
                                <option value="WhatsApp">WhatsApp</option>
                            </select>
                        </div>
                        <div class="form-group row">
                            <div class="col-6">
                                <label class="form-label">Date</label>
                                <input type="date" name="FollowupDate" class="form-control" required value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Time</label>
                                <input type="time" name="FollowupTime" class="form-control" required value="<?= date('H:i') ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="Status" class="form-control">
                                <option value="Pending">Pending</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Notes</label>
                            <textarea name="Notes" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include('../includes/common_scripts.php'); ?>
</body>
</html>
