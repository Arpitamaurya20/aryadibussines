$(document).ready(function () {
    /*$("#nav_corporate").addClass("open");
    $("#nav_corporate").addClass("active");
    $("#nav_branch").addClass("active");*/
    // $('#view-branch').dataTable({
    //     responsive: true
    // });

    $('.js-thead-colors a').on('click', function () {
        var theadColor = $(this).attr("data-bg");
        console.log(theadColor);
        $('#dt-basic-example thead').removeClassPrefix('bg-').addClass(theadColor);
    });

    $('.js-tbody-colors a').on('click', function () {
        var theadColor = $(this).attr("data-bg");
        console.log(theadColor);
        $('#dt-basic-example').removeClassPrefix('bg-').addClass(theadColor);
    });

});
var branchAssetsModalReady = false;
var branchAssetsDetailsXhr = null;

function getBranchAssetsSelect2Options() {
    return {
        dropdownParent: $("#add_edit_branch_assets_modal"),
        width: "100%"
    };
}

function initBranchAssetsModalWidgets() {
    if (branchAssetsModalReady || typeof $.fn.select2 !== "function") {
        return;
    }

    var select2Opts = getBranchAssetsSelect2Options();
    $("#uom, #service_type, #categories, #sub_categories, #PPMInterval, #asset_checklist_id").each(function () {
        var $el = $(this);
        if (!$el.hasClass("select2-hidden-accessible")) {
            $el.select2(select2Opts);
        }
    });

    var $branch = $("#branch_id");
    if ($branch.length && !$branch.hasClass("select2-hidden-accessible")) {
        $branch.select2($.extend({}, select2Opts, {
            placeholder: "Search & Select",
            allowClear: false,
            ajax: {
                url: "action/search_branches.php",
                dataType: "json",
                delay: 250,
                data: function (params) {
                    return { q: params.term || "" };
                },
                processResults: function (data) {
                    return { results: data.results || [] };
                },
                cache: true
            },
            minimumInputLength: 0
        }));
    }

    // No startDate restriction — allow past (back) dates for AMC period
    var datepickerOpts = {
        format: "yyyy-mm-dd",
        todayBtn: "linked",
        clearBtn: true,
        todayHighlight: true,
        autoclose: true
    };
    ["#amc_start_date", "#amc_end_date"].forEach(function (selector) {
        var $el = $(selector);
        if ($el.data("datepicker")) {
            $el.datepicker("destroy");
        }
        $el.datepicker(datepickerOpts);
    });

    branchAssetsModalReady = true;
}

function showBranchAssetsModalLoader(show) {
    var $loader = $("#branch_assets_modal_loader");
    if (!$loader.length) {
        return;
    }
    if (show) {
        $loader.show();
    } else {
        $loader.hide();
    }
}

function setSelectValueQuiet(selector, value) {
    var el = $(selector).get(0);
    var previousOnChange = el ? el.onchange : null;
    if (el) {
        el.onchange = null;
    }
    var safeValue = (value === null || value === undefined) ? "" : value;
    $(selector).val(safeValue);
    if ($(selector).hasClass("select2-hidden-accessible")) {
        $(selector).trigger("change.select2");
    }
    if (el) {
        el.onchange = previousOnChange;
    }
}

function resetBranchSelect() {
    var $branch = $("#branch_id");
    $branch.find("option").remove();
    $branch.append(new Option("Search & Select", "-1", true, true));
    $branch.val("-1");
    if ($branch.hasClass("select2-hidden-accessible")) {
        $branch.trigger("change.select2");
    }
}

function setBranchSelect(branchId, branchName) {
    var $branch = $("#branch_id");
    if (!branchId || branchId == "-1") {
        resetBranchSelect();
        return;
    }
    var label = branchName || ("Branch #" + branchId);
    if ($branch.find("option[value='" + branchId + "']").length === 0) {
        $branch.append(new Option(label, branchId, true, true));
    }
    $branch.val(String(branchId));
    if ($branch.hasClass("select2-hidden-accessible")) {
        $branch.trigger("change.select2");
    }
}

function openBranch_modal() {
    if (branchAssetsDetailsXhr && branchAssetsDetailsXhr.readyState !== 4) {
        branchAssetsDetailsXhr.abort();
    }
    $("#branch_modal_title").html("Add Branch Assets");
    $("#add_update_branch_assets_form")[0].reset();
    $("#form_action").val("add");
    $("#form_id").val("-1");
    showBranchAssetsModalLoader(false);
    $("#add_edit_branch_assets_modal").modal("show");
    initBranchAssetsModalWidgets();
    resetBranchSelect();
    setSelectValueQuiet("#uom", "-1");
    setSelectValueQuiet("#service_type", "-1");
    setSelectValueQuiet("#categories", "-1");
    setSelectValueQuiet("#sub_categories", "-1");
    setSelectValueQuiet("#PPMInterval", "-1");
    resetAssetChecklistSelect("Select category first");
    $("#sub_categories_div").css("display", "none");
    const element = document.getElementById('branch_asset_disable_ppm_btn');
    if (element) {
        element.style.display = 'none';
    }
}
function UpdateBranch_modal(branch_asset_id) {
    $("#branch_modal_title").html("Update Branch Assets");
    $("#add_update_branch_assets_form")[0].reset();
    $("#form_action").val("Update");
    $("#form_id").val(branch_asset_id);
    showBranchAssetsModalLoader(true);
    $("#add_edit_branch_assets_modal").modal("show");
    initBranchAssetsModalWidgets();

    if (branchAssetsDetailsXhr && branchAssetsDetailsXhr.readyState !== 4) {
        branchAssetsDetailsXhr.abort();
    }

    branchAssetsDetailsXhr = $.ajax({
        url: "action/get_branch_assets_details.php",
        type: "POST",
        data: { ID: branch_asset_id },
        cache: false,
        success: function (data) {
            var response;
            try {
                response = typeof data === "object" ? data : JSON.parse(data);
            } catch (e) {
                showBranchAssetsModalLoader(false);
                TechXAlert("Unable to load asset details. Please try again.");
                return;
            }
            if (response.error == false && response.data) {
                var asset = response.data;
                setBranchSelect(asset.BranchID, asset.BranchSite);
                $("#equipment_name").val(asset.EquipmentName);
                $("#make").val(asset.Make);
                $("#model").val(asset.Model);
                $("#serial_no").val(asset.SNo);
                $("#capacity").val(asset.Capacity);
                $("#quantity").val(asset.Qty);
                setSelectValueQuiet("#uom", asset.UoM);
                $("#unit_rate").val(asset.UnitRate);
                $("#amount").val(asset.Amount);
                $("#manufacturing_year").val(asset.ManufacturingYear);
                $("#equipment_age").val(asset.EquipmentAge);
                setSelectValueQuiet("#service_type", asset.ServiceType);
                setSelectValueQuiet("#categories", asset.Category);
                setSelectValueQuiet("#sub_categories", asset.SubCategory);
                $("#tat").val(asset.Tat);
                $("#floor_number").val(asset.FloorNumber);
                $("#equipment_location").val(asset.EquipmentLocation);
                $("#description").val(asset.Description);
                $("#form_action").val("Update");
                $("#form_id").val(branch_asset_id);
                $("#amc_start_date").val(asset.AMCStartDate);
                $("#amc_end_date").val(asset.AMCEndDate);
                setSelectValueQuiet("#PPMInterval", asset.PPMInterval);
                $("#sub_categories_div").css("display", "block");
                loadAssetCategoryChecklists(asset.Category, asset.AssetChecklistID);
                const element = document.getElementById('branch_asset_disable_ppm_btn');
                if (element) {
                    element.style.display = '';
                }
            } else {
                TechXAlert(response.message || "Unable to load asset details.");
            }
            showBranchAssetsModalLoader(false);
        },
        error: function (xhr, status) {
            if (status !== "abort") {
                showBranchAssetsModalLoader(false);
                TechXAlert("Unable to load asset details. Please try again.");
            }
        }
    });
}
function ToggleBranchAssetStatus(asset_id, is_active) {
    var actionLabel = is_active === 1 ? "activate" : "inactivate";
    alertify.confirm('TechXpert ', 'Do you really want to ' + actionLabel + ' this branch asset? Related corporate/PPM tickets will also be updated.', function () {
        $.post("action/toggle_branch_asset_status.php", {
            ID: asset_id,
            IsActive: is_active
        },
            function (data, status) {
                var response = JSON.parse(data);
                TechXAlert(response.message);
                if (response.error == false) {
                    setTimeout(function () {
                        reloadBranchAssetsTables();
                    }, 800);
                }
            });

    },
        function () {
            alertify.error('Action Cancelled')
        });
}

function DeactivateBranchAsset(asset_id) {
    ToggleBranchAssetStatus(asset_id, 0);
}

function ActivateBranchAsset(asset_id) {
    ToggleBranchAssetStatus(asset_id, 1);
}

function reloadBranchAssetsTables() {
    if ($.fn.DataTable.isDataTable('#view-branch-assets-active')) {
        $('#view-branch-assets-active').DataTable().ajax.reload(null, false);
    }
    if ($.fn.DataTable.isDataTable('#view-branch-assets-inactive')) {
        $('#view-branch-assets-inactive').DataTable().ajax.reload(null, false);
    }
}

function DeleteBranchAssets(branch_id) {
    alertify.confirm('TechXpert ', 'Do you really want to delete branch assets', function () {
        $.post("action/delete_branch_assets.php", {
            ID: branch_id
        },
            function (data, status) {
                var response = JSON.parse(data);
                TechXAlert(response.message);
                if (response.error == false) {
                    setInterval(function () {
                        location.reload();
                    }, 2000);
                }
            });

    },
        function () {
            alertify.error('Deletion Cancelled')
        });
}
function AddUpdateBranchAssets() {
    var branch = document.getElementById("branch_id").value;
    if (branch === "-1") {
        TechXAlert("Please Select Branch");
        return false;
    }

    var equipment_name = document.getElementById("equipment_name").value;
    if (equipment_name === "") {
        TechXAlert("Please Enter Equipment Name");
        return false;
    }


    var quantity = document.getElementById("quantity").value;
    if (quantity === "") {
        TechXAlert("Please Enter Qty");
        return false;
    }

    var equipment_age = document.getElementById("equipment_age").value;
    if (equipment_age === "") {
        TechXAlert("Please Enter Age of Equipment");
        return false;
    }

    var PPMInterval = document.getElementById("PPMInterval").value;
    if (PPMInterval === "-1") {
        TechXAlert("Please Select PPM Interval");
        return false;
    }


    let myForm = document.getElementById("add_update_branch_assets_form");
    var formData = new FormData(myForm);
    // document.getElementById("branch_assets_btn").innerHTML ="Submiting....";
     $("#branch_assets_btn")
      .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...')
      .prop("disabled", true);
    $.ajax({
        url: "action/add_update_branch_assets.php",
        type: "POST",
        data: formData,
        success: function (data) {
            var response = JSON.parse(data);
            TechXAlert(response.message);
            if (response.error == false) {
                setTimeout(function () {
                    reloadBranchAssetsTables();
                    $('#add_edit_branch_assets_modal').modal('hide');
                    $("#branch_assets_btn").html('Submit').prop("disabled", false);
                }, 800);
            } else {
                $("#branch_assets_btn").html('Submit').prop("disabled", false);
            }
        },
        error: function () {
            $("#branch_assets_btn").html('Submit').prop("disabled", false);
        },
        cache: false,
        contentType: false,
        processData: false,
    });
    return false;
}

function DisableAssetPPMTickets()
{
  var BranchAssetID = $("#form_id").val();
  alertify.confirm(
    "TechXpert ",
    "Do you really want to disable all PPM tickets for this asset?.",
    function () {
      $.post(
        "action/disable_branch_asset_ppm_tickets.php",
        {
          BranchAssetID: BranchAssetID,
        },
        function (data, status) {
          var response = JSON.parse(data);
          TechXAlert(response.message);
          
        }
      );
    },
    function () {
      alertify.error("Deletion Cancelled");
    }
  );
  
  
  return false;
}

// function AMCSelecte() {
//     // let AMCselect = document.getElementById("company_tendor");
//     let AMCdate = document.getElementById("amc_start_date");
//     let Enddate = document.getElementById("amc_end_date");
//     let selectedOption = $("#company_tendor").val();
//     if (!selectedOption.includes("AMC")) {
//       AMCdate.disabled = true;
//       Enddate.disabled = true;
//     } else {
//       AMCdate.disabled = false;
//       Enddate.disabled = false;
//     }
//   }

function MultiplyQty_Unit() {
    var Quantity = document.getElementById("quantity").value;
    var Unit = document.getElementById("unit_rate").value;
    let Amount = Quantity * Unit;
    $("#amount").val(Amount);
    $("#amount").attr('readonly', 'readonly');
}

function OpenRaiseAMCTicket(BranchAssetID, BranchID, CreatedBy) {
    //$('#corporate_modal_id').val(CorporateID);
    $('#branch_modal_id').val(BranchID);
    $('#branch_asset_modal_id').val(BranchAssetID);
    $('#created_by_modal').val(CreatedBy);
    $("#raiseamcticket").modal("show");
}

function RaiseAMCTickets() {
    var message = document.getElementById("message").value;
    if (message === "") {
        TechXAlert("Please type message");
        return false;
    }
    document.getElementById("ticket_raise_btn").innerHTML ="Submiting....";
    $.ajax({
        url: "action/add-amc-ticket.php",
        type: "POST",
        data: $("#raise_amc_tickets").serialize(),
        success: function (data) {
            var response = JSON.parse(data);
            TechXAlert(response.message);
            $("#raiseamcticket").modal("hide");
            if (response.error == false) {
                setInterval(function () {
                    location.reload();
                }, 2000);
            }
        },
    });
    return false;
}

function DownloadAssetsFileFormat() {
  // Replace 'file_url' with the URL of the file you want to download
  var file_url = 'https://techxpertindia.in/admin/branch-assets/Branch-Assets-Template.xlsx';
  
  // Create a new anchor element
  var link = document.createElement('a');
  
  // Set the href attribute to the file URL
  link.href = file_url;
  
  // Set the download attribute to the file name
  link.setAttribute('download', 'Branch-Assets-Template.xlsx');
  
  // Simulate a click on the anchor element to initiate the download
  link.click();
}

function resetAssetChecklistSelect(placeholder) {
    var $el = $("#asset_checklist_id");
    if (!$el.length) {
        return;
    }
    $el.find("option").remove();
    $el.append(new Option(placeholder || "Select category first", "-1", true, true));
    if ($el.hasClass("select2-hidden-accessible")) {
        $el.trigger("change.select2");
    }
}

function loadAssetCategoryChecklists(categoryId, selectedId, done) {
    var $el = $("#asset_checklist_id");
    if (!$el.length) {
        if (typeof done === "function") {
            done();
        }
        return;
    }
    if (!categoryId || categoryId == "-1") {
        resetAssetChecklistSelect("Select category first");
        if (typeof done === "function") {
            done();
        }
        return;
    }
    $.post("action/get_checklists_by_category.php", {
        CategoryID: categoryId
    }, function (data) {
        var response;
        try {
            response = typeof data === "object" ? data : JSON.parse(data);
        } catch (e) {
            response = { results: [] };
        }
        var wasSelect2 = $el.hasClass("select2-hidden-accessible");
        if (wasSelect2) {
            $el.select2("destroy");
        }
        $el.empty().append(new Option("No checklist mapped", "-1", true, true));
        if (response && response.results) {
            $.each(response.results, function (_, row) {
                $el.append(new Option(row.text, String(row.id), false, false));
            });
        }
        if (wasSelect2 || branchAssetsModalReady) {
            $el.select2(getBranchAssetsSelect2Options());
        }
        if (selectedId && selectedId != "-1") {
            setSelectValueQuiet("#asset_checklist_id", String(selectedId));
        }
        if (typeof done === "function") {
            done();
        }
    }).fail(function () {
        if (typeof done === "function") {
            done();
        }
    });
}

  function SelectBranchAssetsCategories() {
        $.post("action/get_category.php", {
                CategoryID: $("#categories").val()
            },
            function(data, status) {
                var $sub = $("#sub_categories");
                var wasSelect2 = $sub.hasClass("select2-hidden-accessible");
                if (wasSelect2) {
                    $sub.select2("destroy");
                }
                document.getElementById("sub_categories_div").style.display = "block";
                document.getElementById("sub_categories").innerHTML = data;
                if (wasSelect2 || branchAssetsModalReady) {
                    $sub.select2(getBranchAssetsSelect2Options());
                }
            });
        loadAssetCategoryChecklists($("#categories").val(), "-1");
    }

function ViewPPMTickets(BranchAssetsID)
{
    $.post(
        "../controllers/setSession.php", {
            BranchAssetsID: BranchAssetsID,
      },
      function(data, status) {
          BasicURLRouter("../ppm-ticket/view-ppm-ticket.php");
      }
  );
}

function ExportBranchAssetsData() {
  $.ajax({
      url: "action/export_branch_assets.php",
      type: "POST",
      data: $("#import_form").serialize(),
      success: function (data) {
          window.location.href = "report.xls";
      },
  });
  return false;
}

function OpenCSVmodal(){
       $("#upload_csv").modal();
}

function UploadBranchassets_CSV() {
  let myForm = document.getElementById("uplaod_branch_csv"); 
    var formData = new FormData(myForm);
    $.ajax({
        url: "action/upload_csv.php",
        type: "POST",
        data: formData,
        success: function (data) {
            var response = JSON.parse(data);
            TechXAlert(response.message);
        },
        cache: false,
        contentType: false,
        processData: false,
    });
    return false;
}

function FilterAssets()
{
    var param = "p=1";
    var branchObject = document.getElementById("branch_name");
    if(branchObject !== null)
    {
        var BranchID = document.getElementById("branch_name").value;
        param = param+"&BranchID="+BranchID;
    }
    var company_object = document.getElementById("filter_company_id");
    if(company_object !== null)
    {
        var CorporateID = document.getElementById("filter_company_id").value;
        param = param+"&CorporateID="+CorporateID;
    }
    if (typeof window.initBranchAssetsTable === "function") {
        window.initBranchAssetsTable('#view-branch-assets-active', 1, param);
        window.initBranchAssetsTable('#view-branch-assets-inactive', 0, param);
        return;
    }
}

function BranchAssetsGetBranchesFromCorporateID(selection)
{
    $.ajax({
      url: "include/ba_view_branches_filter_div.php",
      type: "POST",
      data: 
      {
        "CompanyID":selection.value
      },
      success: function (data_response) {
          document.getElementById("branches_filter_div").innerHTML = data_response;
      },
  });
}

function ViewEquipmentDetails(EquipmentID) 
{
    $.post(
        "../controllers/setSession.php", {
            EquipmentID: EquipmentID,
        },
        function(data, status) {
            BasicURLRouter("view-branch-asset-details");
        }
    );
}
function ba_ViewPPMTicketDetails(TicketID) {
        $.post(
            "../controllers/setSession.php", {
                TicketID: TicketID,
            },
            function(data, status) {
                BasicURLRouter("../ppm-ticket/view-ppm-tickets-details.php");
            }
        );
    }
function ba_ViewTicketDetails(TicketID) {
        $.post(
            "../controllers/setSession.php", {
                TicketID: TicketID,
            },
            function(data, status) {
                BasicURLRouter("../corporate-tickets/view-corporate-tickets-details.php");
            }
        );
    }


     function RaiseBulkTickets() {
    var bulkDate = $('#bulk_ticket_date').val();
    var branchID = $('#bulk_ticket_branch_id').val();
    if(bulkDate === '') {
        alert("Please select a date for the bulk ticket.");
        return false;
    }

    $.ajax({
        url: 'action/raise_bulk_tickets.php',
        type: 'POST',
        data: {
            bulk_ticket_date: bulkDate,
            branch_id: branchID
        },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                alert("Bulk tickets raised successfully!");
                $('#bulk_ticket_modal').modal('hide');
                reloadBranchAssetsTables();
            } else {
                alert("Error: " + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error(xhr.responseText);
            alert("Something went wrong while raising bulk tickets.");
        }
    });

    return false;
}