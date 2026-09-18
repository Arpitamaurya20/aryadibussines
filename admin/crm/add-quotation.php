<?php 
session_start();
include('../controllers/common_controllers.php');
include('controller/crm_controller.php');
$UserType = SessionCheck();
setNavigation($_SESSION['Roles']);
$conn = _connectodb();

// Get leads and products for dropdowns
$leads = getAllLeads($conn);
$products = getAllProducts($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Create Quotation</title>
    <?php include('../includes/common_head_content.php'); ?>
    <style>
        .item-row input, .item-row select { font-size: 13px; }
        .totals-row { font-weight: bold; font-size: 15px; }
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
                        <li class="breadcrumb-item"><a href="view-quotations.php">Quotations</a></li>
                        <li class="breadcrumb-item active">Create New</li>
                    </ol>
                    
                    <form action="action/submit_quotation.php" method="POST">
                        <div class="row">
                            <div class="col-xl-12">
                                <div class="panel">
                                    <div class="panel-hdr">
                                        <h2>Quotation Details</h2>
                                    </div>
                                    <div class="panel-container show">
                                        <div class="panel-content">
                                            <div class="row form-group">
                                                <div class="col-md-4">
                                                    <label class="form-label">Select Lead / Customer <span class="text-danger">*</span></label>
                                                    <select name="LeadID" class="form-control select2" required>
                                                        <option value="">-- Select --</option>
                                                        <?php foreach($leads as $l): ?>
                                                            <option value="<?= $l['ID'] ?>"><?= htmlspecialchars($l['LeadName']) ?> (<?= htmlspecialchars($l['CompanyName']) ?>)</option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Quotation Date <span class="text-danger">*</span></label>
                                                    <input type="date" name="QuoteDate" class="form-control" required value="<?= date('Y-m-d') ?>">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Valid Until</label>
                                                    <input type="date" name="ValidUntil" class="form-control" value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="panel">
                                    <div class="panel-hdr">
                                        <h2>Products & Services</h2>
                                    </div>
                                    <div class="panel-container show">
                                        <div class="panel-content">
                                            <table class="table table-bordered" id="itemsTable">
                                                <thead class="bg-primary-50">
                                                    <tr>
                                                        <th width="30%">Product / Service</th>
                                                        <th width="20%">Description</th>
                                                        <th width="10%">Qty</th>
                                                        <th width="12%">Unit Price</th>
                                                        <th width="10%">GST %</th>
                                                        <th width="12%">Total (Incl GST)</th>
                                                        <th width="6%">Act</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="itemsBody">
                                                    <!-- Dynamic rows -->
                                                </tbody>
                                            </table>
                                            <button type="button" class="btn btn-sm btn-info" onclick="addRow()">+ Add Item</button>
                                            
                                            <hr>
                                            <div class="row justify-content-end">
                                                <div class="col-md-4">
                                                    <table class="table table-clean">
                                                        <tr>
                                                            <td>Sub Total (Excl. Tax)</td>
                                                            <td class="text-right">
                                                                <input type="number" step="0.01" name="SubTotal" id="subTotal" class="form-control form-control-sm text-right" readonly>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>Discount</td>
                                                            <td class="text-right">
                                                                <input type="number" step="0.01" name="Discount" id="discount" class="form-control form-control-sm text-right" value="0" onkeyup="calculateTotals()">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>Total GST</td>
                                                            <td class="text-right">
                                                                <input type="number" step="0.01" name="TotalTax" id="totalTax" class="form-control form-control-sm text-right" readonly>
                                                            </td>
                                                        </tr>
                                                        <tr class="totals-row">
                                                            <td>Grand Total</td>
                                                            <td class="text-right">
                                                                <input type="number" step="0.01" name="GrandTotal" id="grandTotal" class="form-control form-control-sm text-right bg-primary-100" readonly>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="panel-content border-top bg-light text-right">
                                            <a href="view-quotations.php" class="btn btn-secondary mr-2">Cancel</a>
                                            <button type="submit" class="btn btn-primary">Save Quotation</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </main>
                <?php include('../includes/common_footer.php'); ?>
            </div>
        </div>
    </div>

    <?php 
        include('../includes/common_modules.php');
        include('../includes/common_scripts.php'); 
    ?>
    <script>
        // Pass products to JS
        const products = <?= json_encode($products) ?>;
        
        let rowIdx = 0;
        
        function addRow() {
            let options = '<option value="">- Select -</option>';
            products.forEach(p => {
                options += `<option value="${p.ID}" data-price="${p.UnitPrice}" data-gst="${p.GST_Percent}">${p.ProductName}</option>`;
            });
            
            const tr = `
            <tr id="row_${rowIdx}" class="item-row">
                <td>
                    <select name="items[${rowIdx}][ProductID]" class="form-control product-select" onchange="onProductSelect(${rowIdx}, this)" required>
                        ${options}
                    </select>
                </td>
                <td><input type="text" name="items[${rowIdx}][Description]" class="form-control"></td>
                <td><input type="number" step="0.01" name="items[${rowIdx}][Quantity]" id="qty_${rowIdx}" class="form-control qty" value="1" onkeyup="calcRow(${rowIdx})" onchange="calcRow(${rowIdx})" required></td>
                <td><input type="number" step="0.01" name="items[${rowIdx}][UnitPrice]" id="price_${rowIdx}" class="form-control price" onkeyup="calcRow(${rowIdx})" onchange="calcRow(${rowIdx})" required></td>
                <td><input type="number" step="0.01" name="items[${rowIdx}][GST_Percent]" id="gst_${rowIdx}" class="form-control gst" onkeyup="calcRow(${rowIdx})" onchange="calcRow(${rowIdx})" required></td>
                <td><input type="number" step="0.01" name="items[${rowIdx}][TotalAmount]" id="total_${rowIdx}" class="form-control row-total" readonly></td>
                <td><button type="button" class="btn btn-sm btn-danger btn-icon" onclick="removeRow(${rowIdx})"><i class="fal fa-trash"></i></button></td>
            </tr>`;
            
            $('#itemsBody').append(tr);
            rowIdx++;
        }
        
        function onProductSelect(idx, el) {
            const selected = $(el).find('option:selected');
            if(selected.val()) {
                $('#price_'+idx).val(selected.data('price'));
                $('#gst_'+idx).val(selected.data('gst'));
                calcRow(idx);
            }
        }
        
        function calcRow(idx) {
            const qty = parseFloat($('#qty_'+idx).val()) || 0;
            const price = parseFloat($('#price_'+idx).val()) || 0;
            const gst = parseFloat($('#gst_'+idx).val()) || 0;
            
            const baseAmt = qty * price;
            const gstAmt = baseAmt * (gst / 100);
            const total = baseAmt + gstAmt;
            
            $('#total_'+idx).val(total.toFixed(2));
            calculateTotals();
        }
        
        function removeRow(idx) {
            $('#row_'+idx).remove();
            calculateTotals();
        }
        
        function calculateTotals() {
            let subTotal = 0;
            let totalTax = 0;
            
            $('.item-row').each(function() {
                const idx = $(this).attr('id').split('_')[1];
                const qty = parseFloat($('#qty_'+idx).val()) || 0;
                const price = parseFloat($('#price_'+idx).val()) || 0;
                const gst = parseFloat($('#gst_'+idx).val()) || 0;
                
                const baseAmt = qty * price;
                const gstAmt = baseAmt * (gst / 100);
                
                subTotal += baseAmt;
                totalTax += gstAmt;
            });
            
            const discount = parseFloat($('#discount').val()) || 0;
            
            $('#subTotal').val(subTotal.toFixed(2));
            $('#totalTax').val(totalTax.toFixed(2));
            $('#grandTotal').val((subTotal + totalTax - discount).toFixed(2));
        }
        
        // Add first row by default
        $(document).ready(function() {
            addRow();
        });
    </script>
</body>
</html>
