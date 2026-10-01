var vendorVerificationTable = null;
var vendorVerificationConfig = {};

function vendorVerificationEscapeHtml(value) {
    return $('<div>').text(value == null ? '' : String(value)).html();
}

function closeVendorVerificationFilterUi() {
    $('.vendor-verification-filter').each(function () {
        var $el = $(this);
        if ($el.data('select2')) {
            $el.select2('close');
        }
    });

    if ($('#vendor_filter_date').data('daterangepicker')) {
        $('#vendor_filter_date').data('daterangepicker').hide();
    }
}

function vendorVerificationParseResponse(data) {
    if (typeof data === 'object' && data !== null) {
        return data;
    }
    try {
        return JSON.parse(data);
    } catch (error) {
        return { error: true, message: 'Invalid server response.' };
    }
}

function vendorVerificationEncodeMulti(values) {
    if (!values || values.length === 0) {
        return '-1';
    }
    return Array.isArray(values) ? values.join(',') : values;
}

function vendorVerificationCaptureFilters() {
    return {
        status: $('#vendor_status_filter').val() || [],
        state: $('#vendor_state_filter').val() || [],
        vendor_type: $('#vendor_type_filter').val() || [],
        vendor_category: $('#vendor_category_filter').val() || [],
        filter_date: $('#vendor_filter_date').val() || '',
        keyword: $('#vendor_keyword_filter').val() || ''
    };
}

function vendorVerificationBuildParam(filterState) {
    var state = filterState || vendorVerificationCaptureFilters();
    var filterDate = state.filter_date === 'All Time' ? 'all' : state.filter_date;
    return '?filter_date=' + encodeURIComponent(filterDate)
        + '&status=' + encodeURIComponent(vendorVerificationEncodeMulti(state.status))
        + '&state=' + encodeURIComponent(vendorVerificationEncodeMulti(state.state))
        + '&vendor_type=' + encodeURIComponent(vendorVerificationEncodeMulti(state.vendor_type))
        + '&vendor_category=' + encodeURIComponent(vendorVerificationEncodeMulti(state.vendor_category))
        + '&keyword=' + encodeURIComponent(state.keyword || '');
}

function initVendorVerificationFilters() {
    $('.vendor-verification-filter').each(function () {
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

    $('#vendor_filter_date').daterangepicker({
        startDate: moment().subtract(365, 'days'),
        endDate: moment(),
        locale: { format: 'YYYY-MM-DD' },
        ranges: {
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'Last 1 Year': [moment().subtract(365, 'days'), moment()],
            'All Time': [moment('2000-01-01'), moment()]
        }
    });

    $('#vendor_keyword_filter').on('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            refreshVendorVerificationTable();
        }
    });

    $('#vendorVerificationModal')
        .on('show.bs.modal', function () {
            closeVendorVerificationFilterUi();
        })
        .on('shown.bs.modal', function () {
            closeVendorVerificationFilterUi();
        })
        .on('hidden.bs.modal', function () {
            closeVendorVerificationFilterUi();
            $('#vendor_verification_modal_body').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>');
        });

    $('#vendorVerificationModal').on('hidden.bs.modal', function () {
        $('.select2-container--open').remove();
    });
}

function initVendorVerificationPage(config) {
    vendorVerificationConfig = config || {};
    initVendorVerificationFilters();
    initVendorVerificationTable(vendorVerificationConfig.initialParam || '');
}

function initVendorVerificationTable(param) {
    var selector = '#vendor-registration-verification-table';
    if ($.fn.DataTable.isDataTable(selector)) {
        vendorVerificationTable = $(selector).DataTable();
        vendorVerificationTable.ajax.url(vendorVerificationConfig.listUrl + param).load(null, false);
        return;
    }

    vendorVerificationTable = $(selector).DataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        ordering: false,
        serverMethod: 'post',
        ajax: {
            url: vendorVerificationConfig.listUrl + param
        },
        columns: [
            { data: 'Registration' },
            { data: 'Applicant' },
            { data: 'Business' },
            { data: 'TypeCategory' },
            { data: 'State' },
            { data: 'SubmittedAt' },
            { data: 'Status' },
            { data: 'StateApproval' },
            { data: 'HrApproval' },
            { data: 'Actions' }
        ]
    });
}

function refreshVendorVerificationTable() {
    if (!vendorVerificationTable) {
        initVendorVerificationTable(vendorVerificationBuildParam());
        return;
    }
    vendorVerificationTable.ajax.url(vendorVerificationConfig.listUrl + vendorVerificationBuildParam()).load(null, false);
}

function resetVendorVerificationFilters() {
    $('#vendor_status_filter').val(null).trigger('change');
    $('#vendor_state_filter').val(null).trigger('change');
    $('#vendor_type_filter').val(null).trigger('change');
    $('#vendor_category_filter').val(null).trigger('change');
    $('#vendor_filter_date').val(moment().subtract(365, 'days').format('YYYY-MM-DD') + ' - ' + moment().format('YYYY-MM-DD'));
    $('#vendor_keyword_filter').val('');
    refreshVendorVerificationTable();
}

function vendorVerificationRenderTimeline(timeline) {
    if (!timeline || timeline.length === 0) {
        return '<p class="vendor-muted-note mb-0">No verification timeline available.</p>';
    }

    var html = '<div class="vendor-timeline">';
    timeline.forEach(function (item) {
        html += '<div class="vendor-timeline-item">'
            + '<span class="vendor-timeline-dot ' + vendorVerificationEscapeHtml(item.status || 'waiting') + '"></span>'
            + '<div class="vendor-timeline-content">'
            + '<h6>' + vendorVerificationEscapeHtml(item.title || '') + '</h6>'
            + '<p>' + vendorVerificationEscapeHtml(item.meta || '-') + '</p>';
        if (item.remarks) {
            html += '<p class="mt-1"><strong>Remarks:</strong> ' + vendorVerificationEscapeHtml(item.remarks) + '</p>';
        }
        html += '</div></div>';
    });
    html += '</div>';
    return html;
}

function vendorVerificationRenderDocuments(documents) {
    if (!documents || documents.length === 0) {
        return '<p class="vendor-muted-note mb-0">No documents uploaded.</p>';
    }

    var html = '<div class="vendor-document-grid">';
    documents.forEach(function (doc) {
        html += '<div class="vendor-document-card">';
        html += '<div class="small font-weight-600 mb-2">' + vendorVerificationEscapeHtml(doc.label || 'Document') + '</div>';
        if (doc.url) {
            if (doc.is_image) {
                html += '<img src="' + vendorVerificationEscapeHtml(doc.url) + '" class="vendor-document-preview mb-2" alt="' + vendorVerificationEscapeHtml(doc.label || 'Document') + '">';
            } else {
                html += '<div class="vendor-document-preview mb-2 d-flex align-items-center justify-content-center"><i class="fal fa-file-alt fa-2x text-muted"></i></div>';
            }
            html += '<div class="small text-muted mb-2">' + vendorVerificationEscapeHtml(doc.file_name || '') + '</div>';
            html += '<a href="' + vendorVerificationEscapeHtml(doc.url) + '" target="_blank" class="btn btn-sm btn-outline-primary">Open</a>';
        } else {
            html += '<div class="vendor-document-preview mb-2 d-flex align-items-center justify-content-center"><span class="small text-muted">Not Uploaded</span></div>';
            html += '<div class="small text-muted">No file available</div>';
        }
        html += '</div>';
    });
    html += '</div>';
    return html;
}

function vendorVerificationRenderActionPanels(data) {
    var html = '';
    var permissions = data.Permissions || {};

    if (permissions.CanStateVerify) {
        html += '<div class="vendor-review-card vendor-action-panel">'
            + '<div class="vendor-review-section-title">State Corporate Lead Verification</div>'
            + '<p class="vendor-muted-note">This lead belongs to your mapped state scope. You can complete the first verification stage here.</p>'
            + '<textarea class="form-control mb-3" id="vendor_state_action_remarks" placeholder="State verification remarks (optional)"></textarea>'
            + '<button type="button" class="btn btn-success btn-sm vendor-action-btn mr-2" onclick="submitVendorVerificationAction(' + parseInt(data.ID, 10) + ', \'state_approve\', \'vendor_state_action_remarks\')">Approve State Verification</button>'
            + '<button type="button" class="btn btn-danger btn-sm vendor-action-btn" onclick="submitVendorVerificationAction(' + parseInt(data.ID, 10) + ', \'state_reject\', \'vendor_state_action_remarks\')">Reject at State Level</button>'
            + '</div>';
    }

    if (permissions.CanHrVerify) {
        html += '<div class="vendor-review-card vendor-action-panel">'
            + '<div class="vendor-review-section-title">HR / Admin Final Verification</div>';
        if (data.Status === 'Pending') {
            html += '<p class="vendor-muted-note">HR/Admin can directly complete final verification even before state approval when business needs require it.</p>';
        } else {
            html += '<p class="vendor-muted-note">Use this section for final verification and closure.</p>';
        }
        html += '<textarea class="form-control mb-3" id="vendor_hr_action_remarks" placeholder="HR final verification remarks (optional)"></textarea>'
            + '<button type="button" class="btn btn-success btn-sm vendor-action-btn mr-2" onclick="submitVendorVerificationAction(' + parseInt(data.ID, 10) + ', \'hr_approve\', \'vendor_hr_action_remarks\')">Approve Final Verification</button>'
            + '<button type="button" class="btn btn-danger btn-sm vendor-action-btn" onclick="submitVendorVerificationAction(' + parseInt(data.ID, 10) + ', \'hr_reject\', \'vendor_hr_action_remarks\')">Reject at Final Stage</button>'
            + '</div>';
    }

    if (!permissions.CanStateVerify && !permissions.CanHrVerify) {
        html += '<div class="vendor-review-card">'
            + '<div class="vendor-review-section-title">Action Access</div>';
        if (permissions.FinanceViewOnly) {
            html += '<p class="vendor-muted-note mb-0">Finance access is view-only for vendor verification records.</p>';
        } else if (data.Status === 'Approved' || data.Status === 'Rejected') {
            html += '<p class="vendor-muted-note mb-0">This registration is already closed and no further verification action is available.</p>';
        } else {
            html += '<p class="vendor-muted-note mb-0">No action is available for your current role on this registration.</p>';
        }
        html += '</div>';
    }

    return html;
}

function vendorVerificationRenderDetails(data) {
    var stateRemarks = data.StateManagerRemarks ? '<div class="small text-muted mt-2"><strong>Remarks:</strong> ' + vendorVerificationEscapeHtml(data.StateManagerRemarks) + '</div>' : '';
    var hrRemarks = data.HrRemarks ? '<div class="small text-muted mt-2"><strong>Remarks:</strong> ' + vendorVerificationEscapeHtml(data.HrRemarks) + '</div>' : '';
    var rejectionRemarks = data.RejectionReason ? '<div class="small text-danger mt-2"><strong>Reason:</strong> ' + vendorVerificationEscapeHtml(data.RejectionReason) + '</div>' : '';

    return ''
        + '<div class="vendor-review-card">'
        + '  <div class="d-flex flex-wrap justify-content-between align-items-start">'
        + '      <div>'
        + '          <div class="vendor-review-section-title mb-1">Registration ' + vendorVerificationEscapeHtml(data.RegistrationCode || '') + '</div>'
        + '          <div class="vendor-muted-note">Submitted by ' + vendorVerificationEscapeHtml(data.SubmitterName || '-') + ' (' + vendorVerificationEscapeHtml(data.SubmitterEmployeeNumber || '-') + ')</div>'
        + '      </div>'
        + '      <div>' + (data.StatusBadgeHtml || '') + '</div>'
        + '  </div>'
        + '</div>'
        + '<div class="row">'
        + '  <div class="col-lg-7">'
        + '      <div class="vendor-review-card">'
        + '          <div class="vendor-review-section-title">Applicant Details</div>'
        + '          <div class="vendor-kv">'
        + '              <div class="key">Contact Name</div><div class="value">' + vendorVerificationEscapeHtml(data.Name || '-') + '</div>'
        + '              <div class="key">Mobile</div><div class="value">' + vendorVerificationEscapeHtml(data.Mobile || '-') + '</div>'
        + '              <div class="key">Email</div><div class="value">' + vendorVerificationEscapeHtml(data.Email || '-') + '</div>'
        + '              <div class="key">Business</div><div class="value">' + vendorVerificationEscapeHtml(data.BusinessName || '-') + '</div>'
        + '              <div class="key">Vendor Type</div><div class="value">' + vendorVerificationEscapeHtml(data.VendorType || '-') + '</div>'
        + '              <div class="key">Category</div><div class="value">' + vendorVerificationEscapeHtml(data.VendorCategory || '-') + '</div>'
        + '              <div class="key">State</div><div class="value">' + vendorVerificationEscapeHtml(data.StateName || '-') + '</div>'
        + '              <div class="key">City / Pincode</div><div class="value">' + vendorVerificationEscapeHtml((data.City || '-') + ' / ' + (data.Pincode || '-')) + '</div>'
        + '              <div class="key">Current Address</div><div class="value">' + vendorVerificationEscapeHtml(data.CurrentAddress || '-') + '</div>'
        + '              <div class="key">Permanent Address</div><div class="value">' + vendorVerificationEscapeHtml(data.PermanentAddress || '-') + '</div>'
        + '          </div>'
        + '      </div>'
        + '      <div class="vendor-review-card">'
        + '          <div class="vendor-review-section-title">Compliance & Bank Details</div>'
        + '          <div class="vendor-kv">'
        + '              <div class="key">GST Number</div><div class="value">' + vendorVerificationEscapeHtml(data.GstNumber || '-') + '</div>'
        + '              <div class="key">PAN Number</div><div class="value">' + vendorVerificationEscapeHtml(data.PanNumber || '-') + '</div>'
        + '              <div class="key">Aadhaar Number</div><div class="value">' + vendorVerificationEscapeHtml(data.AadharNumber || '-') + '</div>'
        + '              <div class="key">Bank Name</div><div class="value">' + vendorVerificationEscapeHtml(data.BankName || '-') + '</div>'
        + '              <div class="key">Account Name</div><div class="value">' + vendorVerificationEscapeHtml(data.AccountName || '-') + '</div>'
        + '              <div class="key">Account Number</div><div class="value">' + vendorVerificationEscapeHtml(data.AccountNumber || '-') + '</div>'
        + '              <div class="key">IFSC Code</div><div class="value">' + vendorVerificationEscapeHtml(data.IfscCode || '-') + '</div>'
        + '              <div class="key">Applicant Remarks</div><div class="value">' + vendorVerificationEscapeHtml(data.Remarks || '-') + '</div>'
        + '          </div>'
        + '      </div>'
        + '      <div class="vendor-review-card">'
        + '          <div class="vendor-review-section-title">Uploaded Documents</div>'
        +            vendorVerificationRenderDocuments(data.Documents || [])
        + '      </div>'
        + '  </div>'
        + '  <div class="col-lg-5">'
        + '      <div class="vendor-review-card">'
        + '          <div class="vendor-review-section-title">Verification Timeline</div>'
        +            vendorVerificationRenderTimeline(data.Timeline || [])
        + '      </div>'
        + '      <div class="vendor-review-card">'
        + '          <div class="vendor-review-section-title">State Verification Snapshot</div>'
        + '          <div>' + (data.StateApprovalHtml || '') + '</div>'
                   + stateRemarks
        + '      </div>'
        + '      <div class="vendor-review-card">'
        + '          <div class="vendor-review-section-title">HR Final Snapshot</div>'
        + '          <div>' + (data.HrApprovalHtml || '') + '</div>'
                   + hrRemarks
        + '      </div>'
        + ((data.Status === 'Rejected')
            ? '<div class="vendor-review-card"><div class="vendor-review-section-title">Rejection Snapshot</div><div class="small text-muted">Rejected By: ' + vendorVerificationEscapeHtml(data.RejectedByName || '-') + '<br>Rejected At: ' + vendorVerificationEscapeHtml(data.RejectedAt || '-') + '<br>Rejected Stage: ' + vendorVerificationEscapeHtml(data.RejectedAtStage || '-') + '</div>' + rejectionRemarks + '</div>'
            : '')
        +        vendorVerificationRenderActionPanels(data)
        + '  </div>'
        + '</div>';
}

function openVendorVerificationReview(id) {
    closeVendorVerificationFilterUi();
    $('.select2-container--open').remove();
    $('#vendorVerificationModal').modal('show');
    $('#vendor_verification_modal_body').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>');

    $.getJSON(vendorVerificationConfig.detailUrl, { ID: id }, function (response) {
        var data = vendorVerificationParseResponse(response);
        if (data.error) {
            $('#vendor_verification_modal_body').html('<div class="alert alert-danger mb-0">' + vendorVerificationEscapeHtml(data.message || 'Unable to load registration details.') + '</div>');
            return;
        }
        $('#vendor_verification_modal_body').html(vendorVerificationRenderDetails(data.data || {}));
    }).fail(function () {
        $('#vendor_verification_modal_body').html('<div class="alert alert-danger mb-0">Unable to load vendor registration details. Please try again.</div>');
    });
}

function submitVendorVerificationAction(id, action, textareaId) {
    var remarks = textareaId ? ($('#' + textareaId).val() || '') : '';
    var $buttons = $('#vendorVerificationModal .vendor-action-btn');
    $buttons.prop('disabled', true);

    $.post(vendorVerificationConfig.updateUrl, {
        ID: id,
        Action: action,
        Remarks: remarks
    }, function (response) {
        var data = vendorVerificationParseResponse(response);
        if (data.error) {
            $buttons.prop('disabled', false);
            TechXAlert(data.message || 'Unable to update vendor verification.');
            return;
        }

        if (typeof alertify !== 'undefined' && alertify.success) {
            alertify.success(data.message || 'Verification updated.', 2);
        }

        refreshVendorVerificationTable();
        openVendorVerificationReview(id);
    }, 'json').fail(function () {
        $buttons.prop('disabled', false);
        TechXAlert('Unable to update vendor verification. Please try again.');
    });
}
