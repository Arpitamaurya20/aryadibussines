var checklistTable = null;

function updateChecklistFilterSummary() {
    var parts = [];
    var masterText = $('#filter_checklist_master_id option:selected').text();
    if ($('#filter_checklist_master_id').val() !== '0' && masterText) {
        parts.push(masterText);
    }
    var subText = $('#filter_checklist_sub_id option:selected').text();
    if ($('#filter_checklist_sub_id').val() !== '0' && subText) {
        parts.push(subText);
    }
    var fieldType = $('#filter_checklist_field_type option:selected').text();
    if ($('#filter_checklist_field_type').val()) {
        parts.push(fieldType);
    }
    var mandatory = $('#filter_checklist_mandatory').val();
    if (mandatory === '1') {
        parts.push('Mandatory');
    } else if (mandatory === '0') {
        parts.push('Optional');
    }
    var status = $('#filter_checklist_status').val();
    if (status === '1') {
        parts.push('Active');
    } else if (status === '0') {
        parts.push('Inactive');
    }
    caUpdateFilterSummary('#ca_checklist_filter_summary', parts);
}

function initChecklistPage() {
    caBindFilterCanvasUi();
    caBindModalSelect2Ui('#checklist_modal');
    caBindModalSelect2Ui('#bulk_checklist_modal');
    initChecklistTable();
    updateChecklistFilterSummary();
}

function initChecklistTable() {
    $("#nav_corporate_audit").addClass("active open");
    $("#nav_corporate_audit_checklist").addClass("active");

    checklistTable = $('#view-master-checklist-data').DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        ordering: false,
        serverMethod: 'post',
        ajax: {
            url: 'ajax/master-checklist-list-post.php',
            data: function (d) {
                d.filter_master_audit_id = $('#filter_checklist_master_id').val() || '0';
                d.filter_sub_audit_id = $('#filter_checklist_sub_id').val() || '0';
                d.filter_field_type = $('#filter_checklist_field_type').val() || '';
                d.filter_is_mandatory = $('#filter_checklist_mandatory').val() || '-1';
                d.filter_status = $('#filter_checklist_status').val() || '-1';
            }
        },
        columns: [
            {
                data: 'id',
                render: function (data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            { data: 'SubAudit' },
            { data: 'CheckpointName' },
            { data: 'FieldType' },
            { data: 'IdealValue' },
            { data: 'IsMandatory' },
            { data: 'SortOrder' },
            { data: 'IsActive' },
            { data: 'Action' }
        ]
    });
}

function reloadChecklistTable() {
    updateChecklistFilterSummary();
    checklistTable.ajax.reload();
}

function applyChecklistFilters() {
    caCloseFilterCanvas();
    reloadChecklistTable();
}

function resetChecklistFilters() {
    caResetFilterFields([
        'filter_checklist_master_id',
        'filter_checklist_sub_id',
        'filter_checklist_field_type',
        'filter_checklist_mandatory',
        'filter_checklist_status'
    ]);
    loadChecklistSubAudits(true);
    updateChecklistFilterSummary();
}

function loadChecklistSubAudits(resetValue) {
    caFilterSubAuditOptions('filter_checklist_master_id', 'filter_checklist_sub_id', resetValue);
}

function filterChecklistSubAuditsInModal(resetValue) {
    caFilterSubAuditOptions('checklist_master_audit_id', 'sub_audit_id', resetValue);
}

function toggleChecklistOptionsField() {
    if ($('#field_type').val() === 'select') {
        $('#options_json_group').show();
    } else {
        $('#options_json_group').hide();
    }
}

function openChecklistModal() {
    $('#checklist_modal_title').text('Add Checkpoint');
    $('#checklist_form')[0].reset();
    $('#checklist_form_action').val('add');
    $('#checklist_form_id').val('');
    toggleChecklistOptionsField();

    var masterId = $('#filter_checklist_master_id').val();
    if (masterId && masterId !== '0') {
        $('#checklist_master_audit_id').val(masterId).trigger('change');
    }
    var subId = $('#filter_checklist_sub_id').val();
    if (subId && subId !== '0') {
        filterChecklistSubAuditsInModal(false);
        $('#sub_audit_id').val(subId).trigger('change');
    }

    $('#checklist_modal').modal('show');
}

function editChecklist(id) {
    $.post('action/get_checklist_details.php', { ID: id }, function (data) {
        var res = JSON.parse(data);
        if (res.error) {
            alertify.alert('TechXpert', res.message);
            return;
        }
        var d = res.data;
        $('#checklist_modal_title').text('Edit Checkpoint');

        var $subOpt = $('#sub_audit_id option[value="' + d.SubAuditID + '"]');
        if ($subOpt.length) {
            $('#checklist_master_audit_id').val($subOpt.data('master')).trigger('change');
        }
        filterChecklistSubAuditsInModal(false);
        $('#sub_audit_id').val(d.SubAuditID).trigger('change');

        $('#checkpoint_name').val(d.CheckpointName);
        $('#checkpoint_description').val(d.CheckpointDescription);
        $('#field_type').val(d.FieldType).trigger('change');
        $('#ideal_value').val(d.IdealValue);
        $('#min_value').val(d.MinValue);
        $('#max_value').val(d.MaxValue);
        $('#unit').val(d.Unit);
        $('#options_json').val(d.options_list || '');
        $('#help_text').val(d.HelpText);
        $('#checklist_sort_order').val(d.SortOrder);
        $('#is_mandatory').val(d.IsMandatory).trigger('change');
        $('#checklist_is_active').val(d.IsActive).trigger('change');
        $('#checklist_form_action').val('edit');
        $('#checklist_form_id').val(d.ID);
        toggleChecklistOptionsField();
        $('#checklist_modal').modal('show');
    });
}

function saveChecklist() {
    if ($('#sub_audit_id').val() === '0' || $.trim($('#checkpoint_name').val()) === '') {
        alertify.alert('TechXpert', 'Sub audit and checkpoint name are required.');
        return;
    }
    $.ajax({
        url: 'action/add_update_checklist.php',
        type: 'POST',
        data: $('#checklist_form').serialize(),
        success: function (data) {
            var res = JSON.parse(data);
            alertify.alert('TechXpert', res.message);
            if (!res.error) {
                $('#checklist_modal').modal('hide');
                checklistTable.ajax.reload(null, false);
            }
        }
    });
}

function deleteChecklist(id) {
    alertify.confirm('TechXpert', 'Deactivate this checkpoint?', function () {
        $.post('action/delete_checklist.php', { ID: id }, function (data) {
            var res = JSON.parse(data);
            alertify.alert('TechXpert', res.message);
            if (!res.error) {
                checklistTable.ajax.reload(null, false);
            }
        });
    }, function () {});
}

// ---------- Bulk add checkpoints ----------

function getBulkFieldTypeOptionsHtml(selectedValue) {
    var html = '';
    var types = window.caChecklistFieldTypes || { text: 'Text' };
    $.each(types, function (key, label) {
        var selected = key === (selectedValue || 'text') ? ' selected' : '';
        html += '<option value="' + key + '"' + selected + '>' + label + '</option>';
    });
    return html;
}

function filterBulkChecklistSubAudits(resetValue) {
    caFilterSubAuditOptions('bulk_master_audit_id', 'bulk_sub_audit_id', resetValue);
}

function updateBulkChecklistRowNumbers() {
    $('#bulk_checklist_rows tr').each(function (index) {
        $(this).find('.bulk-row-num').text(index + 1);
        $(this).attr('data-row-index', index);
    });
    var count = $('#bulk_checklist_rows tr').length;
    $('#bulk_checklist_row_count').text(count + ' row' + (count === 1 ? '' : 's'));
}

function buildBulkChecklistRowHtml(defaults) {
    defaults = defaults || {};
    var fieldType = defaults.field_type || $('#bulk_default_field_type').val() || 'text';
    var mandatory = defaults.is_mandatory !== undefined ? defaults.is_mandatory : ($('#bulk_default_mandatory').val() || '0');
    var sortOrder = defaults.sort_order !== undefined ? defaults.sort_order : ($('#bulk_checklist_rows tr').length + 1);

    return '<tr class="bulk-checklist-row">' +
        '<td class="bulk-row-num"></td>' +
        '<td class="bulk-name-col"><textarea class="form-control bulk-checkpoint-name" rows="3" placeholder="Checkpoint name or instruction (2–3 lines)"></textarea></td>' +
        '<td class="bulk-desc-col"><textarea class="form-control bulk-checkpoint-desc" rows="2" placeholder="Optional notes"></textarea></td>' +
        '<td><select class="form-control bulk-field-type">' + getBulkFieldTypeOptionsHtml(fieldType) + '</select></td>' +
        '<td><input type="text" class="form-control bulk-ideal-value" placeholder="Ideal"></td>' +
        '<td><input type="text" class="form-control bulk-unit" placeholder="Unit"></td>' +
        '<td><input type="text" class="form-control bulk-min-value" placeholder="Min"></td>' +
        '<td><input type="text" class="form-control bulk-max-value" placeholder="Max"></td>' +
        '<td><select class="form-control bulk-mandatory">' +
            '<option value="1"' + (mandatory === '1' ? ' selected' : '') + '>Yes</option>' +
            '<option value="0"' + (mandatory === '0' ? ' selected' : '') + '>No</option>' +
        '</select></td>' +
        '<td><input type="number" class="form-control bulk-sort-order" value="' + sortOrder + '"></td>' +
        '<td class="text-center"><button type="button" class="btn btn-xs btn-danger" onclick="removeBulkChecklistRow(this);" title="Remove row"><i class="fa fa-times"></i></button></td>' +
    '</tr>';
}

function addBulkChecklistRows(count) {
    count = parseInt(count, 10) || 1;
    var defaults = {
        field_type: $('#bulk_default_field_type').val(),
        is_mandatory: $('#bulk_default_mandatory').val()
    };
    var startIndex = $('#bulk_checklist_rows tr').length;
    for (var i = 0; i < count; i++) {
        defaults.sort_order = startIndex + i + 1;
        $('#bulk_checklist_rows').append(buildBulkChecklistRowHtml(defaults));
    }
    updateBulkChecklistRowNumbers();
}

function removeBulkChecklistRow(btn) {
    $(btn).closest('tr').remove();
    if ($('#bulk_checklist_rows tr').length === 0) {
        addBulkChecklistRows(3);
    } else {
        updateBulkChecklistRowNumbers();
    }
}

function clearBulkChecklistRows() {
    $('#bulk_checklist_rows').empty();
    addBulkChecklistRows(3);
}

function collectBulkChecklistItems() {
    var items = [];
    $('#bulk_checklist_rows tr').each(function () {
        var $row = $(this);
        items.push({
            checkpoint_name: $.trim($row.find('.bulk-checkpoint-name').val()),
            checkpoint_description: $.trim($row.find('.bulk-checkpoint-desc').val()),
            field_type: $row.find('.bulk-field-type').val(),
            ideal_value: $.trim($row.find('.bulk-ideal-value').val()),
            unit: $.trim($row.find('.bulk-unit').val()),
            min_value: $.trim($row.find('.bulk-min-value').val()),
            max_value: $.trim($row.find('.bulk-max-value').val()),
            is_mandatory: $row.find('.bulk-mandatory').val(),
            sort_order: $row.find('.bulk-sort-order').val()
        });
    });
    return items;
}

function openBulkChecklistModal() {
    $('#bulk_sub_audit_id').val('0');
    $('#bulk_master_audit_id').val('0');
    $('#bulk_default_field_type').val('text');
    $('#bulk_default_mandatory').val('0');
    $('#bulk_default_status').val('1');

    var masterId = $('#filter_checklist_master_id').val();
    if (masterId && masterId !== '0') {
        $('#bulk_master_audit_id').val(masterId);
    }
    filterBulkChecklistSubAudits(true);

    var subId = $('#filter_checklist_sub_id').val();
    if (subId && subId !== '0') {
        filterBulkChecklistSubAudits(false);
        $('#bulk_sub_audit_id').val(subId);
    }

    caSyncSelect2('#bulk_master_audit_id, #bulk_sub_audit_id, #bulk_default_field_type, #bulk_default_mandatory, #bulk_default_status');

    $('#bulk_checklist_rows').empty();
    addBulkChecklistRows(5);
    $('#bulk_checklist_modal').modal('show');
}

function saveBulkChecklist() {
    var subAuditId = $('#bulk_sub_audit_id').val();
    if (!subAuditId || subAuditId === '0') {
        alertify.alert('TechXpert', 'Please select a sub audit module.');
        return;
    }

    var items = collectBulkChecklistItems();
    var hasName = items.some(function (item) {
        return item.checkpoint_name !== '';
    });
    if (!hasName) {
        alertify.alert('TechXpert', 'Enter at least one checkpoint name.');
        return;
    }

    var payload = {
        sub_audit_id: subAuditId,
        default_field_type: $('#bulk_default_field_type').val(),
        default_is_mandatory: $('#bulk_default_mandatory').val(),
        default_is_active: $('#bulk_default_status').val(),
        items: items
    };

    $.ajax({
        url: 'action/bulk_add_checklist.php',
        type: 'POST',
        contentType: 'application/json; charset=utf-8',
        data: JSON.stringify(payload),
        success: function (res) {
            if (typeof res === 'string') {
                res = JSON.parse(res);
            }
            alertify.alert('TechXpert', res.message);
            if (!res.error) {
                $('#bulk_checklist_modal').modal('hide');
                checklistTable.ajax.reload(null, false);
            }
        },
        error: function () {
            alertify.alert('TechXpert', 'Unable to save bulk checkpoints. Please try again.');
        }
    });
}
