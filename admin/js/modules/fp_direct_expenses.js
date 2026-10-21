/* Finance & Performance: Direct Expenses list (due dates, part payments, overdue bills). */
(function ($, FP) {
    "use strict";

    var esc = FP.esc;
    var DUE_CLASS = { overdue: "danger", due_soon: "warning", upcoming: "light", no_due: "light", paid: "success", closed: "dark" };
    var STATUS_CLASS = { draft: "secondary", submitted: "info", returned: "warning", approved: "success", rejected: "danger", cancelled: "dark" };

    function filterParams() {
        var params = {};
        $.each($("#fp-direct-filters").serializeArray(), function (_, field) {
            if (field.value !== "") {
                params[field.name] = field.value;
            }
        });
        return params;
    }

    function money(value) {
        return esc(FP.formatMoney(value));
    }

    $(function () {
        FP.activateNav("", "direct_expenses");
        var timer = null;
        var table = $("#fp-direct-table").DataTable({
            serverSide: true,
            processing: true,
            searching: false,
            ordering: false,
            responsive: true,
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            ajax: {
                url: "action/list_direct_expenses.php",
                type: "GET",
                data: function (d) {
                    return $.extend({ draw: d.draw, start: d.start, length: d.length }, filterParams());
                },
                dataSrc: function (res) {
                    var s = res.summary;
                    if (s) {
                        $("#fp-sum-overdue").text(s.OverdueCount);
                        $("#fp-sum-overdue-amount").text(FP.formatMoney(s.OverdueBalance) + " unpaid");
                        $("#fp-sum-soon").text(s.DueSoonCount);
                        $("#fp-sum-open").text(s.OpenCount);
                        $("#fp-sum-open-amount").text(FP.formatMoney(s.OpenBalance) + " unpaid");
                    }
                    return res.data || [];
                },
                error: function (xhr) {
                    $("#fp-direct-table_processing").hide();
                    FP.alertFailure(xhr);
                }
            },
            columns: [
                {
                    data: "ExpenseNumber", render: function (v, t, row) {
                        return '<a href="view-expense-request.php?id=' + row.ID + '"><strong>' + esc(v) + "</strong></a>" +
                            '<div class="small text-muted fp-purpose">' + esc(row.Purpose.length > 80 ? row.Purpose.substring(0, 77) + "..." : row.Purpose) + "</div>" +
                            (row.RuleNumber ? '<span class="badge badge-light" title="Created from a recurring expense"><i class="fa-solid fa-arrows-rotate mr-1"></i>' + esc(row.RuleNumber) + (row.Period ? ", " + esc(row.Period) : "") + "</span>" : "");
                    }
                },
                {
                    data: "Vendor", render: function (v, t, row) {
                        return v ? esc(v) + (row.Registered ? ' <i class="fa-solid fa-circle-check text-success" title="Registered vendor"></i>' : "") : '<span class="text-muted">Not entered</span>';
                    }
                },
                { data: "Office", render: function (v, t, row) { return esc(v) + '<div class="small text-muted">' + esc(row.Employee) + "</div>"; } },
                {
                    data: "DueDate", className: "text-nowrap", render: function (v, t, row) {
                        return (v ? esc(v) : '<span class="text-muted">-</span>') + '<div><span class="badge badge-' + (DUE_CLASS[row.DueState] || "light") + '">' + esc(row.DueStateLabel) + "</span></div>";
                    }
                },
                { data: "TotalAmount", className: "text-right text-nowrap", render: money },
                { data: "PaidAmount", className: "text-right text-nowrap", render: function (v, t, row) { return money(v) + '<div class="small text-muted">' + esc(row.PaymentStatusLabel) + "</div>"; } },
                { data: "Balance", className: "text-right text-nowrap", render: function (v) { return "<strong>" + money(v) + "</strong>"; } },
                {
                    data: "StatusLabel", render: function (v, t, row) {
                        return '<span class="badge badge-' + (STATUS_CLASS[row.Status] || "light") + '">' + esc(v) + '</span><div class="small text-muted">' + esc(row.NextStep) + "</div>";
                    }
                },
                {
                    data: null, className: "text-nowrap", render: function (v, t, row) {
                        if (row.CanEdit) {
                            return '<a class="btn btn-xs btn-primary" href="view-expense-request-form.php?id=' + row.ID + '">' + (row.Status === "returned" ? "Revise" : "Complete") + "</a>";
                        }
                        if (row.CanPay) {
                            return '<a class="btn btn-xs btn-primary" href="view-expense-request.php?id=' + row.ID + '#fp-payments-panel">Pay</a>';
                        }
                        return '<a class="btn btn-xs btn-outline-secondary" href="view-expense-request.php?id=' + row.ID + '">Open</a>';
                    }
                }
            ],
            language: { emptyTable: "No direct expenses yet.", zeroRecords: "No direct expenses match these filters." }
        });

        $("#fp-direct-filters").on("change", "select, input[type=date]", function () { table.ajax.reload(); });
        $("#fp-df-q").on("input", function () {
            clearTimeout(timer);
            timer = setTimeout(function () { table.ajax.reload(); }, 350);
        });
        $("#fp-cards").on("click", ".fp-card", function () {
            $(".fp-card").removeClass("active");
            $(this).addClass("active");
            $("#fp-df-due").val($(this).data("due"));
            table.ajax.reload();
        });
        $("#fp-direct-export").on("click", function (e) {
            e.preventDefault();
            window.location.href = "action/export_direct_expenses.php?" + $.param(filterParams());
        });
    });
})(jQuery, window.FP);
