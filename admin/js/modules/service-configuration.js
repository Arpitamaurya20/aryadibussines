function validISNumber(basic) {
  const input = event.target;
  let value = input.value;
  
  value = value.replace(/[^0-9.]/g, ''); // Remove non-numeric characters
  input.value = value;
}


function AddLaundryServiceConfig() {
  


  // var Service_id = document.getElementById("Service_id").value;
  // if (Service_id == '') {
  //   TechXAlert("Please Select Service");
  //   return false;
  // }

  // var Sub_Service_id = document.getElementById("Sub_Service_id").value;
  // if (Sub_Service_id == '') {
  //   TechXAlert("Please Select Sub Service");
  //   return false;
  // }

  var Type_of_clothes = document.getElementById("Type_of_clothes").value;
  if (Type_of_clothes == '') {
    TechXAlert("Please Enter Type Of Clothes");
    return false;
  }

  var Clothe_Price = document.getElementById("Clothe_Price").value;
  if (Clothe_Price == '') {
    TechXAlert("Please Enter Price");
    return false;
  }

  $("#luandry_service_btn").val("Please Wait...");
  $.ajax({
    url: "./action/luandry_services_modal_action.php",
    type: "POST",
    data: $("#luandryservice_form").serialize(),
    success: function (data) {
      var response = JSON.parse(data);

      TechXAlert(response.message);
      if (response.error == false) {
        setInterval(function () {
          location.reload();
      }, 1000);
        // jQuery("#luandryservice_form")[0].reset();
        // $("#luandryservice").close();
      }

      return false;
    },
  });
  return false;
}

function DeleteLaundryService(laundry_service_conf_id) {
  alertify.confirm(
    "TechXpert ",
    "Do you really want to delete Laundry Services Configuration?",
    function () {
      $.post(
        "action/delete_service_conf.php",
        {
          ID: laundry_service_conf_id,
        },
        function (data, status) {
          var response = JSON.parse(data);
          TechXAlert(response.message);
          if (response.error == false) {
            setInterval(function () {
              location.reload();
            }, 2000);
          }
        }
      );
    },
    function () {
      alertify.error("Deletion Cancelled");
    }
  );
}

function open_EditLaundryModal(Laundry_conf_id) {
  $.post("action/get_luandry_conf_details.php", {
      ID: Laundry_conf_id
  },
      function (data, status) {
          var response = JSON.parse(data);
          if (response.error == false) {
              $("#Type_of_clothes").val(response.data.TypeOfClothes);
              $("#Clothe_Price").val(response.data.Price);

              $("#laundry_form_id").val(Laundry_conf_id);
              $("#editlaundryservice").modal();
          }
      });

}


function DeleteRateCardPDF(sub_service_id) {
  alertify.confirm(
    "TechXpert ",
    "Do you really want to delete Rate Card?",
    function () {
      $.post(
        "action/delete_sub_service_rate_card.php",
        {
          ID: sub_service_id,
        },
        function (data, status) {
          var response = JSON.parse(data);
          TechXAlert(response.message);
          if (response.error == false) {
            setInterval(function () {
              location.reload();
            }, 2000);
          }
        }
      );
    },
    function () {
      alertify.error("Deletion Cancelled");
    }
  );
}