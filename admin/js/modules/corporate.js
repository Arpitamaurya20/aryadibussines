$(document).ready(function() 
    {
        $("#nav_corporate").addClass("active");
        $("#nav_corporate").addClass("open");
        $("#nav_main_corporate").addClass("active");
        $('#view-corporate').dataTable({
            responsive: true
        });

        $('.js-thead-colors a').on('click', function() {
            var theadColor = $(this).attr("data-bg");
            console.log(theadColor);
            $('#dt-basic-example thead').removeClassPrefix('bg-').addClass(theadColor);
        });

        $('.js-tbody-colors a').on('click', function() {
            var theadColor = $(this).attr("data-bg");
            console.log(theadColor);
            $('#dt-basic-example').removeClassPrefix('bg-').addClass(theadColor);
        });

    });
    function opencorporate_modal() {
        $("#add_update_corporate_form")[0].reset();
        $("#form_action").val("add");
        $("#corporate_heading").html("Add Company HQ");
        $("#add_edit_corporate_modal").modal();
    }
    function UpdateCorporate_modal(corporate_id)
    {
        $("#corporate_heading").html("Update Company HQ");
        $.post("action/get_corporate_details.php", {
            ID: corporate_id
        },
        function(data, status) {
            var response = JSON.parse(data);
            if(response.error == false)
            {
                var corporate_name = response.data.CorporateName;
                var corporate_gst = response.data.CorporateGST;
                var corporate_address = response.data.CoporateAddress;
                $("#corporate_name").val(corporate_name);
                $("#corporate_gst").val(corporate_gst);
                $("#corporate_address").val(corporate_address);
                $("#form_action").val("Update");
                $("#form_id").val(corporate_id);
            }
        });
        $("#add_edit_corporate_modal").modal();
    }
    function DeleteCorporate(corporate_id) {
        alertify.confirm('TechXpert ', 'Do you really want to delete Company', function() {
                $.post("action/delete_corporate.php", {
                        ID: corporate_id
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
    function AddUpdateCorporate() {

        var corporate_name = document.getElementById("corporate_name").value;

        if(corporate_name == ""){
            TechXAlert("Please Enter Company HQ Name");
            return false;
        }

        var corporate_gst = document.getElementById("corporate_gst").value;

        if(corporate_gst == ""){
            TechXAlert("Please Enter Company GST N0.");
            return false;
        }

        var corporate_username = document.getElementById("corporate_username").value;
        var corporate_password = document.getElementById("corporate_password").value;

        if(corporate_username != "" && corporate_password == ""){     
            TechXAlert("Please Enter Company Password");
        }
        
        document.getElementById("company_btn").innerHTML ="Submiting...";
        $.ajax({
            url: "action/add_update_corporate.php",
            type: "POST",
            data: $("#add_update_corporate_form").serialize(),
            success: function(data) {
                var response = JSON.parse(data);
                TechXAlert(response.message);
                if (response.error == false)
                {
                  setInterval(function(){
                    location.reload();
                  }, 2000);
                }
            },
        });
        return false;
    }

    function ViewCompanyAccounts(CorporateHQID)
    {
        $.post(
            "../controllers/setSession.php", {
                CorporateHQID: CorporateHQID,
          },
          function(data, status) {
              BasicURLRouter("../company/view-company");
          }
      );
    }


      function UploadCompanyCsv() {
    $("#upload_company_csv").modal();
}

function UploadCompanyARC_CSV() {
    let myForm = document.getElementById("upload_company_arc_csv"); 
    var formData = new FormData(myForm);
    $.ajax({
        url: "action/importcsv.php",
        type: "POST",
        data: formData,
        success: function (data) {
            var response = JSON.parse(data);
            TechXAlert(response.message);
            if (response.error == false) {
                setInterval(function() {
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

// function UploadCompanyARC_CSV(){
//     //alert('hello');
//         document.getElementById("upload_csv_btn").innerHTML ="Please Wait....";
//         $.ajax({
//             url: "action/importcsv.php",
//             type: "POST",
//             data: $("#uplaod_company_arc_csv").serialize(),
//             success: function(data) {
//                 var response = JSON.parse(data);
//                 TechXAlert(response.message);
//                 if (response.error == false)
//                 {
//                   setInterval(function(){
//                     location.reload();
//                   }, 2000);
//                 }
//             },
//         });
//         return false;
// }

function DownloadCorporateFileFormat() {
  // Replace 'file_url' with the URL of the file you want to download
  var file_url = 'http://localhost/Projects/techxpertindia/admin/corporate/Corporate-format.xlsx';
  
  // Create a new anchor element
  var link = document.createElement('a');
  
  // Set the href attribute to the file URL
  link.href = file_url;
  
  // Set the download attribute to the file name
  link.setAttribute('download', 'Corporate-format.xlsx');
  
  // Simulate a click on the anchor element to initiate the download
  link.click();
}



function OpenSetAccessModalCorporate(CompanyID)
{
  $("#set_access_company_id").val(CompanyID);
//   $("#set_access_phonenumber").val(CorporatePhoneNumber);
  $("#set_access_modal_company").modal();
}

function SetAccessCorporate()
{
  var username = $("#set_access_username_company").val();
  var password = $("#set_access_password_company").val();
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
      url: "action/set_access_corporate.php",
      type: "POST",
      data: $("#set_access_form_company").serialize(),
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

function OpenResetPasswordModalCorporate(CompanyID)
{
  $("#reset_company_id").val(CompanyID);
  $("#resetpassword_modal_company").modal();
}

function ResetPasswordCorporate() {
    var reset_passsword_company = $("#reset_passsword_company").val();
    var confirm_passsword_company = $("#confirm_passsword_company").val();
    if (reset_passsword_company == "" || confirm_passsword_company == "") {
      TechXAlert("Kindly enter Password");
      return false;
    } else {
      if (confirm_passsword_company != reset_passsword_company) {
        TechXAlert("Password doesn't match");
        return false;
      } else {
        $.ajax({
          url: "action/reset_password_company.php",
          type: "POST",
          data: $("#reset_password_form_company").serialize(),
          success: function (data) {
            var response = JSON.parse(data);
            alert(response.message);
            $("#resetpassword_modal_company").modal("hide");
          },
        });
      }
    }
  }

