var employeeAssetAckTable = null;
var assetAckRowCounter = 0;

function parseAssetAckResponse(data) {
    if (typeof data === 'object' && data !== null) {
        return data;
    }
    try {
        return JSON.parse(data);
    } catch (e) {
        return { error: true, message: 'Invalid server response.' };
    }
}

function getAssetAckCategories() {
    return (window.TechXpertAssetAck && window.TechXpertAssetAck.categories) ? window.TechXpertAssetAck.categories : ['Other'];
}

function getAssetAckSelect2Config(dropdownParent, placeholder, allowClear) {
    return {
        width: '100%',
        allowClear: allowClear !== undefined ? allowClear : !!placeholder,
        placeholder: placeholder || '',
        minimumResultsForSearch: 0,
        dropdownParent: dropdownParent || $(document.body)
    };
}

function getAssetAckCategorySelect2Config(dropdownParent) {
    return $.extend({}, getAssetAckSelect2Config(dropdownParent, 'Search category...'), {
        dropdownCssClass: 'asset-ack-category-dropdown',
        dropdownAutoWidth: true
    });
}

function bindAssetAckCategorySelect2Width($el) {
    $el.off('select2:open.assetAck').on('select2:open.assetAck', function () {
        var $select = $(this);
        setTimeout(function () {
            var inst = $select.data('select2');
            if (!inst) {
                return;
            }
            var minWidth = Math.max(inst.$container.outerWidth(), 260);
            $('.select2-dropdown.asset-ack-category-dropdown').css({
                'min-width': minWidth + 'px',
                'width': 'auto'
            });
        }, 0);
    });
}

function initAssetAckEmployeeSelect2() {
    if (!$.fn.select2) {
        return;
    }
    if ($('#employee_filter').length && !$('#employee_filter').data('select2')) {
        $('#employee_filter').select2(getAssetAckSelect2Config($('#js-page-content'), 'Search employee...'));
    }
    if ($('#status_filter').length && !$('#status_filter').data('select2')) {
        $('#status_filter').select2(getAssetAckSelect2Config($('#js-page-content'), 'All statuses', false));
    }
    if ($('#assign_employee_id').length && !$('#assign_employee_id').data('select2')) {
        $('#assign_employee_id').select2(getAssetAckSelect2Config($('#assign_assets_modal'), 'Search employee by name or number'));
    }
}

function initAssetAckCategorySelect2($container) {
    if (!$.fn.select2) {
        return;
    }
    ($container || $(document)).find('select.asset-category').each(function () {
        var $el = $(this);
        if ($el.data('select2')) {
            return;
        }
        var $modal = $el.closest('.modal');
        $el.select2(getAssetAckCategorySelect2Config($modal.length ? $modal : $(document.body)));
        bindAssetAckCategorySelect2Width($el);
    });
}

function destroyAssetAckCategorySelect2($row) {
    if (!$.fn.select2) {
        return;
    }
    $row.find('select.asset-category').each(function () {
        var $el = $(this);
        if ($el.data('select2')) {
            $el.select2('destroy');
        }
    });
}

function buildAssetCategoryOptions(selected) {
    var html = '';
    getAssetAckCategories().forEach(function (cat) {
        var sel = (selected === cat) ? ' selected' : '';
        html += '<option value="' + cat + '"' + sel + '>' + cat + '</option>';
    });
    return html;
}

function escapeAssetAckHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function isStandardAssetCategory(category) {
    return getAssetAckCategories().indexOf(category) !== -1 && category !== 'Other';
}

function resolveAssetCategoryPrefill(prefill) {
    prefill = prefill || {};
    var category = prefill.category || 'Laptop';
    var customCategory = '';
    if (category && !isStandardAssetCategory(category) && category !== 'Other') {
        customCategory = category;
        category = 'Other';
    } else if (category === 'Other') {
        customCategory = prefill.custom_category || '';
    }
    return {
        category: category,
        custom_category: customCategory
    };
}

function buildAssetRowHtml(prefill) {
    assetAckRowCounter += 1;
    prefill = prefill || {};
    var categoryPrefill = resolveAssetCategoryPrefill(prefill);
    var rowId = 'asset_row_' + assetAckRowCounter;
    var showOther = categoryPrefill.category === 'Other';
    return '<tr id="' + rowId + '">'
        + '<td class="category-cell">'
        + '<div class="category-select-wrap">'
        + '<select class="form-control form-control-sm asset-category">' + buildAssetCategoryOptions(categoryPrefill.category) + '</select>'
        + '</div>'
        + '<input type="text" class="form-control form-control-sm asset-category-other mt-1" placeholder="Enter asset category name"'
        + ' value="' + escapeAssetAckHtml(categoryPrefill.custom_category) + '"'
        + (showOther ? '' : ' style="display:none;"') + '>'
        + '</td>'
        + '<td><input type="text" class="form-control form-control-sm asset-description" placeholder="e.g. MacBook Pro 14" value="' + escapeAssetAckHtml(prefill.description) + '"></td>'
        + '<td><input type="text" class="form-control form-control-sm asset-serial" placeholder="Serial / Asset ID" value="' + escapeAssetAckHtml(prefill.serial) + '"></td>'
        + '<td><input type="date" class="form-control form-control-sm asset-allocation-date" value="' + escapeAssetAckHtml(prefill.allocation_date) + '"></td>'
        + '<td><input type="text" class="form-control form-control-sm asset-remarks" placeholder="Optional" value="' + escapeAssetAckHtml(prefill.remarks) + '"></td>'
        + '<td class="text-center"><button type="button" class="btn btn-xs btn-outline-danger btn-remove-asset-row" data-row="' + rowId + '"><i class="fa fa-times"></i></button></td>'
        + '</tr>';
}

function toggleAssetCategoryOther($row) {
    var isOther = $row.find('.asset-category').val() === 'Other';
    $row.find('.asset-category-other').toggle(isOther);
    if (!isOther) {
        $row.find('.asset-category-other').val('');
    }
}

function getRowAssetCategory($row) {
    var category = $row.find('.asset-category').val();
    if (category === 'Other') {
        return $.trim($row.find('.asset-category-other').val());
    }
    return category;
}

function validateAssetRowsBeforeSave() {
    var isValid = true;
    $('#asset_rows_body tr').each(function () {
        var $row = $(this);
        if ($row.find('.asset-category').val() === 'Other' && !getRowAssetCategory($row)) {
            isValid = false;
            $row.find('.asset-category-other').addClass('is-invalid');
        } else {
            $row.find('.asset-category-other').removeClass('is-invalid');
        }
    });
    if (!isValid) {
        TechXAlert('Please enter a category name when Other is selected.', 'warning');
    }
    return isValid;
}

function resetAssetRows(prefillRows) {
    $('#asset_rows_body tr').each(function () {
        destroyAssetAckCategorySelect2($(this));
    });
    $('#asset_rows_body').empty();
    assetAckRowCounter = 0;
    var rows = prefillRows && prefillRows.length ? prefillRows : [{}];
    rows.forEach(function (row) {
        $('#asset_rows_body').append(buildAssetRowHtml(row));
    });
    initAssetAckCategorySelect2($('#asset_rows_body'));
}

function collectAssetRowsFromTable() {
    var assets = [];
    $('#asset_rows_body tr').each(function () {
        var $row = $(this);
        assets.push({
            category: getRowAssetCategory($row),
            description: $.trim($row.find('.asset-description').val()),
            serial: $.trim($row.find('.asset-serial').val()),
            allocation_date: $row.find('.asset-allocation-date').val(),
            remarks: $.trim($row.find('.asset-remarks').val())
        });
    });
    return assets;
}

function buildAssetAckListAjaxUrl() {
    var employeeId = $('#employee_filter').val() || -1;
    var status = $('#status_filter').val() || -1;
    return 'ajax/asset-ack-list-post.php?EmployeeID=' + encodeURIComponent(employeeId)
        + '&status=' + encodeURIComponent(status);
}

function closePageSelect2Dropdowns() {
    if (!$.fn.select2) {
        return;
    }
    $('#employee_filter').each(function () {
        if ($(this).data('select2')) {
            $(this).select2('close');
        }
    });
}

function initAssignAssetsModalHandlers() {
    $('#assign_assets_modal, #add_assets_modal, #public_link_modal').on('show.bs.modal', function () {
        closePageSelect2Dropdowns();
    });

    $('#btn_open_assign_assets').on('click', function () {
        closePageSelect2Dropdowns();
        $('#assign_employee_id').val('').trigger('change');
        resetAssetRows([{}]);
        $('#assign_assets_modal').modal('show');
    });

    $(document).on('click', '.btn-generate-link', function (e) {
        e.preventDefault();
        var employeeId = $(this).attr('data-employee-id') || $(this).data('employeeId');
        generatePublicAssetLink(employeeId);
    });
}

function initEmployeeAssetAckList() {
    if ($('#employee_asset_ack_table').length === 0) {
        return;
    }

    if (!$.fn.DataTable) {
        console.error('DataTables is not loaded.');
        return;
    }

    employeeAssetAckTable = $('#employee_asset_ack_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: buildAssetAckListAjaxUrl(),
            type: 'POST'
        },
        columns: [
            { data: 0 },
            { data: 1 },
            { data: 2 },
            { data: 3 },
            { data: 4 },
            { data: 5 },
            { data: 6, orderable: false, searchable: false }
        ],
        order: [[0, 'asc']],
        pageLength: 25
    });

    $('#btn_filter_asset_ack').on('click', function () {
        employeeAssetAckTable.ajax.url(buildAssetAckListAjaxUrl()).load();
    });
}

function generatePublicAssetLink(employeeId) {
    employeeId = parseInt(employeeId, 10);
    if (!employeeId) {
        TechXAlert('Employee reference is missing.', 'warning');
        return;
    }
    $.post('action/generate_public_link.php', { employee_id: employeeId }, function (data) {
        var res = parseAssetAckResponse(data);
        if (res.error) {
            TechXAlert(res.message || 'Failed to generate link.', 'error');
            return;
        }
        $('#public_link_input').val(res.url || '');
        $('#public_link_modal').modal('show');
    }).fail(function () {
        TechXAlert('Failed to generate public link. Please try again.', 'error');
    });
}

function initAssetRowHandlers() {
    $(document).on('change', '.asset-category', function () {
        toggleAssetCategoryOther($(this).closest('tr'));
    });

    $(document).on('click', '#btn_add_asset_row', function () {
        var $row = $(buildAssetRowHtml({}));
        $('#asset_rows_body').append($row);
        initAssetAckCategorySelect2($row);
    });

    $(document).on('click', '.btn-remove-asset-row', function () {
        var rowId = $(this).data('row');
        var $body = $('#asset_rows_body');
        if ($body.find('tr').length <= 1) {
            TechXAlert('At least one asset row is required.', 'warning');
            return;
        }
        destroyAssetAckCategorySelect2($('#' + rowId));
        $('#' + rowId).remove();
    });

    $(document).on('click', '#btn_save_assigned_assets', function () {
        var employeeId = window.TechXpertAssetAck && window.TechXpertAssetAck.managePage
            ? window.TechXpertAssetAck.employeeId
            : $('#assign_employee_id').val();
        if (!employeeId) {
            TechXAlert('Please select an employee.', 'warning');
            return;
        }
        if (!validateAssetRowsBeforeSave()) {
            return;
        }
        var assets = collectAssetRowsFromTable();
        $.post('action/save_employee_assets.php', {
            employee_id: employeeId,
            assets: JSON.stringify(assets)
        }, function (data) {
            var res = parseAssetAckResponse(data);
            if (res.error) {
                TechXAlert(res.message || 'Save failed.', 'error');
                return;
            }
            TechXAlert(res.message || 'Saved.', 'success');
            if (window.TechXpertAssetAck && window.TechXpertAssetAck.managePage) {
                window.location.reload();
            } else {
                $('#assign_assets_modal').modal('hide');
                if (employeeAssetAckTable) {
                    employeeAssetAckTable.ajax.reload(null, false);
                }
            }
        });
    });

    $('#btn_copy_public_link').on('click', function () {
        var $input = $('#public_link_input');
        $input.trigger('select');
        try {
            document.execCommand('copy');
            TechXAlert('Link copied to clipboard.', 'success');
        } catch (e) {
            TechXAlert('Copy the link manually.', 'info');
        }
    });
}

function initManageEmployeeAssetsPage() {
    if (!(window.TechXpertAssetAck && window.TechXpertAssetAck.managePage)) {
        return;
    }

    $('#btn_add_more_assets').on('click', function () {
        resetAssetRows([{}]);
        $('#add_assets_modal').modal('show');
    });

    $('#btn_show_public_link').on('click', function () {
        $('#public_link_modal').modal('show');
    });

    $(document).on('click', '.btn-change-asset-status', function () {
        var assetId = $(this).attr('data-id') || $(this).data('id');
        var description = $(this).attr('data-description') || '';
        $('#hold_status_asset_id').val(assetId);
        $('#hold_status_asset_label').text(description || ('Asset #' + assetId));
        $('#hold_status_select').val('');
        $('#hold_status_remarks').val('');
        $('#asset_hold_status_modal').modal('show');
    });

    $('#btn_save_asset_hold_status').on('click', function () {
        var assetId = $('#hold_status_asset_id').val();
        var holdStatus = $('#hold_status_select').val();
        var remarks = $.trim($('#hold_status_remarks').val());
        if (!assetId || !holdStatus) {
            TechXAlert('Please select a status for this asset.', 'warning');
            return;
        }
        $.post('action/update_asset_hold_status.php', {
            asset_id: assetId,
            employee_id: window.TechXpertAssetAck.employeeId,
            hold_status: holdStatus,
            remarks: remarks
        }, function (data) {
            var res = parseAssetAckResponse(data);
            if (res.error) {
                TechXAlert(res.message || 'Status update failed.', 'error');
                return;
            }
            TechXAlert(res.message || 'Status updated.', 'success');
            window.location.reload();
        });
    });
}

$(document).ready(function () {
    if ($('#_Nav_Employee_Asset_Acknowledgement').length) {
        $('#_Nav_Employee_Asset_Acknowledgement').addClass('active open');
    }

    if ($.fn.select2) {
        initAssetAckEmployeeSelect2();
    }

    initAssetRowHandlers();
    initAssignAssetsModalHandlers();
    initEmployeeAssetAckList();
    initManageEmployeeAssetsPage();
});
