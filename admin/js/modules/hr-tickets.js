(function ($) {
    'use strict';

    function parseJsonResponse(data) {
        if (typeof data === 'object') return data;
        try { return JSON.parse(data); } catch (e) { return { error: true, message: 'Invalid response.' }; }
    }

    function initDataTable(selector) {
        if (!$(selector).length || !$.fn.DataTable) return;
        $(selector).DataTable({
            order: [[0, 'desc']],
            pageLength: 25,
            responsive: true,
            dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
        });
    }

    function togglePaymentRef() {
        var cat = $('#hr_ticket_category').val();
        var isPayment = cat === 'payment_related';
        $('#hr_payment_ref_wrap').toggle(isPayment);
        $('#hr_payment_reference').prop('required', isPayment);
        if (isPayment) {
            $('#hr_ticket_priority').val('high');
        }
    }

    $(function () {
        initDataTable('#hr_my_tickets_table');
        initDataTable('#hr_manage_tickets_table');

        $('#hr_btn_raise_ticket').on('click', function () {
            $('#hr_raise_ticket_form')[0].reset();
            togglePaymentRef();
            $('#hr_raise_ticket_modal').modal('show');
        });

        $('#hr_ticket_category').on('change', togglePaymentRef);

        $('#hr_raise_ticket_form').on('submit', function (e) {
            e.preventDefault();
            var $btn = $('#hr_raise_ticket_btn');
            $btn.prop('disabled', true);
            $.post('action/save-hr-ticket.php', $(this).serialize(), function (raw) {
                var res = parseJsonResponse(raw);
                if (res.error) {
                    alertify.error(res.message || 'Could not raise ticket.');
                    $btn.prop('disabled', false);
                    return;
                }
                alertify.success(res.message || 'Ticket raised.');
                $('#hr_raise_ticket_modal').modal('hide');
                if (res.id) {
                    window.location.href = 'view-hr-ticket-detail?t=' + btoa(String(res.id)).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
                } else {
                    window.location.reload();
                }
            }).fail(function () {
                alertify.error('Request failed.');
                $btn.prop('disabled', false);
            });
        });
    });
}(jQuery));
