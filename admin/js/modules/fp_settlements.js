/* Finance & Performance: Settlements list page (my advances, branch review queue, Finance verification queue, outstanding advances). */
(function ($, FP) {
    "use strict";

    var esc = FP.esc;
    var tables = {};

    function money(value) {
        return esc(FP.formatMoney(String(value).replace(/^-/, "")));
    }

    function position(row) {
        var cls = row.Balance === "0.00" ? "text-success" : "text-danger";
        return '<span class="' + cls + '">' + esc(row.BalanceText) + "</span>" + (row.Overdue ? ' <span class="badge badge-danger">Overdue</span>' : "");
    }

    function returnedExtra(row) {
        return money(row.Refund) + '<div class="small text-muted">extra ' + money(row.Additional) + "</div>";
    }

    function filterParams($form) {
        var params = {};
        $.each($form.serializeArray(), function (_, field) {
            if (field.value !== "") {
                params[field.name] = field.value;
            }
        });
        return params;
    }

    function failedLoad(id) {
        return function (xhr) {
            $("#" + id + "_processing").hide();
            FP.alertFailure(xhr);
        };
    }

    function initMine() {
        tables.mine = $("#fp-mine-table").DataTable({
            ajax: { url: "action/list_advances.php", data: { mine: "1", status: "all" }, dataSrc: function (res) { return res.data ? res.data.rows : []; }, error: failedLoad("fp-mine-table") },
            responsive: true,
            pageLength: 25,
            order: [],
            columns: [
                { data: "ExpenseNumber", render: function (v, t, row) { return '<a href="view-advance-settlement.php?id=' + row.ID + '"><strong>' + esc(v) + '</strong></a><div class="small text-muted">' + esc(row.Office) + "</div>"; } },
                { data: "Purpose", className: "fp-purpose", render: function (v) { return esc(v.length > 100 ? v.substring(0, 97) + "..." : v); } },
                { data: "Paid", className: "text-right text-nowrap", render: function (v, t, row) { return money(v) + '<div class="small text-muted">on ' + esc(row.FirstPaid) + "</div>"; } },
                { data: "Accepted", className: "text-right text-nowrap", render: money },
                { data: "Refund", className: "text-right text-nowrap", render: function (v, t, row) { return returnedExtra(row); } },
                { data: "BalanceText", render: function (v, t, row) { return position(row); } },
                { data: "SettlementLabel", render: function (v, t, row) { return esc(v) + (row.OpenClaim ? '<div class="small text-muted">' + esc(row.OpenClaim) + "</div>" : ""); } },
                {
                    data: null, orderable: false, className: "text-nowrap", render: function (v, t, row) {
                        var start = row.SettlementStatus !== "settled" && !row.OpenClaim;
                        return '<a class="btn btn-xs ' + (start ? "btn-primary" : "btn-outline-primary") + '" href="view-advance-settlement.php?id=' + row.ID + '">' + (start ? "Settle" : "Open") + "</a>";
                    }
                }
            ],
            language: { emptyTable: "No advances have been paid to you." }
        });
    }

    function initQueue(view) {
        tables[view] = $("#fp-" + view + "-table").DataTable({
            ajax: { url: "action/list_settlement_claims.php", data: { view: view }, dataSrc: function (res) { return res.data || []; }, error: failedLoad("fp-" + view + "-table") },
            responsive: true,
            pageLength: 25,
            order: [],
            columns: [
                { data: "SettlementNumber", render: function (v, t, row) { return "<strong>" + esc(v) + '</strong><div class="small text-muted">' + esc(row.StatusLabel) + "</div>"; } },
                { data: "ExpenseNumber", render: function (v, t, row) { return '<a href="view-expense-request.php?id=' + row.RequestID + '">' + esc(v) + "</a>"; } },
                { data: "Employee", render: function (v) { return esc(v); } },
                { data: "Office", render: function (v) { return esc(v); } },
                { data: "ClaimedAmount", className: "text-right text-nowrap", render: money },
                { data: "LineCount" },
                { data: "SubmittedAt", className: "text-nowrap", render: function (v) { return esc(v); } },
                {
                    data: null, orderable: false, render: function (v, t, row) {
                        return row.CanAct
                            ? '<a class="btn btn-xs btn-primary" href="view-advance-settlement.php?id=' + row.RequestID + '">' + (view === "review" ? "Review" : "Verify") + "</a>"
                            : '<a class="btn btn-xs btn-outline-secondary" href="view-advance-settlement.php?id=' + row.RequestID + '">Open</a><div class="small text-muted">' + esc(row.CannotActReason) + "</div>";
                    }
                }
            ],
            language: { emptyTable: view === "review" ? "No settlements are waiting for branch review." : "No settlements are waiting for Finance verification." }
        });
    }

    function initOutstanding() {
        var timer = null;
        tables.outstanding = $("#fp-out-table").DataTable({
            ajax: {
                url: "action/list_advances.php",
                data: function () { return filterParams($("#fp-out-filters")); },
                dataSrc: function (res) {
                    var data = res.data || { rows: [], summary: null };
                    if (data.summary) {
                        $.each(data.summary.Buckets, function (key, value) { $('[data-total="' + key + '"]').text(FP.formatMoney(value)); });
                        $("#fp-total-holding").text(FP.formatMoney(data.summary.Holding));
                        $("#fp-total-owed").text(FP.formatMoney(data.summary.Owed));
                        $("#fp-due-days").text(data.summary.DueDays);
                    }
                    return data.rows;
                },
                error: failedLoad("fp-out-table")
            },
            responsive: true,
            pageLength: 25,
            order: [],
            columns: [
                { data: "ExpenseNumber", render: function (v, t, row) { return '<a href="view-advance-settlement.php?id=' + row.ID + '"><strong>' + esc(v) + '</strong></a><div class="small text-muted fp-purpose">' + esc(row.Purpose.length > 80 ? row.Purpose.substring(0, 77) + "..." : row.Purpose) + "</div>"; } },
                { data: "Employee", render: function (v) { return esc(v); } },
                { data: "Office", render: function (v, t, row) { return esc(v) + '<div class="small text-muted">' + esc(row.State) + "</div>"; } },
                { data: "Paid", className: "text-right text-nowrap", render: money },
                { data: "Accepted", className: "text-right text-nowrap", render: money },
                { data: "Refund", className: "text-right text-nowrap", render: function (v, t, row) { return returnedExtra(row); } },
                { data: "BalanceText", render: function (v, t, row) { return position(row); } },
                { data: "Days", className: "text-nowrap", render: function (v, t, row) { return t === "display" ? esc(v) + ' days<div class="small text-muted">' + esc(row.BucketLabel) + "</div>" : v; } },
                { data: "SettlementLabel", render: function (v, t, row) { return esc(v) + (row.OpenClaim ? '<div class="small text-muted">' + esc(row.OpenClaim) + "</div>" : ""); } }
            ],
            language: { emptyTable: "No advances match these filters." }
        });
        $("#fp-out-filters").on("change", "select", function () { tables.outstanding.ajax.reload(); });
        $("#fp-of-q").on("input", function () {
            clearTimeout(timer);
            timer = setTimeout(function () { tables.outstanding.ajax.reload(); }, 350);
        });
        $("#fp-buckets").on("click", ".fp-bucket", function () {
            var bucket = $(this).hasClass("active") ? "" : $(this).data("bucket");
            $(".fp-bucket").removeClass("active");
            if (bucket) {
                $(this).addClass("active");
            }
            $("#fp-of-bucket").val(bucket);
            tables.outstanding.ajax.reload();
        });
        $("#fp-out-export").on("click", function (e) {
            e.preventDefault();
            window.location.href = "action/export_advances.php?" + $.param(filterParams($("#fp-out-filters")));
        });
    }

    var INIT = { mine: initMine, review: function () { initQueue("review"); }, verify: function () { initQueue("verify"); }, outstanding: initOutstanding };

    function ensure(tab) {
        if (!tables[tab] && INIT[tab] && $("#fp-tab-" + tab).length) {
            INIT[tab]();
        } else if (tables[tab]) {
            tables[tab].columns.adjust();
        }
    }

    $(function () {
        FP.activateNav("", "settlements");
        var hash = window.location.hash.replace("#", "");
        if (hash && $('#fp-settlement-tabs a[data-tab="' + hash + '"]').length) {
            $('#fp-settlement-tabs a[data-tab="' + hash + '"]').tab("show");
        }
        $("#fp-settlement-tabs").on("shown.bs.tab", "a", function () {
            var tab = $(this).data("tab");
            window.history.replaceState(null, "", "#" + tab);
            ensure(tab);
        });
        ensure($("#fp-settlement-tabs a.active").data("tab"));
    });
})(jQuery, window.FP);
