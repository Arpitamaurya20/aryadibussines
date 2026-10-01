var billingTable;
var ticketStatusChart;
var billingStatusChart;
var selectedTickets = {}; // Store selected ticket IDs

// Initialize charts
function initializeCharts() {
    // Ticket Status Chart
    var ticketCtx = document.getElementById('ticketStatusChart').getContext('2d');
    if (ticketStatusChart) {
        ticketStatusChart.destroy();
    }
    ticketStatusChart = new Chart(ticketCtx, {
        type: 'bar',
        data: {
            labels: ['Raised', 'Assigned', 'Closed'],
            datasets: [{
                label: 'Ticket Count',
                data: [0, 0, 0],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.6)',
                    'rgba(54, 162, 235, 0.6)',
                    'rgba(75, 192, 192, 0.6)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(75, 192, 192, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true
                    }
                }]
            },
            legend: {
                display: false
            }
        }
    });
    
    // Billing Status Chart
    var billingCtx = document.getElementById('billingStatusChart').getContext('2d');
    if (billingStatusChart) {
        billingStatusChart.destroy();
    }
    billingStatusChart = new Chart(billingCtx, {
        type: 'doughnut',
        data: {
            labels: ['Billed', 'Unbilled'],
            datasets: [{
                data: [0, 0],
                backgroundColor: [
                    'rgba(40, 167, 69, 0.6)',
                    'rgba(255, 193, 7, 0.6)'
                ],
                borderColor: [
                    'rgba(40, 167, 69, 1)',
                    'rgba(255, 193, 7, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

// Load branches based on company selection
function loadBranches() {
    var CompanyID = $('#filter_company').val();
    $('#filter_branch').html('<option value="">All Branches</option>');
    
    if (CompanyID) {
        $.ajax({
            url: '../PPMBilling/ajax/get_branches_list.php',
            type: 'GET',
            data: { CompanyID: CompanyID },
            dataType: 'json',
            success: function(response) {
                if (response.error == false && response.data) {
                    $.each(response.data, function(index, branch) {
                        $('#filter_branch').append('<option value="' + branch.ID + '">' + branch.BranchSite + '</option>');
                    });
                }
            }
        });
    }
}

// Load billing data
function loadBillingData() {
    var CompanyID = $('#filter_company').val() || '';
    var BranchID = $('#filter_branch').val() || '';
    var dateRange = $('#date_range').val();
    var TicketIDSearch = $('#search_ticket_id').val() || '';
    
    // Get current month start and end dates
    var today = new Date();
    var firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    var lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    
    var StartDate = firstDay.toISOString().split('T')[0];
    var EndDate = lastDay.toISOString().split('T')[0];
    
    // Use moment if available, otherwise use native Date
    if (typeof moment !== 'undefined') {
        StartDate = moment().startOf('month').format('YYYY-MM-DD');
        EndDate = moment().endOf('month').format('YYYY-MM-DD');
    }
    
    if (dateRange && dateRange.trim() !== '') {
        var dates = dateRange.split(' - ');
        if (dates.length === 2) {
            var startDateStr = dates[0].trim();
            var endDateStr = dates[1].trim();
            
            // Try to parse with moment if available
            if (typeof moment !== 'undefined') {
                var startMoment = moment(startDateStr, ['YYYY-MM-DD', 'MM/DD/YYYY', 'DD/MM/YYYY'], true);
                var endMoment = moment(endDateStr, ['YYYY-MM-DD', 'MM/DD/YYYY', 'DD/MM/YYYY'], true);
                
                if (startMoment.isValid()) {
                    StartDate = startMoment.format('YYYY-MM-DD');
                } else {
                    StartDate = startDateStr;
                }
                
                if (endMoment.isValid()) {
                    EndDate = endMoment.format('YYYY-MM-DD');
                } else {
                    EndDate = endDateStr;
                }
            } else {
                // Fallback: try to parse date string
                StartDate = startDateStr;
                EndDate = endDateStr;
            }
        }
    }
    
    // Validate date format (YYYY-MM-DD)
    var dateRegex = /^\d{4}-\d{2}-\d{2}$/;
    if (!dateRegex.test(StartDate)) {
        console.warn('Invalid StartDate format:', StartDate, 'Using default');
        var today = new Date();
        var firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        StartDate = firstDay.toISOString().split('T')[0];
    }
    if (!dateRegex.test(EndDate)) {
        console.warn('Invalid EndDate format:', EndDate, 'Using default');
        var today = new Date();
        var lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        EndDate = lastDay.toISOString().split('T')[0];
    }
    
    console.log('Loading billing data:', {CompanyID, BranchID, StartDate, EndDate, dateRange: dateRange, TicketIDSearch: TicketIDSearch});
    
    // Show loading indicator
    var tbody = $('#billing_tbody');
    tbody.html('<tr><td colspan="15" class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading billing data...</td></tr>');
    
    // Load statistics (skip if searching by TicketID)
    if (!TicketIDSearch) {
        loadBillingStatistics(CompanyID, BranchID, StartDate, EndDate);
    }
    
    // Load tickets
    loadBillingTickets(CompanyID, BranchID, StartDate, EndDate, TicketIDSearch);
}

// Load billing statistics
function loadBillingStatistics(CompanyID, BranchID, StartDate, EndDate) {
    $.ajax({
        url: '../PPMBilling/action/get_billing_statistics.php',
        type: 'GET',
        data: {
            CorporateID: CompanyID || -1,
            BranchID: BranchID || -1,
            StartDate: StartDate,
            EndDate: EndDate
        },
        dataType: 'json',
        success: function(response) {
            console.log('Statistics Response:', response);
            if (response.error == false && response.data) {
                var stats = response.data;
                
                // Update statistics cards - Amount in big text, ticket count in small text
                $('#stat_raised_amount').text('₹' + (stats.ticket_amounts.Raised || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                $('#stat_raised').text((stats.ticket_status.Raised || 0) + ' tickets');
                $('#stat_assigned_amount').text('₹' + (stats.ticket_amounts.Assigned || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                $('#stat_assigned').text((stats.ticket_status.Assigned || 0) + ' tickets');
                $('#stat_closed_amount').text('₹' + (stats.ticket_amounts.Closed || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                $('#stat_closed').text((stats.ticket_status.Closed || 0) + ' tickets');
                $('#stat_billed_amount').text('₹' + (stats.billing_amounts.Billed || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                $('#stat_billed').text((stats.billing_status.Billed || 0) + ' tickets');
                $('#stat_unbilled_amount').text('₹' + (stats.billing_amounts.Unbilled || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                $('#stat_unbilled').text((stats.billing_status.Unbilled || 0) + ' tickets');
                $('#stat_pending_amount').text('₹' + (stats.payment_amounts.Pending || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                $('#stat_pending').text((stats.payment_status.Pending || 0) + ' tickets');
                
                // Update charts
                if (ticketStatusChart) {
                    ticketStatusChart.data.datasets[0].data = [
                        stats.ticket_status.Raised || 0,
                        stats.ticket_status.Assigned || 0,
                        stats.ticket_status.Closed || 0
                    ];
                    ticketStatusChart.update();
                }
                
                if (billingStatusChart) {
                    billingStatusChart.data.datasets[0].data = [
                        stats.billing_status.Billed || 0,
                        stats.billing_status.Unbilled || 0
                    ];
                    billingStatusChart.update();
                }
            } else {
                console.error('Statistics Error:', response.message || 'Unknown error');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error loading statistics:', error);
            console.error('Response:', xhr.responseText);
        }
    });
}

// Load billing tickets
function loadBillingTickets(CompanyID, BranchID, StartDate, EndDate, TicketIDSearch) {
    // Convert empty strings to -1 for proper filtering
    var CorporateID = (CompanyID && CompanyID !== '') ? parseInt(CompanyID) : -1;
    var BranchIDValue = (BranchID && BranchID !== '') ? parseInt(BranchID) : -1;
    
    var ajaxData = {
        CorporateID: CorporateID,
        BranchID: BranchIDValue,
        StartDate: StartDate,
        EndDate: EndDate
    };
    
    // Add TicketID search if provided
    if (TicketIDSearch && TicketIDSearch.trim() !== '') {
        ajaxData.TicketID = TicketIDSearch.trim();
    }
    
    console.log('loadBillingTickets - Filters being sent:', ajaxData);
    
    $.ajax({
        url: '../PPMBilling/action/get_billing_tickets.php',
        type: 'GET',
        data: ajaxData,
        dataType: 'json',
        success: function(response) {
            console.log('Tickets Response:', response);
            console.log('Tickets Count:', response.count || (response.data ? response.data.length : 0));
            console.log('Filters received by server:', response.debug_info ? response.debug_info.filters_applied : 'N/A');
            
            if (response.debug_info) {
                console.log('Debug Info:', response.debug_info);
                // Show count badge and date range info
                if (response.debug_info.direct_db_count !== undefined) {
                    $('#ticket_count_badge').text('Total: ' + response.debug_info.direct_db_count + ' tickets').show();
                }
                if (response.debug_info.date_range) {
                    $('#date_range_info').text('Filtering by PPMDate: ' + response.debug_info.date_range).show();
                }
            }
            if (response.error == false && response.data) {
                var tbody = $('#billing_tbody');
                
                // Destroy DataTable first to prevent caching issues
                if ($.fn.DataTable.isDataTable('#billing_table')) {
                    billingTable.clear();
                    billingTable.destroy();
                    billingTable = null;
                }
                
                tbody.empty(); // Clear existing data
                
                console.log('Populating table with', response.data.length, 'tickets');
                console.log('Filters applied:', ajaxData);
                
                if (response.data && response.data.length > 0) {
                    $.each(response.data, function(index, item) {
                        var billingStatusClass = (item.BillingStatus == 'Billed') ? 'billing-status-billed' : 'billing-status-unbilled';
                        var paymentStatusClass = 'payment-status-' + (item.PaymentStatus || 'pending').toLowerCase();
                        
                        // Store calculated amount for this ticket
                        ticketCalculatedAmounts[item.TicketID] = parseFloat(item.CalculatedAmount || 0);
                        
                        // Handle billed amount display
                        // For Unbilled: show calculated amount (potential billing)
                        // For Billed: show actual billed amount (or calculated if billed is 0)
                        var billedAmount = 0;
                        if (item.BillingStatus == 'Billed') {
                            billedAmount = parseFloat(item.BilledAmount || 0);
                            // If billed amount is 0, show calculated amount
                            if (billedAmount == 0) {
                                billedAmount = parseFloat(item.CalculatedAmount || 0);
                            }
                        } else {
                            // For unbilled, show calculated amount
                            billedAmount = parseFloat(item.CalculatedAmount || 0);
                        }
                        
                        var row = '<tr data-ticket-id="' + item.TicketID + '">' +
                            '<td><input type="checkbox" class="ticket-checkbox" value="' + item.TicketID + '" onchange="updateBulkButtons();"></td>' +
                            '<td>' + (item.TicketNumber || item.TicketID) + '</td>' +
                            '<td>' + (item.CompanyName || '') + '</td>' +
                            '<td>' + (item.BranchSite || '') + '</td>' +
                            '<td>' + (item.EquipmentName || '') + '</td>' +
                            '<td>' + (item.PPMDate || '') + '</td>' +
                            '<td>' + (item.TicketStatus || '') + '</td>' +
                            '<td>₹' + (parseFloat(item.AssetUnitRate || 0)).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>' +
                            '<td>₹' + (parseFloat(item.CalculatedAmount || 0)).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>' +
                            '<td><span class="billing-status-badge ' + billingStatusClass + '">' + (item.BillingStatus || 'Unbilled') + '</span></td>' +
                            '<td><span class="billing-status-badge ' + paymentStatusClass + '">' + (item.PaymentStatus || 'Pending') + '</span></td>' +
                            '<td>₹' + billedAmount.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>' +
                            '<td>' + (item.BillingNumber || '-') + '</td>' +
                            '<td>' + (item.BilledDate || '-') + '</td>' +
                            '<td>' +
                            '<button class="btn btn-sm btn-info" onclick="openBillingDetailsModal(' + item.TicketID + ', \'' + (item.BillingStatus || 'Unbilled').replace(/'/g, "\\'") + '\', \'' + (item.PaymentStatus || 'Pending').replace(/'/g, "\\'") + '\', ' + (item.BilledAmount || 0) + ', \'' + (item.BillingNumber || '').replace(/'/g, "\\'") + '\', \'' + (item.BilledDate || '') + '\', \'' + StartDate + '\', \'' + EndDate + '\', \'' + (item.Remarks || '').replace(/'/g, "\\'") + '\');" title="Edit Billing"><i class="fa fa-edit"></i></button> ' +
                            '<button class="btn btn-sm btn-primary" onclick="toggleBillingStatus(' + item.TicketID + ', \'' + (item.BillingStatus || 'Unbilled').replace(/'/g, "\\'") + '\', \'' + StartDate + '\', \'' + EndDate + '\');" title="Toggle Billing Status"><i class="fa fa-toggle-' + ((item.BillingStatus == 'Billed') ? 'on' : 'off') + '"></i></button>' +
                            '</td>' +
                            '</tr>';
                        tbody.append(row);
                    });
                    
                    // Destroy existing DataTable completely before reinitializing
                    if ($.fn.DataTable.isDataTable('#billing_table')) {
                        billingTable.clear();
                        billingTable.destroy();
                        billingTable = null;
                    }
                    
                    // Small delay to ensure DOM is ready
                    setTimeout(function() {
                        billingTable = $('#billing_table').DataTable({
                            "pageLength": 25,
                            "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                            "order": [[5, "desc"]], // Sort by PPM Date (column index changed due to checkbox)
                            "columnDefs": [
                                { "orderable": false, "targets": [0, 14] } // Disable sorting on checkbox and Action columns
                            ],
                            "destroy": true, // Allow reinitialization
                            "retrieve": false // Don't retrieve existing instance
                        });
                        
                        console.log('DataTable initialized with', billingTable.rows().count(), 'rows');
                    }, 100);
                    
                    // Attach checkbox change event
                    $(document).off('change', '.ticket-checkbox').on('change', '.ticket-checkbox', function() {
                        updateBulkButtons();
                    });
                } else {
                    var noDataMsg = 'No billing records found for the selected filters';
                    if (response.debug) {
                        console.log('No data found. Debug info:', response.debug);
                        if (response.debug.total_tickets_in_range !== undefined) {
                            noDataMsg += ' (Total tickets in date range: ' + response.debug.total_tickets_in_range + ')';
                        }
                    }
                    tbody.append('<tr><td colspan="15" class="text-center">' + noDataMsg + '</td></tr>');
                }
                } else {
                console.error('Tickets Error:', response.message || 'Unknown error');
                console.error('Debug Info:', response.debug || 'No debug info');
                var tbody = $('#billing_tbody');
                tbody.empty();
                var errorMsg = response.message || 'Unknown error';
                if (response.debug) {
                    console.log('Debug Details:', response.debug);
                    if (response.debug.total_tickets_2025 !== undefined) {
                        errorMsg += ' (Total 2025 tickets: ' + response.debug.total_tickets_2025 + ')';
                    }
                    if (response.debug.available_date_range) {
                        errorMsg += ' (Available range: ' + response.debug.available_date_range.min_date + ' to ' + response.debug.available_date_range.max_date + ')';
                    }
                }
                tbody.append('<tr><td colspan="15" class="text-center text-danger">Error loading data: ' + errorMsg + '</td></tr>');
            }
            
            // Reset selections
            selectedTickets = {};
            updateBulkButtons();
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error loading tickets:', error);
            console.error('Response:', xhr.responseText);
        }
    });
}

// Toggle billing status
function toggleBillingStatus(TicketID, CurrentStatus, StartDate, EndDate) {
    if (confirm('Are you sure you want to toggle billing status?')) {
        $.ajax({
            url: '../PPMBilling/action/toggle_billing_status.php',
            type: 'POST',
            data: {
                TicketID: TicketID,
                CurrentBillingStatus: CurrentStatus,
                BillingStartDate: StartDate,
                BillingEndDate: EndDate
            },
            dataType: 'json',
            success: function(response) {
                if (response.error == false) {
                    alert(response.message);
                    loadBillingData();
                } else {
                    alert('Error: ' + response.message);
                }
            }
        });
    }
}

// Store calculated amounts for each ticket
var ticketCalculatedAmounts = {};

// Open billing details modal
function openBillingDetailsModal(TicketID, BillingStatus, PaymentStatus, BilledAmount, BillingNumber, BilledDate, StartDate, EndDate, Remarks) {
    // Get calculated amount - try from stored data or from the table
    var calculatedAmount = ticketCalculatedAmounts[TicketID] || 0;
    
    // If not found in stored data, try to get from table
    if (calculatedAmount == 0 && $.fn.DataTable.isDataTable('#billing_table')) {
        var table = $('#billing_table').DataTable();
        table.rows().every(function(rowIdx, tableLoop, rowLoop) {
            var data = this.data();
            // Check if this row matches the ticket ID
            var rowTicketID = data[0] || '';
            if (rowTicketID.toString().includes(TicketID.toString()) || TicketID.toString().includes(rowTicketID.toString())) {
                // Extract calculated amount from column 7 (index 7)
                if (data[7]) {
                    var calcAmountText = data[7].toString();
                    calculatedAmount = parseFloat(calcAmountText.replace(/[₹,]/g, '')) || 0;
                    ticketCalculatedAmounts[TicketID] = calculatedAmount;
                    return false; // Stop iteration
                }
            }
        });
    }
    
    // If still 0, use BilledAmount as fallback
    if (calculatedAmount == 0) {
        calculatedAmount = parseFloat(BilledAmount) || 0;
    }
    
    $('#modal_ticket_id').val(TicketID);
    $('#modal_billing_status').val(BillingStatus || 'Unbilled');
    $('#modal_payment_status').val(PaymentStatus || 'Pending');
    $('#modal_calculated_amount').val('₹' + calculatedAmount.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    
    // Set billed amount: if status is Billed and amount is 0, use calculated amount
    var billedAmt = parseFloat(BilledAmount) || 0;
    if (BillingStatus == 'Billed' && billedAmt == 0) {
        billedAmt = calculatedAmount;
    }
    $('#modal_billed_amount').val(billedAmt);
    
    $('#modal_billing_number').val(BillingNumber || '');
    $('#modal_billed_date').val(BilledDate || '');
    $('#modal_billing_start_date').val(StartDate);
    $('#modal_billing_end_date').val(EndDate);
    $('#modal_remarks').val(Remarks || '');
    $('#modal_send_zoho').prop('checked', false);
    
    // Handle billing status change
    handleBillingStatusChange();
    
    $('#billingDetailsModal').modal('show');
}

// Handle billing status change
function handleBillingStatusChange() {
    var billingStatus = $('#modal_billing_status').val();
    var calculatedAmount = parseFloat($('#modal_calculated_amount').val().replace(/[₹,]/g, '')) || 0;
    var currentBilledAmount = parseFloat($('#modal_billed_amount').val()) || 0;
    
    if (billingStatus == 'Billed') {
        // If billed amount is 0 or empty, set it to calculated amount
        if (currentBilledAmount == 0 || !$('#modal_billed_amount').val()) {
            $('#modal_billed_amount').val(calculatedAmount);
        }
        $('#modal_billing_number').attr('placeholder', 'Enter billing/invoice number');
    } else {
        $('#modal_billing_number').attr('placeholder', 'Enter billing number');
    }
}

// Save billing details
function saveBillingDetails() {
    var formData = $('#billing_details_form').serializeArray();
    var data = {};
    
    $.each(formData, function(i, field) {
        data[field.name] = field.value;
    });
    
    $.ajax({
        url: '../PPMBilling/action/update_billing_details.php',
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function(response) {
            if (response.error == false) {
                alert(response.message || 'Billing details updated successfully');
                $('#billingDetailsModal').modal('hide');
                loadBillingData();
            } else {
                alert('Error: ' + response.message);
            }
        }
    });
}

// Reset filters
function resetFilters() {
    $('#filter_company').val('').trigger('change');
    $('#filter_branch').html('<option value="">All Branches</option>').trigger('change');
    $('#date_range').val('');
    $('#search_ticket_id').val('');
    
    // Reset date range picker if it exists
    if ($('#date_range').data('daterangepicker')) {
        if (typeof moment !== 'undefined') {
            $('#date_range').data('daterangepicker').setStartDate(moment().startOf('month'));
            $('#date_range').data('daterangepicker').setEndDate(moment().endOf('month'));
        } else {
            var today = new Date();
            var firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            var lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            $('#date_range').data('daterangepicker').setStartDate(firstDay);
            $('#date_range').data('daterangepicker').setEndDate(lastDay);
        }
    }
    loadBillingData();
}

// Toggle select all
function toggleSelectAll(checkbox) {
    var isChecked = checkbox.checked;
    $('.ticket-checkbox').each(function() {
        this.checked = isChecked;
        var ticketID = $(this).val();
        if (isChecked) {
            selectedTickets[ticketID] = true;
        } else {
            delete selectedTickets[ticketID];
        }
    });
    updateBulkButtons();
}

// Update bulk action buttons state
function updateBulkButtons() {
    var checkedCount = $('.ticket-checkbox:checked').length;
    if (checkedCount > 0) {
        $('#bulk_bill_btn').prop('disabled', false);
        $('#bulk_unbill_btn').prop('disabled', false);
        $('#bulk_bill_btn').text('Mark ' + checkedCount + ' as Billed');
        $('#bulk_unbill_btn').text('Mark ' + checkedCount + ' as Unbilled');
    } else {
        $('#bulk_bill_btn').prop('disabled', true);
        $('#bulk_unbill_btn').prop('disabled', true);
        $('#bulk_bill_btn').text('Mark Selected as Billed');
        $('#bulk_unbill_btn').text('Mark Selected as Unbilled');
    }
    
    // Update selectedTickets object
    selectedTickets = {};
    $('.ticket-checkbox:checked').each(function() {
        selectedTickets[$(this).val()] = true;
    });
}

// Clear all selections
function clearAllSelections() {
    $('.ticket-checkbox').prop('checked', false);
    $('#select_all_tickets').prop('checked', false);
    selectedTickets = {};
    updateBulkButtons();
}

// Bulk billing action
function bulkBillingActionold(action) {
    var selectedIDs = [];
    $('.ticket-checkbox:checked').each(function() {
        selectedIDs.push($(this).val());
    });
    
    if (selectedIDs.length === 0) {
        alert('Please select at least one ticket');
        return;
    }
    
    var actionText = action == 'Billed' ? 'mark as Billed' : 'mark as Unbilled';
    if (!confirm('Are you sure you want to ' + actionText + ' ' + selectedIDs.length + ' ticket(s)?')) {
        return;
    }
    
    // Get date range
    var dateRange = $('#date_range').val();
    var StartDate = '';
    var EndDate = '';
    
    if (dateRange && dateRange.trim() !== '') {
        var dates = dateRange.split(' - ');
        if (dates.length === 2) {
            StartDate = dates[0].trim();
            EndDate = dates[1].trim();
        }
    } else {
        var today = new Date();
        var firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        var lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        StartDate = firstDay.toISOString().split('T')[0];
        EndDate = lastDay.toISOString().split('T')[0];
    }
    
    // Show loading
    $('#bulk_bill_btn, #bulk_unbill_btn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');
    
    $.ajax({
        url: '../PPMBilling/action/bulk_billing_action.php',
        type: 'POST',
        data: {
            TicketIDs: selectedIDs,
            BillingStatus: action,
            BillingStartDate: StartDate,
            BillingEndDate: EndDate
        },
        dataType: 'json',
        success: function(response) {
            if (response.error == false) {
                alert(response.message || 'Bulk billing action completed successfully');
                clearAllSelections();
                loadBillingData();
            } else {
                alert('Error: ' + (response.message || 'Unknown error occurred'));
            }
            updateBulkButtons();
        },
        error: function(xhr, status, error) {
            console.error('Bulk billing error:', error);
            alert('Error occurred while processing bulk billing action');
            updateBulkButtons();
        }
    });
}

   function bulkBillingAction(action) {

    var selectedIDs = [];
    var selectedRows = [];

    $('.ticket-checkbox:checked').each(function () {

        var checkbox = $(this);
        var row = checkbox.closest('tr');

        selectedIDs.push(checkbox.val());

        // Get Ticket ID (column 1)
        var ticket = row.find('td:eq(1)').text().trim();

        // Get Calculated Amount (column 8)
        var amountText = row.find('td:eq(8)').text().trim();

        // Remove ₹ and commas
        amountText = amountText.replace(/₹|,/g, '');

        var amount = parseFloat(amountText) || 0;

        selectedRows.push({
            ticket: ticket,
            amount: amount
        });
    });

    if (selectedIDs.length === 0) {
        alert('Please select at least one ticket');
        return;
    }

    if (action !== 'Billed') {
        alert('Only Billed action opens invoice modal');
        return;
    }

    window.bulkSelectedTickets = selectedIDs;
    window.bulkSelectedRows = selectedRows;

    renderInvoicePreview();
    $('#billingDetailsModal').modal('show');
}

// Export billing data to CSV
function exportBillingToCSV() {
    var CompanyID = $('#filter_company').val() || '';
    var BranchID = $('#filter_branch').val() || '';
    var dateRange = $('#date_range').val();
    
    // Get current month start and end dates as default
    var today = new Date();
    var firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    var lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    
    var StartDate = firstDay.toISOString().split('T')[0];
    var EndDate = lastDay.toISOString().split('T')[0];
    
    // Use moment if available, otherwise use native Date
    if (typeof moment !== 'undefined') {
        StartDate = moment().startOf('month').format('YYYY-MM-DD');
        EndDate = moment().endOf('month').format('YYYY-MM-DD');
    }
    
    if (dateRange && dateRange.trim() !== '') {
        var dates = dateRange.split(' - ');
        if (dates.length === 2) {
            var startDateStr = dates[0].trim();
            var endDateStr = dates[1].trim();
            
            // Try to parse with moment if available
            if (typeof moment !== 'undefined') {
                var startMoment = moment(startDateStr, ['YYYY-MM-DD', 'MM/DD/YYYY', 'DD/MM/YYYY'], true);
                var endMoment = moment(endDateStr, ['YYYY-MM-DD', 'MM/DD/YYYY', 'DD/MM/YYYY'], true);
                
                if (startMoment.isValid()) {
                    StartDate = startMoment.format('YYYY-MM-DD');
                } else {
                    StartDate = startDateStr;
                }
                
                if (endMoment.isValid()) {
                    EndDate = endMoment.format('YYYY-MM-DD');
                } else {
                    EndDate = endDateStr;
                }
            } else {
                StartDate = startDateStr;
                EndDate = endDateStr;
            }
        }
    }
    
    // Validate date format (YYYY-MM-DD)
    var dateRegex = /^\d{4}-\d{2}-\d{2}$/;
    if (!dateRegex.test(StartDate)) {
        var today = new Date();
        var firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        StartDate = firstDay.toISOString().split('T')[0];
    }
    if (!dateRegex.test(EndDate)) {
        var today = new Date();
        var lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        EndDate = lastDay.toISOString().split('T')[0];
    }
    
    // Build export URL with filters
    var exportUrl = '../PPMBilling/action/export_billing_csv.php?';
    exportUrl += 'CorporateID=' + (CompanyID || -1);
    exportUrl += '&BranchID=' + (BranchID || -1);
    exportUrl += '&StartDate=' + StartDate;
    exportUrl += '&EndDate=' + EndDate;
    
    // Open in new window to trigger download
    window.open(exportUrl, '_blank');
}

// Initialize on page load
$(document).ready(function() {
    initializeCharts();
});


function renderInvoicePreview() {

    if (!window.bulkSelectedRows || window.bulkSelectedRows.length === 0) {
        $('#invoiceTickets').html('<p>No tickets selected</p>');
        return;
    }

    var html = '<table class="table table-bordered">';
    html += '<thead><tr><th>#</th><th>Ticket</th><th>Amount</th></tr></thead><tbody>';

    var total = 0;

    window.bulkSelectedRows.forEach(function (row, index) {

        total += row.amount;

        html += '<tr>';
        html += '<td>' + (index + 1) + '</td>';
        html += '<td>' + row.ticket + '</td>';
        html += '<td>₹ ' + row.amount.toFixed(2) + '</td>';
        html += '</tr>';
    });

    html += '</tbody></table>';

    $('#invoiceTickets').html(html);
    $('#invoiceTotalAmount').text(total.toFixed(2));
}


function saveBillingDetails() {

    if (!window.bulkSelectedTickets || window.bulkSelectedTickets.length === 0) {
        alert("No tickets selected");
        return;
    }

    var formData = $('#bulkBillingForm').serializeArray();
    var dataObj = {};

    formData.forEach(function (item) {
        dataObj[item.name] = item.value;
    });

    dataObj.TicketIDs = window.bulkSelectedTickets;
    dataObj.BillingStatus = 'Billed';
    $.ajax({
        url: 'action/save_bulk_invoice.php',
        type: 'POST',
        data: dataObj,
        dataType: 'json',
        success: function (response) {

            if (!response.error) {
                alert(response.message);
                $('#billingDetailsModal').modal('hide');
                clearAllSelections();
                loadBillingData();
            } else {
                alert(response.message);
            }
        }
    });
}