(function ($, FP) {
    "use strict";

    var state = { offices: [], byId: {}, companies: [], states: [], cities: [], table: null, currentOffice: null, assignments: [] };
    var esc = FP.esc;

    /* ---------------- offices ---------------- */

    function actionButtons(row) {
        var html = '<button type="button" class="btn btn-xs btn-outline-primary mr-1" data-act="edit" data-id="' + row.ID + '" title="Edit"><i class="fa-solid fa-pen"></i></button>';
        html += '<button type="button" class="btn btn-xs btn-outline-info mr-1" data-act="employees" data-id="' + row.ID + '" title="Employees"><i class="fa-solid fa-users"></i></button>';
        if (row.IsActive) {
            html += '<button type="button" class="btn btn-xs btn-outline-warning" data-act="deactivate" data-id="' + row.ID + '" title="Deactivate"><i class="fa-solid fa-ban"></i></button>';
        } else {
            html += '<button type="button" class="btn btn-xs btn-outline-success" data-act="activate" data-id="' + row.ID + '" title="Activate"><i class="fa-solid fa-check"></i></button>';
        }
        return html;
    }

    function initTable() {
        state.table = $("#fp-office-table").DataTable({
            data: [],
            order: [],
            pageLength: 25,
            autoWidth: false,
            responsive: true,
            language: { emptyTable: "No branch offices yet. Use Add office to create the first one." },
            columns: [
                { data: "OfficeCode", render: function (d, t) { return t === "display" ? "<code>" + esc(d) + "</code>" : d; } },
                {
                    data: "OfficeName",
                    render: function (d, t, row) {
                        if (t !== "display") {
                            return d;
                        }
                        return '<div class="font-weight-bold">' + esc(d) + "</div>" + (row.Address ? '<div class="small text-muted">' + esc(row.Address) + "</div>" : "");
                    }
                },
                { data: "CompanyName", render: function (d, t) { return t === "display" ? esc(d) : d; } },
                { data: "StateName", render: function (d, t, row) { return t === "display" ? esc(d) + (row.CityName ? '<div class="small text-muted">' + esc(row.CityName) + "</div>" : "") : d; } },
                { data: "RequesterCount" },
                { data: "ManagerCount" },
                { data: "RequestCount" },
                { data: "IsActive", render: function (d, t) { return t !== "display" ? d : (d ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-secondary">Inactive</span>'); } },
                { data: null, orderable: false, className: "text-nowrap", render: function (d, t, row) { return actionButtons(row); } }
            ]
        });
    }

    function load() {
        FP.request("GET", "action/list_branch_offices.php").done(function (res) {
            state.offices = res.data.offices || [];
            state.companies = res.data.companies || [];
            state.states = res.data.states || [];
            state.cities = res.data.cities || [];
            state.byId = {};
            state.offices.forEach(function (o) { state.byId[o.ID] = o; });
            state.table.clear().rows.add(state.offices).draw(false);
        }).fail(function (xhr) { FP.alertFailure(xhr); });
    }

    function options(list, valueKey, labelKey, placeholder, current, currentLabel) {
        var html = placeholder !== null ? '<option value="0">' + esc(placeholder) + "</option>" : "";
        var found = false;
        list.forEach(function (item) {
            found = found || String(item[valueKey]) === String(current);
            html += '<option value="' + esc(item[valueKey]) + '">' + esc(item[labelKey]) + "</option>";
        });
        if (current && !found && currentLabel) {
            html += '<option value="' + esc(current) + '">' + esc(currentLabel + " - inactive") + "</option>";
        }
        return html;
    }

    function fillCities(stateId, current) {
        var cities = state.cities.filter(function (c) { return String(c.StateID) === String(stateId); });
        $("#fp-office-city").html(options(cities, "ID", "CityName", "Not specified", current, null)).val(String(current || 0));
    }

    function openForm(office) {
        var $form = $("#fp-office-form");
        $form[0].reset();
        FP.clearErrors($form);
        var isEdit = !!office;
        $("#fp-office-modal-title").text(isEdit ? "Edit office" : "Add office");
        $("#fp-office-id").val(isEdit ? office.ID : 0);
        $("#fp-office-version").val(isEdit ? office.RowVersion : 0);
        $("#fp-office-code").val(isEdit ? office.OfficeCode : "");
        $("#fp-office-name").val(isEdit ? office.OfficeName : "");
        $("#fp-office-address").val(isEdit ? office.Address : "");
        $("#fp-office-company").html(options(state.companies, "ID", "CompanyName", "Choose...", isEdit ? office.CompanyID : 0, isEdit ? office.CompanyName : null)).val(String(isEdit ? office.CompanyID : 0));
        $("#fp-office-state").html(options(state.states, "ID", "StateName", "Choose...", isEdit ? office.StateID : 0, isEdit ? office.StateName : null)).val(String(isEdit ? office.StateID : 0));
        fillCities(isEdit ? office.StateID : 0, isEdit ? office.CityID : 0);
        var locked = isEdit && office.ScopeLocked;
        $("#fp-office-company, #fp-office-state").prop("disabled", locked);
        $("#fp-office-lock-note").toggle(locked);
        $("#fp-office-modal").modal("show");
    }

    function submitForm(e) {
        e.preventDefault();
        var $form = $("#fp-office-form");
        FP.clearErrors($form);
        var $locked = $("#fp-office-company:disabled, #fp-office-state:disabled").prop("disabled", false);
        var data = $form.serialize();
        $locked.prop("disabled", true);
        var $button = $("#fp-office-save").prop("disabled", true);
        FP.request("POST", "action/save_office.php", data).done(function (res) {
            $("#fp-office-modal").modal("hide");
            alertify.success(esc(res.message));
            load();
        }).fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                FP.showErrors($form, xhr.responseJSON.errors);
                return;
            }
            FP.alertFailure(xhr, load);
        }).always(function () { $button.prop("disabled", false); });
    }

    function changeStatus(office, activate) {
        FP.askReason({
            title: (activate ? "Activate " : "Deactivate ") + office.OfficeName,
            text: activate ? "Assigned employees can raise requests against this office again." : "Employees will no longer be able to raise or submit requests against this office. Existing requests are kept.",
            required: !activate,
            confirmLabel: activate ? "Activate" : "Deactivate",
            buttonClass: activate ? "btn-success" : "btn-warning"
        }, function (reason) {
            FP.request("POST", "action/set_office_status.php", { ID: office.ID, RowVersion: office.RowVersion, IsActive: activate ? 1 : 0, Reason: reason })
                .done(function (res) { alertify.success(esc(res.message)); load(); })
                .fail(function (xhr) { FP.alertFailure(xhr, load); });
        });
    }

    /* ---------------- assignments ---------------- */

    function renderAssignments() {
        var html = "";
        state.assignments.forEach(function (a) {
            var name = esc(a.EmployeeName || "Employee #" + a.EmployeeID) + (a.EmployeeNumber ? ' <span class="text-muted">(' + esc(a.EmployeeNumber) + ")</span>" : "");
            if (!a.EmployeeIsActive) {
                name += ' <span class="badge badge-danger">Employee inactive</span>';
            }
            if (a.IsActive && a.CanRequest && a.RequestOfficeCount > 1) {
                name += ' <span class="badge badge-info" title="Requester in more than one office">Multi-office</span>';
            }
            html += '<tr data-employee="' + a.EmployeeID + '">' +
                "<td>" + name + "</td>" +
                "<td>" + esc(a.Designation) + "</td>" +
                '<td class="text-center"><input type="checkbox" class="fp-asg-request"' + (a.CanRequest ? " checked" : "") + (a.IsActive ? "" : " disabled") + "></td>" +
                '<td class="text-center"><input type="checkbox" class="fp-asg-manager"' + (a.IsManager ? " checked" : "") + (a.IsActive ? "" : " disabled") + "></td>" +
                "<td>" + (a.IsActive ? '<span class="badge badge-success">Assigned</span>' : '<span class="badge badge-secondary">Removed</span>') + "</td>" +
                '<td class="text-nowrap">' + (a.IsActive
                    ? '<button type="button" class="btn btn-xs btn-outline-primary mr-1 fp-asg-save">Save</button><button type="button" class="btn btn-xs btn-outline-danger fp-asg-remove">Remove</button>'
                    : '<button type="button" class="btn btn-xs btn-outline-success fp-asg-restore">Restore</button>') +
                "</td></tr>";
        });
        $("#fp-assignment-table tbody").html(html || '<tr><td colspan="6" class="text-center text-muted">No employees assigned yet.</td></tr>');
    }

    function loadAssignments() {
        FP.request("GET", "action/list_office_assignments.php", { OfficeID: state.currentOffice.ID }).done(function (res) {
            state.assignments = res.data.assignments || [];
            renderAssignments();
        }).fail(function (xhr) { FP.alertFailure(xhr); });
    }

    function findAssignment(employeeId) {
        return state.assignments.filter(function (a) { return String(a.EmployeeID) === String(employeeId); })[0] || null;
    }

    function saveAssignment(data, done) {
        data.OfficeID = state.currentOffice.ID;
        FP.request("POST", "action/save_office_assignment.php", data).done(function (res) {
            alertify.success(esc(res.message));
            loadAssignments();
            load();
            if (done) {
                done();
            }
        }).fail(function (xhr) { FP.alertFailure(xhr, loadAssignments); });
    }

    function openAssignments(office) {
        state.currentOffice = office;
        state.assignments = [];
        $("#fp-assignment-title").text("Employees: " + office.OfficeName + " (" + office.OfficeCode + ")");
        $("#fp-assign-employee").val(null).trigger("change");
        $("#fp-assign-request").prop("checked", true);
        $("#fp-assign-manager").prop("checked", false);
        renderAssignments();
        loadAssignments();
        $("#fp-assignment-modal").modal("show");
    }

    function initEmployeeSearch() {
        $("#fp-assign-employee").select2({
            dropdownParent: $("#fp-assignment-modal"),
            placeholder: "Search by name or employee number",
            minimumInputLength: 2,
            allowClear: true,
            ajax: {
                url: "action/find_employees.php",
                dataType: "json",
                delay: 300,
                data: function (params) { return { q: params.term }; },
                processResults: function (res) { return { results: (res.data && res.data.results) || [] }; }
            }
        });
    }

    /* ---------------- wiring ---------------- */

    $(function () {
        FP.activateNav("settings", "offices");
        initTable();
        initEmployeeSearch();
        load();

        $("#fp-office-add").on("click", function () { openForm(null); });
        $("#fp-office-form").on("submit", submitForm);
        $("#fp-office-state").on("change", function () { fillCities($(this).val(), 0); });
        $("#fp-office-code").on("input", function () { this.value = this.value.toUpperCase(); });

        $("#fp-office-table").on("click", "button[data-act]", function () {
            var office = state.byId[$(this).data("id")];
            if (!office) {
                return;
            }
            switch ($(this).data("act")) {
                case "edit": openForm(office); break;
                case "employees": openAssignments(office); break;
                case "activate": changeStatus(office, true); break;
                case "deactivate": changeStatus(office, false); break;
            }
        });

        $("#fp-assign-add").on("click", function () {
            var employeeId = $("#fp-assign-employee").val();
            if (!employeeId) {
                alertify.error("Choose an employee first.");
                return;
            }
            var existing = findAssignment(employeeId);
            saveAssignment({
                EmployeeID: employeeId,
                CanRequest: $("#fp-assign-request").is(":checked") ? 1 : 0,
                IsManager: $("#fp-assign-manager").is(":checked") ? 1 : 0,
                IsActive: 1,
                RowVersion: existing ? existing.RowVersion : 0
            }, function () { $("#fp-assign-employee").val(null).trigger("change"); });
        });

        $("#fp-assignment-table").on("click", ".fp-asg-save, .fp-asg-restore", function () {
            var $row = $(this).closest("tr");
            var a = findAssignment($row.data("employee"));
            var restoring = $(this).hasClass("fp-asg-restore");
            saveAssignment({
                EmployeeID: a.EmployeeID,
                CanRequest: restoring ? a.CanRequest : ($row.find(".fp-asg-request").is(":checked") ? 1 : 0),
                IsManager: restoring ? a.IsManager : ($row.find(".fp-asg-manager").is(":checked") ? 1 : 0),
                IsActive: 1,
                RowVersion: a.RowVersion
            });
        });

        $("#fp-assignment-table").on("click", ".fp-asg-remove", function () {
            var a = findAssignment($(this).closest("tr").data("employee"));
            FP.askReason({
                title: "Remove " + (a.EmployeeName || "employee"),
                text: "The employee will no longer be able to raise requests for, or manage, this office. Their existing requests are kept.",
                required: true,
                confirmLabel: "Remove",
                buttonClass: "btn-danger"
            }, function (reason) {
                saveAssignment({ EmployeeID: a.EmployeeID, CanRequest: a.CanRequest, IsManager: a.IsManager, IsActive: 0, RowVersion: a.RowVersion, Reason: reason });
            });
        });
    });
})(jQuery, window.FP);
