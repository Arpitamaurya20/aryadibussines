(function () {
    var base = '../employee-leave-mgmt/action/';

    function formToObject($form) {
        var o = {};
        var isHalf = $('#elm_duration').val().indexOf('Half') >= 0;
        $form.serializeArray().forEach(function (item) {
            if (item.name === 'HalfDaySession' && !isHalf) {
                return;
            }
            o[item.name] = item.value;
        });
        return o;
    }

    function showValidateResult(res) {
        var $box = $('#elm_validate_result');
        $box.removeClass('d-none alert-success alert-danger');
        if (res.error) {
            $box.addClass('alert-danger').text(res.message || 'Validation failed.');
        } else {
            var days = res.days_to_deduct || (res.calculation && res.calculation.total_days) || 0;
            var avail = res.balance_available;
            var after = res.balance_after;
            var msg = res.message + ' Days: ' + days;
            if (res.is_unpaid) {
                msg += '. No CL/SL balance required.';
            } else if (typeof avail === 'number') {
                msg += '. ' + ($('#elm_type').val() || 'Leave') + ' balance: ' + avail + ' available';
                if (typeof after === 'number') {
                    msg += ', ' + after + ' will remain after approval';
                }
            }
            if (res.is_advance) msg += ' (includes advance leave)';
            if (res.calculation && res.calculation.sandwich_extra > 0) {
                msg += '. Sandwich rule adds ' + res.calculation.sandwich_extra + ' day(s).';
            }
            $box.addClass('alert-success').text(msg);
        }
    }

    $('#elm_duration').on('change', function () {
        var half = $(this).val().indexOf('Half') >= 0;
        $('#elm_half_wrap').toggleClass('d-none', !half);
        $('#elm_half_session').prop('disabled', !half);
        if (half) $('#elm_to').val($('#elm_from').val());
    });
    $('#elm_half_session').prop('disabled', true);
    $('#elm_from').on('change', function () {
        if ($('#elm_duration').val().indexOf('Half') >= 0) {
            $('#elm_to').val($(this).val());
        }
    });

    $('#elm_btn_validate').on('click', function () {
        $.post(base + 'validate-leave.php', formToObject($('#elm_apply_form')), showValidateResult, 'json');
    });

    $('#elm_btn_apply').on('click', function () {
        var $btn = $(this).prop('disabled', true);
        $.post(base + 'apply-leave.php', formToObject($('#elm_apply_form')), function (res) {
            $btn.prop('disabled', false);
            if (res.error) {
                alert(res.message || 'Could not apply leave.');
            } else {
                alert(res.message || 'Leave applied.');
                location.reload();
            }
        }, 'json').fail(function (xhr) {
            $btn.prop('disabled', false);
            var msg = 'Request failed.';
            try {
                var r = JSON.parse(xhr.responseText);
                if (r && r.message) msg = r.message;
            } catch (e) {}
            alert(msg);
        });
    });

    $('.elm-cancel-btn').on('click', function () {
        if (!confirm('Cancel this leave request?')) return;
        var id = $(this).data('id');
        $.post(base + 'cancel-leave.php', { leave_id: id }, function (res) {
            alert(res.message || (res.error ? 'Error' : 'Done'));
            if (!res.error) location.reload();
        }, 'json');
    });

    $('#elm_btn_compoff').on('click', function () {
        var $btn = $(this).prop('disabled', true);
        $.post(base + 'request-comp-off.php', formToObject($('#elm_compoff_form')), function (res) {
            $btn.prop('disabled', false);
            alert(res.message || (res.error ? 'Error' : 'Submitted'));
            if (!res.error) location.reload();
        }, 'json');
    });
})();
