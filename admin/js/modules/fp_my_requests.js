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
    var PAYMENT_LABEL = { unpaid: "Unpaid", partially_paid: "Partly paid", paid: "Paid" };
    var table;
    var searchTimer = null;

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
            html += '<div class="small text-muted">' + esc(PAYMENT_LABEL[row.PaymentStatus] || row.PaymentStatus) + "</div>";
        }
        return html;
    }

    function actionsCell(row) {
        var html = '<a class="btn btn-xs btn-outline-primary mr-1" href="view-expense-request.php?id=' + row.ID + '">View</a>';
        if (row.CanEdit) {
            html += '<a class="btn btn-xs btn-primary" href="view-expense-request-form.php?id=' + row.ID + '">' + (row.Status === "returned" ? "Revise" : "Edit") + "</a>";
        }
        return html;
    }

    function reloadTable() {
        table.ajax.reload();
    }

    $(function () {
        FP.activateNav("booking", "my_requests");

        table = $("#fp-request-table").DataTable({
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
                    $("#fp-request-table_processing").hide();
                    FP.alertFailure(xhr);
                }
            },
            columns: [
                { data: "ExpenseNumber", render: function (v, t, row) { return '<a href="view-expense-request.php?id=' + row.ID + '"><strong>' + esc(v) + "</strong></a>"; } },
                { data: "TypeLabel", render: function (v) { return esc(v); } },
                { data: "EmployeeName", render: function (v, t, row) { return esc(v) + (row.IsMine ? ' <span class="badge badge-light">you</span>' : ""); } },
                { data: "Office", render: function (v, t, row) { return esc(v) + '<div class="small text-muted">' + esc(row.StateName) + "</div>"; } },
                { data: "Purpose", className: "fp-purpose", render: function (v) { return esc(v.length > 120 ? v.substring(0, 117) + "..." : v); } },
                { data: "TotalAmount", className: "text-right text-nowrap", render: function (v, t, row) { return esc(FP.formatMoney(v)) + '<div class="small text-muted">' + row.ItemCount + " line(s)</div>"; } },
                { data: "StatusLabel", render: function (v, t, row) { return statusCell(row); } },
                { data: "CreatedAt", className: "text-nowrap", render: function (v, t, row) { return esc(v) + (row.SubmittedAt ? '<div class="small text-muted">Submitted ' + esc(row.SubmittedAt) + "</div>" : ""); } },
                { data: null, className: "text-nowrap", render: function (v, t, row) { return actionsCell(row); } }
            ],
            language: { emptyTable: "No expense requests found.", zeroRecords: "No expense requests match these filters." }
        });

        $("#fp-filters").on("change", "select, input[type=date]", reloadTable);
        $("#fp-f-q").on("input", function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(reloadTable, 350);
        });
        $("#fp-f-reset").on("click", function () {
            $("#fp-filters").find("select:not(#fp-f-view), input[type=date], input[type=search]").val("");
            reloadTable();
        });
        $("#fp-export").on("click", function (e) {
            e.preventDefault();
            window.location.href = "action/export_expense_requests.php?" + $.param(filterParams());
        });
    });
})(jQuery, window.FP);
