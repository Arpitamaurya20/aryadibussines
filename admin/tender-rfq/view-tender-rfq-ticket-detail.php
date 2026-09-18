<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    require_once('../includes/autoloader.inc.php');
    include('../controllers/common_controllers.php');
    include('controller/tender_rfq_controller.php');
    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);
    $conn = _connectodb();
    if (!isset($_Nav_Tender_RFQ) || !$_Nav_Tender_RFQ) {
        header('HTTP/1.0 403 Forbidden');
        echo 'Access denied.';
        exit;
    }
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $trfq = new TenderRfq($conn);
    $ticket = $trfq->getTicketById($id, tender_rfq_session_roles(), $UserType);
    if (!$ticket) {
        header('Location: view-tender-rfq-tickets');
        exit;
    }
    $headers = $trfq->decodeHeaders($ticket);
    $rows = $trfq->getImportRows($id);
    $publicId = $trfq->ticketPublicId($id);
    $qref = $ticket['quotation_ref'] ? $ticket['quotation_ref'] : $trfq->defaultQuotationRef($id);
    $flashOk = isset($_GET['ok']) && $_GET['ok'] === '1';
    $flashErr = isset($_GET['err']) ? (string) $_GET['err'] : '';
    ?>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($publicId); ?> — Tender RFQ</title>
    <?php include('../includes/common_head_content.php'); ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    
    <style>
        /* Modern Card & Detail Styling Enhancements */
        .info-card {
            background: #f8f9fa;
            border-left: 4px solid #377dff;
            border-radius: 6px;
            padding: 1rem;
            height: 100%;
        }
        .meta-label {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
            font-weight: 600;
            margin-bottom: 2px;
        }
        .meta-value {
            font-size: 0.95rem;
            font-weight: 600;
            color: #212529;
            margin-bottom: 0;
        }
        .upload-box {
            background: #f1f5f9;
            border: 2px dashed #cbd5e1;
            border-radius: 8px;
            padding: 1.5rem;
            transition: all 0.2s ease-in-out;
        }
        .upload-box:hover {
            border-color: #377dff;
            background: #eef2ff;
        }
        .badge-chip {
            font-size: 0.8rem;
            padding: 0.35em 0.65em;
            border-radius: 50rem;
        }
    </style>
</head>
<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
    <?php include('../js/theme_settings.js'); ?>
    <div class="page-wrapper">
        <div class="page-inner">
            <?php include('../navigation/admin_navigation.php'); ?>
            <div class="page-content-wrapper">
                <?php include('../includes/common_header.php'); ?>
                <main id="js-page-content" role="main" class="page-content">
                    
                    <!-- Breadcrumbs -->
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">Aryadibusiness</a></li>
                        <li class="breadcrumb-item"><a href="view-tender-rfq-tickets">Tender RFQ</a></li>
                        <li class="breadcrumb-item active"><?= htmlspecialchars($publicId); ?></li>
                    </ol>

                    <!-- Alert Messages -->
                    <?php if ($flashOk) { ?>
                    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fal fa-check-circle fa-lg mr-2"></i>
                            <div>CSV imported successfully. You can download the updated PDF below.</div>
                        </div>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <?php } ?>

                    <?php if ($flashErr !== '') { ?>
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fal fa-exclamation-triangle fa-lg mr-2"></i>
                            <div><?= htmlspecialchars(rawurldecode($flashErr)); ?></div>
                        </div>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <?php } ?>

                    <!-- Main Content Panel -->
                    <div class="panel shadow-sm rounded">
                        <!-- Panel Header & Action Bar -->
                        <div class="panel-hdr py-3 px-4 d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom">
                            <div>
                                <h2 class="mb-0 d-flex align-items-center">
                                    <span class="font-weight-bold text-dark mr-2"><?= htmlspecialchars($publicId); ?></span>
                                    <?php if (!empty($ticket['title'])) { ?>
                                        <span class="text-muted font-weight-normal border-left pl-2 ml-1" style="font-size: 1.1rem;"><?= htmlspecialchars($ticket['title']); ?></span>
                                    <?php } ?>
                                </h2>
                            </div>
                            <div class="btn-group mt-2 mt-sm-0" role="group">
                                <a class="btn btn-primary font-weight-bold shadow-sm" href="action/generate-tender-rfq-pdf.php?id=<?= (int) $id; ?>" target="_blank">
                                    <i class="fal fa-file-pdf mr-1"></i> Download PDF
                                </a>
                                <a class="btn btn-outline-secondary ml-2" href="view-tender-rfq-tickets">
                                    <i class="fal fa-arrow-left mr-1"></i> Back to List
                                </a>
                            </div>
                        </div>

                        <div class="panel-container show">
                            <div class="panel-content p-4">
                                
                                <!-- Meta Information Cards Grid -->
                                <div class="row mb-4">
                                    <div class="col-sm-6 col-lg-3 mb-3">
                                        <div class="p-3 bg-light rounded border">
                                            <div class="meta-label"><i class="fal fa-hashtag mr-1"></i> Quotation Ref</div>
                                            <div class="meta-value text-primary"><?= htmlspecialchars($qref); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-lg-3 mb-3">
                                        <div class="p-3 bg-light rounded border">
                                            <div class="meta-label"><i class="fal fa-calendar-alt mr-1"></i> Quotation Date</div>
                                            <div class="meta-value"><?= htmlspecialchars($ticket['quotation_date'] ?? 'N/A'); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-lg-3 mb-3">
                                        <div class="p-3 bg-light rounded border">
                                            <div class="meta-label"><i class="fal fa-clock mr-1"></i> Expiry Date</div>
                                            <div class="meta-value text-danger"><?= htmlspecialchars($ticket['expiry_date'] ?? 'N/A'); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-lg-3 mb-3">
                                        <div class="p-3 bg-light rounded border">
                                            <div class="meta-label"><i class="fal fa-list-ol mr-1"></i> Imported Line Items</div>
                                            <div class="meta-value">
                                                <span class="badge badge-primary badge-chip"><?= (int) ($ticket['import_row_count'] ?? 0); ?> Rows</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Key Metadata & Parties Section -->
                                <div class="row mb-4">
                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100 border shadow-none">
                                            <div class="card-header bg-white font-weight-bold py-2 border-bottom">
                                                <i class="fal fa-user-tie mr-1 text-primary"></i> Customer & Reference Details
                                            </div>
                                            <div class="card-body">
                                                <p class="mb-2"><strong>Client Ref:</strong> <span class="text-dark"><?= htmlspecialchars($ticket['client_reference'] ?? '—'); ?></span></p>
                                                <p class="mb-2"><strong>Customer:</strong> <span class="text-dark"><?= htmlspecialchars($ticket['customer_name'] ?? '—'); ?></span> (<?= htmlspecialchars($ticket['customer_contact'] ?? 'No contact'); ?>)</p>
                                                <p class="mb-2"><strong>Sales Person:</strong> <span class="text-dark"><?= htmlspecialchars($ticket['sales_person'] ?? '—'); ?></span></p>
                                                <p class="mb-0"><strong>Place of Supply:</strong> <span class="text-dark"><?= htmlspecialchars($ticket['place_of_supply'] ?? '—'); ?></span></p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100 border shadow-none">
                                            <div class="card-header bg-white font-weight-bold py-2 border-bottom">
                                                <i class="fal fa-file-invoice-dollar mr-1 text-success"></i> Billing & Shipping
                                            </div>
                                            <div class="card-body">
                                                <p class="mb-1"><strong>Bill To:</strong> <?= htmlspecialchars($ticket['bill_to_entity'] ?? '—'); ?></p>
                                                <p class="text-muted small mb-2"><?= nl2br(htmlspecialchars($ticket['bill_to_address'] ?? '')); ?></p>
                                                <p class="mb-2"><strong>GSTIN:</strong> <span class="badge badge-light border"><?= htmlspecialchars($ticket['bill_to_gstin'] ?? 'N/A'); ?></span></p>
                                                <hr class="my-2">
                                                <p class="mb-0"><strong>Ship To:</strong> <span class="text-muted"><?= nl2br(htmlspecialchars($ticket['ship_to'] ?? 'Same as Billing')); ?></span></p>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if (!empty($ticket['description'])) { ?>
                                    <div class="col-12">
                                        <div class="p-3 bg-white rounded border">
                                            <strong class="text-muted d-block mb-1">Description / Notes:</strong>
                                            <div><?= nl2br(htmlspecialchars($ticket['description'])); ?></div>
                                        </div>
                                    </div>
                                    <?php } ?>
                                </div>

                                <hr class="my-4">

                                <!-- CSV Upload Section -->
                                <div class="upload-box mb-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mr-3" style="width: 40px; height: 40px; font-size: 1.2rem;">
                                            <i class="fal fa-file-csv"></i>
                                        </div>
                                        <div>
                                            <h5 class="mb-0 font-weight-bold">Import Line Items (CSV)</h5>
                                            <small class="text-muted">Uploading a new CSV replaces existing line items. Data is stored dynamically per row.</small>
                                        </div>
                                    </div>

                                    <form method="post" action="action/upload-csv.php" enctype="multipart/form-data">
                                        <input type="hidden" name="ticket_id" value="<?= (int) $id; ?>">
                                        <div class="form-row align-items-end">
                                            <div class="form-group col-md-5 mb-3 mb-md-0">
                                                <label class="font-weight-bold">Select CSV File <span class="text-danger">*</span></label>
                                                <div class="custom-file">
                                                    <input type="file" name="csv_file" class="form-control" accept=".csv,text/csv" required>
                                                </div>
                                            </div>
                                            <div class="form-group col-md-4 mb-3 mb-md-0">
                                                <label class="font-weight-bold">Unique Column Header <small class="text-muted">(Optional)</small></label>
                                                <input type="text" name="unique_key_header" class="form-control" placeholder="e.g. PO Number, Item Code">
                                            </div>
                                            <div class="form-group col-md-3 mb-0">
                                                <button type="submit" class="btn btn-primary btn-block font-weight-bold">
                                                    <i class="fal fa-upload mr-1"></i> Upload & Import
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <!-- Data Preview Section -->
                                <?php if (count($headers) && count($rows)) { ?>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="font-weight-bold mb-0">
                                            <i class="fal fa-table mr-1 text-info"></i> Imported Data Preview
                                        </h5>
                                        <span class="text-muted small">Showing CSV order structure</span>
                                    </div>

                                    <div class="table-responsive rounded border">
                                        <table id="preview_table" class="table table-hover table-striped table-sm w-100 mb-0">
                                            <thead class="thead-dark">
                                                <tr>
                                                    <?php foreach ($headers as $h) { ?>
                                                        <th class="py-2"><?= htmlspecialchars($h); ?></th>
                                                    <?php } ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($rows as $r) {
                                                    $obj = json_decode($r['dynamic_data'], true);
                                                    if (!is_array($obj)) {
                                                        $obj = [];
                                                    }
                                                    ?>
                                                <tr>
                                                    <?php foreach ($headers as $h) { ?>
                                                        <td><?= htmlspecialchars($obj[$h] ?? ''); ?></td>
                                                    <?php } ?>
                                                </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php } else { ?>
                                    <div class="text-center py-5 border rounded bg-light">
                                        <i class="fal fa-folder-open fa-3x text-muted mb-2"></i>
                                        <h6 class="text-muted font-weight-bold">No CSV Data Imported Yet</h6>
                                        <p class="text-muted small mb-0">Upload a valid CSV file using the form above to display and store item details.</p>
                                    </div>
                                <?php } ?>

                            </div>
                        </div>
                    </div>
                </main>
                <div class="page-content-overlay" data-action="toggle" data-class="mobile-nav-on"></div>
                <?php include('../includes/common_footer.php'); ?>
            </div>
        </div>
    </div>
    <?php include('../includes/common_modules.php'); ?>
    <?php include('../includes/common_scripts.php'); ?>
    <?php if (count($headers) && count($rows)) { ?>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script>
        $(document).ready(function() {
            $('#preview_table').dataTable({
                pageLength: 25,
                scrollX: true,
                responsive: true,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search table..."
                }
            });
        });
    </script>
    <?php } ?>
</body>
</html>