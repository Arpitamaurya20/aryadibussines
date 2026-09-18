function openStateGSTModal(Action)
{
  $("#state").select2();
  $("#form_action").val("add");
  $("#form_id").val("-1");
  $("#stateGSTModal").modal("show");
}

function SaveStateGST()
{
  var state = $("#state").val();
  if(state == -1)
  {
    TechXAlert("Kindly Select State");
    return false;
  }
  var state_gst = $("#state_gst").val();
  if(state_gst == "")
  {
    TechXAlert("Please Enter GST number");
    return false;
  }
  var state_gst_address = $("#state_gst_address").val();
  if(state_gst_address == "")
  {
    TechXAlert("Kindly Enter Billing Address");
    return false;
  }
  $.ajax({
    url: "action/update_state_gst.php",
    type: "POST",
    data: $("#add_update_company_state_gst").serialize(),
    success: function (data) {
      var response = JSON.parse(data);
      TechXAlert(response.message);
      // Refresh Datatable
      $("#stateGSTModal").modal("hide");
      $('#view-state-gst').DataTable().ajax.reload();
    },
  });
}

function UpdateStateGST(ID)
{
  $.post("action/getStateGSTDetails.php", {
            StateGSTID: ID,
      },
      function(data, status) {
        var data_response = JSON.parse(data);
        $("#form_action").val("update");
        $("#form_id").val(ID);
        $("#state").val(data_response.CompanyState);
        $("#state").select2();
        $("#state_gst").val(data_response.GST);
        $("#state_gst_address").val(data_response.Address);
        $("#stateGSTModal").modal("show");
      }
  );
}

function DeleteStateGST(ID)
{
  alertify.confirm(
    "TechXpert ",
    "Do you really want to delete?",
    function () {
      $.post(
        "action/delete_state_gst.php",
        {
          ID: ID,
        },
        function (data, status) {
          var response = JSON.parse(data);
          TechXAlert(response.message);
          $('#view-state-gst').DataTable().ajax.reload();
        }
      );
    },
    function () {
      alertify.error("Deletion Cancelled");
    }
  );
}