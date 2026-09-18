var convenienceDashboardSummaryUrl = '../employees-convenience/ajax/convenience_dashboard_summary.php';

function convenienceDashboardFormatAmount(amount) {
    var value = parseFloat(amount || 0);
    if (isNaN(value) || !isFinite(value) || value < 0) {
        value = 0;
    }
    if (value > 1000000000) {
        value = 1000000000;
    }
    return 'Rs. ' + value.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function convenienceDashboardSetText(id, value) {
    var el = document.getElementById(id);
    if (el) {
        el.textContent = value;
    }
}

function convenienceDashboardApplyScopeVisibility(scope) {
    var $row = $('.convenience-dashboard-cards');
    if (!$row.length) {
        return;
    }

    var showAll = {
        'card-total': true,
        'card-pending-supervisor': scope === 'supervisor',
        'card-supervisor-timeout': scope === 'hr' || scope === 'supervisor',
        'card-pending-hr': scope === 'hr' || scope === 'supervisor',
        'card-pending-payment': true,
        'card-payment-done': true,
        'card-rejected': true
    };

    Object.keys(showAll).forEach(function (className) {
        $row.find('.' + className).toggle(!!showAll[className]);
    });
}

function renderConvenienceDashboard(data, scope) {
    if (!data || data.error || !data.summary) {
        return;
    }

    var summary = data.summary;
    var formatted = data.formatted || {};

    convenienceDashboardSetText('conv_dash_total_count', summary.total_count || 0);
    convenienceDashboardSetText('conv_dash_total_amount', formatted.total_amount || convenienceDashboardFormatAmount(summary.total_amount));

    convenienceDashboardSetText('conv_dash_pending_supervisor_count', summary.pending_supervisor_count || 0);
    convenienceDashboardSetText('conv_dash_pending_supervisor_amount', formatted.pending_supervisor_amount || convenienceDashboardFormatAmount(summary.pending_supervisor_amount));

    convenienceDashboardSetText('conv_dash_supervisor_timeout_count', summary.supervisor_timeout_count || 0);
    convenienceDashboardSetText('conv_dash_supervisor_timeout_amount', formatted.supervisor_timeout_amount || convenienceDashboardFormatAmount(summary.supervisor_timeout_amount));

    convenienceDashboardSetText('conv_dash_pending_hr_count', summary.pending_hr_count || 0);
    convenienceDashboardSetText('conv_dash_pending_hr_amount', formatted.pending_hr_amount || convenienceDashboardFormatAmount(summary.pending_hr_amount));

    convenienceDashboardSetText('conv_dash_pending_payment_count', summary.pending_payment_count || 0);
    convenienceDashboardSetText('conv_dash_pending_payment_amount', formatted.pending_payment_amount || convenienceDashboardFormatAmount(summary.pending_payment_amount));

    convenienceDashboardSetText('conv_dash_payment_done_count', summary.payment_done_count || 0);
    convenienceDashboardSetText('conv_dash_payment_done_amount', formatted.payment_done_amount || convenienceDashboardFormatAmount(summary.payment_done_amount));

    convenienceDashboardSetText('conv_dash_rejected_count', summary.rejected_count || 0);
    convenienceDashboardSetText('conv_dash_rejected_amount', formatted.rejected_amount || convenienceDashboardFormatAmount(summary.rejected_amount));

    convenienceDashboardApplyScopeVisibility(scope || ($('.convenience-dashboard-cards').data('scope') || 'admin'));

    var $loading = $('#convenience_dashboard_loading');
    if ($loading.length) {
        $loading.text('Updated').removeClass('badge-light').addClass('badge-success');
        setTimeout(function () {
            $loading.text('Live').removeClass('badge-success').addClass('badge-light');
        }, 1500);
    }
}

function loadConvenienceDashboard(scope, queryString) {
    if (!document.getElementById('convenience_dashboard_panel')) {
        return;
    }

    var params = queryString || '';
    if (params.indexOf('?') !== 0) {
        params = '?' + params;
    }
    if (params.indexOf('scope=') === -1) {
        params += (params.length > 1 ? '&' : '') + 'scope=' + encodeURIComponent(scope || 'admin');
    }

    var $loading = $('#convenience_dashboard_loading');
    if ($loading.length) {
        $loading.text('Updating...').removeClass('badge-success').addClass('badge-light');
    }

    $.getJSON(convenienceDashboardSummaryUrl + params, function (data) {
        renderConvenienceDashboard(data, scope);
    }).fail(function () {
        if ($loading.length) {
            $loading.text('Update failed').removeClass('badge-success').addClass('badge-danger');
        }
    });
}

function buildConvenienceDashboardQueryFromParams(paramString) {
    if (!paramString) {
        return '?scope=admin';
    }
    return paramString.indexOf('?') === 0 ? paramString : '?' + paramString;
}
