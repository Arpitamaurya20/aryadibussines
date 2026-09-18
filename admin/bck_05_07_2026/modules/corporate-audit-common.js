/* Shared Select2 + offcanvas filter helpers for Corporate Audit module */

function caDestroySelect2(selector) {
    var $els = selector ? $(selector) : $('.ca-audit-select');
    $els.each(function () {
        if ($(this).data('select2')) {
            $(this).select2('destroy');
        }
    });
}

function caInitSelect2(selector, dropdownParent) {
    var $els = selector ? $(selector) : $('.ca-audit-select');
    $els.each(function () {
        var $el = $(this);
        if ($el.data('select2')) {
            return;
        }
        $el.select2({
            placeholder: $el.data('placeholder') || 'Select or search...',
            allowClear: !$el.prop('multiple'),
            width: '100%',
            dropdownParent: dropdownParent ? $(dropdownParent) : $(document.body)
        });
    });
}

function caSyncSelect2(selector) {
    var $els = selector ? $(selector) : $('.ca-audit-select');
    $els.each(function () {
        var $el = $(this);
        if ($el.data('select2')) {
            $el.trigger('change.select2');
        }
    });
}

function caCloseSelect2(selector) {
    var $els = selector ? $(selector) : $('.ca-audit-select');
    $els.each(function () {
        var $el = $(this);
        if ($el.data('select2')) {
            $el.select2('close');
        }
    });
}

function caToggleFilterCanvas() {
    var willOpen = !$('#ca_filter_offcanvas').hasClass('show');
    if (willOpen) {
        caInitSelect2('#ca_filter_offcanvas .ca-audit-select', document.body);
    }
    $('#ca_filter_offcanvas').toggleClass('show', willOpen);
    $('#ca_filter_offcanvas_backdrop').toggleClass('show', willOpen);
    $('body').toggleClass('ca-audit-filters-open', willOpen);
}

function caCloseFilterCanvas() {
    caCloseSelect2('#ca_filter_offcanvas .ca-audit-select');
    $('#ca_filter_offcanvas').removeClass('show');
    $('#ca_filter_offcanvas_backdrop').removeClass('show');
    $('body').removeClass('ca-audit-filters-open');
}

function caBindFilterCanvasUi() {
    $('#ca_filter_offcanvas_backdrop').on('mousedown', function (e) {
        if ($(e.target).is('#ca_filter_offcanvas_backdrop')) {
            caCloseFilterCanvas();
        }
    });
}

function caBindModalSelect2Ui(modalSelector) {
    $(modalSelector)
        .on('show.bs.modal', function () {
            caCloseSelect2('#ca_filter_offcanvas .ca-audit-select');
            caInitSelect2($(this).find('.ca-audit-select'), document.body);
        })
        .on('hidden.bs.modal', function () {
            caDestroySelect2($(this).find('.ca-audit-select'));
            caSyncSelect2('#ca_filter_offcanvas .ca-audit-select');
        });
}

function caUpdateFilterSummary(summarySelector, parts) {
    var text = 'All records';
    if (parts && parts.length) {
        text = parts.join(' · ');
    }
    $(summarySelector).text(text);
}

function caFilterSubAuditOptions(masterSelectId, subSelectId, resetValue) {
    var masterId = $('#' + masterSelectId).val() || '0';
    var $sub = $('#' + subSelectId);
    var currentVal = resetValue ? '0' : ($sub.val() || '0');
    var hasCurrent = false;

    $sub.find('option').each(function () {
        var $opt = $(this);
        if ($opt.val() === '0' || $opt.val() === '') {
            $opt.prop('disabled', false).show();
            return;
        }
        var optMaster = String($opt.data('master') || '');
        var show = masterId === '0' || optMaster === String(masterId);
        $opt.prop('disabled', !show);
        if (show && $opt.val() === currentVal) {
            hasCurrent = true;
        }
    });

    if (!hasCurrent) {
        $sub.val('0');
    }
    caSyncSelect2('#' + subSelectId);
}

function caResetFilterFields(fieldIds) {
    fieldIds.forEach(function (id) {
        var $el = $('#' + id);
        if ($el.length) {
            $el.val($el.find('option:first').val() || '0').trigger('change');
        }
    });
}
