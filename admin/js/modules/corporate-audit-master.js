var masterAuditTable = null;

function updateMasterAuditFilterSummary() {
    var parts = [];
    var status = $('#filter_audit_status').val();
    if (status === '1') {
        parts.push('Active');
    } else if (status === '0') {
        parts.push('Inactive');
    }
    caUpdateFilterSummary('#ca_master_audit_filter_summary', parts);
}

function initMasterAuditPage() {
    caBindFilterCanvasUi();
    caBindModalSelect2Ui('#master_audit_modal');
    initMasterAuditTable();
    updateMasterAuditFilterSummary();
}

function initMasterAuditTable() {
    $("#nav_corporate_audit").addClass("active open");
    $("#nav_corporate_audit_master").addClass("active");

    masterAuditTable = $('#view-master-audit-data').DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        ordering: false,
        serverMethod: 'post',
        ajax: {
            url: 'ajax/master-audit-list-post.php',
            data: function (d) {
                d.filter_status = $('#filter_audit_status').val() || '-1';
            }
        },
        columns: [
            {
                data: 'id',
                render: function (data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            { data: 'Icon' },
            { data: 'AuditName' },
            { data: 'IconClass' },
            { data: 'SortOrder' },
            { data: 'IsActive' },
            { data: 'Action' }
        ]
    });
}

function reloadMasterAuditTable() {
    updateMasterAuditFilterSummary();
    masterAuditTable.ajax.reload();
}

function applyMasterAuditFilters() {
    caCloseFilterCanvas();
    reloadMasterAuditTable();
}

function resetMasterAuditFilters() {
    caResetFilterFields(['filter_audit_status']);
    updateMasterAuditFilterSummary();
}

function openMasterAuditModal() {
    $('#master_audit_modal_title').text('Add Master Audit');
    $('#master_audit_form')[0].reset();
    $('#form_action').val('add');
    $('#form_id').val('');
    $('#icon_preview').html('');
    $('#master_audit_modal').modal('show');
}

function editMasterAudit(id) {
    $.post('action/get_master_audit_details.php', { ID: id }, function (data) {
        var res = JSON.parse(data);
        if (res.error) {
            alertify.alert('TechXpert', res.message);
            return;
        }
        var d = res.data;
        $('#master_audit_modal_title').text('Edit Master Audit');
        $('#audit_name').val(d.AuditName);
        $('#audit_description').val(d.AuditDescription);
        $('#icon_class').val(d.IconClass);
        $('#sort_order').val(d.SortOrder);
        $('#is_active').val(d.IsActive).trigger('change');
        $('#form_action').val('edit');
        $('#form_id').val(d.ID);
        if (d.IconImage) {
            $('#icon_preview').html('<img src="../media/corporate-audit/' + d.IconImage + '" style="max-height:48px;">');
        } else if (d.IconClass) {
            $('#icon_preview').html('<i class="' + d.IconClass + ' fa-2x"></i>');
        } else {
            $('#icon_preview').html('');
        }
        $('#master_audit_modal').modal('show');
    });
}

function saveMasterAudit() {
    if ($.trim($('#audit_name').val()) === '') {
        alertify.alert('TechXpert', 'Audit name is required.');
        return;
    }
    var formData = new FormData(document.getElementById('master_audit_form'));
    $.ajax({
        url: 'action/add_update_master_audit.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (data) {
            var res = JSON.parse(data);
            alertify.alert('TechXpert', res.message);
            if (!res.error) {
                $('#master_audit_modal').modal('hide');
                masterAuditTable.ajax.reload(null, false);
            }
        }
    });
}

function deleteMasterAudit(id) {
    alertify.confirm('TechXpert', 'Deactivate this master audit?', function () {
        $.post('action/delete_master_audit.php', { ID: id }, function (data) {
            var res = JSON.parse(data);
            alertify.alert('TechXpert', res.message);
            if (!res.error) {
                masterAuditTable.ajax.reload(null, false);
            }
        });
    }, function () {});
}
