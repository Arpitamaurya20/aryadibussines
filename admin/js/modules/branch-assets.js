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
function openBranch_modal() {
    $("#branch_modal_title").html("Add Branch Assets");
    $("#add_update_branch_assets_form")[0].reset();
    $("#form_action").val("add");
    $("#branch_id").select2();
    $("#uom").select2();
    $("#service_type").select2();
    $("#categories").select2();
    $("#sub_categories").select2();
    // $("#equipment_location").select2();
    const element = document.getElementById('branch_asset_disable_ppm_btn');
    if (element) {
        // If it exists, set its display style to 'none'
        element.style.display = 'none';
    }
    $("#add_edit_branch_assets_modal").modal();

    $("#amc_start_date").datepicker({
        format: "yyyy-mm-dd",
        todayBtn: "linked",
        clearBtn: true,
        todayHighlight: true,
        autoclose: true,
        startDate:'+0d',
    });
    $("#amc_end_date").datepicker({
        format: "yyyy-mm-dd",
        todayBtn: "linked",
        clearBtn: true,
        todayHighlight: true,
        autoclose: true,
        startDate:'+0d',
    });
}
function UpdateBranch_modal(branch_asset_id) {
    $("#branch_modal_title").html("Update Branch Assets");
    
    $.post("action/get_branch_assets_details.php", {
        ID: branch_asset_id
    },
        function (data, status) {
            var response = JSON.parse(data);
            if (response.error == false) {
                var branch_id = response.data.BranchID;
                var equipment_name = response.data.EquipmentName;
                var make = response.data.Make;
                var model = response.data.Model;
                var serial_no = response.data.SNo;
                var capacity = response.data.Capacity;
                var quantity = response.data.Qty;
                var uom = response.data.UoM;
                var unit_rate = response.data.UnitRate;
                var amount = response.data.Amount;
                var manufacturing_year = response.data.ManufacturingYear;
                var equipment_age = response.data.EquipmentAge;
                var service_type = response.data.ServiceType;
                var categories = response.data.Category ;
                var sub_categories = response.data.SubCategory;
                var tat = response.data.Tat;
                var floor_number = response.data.FloorNumber;
                var equipment_location = response.data.EquipmentLocation;
                var description = response.data.Description;
                var amc_start_date = response.data.AMCStartDate;
                var amc_end_date = response.data.AMCEndDate;
                var PPMInterval=response.PPMInterval;

                $("#branch_id").val(branch_id);
                $("#equipment_name").val(equipment_name);
                $("#make").val(make);
                $("#model").val(model);
                $("#serial_no").val(serial_no);
                $("#capacity").val(capacity);
                $("#quantity").val(quantity);
                $("#uom").val(uom);
                $("#unit_rate").val(unit_rate);
                $("#amount").val(amount);
                $("#manufacturing_year").val(manufacturing_year);
                $("#equipment_age").val(equipment_age);
                $("#service_type").val(service_type);
                $("#categories").val(categories);
                $("#sub_categories").val(sub_categories);
                $("#tat").val(tat);
                $("#floor_number").val(floor_number);
                $("#equipment_location").val(equipment_location);
                $("#description").val(description);
                $("#form_action").val("Update");
                $("#form_id").val(branch_asset_id);
                $("#amc_start_date").val(amc_start_date);
                $("#amc_end_date").val(amc_end_date);
                $("#PPMInterval").val(PPMInterval);
                $("#add_edit_branch_assets_modal").modal();
                $("#branch_id").select2();
                $("#uom").select2();
                $("#service_type").select2();
                $("#equipment_type").select2();
                $("#categories").select2();
                $("#sub_categories").select2();
                $("#sub_categories_div").css("display","block");
                const element = document.getElementById('branch_asset_disable_ppm_btn');
                if (element) {
                    // If it exists, set its display style to 'none'
                    element.style.display = '';
                }
                

                $("#amc_start_date").datepicker({
                    format: "yyyy-mm-dd",
                    todayBtn: "linked",
                    clearBtn: true,
                    todayHighlight: true,
                    autoclose: true,
                    startDate:'+0d',
                });
                $("#amc_end_date").datepicker({
                    format: "yyyy-mm-dd",
                    todayBtn: "linked",
                    clearBtn: true,
                    todayHighlight: true,
                    autoclose: true,
                    startDate:'+0d',
                });
                // $("#equipment_location").select2();
            }
        });

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
                    location.reload();
                }, 2000);
            }
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

  function SelectBranchAssetsCategories() {
        $.post("action/get_category.php", {
                CategoryID: $("#categories").val()
            },
            function(data, status) {
                document.getElementById("sub_categories_div").style.display = "block";
                document.getElementById("sub_categories").innerHTML = data;
            });
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
    var param = "?p=1";
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
    var table = $('#view-branch-assets').DataTable();
    table.destroy(); 
    var columns = [
            {
                "data": "id",
                render: function(data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            {
                data: 'CompanyBranches'
            },
            {
                data: 'EquipmentName'
            },
            {
                data: 'Make_Model'
            },
            {
                data: 'SNo'
            },
            {
                data: 'Capacity'
            },
            {
                data: 'ManufacturingYear'
            },
            {
                data: 'ServiceType'
            },
            {
                data: 'FloorNumber_EquipmentLocation'
            },
            {
                data: 'PPM'
            },
            {
                data: 'AMCTicket'
            },
            {
                data: 'Update'
            },
            {
                data: 'Action'
            }
        ];

       /* // If nav is 1, add 'CompanyBranches' column at the second position
        if (nav == 1) {
            columns.splice(1, 0, {
                data: 'CompanyBranches'
            });
        }*/

        // Initialize DataTable with dynamic columns
        $('#view-branch-assets').dataTable({
            responsive: true,
            'processing': true,
            'serverSide': true,
            'ordering': false,
            'serverMethod': 'post',
            'ajax': {
                'url': 'include/branch-assets-list-post.php'+param
            },
            'columnDefs': [{
                "targets": [0],
                "className": "text-center"
            }],
            "order": [
                [1, 'asc']
            ],
            'columns': columns // Set dynamic columns here
        });

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
                // Optionally, reload DataTable or page
                $('#view-branch-assets').DataTable().ajax.reload();
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