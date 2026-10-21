/* Finance & Performance payments: register page, "Record payment" form and failed/reversed form (also used on the request page). */
(function ($, FP) {
    "use strict";

    var esc = FP.esc;
    var STATUS_CLASS = { completed: "success", failed: "danger", reversed: "warning" };
    var STATUS_TEXT = {
        failed: { title: "Mark payment failed", button: "Mark failed", text: "Use this when the money never reached the payee (for example the bank transfer bounced). The amount goes back to the unpaid balance and a new payment can be recorded." },
        reversed: { title: "Reverse payment", button: "Reverse payment", text: "Use this when the money was paid but has come back or must be cancelled. The amount goes back to the unpaid balance. The payment record is kept for audit." }
    };
    var form = null;
    var onSaved = null;
    var busy = false;

    function filterParams($form) {
        var params = {};
        $.each($form.serializeArray(), function (_, field) {
            if (field.value !== "") {
                params[field.name] = field.value;
            }
        });
        return params;
    }

    function referenceOptional() {
        return form && form.reference_optional.indexOf($("#fp-pay-mode").val()) !== -1;
    }

    function updateReferenceLabel() {
        $("#fp-pay-reference-label").text(referenceOptional() ? "Voucher or receipt number (optional)" : "Transaction reference (UTR / UPI / cheque no.)");
    }

    function updatePayee() {
        var type = $("input[name=PayeeType]:checked").val();
        $("#fp-pay-vendor-box").toggleClass("d-none", type !== "vendor");
        $("#fp-pay-employee-hint").toggleClass("d-none", type !== "employee");
    }

    function resetShared() {
        $("#fp-pay-shared").addClass("d-none");
        $("#fp-pay-shared-list").empty();
        $("#fp-pay-shared-confirm").prop("checked", false);
    }

    function openRecord(requestId, done) {
        onSaved = done;
        FP.request("GET", "action/payment_form.php", { id: String(requestId) }).done(function (res) {
            form = res.data;
            var r = form.request;
            var $form = $("#fp-payment-form");
            $form[0].reset();
            FP.clearErrors($form);
            resetShared();
            $("#fp-pay-title").text(r.ExpenseNumber);
            $("#fp-pay-summary").html(
                "<strong>" + esc(r.Employee) + "</strong> - " + esc(r.Office) + " - " + esc(r.Type) +
                '<div class="mt-1">Approved ' + esc(FP.formatMoney(r.TotalAmount)) + " | Paid " + esc(FP.formatMoney(r.PaidAmount)) +
                " | <strong>Unpaid balance " + esc(FP.formatMoney(r.Balance)) + "</strong></div>" +
                (r.DueDate ? '<div class="mt-1">Due date: <strong>' + esc(r.DueDate) + "</strong>" + (r.DueDate < form.today ? ' <span class="badge badge-danger">Overdue</span>' : "") + "</div>" : "")
            );
            $("#fp-pay-amount").val(r.Balance);
            $("#fp-pay-amount-help").text("At most " + FP.formatMoney(r.Balance) + ". Enter less for a part payment.");
            $("#fp-pay-date").val(form.today).attr({ max: form.today, min: r.ApprovedOn || null });
            var $types = $("#fp-pay-payee-types").empty();
            $.each(form.payee.types, function (key, label) {
                var id = "fp-pay-payee-" + key;
                $types.append(
                    '<div class="custom-control custom-radio custom-control-inline">' +
                    '<input type="radio" class="custom-control-input" name="PayeeType" id="' + id + '" value="' + esc(key) + '">' +
                    '<label class="custom-control-label" for="' + id + '">' + esc(key === "employee" ? form.payee.employee_name + " (requester)" : label) + "</label></div>"
                );
            });
            var $default = $types.find('input[value="' + (form.payee.default_type || "") + '"]');
            ($default.length ? $default : $types.find("input").first()).prop("checked", true);
            $("#fp-pay-employee-hint").text(form.payee.employee_hint ? "Bank account on file: " + form.payee.employee_hint : "No bank account on file for this employee.");
            $("#fp-pay-vendors").html($.map(form.payee.vendors, function (v) { return '<option value="' + esc(v) + '">'; }).join(""));
            if (form.payee.vendor_name || form.payee.vendors.length === 1) {
                $("#fp-pay-vendor").val(form.payee.vendor_name || form.payee.vendors[0]);
            }
            var vendorHint = [form.payee.vendor_hint ? "Bank (vendor master): " + form.payee.vendor_hint : "", form.payee.vendor_ref ? "Consumer / agreement no.: " + form.payee.vendor_ref : ""].filter(Boolean);
            $("#fp-pay-vendor-hint").text(vendorHint.join(" | "));
            updatePayee();
            updateReferenceLabel();
            busy = false;
            $("#fp-pay-save").prop("disabled", false);
            $("#fp-payment-modal").modal("show");
        }).fail(function (xhr) {
            FP.alertFailure(xhr);
        });
    }

    function saveRecord() {
        if (busy || !form) {
            return;
        }
        var $form = $("#fp-payment-form");
        FP.clearErrors($form);
        var data = {
            RequestID: String(form.request.ID),
            RowVersion: String(form.request.RowVersion),
            Amount: $.trim($("#fp-pay-amount").val()),
            PaymentDate: $("#fp-pay-date").val(),
            PaymentMode: $("#fp-pay-mode").val(),
            Reference: $.trim($("#fp-pay-reference").val()),
            PayeeType: $("input[name=PayeeType]:checked").val() || "",
            PayeeName: $.trim($("#fp-pay-vendor").val()),
            Remarks: $.trim($("#fp-pay-remarks").val()),
            ConfirmSharedReference: $("#fp-pay-shared-confirm").is(":checked") ? "1" : "0"
        };
        var errors = {};
        if (!/^\d{1,10}(\.\d{1,2})?$/.test(data.Amount.replace(/,/g, "")) || parseFloat(data.Amount.replace(/,/g, "")) <= 0) {
            errors.Amount = "Enter an amount greater than zero (up to 2 decimals).";
        }
        if (!data.PaymentDate) {
            errors.PaymentDate = "Enter the date the payment was made.";
        }
        if (!data.PaymentMode) {
            errors.PaymentMode = "Choose how the payment was made.";
        }
        if (!data.Reference && !referenceOptional()) {
            errors.Reference = "Enter the transaction reference.";
        }
        if (data.PayeeType === "vendor" && data.PayeeName.length < 2) {
            errors.PayeeName = "Enter the vendor name.";
        }
        if (!$.isEmptyObject(errors)) {
            FP.showErrors($form, errors);
            return;
        }
        busy = true;
        $("#fp-pay-save").prop("disabled", true);
        FP.request("POST", "action/record_payment.php", data).done(function (res) {
            $("#fp-payment-modal").modal("hide");
            alertify.success(esc(res.message));
            if (onSaved) {
                onSaved();
            }
        }).fail(function (xhr) {
            busy = false;
            $("#fp-pay-save").prop("disabled", false);
            var res = xhr.responseJSON || {};
            if (res.errors) {
                FP.showErrors($form, res.errors);
            }
            if (res.data && res.data.shared_reference) {
                $("#fp-pay-shared-list").html($.map(res.data.shared_reference, function (p) {
                    return "<li>" + esc(p.ExpenseNumber) + " - " + esc(p.PaymentNumber) + ", " + esc(FP.formatMoney(p.Amount)) + ", " + esc(p.PaymentDate) + "</li>";
                }).join(""));
                $("#fp-pay-shared").removeClass("d-none");
                return;
            }
            if (xhr.status === 422 && res.errors) {
                return;
            }
            FP.alertFailure(xhr, function () { $("#fp-payment-modal").modal("hide"); if (onSaved) { onSaved(); } });
        });
    }

    function openStatus($button, done) {
        var outcome = $button.data("outcome");
        var text = STATUS_TEXT[outcome];
        var $form = $("#fp-payment-status-form");
        $form[0].reset();
        FP.clearErrors($form);
        $form.data({ id: $button.data("id"), outcome: outcome });
        $("#fp-pstatus-title").text(text.title + " " + $button.data("number"));
        $("#fp-pstatus-text").text(text.text);
        $("#fp-pstatus-save").text(text.button).attr("class", "btn " + (outcome === "failed" ? "btn-danger" : "btn-warning")).prop("disabled", false);
        onSaved = done;
        busy = false;
        $("#fp-payment-status-modal").modal("show");
    }

    function saveStatus() {
        if (busy) {
            return;
        }
        var $form = $("#fp-payment-status-form");
        FP.clearErrors($form);
        var reason = $.trim($("#fp-pstatus-reason").val());
        if (reason.length < 5) {
            FP.showErrors($form, { Reason: "Please write why (at least 5 characters)." });
            return;
        }
        busy = true;
        $("#fp-pstatus-save").prop("disabled", true);
        FP.request("POST", "action/change_payment_status.php", {
            PaymentID: String($form.data("id")),
            Outcome: $form.data("outcome"),
            Reason: reason,
            StatusReference: $.trim($("#fp-pstatus-reference").val())
        }).done(function (res) {
            $("#fp-payment-status-modal").modal("hide");
            alertify.success(esc(res.message));
            if (onSaved) {
                onSaved();
            }
        }).fail(function (xhr) {
            busy = false;
            $("#fp-pstatus-save").prop("disabled", false);
            var res = xhr.responseJSON || {};
            if (res.errors) {
                FP.showErrors($form, res.errors);
                return;
            }
            FP.alertFailure(xhr, function () { $("#fp-payment-status-modal").modal("hide"); if (onSaved) { onSaved(); } });
        });
    }

    function statusButtons(row) {
        return '<button type="button" class="btn btn-xs btn-outline-danger fp-payment-status mr-1" data-outcome="failed" data-id="' + row.ID + '" data-number="' + esc(row.PaymentNumber) + '">Failed</button>' +
            '<button type="button" class="btn btn-xs btn-outline-warning fp-payment-status" data-outcome="reversed" data-id="' + row.ID + '" data-number="' + esc(row.PaymentNumber) + '">Reverse</button>';
    }

    function initRegisterPage() {
        var searchTimers = {};
        var payable = $("#fp-payable-table").DataTable({
            serverSide: true,
            processing: true,
            searching: false,
            ordering: false,
            responsive: true,
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            ajax: {
                url: "action/payable_requests.php",
                type: "GET",
                data: function (d) {
                    return $.extend({ draw: d.draw, start: d.start, length: d.length }, filterParams($("#fp-payable-filters")));
                },
                dataSrc: function (res) {
                    $("#fp-payable-balance").text(FP.formatMoney(res.summary ? res.summary.Balance : 0));
                    return res.data || [];
                },
                error: function (xhr) {
                    $("#fp-payable-table_processing").hide();
                    FP.alertFailure(xhr);
                }
            },
            columns: [
                { data: "ExpenseNumber", render: function (v, t, row) { return '<a href="view-expense-request.php?id=' + row.ID + '"><strong>' + esc(v) + '</strong></a><div class="small text-muted">' + esc(row.TypeLabel) + "</div>"; } },
                { data: "Employee", render: function (v) { return esc(v); } },
                { data: "Office", render: function (v, t, row) { return esc(v) + '<div class="small text-muted">' + esc(row.State) + "</div>"; } },
                { data: "Purpose", className: "fp-purpose", render: function (v, t, row) { return esc(v.length > 100 ? v.substring(0, 97) + "..." : v) + (row.VendorName ? '<div class="small text-muted">Pay to: ' + esc(row.VendorName) + "</div>" : ""); } },
                {
                    data: "ApprovedAt", className: "text-nowrap", render: function (v, t, row) {
                        return esc(v) + (row.DueDate ? '<div class="small ' + (row.Overdue ? "text-danger" : "text-muted") + '">Due ' + esc(row.DueDate) + (row.Overdue ? " (overdue)" : "") + "</div>" : "");
                    }
                },
                { data: "TotalAmount", className: "text-right text-nowrap", render: function (v) { return esc(FP.formatMoney(v)); } },
                { data: "PaidAmount", className: "text-right text-nowrap", render: function (v, t, row) { return esc(FP.formatMoney(v)) + '<div class="small text-muted">' + esc(row.PaymentStatusLabel) + "</div>"; } },
                { data: "Balance", className: "text-right text-nowrap", render: function (v) { return "<strong>" + esc(FP.formatMoney(v)) + "</strong>"; } },
                {
                    data: null, className: "text-nowrap", render: function (v, t, row) {
                        return row.CanPay
                            ? '<button type="button" class="btn btn-xs btn-primary fp-record-payment" data-id="' + row.ID + '">Record payment</button>'
                            : '<span class="small text-muted">' + esc(row.CannotPayReason || "Cannot pay") + "</span>";
                    }
                }
            ],
            language: { emptyTable: "No approved requests are waiting for payment.", zeroRecords: "No requests match these filters." }
        });

        var register = null;
        function loadRegister() {
            if (register) {
                register.ajax.reload(null, false);
                return;
            }
            register = $("#fp-register-table").DataTable({
                serverSide: true,
                processing: true,
                searching: false,
                ordering: false,
                responsive: true,
                pageLength: 25,
                lengthMenu: [10, 25, 50, 100],
                ajax: {
                    url: "action/payment_register.php",
                    type: "GET",
                    data: function (d) {
                        return $.extend({ draw: d.draw, start: d.start, length: d.length }, filterParams($("#fp-register-filters")));
                    },
                    dataSrc: function (res) {
                        $("#fp-register-total").text(FP.formatMoney(res.summary ? res.summary.Completed : 0));
                        return res.data || [];
                    },
                    error: function (xhr) {
                        $("#fp-register-table_processing").hide();
                        FP.alertFailure(xhr);
                    }
                },
                columns: [
                    { data: "PaymentNumber", className: "text-nowrap", render: function (v) { return "<strong>" + esc(v) + "</strong>"; } },
                    { data: "PaymentDate", className: "text-nowrap", render: function (v) { return esc(v); } },
                    { data: "ExpenseNumber", render: function (v, t, row) { return '<a href="view-expense-request.php?id=' + row.RequestID + '">' + esc(v) + '</a><div class="small text-muted">' + esc(row.Office) + "</div>"; } },
                    { data: "Payee", render: function (v, t, row) { return esc(v) + (row.PayeeAccount ? '<div class="small text-muted">' + esc(row.PayeeAccount) + "</div>" : ""); } },
                    { data: "Amount", className: "text-right text-nowrap", render: function (v, t, row) { return row.Status === "completed" ? esc(FP.formatMoney(v)) : "<s>" + esc(FP.formatMoney(v)) + "</s>"; } },
                    { data: "ModeLabel", render: function (v, t, row) { return esc(v) + '<div class="small text-muted">' + esc(row.Reference || "-") + (row.SharedReference ? ' <span class="badge badge-light">shared</span>' : "") + "</div>"; } },
                    {
                        data: "StatusLabel", render: function (v, t, row) {
                            return '<span class="badge badge-' + (STATUS_CLASS[row.Status] || "light") + '">' + esc(v) + "</span>" +
                                (row.StatusReason ? '<div class="small text-muted">' + esc(row.StatusReason) + "</div>" : "");
                        }
                    },
                    { data: "RecordedBy", render: function (v, t, row) { return esc(v) + '<div class="small text-muted">' + esc(row.RecordedAt) + "</div>"; } },
                    { data: null, className: "text-nowrap", render: function (v, t, row) { return row.CanChangeStatus ? statusButtons(row) : ""; } }
                ],
                language: { emptyTable: "No payments recorded yet.", zeroRecords: "No payments match these filters." }
            });
        }

        function reloadAll() {
            payable.ajax.reload(null, false);
            if (register) {
                register.ajax.reload(null, false);
            }
        }

        $("#fp-register-link").on("shown.bs.tab", loadRegister);
        $("#fp-payable-filters").on("change", "select", function () { payable.ajax.reload(); });
        $("#fp-register-filters").on("change", "select, input[type=date]", function () { if (register) { register.ajax.reload(); } });
        $("#fp-pf-q, #fp-rf-q").on("input", function () {
            var id = this.id;
            clearTimeout(searchTimers[id]);
            searchTimers[id] = setTimeout(function () {
                if (id === "fp-pf-q") {
                    payable.ajax.reload();
                } else if (register) {
                    register.ajax.reload();
                }
            }, 350);
        });
        $("#fp-payable-table").on("click", ".fp-record-payment", function () {
            openRecord($(this).data("id"), reloadAll);
        });
        $("#fp-register-table").on("click", ".fp-payment-status", function () {
            openStatus($(this), reloadAll);
        });
        $("#fp-register-export").on("click", function (e) {
            e.preventDefault();
            window.location.href = "action/export_payments.php?" + $.param(filterParams($("#fp-register-filters")));
        });
    }

    $(function () {
        $("#fp-payment-form").on("submit", saveRecord);
        $("#fp-payment-form").on("change", "#fp-pay-mode", updateReferenceLabel);
        $("#fp-payment-form").on("change", "input[name=PayeeType]", updatePayee);
        $("#fp-payment-form").on("input", "#fp-pay-reference", resetShared);
        $("#fp-payment-status-form").on("submit", saveStatus);

        if ($("#fp-payable-table").length) {
            FP.activateNav("", "payments");
            initRegisterPage();
        }
        var reloadPage = function () { setTimeout(function () { window.location.reload(); }, 900); };
        $("#fp-payments-panel").on("click", ".fp-record-payment", function () {
            openRecord($(this).data("id"), reloadPage);
        });
        $("#fp-payments-panel").on("click", ".fp-payment-status", function () {
            openStatus($(this), reloadPage);
        });
    });
})(jQuery, window.FP);
