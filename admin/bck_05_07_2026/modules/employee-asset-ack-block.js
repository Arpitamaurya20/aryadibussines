(function () {
    function shouldSkipAssetAckBlock() {
        var path = window.location.pathname || '';
        if (path.indexOf('employee-asset-acknowledgement') !== -1) {
            return true;
        }
        if (path.indexOf('authentication/login') !== -1) {
            return true;
        }
        if (path.indexOf('employee-asset-ack-form.php') !== -1) {
            return true;
        }
        return false;
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function buildBlockingModalHtml(employee, assets) {
        var cards = '';
        assets.forEach(function (asset) {
            cards += '<div class="employee-asset-ack-card">'
                + '<div class="employee-asset-ack-card-row">'
                + '<span class="employee-asset-ack-card-label">Select</span>'
                + '<span class="employee-asset-ack-card-value">'
                + '<input type="checkbox" class="asset-ack-check" value="' + asset.id + '" checked>'
                + '</span>'
                + '</div>'
                + '<div class="employee-asset-ack-card-row"><span class="employee-asset-ack-card-label">Category</span><span class="employee-asset-ack-card-value">' + escapeHtml(asset.category) + '</span></div>'
                + '<div class="employee-asset-ack-card-row"><span class="employee-asset-ack-card-label">Asset Details</span><span class="employee-asset-ack-card-value">' + escapeHtml(asset.description) + '</span></div>'
                + '<div class="employee-asset-ack-card-row"><span class="employee-asset-ack-card-label">Serial / Asset ID</span><span class="employee-asset-ack-card-value">' + escapeHtml(asset.serial || '-') + '</span></div>'
                + '<div class="employee-asset-ack-card-row"><span class="employee-asset-ack-card-label">Allocation Date</span><span class="employee-asset-ack-card-value">' + escapeHtml(asset.allocation_date) + '</span></div>'
                + '</div>';
        });

        return '<style>'
            + '#employee_asset_ack_block_modal .modal-dialog { margin: 10px auto; max-width: 720px; width: calc(100% - 20px); }'
            + '#employee_asset_ack_block_modal .modal-content { border-radius: 10px; overflow: hidden; }'
            + '#employee_asset_ack_block_modal .modal-body { max-height: calc(100vh - 220px); overflow-y: auto; -webkit-overflow-scrolling: touch; }'
            + '#employee_asset_ack_block_modal .employee-asset-ack-card { border: 1px solid #e9ecef; border-radius: 10px; padding: 12px; margin-bottom: 12px; background: #fff; }'
            + '#employee_asset_ack_block_modal .employee-asset-ack-card-row { display: flex; flex-direction: column; gap: 4px; padding: 6px 0; border-bottom: 1px solid #f3f4f6; }'
            + '#employee_asset_ack_block_modal .employee-asset-ack-card-row:last-child { border-bottom: 0; }'
            + '#employee_asset_ack_block_modal .employee-asset-ack-card-label { font-weight: 600; color: #495057; font-size: .78rem; text-transform: uppercase; letter-spacing: .03em; }'
            + '#employee_asset_ack_block_modal .employee-asset-ack-card-value { word-break: break-word; }'
            + '#employee_asset_ack_block_modal .custom-control { min-height: 44px; padding-left: 2rem; }'
            + '#employee_asset_ack_block_modal .custom-control-label { line-height: 1.45; padding-top: 8px; }'
            + '#employee_asset_ack_block_modal .modal-footer .btn { min-height: 44px; width: 100%; }'
            + '@media (min-width: 768px) {'
            + '#employee_asset_ack_block_modal .employee-asset-ack-card-row { flex-direction: row; justify-content: space-between; gap: 12px; }'
            + '#employee_asset_ack_block_modal .employee-asset-ack-card-label { flex: 0 0 38%; max-width: 38%; text-transform: none; font-size: .92rem; }'
            + '#employee_asset_ack_block_modal .employee-asset-ack-card-value { text-align: right; }'
            + '#employee_asset_ack_block_modal .modal-footer .btn { width: auto; }'
            + '}'
            + '</style>'
            + '<div class="modal fade" id="employee_asset_ack_block_modal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">'
            + '<div class="modal-dialog modal-dialog-scrollable" role="document">'
            + '<div class="modal-content">'
            + '<div class="modal-header" style="background:#003f88;color:#fff;">'
            + '<h5 class="modal-title">Company Asset Acknowledgement Required</h5>'
            + '</div>'
            + '<div class="modal-body">'
            + '<div class="alert alert-warning">'
            + '<strong>Action required:</strong> Please confirm that the company assets listed below are assigned to you and currently in your possession. You cannot use the portal until acknowledgement is completed.'
            + '</div>'
            + '<p class="mb-2"><strong>Employee:</strong> ' + escapeHtml(employee.name) + '</p>'
            + '<div class="employee-asset-ack-list">' + cards + '</div>'
            + '<div class="custom-control custom-checkbox mt-3">'
            + '<input type="checkbox" class="custom-control-input" id="employee_asset_ack_consent">'
            + '<label class="custom-control-label" for="employee_asset_ack_consent">'
            + 'I confirm that the assets listed above belong to me and are currently in my possession.'
            + '</label>'
            + '</div>'
            + '</div>'
            + '<div class="modal-footer">'
            + '<button type="button" class="btn btn-primary" id="btn_submit_asset_ack_block">Submit Acknowledgement</button>'
            + '</div>'
            + '</div></div></div>';
    }

    function showBlockingModal(employee, assets) {
        if ($('#employee_asset_ack_block_modal').length) {
            return;
        }
        $('body').append(buildBlockingModalHtml(employee, assets));
        var $modal = $('#employee_asset_ack_block_modal');
        $modal.modal({ backdrop: 'static', keyboard: false, show: true });

        $modal.on('hidden.bs.modal', function () {
            if ($('.asset-ack-check:checked').length > 0) {
                $modal.modal({ backdrop: 'static', keyboard: false, show: true });
            }
        });

        $('#btn_submit_asset_ack_block').on('click', function () {
            if (!$('#employee_asset_ack_consent').is(':checked')) {
                TechXAlert('Please confirm possession of all listed assets.', 'warning');
                return;
            }
            var ids = [];
            $('.asset-ack-check:checked').each(function () {
                ids.push(parseInt($(this).val(), 10));
            });
            if (!ids.length) {
                TechXAlert('Select at least one asset to acknowledge.', 'warning');
                return;
            }
            $.post('../employee-asset-acknowledgement/action/acknowledge_assets.php', {
                asset_ids: JSON.stringify(ids),
                consent: '1'
            }, function (data) {
                var res = (typeof data === 'object') ? data : JSON.parse(data);
                if (res.error) {
                    TechXAlert(res.message || 'Acknowledgement failed.', 'error');
                    return;
                }
                TechXAlert(res.message || 'Acknowledged successfully.', 'success');
                $modal.modal('hide');
                $('#employee_asset_ack_block_modal').remove();
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open');
            });
        });
    }

    function checkPendingAssetAcknowledgements() {
        if (shouldSkipAssetAckBlock()) {
            return;
        }
        $.getJSON('../employee-asset-acknowledgement/ajax/get_pending_acknowledgements.php', function (response) {
            if (!response || response.error || !response.has_pending) {
                return;
            }
            if (!response.assets || !response.assets.length || !response.employee) {
                return;
            }
            showBlockingModal(response.employee, response.assets);
        });
    }

    $(document).ready(function () {
        checkPendingAssetAcknowledgements();
    });
})();
