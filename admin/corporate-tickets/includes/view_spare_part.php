<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Spare Part Requests Dashboard</title>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <link rel="stylesheet" href="../js/datagrid/datatables/datatables.bundle.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    .card {
      border: none;
      border-radius: 10px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .card h5 {
      font-weight: 600;
    }
    .metric-value {
      font-size: 1.5rem;
      font-weight: bold;
    }
    .good-health {
      color: #28a745;
    }
    .poor-health {
      color: #dc3545;
    }
    .table thead {
      background-color: #027dc1;
      color: #fff;
    }
    .total-cost {
      font-weight: bold;
      background-color: #003f88;
      color: #fff;
    }
    .dashboard-container {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
    }
    .left-panel {
      flex: 2;
    }
    .right-panel {
      flex: 1;
    }
    .right-panel .table thead {
      background-color: #198754;
      color: white;
    }
    .action-btn {
      cursor: pointer;
      color: #007bff;
    }
    .action-btn:hover {
      color: #0056b3;
    }

  /* 🔹 Small Compact Cards */
.small-card {
    border: none;
    border-radius: 12px;
    padding: 14px 18px;
    background: #ffffff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    text-align: center;
    min-width: 160px;
}

.small-card h5 {
    font-size: 14px;
    margin-bottom: 6px;
    font-weight: 600;
}

.small-card .metric-value {
    font-size: 18px;  /* smaller numbers */
    font-weight: 700;
}

/* Compact colored backgrounds */
.small-blue  { background: linear-gradient(135deg,#007bff,#0056d6); color:white; }
.small-orange{ background: linear-gradient(135deg,#fd7e14,#cc5a02); color:white; }
.small-green { background: linear-gradient(135deg,#28a745,#1f7a38); color:white; }
.small-red   { background: linear-gradient(135deg,#dc3545,#a71d2a); color:white; }

  </style>
</head>
<body class="p-4">
<div class="card p-4 mt-2">
    <h4 class="text-center">Branch Financial Comparison</h4>
    <canvas id="branchGraph" height="120"></canvas>
</div>


<div class="dashboard-container mb-3 mt-4">

    <div class="small-card small-blue flex-fill">
        <h5>Total Branch Cost</h5>
        <div class="metric-value" id="branch-asset-total">₹ 0.00</div>
    </div>

    <div class="small-card small-orange flex-fill">
        <h5>Branch Spending Till</h5>
        <div class="metric-value" id="branch-spending-total">₹ 0.00</div>
    </div>


    <div class="small-card small-green flex-fill">
        <h5>Total Asset Cost</h5>
        <div class="metric-value" id="total-asset">₹ 0.00</div>
    </div>

    <div class="small-card small-red flex-fill">
        <h5>Total Assets Spending Cost</h5>
        <div class="metric-value" id="total-spending">₹ 0.00</div>
    </div>

</div>




  <div class="dashboard-container">
    <!-- Left: Main Table -->
    <div class="left-panel">
     <div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Requested Spare Parts</h5>

   <div class="d-flex gap-2">

    <!-- City Lead → Verify Button -->
    <?php if ($CityLead == 1 || $UserType == "Admin" ||  $TicketManager || $Branch_Account_Manager) { ?>  
        <button class="btn btn-primary btn-sm" id="verifyBtn" style="margin-right:20px;">
            Verify Items
        </button>
    <?php } ?>

    <!-- Finance Manager → Approve / Reject Buttons -->
    <?php if ($Finance_Manager || $UserType == "Admin" ||  $TicketManager) { ?>
        <button class="btn btn-success btn-sm" id="approveBtn" style="display:none; margin-right:20px;">
            Approve All
        </button>

        <button class="btn btn-danger btn-sm" id="rejectBtn" style="display:none;">
            Reject All
        </button>
    <?php } ?>

</div>


</div>

  <div class="card-body">
      <table class="table table-bordered">
          <thead>
              <tr>
                  <th>#</th>
                  <th>Spare Part</th>
                  <th>Qty</th>
                  <th>Uom</th>
                  <th>Price (₹)</th>
                  <th>Total (₹)</th>
                  <th>Action</th>
              </tr>
          </thead>
          <tbody id="cartItemsBody"></tbody>

          <tfoot>
              <tr>
                  <th colspan="5" class="text-end">Grand Total</th>
                  <th id="cartTotal">0</th>
              </tr>
          </tfoot>
      </table>
  </div>
</div>

    </div>

    <!-- Right: History Panel -->
    <div class="right-panel">
    <div class="card">
      <div class="card-header">
        <h5>All Carts</h5>
      </div>
      <div class="card-body">
        <table id="cart-history" class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>Cart ID</th>
              <th>Date</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
</div>
       <input type="hidden" id="BranchAssetsID" value="<?php echo $BranchAssetsID['BranchAssetID']; ?>">
<input type="hidden" id="CartID" value="<?php echo $cartID; ?>">
 
<input type="hidden" id="AMCTicketID" value="<?php echo $ID; ?>">
  </div>

  <!-- Modal: History Details -->
  <div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="historyModalLabel">Spending Details - <span id="modal-date"></span></h5>
          <button type="button" class="btn-close btn-close-white" data-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <table class="table table-bordered table-striped" id="history-details-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Spare Part</th>
                <th>QTY</th>
                <th>UOM</th>
                <th>Price (₹)</th>
                <th>Total Ammount (₹)</th>
                <th>Approved By</th>
                <th>Approved Date</th>
                
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Approve All Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="approveModalLabel">Approve Spare Parts</h5>
        <button type="button" class="btn-close btn-close-white" data-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="approveCartID" value="<?php echo $cartID; ?>">
        <div class="mb-3">
          <label for="approveRemarks" class="form-label">Remarks</label>
          <textarea class="form-control" id="approveRemarks" rows="3" placeholder="Enter remarks..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success" id="confirmApproveBtn">Approve</button>
      </div>
    </div>
  </div>
</div>


  <!-- JS -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <script src="../js/jquery-3.6.0.min.js"></script>
  <script src="../js/bootstrap.bundle.min.js"></script>
  <script src="../js/datagrid/datatables/datatables.bundle.js"></script>

  <script>
  let BranchAssetsID = document.getElementById("BranchAssetsID").value;
  let CartID = document.getElementById("CartID").value;
  let AMCTicketID = document.getElementById("AMCTicketID").value;
  $(document).ready(function () {

    loadAssetCost(BranchAssetsID);
    loadDashboardMetrics(BranchAssetsID);
   
    if(CartID !== "N.A.") {
        loadCartItems(CartID);
    }

   function loadCartItems(CartID) {
    $.ajax({
        url: "https://techxpertindia.in/api/get_spare_part_cart.php",
        method: "POST",
        dataType: "json",
        contentType: "application/json",
        data: JSON.stringify({ CartID: CartID }),
        success: function(res) {

            if(res.error) {
                $("#cartItemsBody").html(`<tr><td colspan="6" class="text-center">No Pending Request</td></tr>`);
                return;
            }

            $("#approveBtn").show().attr("data-cartid", res.Cart.CartID);
            $("#rejectBtn").show().attr("data-cartid", res.Cart.CartID);

            let rows = "";
            let total = 0;
            let count = 1;

            res.Items.forEach(item => {
               rows += `
              <tr data-itemid="${item.ID}">
                  <td>${count++}</td>
                  <td>${item.SparePart}</td>

                  <td><input type="number" class="form-control qty-input" value="${item.Qty}" min="1"></td>

                  <td>${item.UOM || ''}</td>

                  <td><input type="number" class="form-control price-input" value="${item.Price}" min="0"></td>

                  <td class="total-cell">${item.TotalAmount}</td>

                  <td>
                      <button class="btn btn-warning btn-sm save-item">Save</button>
                  </td>
              </tr>
              `;

                total += parseFloat(item.TotalAmount || 0);
            });

            $("#cartItemsBody").html(rows);
            $("#cartTotal").text(total);

            // Disable edit if Verified / Approved
              if(res.Cart.Status === "Verified") {
                  $("#verifyBtn").hide();
                  $(".qty-input, .price-input, .save-item").prop("disabled", true);
              }

              if(res.Cart.Status === "Approved") {
                  $("#verifyBtn").hide();
                  $("#approveBtn").hide();
                  $("#rejectBtn").hide();
                  $(".qty-input, .price-input, .save-item").prop("disabled", true);
              }
        }

        
    });
}


function updateGrandTotal() {
    let grandTotal = 0;
    $(".total-cell").each(function() {
        grandTotal += parseFloat($(this).text()) || 0;
    });
    $("#cartTotal").text(grandTotal.toFixed(2));
}


function updateGrandTotal() {
    let grandTotal = 0;
    $(".total-cell").each(function() {
        grandTotal += parseFloat($(this).text()) || 0;
    });
    $("#cartTotal").text(grandTotal.toFixed(2));
}


$(document).on("click", ".save-item", function () {
    let row = $(this).closest("tr");

    let ItemID = row.attr("data-itemid");
    let qty = row.find(".qty-input").val();
    let price = row.find(".price-input").val();
    let total = (qty * price).toFixed(2);

    $.ajax({
        url: "https://techxpertindia.in/api/update_spare_part_item.php",
        method: "POST",
        contentType: "application/json",
        data: JSON.stringify({
            ItemID: ItemID,
            Qty: qty,
            Price: price,
            TotalAmount: total
        }),
        success: function(res) {
            if (res.error === true) {
              alert("Error: " + res.message);
          } else {
              alert("Updated successfully!");
              location.reload();
          }
        }
    });
});





});
   function loadDashboardMetrics(AssetID = 1) {
    $.get('ajax/get_spare_part_summary.php', { AssetID: AssetID }, function (res) {

        console.log("API response:", res);
        let asset = parseFloat(res.total_asset) || 0;
        let spending = parseFloat(res.total_spending) || 0;

        $('#total-asset').text('₹ ' + asset.toFixed(2));
        $('#total-spending').text('₹ ' + spending.toFixed(2));

        let healthElement = $('#system-health');

        if (asset === 0) {
            healthElement.text('N/A');
            return;
        }

        let percent = ((spending / asset) * 100).toFixed(1);

        if (spending > asset) {
            healthElement.text('Not Good (' + percent + '%)');
            healthElement.removeClass('good-health').addClass('poor-health');
        } else {
            healthElement.text('Good (' + percent + '%)');
            healthElement.removeClass('poor-health').addClass('good-health');
        }
    });
}




    function loadSpendingHistory() {
      $.get('ajax/get_spending_history.php', function (res) {
        let data = JSON.parse(res);
        let tbody = $('#spending-history tbody');
        tbody.empty();
        data.forEach(row => {
          tbody.append(`
            <tr>
              <td>${row.Date}</td>
              <td>₹ ${parseFloat(row.Total).toFixed(2)}</td>
              <td class="text-center">
                <i class="fa-solid fa-clock-rotate-left action-btn" onclick="viewHistoryDetails('${row.Date}')"></i>
              </td>
            </tr>
          `);
        });
      });
    }

    function viewHistoryDetails(date) {
      $('#modal-date').text(date);
      $('#history-details-table tbody').html('<tr><td colspan="5" class="text-center">Loading...</td></tr>');
      $('#historyModal').modal('show');

      $.get('ajax/get_spending_details_by_date.php', { date: date }, function (res) {
        let data = JSON.parse(res);
        let tbody = $('#history-details-table tbody');
        tbody.empty();

        if (data.length === 0) {
          tbody.append('<tr><td colspan="5" class="text-center">No records found</td></tr>');
          return;
        }

        data.forEach((row, i) => {
          tbody.append(`
            <tr>
              <td>${i + 1}</td>
              <td>${row.SparePart}</td>
              <td>${row.CategoriesName}</td>
              <td>${row.UOMName}</td>
              <td>₹ ${parseFloat(row.Price).toFixed(2)}</td>
            </tr>
          `);
        });
      });
    }
    function loadAssetCost(BranchAssetsID) {

    $.ajax({
        url: "ajax/get_assets_total_cost_details.php",
        type: "POST",
        data: { BranchAssetsID: BranchAssetsID },
        dataType: "json",
        success: function (response) {
               console.log('hello');
               console.log(response);
            if (response && response && response.total_asset) {
                let cost = parseFloat(response.total_asset).toFixed(2);
                $("#total-asset").text("₹ " + cost);
            } else {
                $("#total-asset").text("₹ 0.00");
            }
            if (response && response && response.total_spending) {
                let total_spending = parseFloat(response.total_spending).toFixed(2);
                $("#total-spending").text("₹ " + total_spending);
            } else {
                $("#total-spending").text("₹ 0.00");
            }
        }
    });
}


// Open approve modal
$('#approveBtn').click(function() {
    let cartID = $(this).attr("data-cartid");
    $('#approveCartID').val(cartID);  // store CartID in hidden field
    $('#approveRemarks').val("Approved for purchase."); // optional default
    $('#approveModal').modal('show');
});


$('#confirmApproveBtn').click(function() {
    let cartID = $('#approveCartID').val();
    let remarks = $('#approveRemarks').val();
    let approvedBy = "EMP001";

    

    if (!remarks.trim()) {
        alert("Please enter remarks.");
        return;
    }

    $.ajax({
        url: "https://techxpertindia.in/api/approve_spare_part_cart.php",
        method: "POST",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            CartID: cartID,
            ApprovedBy: approvedBy,
            Remarks: remarks
        }),
        success: function(res) {
            if (res.error) {
                alert("Error: " + res.message);
            } else {
                alert(res.message || "Cart approved successfully!");
                 location.reload();
                $('#approveModal').modal('hide');
                loadCartItems(cartID);       // refresh cart table
                loadDashboardMetrics();      // refresh dashboard
                loadSpendingHistory();       // refresh history
                
            }
        },
        error: function(err) {
            console.error(err);
            alert("Something went wrong while approving.");
        }
    });
});



$('#verifyBtn').click(function () {

    $.ajax({
        url: "https://techxpertindia.in/api/verify_spare_part_cart.php",
        method: "POST",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({ CartID: CartID }),

        success: function(res) {

            if(res.error) {
                alert(res.message);
                return;
            }

            alert("Verified Successfully!");

            // 🔥 HIDE VERIFY BUTTON
            $('#verifyBtn').hide();

            // 🔥 DISABLE ALL EDITING
            $('.qty-input, .price-input, .save-item').prop("disabled", true);

        }
    });

});




$('#rejectBtn').click(function () {

    if (!confirm("Are you sure you want to REJECT this entire cart? This action cannot be undone.")) {
        return; // stop if cancelled
    }

    let cartID = $(this).attr("data-cartid");

    $.ajax({
        url: "https://techxpertindia.in/api/reject_spare_part_cart.php",
        method: "POST",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            CartID: cartID
        }),
        success: function (res) {
            if (res.error) {
                alert("Error: " + res.message);
            } else {
                alert(res.message || "Cart rejected successfully!");
                location.reload();
            }
        },
        error: function (err) {
            console.error(err);
            alert("Something went wrong while rejecting.");
        }
    });
});




function loadCartHistory() {
    $.ajax({
        url: "ajax/get_all_spare_part_carts.php",
        method: "GET",
        data: { BranchAssetsID: BranchAssetsID },   // <-- IMPORTANT
        dataType: "json",
        success: function(res) {
            let tbody = $('#cart-history tbody');
            tbody.empty();

            if(res.error || !res.data || res.data.length === 0) {
                tbody.append('<tr><td colspan="3" class="text-center">No carts found</td></tr>');
                return;
            }

            res.data.forEach(cart => {
                tbody.append(`
                    <tr>
                        <td>${cart.CartID}</td>
                        <td>${cart.CreatedDate}</td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-primary" onclick="viewCartItems('${cart.CartID}')">View Items</button>
                        </td>
                    </tr>
                `);
            });
        },
        error: function(err) {
            console.error(err);
            $('#cart-history tbody').html('<tr><td colspan="3" class="text-center">Error loading carts</td></tr>');
        }
    });
}

// Show items in modal
function viewCartItems(cartID) {
    $('#modal-date').text(cartID);
    $('#history-details-table tbody').html('<tr><td colspan="8" class="text-center">Loading...</td></tr>');
    $('#historyModal').modal('show');

    $.ajax({
        url: "https://techxpertindia.in/api/get_final_spare_part_items.php",
        method: "POST",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({ CartID: cartID }),
        success: function(res) {
            let tbody = $('#history-details-table tbody');
            tbody.empty();

            if(res.error || !res.Items || res.Items.length === 0) {
                tbody.append('<tr><td colspan="8" class="text-center">No items found</td></tr>');
                return;
            }

            let grandTotal = 0;

            res.Items.forEach((item, i) => {
                let approvedDateTime = item.ApprovedDate ? `${item.ApprovedDate} ${item.ApprovedTime}` : '-';
                tbody.append(`
                    <tr>
                        <td>${i + 1}</td>
                        <td>${item.SparePart}</td>
                        <td>${item.Qty}</td>
                        <td>${item.UOM || '-'}</td>
                        <td>₹ ${parseFloat(item.FinalPrice).toFixed(2)}</td>
                        <td>₹ ${parseFloat(item.FinalTotalAmount).toFixed(2)}</td>
                        <td>${item.ApprovedBy || '-'}</td>
                        <td>${approvedDateTime}</td>
                    </tr>
                `);
                grandTotal += parseFloat(item.FinalTotalAmount);
            });

            // Add Grand Total row
            tbody.append(`
                <tr>
                    <td colspan="5" class="text-end"><strong>Grand Total</strong></td>
                    <td><strong>₹ ${grandTotal.toFixed(2)}</strong></td>
                    <td colspan="2"></td>
                </tr>
            `);
        },
        error: function(err) {
            console.error(err);
            $('#history-details-table tbody').html('<tr><td colspan="8" class="text-center">Error loading items</td></tr>');
        }
    });
}


function loadBranchTotals(AssetID) {
  
    $.get("ajax/get_branch_summary.php", { AssetID: AssetID }, function(res) {
        let data = JSON.parse(res);

        let asset = parseFloat(data.total_asset || 0).toFixed(2);
        let spending = parseFloat(data.total_spending || 0).toFixed(2);

        $('#branch-asset-total').text("₹ " + asset);
        $('#branch-spending-total').text("₹ " + spending);

        showFinancialGraph(
    Number(data.total_asset),
    Number(data.total_spending)
);

    });


}


var branchGraph;

function showFinancialGraph(asset, spending) {

    let profitLoss = asset - spending;

    let ctx = document.getElementById("branchGraph").getContext("2d");

    if (branchGraph) branchGraph.destroy();

    branchGraph = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ["Branch Total", "Total Spending", 
                     profitLoss >= 0 ? "Profit" : "Loss"],
            datasets: [{
                data: [
                    asset,
                    spending,
                    Math.abs(profitLoss)
                ],
                backgroundColor: [
                    "#007bff",     // asset
                    "#fd7e14",     // spending
                    profitLoss >= 0 ? "#28a745" : "#dc3545" // profit or loss
                ],
                borderWidth: 1
            }]
        },
        options: {
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: value => "₹ " + value.toLocaleString("en-IN")
                    }
                }
            }
        }
    });
}



// Call this on page load
$(document).ready(function(){
    loadCartHistory();
    loadBranchTotals(BranchAssetsID);

    
});

  </script>


  




</body>
</html>
