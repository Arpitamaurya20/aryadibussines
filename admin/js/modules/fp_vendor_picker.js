/* Finance & Performance: payee picker for direct and recurring expenses.
   Searches approved vendors in the ERP vendor master; in 'optional' mode a payee who is not a registered vendor
   (landlord, electricity board) can be typed instead. Posts VendorRegistrationID, VendorName, VendorGSTIN, VendorAccountRef. */
(function ($, FP) {
    "use strict";

    var esc = FP.esc;

    function template(id) {
        return '<input type="hidden" name="VendorRegistrationID" value="0">' +
            '<div class="fp-vp-chosen border rounded p-2 mb-2 bg-light" style="display:none"></div>' +
            '<div class="fp-vp-find">' +
                '<label for="' + id + '-q">Vendor from the vendor master</label>' +
                '<div class="position-relative">' +
                    '<input type="search" class="form-control fp-vp-q" id="' + id + '-q" maxlength="60" autocomplete="off" placeholder="Type 2+ letters of the name, GSTIN or vendor code">' +
                    '<div class="dropdown-menu w-100 fp-vp-results" style="max-height:260px;overflow:auto"></div>' +
                '</div>' +
                '<div class="text-danger small" data-for="VendorRegistrationID"></div>' +
                '<a href="#" class="small fp-vp-manual-on" style="display:none">Not a registered vendor (landlord, electricity board...)? Enter the payee instead</a>' +
            '</div>' +
            '<div class="fp-vp-manual" style="display:none">' +
                '<div class="form-row">' +
                    '<div class="form-group col-md-7 mb-2"><label for="' + id + '-name">Payee name</label>' +
                        '<input type="text" class="form-control" name="VendorName" id="' + id + '-name" maxlength="150">' +
                        '<div class="invalid-feedback" data-for="VendorName"></div></div>' +
                    '<div class="form-group col-md-5 mb-2"><label for="' + id + '-gstin">GSTIN (if any)</label>' +
                        '<input type="text" class="form-control text-uppercase" name="VendorGSTIN" id="' + id + '-gstin" maxlength="15">' +
                        '<div class="invalid-feedback" data-for="VendorGSTIN"></div></div>' +
                '</div>' +
                '<a href="#" class="small fp-vp-manual-off">Search the vendor master instead</a>' +
            '</div>' +
            '<div class="form-group mt-2 mb-0"><label for="' + id + '-ref">Consumer / customer / agreement no.</label>' +
                '<input type="text" class="form-control" name="VendorAccountRef" id="' + id + '-ref" maxlength="60" placeholder="e.g. electricity consumer no. or rent agreement ref.">' +
                '<small class="form-text text-muted">Not a bank account number. Bank details of registered vendors come from the vendor master.</small>' +
                '<div class="invalid-feedback" data-for="VendorAccountRef"></div></div>';
    }

    /* options: mode ('optional' | 'required'), onChange(). Returns { get, set, setMode, setDisabled }. */
    FP.vendorPicker = function ($el, options) {
        options = options || {};
        var id = $el.attr("id") || "fp-vp";
        var mode = options.mode || "optional";
        var chosen = null;
        var timer = null;
        var lastQuery = "";
        $el.html(template(id));
        var $q = $el.find(".fp-vp-q");
        var $results = $el.find(".fp-vp-results");

        function changed() {
            if (options.onChange) {
                options.onChange();
            }
        }

        function showManual(on) {
            $el.find(".fp-vp-manual").toggle(on);
            $el.find(".fp-vp-find").toggle(!on && !chosen);
            $el.find(".fp-vp-manual-on").toggle(mode === "optional" && !on);
        }

        function renderChosen() {
            var $box = $el.find(".fp-vp-chosen");
            if (!chosen) {
                $box.hide().empty();
                $el.find('[name="VendorRegistrationID"]').val("0");
                return;
            }
            $el.find('[name="VendorRegistrationID"]').val(String(chosen.ID));
            var details = [chosen.GSTIN ? "GSTIN " + chosen.GSTIN : "", chosen.Category, chosen.Place, chosen.AccountHint ? "Bank: " + chosen.AccountHint : ""].filter(Boolean);
            $box.html('<div class="d-flex"><div class="mr-auto"><strong>' + esc(chosen.Name) + '</strong> <span class="badge badge-success">' + esc(chosen.Code || "Registered vendor") + "</span>" +
                (details.length ? '<div class="small text-muted">' + esc(details.join(" | ")) + "</div>" : "") + "</div>" +
                '<div><button type="button" class="btn btn-xs btn-outline-secondary fp-vp-change">Change</button></div></div>').show();
            $el.find(".fp-vp-find, .fp-vp-manual").hide();
        }

        function search() {
            var q = $.trim($q.val());
            if (q.length < 2) {
                $results.removeClass("show").empty();
                return;
            }
            lastQuery = q;
            FP.request("GET", "action/search_vendors.php", { q: q }).done(function (res) {
                if (q !== lastQuery) {
                    return;
                }
                var list = res.data || [];
                var html = list.map(function (v, index) {
                    return '<a href="#" class="dropdown-item fp-vp-item" data-index="' + index + '" style="white-space:normal"><strong>' + esc(v.Name) + "</strong> " +
                        '<span class="small text-muted">' + esc([v.Code, v.GSTIN, v.Place].filter(Boolean).join(" | ")) + "</span></a>";
                }).join("");
                if (!html) {
                    html = '<span class="dropdown-item-text small text-muted">No approved vendor matches "' + esc(q) + '".' +
                        (mode === "optional" ? " Use \"Enter the payee instead\" for a landlord or utility." : " Ask for the vendor to be registered and approved first.") + "</span>";
                }
                $results.data("list", list).html(html).addClass("show");
            }).fail(function (xhr) { FP.alertFailure(xhr); });
        }

        $q.on("input", function () {
            clearTimeout(timer);
            timer = setTimeout(search, 300);
        }).on("blur", function () {
            setTimeout(function () { $results.removeClass("show"); }, 200);
        });
        $el.on("click", ".fp-vp-item", function (e) {
            e.preventDefault();
            chosen = $results.data("list")[parseInt($(this).data("index"), 10)];
            $results.removeClass("show").empty();
            $q.val("");
            $el.find('[name="VendorName"], [name="VendorGSTIN"]').val("");
            renderChosen();
            changed();
        }).on("click", ".fp-vp-change", function () {
            chosen = null;
            renderChosen();
            showManual(false);
            $q.focus();
            changed();
        }).on("click", ".fp-vp-manual-on", function (e) {
            e.preventDefault();
            showManual(true);
            $el.find('[name="VendorName"]').focus();
        }).on("click", ".fp-vp-manual-off", function (e) {
            e.preventDefault();
            $el.find('[name="VendorName"], [name="VendorGSTIN"]').val("");
            showManual(false);
            changed();
        }).on("input", '[name="VendorGSTIN"]', function () {
            this.value = this.value.toUpperCase();
        });

        var api = {
            get: function () {
                return {
                    VendorRegistrationID: chosen ? chosen.ID : 0,
                    VendorName: chosen ? "" : $.trim($el.find('[name="VendorName"]').val()),
                    VendorGSTIN: chosen ? "" : $.trim($el.find('[name="VendorGSTIN"]').val()),
                    VendorAccountRef: $.trim($el.find('[name="VendorAccountRef"]').val())
                };
            },
            /* values: VendorRegistrationID, VendorName, VendorGSTIN, VendorAccountRef, VendorLabel (registration code) */
            set: function (values) {
                values = values || {};
                $el.find('[name="VendorAccountRef"]').val(values.VendorAccountRef || "");
                if (values.VendorRegistrationID) {
                    chosen = { ID: values.VendorRegistrationID, Name: values.VendorName, Code: values.VendorLabel || "", GSTIN: values.VendorGSTIN, Category: "", Place: "", AccountHint: "" };
                    $el.find('[name="VendorName"], [name="VendorGSTIN"]').val("");
                    renderChosen();
                    return;
                }
                chosen = null;
                renderChosen();
                $el.find('[name="VendorName"]').val(values.VendorName || "");
                $el.find('[name="VendorGSTIN"]').val(values.VendorGSTIN || "");
                showManual(mode === "optional" && !!values.VendorName);
            },
            setMode: function (newMode) {
                mode = newMode === "required" ? "required" : "optional";
                showManual(mode === "optional" && $el.find(".fp-vp-manual").is(":visible"));
            },
            setDisabled: function (disabled) {
                $el.find("input").prop("disabled", disabled);
                $el.find(".fp-vp-change, .fp-vp-manual-on, .fp-vp-manual-off").toggle(!disabled);
            }
        };
        showManual(false);
        return api;
    };
})(jQuery, window.FP);
