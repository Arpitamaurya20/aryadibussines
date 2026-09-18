<?php

$EmployeeID = isset($ID) ? intval($ID) : 0;

$FinanceAccess = 0;
$stateAccess   = [];

/* =====================================================
   1️⃣ FETCH FINANCE ACCESS
===================================================== */
$finance_query = "
    SELECT 1
    FROM user_quation_access
    WHERE EmployeeID = '$EmployeeID'
    AND IsApprovedFinance = 1
    AND IsActive = 1
    LIMIT 1
";

$finance_check = $conn->query($finance_query);

if($finance_check && $finance_check->num_rows > 0){
    $FinanceAccess = 1;
}


/* =====================================================
   2️⃣ FETCH STATE ACCESS
===================================================== */
$state_query = "
    SELECT StateName
    FROM user_quation_access
    WHERE EmployeeID = '$EmployeeID'
    AND IsApprovedState = 1
    AND IsActive = 1
    AND StateName != 'ALL'
";

$access_result = $conn->query($state_query);

if($access_result){
    while($row = $access_result->fetch_assoc()){
        $stateAccess[] = strtoupper(trim($row['StateName']));
    }
}

?>

<div class="profile-grid-premium">
    <div class="profile-card-premium" style="grid-column: 1 / -1;">
        <div class="profile-card-hdr">
            <i class="fal fa-file-invoice-dollar"></i>
            <h3>Employee Quotation Access</h3>
        </div>

        <div class="mt-3">

        <form id="save_other_access">

            <input type="hidden" name="EmployeeID" value="<?= $EmployeeID; ?>">

            <!-- ================================
                 STATE APPROVAL MAIN
            ================================= -->
            <div class="form-check mb-3">
                <input class="form-check-input"
                       type="checkbox"
                       id="StateApprovalMain"
                       name="StateApprovalMain"
                       value="1"
                       <?= count($stateAccess) > 0 ? 'checked' : ''; ?>>

                <label class="form-check-label profile-lbl" style="font-size: 13px;">
                    Enable State Approval
                </label>
            </div>

            <!-- ================================
                 STATE LIST
            ================================= -->
            <div id="stateListDiv" class="mb-4"
                 style="<?= count($stateAccess) > 0 ? '' : 'display:none;' ?>">

                <div class="table-responsive" style="max-height: 250px; overflow-y: auto; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <table class="table table-hover mb-0" style="border: none;">
                        <thead style="background: #f8fafc; position: sticky; top: 0;">
                            <tr>
                                <th style="border-bottom: none; font-size: 12px; color: #94a3b8; text-transform: uppercase;">State Name</th>
                                <th style="border-bottom: none; font-size: 12px; color: #94a3b8; text-transform: uppercase;">Select</th>
                            </tr>
                        </thead>
                        <tbody>

                        <?php
                        $states = getAllStates($conn); // your predefined function

                        foreach($states as $state){

                            $StateNameOriginal = trim($state['StateName']);
                            $StateNameCompare  = strtoupper($StateNameOriginal);
                        ?>
                            <tr>
                                <td style="font-weight: 500; color: #f8fafc;"><?= $StateNameOriginal; ?></td>
                                <td>
                                    <input type="checkbox"
                                           name="StateName[]"
                                           value="<?= $StateNameOriginal; ?>"
                                           <?= in_array($StateNameCompare, $stateAccess) ? 'checked' : ''; ?>>
                                </td>
                            </tr>
                        <?php } ?>

                        </tbody>
                    </table>
                </div>
            </div>

            <hr style="border-color: #f1f5f9; margin: 24px 0;">

            <!-- ================================
                 FINANCE APPROVAL
            ================================= -->
            <div class="form-check mb-4">
                <input class="form-check-input"
                       type="checkbox"
                       id="FinanceApproval"
                       name="FinanceApproval"
                       value="1"
                       <?= $FinanceAccess ? 'checked' : ''; ?>>

                <label class="form-check-label profile-lbl" style="font-size: 13px;">
                    Enable Finance Approval (All States)
                </label>
            </div>

            <div id="accessMessage"></div>

            <button type="submit" class="btn-premium w-100 justify-content-center" id="saveBtn">
                <i class="fal fa-save"></i> Save Access
            </button>

        </form>

        </div>
    </div>
</div>


<script>
/* =====================================================
   TOGGLE STATE LIST
===================================================== */
document.getElementById("StateApprovalMain")
.addEventListener("change", function() {

    document.getElementById("stateListDiv").style.display =
        this.checked ? "block" : "none";

    if(!this.checked){
        document.querySelectorAll("input[name='StateName[]']")
        .forEach(cb => cb.checked = false);
    }
});


/* =====================================================
   OPTIONAL: Finance overrides state
===================================================== */
document.getElementById("FinanceApproval")
.addEventListener("change", function() {

    if(this.checked){
        document.getElementById("StateApprovalMain").checked = false;
        document.getElementById("stateListDiv").style.display = "none";

        document.querySelectorAll("input[name='StateName[]']")
        .forEach(cb => cb.checked = false);
    }
});


/* =====================================================
   AJAX SUBMIT
===================================================== */
document.getElementById("save_other_access")
.addEventListener("submit", function(e) {

    e.preventDefault();

    let saveBtn = document.getElementById("saveBtn");
    saveBtn.disabled = true;
    saveBtn.innerText = "Saving...";

    let formData = new FormData(this);

    fetch("action/save_employee_other_access.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {

        if(data.status === "success"){
            document.getElementById("accessMessage").innerHTML =
                '<div class="alert alert-success">'+data.message+'</div>';
        } else {
            document.getElementById("accessMessage").innerHTML =
                '<div class="alert alert-danger">'+data.message+'</div>';
        }

        saveBtn.disabled = false;
        saveBtn.innerText = "Save Access";
    })
    .catch(error => {

        document.getElementById("accessMessage").innerHTML =
            '<div class="alert alert-danger">Something went wrong</div>';

        saveBtn.disabled = false;
        saveBtn.innerText = "Save Access";
    });
});
</script>
