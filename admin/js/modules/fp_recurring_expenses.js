/* Finance & Performance: Recurring Expenses (rules that prepare draft direct expenses for each period). */
(function ($, FP) {
    "use strict";

    var esc = FP.esc;
    var MONTHS = { monthly: 1, quarterly: 3, half_yearly: 6, yearly: 12 };
    var STATUS_CLASS = { active: "success", paused: "warning", ended: "dark" };
    var HISTORY_LABELS = { create: "Created", update: "Changed", pause: "Paused", resume: "Resumed", end: "Ended", generate: "Draft created" };
    var FIELD_LABELS = {
        Title: "Name", OfficeID: "Branch office", OwnerEmployeeID: "Owner", CategoryID: "Category", LineDescription: "Line description",
        VendorRegistrationID: "Registered vendor", VendorName: "Pay to", VendorGSTIN: "GSTIN", VendorAccountRef: "Consumer / agreement no.",
        Amount: "Amount", AmountType: "Amount is", Frequency: "Frequency", StartDate: "Starts", EndDate: "Ends", DueDay: "Due day",
        DueTiming: "Due", ReminderDays: "Draft days before due", NextPeriodStart: "Next period starts", Status: "Status", Notes: "Notes",
        SkippedPeriods: "Past periods skipped", Period: "Period", ExpenseNumber: "Draft", DueDate: "Due date"
    };
    var table = null;
    var form = null;
    var vendor = null;
    var current = null;

    function filterParams() {
        var params = {};
        $.each($("#fp-rule-filters").serializeArray(), function (_, field) {
            if (field.value !== "") {
                params[field.name] = field.value;
            }
        });
        return params;
    }

    function pad(n) {
        return (n < 10 ? "0" : "") + n;
    }

    function addMonths(ym, months) {
        var parts = ym.split("-");
        var total = parseInt(parts[0], 10) * 12 + parseInt(parts[1], 10) - 1 + months;
        return Math.floor(total / 12) + "-" + pad(total % 12 + 1);
    }

    function formatDate(iso) {
        if (!iso) {
            return "-";
        }
        var d = new Date(iso + "T00:00:00");
        return isNaN(d.getTime()) ? iso : d.toLocaleDateString("en-IN", { day: "2-digit", month: "short", year: "numeric" });
    }

    function monthLabel(ym) {
        var d = new Date(ym + "-01T00:00:00");
        return d.toLocaleDateString("en-IN", { month: "short", year: "numeric" });
    }

    /* Mirrors fp_recurring_period(); the server is the source of truth. */
    function preview() {
        var start = $("#fp-rule-start").val();
        var months = MONTHS[$("#fp-rule-frequency").val()];
        var day = parseInt($("#fp-rule-due-day").val(), 10);
        var before = parseInt($("#fp-rule-reminder").val(), 10);
        $("#fp-rule-form [name=StartDate]").val(start ? start + "-01" : "");
        if (!start || !months || !(day >= 1 && day <= 28) || !(before >= 0)) {
            $("#fp-rule-preview").text("Fill in the frequency, first month and due day to see when the first draft is prepared.");
            return;
        }
        var endMonth = addMonths(start, months - 1);
        var dueMonth = $("#fp-rule-timing").val() === "arrears" ? addMonths(start, months) : start;
        var due = new Date(dueMonth + "-" + pad(day) + "T00:00:00");
        var create = new Date(due.getTime() - before * 86400000);
        var period = months === 1 ? monthLabel(start) : monthLabel(start) + " - " + monthLabel(endMonth);
        $("#fp-rule-preview").html("First bill: <strong>" + esc(period) + "</strong>, due <strong>" + esc(due.toLocaleDateString("en-IN", { day: "2-digit", month: "short", year: "numeric" })) +
            "</strong>. Its draft is prepared on " + esc(create.toLocaleDateString("en-IN", { day: "2-digit", month: "short", year: "numeric" })) + " (or the next daily run).");
    }

    /* ---------------- form ---------------- */

    function officeById(id) {
        return form.offices.filter(function (o) { return String(o.ID) === String(id); })[0] || null;
    }

    function renderOwners(selected) {
        var office = officeById($("#fp-rule-office").val());
        var $owner = $("#fp-rule-owner");
        if (!office) {
            $owner.html('<option value="">Choose the office first</option>').prop("disabled", true);
            return;
        }
        if (!office.Finance) {
            $owner.html('<option value="' + form.me.ID + '">' + esc(form.me.Label) + " (you)</option>").prop("disabled", true);
            return;
        }
        var list = form.owners[office.ID] || [];
        var html = '<option value="">' + (list.length ? "Choose..." : "No requesters assigned to this office") + "</option>";
        list.forEach(function (e) { html += '<option value="' + e.ID + '">' + esc(e.Label) + "</option>"; });
        $owner.html(html).prop("disabled", false).val(selected ? String(selected) : "");
    }

    function renderCategories(selected) {
        var office = officeById($("#fp-rule-office").val());
        var html = '<option value="">Choose...</option>';
        form.categories.forEach(function (c) {
            if (!office || c.CompanyID === 0 || c.CompanyID === office.CompanyID) {
                html += '<option value="' + c.ID + '">' + esc(c.Label) + "</option>";
            }
        });
        $("#fp-rule-category").html(html).val(selected ? String(selected) : "");
    }

    function options(map, $select) {
        $select.html($.map(map, function (label, key) { return '<option value="' + esc(key) + '">' + esc(label) + "</option>"; }).join(""));
    }

    function openForm(ruleId) {
        FP.request("GET", "action/get_recurring_form.php", ruleId ? { id: String(ruleId) } : {}).done(function (res) {
            form = res.data;
            var r = form.rule;
            var $form = $("#fp-rule-form");
            $form[0].reset();
            FP.clearErrors($form);
            $("#fp-rule-title").text(r ? "Edit " + r.RuleNumber : "New recurring expense");
            $form.find("[name=ID]").val(r ? r.ID : 0);
            $form.find("[name=RowVersion]").val(r ? r.RowVersion : 0);
            var officeHtml = form.offices.length > 1 ? '<option value="">Choose...</option>' : "";
            form.offices.forEach(function (o) { officeHtml += '<option value="' + o.ID + '">' + esc(o.OfficeName + " (" + o.OfficeCode + ")") + "</option>"; });
            $("#fp-rule-office").html(officeHtml).val(r ? String(r.OfficeID) : (form.offices.length === 1 ? String(form.offices[0].ID) : ""));
            options(form.amountTypes, $("#fp-rule-amount-type"));
            options(form.frequencies, $("#fp-rule-frequency"));
            options(form.timings, $("#fp-rule-timing"));
            if (!vendor) {
                vendor = FP.vendorPicker($("#fp-rule-vendor"), { mode: form.vendorMode });
            } else {
                vendor.setMode(form.vendorMode);
            }
            vendor.set(r ? $.extend({}, r, { VendorLabel: "" }) : {});
            renderCategories(r ? r.CategoryID : 0);
            if (r && r.OfficeID && !$("#fp-rule-office").val()) {
                $("#fp-rule-office").append('<option value="' + r.OfficeID + '">Current office (not available to you)</option>').val(String(r.OfficeID));
            }
            renderOwners(r ? r.OwnerEmployeeID : 0);
            if (r && r.OwnerEmployeeID && String($("#fp-rule-owner").val()) !== String(r.OwnerEmployeeID)) {
                $("#fp-rule-owner").append('<option value="' + r.OwnerEmployeeID + '">' + esc(r.OwnerLabel) + " (no longer a requester here)</option>").val(String(r.OwnerEmployeeID));
            }
            if (r) {
                $("#fp-rule-name").val(r.Title);
                $("#fp-rule-line").val(r.LineDescription === r.Title ? "" : r.LineDescription);
                $("#fp-rule-amount").val(r.Amount);
                $("#fp-rule-amount-type").val(r.AmountType);
                $("#fp-rule-frequency").val(r.Frequency);
                $("#fp-rule-start").val(r.StartDate.substring(0, 7));
                $("#fp-rule-end").val(r.EndDate);
                $("#fp-rule-due-day").val(r.DueDay);
                $("#fp-rule-timing").val(r.DueTiming);
                $("#fp-rule-reminder").val(r.ReminderDays);
                $("#fp-rule-notes").val(r.Notes);
            } else {
                $("#fp-rule-start").attr("min", form.earliestStart.substring(0, 7));
            }
            $("#fp-rule-locked").toggle(form.locked);
            $("#fp-rule-frequency, #fp-rule-start").prop("disabled", form.locked);
            preview();
            $("#fp-rule-save").prop("disabled", false);
            $("#fp-detail-modal").modal("hide");
            $("#fp-rule-modal").modal("show");
        }).fail(function (xhr) { FP.alertFailure(xhr); });
    }

    function saveForm() {
        var $form = $("#fp-rule-form");
        FP.clearErrors($form);
        preview();
        var data = {
            ID: $form.find("[name=ID]").val(),
            RowVersion: $form.find("[name=RowVersion]").val(),
            Title: $("#fp-rule-name").val(),
            OfficeID: $("#fp-rule-office").val() || "",
            OwnerEmployeeID: $("#fp-rule-owner").val() || "",
            CategoryID: $("#fp-rule-category").val() || "",
            LineDescription: $("#fp-rule-line").val(),
            Amount: $.trim($("#fp-rule-amount").val()),
            AmountType: $("#fp-rule-amount-type").val(),
            Frequency: $("#fp-rule-frequency").val(),
            StartDate: $form.find("[name=StartDate]").val(),
            EndDate: $("#fp-rule-end").val(),
            DueDay: $("#fp-rule-due-day").val(),
            DueTiming: $("#fp-rule-timing").val(),
            ReminderDays: $("#fp-rule-reminder").val(),
            Notes: $("#fp-rule-notes").val()
        };
        $.extend(data, vendor.get());
        var $button = $("#fp-rule-save").prop("disabled", true);
        FP.request("POST", "action/save_recurring_rule.php", data).done(function (res) {
            $("#fp-rule-modal").modal("hide");
            alertify.alert("TechXpert", esc(res.message));
            table.ajax.reload(null, false);
        }).fail(function (xhr) {
            var res = xhr.responseJSON || {};
            if (xhr.status === 422 && res.errors) {
                FP.showErrors($form, res.errors);
                if (res.errors.Frequency && form.locked) {
                    alertify.error(esc(res.errors.Frequency));
                }
                return;
            }
            FP.alertFailure(xhr, function () { $("#fp-rule-modal").modal("hide"); table.ajax.reload(null, false); });
        }).always(function () { $button.prop("disabled", false); });
    }

    /* ---------------- detail ---------------- */

    function historyValue(field, value) {
        if (value === null || value === undefined || value === "") {
            return "-";
        }
        if (field === "StartDate" || field === "EndDate" || field === "NextPeriodStart" || field === "DueDate") {
            return formatDate(value);
        }
        return typeof value === "object" ? JSON.stringify(value) : String(value);
    }

    function renderHistory(history) {
        if (!history.length) {
            return '<p class="text-muted">No history yet.</p>';
        }
        return '<ul class="list-unstyled fp-history mb-0">' + history.map(function (h) {
            var lines = [];
            var fields = h.Action === "update" ? h.Changed : Object.keys(h.New || {});
            if (h.Action !== "create") {
                fields.forEach(function (field) {
                    var before = h.Old && Object.prototype.hasOwnProperty.call(h.Old, field) ? historyValue(field, h.Old[field]) : null;
                    var after = historyValue(field, (h.New || {})[field]);
                    lines.push(esc(FIELD_LABELS[field] || field) + ": " + (before !== null && h.Action !== "generate" ? esc(before) + " &rarr; " : "") + esc(after));
                });
            }
            return "<li><strong>" + esc(HISTORY_LABELS[h.Action] || h.Action) + "</strong> <span class=\"small text-muted\">" + esc(h.At) + " by " + esc(h.Actor) + "</span>" +
                (h.Remarks ? '<div class="small">Reason: ' + esc(h.Remarks) + "</div>" : "") +
                (lines.length ? '<div class="small text-muted">' + lines.join("<br>") + "</div>" : "") + "</li>";
        }).join("") + "</ul>";
    }

    function dlItem(label, value, wide) {
        return '<div class="' + (wide ? "col-md-12" : "col-md-3") + '"><dt>' + esc(label) + "</dt><dd>" + value + "</dd></div>";
    }

    function renderDetail(data) {
        var r = data.rule;
        current = data;
        $("#fp-detail-title").html(esc(r.RuleNumber) + ' <span class="fw-300">' + esc(r.Title) + '</span> <span class="badge badge-' + (STATUS_CLASS[r.Status] || "light") + '">' + esc(r.StatusLabel) + "</span>");
        var html = "";
        if (r.LastRunNote) {
            html += '<div class="alert alert-warning py-2"><strong>The last automatic run could not prepare the draft:</strong> ' + esc(r.LastRunNote) + "</div>";
        }
        if (r.Status !== "active" && r.StatusReason) {
            html += '<div class="alert alert-light border py-2">' + esc(r.StatusLabel) + ": " + esc(r.StatusReason) + "</div>";
        }
        html += '<dl class="row fp-dl">' +
            dlItem("Branch office", esc(r.Office) + '<div class="small text-muted">' + esc(r.Company + ", " + r.State) + "</div>") +
            dlItem("Owner", esc(r.Owner)) +
            dlItem("Category", esc(r.Category)) +
            dlItem("Amount", "<strong>" + esc(FP.formatMoney(r.Amount)) + "</strong><div class=\"small text-muted\">" + esc(r.AmountType) + "</div>") +
            dlItem("Pay to", esc(r.Vendor) + (r.VendorCode ? ' <span class="badge badge-success">' + esc(r.VendorCode) + "</span>" : ' <span class="badge badge-light">not a registered vendor</span>') +
                (r.VendorGSTIN ? '<div class="small text-muted">GSTIN ' + esc(r.VendorGSTIN) + "</div>" : "")) +
            dlItem("Consumer / agreement no.", esc(r.VendorAccountRef || "-")) +
            dlItem("Frequency", esc(r.Frequency) + '<div class="small text-muted">from ' + esc(formatDate(r.StartDate)) + (r.EndDate ? " to " + esc(formatDate(r.EndDate)) : "") + "</div>") +
            dlItem("Due", "Day " + r.DueDay + '<div class="small text-muted">' + esc(r.DueTiming) + "; draft " + r.ReminderDays + " days before</div>") +
            (r.Notes ? dlItem("Notes", '<span style="white-space:pre-line">' + esc(r.Notes) + "</span>", true) : "") +
            "</dl>";
        html += '<h6>Next bills</h6>';
        html += data.upcoming.length
            ? '<table class="table table-sm table-bordered"><thead class="thead-light"><tr><th>Period</th><th>Due date</th><th>Draft prepared on</th></tr></thead><tbody>' +
                data.upcoming.map(function (u) { return "<tr><td>" + esc(u.Period) + "</td><td>" + esc(formatDate(u.DueDate)) + "</td><td>" + esc(formatDate(u.CreateOn)) + "</td></tr>"; }).join("") + "</tbody></table>"
            : '<p class="text-muted">No further bills will be prepared' + (r.Status === "paused" ? " while the rule is paused" : "") + ".</p>";
        html += "<h6>Drafts created</h6>";
        html += data.requests.length
            ? '<div class="table-responsive"><table class="table table-sm table-bordered"><thead class="thead-light"><tr><th>Expense</th><th>Period</th><th>Due</th><th class="text-right">Amount</th><th class="text-right">Paid</th><th>Status</th></tr></thead><tbody>' +
                data.requests.map(function (q) {
                    return "<tr><td>" + (q.CanOpen ? '<a href="view-expense-request.php?id=' + q.ID + '">' + esc(q.ExpenseNumber) + "</a>" : esc(q.ExpenseNumber)) + "</td><td>" + esc(q.Period) + "</td><td>" + esc(formatDate(q.DueDate)) +
                        '</td><td class="text-right">' + esc(FP.formatMoney(q.TotalAmount)) + '</td><td class="text-right">' + esc(FP.formatMoney(q.PaidAmount)) +
                        "</td><td>" + esc(q.StatusLabel) + '<div class="small text-muted">' + esc(q.NextStep) + "</div></td></tr>";
                }).join("") + "</tbody></table></div>"
            : '<p class="text-muted">None yet.</p>';
        html += '<h6 class="mt-3">History</h6>' + renderHistory(data.history);
        $("#fp-detail-body").html(html);

        var actions = '<button type="button" class="btn btn-secondary mr-auto" data-dismiss="modal">Close</button>';
        if (data.can_manage && r.Status !== "ended") {
            if (data.can_generate) {
                actions += '<button type="button" class="btn btn-outline-primary fp-act" data-act="generate">Create next draft now</button>';
            }
            actions += '<button type="button" class="btn btn-outline-primary fp-act" data-act="edit">Edit</button>';
            actions += r.Status === "active"
                ? '<button type="button" class="btn btn-outline-warning fp-act" data-act="paused">Pause</button>'
                : '<button type="button" class="btn btn-outline-success fp-act" data-act="active">Resume</button>';
            actions += '<button type="button" class="btn btn-outline-danger fp-act" data-act="ended">End</button>';
        }
        $("#fp-detail-actions").html(actions);
    }

    function openDetail(ruleId) {
        FP.request("GET", "action/get_recurring_rule.php", { id: String(ruleId) }).done(function (res) {
            renderDetail(res.data);
            window.history.replaceState(null, "", "#rule-" + ruleId);
            $("#fp-detail-modal").modal("show");
        }).fail(function (xhr) { FP.alertFailure(xhr); });
    }

    function afterAction(res) {
        alertify.alert("TechXpert", esc(res.message));
        table.ajax.reload(null, false);
        openDetail(current.rule.ID);
    }

    function setStatus(status) {
        var r = current.rule;
        var texts = {
            paused: { title: "Pause " + r.RuleNumber, text: "No new drafts are prepared while paused. Drafts already created are not changed.", label: "Pause", cls: "btn-warning", required: false },
            active: { title: "Resume " + r.RuleNumber, text: "Drafts are prepared again from the next bill that is not yet due. Bills whose due date has already passed are skipped; book them as normal direct expenses if still unpaid.", label: "Resume", cls: "btn-success", required: false },
            ended: { title: "End " + r.RuleNumber, text: "No more drafts will ever be prepared from this rule (for example the office moved or the contract ended). This cannot be undone. Drafts already created are not changed.", label: "End rule", cls: "btn-danger", required: true }
        }[status];
        FP.askReason({ title: texts.title, text: texts.text, required: texts.required, confirmLabel: texts.label, buttonClass: texts.cls }, function (reason) {
            FP.request("POST", "action/set_recurring_status.php", { ID: r.ID, RowVersion: r.RowVersion, Status: status, Reason: reason })
                .done(afterAction)
                .fail(function (xhr) { FP.alertFailure(xhr, function () { openDetail(r.ID); }); });
        });
    }

    function generateNow() {
        var r = current.rule;
        alertify.confirm("TechXpert", "Prepare the draft for the next bill now? The owner still has to check it, attach the bill and submit it for approval.", function () {
            FP.request("POST", "action/generate_recurring_now.php", { ID: r.ID, RowVersion: r.RowVersion })
                .done(afterAction)
                .fail(function (xhr) { FP.alertFailure(xhr, function () { openDetail(r.ID); }); });
        }, function () {});
    }

    /* ---------------- list ---------------- */

    $(function () {
        FP.activateNav("", "recurring_expenses");
        var timer = null;
        table = $("#fp-rule-table").DataTable({
            serverSide: true,
            processing: true,
            searching: false,
            ordering: false,
            responsive: true,
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            ajax: {
                url: "action/list_recurring_rules.php",
                type: "GET",
                data: function (d) { return $.extend({ draw: d.draw, start: d.start, length: d.length }, filterParams()); },
                dataSrc: function (res) { return res.data || []; },
                error: function (xhr) {
                    $("#fp-rule-table_processing").hide();
                    FP.alertFailure(xhr);
                }
            },
            columns: [
                {
                    data: "RuleNumber", render: function (v, t, row) {
                        return '<a href="#" class="fp-open" data-id="' + row.ID + '"><strong>' + esc(v) + "</strong></a><div>" + esc(row.Title) + "</div>" +
                            (row.LastRunNote ? '<div class="small text-danger"><i class="fa-solid fa-triangle-exclamation mr-1"></i>' + esc(row.LastRunNote) + "</div>" : "");
                    }
                },
                { data: "Vendor", render: function (v, t, row) { return esc(v) + (row.Registered ? ' <i class="fa-solid fa-circle-check text-success" title="Registered vendor"></i>' : ""); } },
                { data: "Office", render: function (v, t, row) { return esc(v) + '<div class="small text-muted">' + esc(row.Owner) + "</div>"; } },
                { data: "Amount", className: "text-right text-nowrap", render: function (v, t, row) { return esc(FP.formatMoney(v)) + (row.AmountType === "estimate" ? '<div class="small text-muted">estimate</div>' : ""); } },
                { data: "Frequency", render: function (v) { return esc(v); } },
                {
                    data: "NextDue", className: "text-nowrap", render: function (v, t, row) {
                        return v ? esc(row.NextPeriod) + '<div class="small text-muted">due ' + esc(formatDate(v)) + "; draft " + esc(formatDate(row.NextCreate)) + "</div>" : '<span class="text-muted">-</span>';
                    }
                },
                { data: "DraftCount", render: function (v, t, row) { return v + (row.OpenCount ? '<div class="small text-muted">' + row.OpenCount + " not fully paid</div>" : ""); } },
                { data: "StatusLabel", render: function (v, t, row) { return '<span class="badge badge-' + (STATUS_CLASS[row.Status] || "light") + '">' + esc(v) + "</span>"; } },
                { data: null, className: "text-nowrap", render: function (v, t, row) { return '<button type="button" class="btn btn-xs btn-outline-primary fp-open" data-id="' + row.ID + '">' + (row.CanManage ? "Manage" : "View") + "</button>"; } }
            ],
            language: { emptyTable: "No recurring expenses yet.", zeroRecords: "No recurring expenses match these filters." }
        });

        $("#fp-rule-filters").on("change", "select, input[type=checkbox]", function () { table.ajax.reload(); });
        $("#fp-rf-q").on("input", function () {
            clearTimeout(timer);
            timer = setTimeout(function () { table.ajax.reload(); }, 350);
        });
        $("#fp-rule-table").on("click", ".fp-open", function (e) {
            e.preventDefault();
            openDetail($(this).data("id"));
        });
        $("#fp-rule-new").on("click", function () { openForm(0); });
        $("#fp-rule-form")
            .on("submit", saveForm)
            .on("change", "#fp-rule-office", function () { renderOwners(0); renderCategories($("#fp-rule-category").val()); })
            .on("change input", "#fp-rule-start, #fp-rule-frequency, #fp-rule-due-day, #fp-rule-timing, #fp-rule-reminder", preview);
        $("#fp-detail-actions").on("click", ".fp-act", function () {
            var act = $(this).data("act");
            if (act === "edit") {
                openForm(current.rule.ID);
            } else if (act === "generate") {
                generateNow();
            } else {
                setStatus(act);
            }
        });
        $("#fp-detail-modal").on("hidden.bs.modal", function () {
            if (window.location.hash.indexOf("#rule-") === 0) {
                window.history.replaceState(null, "", window.location.pathname);
            }
        });
        var match = /^#rule-(\d+)$/.exec(window.location.hash);
        if (match) {
            openDetail(parseInt(match[1], 10));
        }
    });
})(jQuery, window.FP);
