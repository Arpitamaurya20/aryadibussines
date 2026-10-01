
function GenerateTodayAttendanceStats()
{
    $.ajax({
        url: "action/today_attendance_stats.php",
        type: "POST",
        data: 
            {
            },
        success: function (data) 
        {
           document.getElementById("today_attendance_stats").innerHTML = data;
        },
    });
}

function formatAttendanceListDateYMD(date) {
    var y = date.getFullYear();
    var m = String(date.getMonth() + 1).padStart(2, '0');
    var d = String(date.getDate()).padStart(2, '0');
    return y + '-' + m + '-' + d;
}

function initAttendanceListMultiSelectFilters() {
    $('.attendance-list-multi-filter').each(function () {
        var $el = $(this);
        if ($el.data('select2')) {
            return;
        }
        $el.select2({
            placeholder: $el.data('placeholder') || 'All',
            allowClear: true,
            width: '100%',
            dropdownParent: $(document.body)
        });
    });
}

function syncAttendanceListFilterSelect2Ui() {
    $('.attendance-list-multi-filter').each(function () {
        var $el = $(this);
        if ($el.data('select2')) {
            $el.trigger('change.select2');
        }
    });
}

function captureAttendanceListFilters() {
    return {
        employee: $('#employee_filter').length ? ($('#employee_filter').val() || []) : [],
        status: $('#status_filter').length ? ($('#status_filter').val() || []) : [],
        state: $('#state_filter').length ? ($('#state_filter').val() || []) : [],
        department: $('#department_filter').length ? ($('#department_filter').val() || []) : [],
        designation: $('#designation_filter').length ? ($('#designation_filter').val() || []) : [],
        filter_date: document.getElementById('filter_date').value,
        employee_number: document.getElementById('employee_number_filter') ? document.getElementById('employee_number_filter').value : ''
    };
}

function restoreAttendanceListFilters(filterState) {
    if (!filterState) {
        return;
    }
    if ($('#employee_filter').length) {
        $('#employee_filter').val(filterState.employee.length ? filterState.employee : null).trigger('change');
    }
    if ($('#status_filter').length) {
        $('#status_filter').val(filterState.status.length ? filterState.status : null).trigger('change');
    }
    if ($('#state_filter').length) {
        $('#state_filter').val(filterState.state.length ? filterState.state : null).trigger('change');
    }
    if ($('#department_filter').length) {
        $('#department_filter').val(filterState.department.length ? filterState.department : null).trigger('change');
    }
    if ($('#designation_filter').length) {
        $('#designation_filter').val(filterState.designation.length ? filterState.designation : null).trigger('change');
    }
    document.getElementById('filter_date').value = filterState.filter_date || '';
    if (document.getElementById('employee_number_filter')) {
        document.getElementById('employee_number_filter').value = filterState.employee_number || '';
    }
    syncAttendanceListFilterSelect2Ui();
}

function encodeAttendanceListMultiFilterValue(values) {
    if (!values || values.length === 0) {
        return '-1';
    }
    return Array.isArray(values) ? values.join(',') : values;
}

function buildAttendanceListFilterParam(filterState) {
    var state = filterState || captureAttendanceListFilters();
    var filter_date = state.filter_date === 'All Time' ? 'all' : state.filter_date;
    return '?filter_date=' + encodeURIComponent(filter_date)
        + '&EmployeeID=' + encodeURIComponent(encodeAttendanceListMultiFilterValue(state.employee))
        + '&ApprovalStatus=' + encodeURIComponent(encodeAttendanceListMultiFilterValue(state.status))
        + '&state=' + encodeURIComponent(encodeAttendanceListMultiFilterValue(state.state))
        + '&department=' + encodeURIComponent(encodeAttendanceListMultiFilterValue(state.department))
        + '&designation=' + encodeURIComponent(encodeAttendanceListMultiFilterValue(state.designation))
        + '&employee_number=' + encodeURIComponent(state.employee_number || '');
}

function getAttendanceListTableColumns() {
    return [
        { data: 'EmployeeName' },
        { data: 'RecordDate' },
        { data: 'CheckInTime' },
        { data: 'CheckOutTime' },
        { data: 'Duration' },
        { data: 'ApprovalStatus' },
        { data: 'ApprovalInfo' },
        { data: 'State' }
    ];
}

function initAttendanceListTable(filterParam) {
    var selector = '#view-attendance-records';
    if (!$(selector).length) {
        return;
    }
    if ($.fn.DataTable.isDataTable(selector)) {
        $(selector).DataTable().destroy();
    }
    $(selector).dataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        ordering: false,
        serverMethod: 'post',
        ajax: {
            url: 'action/view-attendance-list-post.php' + filterParam
        },
        columnDefs: [{
            targets: [0],
            className: 'text-center'
        }],
        columns: getAttendanceListTableColumns()
    });
}

function initAttendanceListPage(filterParam) {
    initAttendanceListMultiSelectFilters();
    $('#filter_date').daterangepicker({
        locale: {
            format: 'YYYY-MM-DD'
        }
    });
    initAttendanceListTable(filterParam);
    GenerateTodayAttendanceStats();
}

function RefreshAttendanceList(filterDateValue) {
    var selector = '#view-attendance-records';
    if (!$(selector).length) {
        return;
    }
    var filterState = captureAttendanceListFilters();
    if (filterDateValue !== undefined) {
        filterState.filter_date = filterDateValue === 'all' ? 'All Time' : filterDateValue;
        document.getElementById('filter_date').value = filterState.filter_date;
    }
    var param = buildAttendanceListFilterParam(filterState);
    if ($.fn.DataTable.isDataTable(selector)) {
        $(selector).DataTable().ajax.url('action/view-attendance-list-post.php' + param).load(function () {
            restoreAttendanceListFilters(filterState);
        }, false);
    } else {
        initAttendanceListTable(param);
    }
}

function setAttendanceListQuickFilter(type) {
    var today = new Date();
    var start;
    if (type === '7days') {
        start = new Date(today);
        start.setDate(start.getDate() - 7);
        RefreshAttendanceList(formatAttendanceListDateYMD(start) + ' - ' + formatAttendanceListDateYMD(today));
        return;
    }
    if (type === '30days') {
        start = new Date(today);
        start.setDate(start.getDate() - 30);
        RefreshAttendanceList(formatAttendanceListDateYMD(start) + ' - ' + formatAttendanceListDateYMD(today));
        return;
    }
    if (type === '365days') {
        start = new Date(today);
        start.setDate(start.getDate() - 365);
        RefreshAttendanceList(formatAttendanceListDateYMD(start) + ' - ' + formatAttendanceListDateYMD(today));
        return;
    }
    if (type === 'all') {
        RefreshAttendanceList('all');
    }
}

function RefreshAttendance()
{
    if ($('#view-attendance-records').length && $('#employee_filter').length) {
        RefreshAttendanceList();
        return;
    }

    var table = $('#view-attendance-records').DataTable();
    table.destroy();
    var EmployeeID = document.getElementById("employee_name").value;
    var filter_date = document.getElementById("filter_date").value;
    var param = "?filter_date=" + encodeURIComponent(filter_date) + "&EmployeeID=" + encodeURIComponent(EmployeeID);
    $('#view-attendance-records').dataTable({
            responsive: true,
            'processing': true,
            'serverSide': true,
            'ordering': false,
            'serverMethod': 'post',
            'ajax': {
                'url': 'action/view-attendance-list-post.php' + param
            },
            'columnDefs': [{
                "targets": [0],
                "className": "text-center"
            }],
            'columns': getAttendanceListTableColumns()
            });
}

function DeleteAttendance(id) {
    alertify.confirm('TechXpert ', 'Do you really want to delete Attendance', function() {
            $.post("action/delete_attendance.php", {
                    ID: id
                },
                function(data, status) {
                    var response = JSON.parse(data);
                    TechXAlert(response.message);
                    if (response.error == false)
                    {
                      setInterval(function(){
                        location.reload();
                      }, 2000);
                    }
                });
        },
        function() {
            alertify.error('Deletion Cancelled')
        });
}
function AddAttendance() {

    document.getElementById("submit").innerHTML ="Submiting....";
    $.ajax({
        url: "action/add_attendance.php",
        type: "POST",
        data: $("#add_attendance_form").serialize(),
        success: function(data) {
            var response = JSON.parse(data);
            TechXAlert(response.message);
            if (response.error == false)
            {
              setInterval(function(){
                location.reload();
              }, 2000);
            }
        },

    });
    return false;
}

function UpdateAttendance(ID,Hours) {
alertify.confirm(
"TechXpert Are you sure you want to complete today work",
function (e) {
    $.post(
      "action/update_attendance.php",
      {
        ID: ID,
        Hours: Hours,
      },
      function (data, status) {
        var response = JSON.parse(data);
        TechXAlert(response.message);
        if (response.error == false)
          setInterval(function () {
            location.reload();
          }, 2000);
      }
    );
  }
);
return false;
}

function submitAttendanceListExport(actionUrl, employeeIdOverride, includeApprovalStatus, extraFields)
{
    var filterState = captureAttendanceListFilters();
    var filter_date = filterState.filter_date === 'All Time' ? 'all' : filterState.filter_date;
    var employeeId = encodeAttendanceListMultiFilterValue(filterState.employee);
    if (employeeIdOverride !== undefined && employeeIdOverride !== null && employeeIdOverride !== '' && employeeIdOverride !== 'N.A.') {
        employeeId = employeeIdOverride;
    } else if (employeeId === '-1' && document.getElementById('employee_name')) {
        employeeId = document.getElementById('employee_name').value || '-1';
    }
    if ((!filter_date || filter_date === '') && document.getElementById('filter_date')) {
        filter_date = document.getElementById('filter_date').value;
    }

    var form = document.createElement('form');
    form.method = 'POST';
    form.action = actionUrl;
    form.style.display = 'none';
    if (extraFields && extraFields.target) {
        form.target = extraFields.target;
    }

    var fields = {
        EmployeeID: employeeId,
        filter_date: filter_date,
        state: encodeAttendanceListMultiFilterValue(filterState.state),
        department: encodeAttendanceListMultiFilterValue(filterState.department),
        designation: encodeAttendanceListMultiFilterValue(filterState.designation),
        employee_number: filterState.employee_number || ''
    };

    if (includeApprovalStatus) {
        fields.ApprovalStatus = encodeAttendanceListMultiFilterValue(filterState.status);
    }

    if (extraFields) {
        Object.keys(extraFields).forEach(function (key) {
            if (key !== 'target') {
                fields[key] = extraFields[key];
            }
        });
    }

    Object.keys(fields).forEach(function (key) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = fields[key];
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
    return false;
}

function ExportAttendanceData(employeeIdOverride)
{
    return submitAttendanceListExport('action/export_attendance.php', employeeIdOverride, true);
}

function ExportAttendanceRegister(employeeIdOverride)
{
    return submitAttendanceListExport('action/export_attendance_register.php', employeeIdOverride, false);
}

function PreviewAttendanceRegister(employeeIdOverride)
{
    return submitAttendanceListExport(
        'action/export_attendance_register.php',
        employeeIdOverride,
        false,
        { preview: '1', target: '_blank' }
    );
}


function ViewAttendanceList(EmployeeID) {
  $.post(
    "../controllers/setSession.php",
    {
      EmployeeID: EmployeeID,
    },
    function (data, status) {
      BasicURLRouter("view_employee_total_attendance.php");
    }
  );
}

function ViewEmployeeDetail(EmployeeID) {
  $.post(
    "../controllers/setSession.php",
    {
      EmployeeID: EmployeeID,
    },
    function (data, status) {
      BasicURLRouter("../employees/view_employee.php");
    }
  );
}
