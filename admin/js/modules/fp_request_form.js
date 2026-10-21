(function ($, FP) {
    "use strict";

    var esc = FP.esc;
    var TYPE_HELP = {
        advance: "Money paid to you before you spend it. Receipts are settled later.",
        reimbursement: "You already paid. Claim the amount back with receipts.",
        direct: "The company pays the vendor's invoice directly. Vendor and invoice details are required."
    };
    var ITEM_FIELDS = ["CategoryID", "Description", "ExpenseDate", "VendorName", "VendorGSTIN", "InvoiceNumber", "InvoiceDate", "Amount", "TaxAmount"];
    var state = { data: null, offices: {}, categories: {}, savedLabels: {}, request: null, attachments: [], dirty: false, busy: false, vendor: null };

    function requestIdFromUrl() {
        var match = /[?&]id=(\d+)/.exec(window.location.search);
        return match ? parseInt(match[1], 10) : 0;
    }

    function typeFromUrl() {
        var match = /[?&]type=(\w+)/.exec(window.location.search);
        return match ? match[1] : "";
    }

    function currentType() {
        return $("input[name=RequestType]:checked").val() || "";
    }

    function currentOffice() {
        return state.offices[$("#fp-req-office").val()] || null;
    }

    function categoryAllowed(category, type, office) {
        if (office && category.CompanyID !== 0 && category.CompanyID !== office.CompanyID) {
            return false;
        }
        if (type === "advance") {
            return category.AdvanceAllowed === 1;
        }
        if (type === "reimbursement") {
            return category.ReimbursementAllowed === 1;
        }
        return true;
    }

    /* ---------------- header ---------------- */

    function renderTypes() {
        var html = "";
        $.each(state.data.types, function (key, label) {
            html += '<div class="col-md-4 mb-2"><label class="border rounded p-2 d-block fp-type-option mb-0" data-type="' + esc(key) + '">' +
                '<input type="radio" name="RequestType" value="' + esc(key) + '" class="mr-1"> <strong>' + esc(label) + "</strong>" +
                '<div class="small text-muted">' + esc(TYPE_HELP[key] || "") + "</div></label></div>";
        });
        $("#fp-type-options").html(html);
    }

    function renderOffices(selectedId) {
        var list = state.data.offices;
        var html = list.length > 1 ? '<option value="">Choose your branch office...</option>' : "";
        list.forEach(function (o) {
            html += '<option value="' + o.ID + '">' + esc(o.OfficeName + " (" + o.OfficeCode + ")" + (o.CityName ? " - " + o.CityName : "")) + "</option>";
        });
        $("#fp-req-office").html(html).prop("disabled", list.length === 1);
        if (selectedId && state.offices[selectedId]) {
            $("#fp-req-office").val(String(selectedId));
        } else if (list.length === 1) {
            $("#fp-req-office").val(String(list[0].ID));
        }
        renderDerived();
    }

    function renderDerived() {
        var office = currentOffice();
        $("#fp-req-company").text(office ? office.CompanyName : "-");
        $("#fp-req-state").text(office ? office.StateName : "-");
    }

    function applyTypeUi() {
        var type = currentType();
        $(".fp-type-option").removeClass("active");
        $('.fp-type-option[data-type="' + type + '"]').addClass("active");
        $("#fp-date-head").text(type === "advance" ? "Expected date" : "Expense date *");
        $("#fp-type-hint").text(type ? TYPE_HELP[type] : "Choose a request type to see the categories you can use.");
        $("#fp-direct-box").toggle(type === "direct");
        $("#fp-items tbody tr").each(function () { refreshCategoryOptions($(this)); });
    }

    /* Drafts created from a recurring expense keep their type, office and period. */
    function applyRecurringLock(r) {
        var recurring = r && r.Recurring;
        $("#fp-recurring-note").toggle(!!recurring).html(recurring
            ? "<strong>Created from recurring expense " + esc(recurring.RuleNumber) + "</strong> (" + esc(recurring.Title) + ", " + esc(recurring.Period) + "). " +
              "Check the amount against the actual bill, attach the vendor invoice and submit it for approval. The type, office and period cannot be changed."
            : "");
        $("input[name=RequestType]").prop("disabled", !!recurring);
        $("#fp-req-office").prop("disabled", !!recurring || state.data.offices.length === 1);
        $("#fp-req-from, #fp-req-to").prop("readonly", !!recurring);
        $("#fp-req-recurring").prop("disabled", !!recurring);
    }

    /* ---------------- line items ---------------- */

    function categoryOptions(selectedId) {
        var type = currentType();
        var office = currentOffice();
        var html = '<option value="">Choose...</option>';
        var selectedShown = false;
        state.data.categories.forEach(function (c) {
            var allowed = categoryAllowed(c, type, office);
            if (!allowed && c.ID !== selectedId) {
                return;
            }
            selectedShown = selectedShown || c.ID === selectedId;
            html += '<option value="' + c.ID + '">' + esc(c.Label + (allowed ? "" : " (not allowed for this request)")) + "</option>";
        });
        if (selectedId && !selectedShown) {
            html += '<option value="' + selectedId + '">' + esc((state.savedLabels[selectedId] || "Category") + " (inactive)") + "</option>";
        }
        return html;
    }

    function refreshCategoryOptions($row) {
        var $select = $row.find('[data-field="CategoryID"]');
        var selected = parseInt($select.val(), 10) || 0;
        $select.html(categoryOptions(selected)).val(selected ? String(selected) : "");
        refreshLineHint($row);
    }

    function proofTypes() {
        return Object.keys(state.data.documentTypes).filter(function (key) { return state.data.documentTypes[key].proof; });
    }

    /* Mirrors fp_document_requirements(); the server re-checks on submit. */
    function lineRequirements($row) {
        var type = currentType();
        var category = state.categories[$row.find('[data-field="CategoryID"]').val()];
        var amount = parseFloat(String($row.find('[data-field="Amount"]').val()).replace(/,/g, ""));
        var rules = [];
        if (!category) {
            return rules;
        }
        if (type === "direct") {
            rules.push({ label: "Vendor tax invoice", types: ["invoice"] });
        } else if (type !== "advance" && category.ReceiptRequired === 1) {
            rules.push({ label: "Receipt, bill or invoice", types: proofTypes() });
        }
        if (category.QuotationRequiredAbove !== null && !isNaN(amount) && amount > parseFloat(category.QuotationRequiredAbove)) {
            rules.push({ label: "Quotation (line above " + FP.formatMoney(category.QuotationRequiredAbove) + ")", types: ["quotation"] });
        }
        return rules;
    }

    function attachedTypes(itemId) {
        return state.attachments.filter(function (a) { return a.ItemID === itemId; }).map(function (a) { return a.DocumentType; });
    }

    function refreshLineHint($row) {
        var itemId = parseInt($row.data("item-id"), 10) || 0;
        var attached = itemId ? attachedTypes(itemId) : [];
        var html = $.map(lineRequirements($row), function (rule) {
            if (!itemId) {
                return '<div class="text-muted">Needs: ' + esc(rule.label) + " (attach after saving)</div>";
            }
            var met = rule.types.some(function (t) { return attached.indexOf(t) !== -1; });
            return met
                ? '<div class="text-success"><i class="fa-solid fa-check"></i> ' + esc(rule.label) + "</div>"
                : '<div class="text-danger"><i class="fa-solid fa-xmark"></i> ' + esc(rule.label) + " missing</div>";
        }).join("");
        if (!html && attached.length) {
            html = '<div class="text-muted"><i class="fa-solid fa-paperclip"></i> ' + attached.length + " document(s)</div>";
        }
        $row.find(".fp-line-hint").html(html);
    }

    function suggestDocumentType() {
        var itemId = parseInt($("#fp-attach-line").val(), 10) || 0;
        var suggestion = currentType() === "direct" ? "invoice" : "receipt";
        if (itemId) {
            var $row = $('#fp-items tbody tr[data-item-id="' + itemId + '"]');
            var attached = attachedTypes(itemId);
            lineRequirements($row).some(function (rule) {
                if (!rule.types.some(function (t) { return attached.indexOf(t) !== -1; })) {
                    suggestion = rule.types[0];
                    return true;
                }
                return false;
            });
        }
        $("#fp-attach-type").val(suggestion);
    }

    function addRow(item) {
        item = item || {};
        var $row = $("<tr>").attr("data-item-id", item.ItemID || 0);
        $row.html(
            '<td class="fp-line-no align-middle text-center"></td>' +
            '<td class="fp-col-category"><select class="form-control" data-field="CategoryID"></select><div class="fp-line-hint"></div></td>' +
            '<td class="fp-col-desc"><input type="text" class="form-control" data-field="Description" maxlength="500"></td>' +
            '<td class="fp-col-date"><input type="date" class="form-control" data-field="ExpenseDate"></td>' +
            '<td class="fp-col-text"><input type="text" class="form-control" data-field="VendorName" maxlength="150"></td>' +
            '<td class="fp-col-text"><input type="text" class="form-control text-uppercase" data-field="VendorGSTIN" maxlength="15"></td>' +
            '<td class="fp-col-text"><input type="text" class="form-control" data-field="InvoiceNumber" maxlength="50"></td>' +
            '<td class="fp-col-date"><input type="date" class="form-control" data-field="InvoiceDate"></td>' +
            '<td class="fp-col-money"><input type="text" inputmode="decimal" class="form-control text-right" data-field="Amount"></td>' +
            '<td class="fp-col-money"><input type="text" inputmode="decimal" class="form-control text-right" data-field="TaxAmount"></td>' +
            '<td class="align-middle"><button type="button" class="btn btn-xs btn-outline-danger fp-remove-line" title="Remove line"><i class="fa-solid fa-xmark"></i></button></td>'
        );
        $("#fp-items tbody").append($row);
        $row.find('[data-field="CategoryID"]').html(categoryOptions(item.CategoryID || 0)).val(item.CategoryID ? String(item.CategoryID) : "");
        ITEM_FIELDS.forEach(function (field) {
            if (field !== "CategoryID" && item[field] !== undefined && item[field] !== null) {
                $row.find('[data-field="' + field + '"]').val(field === "TaxAmount" && parseFloat(item[field]) === 0 ? "" : item[field]);
            }
        });
        refreshLineHint($row);
        renumber();
    }

    function renumber() {
        $("#fp-items tbody tr").each(function (index) { $(this).find(".fp-line-no").text(index + 1); });
        var total = 0;
        $("#fp-items tbody [data-field=Amount]").each(function () {
            var value = parseFloat(String($(this).val()).replace(/,/g, ""));
            total += isNaN(value) ? 0 : value;
        });
        $("#fp-total").text(FP.formatMoney(total));
    }

    function collect() {
        var payload = {
            ID: $("#fp-req-id").val(),
            RowVersion: $("#fp-req-version").val(),
            RequestType: currentType(),
            OfficeID: $("#fp-req-office").val() || "",
            Purpose: $("#fp-req-purpose").val(),
            PeriodFrom: $("#fp-req-from").val(),
            PeriodTo: $("#fp-req-to").val(),
            IsRecurring: $("#fp-req-recurring").is(":checked") ? 1 : 0,
            Remarks: $("#fp-req-remarks").val(),
            items: []
        };
        if (payload.RequestType === "direct" && state.vendor) {
            $.extend(payload, state.vendor.get(), { DueDate: $("#fp-req-due").val() });
        }
        $("#fp-items tbody tr").each(function () {
            var $row = $(this);
            var item = { ItemID: $row.data("item-id") || 0 };
            ITEM_FIELDS.forEach(function (field) { item[field] = $row.find('[data-field="' + field + '"]').val() || ""; });
            payload.items.push(item);
        });
        return payload;
    }

    /* ---------------- errors ---------------- */

    function clearAllErrors() {
        FP.clearErrors($("#fp-request-form"));
        $("#fp-items .is-invalid").removeClass("is-invalid").removeAttr("title");
        $("#fp-error-summary").hide().empty();
    }

    function showAllErrors(errors, heading) {
        var messages = [];
        $.each(errors || {}, function (key, message) {
            var match = /^items\.(\d+)\.(\w+)$/.exec(key);
            if (match) {
                var $row = $("#fp-items tbody tr").eq(parseInt(match[1], 10));
                $row.find('[data-field="' + match[2] + '"]').addClass("is-invalid").attr("title", message);
                messages.push((message.indexOf("Line ") === 0 ? "" : "Line " + (parseInt(match[1], 10) + 1) + ": ") + message);
            } else {
                messages.push(message);
            }
        });
        FP.showErrors($("#fp-request-form"), errors);
        if (messages.length) {
            $("#fp-error-summary").html("<strong>" + esc(heading) + "</strong><ul class=\"mb-0\">" + messages.map(function (m) { return "<li>" + esc(m) + "</li>"; }).join("") + "</ul>").show();
            $("html, body").animate({ scrollTop: $("#fp-error-summary").offset().top - 80 }, 200);
        }
    }

    /* ---------------- attachments ---------------- */

    function renderAttachments() {
        var saved = !!(state.request && state.request.ID);
        $("#fp-attach-locked").toggle(!saved);
        $("#fp-attach-area").toggle(saved);
        if (!saved) {
            return;
        }
        var lineOptions = '<option value="0">Whole request</option>';
        $("#fp-items tbody tr").each(function (index) {
            var itemId = parseInt($(this).data("item-id"), 10) || 0;
            if (itemId) {
                var category = state.categories[$(this).find('[data-field="CategoryID"]').val()];
                lineOptions += '<option value="' + itemId + '">Line ' + (index + 1) + (category ? " - " + esc(category.Label) : "") + "</option>";
            }
        });
        var current = $("#fp-attach-line").val();
        $("#fp-attach-line").html(lineOptions);
        if (current && $("#fp-attach-line option[value='" + current + "']").length) {
            $("#fp-attach-line").val(current);
        } else if ($("#fp-attach-line option").length > 1) {
            $("#fp-attach-line").prop("selectedIndex", 1);
        }
        if (!$("#fp-attach-type option").length) {
            $("#fp-attach-type").html($.map(state.data.documentTypes, function (type, key) {
                return '<option value="' + esc(key) + '">' + esc(type.label) + "</option>";
            }).join(""));
        }
        var html = "";
        state.attachments.forEach(function (a) {
            var url = "action/download_attachment.php?ID=" + a.ID;
            html += "<tr><td>" + (a.LineNo ? "Line " + a.LineNo : "Whole request") + "</td>" +
                "<td>" + esc(a.DocumentLabel) + "</td>" +
                '<td><a href="' + url + '" target="_blank" rel="noopener">' + esc(a.OriginalName) + "</a>" +
                ' <a href="' + url + '&download=1" class="ml-1" title="Download"><i class="fa-solid fa-download"></i></a></td>' +
                "<td>" + esc(a.Notes) + "</td>" +
                '<td class="text-nowrap">' + (a.SizeBytes / 1024).toFixed(0) + " KB</td>" +
                '<td class="text-nowrap">' + esc(a.UploadedAt) + (a.UploadedByName ? '<div class="small text-muted">' + esc(a.UploadedByName) + "</div>" : "") + "</td>" +
                '<td><button type="button" class="btn btn-xs btn-outline-danger fp-remove-attachment" data-id="' + a.ID + '">Remove</button></td></tr>';
        });
        $("#fp-attach-list").html(html || '<tr><td colspan="7" class="text-muted text-center">No attachments yet.</td></tr>');
        $("#fp-items tbody tr").each(function () { refreshLineHint($(this)); });
        suggestDocumentType();
    }

    function upload() {
        var input = $("#fp-attach-file")[0];
        if (!input.files || !input.files.length) {
            alertify.error("Choose a file first.");
            return;
        }
        var file = input.files[0];
        if (file.size > state.data.maxUploadBytes) {
            alertify.alert("TechXpert", "The file is larger than " + (state.data.maxUploadBytes / 1048576).toFixed(1) + " MB.");
            return;
        }
        if (state.dirty) {
            alertify.alert("TechXpert", "Save the draft first so the lines match what is stored.");
            return;
        }
        var form = new FormData();
        form.append("RequestID", state.request.ID);
        form.append("ItemID", $("#fp-attach-line").val() || 0);
        form.append("DocumentType", $("#fp-attach-type").val() || "");
        form.append("Notes", $("#fp-attach-notes").val() || "");
        form.append("file", file);
        var $button = $("#fp-attach-upload").prop("disabled", true);
        FP.request("POST", "action/upload_attachment.php", form, { processData: false, contentType: false }).done(function (res) {
            alertify.success(esc(res.message));
            input.value = "";
            $("#fp-attach-notes").val("");
            reload(state.request.ID);
        }).fail(function (xhr) { FP.alertFailure(xhr); }).always(function () { $button.prop("disabled", false); });
    }

    /* ---------------- load / save ---------------- */

    function render(data) {
        state.data = data;
        state.offices = {};
        state.categories = {};
        data.offices.forEach(function (o) { state.offices[o.ID] = o; });
        data.categories.forEach(function (c) { state.categories[c.ID] = c; });
        state.savedLabels = {};
        data.items.forEach(function (i) { state.savedLabels[i.CategoryID] = i.CategoryLabel; });
        state.request = data.request;
        state.attachments = data.attachments || [];

        $("#fp-form-loading").hide();
        if (!data.offices.length && !data.request) {
            $("#fp-no-office").show();
            return;
        }
        $("#fp-request-form").show();
        $("#fp-attach-max").text((data.maxUploadBytes / 1048576).toFixed(1) + " MB");

        var r = data.request;
        renderTypes();
        renderOffices(r ? r.OfficeID : 0);
        if (!state.vendor) {
            state.vendor = FP.vendorPicker($("#fp-req-vendor"), { mode: data.vendorMode, onChange: function () { state.dirty = true; } });
        }
        state.vendor.set(r && r.RequestType === "direct" ? r : {});
        $("#fp-req-due").val(r && r.RequestType === "direct" ? r.DueDate : "");
        if (!r && data.types[typeFromUrl()]) {
            $("input[name=RequestType][value='" + typeFromUrl() + "']").prop("checked", true);
        }
        if (r) {
            $("input[name=RequestType][value='" + r.RequestType + "']").prop("checked", true);
            $("#fp-req-id").val(r.ID);
            $("#fp-req-version").val(r.RowVersion);
            $("#fp-req-purpose").val(r.Purpose);
            $("#fp-req-from").val(r.PeriodFrom);
            $("#fp-req-to").val(r.PeriodTo);
            $("#fp-req-recurring").prop("checked", r.IsRecurring === 1);
            $("#fp-req-remarks").val(r.Remarks);
            $("#fp-form-title").text("Expense request " + r.ExpenseNumber);
            $("#fp-breadcrumb-title").text("Edit " + r.ExpenseNumber);
            $("#fp-req-status").text(r.Status === "returned" ? "Returned for changes" : "Draft").show();
            $("#fp-cancel-request").show();
        }
        $("#fp-items tbody").empty();
        (data.items.length ? data.items : [{}]).forEach(addRow);
        applyTypeUi();
        applyRecurringLock(r);
        renderAttachments();
        state.dirty = false;
    }

    function reload(id) {
        return FP.request("GET", "action/load_request_form.php", id ? { ID: id } : {}).done(function (res) {
            render(res.data);
        }).fail(function (xhr) {
            $("#fp-form-loading").html(esc(FP.failMessage(xhr)) + (id ? ' <a href="view-expense-request.php?id=' + id + '">View the request</a>' : ""));
        });
    }

    function save() {
        clearAllErrors();
        var deferred = $.Deferred();
        FP.request("POST", "action/save_expense_request.php", $.param(collect())).done(function (res) {
            state.dirty = false;
            if (!state.request || state.request.ID !== res.data.ID) {
                window.history.replaceState(null, "", "view-expense-request-form.php?id=" + res.data.ID);
            }
            reload(res.data.ID).done(function () { deferred.resolve(res); }).fail(function () { deferred.reject(); });
        }).fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                showAllErrors(xhr.responseJSON.errors, "Please correct the following before saving:");
            } else {
                FP.alertFailure(xhr);
            }
            deferred.reject();
        });
        return deferred.promise();
    }

    function setBusy(busy) {
        state.busy = busy;
        $("#fp-save-draft, #fp-submit, #fp-cancel-request").prop("disabled", busy);
    }

    /* ---------------- wiring ---------------- */

    $(function () {
        FP.activateNav("booking", requestIdFromUrl() ? "my_requests" : "new_request");
        reload(requestIdFromUrl());

        $("#fp-request-form")
            .on("change", "input[name=RequestType], #fp-req-office", function () { renderDerived(); applyTypeUi(); })
            .on("input change", "input, select, textarea", function () {
                if (!$(this).closest("#fp-attach-area").length) {
                    state.dirty = true;
                }
            })
            .on("input", "[data-field=Amount]", function () { renumber(); refreshLineHint($(this).closest("tr")); })
            .on("input", "[data-field=VendorGSTIN]", function () { this.value = this.value.toUpperCase(); })
            .on("change", "[data-field=CategoryID]", function () { refreshLineHint($(this).closest("tr")); })
            .on("click", ".fp-remove-line", function () {
                if ($("#fp-items tbody tr").length === 1) {
                    alertify.error("A request needs at least one line.");
                    return;
                }
                $(this).closest("tr").remove();
                renumber();
                state.dirty = true;
            })
            .on("click", ".fp-remove-attachment", function () {
                var id = $(this).data("id");
                alertify.confirm("TechXpert", "Remove this attachment?", function () {
                    FP.request("POST", "action/delete_attachment.php", { ID: id }).done(function (res) {
                        alertify.success(esc(res.message));
                        reload(state.request.ID);
                    }).fail(function (xhr) { FP.alertFailure(xhr); });
                }, function () {});
            });

        $("#fp-add-line").on("click", function () {
            if ($("#fp-items tbody tr").length >= 50) {
                alertify.error("A request can have at most 50 lines.");
                return;
            }
            addRow({});
            state.dirty = true;
        });
        $("#fp-attach-upload").on("click", upload);
        $("#fp-attach-line").on("change", suggestDocumentType);

        $("#fp-save-draft").on("click", function () {
            if (state.busy) {
                return;
            }
            setBusy(true);
            save().done(function (res) { alertify.success(esc(res.message)); }).always(function () { setBusy(false); });
        });

        $("#fp-submit").on("click", function () {
            if (state.busy) {
                return;
            }
            alertify.confirm("TechXpert", "Submit this request for approval? You will not be able to edit it afterwards unless it is returned to you.", function () {
                setBusy(true);
                save().done(function () {
                    FP.request("POST", "action/submit_expense_request.php", { ID: state.request.ID, RowVersion: state.request.RowVersion }).done(function (res) {
                        state.dirty = false;
                        alertify.alert("TechXpert", esc(res.message), function () { window.location.href = "view-expense-request.php?id=" + res.data.ID; });
                    }).fail(function (xhr) {
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            showAllErrors(xhr.responseJSON.errors, "The request was saved as a draft but is not ready to submit:");
                        } else {
                            FP.alertFailure(xhr, function () { reload(state.request.ID); });
                        }
                    }).always(function () { setBusy(false); });
                }).fail(function () { setBusy(false); });
            }, function () {});
        });

        $("#fp-cancel-request").on("click", function () {
            FP.askReason({
                title: "Cancel " + state.request.ExpenseNumber,
                text: "The request will be marked as cancelled. It stays in your list for reference and cannot be reopened.",
                required: true,
                confirmLabel: "Cancel request",
                buttonClass: "btn-danger"
            }, function (reason) {
                FP.request("POST", "action/cancel_request.php", { ID: state.request.ID, RowVersion: state.request.RowVersion, Reason: reason }).done(function (res) {
                    state.dirty = false;
                    alertify.alert("TechXpert", esc(res.message), function () { window.location.href = "view-expense-requests.php"; });
                }).fail(function (xhr) { FP.alertFailure(xhr, function () { reload(state.request.ID); }); });
            });
        });

        $(window).on("beforeunload", function () {
            if (state.dirty) {
                return "You have unsaved changes.";
            }
        });
    });
})(jQuery, window.FP);
