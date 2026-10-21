/* Finance & Performance: advance settlement claim editor (view-settlement-form.php). */
(function ($, FP) {
    "use strict";

    var esc = FP.esc;
    var LINE_FIELDS = ["CategoryID", "Description", "ExpenseDate", "VendorName", "VendorGSTIN", "InvoiceNumber", "InvoiceDate", "Amount", "TaxAmount"];
    var MAX_LINES = 50;
    var state = { data: null, categories: {}, savedLabels: {}, claim: null, documents: [], dirty: false, busy: false };

    function urlParam(name) {
        var match = new RegExp("[?&]" + name + "=(\\d+)").exec(window.location.search);
        return match ? parseInt(match[1], 10) : 0;
    }

    /* ---------------- lines ---------------- */

    function categoryOptions(selectedId) {
        var html = '<option value="">Choose...</option>';
        var shown = false;
        state.data.categories.forEach(function (c) {
            shown = shown || c.ID === selectedId;
            html += '<option value="' + c.ID + '">' + esc(c.Label) + "</option>";
        });
        if (selectedId && !shown) {
            html += '<option value="' + selectedId + '">' + esc((state.savedLabels[selectedId] || "Category") + " (inactive)") + "</option>";
        }
        return html;
    }

    function proofTypes() {
        return Object.keys(state.data.documentTypes).filter(function (key) { return state.data.documentTypes[key].proof; });
    }

    /* Mirrors fp_document_requirements('reimbursement', ...); the server re-checks on submit. */
    function lineRequirements($row) {
        var category = state.categories[$row.find('[data-field="CategoryID"]').val()];
        var amount = parseFloat(String($row.find('[data-field="Amount"]').val()).replace(/,/g, ""));
        var rules = [];
        if (!category) {
            return rules;
        }
        if (category.ReceiptRequired === 1) {
            rules.push({ label: "Receipt, bill or invoice", types: proofTypes() });
        }
        if (category.QuotationRequiredAbove !== null && !isNaN(amount) && amount > parseFloat(category.QuotationRequiredAbove)) {
            rules.push({ label: "Quotation (line above " + FP.formatMoney(category.QuotationRequiredAbove) + ")", types: ["quotation"] });
        }
        return rules;
    }

    function attachedTypes(lineId) {
        return state.documents.filter(function (d) { return d.LineID === lineId; }).map(function (d) { return d.DocumentType; });
    }

    function refreshLineHint($row) {
        var lineId = parseInt($row.data("line-id"), 10) || 0;
        var attached = lineId ? attachedTypes(lineId) : [];
        var html = $.map(lineRequirements($row), function (rule) {
            if (!lineId) {
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

    function addRow(line) {
        line = line || {};
        var $row = $("<tr>").attr("data-line-id", line.LineID || 0);
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
        $("#fp-lines tbody").append($row);
        $row.find('[data-field="CategoryID"]').html(categoryOptions(line.CategoryID || 0)).val(line.CategoryID ? String(line.CategoryID) : "");
        $row.find("input[type=date]").attr("max", state.data.today);
        LINE_FIELDS.forEach(function (field) {
            if (field !== "CategoryID" && line[field] !== undefined && line[field] !== null) {
                $row.find('[data-field="' + field + '"]').val(field === "TaxAmount" && parseFloat(line[field]) === 0 ? "" : line[field]);
            }
        });
        refreshLineHint($row);
        renumber();
    }

    function renumber() {
        $("#fp-lines tbody tr").each(function (index) { $(this).find(".fp-line-no").text(index + 1); });
        var total = 0;
        $("#fp-lines tbody [data-field=Amount]").each(function () {
            var value = parseFloat(String($(this).val()).replace(/,/g, ""));
            total += isNaN(value) ? 0 : value;
        });
        $("#fp-total").text(FP.formatMoney(total));
    }

    function collect() {
        var payload = {
            ID: state.claim ? state.claim.ID : 0,
            RowVersion: state.claim ? state.claim.RowVersion : 0,
            RequestID: state.data.advance.ID,
            Notes: $("#fp-stl-notes").val(),
            lines: []
        };
        $("#fp-lines tbody tr").each(function () {
            var $row = $(this);
            var line = { LineID: $row.data("line-id") || 0 };
            LINE_FIELDS.forEach(function (field) { line[field] = $row.find('[data-field="' + field + '"]').val() || ""; });
            payload.lines.push(line);
        });
        return payload;
    }

    /* ---------------- errors ---------------- */

    function clearAllErrors() {
        FP.clearErrors($("#fp-settlement-form"));
        $("#fp-lines .is-invalid").removeClass("is-invalid").removeAttr("title");
        $("#fp-error-summary").hide().empty();
    }

    function showAllErrors(errors, heading) {
        var messages = [];
        $.each(errors || {}, function (key, message) {
            var match = /^lines\.(\d+)\.(\w+)$/.exec(key);
            if (match) {
                var $row = $("#fp-lines tbody tr").eq(parseInt(match[1], 10));
                $row.find('[data-field="' + match[2] + '"]').addClass("is-invalid").attr("title", message);
                messages.push((message.indexOf("Line ") === 0 ? "" : "Line " + (parseInt(match[1], 10) + 1) + ": ") + message);
            } else {
                messages.push(message);
            }
        });
        FP.showErrors($("#fp-settlement-form"), errors);
        if (messages.length) {
            $("#fp-error-summary").html("<strong>" + esc(heading) + '</strong><ul class="mb-0">' + messages.map(function (m) { return "<li>" + esc(m) + "</li>"; }).join("") + "</ul>").show();
            $("html, body").animate({ scrollTop: $("#fp-error-summary").offset().top - 80 }, 200);
        }
    }

    /* ---------------- documents ---------------- */

    function suggestDocumentType() {
        var lineId = parseInt($("#fp-attach-line").val(), 10) || 0;
        var suggestion = "receipt";
        if (lineId) {
            var $row = $('#fp-lines tbody tr[data-line-id="' + lineId + '"]');
            var attached = attachedTypes(lineId);
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

    function renderDocuments() {
        var saved = !!state.claim;
        $("#fp-attach-locked").toggle(!saved);
        $("#fp-attach-area").toggle(saved);
        if (!saved) {
            return;
        }
        var options = '<option value="0">Whole settlement</option>';
        $("#fp-lines tbody tr").each(function (index) {
            var lineId = parseInt($(this).data("line-id"), 10) || 0;
            if (lineId) {
                var category = state.categories[$(this).find('[data-field="CategoryID"]').val()];
                options += '<option value="' + lineId + '">Line ' + (index + 1) + (category ? " - " + esc(category.Label) : "") + "</option>";
            }
        });
        var current = $("#fp-attach-line").val();
        $("#fp-attach-line").html(options);
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
        state.documents.forEach(function (d) {
            var url = "action/download_settlement_document.php?ID=" + d.ID;
            html += "<tr><td>" + (d.LineNo ? "Line " + d.LineNo : "Whole settlement") + "</td>" +
                "<td>" + esc(d.DocumentLabel) + "</td>" +
                '<td><a href="' + url + '" target="_blank" rel="noopener">' + esc(d.OriginalName) + "</a>" +
                ' <a href="' + url + '&download=1" class="ml-1" title="Download"><i class="fa-solid fa-download"></i></a></td>' +
                "<td>" + esc(d.Notes) + "</td>" +
                '<td class="text-nowrap">' + (d.SizeBytes / 1024).toFixed(0) + " KB</td>" +
                '<td class="text-nowrap">' + esc(d.UploadedAt) + "</td>" +
                '<td><button type="button" class="btn btn-xs btn-outline-danger fp-remove-document" data-id="' + d.ID + '">Remove</button></td></tr>';
        });
        $("#fp-attach-list").html(html || '<tr><td colspan="7" class="text-muted text-center">No documents yet.</td></tr>');
        $("#fp-lines tbody tr").each(function () { refreshLineHint($(this)); });
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
        form.append("SettlementID", state.claim.ID);
        form.append("LineID", $("#fp-attach-line").val() || 0);
        form.append("DocumentType", $("#fp-attach-type").val() || "");
        form.append("Notes", $("#fp-attach-notes").val() || "");
        form.append("file", file);
        var $button = $("#fp-attach-upload").prop("disabled", true);
        FP.request("POST", "action/upload_settlement_document.php", form, { processData: false, contentType: false }).done(function (res) {
            alertify.success(esc(res.message));
            input.value = "";
            $("#fp-attach-notes").val("");
            load(0, state.claim.ID);
        }).fail(function (xhr) { FP.alertFailure(xhr); }).always(function () { $button.prop("disabled", false); });
    }

    /* ---------------- load / save ---------------- */

    function render(data) {
        state.data = data;
        state.categories = {};
        data.categories.forEach(function (c) { state.categories[c.ID] = c; });
        state.savedLabels = {};
        data.lines.forEach(function (l) { state.savedLabels[l.CategoryID] = l.CategoryLabel; });
        state.claim = data.claim;
        state.documents = data.documents || [];

        var a = data.advance;
        $("#fp-form-loading").hide();
        $("#fp-settlement-form").show();
        $("#fp-back").attr("href", "view-advance-settlement.php?id=" + a.ID);
        $("#fp-attach-max").text((data.maxUploadBytes / 1048576).toFixed(1) + " MB");
        $("#fp-advance-info").html(
            "Advance <strong>" + esc(a.ExpenseNumber) + "</strong> - " + esc(a.Office) + " - approved " + esc(FP.formatMoney(a.TotalAmount)) +
            (a.PeriodFrom ? '<span class="text-muted"> - period ' + esc(a.PeriodFrom) + " to " + esc(a.PeriodTo) + "</span>" : "") +
            '<div class="small text-muted">' + esc(a.Purpose) + "</div>"
        );
        $("#fp-fig-paid").text(FP.formatMoney(a.Paid));
        $("#fp-fig-accepted").text(FP.formatMoney(a.Accepted));
        $("#fp-fig-refund").text(FP.formatMoney(a.Refund));
        $("#fp-fig-additional").text(FP.formatMoney(a.Additional));
        $("#fp-fig-balance").text(a.BalanceText);

        var c = data.claim;
        if (c) {
            $("#fp-form-title").text("Settlement " + c.SettlementNumber);
            $("#fp-breadcrumb-title").text("Edit " + c.SettlementNumber);
            $("#fp-claim-status").text(c.StatusLabel).show();
            $("#fp-stl-notes").val(c.Notes);
            $("#fp-cancel-settlement").show();
            if (c.ReturnReason) {
                $("#fp-return-reason").html("<strong>Returned for changes:</strong> " + esc(c.ReturnReason)).show();
            }
        } else {
            $("#fp-form-title").text("Settle advance " + a.ExpenseNumber);
        }
        $("#fp-lines tbody").empty();
        (data.lines.length ? data.lines : [{}]).forEach(addRow);
        renderDocuments();
        state.dirty = false;
    }

    function load(requestId, settlementId) {
        var params = settlementId ? { id: settlementId } : { request: requestId };
        return FP.request("GET", "action/load_settlement_form.php", params).done(function (res) {
            render(res.data);
        }).fail(function (xhr) {
            var back = requestId ? ' <a href="view-advance-settlement.php?id=' + requestId + '">Back to the advance</a>' : ' <a href="view-settlements.php">Back to Settlements</a>';
            $("#fp-form-loading").html(esc(FP.failMessage(xhr)) + back);
        });
    }

    function save() {
        clearAllErrors();
        var deferred = $.Deferred();
        FP.request("POST", "action/save_settlement.php", $.param(collect())).done(function (res) {
            state.dirty = false;
            if (!state.claim) {
                window.history.replaceState(null, "", "view-settlement-form.php?id=" + res.data.ID);
            }
            load(0, res.data.ID).done(function () { deferred.resolve(res); }).fail(function () { deferred.reject(); });
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
        $("#fp-save-draft, #fp-submit, #fp-cancel-settlement").prop("disabled", busy);
    }

    /* ---------------- wiring ---------------- */

    $(function () {
        FP.activateNav("", "settlements");
        load(urlParam("request"), urlParam("id"));

        $("#fp-settlement-form")
            .on("input change", "input, select, textarea", function () {
                if (!$(this).closest("#fp-attach-area").length) {
                    state.dirty = true;
                }
            })
            .on("input", "[data-field=Amount]", function () { renumber(); refreshLineHint($(this).closest("tr")); })
            .on("input", "[data-field=VendorGSTIN]", function () { this.value = this.value.toUpperCase(); })
            .on("change", "[data-field=CategoryID]", function () { refreshLineHint($(this).closest("tr")); })
            .on("click", ".fp-remove-line", function () {
                if ($("#fp-lines tbody tr").length === 1) {
                    alertify.error("A settlement needs at least one line.");
                    return;
                }
                $(this).closest("tr").remove();
                renumber();
                state.dirty = true;
            })
            .on("click", ".fp-remove-document", function () {
                var id = $(this).data("id");
                alertify.confirm("TechXpert", "Remove this document?", function () {
                    FP.request("POST", "action/delete_settlement_document.php", { ID: id }).done(function (res) {
                        alertify.success(esc(res.message));
                        load(0, state.claim.ID);
                    }).fail(function (xhr) { FP.alertFailure(xhr); });
                }, function () {});
            });

        $("#fp-add-line").on("click", function () {
            if ($("#fp-lines tbody tr").length >= MAX_LINES) {
                alertify.error("A settlement can have at most " + MAX_LINES + " lines.");
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
            alertify.confirm("TechXpert", "Submit this settlement for branch review? You cannot change it afterwards unless it is returned to you.", function () {
                setBusy(true);
                save().done(function () {
                    FP.request("POST", "action/submit_settlement.php", { ID: state.claim.ID, RowVersion: state.claim.RowVersion }).done(function (res) {
                        state.dirty = false;
                        alertify.alert("TechXpert", esc(res.message), function () { window.location.href = "view-advance-settlement.php?id=" + res.data.RequestID; });
                    }).fail(function (xhr) {
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            showAllErrors(xhr.responseJSON.errors, "The settlement was saved as a draft but is not ready to submit:");
                        } else {
                            FP.alertFailure(xhr, function () { load(0, state.claim.ID); });
                        }
                    }).always(function () { setBusy(false); });
                }).fail(function () { setBusy(false); });
            }, function () {});
        });

        $("#fp-cancel-settlement").on("click", function () {
            FP.askReason({
                title: "Cancel " + state.claim.SettlementNumber,
                text: "The settlement will be marked as cancelled and kept for reference. You can start a new settlement for this advance afterwards.",
                required: false,
                confirmLabel: "Cancel settlement",
                buttonClass: "btn-danger"
            }, function (reason) {
                FP.request("POST", "action/cancel_settlement.php", { ID: state.claim.ID, RowVersion: state.claim.RowVersion, Reason: reason }).done(function (res) {
                    state.dirty = false;
                    alertify.alert("TechXpert", esc(res.message), function () { window.location.href = "view-advance-settlement.php?id=" + res.data.RequestID; });
                }).fail(function (xhr) { FP.alertFailure(xhr, function () { load(0, state.claim.ID); }); });
            });
        });

        $(window).on("beforeunload", function () {
            if (state.dirty) {
                return "You have unsaved changes.";
            }
        });
    });
})(jQuery, window.FP);
