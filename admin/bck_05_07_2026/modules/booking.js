function openBookingAssignmodal() {
  $("#assignemployee_dropdown").select2();
  $("#editassign_booking").modal();
}
function ChangeBookingStatus_Assignment() {
  // Then, get the selected value
  $.ajax({
    url: "action/change_booking_assignment.php",
    type: "POST",
    data: $("#booking_assignment_form").serialize(),
    success: function (data) {
      var response = JSON.parse(data);
      TechXAlert(response.message);
      if (response.error == false)
        setInterval(function () {
          location.reload();
        }, 2000);
    },
  });
}



function openhourlyDetails_modal() {
  $("#add_hourly_service_details").modal();
  $("#start_date").datepicker({
    format: "yyyy-mm-dd",
    todayBtn: "linked",
    clearBtn: true,
    todayHighlight: true,
    autoclose: true,
  });

  $("#end_date").datepicker({
    format: "yyyy-mm-dd",
    todayBtn: "linked",
    clearBtn: true,
    todayHighlight: true,
    autoclose: true,
  });
}

function AddHourlyDetailsTable() {

  let start_date = document.getElementById("start_date").value;

  if (start_date === "") {
    TechXAlert("Please Select Start Date");
    return false;
  }

  let end_date = document.getElementById("end_date").value;

  if (end_date === "") {
    TechXAlert("Please Select End Date");
    return false;
  }


  let start_time = document.getElementById("start_time").value;

  if (start_time === "") {
    TechXAlert("Please Select Start Time");
    return false;
  }


  let end_time = document.getElementById("end_time").value;

  if (end_time === "") {
    TechXAlert("Please Select End Time");
    return false;
  }


  // Then, get the selected value
  $.ajax({
    url: "action/add_hourly_service_details.php",
    type: "POST",
    data: $("#hourly_service_details_form").serialize(),
    success: function (data) {
      var response = JSON.parse(data);
      TechXAlert(response.message);
      if (response.error == false)
        setInterval(function () {
          location.reload();
        }, 2000);
    },
  });
}
