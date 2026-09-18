$(document).ready(function () {
  $("#nav_corporate").addClass("open");
  $("#nav_corporate").addClass("active");
  $("#nav_company").addClass("active");

  $("#view-company").dataTable({
    responsive: true,
  });

  $(".js-thead-colors a").on("click", function () {
    var theadColor = $(this).attr("data-bg");
    console.log(theadColor);
    $("#dt-basic-example thead").removeClassPrefix("bg-").addClass(theadColor);
  });

  $(".js-tbody-colors a").on("click", function () {
    var theadColor = $(this).attr("data-bg");
    console.log(theadColor);
    $("#dt-basic-example").removeClassPrefix("bg-").addClass(theadColor);
  });
});

function openCompany_modal() {
  $("#company_modal_title").html("Add Company Account Details");
  $("#add_update_company_form")[0].reset();
  $("#form_action").val("add");
  
  $("#account_manager").select2();
$("#corporate_industry").select2({ dropdownParent: $("#add_edit_company_modal") });
  $("#company_tendor").select2();
  $("#priority").select2();
  $("#corporate_name").select2();
  $("#corporate_password").css("display", "block");
  $("#company_po_wo_date").datepicker({
    format: "yyyy-mm-dd",
    todayBtn: "linked",
    clearBtn: true,
    todayHighlight: true,
    autoclose: true,
    startDate:'+0d',
  });
  $("#add_edit_company_modal").modal();
  // $("#amc_start_date").datepicker({
  //   format: "yyyy-mm-dd",
  //   todayBtn: "linked",
  //   clearBtn: true,
  //   todayHighlight: true,
  //   autoclose: true,
  // });
  // $("#amc_end_date").datepicker({
  //   format: "yyyy-mm-dd",
  //   todayBtn: "linked",
  //   clearBtn: true,
  //   todayHighlight: true,
  //   autoclose: true,
  // });
}

function UpdateCompany_modal(company_id) {
  $("#company_modal_title").html("Update Company Account Details");
  $("#corporate_password").css("display", "none");
  $.post("action/get_company_details.php",
    {
      ID: company_id,
    },
    function (data, status) {
      var response = JSON.parse(data);
      if (response.error == false) {
        var corporate_name = response.data.CorporateName;
        var company_name = response.data.CompanyName;
        var company_email = response.data.CompanyEmail;
        var company_phone = response.data.CompanyPhone;
        var company_mobile = response.data.CompanyMobile;
        var  priority=       response.data.Priority;
        var  corporate_industry= response.data.IndustryTypeName;
        //var company_branches = response.data.CompanyBranches;
        var company_tendor = response.data.CompanyTendor;
        let company_tendor_array = company_tendor.split(",");

        $("#company_tendor").val(company_tendor_array);
        $("#company_tendor").select2();
        //var company_tendor = response.data.CompanyTendor;
        // var company_sow = response.data.CompanySOW;
        var company_tat = response.data.CompanyTAT;
        var company_po_wo = response.data.CompanyPOWO;
        var company_po_wo_date = response.data.CompanyPOWODate;
        var admin_username = response.data.UserName;
        var TicketsNeedApproval = response.data.TicketsNeedApproval;
        var TicketNeedsWAMessage = response.data.TicketNeedsWAMessage;
        var AccountManager = response.data.AccountManager;
        $("#corporate_name").val(corporate_name);
        $("#company_name").val(company_name);
        $("#company_email").val(company_email);
        $("#company_phone").val(company_phone);
        $("#company_mobile").val(company_mobile);
        //$("#company_branches").val(company_branches);
        // $("#company_sow").val(company_sow);
        $("#company_tat").val(company_tat);
        $("#company_po_wo").val(company_po_wo);
        $("#company_po_wo_date").val(company_po_wo_date);
        $("#admin_username").val(admin_username);
        $("#form_action").val("Update");
        $("#form_id").val(company_id);
        $("#account_manager").val(AccountManager);
        $("#account_manager").select2();
        $("#corporate_name").select2();
        $("#priority").val(priority);
        $("#priority").select2();
        $("#corporate_industry").select2({ dropdownParent: $("#add_edit_company_modal") });
        $("#corporate_industry").val(corporate_industry);

       
        if(TicketsNeedApproval == 1)
        {
           $("#need_approval_by_company_admin").prop("checked", true);
        }
        else
        {
           $("#need_approval_by_company_admin").prop("checked", false);
        }
        if(TicketNeedsWAMessage == 1)
        {
           $("#need_wa_message").prop("checked", true);
        }
        else
        {
           $("#need_wa_message").prop("checked", false);
        }
        $("#additional_priorities").val(response.data.AdditionalPriorities);
        $("#company_po_wo_date").datepicker({
          format: "yyyy-mm-dd",
          todayBtn: "linked",
          clearBtn: true,
          todayHighlight: true,
          autoclose: true,
        });
         $("#add_edit_company_modal").modal();
      }
    }
  );
}
function DeleteCompany(company_id) {
  alertify.confirm(
    "TechXpert ",
    "Do you really want to delete Company?<br><br> Deleting Company will delete all the branches and all the data associated with the company.",
    function () {
      $.post(
        "action/delete_company.php",
        {
          ID: company_id,
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
function AddUpdateCompany() {
  // var AMCstartDate = document.getElementById("amc_start_date").value;
  // var AMCendDate = document.getElementById("amc_end_date").value;

  // if (AMCendDate <= AMCstartDate) {
  //   TechXAlert("End date must be greater than start date.");
  //   return false;
  // }
  var corporate_name = document.getElementById("corporate_name").value;
  if (corporate_name == -1) {
      TechXAlert("Please Select Companies");
      return false;
  }

  var company_name = document.getElementById("company_name").value;
  if (company_name == "") {
      TechXAlert("Please Enter Company Name");
      return false;
  }
  var company_email = document.getElementById("company_email").value;
  if (company_email == "") {
    TechXAlert("Please Enter Company Email");
    return false;
  }

  if (email_check(company_email) == false) {
    TechXAlert("Please Enter Valid Email");
    return false;
  }
  

  var phone_number = document.getElementById("company_phone").value;
  if (phone_number == "") {
    TechXAlert("Please Enter Phone Number");
    return false;
  }
  var company_tendor = document.getElementById("company_tendor").value;
  if (company_tendor == "") {
    TechXAlert("Please Select Tendor");
    return false;
  }

  // var admin_username = document.getElementById("admin_username").value;
  // if ((admin_username != "") && !validateUsername(admin_username)) {
  //     TechXAlert("Enter Valid Username");
  //     return false;
  // }



  let myForm = document.getElementById("add_update_company_form");
  var formData = new FormData(myForm);
  document.getElementById("company_btn").innerHTML ="Submiting....";
  $.ajax({
    url: "action/add_update_company.php",
    type: "POST",
    data: formData,
    success: function (data) {
      var response = JSON.parse(data);
      TechXAlert(response.message);
      if (response.error == false) {
        setInterval(function () {
          location.reload();
        }, 2000);
      }
      else
      {
        document.getElementById("company_btn").innerHTML ="Submit";
      }
    },
      cache: false,
      contentType: false,
      processData: false,
  });
  return false;
}

// function AMCSelecte() {
//   // let AMCselect = document.getElementById("company_tendor");
//   let AMCdate = document.getElementById("amc_start_date");
//   let Enddate = document.getElementById("amc_end_date");
//   let selectedOption = $("#company_tendor").val();
//   if (!selectedOption.includes("AMC")) {
//     AMCdate.disabled = true;
//     Enddate.disabled = true;
//   } else {
//     AMCdate.disabled = false;
//     Enddate.disabled = false;
//   }
// }

function ViewBranches(CompanyID)
{
  $.post(
        "../controllers/setSession.php", {
            CompanyID: CompanyID,
      },
      function(data, status) {
          BasicURLRouter("../branch/view-branch");
      }
  );
}

function ViewRateCard(CompanyID)
{
  $.post(
        "../controllers/setSession.php", {
            CompanyID: CompanyID,
      },
      function(data, status) {
          BasicURLRouter("view-rate-card");
      }
  );
}

function ViewStateGST(CompanyID)
{
  $.post(
        "../controllers/setSession.php", {
            CompanyID: CompanyID,
      },
      function(data, status) {
          BasicURLRouter("view-state-gst");
      }
  );
}




// function ResetPassword(){
//   $("#resetpassword").modal();
// }

function OpenResetPasswordModal(CorporateID)
{
  $("#reset_corporate_id").val(CorporateID);
  $("#resetpassword_modal").modal();
}

function OpenSetAccessModal(CorporateID,CorporatePhoneNumber)
{
  $("#set_access_corporate_id").val(CorporateID);
  $("#set_access_phonenumber").val(CorporatePhoneNumber);
  $("#set_access_modal").modal();
}

function SetAccess()
{
  var username = $("#set_access_username").val();
  var password = $("#set_access_password").val();
  if(username == "")
  {
    TechXAlert("Kindly enter Username");
    return false;
  }
  if(password == "")
  {
    TechXAlert("Kindly enter Password");
    return false;
  }
  $.ajax({
      url: "action/set_access.php",
      type: "POST",
      data: $("#set_access_form").serialize(),
      success: function (data) {
        var response = JSON.parse(data);
        if(response.error == true)
          TechXAlert(response.message);
        else
        {
          alert(response.message);
          $("#set_access_modal").modal("hide");
          location.reload();
        }
      },
    });

}

function ResetPassword() {
  var reset_passsword = $("#reset_passsword").val();
  var reset_confirm_passsword = $("#reset_confirm_passsword").val();
  if (reset_passsword == "" || reset_confirm_passsword == "") {
    TechXAlert("Kindly enter Password");
    return false;
  } else {
    if (reset_confirm_passsword != reset_passsword) {
      TechXAlert("Password doesn't match");
      return false;
    } else {
      $.ajax({
        url: "action/reset_password.php",
        type: "POST",
        data: $("#reset_password_form").serialize(),
        success: function (data) {
          var response = JSON.parse(data);
          alert(response.message);
          $("#resetpassword_modal").modal("hide");
        },
      });
    }
  }
}

function ActiveDeactiveChange(company_id, status) {
  
  alertify.confirm(
    "TechXpert ",
    `Do you really want Change Status of the Company Account?`,
    function () {
      $.post("action/set_active_deactive.php",
        {
          ID: company_id,
          status:status
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
      alertify.error("Action Cancelled");
    }
  );
}


function ExportCompanyData() {
  $.ajax({
      url: "action/export_company.php",
      type: "POST",
      data: $("#import_form").serialize(),
      success: function (data) {
          window.location.href = "report.xls";
      },
  });
  return false;
}

function DownloadCompanyDataAssetsFileFormat() {
  // Replace 'file_url' with the URL of the file you want to download
  var file_url = 'https://techxpertindia.in/admin/company/company-data-template.xlsx';
  
  // Create a new anchor element
  var link = document.createElement('a');
  
  // Set the href attribute to the file URL
  link.href = file_url;
  
  // Set the download attribute to the file name
  link.setAttribute('download', 'company-data-template.xlsx');
  
  // Simulate a click on the anchor element to initiate the download
  link.click();
}

function email_check(company_email) {
  var regex =
    /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
  if (!regex.test(company_email)) {
    return false;
  } else {
    return true;
  }
}

function validaphone(phone) {
  var regex = /^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/im;
  if (!regex.test(phone)) {
    return false;
  } else {
    return true;
  }
}

function validateUsername(username) {
  var pattern = /^[a-zA-Z][a-zA-Z0-9]{2,}$/;
  return pattern.test(username);
}