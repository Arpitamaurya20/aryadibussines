(function ($) {
    'use strict';

    function parseJsonResponse(data) {
        if (typeof data === 'object') return data;
        try { return JSON.parse(data); } catch (e) { return { error: true, message: 'Invalid response.' }; }
    }

    $(function () {
        if ($('#hr_assigned_to').length && $.fn.select2) {
            $('#hr_assigned_to').select2({ width: '100%', placeholder: 'Select HR representative' });
        }

        $('#hr_assignment_form').on('submit', function (e) {
            e.preventDefault();
            var $btn = $('#hr_assignment_save_btn');
            $btn.prop('disabled', true);
            $.post('action/update-hr-ticket.php', $(this).serialize(), function (raw) {
                var res = parseJsonResponse(raw);
                if (res.error) {
                    alertify.error(res.message || 'Update failed.');
                } else {
                    alertify.success(res.message || 'Updated.');
                    window.location.reload();
                }
                $btn.prop('disabled', false);
            }).fail(function () {
                alertify.error('Request failed.');
                $btn.prop('disabled', false);
            });
        });

        $('#hr_add_comment_form').on('submit', function (e) {
            e.preventDefault();
            var $btn = $('#hr_comment_submit_btn');
            $btn.prop('disabled', true);
            $.post('action/add-comment.php', $(this).serialize(), function (raw) {
                var res = parseJsonResponse(raw);
                if (res.error) {
                    alertify.error(res.message || 'Could not send comment.');
                    $btn.prop('disabled', false);
                    return;
                }
                alertify.success(res.message || 'Comment sent.');
                window.location.reload();
            }).fail(function () {
                alertify.error('Request failed.');
                $btn.prop('disabled', false);
            });
        });

        $('#hr_upload_attachment_form').on('submit', function (e) {
            e.preventDefault();
            var $btn = $('#hr_upload_btn');
            var formData = new FormData(this);
            $btn.prop('disabled', true);
            $.ajax({
                url: 'action/upload-attachment.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (raw) {
                    var res = parseJsonResponse(raw);
                    if (res.error) {
                        alertify.error(res.message || 'Upload failed.');
                        $btn.prop('disabled', false);
                        return;
                    }
                    alertify.success(res.message || 'Uploaded.');
                    window.location.reload();
                },
                error: function () {
                    alertify.error('Upload failed.');
                    $btn.prop('disabled', false);
                }
            });
        });

        $('#hr_close_ticket_btn').on('click', function () {
            var ticketId = $(this).data('ticket-id');
            if (!ticketId) return;
            alertify.confirm('Close ticket', 'Confirm this ticket is resolved and can be closed?', function () {
                $.post('action/close-hr-ticket.php', { ticket_id: ticketId }, function (raw) {
                    var res = parseJsonResponse(raw);
                    if (res.error) {
                        alertify.error(res.message || 'Could not close ticket.');
                        return;
                    }
                    alertify.success(res.message || 'Ticket closed.');
                    window.location.reload();
                });
            }, function () {});
        });
    });
}(jQuery));
