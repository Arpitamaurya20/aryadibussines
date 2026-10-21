(function ($) {
    "use strict";

    var esc = FP.esc;
    var state = { budgets: [], byId: {}, companies: [], states: [], offices: [], categories: [], table: null };

    var FIELD_LABELS = {
        CompanyID: "Legal entity",
        StateID: "State",
        OfficeID: "Branch office",
        CategoryID: "Category",
        PeriodType: "Period type",
        PeriodFrom: "From",
        PeriodTo: "To",
        Amount: "Amount",
        Enforcement: "If exceeded",
        Notes: "Notes",
        IsActive: "Active"
    };
    var ACTION_LABELS = { create: "Created", update: "Edited", adjust: "Amount adjusted", activate: "Activated", deactivate: "Deactivated" };
    var ENFORCEMENT_LABELS = { warn: "Warn", block: "Block" };

    function money(value) {
        return FP.formatMoney(value);
    }

    function nameById(list, id, field) {
        for (var i = 0; i < list.length; i++) {
            if (String(list[i].ID) === String(id)) {
                return list[i][field];
            }
        }
        return null;
    }

    /* ---------------- list ---------------- */

    function usageBar(row) {
        var amount = parseFloat(row.Amount) || 0;
        if (amount <= 0) {
            return "";
        }
        var used = Math.min(parseFloat(row.Committed) / amount * 100, 100);
        var reserved = Math.max(Math.min(parseFloat(row.Reserved) / amount * 100, 100 - used), 0);
        return '<div class="fp-bar' + (row.IsOver ? " fp-over" : "") + '" title="Committed ' + esc(money(row.Committed)) +
            (parseFloat(row.Reserved) > 0 ? ", reserved for category budgets " + esc(money(row.Reserved)) : "") + '">' +
            '<span class="fp-bar-used" style="width:' + used.toFixed(1) + '%"></span>' +
            '<span class="fp-bar-reserved" style="width:' + reserved.toFixed(1) + '%"></span></div>';
    }

    function actionButtons(row) {
        var html = '<button type="button" class="btn btn-xs btn-outline-primary mr-1" data-act="edit" data-id="' + row.ID + '" title="Edit rule and notes"><i class="fa-solid fa-pen"></i></button>';
        if (row.IsActive) {
            html += '<button type="button" class="btn btn-xs btn-outline-info mr-1" data-act="adjust" data-id="' + row.ID + '" title="Adjust amount"><i class="fa-solid fa-scale-balanced"></i></button>';
            html += '<button type="button" class="btn btn-xs btn-outline-warning mr-1" data-act="deactivate" data-id="' + row.ID + '" title="Deactivate"><i class="fa-solid fa-ban"></i></button>';
        } else {
            html += '<button type="button" class="btn btn-xs btn-outline-success mr-1" data-act="activate" data-id="' + row.ID + '" title="Activate"><i class="fa-solid fa-check"></i></button>';
        }
        html += '<button type="button" class="btn btn-xs btn-outline-secondary" data-act="history" data-id="' + row.ID + '" title="History"><i class="fa-solid fa-clock-rotate-left"></i></button>';
        return html;
    }

    function numberColumn(field) {
        return {
            data: field,
            className: "fp-num",
            render: function (d, type) { return type === "display" ? esc(money(d)) : parseFloat(d); }
        };
    }

    function initTable() {
        state.table = $("#fp-budget-table").DataTable({
            data: [],
            order: [],
            pageLength: 25,
            autoWidth: false,
            responsive: true,
            language: { emptyTable: "No budgets match the filters. Use Add budget to create one." },
            columns: [
                {
                    data: "ScopeLabel",
                    render: function (d, type, row) {
                        return type === "display" ? '<div class="small text-muted">' + esc(row.LevelLabel) + "</div>" + esc(d) : d;
                    }
                },
                {
                    data: "CategoryLabel",
                    render: function (d, type, row) {
                        if (type !== "display") {
                            return d;
                        }
                        var html = row.CategoryID === null ? "<strong>" + esc(d) + "</strong>" : esc(d);
                        if (row.CategoryID === null && parseFloat(row.Allocated) > 0) {
                            html += '<div class="small text-muted">' + esc(money(row.Allocated)) + " allocated to categories</div>";
                        }
                        if (row.Notes) {
                            html += '<div class="small text-muted">' + esc(row.Notes) + "</div>";
                        }
                        return html;
                    }
                },
                {
                    data: "PeriodFrom",
                    render: function (d, type, row) {
                        return type === "display" ? esc(row.PeriodLabel) + (row.IsCurrent ? ' <span class="badge badge-light">current</span>' : "") : d;
                    }
                },
                numberColumn("Amount"),
                numberColumn("Pending"),
                numberColumn("Approved"),
                numberColumn("Recognized"),
                {
                    data: "Available",
                    className: "fp-num",
                    render: function (d, type, row) {
                        if (type !== "display") {
                            return parseFloat(d);
                        }
                        return '<span class="' + (row.IsOver ? "text-danger font-weight-bold" : "") + '">' + esc(money(d)) + "</span>" + usageBar(row);
                    }
                },
                {
                    data: "Enforcement",
                    render: function (d, type) {
                        return type === "display" ? '<span class="badge ' + (d === "block" ? "badge-danger" : "badge-warning") + '">' + esc(ENFORCEMENT_LABELS[d] || d) + "</span>" : d;
                    }
                },
                {
                    data: "IsActive",
                    render: function (d, type, row) {
                        if (type !== "display") {
                            return d;
                        }
                        return (d ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-secondary">Inactive</span>') +
                            '<div class="small text-muted">' + esc(row.LastChangedBy) + "</div>";
                    }
                },
                { data: null, orderable: false, className: "text-nowrap", render: function (d, type, row) { return actionButtons(row); } }
            ]
        });
    }

    function fillFilters() {
        var $company = $("#fp-filter-company");
        var current = $company.val() || "all";
        var used = {};
        state.budgets.forEach(function (b) { used[b.CompanyID] = b.CompanyName || "Company #" + b.CompanyID; });
        var html = '<option value="all">All</option>';
        Object.keys(used).forEach(function (id) {
            html += '<option value="' + esc(id) + '">' + esc(used[id]) + "</option>";
        });
        $company.html(html).val(used[current] !== undefined ? current : "all");

        var $category = $("#fp-filter-category");
        var currentCategory = $category.val() || "all";
        var categories = {};
        state.budgets.forEach(function (b) {
            if (b.CategoryID !== null) {
                categories[b.CategoryID] = b.CategoryLabel;
            }
        });
        html = '<option value="all">All</option><option value="overall">Overall budgets only</option>';
        Object.keys(categories).forEach(function (id) {
            html += '<option value="' + esc(id) + '">' + esc(categories[id]) + "</option>";
        });
        $category.html(html).val(currentCategory === "all" || currentCategory === "overall" || categories[currentCategory] !== undefined ? currentCategory : "all");
    }

    function draw() {
        var company = $("#fp-filter-company").val();
        var level = $("#fp-filter-level").val();
        var category = $("#fp-filter-category").val();
        var period = $("#fp-filter-period").val();
        var status = $("#fp-filter-status").val();
        var today = new Date();
        var todayText = today.getFullYear() + "-" + ("0" + (today.getMonth() + 1)).slice(-2) + "-" + ("0" + today.getDate()).slice(-2);
        var rows = state.budgets.filter(function (b) {
            if (company !== "all" && String(b.CompanyID) !== company) { return false; }
            if (level !== "all" && b.Level !== level) { return false; }
            if (category === "overall" && b.CategoryID !== null) { return false; }
            if (category !== "all" && category !== "overall" && String(b.CategoryID) !== category) { return false; }
            if (status !== "all" && String(b.IsActive) !== status) { return false; }
            if (period === "current" && !b.IsCurrent) { return false; }
            if (period === "upcoming" && b.PeriodFrom <= todayText) { return false; }
            if (period === "past" && b.PeriodTo >= todayText) { return false; }
            return true;
        });
        state.table.clear().rows.add(rows).draw(false);
    }

    function load() {
        FP.request("GET", "action/list_budgets.php").done(function (res) {
            state.budgets = res.data.budgets || [];
            state.companies = res.data.companies || [];
            state.states = res.data.states || [];
            state.offices = res.data.offices || [];
            state.categories = res.data.categories || [];
            state.byId = {};
            state.budgets.forEach(function (b) { state.byId[b.ID] = b; });
            fillFilters();
            draw();
        }).fail(function (xhr) { FP.alertFailure(xhr); });
    }

    /* ---------------- create / edit ---------------- */

    function fillOfficeOptions() {
        var company = $("#fp-bud-company").val();
        var html = '<option value="">Choose...</option>';
        state.offices.forEach(function (o) {
            if (String(o.CompanyID) === String(company)) {
                html += '<option value="' + o.ID + '">' + esc(o.OfficeName + " (" + o.OfficeCode + ")") + "</option>";
            }
        });
        $("#fp-bud-office").html(html);
    }

    function fillCategoryOptions() {
        var company = $("#fp-bud-company").val();
        var html = '<option value="0">All categories (overall budget)</option>';
        state.categories.forEach(function (c) {
            if (String(c.CompanyID) === "0" || String(c.CompanyID) === String(company)) {
                html += '<option value="' + c.ID + '">' + esc(c.CategoryName + " (" + c.CategoryCode + ")") + "</option>";
            }
        });
        $("#fp-bud-category").html(html);
    }

    function syncLevel() {
        var level = $("#fp-bud-level").val();
        $("#fp-bud-state-group").toggle(level === "state");
        $("#fp-bud-office-group").toggle(level === "office");
    }

    function syncPeriod() {
        var type = $("#fp-bud-period-type").val();
        $(".fp-period-fields").each(function () {
            $(this).toggle(String($(this).data("period")).split(" ").indexOf(type) !== -1);
        });
    }

    function openForm(budget) {
        var $form = $("#fp-budget-form");
        $form[0].reset();
        FP.clearErrors($form);
        var isEdit = !!budget;
        $("#fp-budget-modal-title").text(isEdit ? "Edit budget" : "Add budget");
        $("#fp-bud-id").val(isEdit ? budget.ID : 0);
        $("#fp-bud-version").val(isEdit ? budget.RowVersion : 0);
        $("#fp-bud-new-fields").toggle(!isEdit);
        $("#fp-bud-locked").toggle(isEdit).html(isEdit
            ? "<strong>" + esc(budget.CategoryLabel) + "</strong>, " + esc(budget.ScopeLabel) + ", " + esc(budget.PeriodLabel) + ": " + esc(money(budget.Amount)) +
              '<div class="mt-1 text-muted">Scope and period cannot change; deactivate this budget and add a new one instead. Use Adjust to change the amount.</div>'
            : "");

        if (!isEdit) {
            var html = '<option value="">Choose...</option>';
            state.companies.forEach(function (c) { html += '<option value="' + c.ID + '">' + esc(c.CompanyName) + "</option>"; });
            $("#fp-bud-company").html(html);
            html = '<option value="">Choose...</option>';
            state.states.forEach(function (s) { html += '<option value="' + s.ID + '">' + esc(s.StateName) + "</option>"; });
            $("#fp-bud-state").html(html);
            fillOfficeOptions();
            fillCategoryOptions();
            var now = new Date();
            $("#fp-bud-month").val(now.getFullYear() + "-" + ("0" + (now.getMonth() + 1)).slice(-2));
            syncLevel();
            syncPeriod();
        }
        $("#fp-bud-enforcement").val(isEdit ? budget.Enforcement : "warn");
        $("#fp-bud-notes").val(isEdit ? budget.Notes : "");
        $("#fp-budget-modal").modal("show");
    }

    function submitForm(e) {
        e.preventDefault();
        var $form = $("#fp-budget-form");
        FP.clearErrors($form);
        var $button = $("#fp-budget-save").prop("disabled", true);
        FP.request("POST", "action/save_budget.php", $form.serialize()).done(function (res) {
            $("#fp-budget-modal").modal("hide");
            alertify.success(esc(res.message));
            load();
        }).fail(function (xhr) {
            var res = xhr.responseJSON;
            if (xhr.status === 422 && res && res.errors) {
                FP.showErrors($form, res.errors);
                return;
            }
            if (xhr.status === 404 || xhr.status === 409) {
                $("#fp-budget-modal").modal("hide");
            }
            FP.alertFailure(xhr, load);
        }).always(function () {
            $button.prop("disabled", false);
        });
    }

    /* ---------------- adjust / status ---------------- */

    function openAdjust(budget) {
        var $form = $("#fp-adjust-form");
        $form[0].reset();
        FP.clearErrors($form);
        $("#fp-adj-id").val(budget.ID);
        $("#fp-adj-version").val(budget.RowVersion);
        $("#fp-adj-label").html("<strong>" + esc(budget.CategoryLabel) + "</strong>, " + esc(budget.ScopeLabel) + ", " + esc(budget.PeriodLabel));
        $("#fp-adj-current").text("Current amount " + money(budget.Amount) + ". Committed so far " + money(budget.Committed) +
            (budget.CategoryID === null && parseFloat(budget.Allocated) > 0 ? ". Allocated to category budgets " + money(budget.Allocated) : "") + ".");
        $("#fp-adjust-modal").modal("show");
    }

    function submitAdjust(e) {
        e.preventDefault();
        var $form = $("#fp-adjust-form");
        FP.clearErrors($form);
        var $button = $("#fp-adjust-save").prop("disabled", true);
        FP.request("POST", "action/adjust_budget.php", $form.serialize()).done(function (res) {
            $("#fp-adjust-modal").modal("hide");
            alertify.alert("TechXpert", esc(res.message));
            load();
        }).fail(function (xhr) {
            var res = xhr.responseJSON;
            if (xhr.status === 422 && res && res.errors) {
                FP.showErrors($form, res.errors);
                return;
            }
            if (xhr.status === 404 || xhr.status === 409) {
                $("#fp-adjust-modal").modal("hide");
            }
            FP.alertFailure(xhr, load);
        }).always(function () {
            $button.prop("disabled", false);
        });
    }

    function changeStatus(budget, activate) {
        FP.askReason({
            title: (activate ? "Activate budget" : "Deactivate budget"),
            text: budget.CategoryLabel + ", " + budget.ScopeLabel + ", " + budget.PeriodLabel + ". " + (activate
                ? "Requests will be checked against it again."
                : "Requests will no longer be checked against it. It stays in the list and history."),
            required: !activate,
            confirmLabel: activate ? "Activate" : "Deactivate",
            buttonClass: activate ? "btn-success" : "btn-warning"
        }, function (reason) {
            FP.request("POST", "action/set_budget_status.php", {
                ID: budget.ID,
                RowVersion: budget.RowVersion,
                IsActive: activate ? 1 : 0,
                Reason: reason
            }).done(function (res) {
                alertify.success(esc(res.message));
                load();
            }).fail(function (xhr) { FP.alertFailure(xhr, load); });
        });
    }

    /* ---------------- history ---------------- */

    function formatValue(field, value) {
        if (value === null || value === undefined || value === "") {
            return field === "CategoryID" ? "All categories" : "-";
        }
        switch (field) {
            case "CompanyID": return nameById(state.companies, value, "CompanyName") || "Company #" + value;
            case "StateID": return String(value) === "0" ? "All states" : (nameById(state.states, value, "StateName") || "State #" + value);
            case "OfficeID": return String(value) === "0" ? "All offices" : (nameById(state.offices, value, "OfficeName") || "Office #" + value);
            case "CategoryID": return nameById(state.categories, value, "CategoryName") || "Category #" + value;
            case "Amount": return money(value);
            case "Enforcement": return ENFORCEMENT_LABELS[value] || value;
            case "IsActive": return parseInt(value, 10) === 1 ? "Yes" : "No";
        }
        return String(value);
    }

    function describeChanges(entry) {
        var lines = [];
        if (entry.Action === "create" && entry.NewValues) {
            Object.keys(FIELD_LABELS).forEach(function (field) {
                if (Object.prototype.hasOwnProperty.call(entry.NewValues, field)) {
                    lines.push("<strong>" + esc(FIELD_LABELS[field]) + ":</strong> " + esc(formatValue(field, entry.NewValues[field])));
                }
            });
        } else {
            (entry.ChangedFields || []).forEach(function (field) {
                lines.push("<strong>" + esc(FIELD_LABELS[field] || field) + ":</strong> " +
                    esc(formatValue(field, entry.OldValues ? entry.OldValues[field] : null)) + " &rarr; " +
                    esc(formatValue(field, entry.NewValues ? entry.NewValues[field] : null)));
            });
        }
        return lines.length ? lines.join("<br>") : '<span class="text-muted">-</span>';
    }

    function showHistory(budget) {
        FP.request("GET", "action/get_budget_history.php", { ID: budget.ID }).done(function (res) {
            $("#fp-history-title").text("History: " + res.data.budget.Label);
            var html = "";
            (res.data.adjustments || []).forEach(function (a) {
                var change = parseFloat(a.ChangeAmount);
                html += "<tr><td>" + esc(a.AdjustedAt) + "</td><td>" + esc(a.AdjustedBy) + "</td>" +
                    '<td class="text-right">' + esc(money(a.OldAmount)) + '</td><td class="text-right">' + esc(money(a.NewAmount)) + "</td>" +
                    '<td class="text-right ' + (change < 0 ? "text-danger" : "text-success") + '">' + (change > 0 ? "+" : "") + esc(money(a.ChangeAmount)) + "</td>" +
                    "<td>" + esc(a.Reason) + "</td></tr>";
            });
            $("#fp-adjustments-body").html(html || '<tr><td colspan="6" class="text-muted text-center">No amount changes.</td></tr>');
            html = "";
            (res.data.history || []).forEach(function (entry) {
                html += "<tr><td>" + esc(entry.CreatedAt) + "</td><td>" + esc(entry.ActorUserName || "System") + "</td>" +
                    "<td>" + esc(ACTION_LABELS[entry.Action] || entry.Action) + "</td><td>" + describeChanges(entry) + "</td>" +
                    "<td>" + esc(entry.Remarks) + "</td></tr>";
            });
            $("#fp-history-body").html(html || '<tr><td colspan="5" class="text-muted text-center">No history recorded.</td></tr>');
            $("#fp-history-modal").modal("show");
        }).fail(function (xhr) { FP.alertFailure(xhr, load); });
    }

    /* ---------------- wiring ---------------- */

    $(function () {
        FP.activateNav("", "budgets");
        initTable();
        load();

        $("#fp-budget-add").on("click", function () { openForm(null); });
        $("#fp-budget-form").on("submit", submitForm);
        $("#fp-adjust-form").on("submit", submitAdjust);
        $("#fp-bud-level").on("change", syncLevel);
        $("#fp-bud-period-type").on("change", syncPeriod);
        $("#fp-bud-company").on("change", function () {
            fillOfficeOptions();
            fillCategoryOptions();
        });
        $("#fp-filter-company, #fp-filter-level, #fp-filter-category, #fp-filter-period, #fp-filter-status").on("change", draw);

        $("#fp-budget-table").on("click", "button[data-act]", function () {
            var budget = state.byId[$(this).data("id")];
            if (!budget) {
                return;
            }
            switch ($(this).data("act")) {
                case "edit": openForm(budget); break;
                case "adjust": openAdjust(budget); break;
                case "activate": changeStatus(budget, true); break;
                case "deactivate": changeStatus(budget, false); break;
                case "history": showHistory(budget); break;
            }
        });
    });
})(jQuery);
