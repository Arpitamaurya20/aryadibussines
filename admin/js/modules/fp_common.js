/* Finance & Performance shared browser helpers. */
window.FP = (function ($) {
    "use strict";

    var csrfToken = $('meta[name="fp-csrf-token"]').attr("content") || "";
    var reasonHandler = null;
    var money = window.Intl ? new Intl.NumberFormat("en-IN", { style: "currency", currency: "INR" }) : null;

    function esc(value) {
        return String(value === null || value === undefined ? "" : value).replace(/[&<>"']/g, function (c) {
            return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c];
        });
    }

    function request(method, url, data, extra) {
        return $.ajax($.extend({ url: url, type: method, data: data || {}, dataType: "json", headers: { "X-CSRF-Token": csrfToken } }, extra || {}));
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

    function alertFailure(xhr, onConflict) {
        alertify.alert("TechXpert", esc(failMessage(xhr)));
        if (onConflict && xhr && (xhr.status === 404 || xhr.status === 409)) {
            onConflict();
        }
    }

    function clearErrors($scope) {
        $scope.find(".is-invalid").removeClass("is-invalid");
        $scope.find(".invalid-feedback[data-for]").text("");
        $scope.find("div.text-danger[data-for]").text("");
    }

    function showErrors($scope, errors) {
        $.each(errors || {}, function (field, message) {
            $scope.find('[name="' + field + '"]').addClass("is-invalid");
            $scope.find('[data-for="' + field + '"]').text(message);
        });
    }

    function formatMoney(value) {
        var number = parseFloat(value || 0);
        return money ? money.format(number) : "Rs. " + number.toFixed(2);
    }

    /* options: title, text, required, confirmLabel, buttonClass. Uses #fp-reason-modal on the page. */
    function askReason(options, onConfirm) {
        $("#fp-reason-title").text(options.title);
        $("#fp-reason-text").text(options.text);
        $("#fp-reason-label").text(options.required ? "Reason (required)" : "Reason (optional)");
        $("#fp-reason-input").val("").removeClass("is-invalid");
        $("#fp-reason-confirm").text(options.confirmLabel).attr("class", "btn " + options.buttonClass);
        reasonHandler = { required: options.required, onConfirm: onConfirm };
        $("#fp-reason-modal").modal("show");
    }

    $(function () {
        $(document).on("hidden.bs.modal", ".modal", function () {
            if ($(".modal.show").length) {
                $("body").addClass("modal-open");
            }
        });
        $("#fp-reason-confirm").on("click", function () {
            var reason = $.trim($("#fp-reason-input").val());
            if (!reasonHandler) {
                return;
            }
            if (reasonHandler.required && reason.length < 5) {
                $("#fp-reason-input").addClass("is-invalid");
                return;
            }
            var handler = reasonHandler;
            reasonHandler = null;
            $("#fp-reason-modal").modal("hide");
            handler.onConfirm(reason);
        });
    });

    function activateNav(groupKey, itemKey) {
        $("#nav_finance_performance").addClass("active open");
        if (groupKey) {
            $("#nav_fp_group_" + groupKey).addClass("active open");
        }
        $("#nav_fp_" + itemKey).addClass("active");
    }

    return {
        esc: esc,
        request: request,
        failMessage: failMessage,
        alertFailure: alertFailure,
        clearErrors: clearErrors,
        showErrors: showErrors,
        formatMoney: formatMoney,
        askReason: askReason,
        activateNav: activateNav
    };
})(jQuery);
