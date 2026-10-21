/* Finance & Performance approval center: inbox page and the decision form on the request page. */
(function ($, FP) {
    "use strict";

    var esc = FP.esc;
    var DECISION_CLASS = { approved: "success", rejected: "danger", returned: "warning", reopened: "warning" };
    var CONFIRM = {
        approve: { title: "Approve", text: "Approve this stage?", button: "btn-success" },
        "return": { title: "Return for changes", text: "Send the request back to the requester for changes? After resubmission every stage must approve again.", button: "btn-warning" },
        reject: { title: "Reject", text: "Reject this request? This is final; the requester must create a new request.", button: "btn-danger" }
    };

    function waitingText(hours) {
        if (hours < 1) {
            return "less than 1 h";
        }
        if (hours < 48) {
            return hours + " h";
        }
        return Math.floor(hours / 24) + " days";
    }

    function initInbox() {
        var decidedLoaded = false;
        var inbox = $("#fp-inbox-table").DataTable({
            ajax: {
                url: "action/approval_list.php",
                type: "GET",
                data: { view: "inbox" },
                dataSrc: function (res) {
                    $("#fp-inbox-count").text((res.data || []).length);
                    return res.data || [];
                },
                error: function (xhr) {
                    $("#fp-inbox-table_processing").hide();
                    FP.alertFailure(xhr);
                }
            },
            processing: true,
            responsive: true,
            order: [],
            pageLength: 25,
            columns: [
                { data: "ExpenseNumber", render: function (v, t, row) { return '<a href="view-expense-request.php?id=' + row.ID + '"><strong>' + esc(v) + "</strong></a><div class=\"small text-muted\">" + esc(row.RequestType) + "</div>"; } },
                { data: "Employee", render: function (v) { return esc(v); } },
                { data: "Office", render: function (v, t, row) { return esc(v) + '<div class="small text-muted">' + esc(row.State) + "</div>"; } },
                { data: "Purpose", className: "fp-purpose", render: function (v) { return esc(v); } },
                { data: "TotalAmount", className: "text-right text-nowrap", render: function (v, t) { return t === "display" ? esc(FP.formatMoney(v)) : parseFloat(v); } },
                { data: "Stage", render: function (v, t, row) { return esc(v) + '<div class="small text-muted">As ' + esc(row.DecideAs) + "</div>"; } },
                {
                    data: "WaitingHours", className: "text-nowrap", render: function (v, t, row) {
                        if (t !== "display") {
                            return v;
                        }
                        return esc(waitingText(v)) + (row.Overdue ? ' <span class="badge badge-danger">overdue</span>' : "") + '<div class="small text-muted">since ' + esc(row.WaitingSince) + "</div>";
                    }
                },
                { data: null, orderable: false, className: "text-nowrap", render: function (v, t, row) { return '<a class="btn btn-xs btn-primary" href="view-expense-request.php?id=' + row.ID + '">Review</a>'; } }
            ],
            language: { emptyTable: "Nothing is waiting for your approval." }
        });

        var decided = null;
        function loadDecided() {
            if (decided) {
                decided.ajax.reload();
                return;
            }
            decided = $("#fp-decided-table").DataTable({
                ajax: {
                    url: "action/approval_list.php",
                    type: "GET",
                    data: { view: "decided" },
                    dataSrc: "data",
                    error: function (xhr) {
                        $("#fp-decided-table_processing").hide();
                        FP.alertFailure(xhr);
                    }
                },
                processing: true,
                responsive: true,
                order: [],
                pageLength: 25,
                columns: [
                    { data: "DecidedAt", className: "text-nowrap", render: function (v) { return esc(v); } },
                    { data: "ExpenseNumber", render: function (v, t, row) { return '<a href="view-expense-request.php?id=' + row.RequestID + '">' + esc(v) + "</a>"; } },
                    { data: "Employee", render: function (v, t, row) { return esc(v) + '<div class="small text-muted">' + esc(row.Office) + "</div>"; } },
                    { data: "TotalAmount", className: "text-right text-nowrap", render: function (v, t) { return t === "display" ? esc(FP.formatMoney(v)) : parseFloat(v); } },
                    { data: "Stage", render: function (v, t, row) { return esc(v) + (row.DecidedAs ? '<div class="small text-muted">As ' + esc(row.DecidedAs) + "</div>" : ""); } },
                    { data: "DecisionLabel", render: function (v, t, row) { return '<span class="badge badge-' + (DECISION_CLASS[row.Decision] || "light") + '">' + esc(v) + "</span>"; } },
                    { data: "Comments", className: "fp-comments", render: function (v) { return esc(v || "-"); } },
                    { data: "RequestStatus", render: function (v) { return esc(v); } }
                ],
                language: { emptyTable: "You have not decided any requests yet." }
            });
            decidedLoaded = true;
        }

        $("#fp-decided-link").on("shown.bs.tab", loadDecided);
        $("#fp-refresh").on("click", function () {
            inbox.ajax.reload();
            if (decidedLoaded) {
                decided.ajax.reload();
            }
        });
    }

    function initDecisionForm() {
        var $form = $("#fp-decision-form");
        var busy = false;
        $form.on("click", ".fp-decide", function () {
            var decision = $(this).data("decision");
            var comments = $.trim($("#fp-decision-comments").val());
            FP.clearErrors($form);
            if (decision !== "approve" && comments.length < 5) {
                FP.showErrors($form, { Comments: "Please write a reason (at least 5 characters) so the requester knows what to do." });
                $("#fp-decision-comments").trigger("focus");
                return;
            }
            var conf = CONFIRM[decision];
            alertify.confirm(conf.title, esc(conf.text), function () {
                if (busy) {
                    return;
                }
                busy = true;
                $form.find("button").prop("disabled", true);
                FP.request("POST", "action/decide_approval.php", {
                    ID: String($form.data("id")),
                    RowVersion: String($form.data("rowVersion")),
                    StageNo: String($form.data("stage")),
                    Decision: decision,
                    Comments: comments
                }).done(function (res) {
                    alertify.success(esc(res.message));
                    setTimeout(function () { window.location.reload(); }, 900);
                }).fail(function (xhr) {
                    busy = false;
                    $form.find("button").prop("disabled", false);
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        FP.showErrors($form, xhr.responseJSON.errors);
                    }
                    FP.alertFailure(xhr, function () { window.location.reload(); });
                });
            }, function () {});
        });
    }

    function initReopenForm() {
        var $form = $("#fp-reopen-form");
        var busy = false;
        $form.on("submit", function () {
            var reason = $.trim($("#fp-reopen-reason").val());
            FP.clearErrors($form);
            if (reason.length < 5) {
                FP.showErrors($form, { Reason: "Please write what must be changed (at least 5 characters)." });
                return false;
            }
            if (busy) {
                return false;
            }
            busy = true;
            $("#fp-reopen-confirm").prop("disabled", true);
            FP.request("POST", "action/reopen_request.php", {
                ID: String($form.data("id")),
                RowVersion: String($form.data("rowVersion")),
                Reason: reason
            }).done(function (res) {
                $("#fp-reopen-modal").modal("hide");
                alertify.success(esc(res.message));
                setTimeout(function () { window.location.reload(); }, 900);
            }).fail(function (xhr) {
                busy = false;
                $("#fp-reopen-confirm").prop("disabled", false);
                FP.alertFailure(xhr, function () { window.location.reload(); });
            });
            return false;
        });
    }

    $(function () {
        if ($("#fp-inbox-table").length) {
            FP.activateNav("", "approvals");
            initInbox();
        }
        if ($("#fp-decision-form").length) {
            initDecisionForm();
        }
        if ($("#fp-reopen-form").length) {
            initReopenForm();
        }
    });
})(jQuery, window.FP);
