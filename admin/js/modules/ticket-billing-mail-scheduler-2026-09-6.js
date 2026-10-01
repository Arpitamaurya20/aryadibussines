var tbmsSchedules = [];
var tbmsInvoiceRows = [];
var tbmsAvailableInvoices = [];
var tbmsTinyMceLoaderPromise = null;
var tbmsRichTextEditorIds = ['modal_remarks', 'modal_terms'];

function tbmsEsc(value) {
    return $('<div/>').text(value == null ? '' : value).html();
}

function tbmsGetSelectedDays() {
    var days = [];
    $('.tbms-day-checkbox:checked').each(function () {
        days.push($(this).val());
    });
    return days.join(',');
}

function tbmsUpdateDaySelector(selectedDays) {
    var type = $('#modal_schedule_type').val();
    var html = '';
    var selected = (selectedDays || '').split(',').map(function (d) { return d.trim(); });

    if (type === 'Monthly') {
        for (var i = 1; i <= 31; i++) {
            var checked = selected.indexOf(String(i)) >= 0 ? ' checked' : '';
            html += '<input type="checkbox" class="tbms-day-checkbox" id="tbms_day_' + i + '" value="' + i + '"' + checked + '>';
            html += '<label for="tbms_day_' + i + '" class="day-label">' + i + '</label>';
        }
    } else {
        var weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        weekdays.forEach(function (day) {
            var checked = selected.indexOf(day) >= 0 ? ' checked' : '';
            var short = day.substring(0, 3);
            html += '<input type="checkbox" class="tbms-day-checkbox" id="tbms_day_' + day + '" value="' + day + '"' + checked + '>';
            html += '<label for="tbms_day_' + day + '" class="day-label">' + short + '</label>';
        });
    }
    $('#day_selector').html(html);
}

function tbmsLoadTinyMceScript() {
    if (typeof tinymce !== 'undefined') {
        return Promise.resolve();
    }
    if (tbmsTinyMceLoaderPromise) {
        return tbmsTinyMceLoaderPromise;
    }

    tbmsTinyMceLoaderPromise = new Promise(function (resolve, reject) {
        var script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js';
        script.onload = function () { resolve(); };
        script.onerror = function () { reject(new Error('TinyMCE script failed to load.')); };
        document.head.appendChild(script);
    });

    return tbmsTinyMceLoaderPromise;
}

function tbmsInitRichTextEditors() {
    return tbmsLoadTinyMceScript().then(function () {
        tbmsRichTextEditorIds.forEach(function (editorId) {
            if (!document.getElementById(editorId) || tinymce.get(editorId)) {
                return;
            }

            tinymce.init({
                selector: '#' + editorId,
                menubar: false,
                branding: false,
                height: 220,
                plugins: 'lists link code autoresize',
                toolbar: 'undo redo | bold italic underline | bullist numlist | link | removeformat | code',
                autoresize_bottom_margin: 12,
                content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; }',
                setup: function (editor) {
                    editor.on('change keyup', function () {
                        editor.save();
                    });
                }
            });
        });
    }).catch(function () {
        console.log('TinyMCE unavailable, using plain textarea fallback.');
    });
}

function tbmsSyncRichTextEditors() {
    if (typeof tinymce === 'undefined') {
        return;
    }
    tbmsRichTextEditorIds.forEach(function (editorId) {
        var editor = tinymce.get(editorId);
        if (editor) {
            editor.save();
        }
    });
}

function tbmsDestroyRichTextEditors() {
    if (typeof tinymce === 'undefined') {
        return;
    }
    tbmsRichTextEditorIds.forEach(function (editorId) {
        var editor = tinymce.get(editorId);
        if (editor) {
            editor.remove();
        }
    });
}

function tbmsSetRichTextContent(remarks, terms) {
    $('#modal_remarks').val(remarks || '');
    $('#modal_terms').val(terms || '');
    if (typeof tinymce === 'undefined') {
        return;
    }
    var remarksEditor = tinymce.get('modal_remarks');
    if (remarksEditor) {
        remarksEditor.setContent(remarks || '');
    }
    var termsEditor = tinymce.get('modal_terms');
    if (termsEditor) {
        termsEditor.setContent(terms || '');
    }
}

function tbmsShowScheduleModal(afterInit) {
    $('#scheduleModal').one('shown.bs.modal', function () {
        setTimeout(function () {
            tbmsInitRichTextEditors().then(function () {
                if (typeof afterInit === 'function') {
                    afterInit();
                }
            });
        }, 100);
    });
    $('#scheduleModal').modal('show');
}

function tbmsLoadSchedules() {
    $.get('../tickets-billing/action/mail_scheduler_get_schedules.php', function (response) {
        if (response.error) {
            $('#schedules_list').html('<div class="col-12 alert alert-danger">' + tbmsEsc(response.message || 'Unable to load schedules') + '</div>');
            return;
        }
        tbmsSchedules = response.data || [];
        tbmsRenderSchedules();
    }, 'json');
}

function tbmsRenderSchedules() {
    if (!tbmsSchedules.length) {
        $('#schedules_list').html('<div class="col-12"><div class="alert alert-light border">No schedules yet. Click <strong>New Schedule</strong> to create your first billing reminder.</div></div>');
        return;
    }

    var html = '';
    tbmsSchedules.forEach(function (schedule) {
        var status = schedule.Status || 'Active';
        var cardClass = status.toLowerCase();
        var badgeClass = status === 'Active' ? 'success' : (status === 'Stopped' ? 'warning' : 'danger');
        html += '<div class="col-md-6 col-xl-4">';
        html += '<div class="tbms-card ' + cardClass + '">';
        html += '<div class="d-flex justify-content-between align-items-start mb-2">';
        html += '<h5 class="mb-0">' + tbmsEsc(schedule.ScheduleName) + '</h5>';
        html += '<span class="badge badge-' + badgeClass + '">' + tbmsEsc(status) + '</span>';
        html += '</div>';
        html += '<div class="small text-muted mb-1">' + tbmsEsc(schedule.ScheduleType) + ' | ' + tbmsEsc((schedule.EmailTime || '').substring(0, 5)) + ' | ' + (schedule.EmailsPerWeek || 1) + 'x/week</div>';
        html += '<div class="small mb-1"><strong>To:</strong> ' + tbmsEsc(schedule.EmailTo || '-') + '</div>';
        html += '<div class="small mb-2"><strong>Invoices:</strong> ' + (schedule.InvoiceCount || 0) + '</div>';
        html += '<div class="btn-group btn-group-sm">';
        html += '<button class="btn btn-primary" onclick="tbmsOpenModal(' + schedule.ID + ');"><i class="fa fa-edit"></i> Edit</button>';
        html += '<button class="btn btn-success" onclick="tbmsRunNow(' + schedule.ID + ');"><i class="fa fa-paper-plane"></i> Send Now</button>';
        if (status === 'Active') {
            html += '<button class="btn btn-warning" onclick="tbmsUpdateStatus(' + schedule.ID + ', \'Stopped\');">Stop</button>';
        } else if (status === 'Stopped') {
            html += '<button class="btn btn-info" onclick="tbmsUpdateStatus(' + schedule.ID + ', \'Active\');">Resume</button>';
        }
        html += '<button class="btn btn-danger" onclick="tbmsDeleteSchedule(' + schedule.ID + ');"><i class="fa fa-trash"></i></button>';
        html += '</div></div></div>';
    });
    $('#schedules_list').html(html);
}

function tbmsResetInvoiceRows(rows) {
    tbmsInvoiceRows = rows || [];
    tbmsRenderInvoiceRows();
}

function tbmsRenderInvoiceRows() {
    var html = '';
    tbmsInvoiceRows.forEach(function (row, index) {
        var hasExternal = !!(row.ExternalPdfFile || '').trim();
        html += '<tr data-index="' + index + '">';
        html += '<td><input type="text" class="form-control form-control-sm tbms-inv-number" value="' + tbmsEsc(row.BillingNumber || '') + '"></td>';
        html += '<td><input type="date" class="form-control form-control-sm tbms-inv-date" value="' + tbmsEsc(row.InvoiceDate || '') + '"></td>';
        html += '<td><input type="number" step="0.01" class="form-control form-control-sm tbms-inv-amount" value="' + tbmsEsc(row.TotalAmount || '0') + '"></td>';
        html += '<td><input type="date" class="form-control form-control-sm tbms-inv-due" value="' + tbmsEsc(row.DueDate || '') + '"></td>';
        html += '<td><input type="text" class="form-control form-control-sm tbms-inv-po" value="' + tbmsEsc(row.PONumber || '') + '"></td>';
        html += '<td class="tbms-pdf-cell">';
        html += '<input type="hidden" class="tbms-inv-external-file" value="' + tbmsEsc(row.ExternalPdfFile || '') + '">';
        html += '<div class="input-group input-group-sm">';
        html += '<input type="text" class="form-control tbms-inv-link" value="' + tbmsEsc(row.PdfLink || '') + '" placeholder="Auto or uploaded PDF link">';
        html += '<div class="input-group-append">';
        html += '<button type="button" class="btn btn-outline-secondary tbms-upload-pdf-btn" title="Upload external PDF (Zoho etc.)"><i class="fa fa-upload"></i></button>';
        if (hasExternal && row.PdfLink) {
            html += '<a class="btn btn-outline-info" href="' + tbmsEsc(row.PdfLink) + '" target="_blank" title="Preview uploaded PDF"><i class="fa fa-external-link-alt"></i></a>';
        }
        html += '</div></div>';
        if (hasExternal) {
            html += '<div class="small text-success mt-1"><i class="fa fa-file-pdf"></i> External PDF attached</div>';
        }
        html += '</td>';
        html += '<td><button type="button" class="btn btn-xs btn-outline-danger" onclick="tbmsRemoveInvoiceRow(' + index + ');"><i class="fa fa-times"></i></button></td>';
        html += '</tr>';
    });
    $('#invoice_rows_table tbody').html(html);
}

function tbmsCollectInvoiceRows() {
    var rows = [];
    $('#invoice_rows_table tbody tr').each(function () {
        var billingNumber = $(this).find('.tbms-inv-number').val().trim();
        if (!billingNumber) return;
        rows.push({
            BillingNumber: billingNumber,
            InvoiceDate: $(this).find('.tbms-inv-date').val(),
            TotalAmount: $(this).find('.tbms-inv-amount').val(),
            DueDate: $(this).find('.tbms-inv-due').val(),
            PONumber: $(this).find('.tbms-inv-po').val(),
            PdfLink: $(this).find('.tbms-inv-link').val(),
            ExternalPdfFile: $(this).find('.tbms-inv-external-file').val()
        });
    });
    return rows;
}

function tbmsAddInvoiceRow(prefill) {
    tbmsInvoiceRows.push(prefill || {
        BillingNumber: '',
        InvoiceDate: '',
        TotalAmount: '0',
        DueDate: '',
        PONumber: '',
        PdfLink: '',
        ExternalPdfFile: ''
    });
    tbmsRenderInvoiceRows();
}

function tbmsGetExistingInvoiceNumbers() {
    var numbers = {};
    tbmsCollectInvoiceRows().forEach(function (row) {
        if (row.BillingNumber) {
            numbers[row.BillingNumber.toUpperCase()] = true;
        }
    });
    return numbers;
}

function tbmsAppendInvoices(invoices) {
    if (!invoices || !invoices.length) {
        return 0;
    }
    var existing = tbmsGetExistingInvoiceNumbers();
    var added = 0;
    invoices.forEach(function (inv) {
        var billingNumber = (inv.BillingNumber || '').trim();
        if (!billingNumber || existing[billingNumber.toUpperCase()]) {
            return;
        }
        existing[billingNumber.toUpperCase()] = true;
        tbmsInvoiceRows.push({
            BillingNumber: billingNumber,
            InvoiceDate: inv.InvoiceDate || '',
            TotalAmount: inv.TotalAmount || 0,
            DueDate: inv.DueDate || '',
            PONumber: inv.PONumber || '',
            PdfLink: inv.PdfLink || '',
            ExternalPdfFile: inv.ExternalPdfFile || ''
        });
        added++;
    });
    if (added) {
        tbmsRenderInvoiceRows();
    }
    return added;
}

function tbmsFetchInvoiceByNumber(billingNumber, $row) {
    billingNumber = (billingNumber || '').trim();
    if (!billingNumber) {
        return;
    }
    $row.find('.tbms-inv-number').prop('disabled', true);
    $.get('../tickets-billing/action/mail_scheduler_get_invoice_by_number.php', { BillingNumber: billingNumber }, function (response) {
        $row.find('.tbms-inv-number').prop('disabled', false);
        if (response.error) {
            alert(response.message || 'Invoice not found');
            return;
        }
        var inv = response.data || {};
        $row.find('.tbms-inv-date').val(inv.InvoiceDate || '');
        $row.find('.tbms-inv-amount').val(inv.TotalAmount || '0');
        $row.find('.tbms-inv-due').val(inv.DueDate || '');
        $row.find('.tbms-inv-link').val(inv.PdfLink || '');
        $row.find('.tbms-inv-external-file').val('');
        if (inv.PONumber) {
            $row.find('.tbms-inv-po').val(inv.PONumber);
        }
        var rowIndex = parseInt($row.attr('data-index'), 10);
        if (!isNaN(rowIndex) && tbmsInvoiceRows[rowIndex]) {
            tbmsInvoiceRows[rowIndex].ExternalPdfFile = '';
            tbmsInvoiceRows[rowIndex].PdfLink = inv.PdfLink || '';
        }
    }, 'json').fail(function () {
        $row.find('.tbms-inv-number').prop('disabled', false);
        alert('Unable to fetch invoice details.');
    });
}

function tbmsRenderInvoicePickerRows(invoices) {
    var html = '';
    (invoices || []).forEach(function (inv, index) {
        html += '<tr data-index="' + index + '">';
        html += '<td><input type="checkbox" class="tbms-picker-checkbox" value="' + tbmsEsc(inv.BillingNumber || '') + '"></td>';
        html += '<td>' + tbmsEsc(inv.BillingNumber || '') + '</td>';
        html += '<td class="small">' + tbmsEsc(inv.CompanyNames || '-') + '</td>';
        html += '<td>' + tbmsEsc(inv.InvoiceDate || '') + '</td>';
        html += '<td class="text-right">' + tbmsEsc(inv.TotalAmount || '0') + '</td>';
        html += '<td>' + tbmsEsc(inv.PaymentStatus || '-') + '</td>';
        html += '</tr>';
    });
    $('#invoice_picker_table tbody').html(html || '<tr><td colspan="6" class="text-center text-muted">No billing invoices found.</td></tr>');
    $('#tbms_picker_select_all').prop('checked', false);
}

function tbmsLoadInvoicePickerList(search) {
    var params = {};
    if (search) {
        params.Search = search;
    }
    $('#invoice_picker_table tbody').html('<tr><td colspan="6" class="text-center text-muted">Loading...</td></tr>');
    $.get('../tickets-billing/action/mail_scheduler_get_invoices.php', params, function (response) {
        if (response.error) {
            alert(response.message || 'Unable to load invoices');
            return;
        }
        tbmsAvailableInvoices = response.data || [];
        tbmsRenderInvoicePickerRows(tbmsAvailableInvoices);
    }, 'json');
}

function tbmsOpenInvoicePicker() {
    $('#tbms_invoice_picker_search').val('');
    tbmsLoadInvoicePickerList('');
    $('#invoicePickerModal').modal('show');
}

function tbmsSearchInvoicePicker() {
    tbmsLoadInvoicePickerList($('#tbms_invoice_picker_search').val().trim());
}

function tbmsAddSelectedInvoices() {
    var selectedNumbers = [];
    $('.tbms-picker-checkbox:checked').each(function () {
        selectedNumbers.push($(this).val());
    });
    if (!selectedNumbers.length) {
        alert('Select at least one invoice.');
        return;
    }
    var selected = tbmsAvailableInvoices.filter(function (inv) {
        return selectedNumbers.indexOf(inv.BillingNumber) >= 0;
    });
    var added = tbmsAppendInvoices(selected);
    if (!added) {
        alert('Selected invoice(s) are already in the table.');
        return;
    }
    $('#invoicePickerModal').modal('hide');
}
function tbmsRemoveInvoiceRow(index) {
    tbmsInvoiceRows.splice(index, 1);
    tbmsRenderInvoiceRows();
}

function tbmsSyncInvoiceRowFromDom(index) {
    var $row = $('#invoice_rows_table tbody tr[data-index="' + index + '"]');
    if (!$row.length || !tbmsInvoiceRows[index]) {
        return;
    }
    tbmsInvoiceRows[index] = {
        BillingNumber: $row.find('.tbms-inv-number').val(),
        InvoiceDate: $row.find('.tbms-inv-date').val(),
        TotalAmount: $row.find('.tbms-inv-amount').val(),
        DueDate: $row.find('.tbms-inv-due').val(),
        PONumber: $row.find('.tbms-inv-po').val(),
        PdfLink: $row.find('.tbms-inv-link').val(),
        ExternalPdfFile: $row.find('.tbms-inv-external-file').val()
    };
}

function tbmsUploadInvoicePdf(index) {
    tbmsSyncInvoiceRowFromDom(index);
    var $input = $('#tbms_invoice_pdf_upload');
    $input.data('row-index', index);
    $input.trigger('click');
}

function tbmsHandleInvoicePdfUpload(input) {
    var file = input.files && input.files[0];
    input.value = '';
    if (!file) {
        return;
    }

    var index = parseInt($(input).data('row-index'), 10);
    if (isNaN(index)) {
        return;
    }

    if ((file.type || '').toLowerCase() !== 'application/pdf' && !/\.pdf$/i.test(file.name || '')) {
        alert('Please choose a PDF file.');
        return;
    }

    var $row = $('#invoice_rows_table tbody tr[data-index="' + index + '"]');
    var $btn = $row.find('.tbms-upload-pdf-btn');
    var formData = new FormData();
    formData.append('pdf_file', file);

    $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

    $.ajax({
        url: '../tickets-billing/action/mail_scheduler_upload_invoice_pdf.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function (response) {
            if (response.error) {
                alert(response.message || 'Unable to upload PDF');
                return;
            }
            var data = response.data || {};
            $row.find('.tbms-inv-link').val(data.PdfLink || '');
            $row.find('.tbms-inv-external-file').val(data.ExternalPdfFile || '');
            if (tbmsInvoiceRows[index]) {
                tbmsInvoiceRows[index].PdfLink = data.PdfLink || '';
                tbmsInvoiceRows[index].ExternalPdfFile = data.ExternalPdfFile || '';
            }
            tbmsRenderInvoiceRows();
        },
        error: function () {
            alert('Unable to upload PDF.');
        },
        complete: function () {
            $btn.prop('disabled', false).html('<i class="fa fa-upload"></i>');
        }
    });
}

function tbmsOpenModal(scheduleId) {
    tbmsDestroyRichTextEditors();
    $('#scheduleForm')[0].reset();
    $('#modal_schedule_id').val(scheduleId || 0);
    tbmsResetInvoiceRows([]);
    tbmsUpdateDaySelector('');
    tbmsSetRichTextContent('', '');

    if (!scheduleId) {
        $('#modal_include_invoice_table').prop('checked', true);
        $('#modal_include_pdf_attachment').prop('checked', true);
        $('#modal_emails_per_week').val(2);
        $('#modal_email_subject').val('Billing Invoice Reminder');
        $('#modal_email_body').val('Dear Team,\n\nPlease find the billing invoice reminder details below.\n\nKindly process the payment before the due date.');
        tbmsShowScheduleModal();
        return;
    }

    $.get('../tickets-billing/action/mail_scheduler_get_schedule.php', { ScheduleID: scheduleId }, function (response) {
        if (response.error) {
            alert(response.message || 'Unable to load schedule');
            return;
        }
        var s = response.data;
        $('#modal_schedule_id').val(s.ID);
        $('#modal_schedule_name').val(s.ScheduleName || '');
        $('#modal_schedule_type').val(s.ScheduleType || 'Weekly');
        $('#modal_emails_per_week').val(s.EmailsPerWeek || 1);
        $('#modal_email_time').val((s.EmailTime || '09:00:00').substring(0, 5));
        $('#modal_start_date').val(s.StartDate || '');
        $('#modal_end_date').val(s.EndDate || '');
        $('#modal_status').val(s.Status || 'Active');
        $('#modal_email_subject').val(s.EmailSubject || '');
        $('#modal_email_to').val(s.EmailTo || '');
        $('#modal_email_cc').val(s.EmailCc || '');
        $('#modal_email_bcc').val(s.EmailBcc || '');
        $('#modal_billing_address').val(s.BillingAddress || '');
        $('#modal_shipping_address').val(s.ShippingAddress || '');
        $('#modal_email_body').val(s.EmailBody || '');
        tbmsSetRichTextContent(s.Remarks || '', s.TermsConditions || '');
        $('#modal_include_invoice_table').prop('checked', s.IncludeInvoiceTable == 1);
        $('#modal_include_pdf_attachment').prop('checked', s.IncludePdfAttachment == 1);
        tbmsUpdateDaySelector(s.EmailDays || '');
        tbmsResetInvoiceRows(s.invoices || []);
        tbmsShowScheduleModal(function () {
            tbmsSetRichTextContent(s.Remarks || '', s.TermsConditions || '');
        });
    }, 'json');
}

function tbmsBuildPayload() {
    tbmsSyncRichTextEditors();
    return {
        ScheduleID: parseInt($('#modal_schedule_id').val(), 10) || 0,
        ScheduleName: $('#modal_schedule_name').val().trim(),
        ScheduleType: $('#modal_schedule_type').val(),
        EmailsPerWeek: $('#modal_emails_per_week').val(),
        EmailDays: tbmsGetSelectedDays(),
        EmailTime: $('#modal_email_time').val(),
        StartDate: $('#modal_start_date').val(),
        EndDate: $('#modal_end_date').val(),
        Status: $('#modal_status').val(),
        IsActive: $('#modal_status').val() === 'Active' ? 1 : 0,
        EmailSubject: $('#modal_email_subject').val().trim(),
        EmailTo: $('#modal_email_to').val().trim(),
        EmailCc: $('#modal_email_cc').val().trim(),
        EmailBcc: $('#modal_email_bcc').val().trim(),
        BillingAddress: $('#modal_billing_address').val().trim(),
        ShippingAddress: $('#modal_shipping_address').val().trim(),
        EmailBody: $('#modal_email_body').val().trim(),
        Remarks: $('#modal_remarks').val().trim(),
        TermsConditions: $('#modal_terms').val().trim(),
        IncludeInvoiceTable: $('#modal_include_invoice_table').is(':checked') ? 1 : 0,
        IncludePdfAttachment: $('#modal_include_pdf_attachment').is(':checked') ? 1 : 0,
        invoices: tbmsCollectInvoiceRows()
    };
}

function tbmsSaveSchedule(sendNow) {
    var payload = tbmsBuildPayload();
    if (!payload.ScheduleName) {
        alert('Schedule name is required.');
        return;
    }
    if (!payload.EmailTo) {
        alert('At least one To email is required.');
        return;
    }
    if (!payload.EmailSubject) {
        alert('Email subject is required.');
        return;
    }

    $.ajax({
        url: '../tickets-billing/action/mail_scheduler_save_schedule.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(payload),
        dataType: 'json',
        success: function (response) {
            if (response.error) {
                alert(response.message || 'Unable to save schedule');
                return;
            }
            var scheduleId = response.ScheduleID;
            if (sendNow && scheduleId) {
                tbmsRunNow(scheduleId, function () {
                    $('#scheduleModal').modal('hide');
                    tbmsLoadSchedules();
                });
            } else {
                $('#scheduleModal').modal('hide');
                tbmsLoadSchedules();
                alert('Schedule saved successfully.');
            }
        },
        error: function () {
            alert('Unable to save schedule.');
        }
    });
}

function tbmsRunNow(scheduleId, callback) {
    $.post('../tickets-billing/action/mail_scheduler_run_now.php', { ScheduleID: scheduleId }, function (response) {
        if (response.error) {
            alert(response.message || 'Send failed');
            return;
        }
        alert('Mail sent to ' + (response.sent || 0) + ' recipient(s).');
        if (typeof callback === 'function') callback();
        else tbmsLoadSchedules();
    }, 'json');
}

function tbmsUpdateStatus(scheduleId, status) {
    $.post('../tickets-billing/action/mail_scheduler_update_status.php', { ScheduleID: scheduleId, Status: status }, function (response) {
        if (response.error) {
            alert(response.message || 'Unable to update status');
            return;
        }
        tbmsLoadSchedules();
    }, 'json');
}

function tbmsDeleteSchedule(scheduleId) {
    if (!confirm('Delete this schedule permanently?')) return;
    $.post('../tickets-billing/action/mail_scheduler_delete_schedule.php', { ScheduleID: scheduleId }, function (response) {
        if (response.error) {
            alert(response.message || 'Unable to delete schedule');
            return;
        }
        tbmsLoadSchedules();
    }, 'json');
}

function tbmsLoadLogs(scheduleId) {
    $.get('../tickets-billing/action/mail_scheduler_get_logs.php', { ScheduleID: scheduleId || 0, Limit: 200 }, function (response) {
        if (response.error) {
            alert(response.message || 'Unable to load logs');
            return;
        }
        var html = '';
        (response.data || []).forEach(function (log) {
            html += '<tr>';
            html += '<td>' + tbmsEsc((log.EmailSentDate || '') + ' ' + (log.EmailSentTime || '')) + '</td>';
            html += '<td>' + tbmsEsc(log.ScheduleName || log.ScheduleID) + '</td>';
            html += '<td>' + tbmsEsc(log.RecipientEmail) + '</td>';
            html += '<td>' + tbmsEsc(log.EmailSubject) + '</td>';
            html += '<td>' + tbmsEsc(log.Status) + '</td>';
            html += '<td>' + tbmsEsc(log.InvoiceCount) + '</td>';
            html += '<td>' + tbmsEsc(log.ErrorMessage || '') + '</td>';
            html += '</tr>';
        });
        $('#mail_logs_table tbody').html(html || '<tr><td colspan="7" class="text-center">No logs yet.</td></tr>');
        $('#logs_panel').show();
    }, 'json');
}

$(document).ready(function () {
    tbmsLoadSchedules();

    $('#scheduleModal').on('hidden.bs.modal', function () {
        tbmsDestroyRichTextEditors();
    });

    $(document).on('blur', '#invoice_rows_table .tbms-inv-number', function () {
        var $row = $(this).closest('tr');
        var billingNumber = $(this).val().trim();
        if (!billingNumber) {
            return;
        }
        if ($row.find('.tbms-inv-external-file').val().trim()) {
            return;
        }
        var currentLink = $row.find('.tbms-inv-link').val().trim();
        var currentDate = $row.find('.tbms-inv-date').val().trim();
        if (currentLink && currentDate) {
            return;
        }
        tbmsFetchInvoiceByNumber(billingNumber, $row);
    });

    $(document).on('click', '#invoice_rows_table .tbms-upload-pdf-btn', function () {
        var index = parseInt($(this).closest('tr').attr('data-index'), 10);
        if (!isNaN(index)) {
            tbmsUploadInvoicePdf(index);
        }
    });

    $(document).on('change', '#tbms_invoice_pdf_upload', function () {
        tbmsHandleInvoicePdfUpload(this);
    });

    $(document).on('keydown', '#invoice_rows_table .tbms-inv-number', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $(this).blur();
        }
    });

    $('#tbms_invoice_picker_search').on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            tbmsSearchInvoicePicker();
        }
    });

    $(document).on('change', '#tbms_picker_select_all', function () {
        $('.tbms-picker-checkbox').prop('checked', $(this).is(':checked'));
    });
});
