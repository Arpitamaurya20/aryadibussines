(function ($) {
    "use strict";

    var csrfToken = $('meta[name="fp-csrf-token"]').attr("content") || "";
    var state = { categories: [], companies: [], byId: {}, companyNames: { 0: "All companies" }, table: null, reasonHandler: null };

    var FIELD_LABELS = {
        CategoryCode: "Code",
        CategoryName: "Name",
        Description: "Description",
        ParentCategoryID: "Parent",
        CompanyID: "Company",
        ReceiptRequired: "Receipt required",
        QuotationRequiredAbove: "Quotation required above (INR)",
        AdvanceAllowed: "Advance allowed",
        ReimbursementAllowed: "Reimbursement allowed",
        RecurringAllowed: "Recurring allowed",
        SortOrder: "Sort order",
        IsActive: "Active"
    };
    var FLAG_FIELDS = ["ReceiptRequired", "AdvanceAllowed", "ReimbursementAllowed", "RecurringAllowed", "IsActive"];
    var ACTION_LABELS = { create: "Created", update: "Edited", activate: "Activated", deactivate: "Deactivated", "delete": "Deleted" };

    function esc(value) {
        return String(value === null || value === undefined ? "" : value).replace(/[&<>"']/g, function (c) {
            return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c];
        });
    }

    function request(method, url, data) {
        return $.ajax({ url: url, type: method, data: data || {}, dataType: "json", headers: { "X-CSRF-Token": csrfToken } });
    }

    function failMessage(xhr) {
        var res = xhr && xhr.responseJSON;
        if (res && res.message) {
            return res.message;
        }
        if (xhr && xhr.status === 401) {
            return "Your session has ended. Please log in again.";
        }
        return "Technical problem. Please try again.";
    }

    function handleFailure(xhr) {
        alertify.alert("TechXpert", esc(failMessage(xhr)));
        if (xhr && (xhr.status === 404 || xhr.status === 409)) {
            load();
        }
    }

    /* ---------------- list ---------------- */

    function ruleBadges(row) {
        return [
            ["ReceiptRequired", "Receipt"],
            ["AdvanceAllowed", "Advance"],
            ["ReimbursementAllowed", "Reimbursement"],
            ["RecurringAllowed", "Recurring"]
        ].map(function (rule) {
            var on = parseInt(row[rule[0]], 10) === 1;
            return '<span class="fp-rule ' + (on ? "fp-rule-on" : "fp-rule-off") + '" title="' + esc(FIELD_LABELS[rule[0]] + (on ? ": yes" : ": no")) + '">' + rule[1] + "</span>";
        }).join("") + (row.QuotationRequiredAbove !== null
            ? '<span class="fp-rule fp-rule-on" title="Quotation required for lines above INR ' + esc(row.QuotationRequiredAbove) + '">Quote &gt; ' + esc(row.QuotationRequiredAbove) + "</span>"
            : "");
    }

    function actionButtons(row) {
        var html = '<button type="button" class="btn btn-xs btn-outline-primary mr-1" data-act="edit" data-id="' + row.ID + '" title="Edit"><i class="fa-solid fa-pen"></i></button>';
        if (row.IsActive) {
            html += '<button type="button" class="btn btn-xs btn-outline-warning mr-1" data-act="deactivate" data-id="' + row.ID + '" title="Deactivate"><i class="fa-solid fa-ban"></i></button>';
        } else {
            html += '<button type="button" class="btn btn-xs btn-outline-success mr-1" data-act="activate" data-id="' + row.ID + '" title="Activate"><i class="fa-solid fa-check"></i></button>';
        }
        if (row.CanDelete) {
            html += '<button type="button" class="btn btn-xs btn-outline-danger mr-1" data-act="delete" data-id="' + row.ID + '" title="Delete"><i class="fa-solid fa-trash"></i></button>';
        }
        html += '<button type="button" class="btn btn-xs btn-outline-secondary" data-act="history" data-id="' + row.ID + '" title="History"><i class="fa-solid fa-clock-rotate-left"></i></button>';
        return html;
    }

    function initTable() {
        state.table = $("#fp-category-table").DataTable({
            data: [],
            order: [],
            pageLength: 25,
            autoWidth: false,
            responsive: true,
            language: { emptyTable: "No categories yet. Use Add category to create the first one." },
            columns: [
                {
                    data: "CategoryCode",
                    render: function (d, type) { return type === "display" ? "<code>" + esc(d) + "</code>" : d; }
                },
                {
                    data: "CategoryName",
                    render: function (d, type, row) {
                        if (type !== "display") {
                            return (row.ParentName ? row.ParentName + " " : "") + d + " " + row.Description;
                        }
                        var child = row.ParentCategoryID > 0;
                        var html = '<div class="' + (child ? "fp-child-name" : "font-weight-bold") + '">' + esc(d) + "</div>";
                        if (row.Description) {
                            html += '<div class="small text-muted' + (child ? " pl-4" : "") + '">' + esc(row.Description) + "</div>";
                        }
                        return html;
                    }
                },
                {
                    data: "CompanyName",
                    render: function (d, type) { return type === "display" ? esc(d) : d; }
                },
                { data: null, orderable: false, render: function (d, type, row) { return ruleBadges(row); } },
                {
                    data: "UsageCount",
                    render: function (d, type, row) {
                        if (type !== "display") {
                            return d;
                        }
                        var parts = [];
                        if (row.UsageText) {
                            parts.push(esc(row.UsageText));
                        }
                        if (row.ChildCount) {
                            parts.push(row.ChildCount + " sub-categor" + (row.ChildCount === 1 ? "y" : "ies"));
                        }
                        return parts.length ? parts.join("<br>") : '<span class="text-muted">Not used</span>';
                    }
                },
                {
                    data: "IsActive",
                    render: function (d, type) {
                        if (type !== "display") {
                            return d;
                        }
                        return d ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-secondary">Inactive</span>';
                    }
                },
                {
                    data: "LastChangedAt",
                    render: function (d, type, row) {
                        return type === "display" ? esc(d) + '<div class="small text-muted">' + esc(row.LastChangedBy) + "</div>" : d;
                    }
                },
                { data: null, orderable: false, className: "text-nowrap", render: function (d, type, row) { return actionButtons(row); } }
            ]
        });
    }

    function fillCompanyFilter() {
        var $filter = $("#fp-filter-company");
        var current = $filter.val() || "all";
        var used = {};
        state.categories.forEach(function (c) { used[c.CompanyID] = c.CompanyName; });
        var html = '<option value="all">All</option>';
        Object.keys(used).sort(function (a, b) { return a - b; }).forEach(function (id) {
            html += '<option value="' + esc(id) + '">' + esc(used[id]) + "</option>";
        });
        $filter.html(html).val(used[current] !== undefined ? current : "all");
    }

    function draw() {
        var status = $("#fp-filter-status").val();
        var company = $("#fp-filter-company").val();
        var rows = state.categories.filter(function (c) {
            return (status === "all" || String(c.IsActive) === status) && (company === "all" || String(c.CompanyID) === company);
        });
        state.table.clear().rows.add(rows).draw(false);
    }

    function load() {
        request("GET", "action/list_categories.php").done(function (res) {
            state.categories = res.data.categories || [];
            state.companies = res.data.companies || [];
            state.byId = {};
            state.companyNames = { 0: "All companies" };
            state.companies.forEach(function (c) { state.companyNames[c.ID] = c.CompanyName; });
            state.categories.forEach(function (c) {
                state.byId[c.ID] = c;
                state.companyNames[c.CompanyID] = c.CompanyName;
            });
            fillCompanyFilter();
            draw();
        }).fail(function (xhr) {
            alertify.alert("TechXpert", esc(failMessage(xhr)));
        });
    }

    /* ---------------- create / edit ---------------- */

    function clearErrors() {
        var $form = $("#fp-category-form");
        $form.find(".is-invalid").removeClass("is-invalid");
        $form.find(".invalid-feedback").text("");
        $form.find('div.text-danger[data-for]').text("");
    }

    function showErrors(errors) {
        var $form = $("#fp-category-form");
        $.each(errors || {}, function (field, message) {
            $form.find('[name="' + field + '"]').addClass("is-invalid");
            $form.find('[data-for="' + field + '"]').text(message);
        });
    }

    function categoryLabel(c) {
        return c.CategoryName + " (" + c.CategoryCode + ")" + (c.IsActive ? "" : " - inactive");
    }

    function fillParentOptions(category) {
        var html = '<option value="0">None (top-level category)</option>';
        state.categories.forEach(function (c) {
            if (c.ParentCategoryID !== 0 || (category && c.ID === category.ID)) {
                return;
            }
            if (!c.IsActive && !(category && c.ID === category.ParentCategoryID)) {
                return;
            }
            html += '<option value="' + c.ID + '">' + esc(categoryLabel(c)) + "</option>";
        });
        $("#fp-cat-parent").html(html);
    }

    function fillCompanyOptions(category) {
        var html = '<option value="0">All companies</option>';
        var present = {};
        state.companies.forEach(function (c) {
            present[c.ID] = true;
            html += '<option value="' + c.ID + '">' + esc(c.CompanyName) + "</option>";
        });
        if (category && category.CompanyID > 0 && !present[category.CompanyID]) {
            html += '<option value="' + category.CompanyID + '">' + esc(category.CompanyName + " - inactive") + "</option>";
        }
        $("#fp-cat-company").html(html);
    }

    function openForm(category) {
        var $form = $("#fp-category-form");
        $form[0].reset();
        clearErrors();
        fillParentOptions(category);
        fillCompanyOptions(category);

        var isEdit = !!category;
        $("#fp-category-modal-title").text(isEdit ? "Edit category" : "Add category");
        $("#fp-cat-id").val(isEdit ? category.ID : 0);
        $("#fp-cat-version").val(isEdit ? category.RowVersion : 0);
        $("#fp-cat-code").val(isEdit ? category.CategoryCode : "").prop("readonly", isEdit && category.CodeLocked);
        $("#fp-cat-code-note").text(isEdit && category.CodeLocked
            ? "Locked because the category is used in " + category.UsageText + "."
            : "Short unique code, for example TRAVEL.");
        $("#fp-cat-name").val(isEdit ? category.CategoryName : "");
        $("#fp-cat-description").val(isEdit ? category.Description : "");
        $("#fp-cat-parent").val(isEdit ? String(category.ParentCategoryID) : "0").prop("disabled", isEdit && category.ChildCount > 0);
        $("#fp-cat-company").val(isEdit ? String(category.CompanyID) : "0");
        $("#fp-cat-sort").val(isEdit ? category.SortOrder : 0);
        $("#fp-cat-receipt").prop("checked", isEdit ? category.ReceiptRequired === 1 : true);
        $("#fp-cat-quotation").val(isEdit && category.QuotationRequiredAbove !== null ? category.QuotationRequiredAbove : "");
        $("#fp-cat-advance").prop("checked", isEdit ? category.AdvanceAllowed === 1 : false);
        $("#fp-cat-reimbursement").prop("checked", isEdit ? category.ReimbursementAllowed === 1 : true);
        $("#fp-cat-recurring").prop("checked", isEdit ? category.RecurringAllowed === 1 : false);
        $("#fp-category-modal").modal("show");
    }

    function submitForm(e) {
        e.preventDefault();
        clearErrors();
        var $button = $("#fp-category-save").prop("disabled", true);
        request("POST", "action/save_category.php", $("#fp-category-form").serialize()).done(function (res) {
            $("#fp-category-modal").modal("hide");
            alertify.success(esc(res.message));
            load();
        }).fail(function (xhr) {
            var res = xhr.responseJSON;
            if (xhr.status === 422 && res && res.errors) {
                showErrors(res.errors);
                return;
            }
            if (xhr.status === 404 || xhr.status === 409) {
                $("#fp-category-modal").modal("hide");
            }
            handleFailure(xhr);
        }).always(function () {
            $button.prop("disabled", false);
        });
    }

    /* ---------------- status / delete ---------------- */

    function askReason(options, onConfirm) {
        $("#fp-reason-title").text(options.title);
        $("#fp-reason-text").text(options.text);
        $("#fp-reason-label").text(options.required ? "Reason (required)" : "Reason (optional)");
        $("#fp-reason-input").val("").removeClass("is-invalid");
        $("#fp-reason-confirm").text(options.confirmLabel).attr("class", "btn " + options.buttonClass);
        state.reasonHandler = { required: options.required, onConfirm: onConfirm };
        $("#fp-reason-modal").modal("show");
    }

    function confirmReason() {
        var handler = state.reasonHandler;
        var reason = $.trim($("#fp-reason-input").val());
        if (!handler) {
            return;
        }
        if (handler.required && reason.length < 5) {
            $("#fp-reason-input").addClass("is-invalid");
            return;
        }
        state.reasonHandler = null;
        $("#fp-reason-modal").modal("hide");
        handler.onConfirm(reason);
    }

    function changeStatus(category, activate) {
        askReason({
            title: (activate ? "Activate " : "Deactivate ") + category.CategoryName,
            text: activate
                ? "The category will be available for new expenses again."
                : "The category will stay on existing records but cannot be chosen for new expenses.",
            required: !activate,
            confirmLabel: activate ? "Activate" : "Deactivate",
            buttonClass: activate ? "btn-success" : "btn-warning"
        }, function (reason) {
            request("POST", "action/set_category_status.php", {
                ID: category.ID,
                RowVersion: category.RowVersion,
                IsActive: activate ? 1 : 0,
                Reason: reason
            }).done(function (res) {
                alertify.success(esc(res.message));
                load();
            }).fail(handleFailure);
        });
    }

    function deleteCategory(category) {
        askReason({
            title: "Delete " + category.CategoryName,
            text: "This permanently removes the category. It is allowed only because nothing uses it yet. The change stays in the audit log.",
            required: true,
            confirmLabel: "Delete",
            buttonClass: "btn-danger"
        }, function (reason) {
            request("POST", "action/delete_category.php", {
                ID: category.ID,
                RowVersion: category.RowVersion,
                Reason: reason
            }).done(function (res) {
                alertify.success(esc(res.message));
                load();
            }).fail(handleFailure);
        });
    }

    /* ---------------- history ---------------- */

    function formatValue(field, value) {
        if (value === null || value === undefined || value === "") {
            return field === "ParentCategoryID" ? "None" : "-";
        }
        if (field === "ParentCategoryID") {
            var parent = state.byId[value];
            return parent ? parent.CategoryName + " (" + parent.CategoryCode + ")" : "Category #" + value;
        }
        if (field === "CompanyID") {
            return state.companyNames[value] || "Company #" + value;
        }
        if (FLAG_FIELDS.indexOf(field) !== -1) {
            return parseInt(value, 10) === 1 ? "Yes" : "No";
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
                var before = entry.OldValues ? entry.OldValues[field] : null;
                var after = entry.NewValues ? entry.NewValues[field] : null;
                lines.push('<span class="fp-change"><strong>' + esc(FIELD_LABELS[field] || field) + ":</strong> " +
                    esc(formatValue(field, before)) + " &rarr; " + esc(formatValue(field, after)) + "</span>");
            });
        }
        return lines.length ? lines.join("<br>") : '<span class="text-muted">-</span>';
    }

    function showHistory(category) {
        request("GET", "action/get_category_audit.php", { ID: category.ID }).done(function (res) {
            var c = res.data.category;
            $("#fp-history-title").text("History: " + c.CategoryName + " (" + c.CategoryCode + ")");
            var html = "";
            (res.data.history || []).forEach(function (entry) {
                html += "<tr>" +
                    "<td>" + esc(entry.CreatedAt) + "</td>" +
                    "<td>" + esc(entry.ActorUserName || "System") + "</td>" +
                    "<td>" + esc(ACTION_LABELS[entry.Action] || entry.Action) + "</td>" +
                    "<td>" + describeChanges(entry) + "</td>" +
                    "<td>" + esc(entry.Remarks) + "</td>" +
                    "</tr>";
            });
            $("#fp-history-body").html(html || '<tr><td colspan="5" class="text-muted text-center">No history recorded.</td></tr>');
            $("#fp-history-modal").modal("show");
        }).fail(handleFailure);
    }

    /* ---------------- wiring ---------------- */

    $(function () {
        $("#nav_finance_performance, #nav_fp_group_settings").addClass("active open");
        $("#nav_fp_expense_categories").addClass("active");

        initTable();
        load();

        $("#fp-category-add").on("click", function () { openForm(null); });
        $("#fp-category-form").on("submit", submitForm);
        $("#fp-reason-confirm").on("click", confirmReason);
        $("#fp-filter-status, #fp-filter-company").on("change", draw);
        $("#fp-cat-code").on("input", function () {
            var pos = this.selectionStart;
            this.value = this.value.toUpperCase();
            this.setSelectionRange(pos, pos);
        });
        $("#fp-cat-parent").on("change", function () {
            var parent = state.byId[$(this).val()];
            if (parent && parent.CompanyID > 0) {
                $("#fp-cat-company").val(String(parent.CompanyID));
            }
        });

        $("#fp-category-table").on("click", "button[data-act]", function () {
            var category = state.byId[$(this).data("id")];
            if (!category) {
                return;
            }
            switch ($(this).data("act")) {
                case "edit": openForm(category); break;
                case "activate": changeStatus(category, true); break;
                case "deactivate": changeStatus(category, false); break;
                case "delete": deleteCategory(category); break;
                case "history": showHistory(category); break;
            }
        });
    });
})(jQuery);
