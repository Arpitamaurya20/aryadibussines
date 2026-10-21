(function ($, FP) {
    "use strict";

    var esc = FP.esc;
    var STATUS_CLASS = {
        draft: "secondary",
        submitted: "info",
        returned: "warning",
        approved: "success",
        rejected: "danger",
        cancelled: "dark"
    };
    var table;
    var searchTimer = null;

    function words(value) {
        value = String(value || "").replace(/_/g, " ");
        return value.charAt(0).toUpperCase() + value.slice(1);
    }

    function filterParams() {
        var params = {};
        $.each($("#fp-filters").serializeArray(), function (_, field) {
            if (field.value !== "") {
                params[field.name] = field.value;
            }
        });
        return params;
    }

    function statusCell(row) {
        var html = '<span class="badge badge-' + (STATUS_CLASS[row.Status] || "secondary") + '">' + esc(row.StatusLabel) + "</span>";
        if (row.Status === "approved") {
            html += '<div class="small text-muted">Payment: ' + esc(words(row.PaymentStatus)) + "</div>";
            if (row.SettlementStatus && row.SettlementStatus !== "not_applicable") {
                html += '<div class="small text-muted">Settlement: ' + esc(words(row.SettlementStatus)) + "</div>";
            }
        }
        return html;
    }

    function activityCell(row) {
        var last = row.LastActivity;
        if (!last) {
            return '<span class="text-muted">-</span>';
        }
        return esc(last.Label) + '<div class="small text-muted">' + esc(last.By) + ", " + esc(last.At) + "</div>";
    }

    function reloadTable() {
        table.ajax.reload();
    }

    $(function () {
        FP.activateNav("", "expense_details");

        table = $("#fp-details-table").DataTable({
            serverSide: true,
            processing: true,
            searching: false,
            ordering: false,
            responsive: true,
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            ajax: {
                url: "action/list_expense_requests.php",
                type: "GET",
                data: function (d) {
                    return $.extend({ draw: d.draw, start: d.start, length: d.length }, filterParams());
                },
                error: function (xhr) {
                    $("#fp-details-table_processing").hide();
                    FP.alertFailure(xhr);
                }
            },
            columns: [
                { data: "ExpenseNumber", render: function (v, t, row) { return '<a href="view-expense-request.php?id=' + row.ID + '"><strong>' + esc(v) + "</strong></a><div class=\"small text-muted\">" + esc(row.TypeLabel) + "</div>"; } },
                { data: "EmployeeName", render: function (v, t, row) { return esc(v) + (row.IsMine ? ' <span class="badge badge-light">you</span>' : ""); } },
                { data: "Office", render: function (v, t, row) { return esc(v) + '<div class="small text-muted">' + esc(row.StateName) + "</div>"; } },
                { data: "Purpose", className: "fp-purpose", render: function (v) { return esc(v.length > 100 ? v.substring(0, 97) + "..." : v); } },
                { data: "TotalAmount", className: "text-right text-nowrap", render: function (v, t, row) { return esc(FP.formatMoney(v)) + '<div class="small text-muted">' + row.ItemCount + " line(s)</div>"; } },
                { data: "StatusLabel", render: function (v, t, row) { return statusCell(row); } },
                { data: "LastActivity", render: function (v, t, row) { return activityCell(row); } },
                { data: null, className: "text-nowrap", render: function (v, t, row) { return '<a class="btn btn-xs btn-outline-primary" href="view-expense-request.php?id=' + row.ID + '">Details</a>'; } }
            ],
            language: { emptyTable: "No expense requests found.", zeroRecords: "No expense requests match these filters." }
        });

        $("#fp-filters").on("change", "select, input[type=date]", reloadTable);
        $("#fp-f-q").on("input", function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(reloadTable, 350);
        });
    });
})(jQuery, window.FP);
