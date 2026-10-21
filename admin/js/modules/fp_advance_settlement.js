/* Finance & Performance: advance settlement page (view-advance-settlement.php) - branch review, Finance verification,
   cash returned / extra paid entries, failed / reversed entries and closing. */
(function ($, FP) {
    "use strict";

    var esc = FP.esc;
    var busy = false;
    var page = null;
    var TXN_TEXT = {
        refund: { title: "Record cash returned", help: "Money the employee gave back from the advance (unspent or not accepted)." },
        additional: { title: "Record extra payment", help: "Money paid to the employee because the accepted expenses are more than the advance." }
    };
    var STATUS_TEXT = {
        failed: { title: "Mark failed", button: "Mark failed", text: "Use this when the money never arrived (for example a bounced transfer). The amount goes back into the position and a new entry can be recorded." },
        reversed: { title: "Reverse", button: "Reverse entry", text: "Use this when the money arrived but was sent back or must be cancelled. The entry is kept for audit." }
    };

    function reloadSoon() {
        setTimeout(function () { window.location.reload(); }, 900);
    }

    function done(res) {
        alertify.alert("TechXpert", esc(res.message), function () { window.location.reload(); });
    }

    function failed(xhr, $form) {
        busy = false;
        var res = xhr.responseJSON || {};
        if ($form && xhr.status === 422 && res.errors) {
            FP.showErrors($form, res.errors);
        }
        FP.alertFailure(xhr, reloadSoon);
    }

    function paise(value) {
        var text = String(value || "").replace(/,/g, "");
        if (!/^\d{1,10}(\.\d{1,2})?$/.test(text)) {
            return null;
        }
        var parts = text.split(".");
        return parseInt(parts[0], 10) * 100 + parseInt(((parts[1] || "") + "00").substring(0, 2), 10);
    }

    /* ---------------- holder ---------------- */

    function cancelClaim($claim) {
        FP.askReason({
            title: "Cancel " + $claim.data("number"),
            text: "The settlement will be marked as cancelled and kept for reference. A new settlement can be started afterwards.",
            required: false,
            confirmLabel: "Cancel settlement",
            buttonClass: "btn-danger"
        }, function (reason) {
            FP.request("POST", "action/cancel_settlement.php", { ID: $claim.data("id"), RowVersion: $claim.data("row-version"), Reason: reason })
                .done(done).fail(function (xhr) { failed(xhr); });
        });
    }

    /* ---------------- branch review ---------------- */

    function review($button) {
        if (busy) {
            return;
        }
        var $claim = $button.closest(".fp-claim");
        var $form = $button.closest("form");
        var decision = $button.data("decision");
        var comments = $.trim($form.find("[name=Comments]").val());
        FP.clearErrors($form);
        if (decision === "return" && comments.length < 5) {
            FP.showErrors($form, { Comments: "Say what the employee must correct (at least 5 characters)." });
            return;
        }
        var question = decision === "confirm"
            ? "Confirm " + $claim.data("number") + " and send it to Head Office Finance for verification?"
            : "Return " + $claim.data("number") + " to the employee for changes?";
        alertify.confirm("TechXpert", esc(question), function () {
            busy = true;
            FP.request("POST", "action/review_settlement.php", { ID: $claim.data("id"), RowVersion: $claim.data("row-version"), Decision: decision, Comments: comments })
                .done(done).fail(function (xhr) { failed(xhr, $form); });
        }, function () {});
    }

    /* ---------------- Finance verification ---------------- */

    function refreshLine($row) {
        var decision = $row.find(".fp-line-decision").val();
        var $amount = $row.find(".fp-line-accepted");
        var $reason = $row.find(".fp-line-reason");
        if (decision === "accepted") {
            $amount.val($row.data("amount")).prop("disabled", true);
        } else if (decision === "rejected") {
            $amount.val("0.00").prop("disabled", true);
        } else if (decision === "partial") {
            if ($amount.prop("disabled")) {
                $amount.val("");
            }
            $amount.prop("disabled", false);
        } else {
            $amount.val("").prop("disabled", true);
        }
        $reason.prop("disabled", decision === "" || decision === "accepted");
        if (decision === "accepted") {
            $reason.val("");
        }
    }

    function refreshTotal($claim) {
        var total = 0;
        $claim.find("tr[data-line-id]").each(function () {
            var value = paise($(this).find(".fp-line-accepted").val());
            total += value === null ? 0 : value;
        });
        $claim.find(".fp-verify-total").text(FP.formatMoney(total / 100));
    }

    function verify($button) {
        if (busy) {
            return;
        }
        var $claim = $button.closest(".fp-claim");
        var $form = $button.closest("form");
        var decision = $button.data("decision");
        var comments = $.trim($form.find("[name=Comments]").val());
        FP.clearErrors($claim);
        $claim.find(".fp-verify-errors").hide().empty();
        var errors = {};
        var lines = [];
        if (decision === "return") {
            if (comments.length < 5) {
                errors.Comments = "Say what the employee must correct (at least 5 characters).";
            }
        } else {
            $claim.find("tr[data-line-id]").each(function (index) {
                var $row = $(this);
                var line = {
                    LineID: String($row.data("line-id")),
                    Decision: $row.find(".fp-line-decision").val(),
                    AcceptedAmount: $.trim($row.find(".fp-line-accepted").val()),
                    Reason: $.trim($row.find(".fp-line-reason").val())
                };
                var prefix = "lines." + index + ".";
                if (!line.Decision) {
                    errors[prefix + "Decision"] = "Line " + (index + 1) + ": accept, partly accept or reject it.";
                } else {
                    if (line.Decision === "partial") {
                        var value = paise(line.AcceptedAmount);
                        if (value === null || value <= 0 || value >= paise($row.data("amount"))) {
                            errors[prefix + "AcceptedAmount"] = "Line " + (index + 1) + ": the accepted part must be more than zero and less than the line amount.";
                        }
                    }
                    if (line.Decision !== "accepted" && line.Reason.length < 5) {
                        errors[prefix + "Reason"] = "Line " + (index + 1) + ": give the reason (at least 5 characters).";
                    }
                }
                lines.push(line);
            });
        }
        if (!$.isEmptyObject(errors)) {
            showVerifyErrors($claim, errors);
            return;
        }
        var question = decision === "verify"
            ? "Verify " + $claim.data("number") + " with " + $claim.find(".fp-verify-total").text() + " accepted? Line decisions cannot be changed afterwards."
            : "Return " + $claim.data("number") + " to the employee for changes?";
        alertify.confirm("TechXpert", esc(question), function () {
            busy = true;
            FP.request("POST", "action/verify_settlement.php", $.param({
                ID: $claim.data("id"), RowVersion: $claim.data("row-version"), Decision: decision, Comments: comments, lines: lines
            })).done(done).fail(function (xhr) {
                busy = false;
                var res = xhr.responseJSON || {};
                if (xhr.status === 422 && res.errors) {
                    showVerifyErrors($claim, res.errors);
                    return;
                }
                FP.alertFailure(xhr, reloadSoon);
            });
        }, function () {});
    }

    function showVerifyErrors($claim, errors) {
        FP.showErrors($claim, errors);
        $claim.find(".fp-verify-errors").html('<ul class="mb-0">' + $.map(errors, function (message) { return "<li>" + esc(message) + "</li>"; }).join("") + "</ul>").show();
    }

    /* ---------------- cash returned / extra paid ---------------- */

    function referenceOptional() {
        return page.referenceOptional.indexOf($("#fp-txn-mode").val()) !== -1;
    }

    function openTxn(type) {
        var $form = $("#fp-txn-form");
        $form[0].reset();
        FP.clearErrors($form);
        $("#fp-txn-shared").addClass("d-none");
        $("#fp-txn-shared-confirm").prop("checked", false);
        var due = Math.abs(page.balance) / 100;
        $form.data("type", type);
        $("#fp-txn-title").text(TXN_TEXT[type].title + " - " + page.number);
        $("#fp-txn-help").text(TXN_TEXT[type].help);
        $("#fp-txn-account").toggle(type === "additional");
        $("#fp-txn-amount").val(due.toFixed(2));
        $("#fp-txn-amount-help").text("At most " + FP.formatMoney(due) + (type === "refund" ? " is due from the employee." : " is due to the employee."));
        $("#fp-txn-date").val(page.today).attr({ max: page.today, min: page.firstPaid || null });
        $("#fp-txn-reference-label").text("Transaction reference (UTR / UPI / cheque no.)");
        busy = false;
        $("#fp-txn-save").prop("disabled", false);
        $("#fp-txn-modal").modal("show");
    }

    function saveTxn() {
        if (busy) {
            return;
        }
        var $form = $("#fp-txn-form");
        FP.clearErrors($form);
        var data = {
            RequestID: String(page.requestId),
            RowVersion: String(page.rowVersion),
            TxnType: $form.data("type"),
            Amount: $.trim($("#fp-txn-amount").val()),
            TxnDate: $("#fp-txn-date").val(),
            TxnMode: $("#fp-txn-mode").val(),
            Reference: $.trim($("#fp-txn-reference").val()),
            Remarks: $.trim($("#fp-txn-remarks").val()),
            ConfirmSharedReference: $("#fp-txn-shared-confirm").is(":checked") ? "1" : "0"
        };
        var errors = {};
        var amount = paise(data.Amount);
        if (amount === null || amount <= 0) {
            errors.Amount = "Enter an amount greater than zero (up to 2 decimals).";
        } else if (amount > Math.abs(page.balance)) {
            errors.Amount = "At most " + FP.formatMoney(Math.abs(page.balance) / 100) + ".";
        }
        if (!data.TxnDate) {
            errors.TxnDate = "Enter the date.";
        }
        if (!data.TxnMode) {
            errors.TxnMode = "Choose how the money was transferred.";
        }
        if (!data.Reference && data.TxnMode && !referenceOptional()) {
            errors.Reference = "Enter the transaction reference.";
        }
        if (!$.isEmptyObject(errors)) {
            FP.showErrors($form, errors);
            return;
        }
        busy = true;
        $("#fp-txn-save").prop("disabled", true);
        FP.request("POST", "action/record_settlement_txn.php", data).done(function (res) {
            $("#fp-txn-modal").modal("hide");
            done(res);
        }).fail(function (xhr) {
            busy = false;
            $("#fp-txn-save").prop("disabled", false);
            var res = xhr.responseJSON || {};
            if (res.errors) {
                FP.showErrors($form, res.errors);
            }
            if (res.data && res.data.shared_reference) {
                $("#fp-txn-shared").removeClass("d-none");
                return;
            }
            if (xhr.status === 422 && res.errors) {
                return;
            }
            FP.alertFailure(xhr, function () { $("#fp-txn-modal").modal("hide"); reloadSoon(); });
        });
    }

    function openTxnStatus($button) {
        var outcome = $button.data("outcome");
        var text = STATUS_TEXT[outcome];
        var $form = $("#fp-txn-status-form");
        $form[0].reset();
        FP.clearErrors($form);
        $form.data({ id: $button.data("id"), outcome: outcome });
        $("#fp-tstatus-title").text(text.title + " " + $button.data("number"));
        $("#fp-tstatus-text").text(text.text);
        $("#fp-tstatus-save").text(text.button).attr("class", "btn " + (outcome === "failed" ? "btn-danger" : "btn-warning")).prop("disabled", false);
        busy = false;
        $("#fp-txn-status-modal").modal("show");
    }

    function saveTxnStatus() {
        if (busy) {
            return;
        }
        var $form = $("#fp-txn-status-form");
        FP.clearErrors($form);
        var reason = $.trim($("#fp-tstatus-reason").val());
        if (reason.length < 5) {
            FP.showErrors($form, { Reason: "Please write why (at least 5 characters)." });
            return;
        }
        busy = true;
        $("#fp-tstatus-save").prop("disabled", true);
        FP.request("POST", "action/change_settlement_txn_status.php", {
            TxnID: String($form.data("id")),
            Outcome: $form.data("outcome"),
            Reason: reason,
            StatusReference: $.trim($("#fp-tstatus-reference").val())
        }).done(function (res) {
            $("#fp-txn-status-modal").modal("hide");
            done(res);
        }).fail(function (xhr) {
            busy = false;
            $("#fp-tstatus-save").prop("disabled", false);
            var res = xhr.responseJSON || {};
            if (xhr.status === 422 && res.errors) {
                FP.showErrors($form, res.errors);
                return;
            }
            FP.alertFailure(xhr, function () { $("#fp-txn-status-modal").modal("hide"); reloadSoon(); });
        });
    }

    function closeAdvance() {
        FP.askReason({
            title: "Close advance " + page.number,
            text: "The position is zero and every claim is decided. After closing, the advance, its payments and its settlement can no longer be changed.",
            required: false,
            confirmLabel: "Close advance",
            buttonClass: "btn-dark"
        }, function (remarks) {
            FP.request("POST", "action/close_settlement.php", { RequestID: page.requestId, RowVersion: page.rowVersion, Remarks: remarks })
                .done(done).fail(function (xhr) { failed(xhr); });
        });
    }

    $(function () {
        var $page = $("#fp-settlement-page");
        if (!$page.length) {
            return;
        }
        FP.activateNav("", "settlements");
        page = {
            requestId: $page.data("request-id"),
            rowVersion: $page.data("row-version"),
            number: String($page.data("number")),
            balance: parseInt($page.data("balance"), 10) || 0,
            firstPaid: $page.data("first-paid") || "",
            today: $page.data("today"),
            referenceOptional: String($page.data("reference-optional") || "").split(",")
        };

        $page.on("click", ".fp-cancel-claim", function () { cancelClaim($(this).closest(".fp-claim")); });
        $page.on("click", ".fp-review", function () { review($(this)); });
        $page.on("click", ".fp-verify", function () { verify($(this)); });
        $page.on("change", ".fp-line-decision", function () {
            var $row = $(this).closest("tr");
            refreshLine($row);
            refreshTotal($row.closest(".fp-claim"));
        });
        $page.on("input", ".fp-line-accepted", function () { refreshTotal($(this).closest(".fp-claim")); });
        $page.on("click", ".fp-accept-all", function () {
            var $claim = $(this).closest(".fp-claim");
            $claim.find(".fp-line-decision").val("accepted").each(function () { refreshLine($(this).closest("tr")); });
            refreshTotal($claim);
        });
        $page.on("click", ".fp-record-txn", function () { openTxn($(this).data("type")); });
        $page.on("click", ".fp-txn-status", function () { openTxnStatus($(this)); });
        $("#fp-close-advance").on("click", closeAdvance);

        $("#fp-txn-form").on("submit", saveTxn);
        $("#fp-txn-form").on("change", "#fp-txn-mode", function () {
            $("#fp-txn-reference-label").text(referenceOptional() ? "Voucher or receipt number (optional)" : "Transaction reference (UTR / UPI / cheque no.)");
        });
        $("#fp-txn-form").on("input", "#fp-txn-reference", function () {
            $("#fp-txn-shared").addClass("d-none");
            $("#fp-txn-shared-confirm").prop("checked", false);
        });
        $("#fp-txn-status-form").on("submit", saveTxnStatus);
    });
})(jQuery, window.FP);
