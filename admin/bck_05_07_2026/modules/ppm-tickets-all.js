




    function openPPM_modal(BranchAssetID, BranchID, CorporateID, CreatedBy) {
        $('#corporate_modal_id').val(CorporateID);
        $('#branch_modal_id').val(BranchID);
        $('#branch_asset_modal_id').val(BranchAssetID);
        $('#created_by_modal').val(CreatedBy);
        $("#branch_modal_title").html("Add PPM");
        $("#raise_ppm_ticket")[0].reset();
        $("#form_action").val("add");
        $("#add_edit_arc_modal").modal();
    }

    function ViewPPMTicketDetails(TicketID) {
        $.post(
            "../controllers/setSession.php", {
                TicketID: TicketID,
            },
            function(data, status) {
                window.open("view-ppm-tickets-details.php",'_blank');
            }
        );
    }

    $('.ppm_date').datepicker({
                format: "yyyy-mm-dd",
                todayBtn: "linked",
                clearBtn: true,
                todayHighlight: true,
                autoclose: true,
                minDate: 0,

            });



// View PPM ticket JS End

  function ExportPPMTicketData() {
  $.ajax({
      url: "action/export_ppm_ticket.php",
      type: "POST",
      data: $("#import_form").serialize(),
      success: function (data) {
          window.location.href = "report.xls";
      },
  });
  return false;
}

function FilterPPMTickets()
{

    var table = $('#view-all-ppm-tickets').DataTable();
    table.destroy();
    var param = "";
    var filter_date = document.getElementById("filter_date").value;
    param = "?filter_date=" + filter_date;
    var statusObject = document.getElementById("ticket_status");
    if(statusObject !== null)
    {
        var status = document.getElementById("ticket_status").value;
        param = param+"&status="+status;
    }
    var companyaccountObject = document.getElementById("filter_company_id");
    if(companyaccountObject !== null)
    {
        var filter_company_id = document.getElementById("filter_company_id").value;
        param = param+"&filter_company_id="+filter_company_id;
    }
    var branchObject = document.getElementById("branch_name");
    if(branchObject !== null)
    {
        var BranchID = document.getElementById("branch_name").value;
        param = param+"&BranchID="+BranchID;
    }
    var stateObject = document.getElementById("stateName");
    if(stateObject !== null)
    {
        var stateName = document.getElementById("stateName").value;
        param = param+"&SateName="+stateName;
    }

    $('#view-all-ppm-tickets').dataTable({
            responsive: true,
            'processing': true,
            'serverSide': true,
            'ordering': false,
            'serverMethod': 'post',
            'ajax': {
                'url': 'ajax/view-ppm-tickets-post.php'+param
            },
            'columnDefs': [{
                "targets": [0],
                "className": "text-center"
            }],
            
            'columns': [{
                    "data": "id",
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },
                {
                    data: 'TicketID'
                },
                {
                    data: 'BranchAsset'
                },
                {
                    data: 'Corporate'
                },
                {
                    data: 'Branch'
                },
                {
                    data: 'PPM_Date'
                },
                {
                    data: 'Status'
                },
                {
                    data: 'View_Ticket'
                },
                {
                    data: 'Action'
                }
            ]


        });
}

function ExportPPMTicketsData() {
  
  document.getElementById("filter_date_export").value = document.getElementById("filter_date").value;

    if (document.getElementById("filter_company_id")) {
      document.getElementById("company_account_export").value = document.getElementById("filter_company_id").value;
    }

    if (document.getElementById("ticket_status")) {
      document.getElementById("status_export").value = document.getElementById("ticket_status").value;
    }

    if (document.getElementById("stateName")) {
      document.getElementById("state_export").value = document.getElementById("stateName").value;
    }
      
    // OPTIONAL (just to confirm selection)
    let dateType = document.querySelector(
        'input[name="date_type_export"]:checked'
    ).value;

    console.log("Export Date Type:", dateType);
  $.ajax({
      url: "action/export_all_ppm_tickets.php",
      type: "POST",
      data: $("#export_form").serialize(),
      success: function (data) {
          window.location.href = "report.xls";
      },
  });
  return false;
}

function DeletePPMTicket(ID)
{
     //alert(deleteid);
    alertify.confirm('TechXpert ', 'Do you really want to delete PPM Ticket', function() {
            $.post("action/delete-ppm-ticket.php", {
                    ID: ID
                },
                function(data, status) {
                    var response = JSON.parse(data);
                    TechXAlert(response.message);
                    if (response.error == false)
                    {
                      setInterval(function(){
                        location.reload();
                      }, 2000);
                    }
                });

        },
        function() {
            alertify.error('Deletion Cancelled')
        });
}

function ppm_GetBranchesFromCorporateID(selection)
{
    $.ajax({
      url: "ajax/get_branches_list.php",
      type: "POST",
      data: 
      {
        "CompanyID":selection.value
      },
      success: function (data_response) {
          document.getElementById("branches_filter_div").innerHTML = data_response;
          $("#branch_name").select2();
      },
  });
}

