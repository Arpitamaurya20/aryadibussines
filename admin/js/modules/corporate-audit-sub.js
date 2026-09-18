var masterSubAuditTable = null;

function updateSubAuditFilterSummary() {
    var parts = [];
    var masterText = $('#filter_master_audit_id option:selected').text();
    if ($('#filter_master_audit_id').val() !== '0' && masterText) {
        parts.push(masterText);
    }
    var status = $('#filter_sub_audit_status').val();
    if (status === '1') {
        parts.push('Active');
    } else if (status === '0') {
        parts.push('Inactive');
    }
    caUpdateFilterSummary('#ca_sub_audit_filter_summary', parts);
}

function initMasterSubAuditPage() {
    caBindFilterCanvasUi();
    caBindModalSelect2Ui('#master_sub_audit_modal');
    initMasterSubAuditTable();
    updateSubAuditFilterSummary();
}

function initMasterSubAuditTable() {
    $("#nav_corporate_audit").addClass("active open");
    $("#nav_corporate_audit_sub").addClass("active");

    masterSubAuditTable = $('#view-master-sub-audit-data').DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        ordering: false,
        serverMethod: 'post',
        ajax: {
            url: 'ajax/master-sub-audit-list-post.php',
            data: function (d) {
                d.filter_master_audit_id = $('#filter_master_audit_id').val() || '0';
                d.filter_status = $('#filter_sub_audit_status').val() || '-1';
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
            { data: 'MasterAudit' },
            { data: 'SubAuditName' },
            { data: 'SortOrder' },
            { data: 'IsActive' },
            { data: 'Action' }
        ]
    });
}

function reloadSubAuditTable() {
    updateSubAuditFilterSummary();
    masterSubAuditTable.ajax.reload();
}

function applySubAuditFilters() {
    caCloseFilterCanvas();
    reloadSubAuditTable();
}

function resetSubAuditFilters() {
    caResetFilterFields(['filter_master_audit_id', 'filter_sub_audit_status']);
    updateSubAuditFilterSummary();
}

function openMasterSubAuditModal() {
    $('#master_sub_audit_modal_title').text('Add Sub Audit');
    $('#master_sub_audit_form')[0].reset();
    $('#sub_form_action').val('add');
    $('#sub_form_id').val('');
    $('#sub_icon_preview').html('');
    var filterId = $('#filter_master_audit_id').val();
    if (filterId && filterId !== '0') {
        $('#master_audit_id').val(filterId).trigger('change');
    }
    $('#master_sub_audit_modal').modal('show');
}

function editMasterSubAudit(id) {
    $.post('action/get_master_sub_audit_details.php', { ID: id }, function (data) {
        var res = JSON.parse(data);
        if (res.error) {
            alertify.alert('TechXpert', res.message);
            return;
        }
        var d = res.data;
        $('#master_sub_audit_modal_title').text('Edit Sub Audit');
        $('#master_audit_id').val(d.MasterAuditID).trigger('change');
        $('#sub_audit_name').val(d.SubAuditName);
        $('#sub_audit_description').val(d.SubAuditDescription);
        $('#sub_icon_class').val(d.IconClass);
        $('#sub_sort_order').val(d.SortOrder);
        $('#sub_is_active').val(d.IsActive).trigger('change');
        $('#sub_form_action').val('edit');
        $('#sub_form_id').val(d.ID);
        if (d.IconImage) {
            $('#sub_icon_preview').html('<img src="../media/corporate-audit/' + d.IconImage + '" style="max-height:48px;">');
        } else if (d.IconClass) {
            $('#sub_icon_preview').html('<i class="' + d.IconClass + ' fa-2x"></i>');
        }
        $('#master_sub_audit_modal').modal('show');
    });
}

function saveMasterSubAudit() {
    if ($('#master_audit_id').val() === '0' || $.trim($('#sub_audit_name').val()) === '') {
        alertify.alert('TechXpert', 'Master audit and sub audit name are required.');
        return;
    }
    var formData = new FormData(document.getElementById('master_sub_audit_form'));
    $.ajax({
        url: 'action/add_update_master_sub_audit.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (data) {
            var res = JSON.parse(data);
            alertify.alert('TechXpert', res.message);
            if (!res.error) {
                $('#master_sub_audit_modal').modal('hide');
                masterSubAuditTable.ajax.reload(null, false);
            }
        }
    });
}

function deleteMasterSubAudit(id) {
    alertify.confirm('TechXpert', 'Deactivate this sub audit?', function () {
        $.post('action/delete_master_sub_audit.php', { ID: id }, function (data) {
            var res = JSON.parse(data);
            alertify.alert('TechXpert', res.message);
            if (!res.error) {
                masterSubAuditTable.ajax.reload(null, false);
            }
        });
    }, function () {});
}
