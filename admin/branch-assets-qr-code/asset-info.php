<?php
$code = $_GET['code'] ?? '';
$format = $_GET['format'] ?? ''; 
header("Access-Control-Allow-Origin: *");
include("../controllers/common_controllers.php");
$conn = _connectodb();

// fetch asset
$sql = "SELECT a.*, q.UniqueCode 
        FROM branch_assets a 
        INNER JOIN branchassets_qr_code q ON a.ID = q.AssetID
        WHERE q.UniqueCode = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $code);
$stmt->execute();
$result = $stmt->get_result();
$asset = $result->fetch_assoc();

if (!$asset) {
   if ($format === "json" || 
    (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
) {
        header('Content-Type: application/json');
       echo json_encode([
            "status" => "error",
            "message" => "Asset not found or not assigned",
            "unique_code" => $code
        ]);
        exit;
    } else {
    http_response_code(404);
    echo '
    <style>
      body {
        margin: 0;
        padding: 0;
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #1e3c72, #2a5298);
        color: #333;
      }
      .asset-wrapper {
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        text-align: center;
      }
      .asset-card {
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 12px 32px rgba(0,0,0,0.2);
        padding: 3rem 2.5rem;
        max-width: 550px;
        width: 90%;
        transform: scale(1.05);
        animation: fadeInZoom 0.6s ease-in-out;
      }
      .asset-card i {
        font-size: 4rem;
        color: #dc3545;
        margin-bottom: 1.2rem;
      }
      .asset-card h2 {
        color: #dc3545;
        font-weight: 800;
        margin-bottom: 1rem;
        font-size: 2rem;
      }
      .asset-card p {
        color: #444;
        font-size: 1.1rem;
        margin-bottom: 2rem;
        line-height: 1.6;
      }
      .asset-card a {
        display: inline-block;
        text-decoration: none;
        background: #007bff;
        color: #fff;
        font-weight: 700;
        font-size: 1rem;
        padding: 0.85rem 2rem;
        border-radius: 10px;
        transition: all 0.3s ease;
      }
      .asset-card a:hover {
        background: #0056b3;
        transform: translateY(-3px) scale(1.05);
        box-shadow: 0 8px 18px rgba(0,0,0,0.25);
      }
      @keyframes fadeInZoom {
        from { opacity: 0; transform: scale(0.9); }
        to   { opacity: 1; transform: scale(1.05); }
      }
    </style>

    <div class="asset-wrapper">
      <div class="asset-card">
        <i class="bi bi-exclamation-octagon-fill"></i>
        <h2>Sorry! Asset Not Assigned</h2>
        <p>
          The QR code you scanned is not yet assigned to any asset.<br>
          Please contact your administrator, or visit our portal to proceed.
        </p>
        <a href="https://techxpertindia.in/" target="_blank">
          <i class="bi bi-arrow-right-circle me-2"></i> Visit Our Portal
        </a>
      </div>
    </div>
    ';
    exit;
}


}

// --- JSON mode ---
if ($format === "json" || 
    (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
) {
    header('Content-Type: application/json');
    echo json_encode(["status" => "success", "asset" => $asset]);
    exit;
}

// --- else HTML mode ---
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Asset Info</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body { background: #f4f6f9; font-family: 'Poppins', sans-serif; /* Apply font */ }
    .card { border-radius: 15px; }
    .card-header { background: #0d6efd; color: #fff; border-top-left-radius: 15px; border-top-right-radius: 15px; }
    .asset-info p { margin: 0.4rem 0; }
    .btn { border-radius: 10px; }
    .modal-content { border-radius: 15px; }
  </style>
</head>
<body>

 <header class="bg-white shadow-sm py-3 mb-4">
  <div class="container text-center">
    <img src="https://techxpertindia.in/images/techx-14.png" 
         alt="Company Logo" 
         style="height:60px;">
  </div>
</header>
<div class="container py-5">
  
<!--   <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold"><i class="bi bi-gear-wide-connected me-2"></i>Asset Details</h3>
    <span class="badge bg-primary">Code: <?php echo htmlspecialchars($code); ?></span>
  </div> -->
  
  <!-- Asset Info -->
  <div id="assetBox" class="card shadow-sm">
    <div class="card-body text-center text-muted">
      <div class="spinner-border text-primary" role="status"></div>
      <p class="mt-2">Loading asset details...</p>
    </div>
  </div>

  <!-- Actions -->
  <div class="d-flex gap-3 mt-4" id="actionButtons">
    <button class="btn btn-primary px-4" data-bs-toggle="modal" data-bs-target="#ticketModal">
      <i class="bi bi-exclamation-octagon me-2"></i> Raise Ticket
    </button>
    <a href="yourapp://asset/<?php echo htmlspecialchars($code); ?>" class="btn btn-success px-4">
      <i class="bi bi-box-arrow-up-right me-2"></i> Download App
    </a>
  </div>
</div>

<!-- Ticket Modal -->
<!-- Ticket Modal -->
<div class="modal fade" id="ticketModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" id="ticketForm">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="bi bi-ticket-detailed me-2"></i>Raise Ticket</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">

        <!-- Hidden fields set from asset API -->
        <input type="hidden" name="BranchID" id="BranchID">
        <input type="hidden" name="BranchAssetID" id="BranchAssetID">
        <input type="hidden" name="CreatedBy" id="CreatedBy">
        <input type="hidden" name="Type" id="Type" value="AMC"> <!-- Default -->


        <!-- Name -->
        <div class="mb-3">
          <label class="form-label fw-semibold">Your Name</label>
          <input type="text" name="Name" class="form-control" placeholder="Enter your name" required>
        </div>

        <!-- Phone Number -->
        <div class="mb-3">
          <label class="form-label fw-semibold">Phone Number</label>
          <input type="tel" name="PhoneNumber" class="form-control" placeholder="Enter your phone number" 
                 pattern="[0-9]{10}" maxlength="10" required>
          <div class="form-text">Enter 10-digit mobile number</div>
        </div>

        <!-- Issue -->
        <div class="mb-3">
          <label class="form-label fw-semibold">Issue Description</label>
          <textarea name="Message" class="form-control" rows="4" required placeholder="Describe the issue..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success px-4">
          <i class="bi bi-check2-circle me-2"></i> Submit
        </button>
      </div>
    </form>
  </div>
</div>


<!-- Toast -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1100">
  <div id="toast" class="toast align-items-center text-bg-primary border-0" role="alert">
    <div class="d-flex">
      <div class="toast-body"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Fetch asset details
fetch("/api/banch-assets-qr-code/getAssetInfo.php?code=<?php echo urlencode($code); ?>")
  .then(r => r.json())
  .then(d => {
    // case: asset not assigned
    if (d.assign_required) {
      document.getElementById('assetBox').innerHTML = `
        <div class="card-body text-center text-danger">
          <i class="bi bi-exclamation-triangle display-4 mb-3"></i>
          <h5>${d.message || "Asset Not Assigned"}</h5>
          <p class="mb-3">Please assign this asset first before raising tickets.</p>
          <a href="yourapp://assignAsset/<?php echo htmlspecialchars($code); ?>" 
             class="btn btn-outline-primary px-4">
            <i class="bi bi-link-45deg me-2"></i> Assign in App
          </a>
        </div>
      `;

      // also hide Raise Ticket button if exists
      document.getElementById("actionButtons").style.display = "none";
    }

    // case: valid asset details
    const asset = d.asset;
    if (asset) {
   document.getElementById('assetBox').innerHTML = `
  <div class="card shadow-lg border-0" style="border-radius:16px; overflow:hidden;">
    <div class="card-header text-white d-flex align-items-center" 
         style="background: linear-gradient(135deg, #0d6efd, #004085);">
      <i class="bi bi-gear-fill me-2 fs-4"></i>
      <h5 class="mb-0">${asset.EquipmentName}</h5>
    </div>
    <div class="card-body asset-info p-0">
      <div class="table-responsive">
        <table class="table table-sm table-striped table-hover mb-0">
          <tbody>
            <tr>
              <td class="fw-bold text-primary"><i class="bi bi-buildings me-2 text-secondary"></i>Make</td>
              <td class="text-dark">${asset.Make}</td>
            </tr>
            <tr>
              <td class="fw-bold text-success"><i class="bi bi-cpu me-2 text-secondary"></i>Model</td>
              <td class="text-dark">${asset.Model}</td>
            </tr>
            <tr>
              <td class="fw-bold text-danger"><i class="bi bi-upc-scan me-2 text-secondary"></i>Serial No</td>
              <td class="text-dark">${asset.SNo}</td>
            </tr>
            <tr>
              <td class="fw-bold text-warning"><i class="bi bi-speedometer2 me-2 text-secondary"></i>Capacity</td>
              <td class="text-dark">${asset.Capacity}</td>
            </tr>
            <tr>
              <td class="fw-bold text-info"><i class="bi bi-geo-alt me-2 text-secondary"></i>Location</td>
              <td class="text-dark">${asset.EquipmentLocation}</td>
            </tr>
            <tr>
              <td class="fw-bold text-secondary"><i class="bi bi-card-text me-2 text-secondary"></i>Description</td>
              <td class="text-dark">${asset.Description}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
`;


      // populate hidden fields
      document.getElementById("BranchID").value = asset.BranchID;
      document.getElementById("BranchAssetID").value = asset.ID;
      document.getElementById("CreatedBy").value = asset.CreatedBy || "system";
    }
  })
  .catch(err => {
    console.error("Fetch asset error:", err);
    document.getElementById('assetBox').innerHTML = `
      <div class="card-body text-center text-danger">
        <i class="bi bi-x-circle display-4 mb-3"></i>
        <h5>Failed to load asset details</h5>
        <p>Please try again later.</p>
      </div>
    `;
  });



// Handle ticket form
document.getElementById('ticketForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const form = e.target;
  const submitBtn = form.querySelector('button[type="submit"]');
  submitBtn.disabled = true; // disable button to prevent double click
  submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span> Submitting...`;
  try {
    const fd = new FormData(form);
    const r = await fetch('./api/raiseQrTicket.php', { method: 'POST', body: fd });
    if (!r.ok) throw new Error(`HTTP error ${r.status}`);
    const resp = await r.json();
    showToast(resp.message || "Ticket generated successfully ✅");
    form.reset();
    bootstrap.Modal.getInstance(document.getElementById('ticketModal')).hide();

  } catch (err) {
    console.error("Ticket error:", err);
    showToast("⚠️ Failed to generate ticket. Please try again.");
  } finally {
    submitBtn.disabled = false;
    submitBtn.innerHTML = `<i class="bi bi-check2-circle me-2"></i> Submit`;
  }
});

// Toast helper
function showToast(msg) {
  const toastEl = document.getElementById('toast');
  toastEl.querySelector('.toast-body').textContent = msg;
  new bootstrap.Toast(toastEl).show();
}
</script>
</body>
</html>
