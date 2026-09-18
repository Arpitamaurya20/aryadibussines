var employeeConvenienceTable = null;

$(document).ready(function () {
    $("#_Nav_Employee_Convenience").addClass("active open");
});

function buildEmployeeConvenienceParams() {
    var ticket = document.getElementById('ticket_filter').value || -1;
    return '?filter_date=' + encodeURIComponent(document.getElementById('filter_date').value)
        + '&EmployeeID=' + encodeURIComponent(document.getElementById('employee_filter').value)
        + '&status=' + encodeURIComponent(document.getElementById('c_status').value || '')
        + '&state=' + encodeURIComponent(document.getElementById('state_filter').value)
        + '&department=' + encodeURIComponent(document.getElementById('department_filter').value)
        + '&designation=' + encodeURIComponent(document.getElementById('designation_filter').value)
        + '&ticket_id=' + encodeURIComponent(ticket)
        + '&employee_number=' + encodeURIComponent(document.getElementById('employee_number_filter').value);
}

function initEmployeeConvenienceTable(param) {
    var selector = '#view-employees-convenience';
    if ($.fn.DataTable.isDataTable(selector)) {
        $(selector).DataTable().ajax.url('ajax/view-employees-convenience-post.php' + param).load(null, false);
        return;
    }
    employeeConvenienceTable = $(selector).DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        ordering: false,
        serverMethod: 'post',
        ajax: { url: 'ajax/view-employees-convenience-post.php' + param },
        columnDefs: [{ targets: [0], className: 'text-center' }],
        columns: [
            { data: null, render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
            { data: 'Employee' },
            { data: 'EmployeeID' },
            { data: 'Ticket' },
            { data: 'Journey' },
            { data: 'Amount' },
            { data: 'Remarks' },
            { data: 'RecordDate' },
            { data: 'State' },
            { data: 'Department' },
            { data: 'Designation' },
            { data: 'Status' },
            { data: 'PaymentStatus' },
            { data: 'ApprovalInfo' }
        ]
    });
}

function FilterEmployeeConvenienceData() {
    initEmployeeConvenienceTable(buildEmployeeConvenienceParams());
}

function ExportEmployeeConvenienceData() {
    document.getElementById('filter_date_export').value = document.getElementById('filter_date').value;
    document.getElementById('employee_filter_export').value = document.getElementById('employee_filter').value;
    document.getElementById('status_export').value = document.getElementById('c_status').value;
    document.getElementById('state_export').value = document.getElementById('state_filter').value;
    document.getElementById('department_export').value = document.getElementById('department_filter').value;
    document.getElementById('designation_export').value = document.getElementById('designation_filter').value;
    document.getElementById('ticket_export').value = document.getElementById('ticket_filter').value || -1;
    document.getElementById('employee_number_export').value = document.getElementById('employee_number_filter').value;
    $.ajax({
        url: 'action/export_employee_convenience_data.php',
        type: 'POST',
        data: $('#export_form').serialize(),
        success: function () {
            window.location.href = 'report.xls';
        }
    });
    return false;
}
