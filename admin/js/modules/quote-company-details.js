
$(document).ready(function() {
    $("#nav_configuration").addClass("active");
    $("#nav_configuration").addClass("open");
    $("#nav_quote_company_details").addClass("active");
});

function openQuoteCompanyDetailsModal(mode) {
    $("#form_quote_company_details")[0].reset();
    $("#qcd_id").val("");
    $("#qcd_existing_header_image").val("");
    $("#qcd_existing_stamp_image").val("");
    $("#qcd_existing_header_image_wrap").hide();
    $("#qcd_existing_header_image_link").attr("href", "#");
    $("#qcd_existing_stamp_image_wrap").hide();
    $("#qcd_existing_stamp_image_link").attr("href", "#");
    if (mode === "add") {
        $("#form_action_qcd").val("add");
        $("#modal_quote_company_details_title").text("Add company quote details");
    }
    $("#modal_quote_company_details").modal("show");
}

function viewQuoteCompanyDetails(id) {
    $.post("action/get_quote_company_details.php", { ID: id }, function(res) {
        var response = typeof res === "object" ? res : JSON.parse(res);
        if (response.error) {
            alertify.alert("TechXpert", response.message);
            return;
        }
        var d = response.data;
        var dash = "—";
        $("#qcd_view_id").text(d.ID != null ? String(d.ID) : dash);
        $("#qcd_view_company_name").text(d.CompanyName || dash);
        $("#qcd_view_company_address").text(d.CompanyAddress || dash);
        $("#qcd_view_gst").text(d.GstNumber || dash);
        $("#qcd_view_pan").text(d.PanNumber || dash);
        $("#qcd_view_email").text(d.Email || dash);
        $("#qcd_view_phone").text(d.Phone || dash);
        $("#qcd_view_created_at").text(d.created_at || dash);
        var active = parseInt(d.IsActive, 10) === 1;
        $("#qcd_view_status").html(
            active
                ? '<span class="badge badge-success">Active</span>'
                : '<span class="badge badge-secondary">Inactive</span>'
        );
        if (d.HeaderImage) {
            var imgPath = "../media/pdf-assets/" + d.HeaderImage;
            $("#qcd_view_header_image").html('<a href="' + imgPath + '" target="_blank"><img src="' + imgPath + '" alt="Header image" style="max-width:240px;max-height:80px;border:1px solid #ddd;padding:2px;"></a>');
        } else {
            $("#qcd_view_header_image").text(dash);
        }
        if (d.StampImage) {
            var stampPath = "../media/pdf-assets/" + d.StampImage;
            $("#qcd_view_stamp_image").html('<a href="' + stampPath + '" target="_blank"><img src="' + stampPath + '" alt="Stamp image" style="max-width:140px;max-height:140px;border:1px solid #ddd;padding:2px;"></a>');
        } else {
            $("#qcd_view_stamp_image").text(dash);
        }
        $("#modal_quote_company_details_view").modal("show");
    });
}

function editQuoteCompanyDetails(id) {
    $.post("action/get_quote_company_details.php", { ID: id }, function(res) {
        var response = typeof res === "object" ? res : JSON.parse(res);
        if (response.error) {
            alertify.alert("TechXpert", response.message);
            return;
        }
        var d = response.data;
        $("#form_action_qcd").val("edit");
        $("#qcd_id").val(d.ID);
        $("#qcd_company_name").val(d.CompanyName || "");
        $("#qcd_company_address").val(d.CompanyAddress || "");
        $("#qcd_gst").val(d.GstNumber || "");
        $("#qcd_pan").val(d.PanNumber || "");
        $("#qcd_email").val(d.Email || "");
        $("#qcd_phone").val(d.Phone || "");
        $("#qcd_header_image").val("");
        $("#qcd_stamp_image").val("");
        $("#qcd_existing_header_image").val(d.HeaderImage || "");
        $("#qcd_existing_stamp_image").val(d.StampImage || "");
        if (d.HeaderImage) {
            var imgPath = "../media/pdf-assets/" + d.HeaderImage;
            $("#qcd_existing_header_image_link").attr("href", imgPath);
            $("#qcd_existing_header_image_wrap").show();
        } else {
            $("#qcd_existing_header_image_wrap").hide();
            $("#qcd_existing_header_image_link").attr("href", "#");
        }
        if (d.StampImage) {
            var stampPath = "../media/pdf-assets/" + d.StampImage;
            $("#qcd_existing_stamp_image_link").attr("href", stampPath);
            $("#qcd_existing_stamp_image_wrap").show();
        } else {
            $("#qcd_existing_stamp_image_wrap").hide();
            $("#qcd_existing_stamp_image_link").attr("href", "#");
        }
        $("#modal_quote_company_details_title").text("Edit company quote details");
        $("#modal_quote_company_details").modal("show");
    });
}

function submitQuoteCompanyDetailsForm() {
    var fileInput = $("#qcd_header_image")[0];
    if (fileInput && fileInput.files && fileInput.files.length > 0) {
        var file = fileInput.files[0];
        if (file.type.indexOf("image/") !== 0) {
            alertify.alert("TechXpert", "Only image files are allowed for header image.");
            return false;
        }
    }
    var stampFileInput = $("#qcd_stamp_image")[0];
    if (stampFileInput && stampFileInput.files && stampFileInput.files.length > 0) {
        var stampFile = stampFileInput.files[0];
        if (stampFile.type.indexOf("image/") !== 0) {
            alertify.alert("TechXpert", "Only image files are allowed for stamp image.");
            return false;
        }
    }
    var formData = new FormData($("#form_quote_company_details")[0]);
    $.ajax({
        url: "action/add_update_quote_company_details.php",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function(data) {
            var response = typeof data === "object" ? data : JSON.parse(data);
            alertify.alert("TechXpert", response.message);
            if (!response.error) {
                $("#modal_quote_company_details").modal("hide");
                $("#quote-company-details-table").DataTable().ajax.reload(null, false);
            }
        }
    });
    return false;
}

function toggleQuoteCompanyDetails(id, isActive) {
    var msg = isActive === 1 ? "Activate this record?" : "Deactivate this record (soft delete)?";
    alertify.confirm("TechXpert", msg, function() {
        $.post("action/toggle_quote_company_details_status.php", { ID: id, IsActive: isActive }, function(data) {
            var response = typeof data === "object" ? data : JSON.parse(data);
            alertify.alert("TechXpert", response.message);
            if (!response.error) {
                $("#quote-company-details-table").DataTable().ajax.reload(null, false);
            }
        });
    }, function() {
        alertify.error("Cancelled");
    });
}
