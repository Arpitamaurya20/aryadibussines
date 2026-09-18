
$(document).ready(function() {
    $("#nav_corporate_user").addClass("active");
    $('#view-corporate-users').dataTable({
        responsive: true
    });

    $('.js-thead-colors a').on('click', function() {
        var theadColor = $(this).attr("data-bg");
        console.log(theadColor);
        $('#view-corporate-users thead').removeClassPrefix('bg-').addClass(theadColor);
    });

    $('.js-tbody-colors a').on('click', function() {
        var theadColor = $(this).attr("data-bg");
        console.log(theadColor);
        $('#view-corporate-users').removeClassPrefix('bg-').addClass(theadColor);
    });
});

function OpenCorporateUserModal() {
    $("#password_col").css("display", "block");
    jQuery("#add_update_corporate_user_form")[0].reset();
    $("#corporate_user_modal").modal();
}

function AddUpdateCorporateUser()
{
    var corporate_user_name = document.getElementById("corporate_user_name").value;
    if (corporate_user_name === "") 
    {
        TechXAlert("Please Enter Name");
        return false;
    }
    var corporate_user_phonenumber = document.getElementById("corporate_user_phonenumber").value;
    if (valid_phone_check(corporate_user_phonenumber) == false) 
    {
        TechXAlert("Please Enter Valid Phonenumber");
        return false;
    }
    var corporate_user_email = document.getElementById("corporate_user_email").value;
    if (valid_email_check(corporate_user_email) == false) 
    {
        TechXAlert("Please Enter Valid User Email");
        return false;
    }
    
    // var corporate_user_password = document.getElementById("corporate_user_password").value;
    // if (corporate_user_password === "") 
    // {
    //     TechXAlert("Please Enter Valid User Password");
    //     return false;
    // }
    /*var corporate_user_approval_minimum = document.getElementById("corporate_user_approval_minimum").value;
    if (corporate_user_approval_minimum === "") 
    {
        TechXAlert("Please Enter Valid Minimum Approval amount");
        return false;
    }
    var corporate_user_approval_maximum = document.getElementById("corporate_user_approval_maximum").value;
    if (corporate_user_approval_maximum === "") 
    {
        TechXAlert("Please Enter Valid Maximum Approval amount");
        return false;
    }
    if(parseInt(corporate_user_approval_maximum) <= parseInt(corporate_user_approval_minimum))
    {
        TechXAlert("Maximum approval amount must be greater than minimum approval amount");
        return false;
    }*/
    document.getElementById("corporate_user_modal_submit_text").innerHTML = "Saving..";
    let myForm = document.getElementById("add_update_corporate_user_form");
    var formData = new FormData(myForm);
    $.ajax({
        url: "action/add_corporate_user_action.php",
        type: "POST",
        data: formData,
        async: false,
        success: function (data) {
        var response = JSON.parse(data);
        TechXAlert(response.message);
        if (response.error == false) 
        {
            //jQuery("#add_update_corporate_user_form")[0].reset();
            setTimeout(location.reload(), 2000);
        }
        else
        {
            document.getElementById("AddEmployeeButton").innerHTML = "Save";
        } 
    },
    cache: false,
    contentType: false,
    processData: false,
    });
}



function DeleteCorporateUser(UserID) {
  alertify.confirm(
    "TechXpert ",
    "Do you really want to delete Corporate User Details?",
    function () {
      $.post(
        "action/delete_corporate_user.php",
        {
          ID: UserID,
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


function UpdateCorporateUser(UserID) {
  $("#corporate_user_heading").html("Update Corporate User Details");
  $("#password_col").css("display", "none");
  $.post("action/get_corporate_user_details.php",
    {
      ID: UserID,
    },
    function (data, status) {
      var response = JSON.parse(data);
      if (response.error == false) {
        var corporate_user_name = response.data.Name;
        var corporate_user_phone = response.data.Phonenumber;
        var corporate_user_email = response.data.Email;
        var corporate_user_ApprovalMinRange = response.data.ApprovalMinRange;
        var corporate_user_ApprovalMaxRange = response.data.ApprovalMaxRange;
        $("#corporate_user_name").val(corporate_user_name);
        $("#corporate_user_phonenumber").val(corporate_user_phone);
        $("#corporate_user_email").val(corporate_user_email);
        $("#corporate_user_approval_minimum").val(corporate_user_ApprovalMinRange);
        $("#corporate_user_approval_maximum").val(corporate_user_ApprovalMaxRange);
        $("#corporate_user_modal_submit_text").html("Update");
        $("#form_action").val("Update");
        $("#form_id").val(UserID);
        $("#corporate_user_modal").modal();

      }
    }
  );
}

function ResetCorporatePass(UserID) {
    $("#reset_corporate_id").val(UserID);
    $("#resetpassword_modal").modal();

}


function ResetCorporateUserPassword() {
  var reset_passsword = $("#reset_corporate_passsword").val();
  var reset_confirm_passsword = $("#reset_corporate_confirm_passsword").val();
  if (reset_passsword == "" || reset_confirm_passsword == "") {
    TechXAlert("Kindly enter password");
    return false;
  } else {
    if (reset_confirm_passsword != reset_passsword) {
      TechXAlert("Password doesn't match");
      return false;
    } else {
      document.getElementById("corp_user_btn").innerHTML ="Please Wait....";
      $.ajax({
        url: "action/reset_password.php",
        type: "POST",
        data: $("#reset_corp_user_password_form").serialize(),
        success: function (data) {
          var response = JSON.parse(data);
          alertify.alert(response.message);
          $("#resetpassword_modal").modal("hide");
        },
      });
    }
  }
}
