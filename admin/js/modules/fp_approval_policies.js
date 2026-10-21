(function ($) {
    "use strict";

    var esc = FP.esc;
    var state = {
        policies: [], byId: {}, companies: [], states: [], offices: [], categories: [], roles: [], types: [],
        delegations: [], delegationById: {}, policyTable: null, delegationTable: null, readonly: false
    };

    var FIELD_LABELS = {
        PolicyName: "Name",
        Description: "Description",
        CompanyID: "Legal entity",
        StateID: "State",
        OfficeID: "Branch office",
        RequestType: "Expense type",
        CategoryID: "Category",
        MinAmount: "Total from",
        MaxAmount: "Total up to",
        Priority: "Priority",
        EffectiveFrom: "Effective from",
        EffectiveTo: "Effective to",
        Status: "Status",
        Stages: "Stages",
        DelegatorEmployeeID: "Approver away",
        DelegateEmployeeID: "Delegate",
        FromDate: "From",
        ToDate: "To",
        IsActive: "Active"
    };
    var ACTION_LABELS = {
        create: "Created", update: "Edited", publish: "Published", superseded: "End date set", retire: "Retired",
        "delete": "Draft deleted", cancel: "Cancelled"
    };
    var STATUS_BADGES = { draft: "badge-info", active: "badge-success", retired: "badge-secondary" };
    var DELEGATION_BADGES = { active: "badge-success", scheduled: "badge-info", ended: "badge-secondary", cancelled: "badge-secondary" };

    function nameById(list, id, field) {
        for (var i = 0; i < list.length; i++) {
            if (String(list[i].ID) === String(id)) {
                return list[i][field];
            }
        }
        return null;
    }

    function roleLabel(key) {
        for (var i = 0; i < state.roles.length; i++) {
            if (state.roles[i].key === key) {
                return state.roles[i].label;
            }
        }
        return key;
    }

    function today() {
        var d = new Date();
        return d.getFullYear() + "-" + ("0" + (d.getMonth() + 1)).slice(-2) + "-" + ("0" + d.getDate()).slice(-2);
    }

    function formatDate(value) {
        return value ? value : "";
    }

    function failForm($form, $modal, reload) {
        return function (xhr) {
            var res = xhr.responseJSON;
            if (xhr.status === 422 && res && res.errors) {
                showFormErrors($form, res.errors);
                if (res.message) {
                    alertify.error(esc(res.message));
                }
                return;
            }
            if (xhr.status === 404 || xhr.status === 409) {
                $modal.modal("hide");
            }
            FP.alertFailure(xhr, reload);
        };
    }

    function showFormErrors($form, errors) {
        var plain = {};
        $.each(errors || {}, function (key, message) {
            var match = /^stages\.(\d+)\.(\w+)$/.exec(key);
            if (!match) {
                plain[key] = message;
                return;
            }
            var $row = $("#fp-stage-list .fp-stage-row").eq(parseInt(match[1], 10));
            var field = match[2];
            if (/^(Approver|EscalateTo)(Type|Role|EmployeeID)$/.test(field) && !$row.find('[data-field="' + field + '"]').is(":visible")) {
                field = field.replace(/(Type|Role|EmployeeID)$/, "Type");
            }
            $row.find('[data-field="' + field + '"]').addClass("is-invalid");
            $row.find(".fp-stage-errors").append("<div>" + esc(message) + "</div>");
        });
        FP.showErrors($form, plain);
    }

    function clearFormErrors($form) {
        FP.clearErrors($form);
        $form.find(".fp-stage-errors").empty();
    }

    /* ---------------- policy list ---------------- */

    function policyButtons(row) {
        var html = "";
        if (row.Status === "draft") {
            html += '<button type="button" class="btn btn-xs btn-outline-primary mr-1" data-act="edit" data-id="' + row.ID + '" title="Edit draft"><i class="fa-solid fa-pen"></i></button>';
            html += '<button type="button" class="btn btn-xs btn-outline-success mr-1" data-act="publish" data-id="' + row.ID + '" title="Publish"><i class="fa-solid fa-circle-check"></i></button>';
            html += '<button type="button" class="btn btn-xs btn-outline-danger mr-1" data-act="delete" data-id="' + row.ID + '" title="Delete draft"><i class="fa-solid fa-trash"></i></button>';
        } else {
            html += '<button type="button" class="btn btn-xs btn-outline-primary mr-1" data-act="view" data-id="' + row.ID + '" title="View"><i class="fa-solid fa-eye"></i></button>';
            if (row.IsLatest && !row.CodeHasDraft) {
                html += '<button type="button" class="btn btn-xs btn-outline-info mr-1" data-act="version" data-id="' + row.ID + '" title="Create new version"><i class="fa-solid fa-code-branch"></i></button>';
            }
            if (row.Status === "active") {
                html += '<button type="button" class="btn btn-xs btn-outline-warning mr-1" data-act="retire" data-id="' + row.ID + '" title="Retire"><i class="fa-solid fa-ban"></i></button>';
            }
        }
        html += '<button type="button" class="btn btn-xs btn-outline-secondary" data-act="history" data-id="' + row.ID + '" title="History"><i class="fa-solid fa-clock-rotate-left"></i></button>';
        return html;
    }

    function initPolicyTable() {
        state.policyTable = $("#fp-policy-table").DataTable({
            data: [],
            order: [],
            pageLength: 25,
            autoWidth: false,
            responsive: true,
            language: { emptyTable: "No policies match the filter. Use New policy to create one." },
            columns: [
                {
                    data: "PolicyCode",
                    render: function (d, type, row) {
                        if (type !== "display") {
                            return d + " " + row.PolicyName;
                        }
                        return "<strong>" + esc(d) + "</strong> v" + esc(row.Version) + "<div>" + esc(row.PolicyName) + "</div>" +
                            (row.Description ? '<div class="small text-muted">' + esc(row.Description) + "</div>" : "");
                    }
                },
                {
                    data: "ScopeLabel",
                    render: function (d, type, row) {
                        return type === "display" ? esc(d) + "<br>" + esc(row.TypeLabel) + ", " + esc(row.CategoryLabel) : d;
                    }
                },
                { data: "AmountLabel", render: function (d, type) { return type === "display" ? esc(d) : d; } },
                { data: "Priority" },
                {
                    data: "EffectiveFrom",
                    render: function (d, type, row) {
                        return type === "display" ? esc(d) + " to " + esc(row.EffectiveTo || "open") : d;
                    }
                },
                {
                    data: "Stages",
                    orderable: false,
                    render: function (d, type) {
                        if (type !== "display") {
                            return d.join(" ");
                        }
                        return '<ul class="fp-stage-list">' + d.map(function (s) { return "<li>" + esc(s) + "</li>"; }).join("") + "</ul>";
                    }
                },
                {
                    data: "Status",
                    render: function (d, type, row) {
                        if (type !== "display") {
                            return d;
                        }
                        return '<span class="badge ' + (STATUS_BADGES[d] || "badge-light") + '">' + esc(row.StatusLabel) + "</span>" +
                            (row.InEffect ? ' <span class="badge badge-light">in effect</span>' : "") +
                            (row.UsedCount ? '<div class="small text-muted">Used by ' + esc(row.UsedCount) + " request(s)</div>" : "") +
                            '<div class="small text-muted">' + esc(row.LastChangedBy) + "</div>";
                    }
                },
                { data: null, orderable: false, className: "text-nowrap", render: function (d, type, row) { return policyButtons(row); } }
            ]
        });
    }

    function drawPolicies() {
        var status = $("#fp-filter-status").val();
        var rows = state.policies.filter(function (p) {
            if (status === "current") {
                return p.Status !== "retired";
            }
            return status === "all" || p.Status === status;
        });
        state.policyTable.clear().rows.add(rows).draw(false);
    }

    function fillTestForm() {
        var html = '<option value="">Choose...</option>';
        state.offices.forEach(function (o) { html += '<option value="' + o.ID + '">' + esc(o.OfficeName + " (" + o.OfficeCode + ")") + "</option>"; });
        $("#fp-test-office").html(html);
        html = '<option value="">Choose...</option>';
        state.types.forEach(function (t) { html += '<option value="' + esc(t.key) + '">' + esc(t.label) + "</option>"; });
        $("#fp-test-type").html(html);
        html = "";
        state.categories.forEach(function (c) { html += '<option value="' + c.ID + '">' + esc(c.CategoryName + " (" + c.CategoryCode + ")") + "</option>"; });
        $("#fp-test-categories").html(html);
        if (!$("#fp-test-date").val()) {
            $("#fp-test-date").val(today());
        }
    }

    function loadPolicies() {
        FP.request("GET", "action/list_policies.php").done(function (res) {
            state.policies = res.data.policies || [];
            state.companies = res.data.companies || [];
            state.states = res.data.states || [];
            state.offices = res.data.offices || [];
            state.categories = res.data.categories || [];
            state.roles = res.data.roles || [];
            state.types = res.data.types || [];
            state.byId = {};
            state.policies.forEach(function (p) { state.byId[p.ID] = p; });
            drawPolicies();
            fillTestForm();
        }).fail(function (xhr) { FP.alertFailure(xhr); });
    }

    /* ---------------- policy editor ---------------- */

    function employeeSelect($select, $parent, selectedId, selectedText) {
        $select.empty();
        if (selectedId) {
            $select.append(new Option(selectedText || "Employee #" + selectedId, selectedId, true, true));
        }
        $select.select2({
            dropdownParent: $parent,
            placeholder: "Search by name or employee number",
            minimumInputLength: 2,
            allowClear: true,
            width: "100%",
            ajax: {
                url: "action/search_policy_employees.php",
                dataType: "json",
                delay: 300,
                data: function (params) { return { q: params.term }; },
                processResults: function (res) { return { results: (res.data && res.data.results) || [] }; }
            }
        });
    }

    function fillPolicyOptions() {
        var company = $("#fp-pol-company").val();
        var stateId = $("#fp-pol-state").val();
        var office = $("#fp-pol-office").val();
        var category = $("#fp-pol-category").val();
        var html = '<option value="0">Any branch office</option>';
        state.offices.forEach(function (o) {
            if ((company === "0" || String(o.CompanyID) === company) && (stateId === "0" || String(o.StateID) === stateId)) {
                html += '<option value="' + o.ID + '">' + esc(o.OfficeName + " (" + o.OfficeCode + ")") + "</option>";
            }
        });
        $("#fp-pol-office").html(html).val($("#fp-pol-office option[value='" + office + "']").length ? office : "0");
        html = '<option value="0">Any category</option>';
        state.categories.forEach(function (c) {
            if (company === "0" || String(c.CompanyID) === "0" || String(c.CompanyID) === company) {
                html += '<option value="' + c.ID + '">' + esc((c.ParentCategoryID ? "\u2014 " : "") + c.CategoryName + " (" + c.CategoryCode + ")") + "</option>";
            }
        });
        $("#fp-pol-category").html(html).val($("#fp-pol-category option[value='" + category + "']").length ? category : "0");
    }

    function fillPolicySelects(policy) {
        var html = '<option value="0">Any legal entity</option>';
        state.companies.forEach(function (c) { html += '<option value="' + c.ID + '">' + esc(c.CompanyName) + "</option>"; });
        $("#fp-pol-company").html(html).val(String(policy ? policy.CompanyID : 0));
        if ($("#fp-pol-company").val() === null) {
            $("#fp-pol-company").append('<option value="' + policy.CompanyID + '">Company #' + esc(policy.CompanyID) + "</option>").val(String(policy.CompanyID));
        }
        html = '<option value="0">Any state</option>';
        state.states.forEach(function (s) { html += '<option value="' + s.ID + '">' + esc(s.StateName) + "</option>"; });
        $("#fp-pol-state").html(html).val(String(policy ? policy.StateID : 0));
        html = '<option value="">Any expense type</option>';
        state.types.forEach(function (t) { html += '<option value="' + esc(t.key) + '">' + esc(t.label) + "</option>"; });
        $("#fp-pol-type").html(html).val(policy ? policy.RequestType : "");
        $("#fp-pol-office").html('<option value="' + (policy ? policy.OfficeID : 0) + '"></option>');
        $("#fp-pol-category").html('<option value="' + (policy && policy.CategoryID !== null ? policy.CategoryID : 0) + '"></option>');
        fillPolicyOptions();
        if (policy && policy.OfficeID && $("#fp-pol-office").val() !== String(policy.OfficeID)) {
            $("#fp-pol-office").append('<option value="' + policy.OfficeID + '">Office #' + esc(policy.OfficeID) + " (inactive)</option>").val(String(policy.OfficeID));
        }
        if (policy && policy.CategoryID !== null && $("#fp-pol-category").val() !== String(policy.CategoryID)) {
            $("#fp-pol-category").append('<option value="' + policy.CategoryID + '">Category #' + esc(policy.CategoryID) + " (inactive)</option>").val(String(policy.CategoryID));
        }
    }

    function roleOptions() {
        var html = '<option value="">Choose...</option>';
        state.roles.forEach(function (r) { html += '<option value="' + esc(r.key) + '">' + esc(r.label) + "</option>"; });
        return html;
    }

    function syncStageRow($row) {
        var type = $row.find('[data-field="ApproverType"]').val();
        $row.find(".fp-when-role").toggle(type === "role");
        $row.find(".fp-when-employee").toggle(type === "employee");
        var escalates = $.trim($row.find('[data-field="EscalateAfterHours"]').val()) !== "";
        var escType = $row.find('[data-field="EscalateToType"]').val();
        $row.find(".fp-when-escalate").toggle(escalates);
        $row.find(".fp-when-esc-role").toggle(escalates && escType === "role");
        $row.find(".fp-when-esc-employee").toggle(escalates && escType === "employee");
    }

    function renumberStages() {
        $("#fp-stage-list .fp-stage-row").each(function (index) {
            $(this).find(".fp-stage-no").text(index + 1);
        });
    }

    function addStageRow(stage) {
        var $row = $($("#fp-stage-template").html());
        var $modal = $("#fp-policy-modal");
        $row.find(".fp-role-select").html(roleOptions());
        $("#fp-stage-list").append($row);
        stage = stage || { ApproverType: "role", IsMandatory: 1, EscalateToType: "role" };
        $row.find('[data-field="StageName"]').val(stage.StageName || "");
        $row.find('[data-field="ApproverType"]').val(stage.ApproverType || "role");
        $row.find('[data-field="ApproverRole"]').val(stage.ApproverRole || "");
        $row.find('[data-field="IsMandatory"]').val(String(stage.IsMandatory === 0 ? 0 : 1));
        $row.find('[data-field="ApplyFromAmount"]').val(stage.ApplyFromAmount === null || stage.ApplyFromAmount === undefined ? "" : stage.ApplyFromAmount);
        $row.find('[data-field="EscalateAfterHours"]').val(stage.EscalateAfterHours === null || stage.EscalateAfterHours === undefined ? "" : stage.EscalateAfterHours);
        $row.find('[data-field="EscalateToType"]').val(stage.EscalateToType || "role");
        $row.find('[data-field="EscalateToRole"]').val(stage.EscalateToRole || "");
        employeeSelect($row.find('[data-field="ApproverEmployeeID"]'), $modal, stage.ApproverEmployeeID, stage.ApproverEmployeeName);
        employeeSelect($row.find('[data-field="EscalateToEmployeeID"]'), $modal, stage.EscalateToEmployeeID, stage.EscalateToEmployeeName);
        syncStageRow($row);
        renumberStages();
    }

    function setReadonly(readonly) {
        state.readonly = readonly;
        $("#fp-policy-form").toggleClass("fp-readonly", readonly);
        $("#fp-pol-fields").prop("disabled", readonly);
        $("#fp-pol-fields .fp-employee-select").prop("disabled", readonly);
    }

    function openPolicy(id) {
        var $form = $("#fp-policy-form");
        $form[0].reset();
        clearFormErrors($form);
        $("#fp-stage-list").find(".fp-employee-select").each(function () {
            if ($(this).data("select2")) {
                $(this).select2("destroy");
            }
        });
        $("#fp-stage-list").empty();
        if (!id) {
            $("#fp-policy-modal-title").text("New policy");
            $("#fp-pol-id").val(0);
            $("#fp-pol-version").val(0);
            $("#fp-pol-code").prop("readonly", false);
            $("#fp-pol-priority").val("100");
            $("#fp-pol-from").val(today());
            $("#fp-pol-readonly-note").hide();
            setReadonly(false);
            fillPolicySelects(null);
            addStageRow(null);
            $("#fp-policy-modal").modal("show");
            return;
        }
        FP.request("GET", "action/get_policy.php", { ID: id }).done(function (res) {
            var policy = res.data.policy;
            var draft = policy.Status === "draft";
            $("#fp-policy-modal-title").text((draft ? "Edit draft: " : "View: ") + policy.PolicyCode + " v" + policy.Version);
            $("#fp-pol-id").val(policy.ID);
            $("#fp-pol-version").val(policy.RowVersion);
            $("#fp-pol-code").val(policy.PolicyCode).prop("readonly", true);
            $("#fp-pol-name").val(policy.PolicyName);
            $("#fp-pol-description").val(policy.Description);
            $("#fp-pol-min").val(parseFloat(policy.MinAmount) ? policy.MinAmount : "");
            $("#fp-pol-max").val(policy.MaxAmount === null ? "" : policy.MaxAmount);
            $("#fp-pol-priority").val(policy.Priority);
            $("#fp-pol-from").val(policy.EffectiveFrom);
            $("#fp-pol-to").val(policy.EffectiveTo || "");
            $("#fp-pol-readonly-note").toggle(!draft).text(draft ? "" : "This version is " + policy.Status + " and cannot be changed. Use \"Create new version\" to change the rules.");
            fillPolicySelects(policy);
            res.data.stages.forEach(function (stage) { addStageRow(stage); });
            setReadonly(!draft);
            $("#fp-policy-modal").modal("show");
        }).fail(function (xhr) { FP.alertFailure(xhr, loadPolicies); });
    }

    function policyFormData() {
        var data = $("#fp-policy-form").serializeArray();
        $("#fp-stage-list .fp-stage-row").each(function (index) {
            var $row = $(this);
            var type = $row.find('[data-field="ApproverType"]').val();
            var escalates = $.trim($row.find('[data-field="EscalateAfterHours"]').val()) !== "";
            var escType = $row.find('[data-field="EscalateToType"]').val();
            var values = {
                StageName: $row.find('[data-field="StageName"]').val(),
                ApproverType: type,
                ApproverRole: type === "role" ? $row.find('[data-field="ApproverRole"]').val() : "",
                ApproverEmployeeID: type === "employee" ? ($row.find('[data-field="ApproverEmployeeID"]').val() || 0) : 0,
                IsMandatory: $row.find('[data-field="IsMandatory"]').val(),
                ApplyFromAmount: $row.find('[data-field="ApplyFromAmount"]').val(),
                EscalateAfterHours: $row.find('[data-field="EscalateAfterHours"]').val(),
                EscalateToType: escalates ? escType : "",
                EscalateToRole: escalates && escType === "role" ? $row.find('[data-field="EscalateToRole"]').val() : "",
                EscalateToEmployeeID: escalates && escType === "employee" ? ($row.find('[data-field="EscalateToEmployeeID"]').val() || 0) : 0
            };
            $.each(values, function (field, value) {
                data.push({ name: "stages[" + index + "][" + field + "]", value: value === null ? "" : value });
            });
        });
        return $.param(data);
    }

    function submitPolicy(e) {
        e.preventDefault();
        if (state.readonly) {
            return;
        }
        var $form = $("#fp-policy-form");
        clearFormErrors($form);
        var $button = $("#fp-policy-save").prop("disabled", true);
        FP.request("POST", "action/save_policy.php", policyFormData()).done(function (res) {
            $("#fp-policy-modal").modal("hide");
            alertify.success(esc(res.message));
            loadPolicies();
        }).fail(failForm($form, $("#fp-policy-modal"), loadPolicies)).always(function () {
            $button.prop("disabled", false);
        });
    }

    function moveStage($row, direction) {
        if (direction === "up" && $row.prev(".fp-stage-row").length) {
            $row.insertBefore($row.prev(".fp-stage-row"));
        } else if (direction === "down" && $row.next(".fp-stage-row").length) {
            $row.insertAfter($row.next(".fp-stage-row"));
        }
        renumberStages();
    }

    /* ---------------- policy lifecycle ---------------- */

    function publishPolicy(policy) {
        alertify.confirm("Publish policy",
            "Publish " + esc(policy.PolicyCode) + " version " + esc(policy.Version) + "? It applies to requests submitted from " + esc(policy.EffectiveFrom) +
            " and can no longer be edited." + (policy.Version > 1 ? " The previous version will end the day before." : ""),
            function () {
                FP.request("POST", "action/publish_policy.php", { ID: policy.ID, RowVersion: policy.RowVersion }).done(function (res) {
                    alertify.alert("TechXpert", esc(res.message));
                    loadPolicies();
                }).fail(function (xhr) { FP.alertFailure(xhr, loadPolicies); });
            }, function () {}).set("labels", { ok: "Publish", cancel: "Cancel" });
    }

    function newVersion(policy) {
        FP.request("POST", "action/new_policy_version.php", { ID: policy.ID }).done(function (res) {
            alertify.success(esc(res.message));
            loadPolicies();
            openPolicy(res.data.ID);
        }).fail(function (xhr) { FP.alertFailure(xhr, loadPolicies); });
    }

    function retirePolicy(policy) {
        FP.askReason({
            title: "Retire policy",
            text: policy.PolicyCode + " v" + policy.Version + " will no longer be chosen for new requests. Requests already submitted keep their approval route. " +
                "Make sure another policy covers these requests, or they cannot be submitted.",
            required: true,
            confirmLabel: "Retire",
            buttonClass: "btn-warning"
        }, function (reason) {
            FP.request("POST", "action/retire_policy.php", { ID: policy.ID, RowVersion: policy.RowVersion, Reason: reason }).done(function (res) {
                alertify.success(esc(res.message));
                loadPolicies();
            }).fail(function (xhr) { FP.alertFailure(xhr, loadPolicies); });
        });
    }

    function deleteDraft(policy) {
        alertify.confirm("Delete draft", "Delete the draft " + esc(policy.PolicyCode) + " v" + esc(policy.Version) + "? This is recorded in the history.", function () {
            FP.request("POST", "action/delete_policy_draft.php", { ID: policy.ID, RowVersion: policy.RowVersion }).done(function (res) {
                alertify.success(esc(res.message));
                loadPolicies();
            }).fail(function (xhr) { FP.alertFailure(xhr, loadPolicies); });
        }, function () {}).set("labels", { ok: "Delete", cancel: "Cancel" });
    }

    /* ---------------- history ---------------- */

    function formatValue(field, value) {
        if (field === "Stages") {
            return (value || []).map(function (s) {
                var who = s.ApproverType === "role" ? roleLabel(s.ApproverRole) : "Employee #" + s.ApproverEmployeeID;
                return s.StageNo + ". " + s.StageName + " (" + who + (parseInt(s.IsMandatory, 10) === 1 ? "" : ", optional") +
                    (s.ApplyFromAmount !== null ? ", from " + FP.formatMoney(s.ApplyFromAmount) : "") +
                    (s.EscalateAfterHours !== null ? ", escalate after " + s.EscalateAfterHours + " h" : "") + ")";
            }).join("; ");
        }
        if (value === null || value === undefined || value === "") {
            switch (field) {
                case "CategoryID": return "Any category";
                case "MaxAmount": return "No limit";
                case "EffectiveTo": return "Open";
                case "RequestType": return "Any expense type";
            }
            return "-";
        }
        switch (field) {
            case "CompanyID": return String(value) === "0" ? "Any legal entity" : (nameById(state.companies, value, "CompanyName") || "Company #" + value);
            case "StateID": return String(value) === "0" ? "Any state" : (nameById(state.states, value, "StateName") || "State #" + value);
            case "OfficeID": return String(value) === "0" ? "Any branch office" : (nameById(state.offices, value, "OfficeName") || "Office #" + value);
            case "CategoryID": return nameById(state.categories, value, "CategoryName") || "Category #" + value;
            case "MinAmount":
            case "MaxAmount": return FP.formatMoney(value);
            case "IsActive": return parseInt(value, 10) === 1 ? "Yes" : "No";
        }
        return String(value);
    }

    function describeChanges(entry) {
        var lines = [];
        var fields = entry.Action === "create" || entry.Action === "delete"
            ? Object.keys(FIELD_LABELS).filter(function (f) {
                var values = entry.Action === "create" ? entry.NewValues : entry.OldValues;
                return values && Object.prototype.hasOwnProperty.call(values, f);
            })
            : (entry.ChangedFields || []);
        fields.forEach(function (field) {
            if (entry.Action === "create") {
                lines.push("<strong>" + esc(FIELD_LABELS[field] || field) + ":</strong> " + esc(formatValue(field, entry.NewValues[field])));
            } else if (entry.Action === "delete") {
                lines.push("<strong>" + esc(FIELD_LABELS[field] || field) + ":</strong> " + esc(formatValue(field, entry.OldValues[field])));
            } else {
                lines.push("<strong>" + esc(FIELD_LABELS[field] || field) + ":</strong> " +
                    esc(formatValue(field, entry.OldValues ? entry.OldValues[field] : null)) + " &rarr; " +
                    esc(formatValue(field, entry.NewValues ? entry.NewValues[field] : null)));
            }
        });
        return lines.length ? lines.join("<br>") : '<span class="text-muted">-</span>';
    }

    function showHistory(policy) {
        FP.request("GET", "action/get_policy_history.php", { ID: policy.ID }).done(function (res) {
            $("#fp-history-title").text("History: " + res.data.policy.Label);
            var html = "";
            (res.data.history || []).forEach(function (entry) {
                html += "<tr><td>" + esc(entry.CreatedAt) + "</td><td>" + esc(entry.ActorUserName || "System") + "</td>" +
                    "<td>" + esc(ACTION_LABELS[entry.Action] || entry.Action) + "</td><td>" + describeChanges(entry) + "</td>" +
                    "<td>" + esc(entry.Remarks) + "</td></tr>";
            });
            $("#fp-history-body").html(html || '<tr><td colspan="5" class="text-muted text-center">No history recorded.</td></tr>');
            $("#fp-history-modal").modal("show");
        }).fail(function (xhr) { FP.alertFailure(xhr, loadPolicies); });
    }

    /* ---------------- delegations ---------------- */

    function initDelegationTable() {
        state.delegationTable = $("#fp-delegation-table").DataTable({
            data: [],
            order: [],
            pageLength: 25,
            autoWidth: false,
            responsive: true,
            language: { emptyTable: "No delegations recorded." },
            columns: [
                { data: "Delegator", render: function (d, type) { return type === "display" ? esc(d) : d; } },
                { data: "Delegate", render: function (d, type) { return type === "display" ? esc(d) : d; } },
                { data: "FromDate", render: function (d) { return esc(formatDate(d)); } },
                { data: "ToDate", render: function (d) { return esc(formatDate(d)); } },
                {
                    data: "Reason",
                    render: function (d, type, row) {
                        if (type !== "display") {
                            return d;
                        }
                        return esc(d) + '<div class="small text-muted">By ' + esc(row.CreatedBy || "System") + ", " + esc(row.CreatedAt) + "</div>" +
                            (row.Status === "cancelled" ? '<div class="small text-muted">Cancelled by ' + esc(row.CancelledBy) + ": " + esc(row.CancelReason) + "</div>" : "");
                    }
                },
                {
                    data: "Status",
                    render: function (d, type) {
                        return type === "display" ? '<span class="badge ' + (DELEGATION_BADGES[d] || "badge-light") + '">' + esc(d.charAt(0).toUpperCase() + d.slice(1)) + "</span>" : d;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    render: function (d, type, row) {
                        return row.CanCancel ? '<button type="button" class="btn btn-xs btn-outline-warning" data-act="cancel" data-id="' + row.ID + '" title="Cancel"><i class="fa-solid fa-ban"></i></button>' : "";
                    }
                }
            ]
        });
    }

    function loadDelegations() {
        FP.request("GET", "action/list_delegations.php").done(function (res) {
            state.delegations = res.data.delegations || [];
            state.delegationById = {};
            state.delegations.forEach(function (d) { state.delegationById[d.ID] = d; });
            state.delegationTable.clear().rows.add(state.delegations).draw(false);
        }).fail(function (xhr) { FP.alertFailure(xhr); });
    }

    function openDelegation() {
        var $form = $("#fp-delegation-form");
        $form[0].reset();
        FP.clearErrors($form);
        employeeSelect($("#fp-del-delegator"), $("#fp-delegation-modal"), 0, "");
        employeeSelect($("#fp-del-delegate"), $("#fp-delegation-modal"), 0, "");
        $("#fp-del-from").val(today());
        $("#fp-delegation-modal").modal("show");
    }

    function submitDelegation(e) {
        e.preventDefault();
        var $form = $("#fp-delegation-form");
        FP.clearErrors($form);
        var $button = $("#fp-delegation-save").prop("disabled", true);
        FP.request("POST", "action/save_delegation.php", $form.serialize()).done(function (res) {
            $("#fp-delegation-modal").modal("hide");
            alertify.success(esc(res.message));
            loadDelegations();
        }).fail(failForm($form, $("#fp-delegation-modal"), loadDelegations)).always(function () {
            $button.prop("disabled", false);
        });
    }

    function cancelDelegation(delegation) {
        FP.askReason({
            title: "Cancel delegation",
            text: delegation.Delegate + " will no longer approve on behalf of " + delegation.Delegator + ".",
            required: true,
            confirmLabel: "Cancel delegation",
            buttonClass: "btn-warning"
        }, function (reason) {
            FP.request("POST", "action/cancel_delegation.php", { ID: delegation.ID, Reason: reason }).done(function (res) {
                alertify.success(esc(res.message));
                loadDelegations();
            }).fail(function (xhr) { FP.alertFailure(xhr, loadDelegations); });
        });
    }

    /* ---------------- tester ---------------- */

    function runTest(e) {
        e.preventDefault();
        var $form = $("#fp-test-form");
        FP.clearErrors($form);
        var data = $form.serializeArray();
        data.push({ name: "CategoryIDs", value: ($("#fp-test-categories").val() || []).join(",") });
        FP.request("GET", "action/test_policy.php", $.param(data)).done(function (res) {
            var r = res.data;
            var html = "";
            if (r.policy) {
                html += '<p class="mb-2"><strong>Policy chosen:</strong> ' + esc(r.policy) + "</p>";
            }
            if (r.error) {
                html += '<div class="alert alert-danger py-2">' + esc(r.error) + "</div>";
            }
            if (r.stages.length) {
                html += '<table class="table table-sm table-bordered"><thead class="thead-light"><tr><th>Stage</th><th>Approver</th><th>Mandatory</th><th>Result</th></tr></thead><tbody>';
                r.stages.forEach(function (s) {
                    html += "<tr><td>" + esc(s.StageNo + ". " + s.StageName) + "</td><td>" + esc(s.Approver) + "</td><td>" + (s.IsMandatory ? "Yes" : "No") + "</td><td>" +
                        (s.Status === "pending" ? '<span class="badge badge-primary">Needs approval</span>' : '<span class="badge badge-secondary">Skipped</span>') +
                        (s.Note ? '<div class="small text-muted">' + esc(s.Note) + "</div>" : "") + "</td></tr>";
                });
                html += "</tbody></table>";
            }
            if (r.candidates.length > 1) {
                html += '<p class="small text-muted mb-0">All matching policies, best first: ' + esc(r.candidates.join(", ")) + "</p>";
            }
            $("#fp-test-result").html(html);
        }).fail(function (xhr) {
            var res = xhr.responseJSON;
            if (xhr.status === 422 && res && res.errors) {
                FP.showErrors($form, res.errors);
                return;
            }
            FP.alertFailure(xhr);
        });
    }

    /* ---------------- wiring ---------------- */

    $(function () {
        FP.activateNav("settings", "approval_policies");
        initPolicyTable();
        initDelegationTable();
        loadPolicies();
        loadDelegations();

        $("#fp-filter-status").on("change", drawPolicies);
        $("#fp-policy-add").on("click", function () { openPolicy(0); });
        $("#fp-policy-form").on("submit", submitPolicy);
        $("#fp-stage-add").on("click", function () {
            if ($("#fp-stage-list .fp-stage-row").length >= 10) {
                alertify.error("A policy can have at most 10 stages.");
                return;
            }
            addStageRow(null);
        });
        $("#fp-pol-company, #fp-pol-state").on("change", fillPolicyOptions);
        $("#fp-pol-code").on("input", function () { this.value = this.value.toUpperCase(); });

        $("#fp-stage-list").on("change input", '[data-field="ApproverType"], [data-field="EscalateAfterHours"], [data-field="EscalateToType"]', function () {
            syncStageRow($(this).closest(".fp-stage-row"));
        });
        $("#fp-stage-list").on("click", "button[data-stage-act]", function () {
            var $row = $(this).closest(".fp-stage-row");
            var act = $(this).data("stage-act");
            if (act === "remove") {
                $row.find(".fp-employee-select").select2("destroy");
                $row.remove();
                renumberStages();
            } else {
                moveStage($row, act);
            }
        });

        $("#fp-policy-table").on("click", "button[data-act]", function () {
            var policy = state.byId[$(this).data("id")];
            if (!policy) {
                return;
            }
            switch ($(this).data("act")) {
                case "edit":
                case "view": openPolicy(policy.ID); break;
                case "publish": publishPolicy(policy); break;
                case "delete": deleteDraft(policy); break;
                case "version": newVersion(policy); break;
                case "retire": retirePolicy(policy); break;
                case "history": showHistory(policy); break;
            }
        });

        $("#fp-delegation-add").on("click", openDelegation);
        $("#fp-delegation-form").on("submit", submitDelegation);
        $("#fp-delegation-table").on("click", "button[data-act='cancel']", function () {
            var delegation = state.delegationById[$(this).data("id")];
            if (delegation) {
                cancelDelegation(delegation);
            }
        });

        $("#fp-test-form").on("submit", runTest);
        $('a[data-toggle="tab"]').on("shown.bs.tab", function () {
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        });
    });
})(jQuery);
