$(document).ready(function () {
  $("#nav_corporate").addClass("open");
  $("#nav_corporate").addClass("active");
  $("#nav_branch").addClass("active");
  // $("#view-branch").dataTable({
  //   responsive: true,
  // });

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

let branchModalSelect2Init = false;

$('#add_edit_branch_modal').on('shown.bs.modal', function () {

  if (!branchModalSelect2Init) {

    $('#branch_company, #branch_city, #branch_state, #account_branch_manager').select2({
      dropdownParent: $('#add_edit_branch_modal'),
      width: '100%',
      placeholder: 'Search & Select'
    });

    branchModalSelect2Init = true;
  }
});

function openBranch_modal() {
  $("#branch_modal_title").html("Add Branch Account");
  $("#add_update_branch_form")[0].reset();
  $("#form_action").val("add");
  // $("#branch_company").select2();
  // $("#branch_city").select2();
  // $("#branch_state").select2();
  $("#account_branch_manager").select2();
  document.getElementById("city_div").style.display = "none";
  $("#add_edit_branch_modal").modal();
  $("#site_password").css("display", "block");

  $('#add_edit_branch_modal').on('hidden.bs.modal', function () {

  $('#branch_company, #branch_city, #branch_state, #account_branch_manager')
    .select2('destroy');

  branchModalSelect2Init = false;
});

}
function UpdateBranch_modal(branch_id,Access) {
  var user_access = document.getElementById("user_access").value;
  $("#branch_modal_title").html("Update Branch Account");
  $("#site_password").css("display", "none");
  // $("#site_password").style.display("none")
  $.post(
    "action/get_branch_details.php",
    {
      ID: branch_id,
    },
    function (data, status) {
      var response = JSON.parse(data);
      if (response.error == false) {
        var branch_company = response.data.CompanyID;
        var branch_name = response.data.BranchSite;
        var branch_email = response.data.BranchEmail;
        var branch_mobile = response.data.BranchMobile;
        var branch_landline = response.data.BranchLandline;
        var branch_address_1 = response.data.BranchAddress1;
        var branch_address_2 = response.data.BranchAddress2;
        var branch_city = response.data.BranchCity;
        var Latitude = response.data.Latitude;
        var Longitude = response.data.Longitude;
        var branch_state = response.data.BranchState;
        var branch_postal_code = response.data.BranchPostalCode;
        var branch_country = response.data.BranchCountry;
        var site_Incharge = response.data.SiteIncharge;
        var branch_code = response.data.BranchCode;
        var branch_username = response.data.UserName;
        var AccountBranchManager = response.data.AccountBranchManager;
        $("#branch_company").val(branch_company);
        $("#branch_name").val(branch_name);
        $("#branch_email").val(branch_email);
        $("#branch_mobile").val(branch_mobile);
        $("#branch_landline").val(branch_landline);
        $("#branch_address_1").val(branch_address_1);
        $("#branch_address_2").val(branch_address_2);
        $("#branch_city").val(branch_city);
        $("#branch_latitude").val(Latitude);
        $("#branch_longitude").val(Longitude);
        $("#branch_state").val(branch_state);
        $("#branch_postal_code").val(branch_postal_code);
        $("#branch_country").val(branch_country);
        $("#site_incharge").val(site_Incharge);
        $("#branch_code").val(branch_code);
        $("#branch_username").val(branch_username);
        $("#form_action").val("Update");
        $("#form_id").val(branch_id);
        $("#add_edit_branch_modal").modal();
        $("#branch_company").select2();
        $("#branch_city").select2();
        $("#branch_state").select2();
        $("#account_branch_manager").val(AccountBranchManager);
        if(user_access == "Admin" || user_access == "Ticket Manager")
        {
          $("#account_branch_manager").select2();
        }
        else
        {
              $("#account_branch_manager").select2();
              $('#account_branch_manager').on('select2:opening select2:closing select2:selecting select2:unselecting', function(e) {
                e.preventDefault();
              });
              // Add custom read-only style
              $('#account_branch_manager').next('.select2-container').addClass('select2-container--readonly');
        }
        $("#city_div").css("display","block");
      }
    }
  );
}
function DeleteBranch(branch_id) {
  alertify.confirm(
    "TechXpert ",
    "Do you really want to delete Branch?<br><br> Deleting Branch will delete all the branches Assets and all the data associated with the Branch.",
    function () {
      $.post(
        "action/delete_branch.php",
        {
          ID: branch_id,
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

function MergeBranch()
{
  var BranchID = $("#form_id").val();
  
  $.post("ajax/get_branches_of_company.php", {
        BranchID: BranchID
      },
      function(data, status) 
      {
          document.getElementById("company_branch_select_div").innerHTML = data;
          $("#to_be_merged_branch").select2();
          $("#merge_branch_modal").modal();
      }
  );
  return false;
}
function DisableBranchPPMTickets()
{
  var BranchID = $("#form_id").val();


  alertify.confirm(
    "TechXpert ",
    "Do you really want to disable all PPM tickets for all the assets of this branch?.",
    function () {
      $.post(
        "action/disable_branch_ppm_tickets.php",
        {
          BranchID: BranchID,
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

function MergeBranchAction()
{
  var current_branch_id = $("#form_id").val();
  var new_branch_id = $("#to_be_merged_branch").val();
  if(new_branch_id == -1)
  {
    TechXAlert("Kindly select Branch to be Merged");
    return false;
  }
  $.post("action/merge_branch_action.php", 
    {
          CurrentBranchID: current_branch_id,
          NewBranchID:new_branch_id
    },
    function(data, status) 
    {
        response = JSON.parse(data);
        TechXAlert(response.message);
        $("#merge_branch_modal").modal("hide");
        return false;
    }
  );
  return false;
}
function AddUpdateBranch() {
  // company validation

  let company_name = document.getElementById("branch_company").value;

  if (company_name === "-1") {
    TechXAlert("Please Select Company Name");
    return false;
  }

  //   branch site  validation
  let branch_site = document.getElementById("branch_name").value;

  if (branch_site === "") {
    TechXAlert("Please Fill Branch Name");
    return false;
  }

  //   email validation

  var email = document.getElementById("branch_email").value;

  if (email == "") {
    TechXAlert("Branch Contact Email cannot be blank");
    return false;
  }

  if (email_check(email) == false) {
    TechXAlert("Please Enter Valid Email");
    return false;
  }

  //   phone validation

  var phone = document.getElementById("branch_mobile").value;
  if (validaphone(phone) == false) {
    TechXAlert("Please Enter Valid Mobile Number");
    return false;
  }

  if (phone == "") {
    TechXAlert("Branch Mobile Number cannot be blank");
    return false;
  }


  // City Validation

  var branch_city = document.getElementById("branch_city").value;
  if (branch_city === "-1") {
    TechXAlert("Please Select City");
    return false;
  }

  // City Validation

  var branch_state = document.getElementById("branch_state").value;
  if (branch_state === "-1") {
    TechXAlert("Please Select State");
    return false;
  }


  // let branch_username = document.getElementById("branch_username").value;

  // if ((branch_username!= "") && !validateUsername(branch_username)) {
  //     TechXAlert("Enter Valid Username");
  //     return false;
  // }

  // if (branch_username) {
  //     let branch_password = document.getElementById("branch_passwords").value;
  //     if (branch_password == "") {
  //         TechXAlert("Please Enter The Password");
  //         return false;
  //     }
  // }
  




  document.getElementById("branch_btn").innerHTML ="Submiting....";

  $.ajax({
    url: "action/add_update_branch.php",
    type: "POST",
    data: $("#add_update_branch_form").serialize(),
    success: function (data) {
      var response = JSON.parse(data);
      TechXAlert(response.message);
      if (response.error == false) 
      {
        setInterval(function () {
          location.reload();
        }, 2000);
      }
      document.getElementById("branch_btn").innerHTML ="Submit";
    },
  });
  return false;
}

function email_check(customer_email) {
  var regex =
    /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
  if (!regex.test(customer_email)) {
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


function ViewBranchAssets(BranchID)
{
    $.post(
        "../controllers/setSession.php", {
            BranchID: BranchID,
      },
      function(data, status) {
          BasicURLRouter("../branch-assets/view-branch-assets");
      }
  );
}

function OpenResetPasswordModal(BranchID)
{
  $("#reset_password_username").val(BranchID);
  $("#reset_branch_password").modal();
}


function ResetPassword() {
  var reset_passsword = $("#reset_passsword").val();
  var reset_confirm_passsword = $("#reset_confirm_passsword").val();
  if (reset_passsword == "" || reset_confirm_passsword == "") {
    TechXAlert("Kindly enter password");
    return false;
  } else {
    if (reset_confirm_passsword != reset_passsword) {
      TechXAlert("Password doesn't match");
      return false;
    } else {
      document.getElementById("reset_password_btn").innerHTML ="Submiting....";
      $.ajax({
        url: "action/reset_password.php",
        type: "POST",
        data: $("#reset_password_form").serialize(),
        success: function (data) {
          var response = JSON.parse(data);
          alert(response.message);
          $("#reset_branch_password").modal("hide");
        },
      });
    }
  }
}

function DownloadBranchFileFormat() {
  // Replace 'file_url' with the URL of the file you want to download
  var file_url = 'https://techxpertindia.in/admin/branch/Branch-format.xlsx';
  
  // Create a new anchor element
  var link = document.createElement('a');
  
  // Set the href attribute to the file URL
  link.href = file_url;
  
  // Set the download attribute to the file name
  link.setAttribute('download', 'Branch-format.xlsx');
  
  // Simulate a click on the anchor element to initiate the download
  link.click();
}

function ExportBranchData() 
{
  $.ajax({
      url: "action/export_branch.php",
      type: "POST",
      data: $("#import_form").serialize(),
      success: function (data) {
          window.location.href = "report.xls";
      },
  });
  return false;
}

function SelectState() 
{
        var state_selected = $("#branch_state").val();
        if(state_selected == -1)
        {
          document.getElementById("city_div").style.display = "none";
        }
        else
        {
          $.post("action/get_city_by_state.php", {
                StateID: $("#branch_state").val()
            },
            function(data, status) {
                document.getElementById("city_div").style.display = "block";
                document.getElementById("branch_city").innerHTML = data;
            });
        }
}

function ViewARCItem(BranchID)
{
  $.post(
        "../controllers/setSession.php", {
            BranchID: BranchID,
      },
      function(data, status) {
          BasicURLRouter("../branch-arc-items/view-branch-arc-items");
      }
  );
}

function ViewSparePartItem(BranchID)
{
  $.post(
        "../controllers/setSession.php", {
            BranchID: BranchID,
      },
      function(data, status) {
          BasicURLRouter("../branch-spare-part/view-branch-spare-parts");
      }
  );
}

function validateUsername(username) {
  var pattern = /^[a-zA-Z][a-zA-Z0-9]{2,}$/;
  return pattern.test(username);
}

function OpenCSVmodal(){
       $("#upload_csv").modal();
}

function UploadBranch_CSV() {
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

function FilterBranches()
{
    
    var table = $('#view-branch').DataTable();
    table.destroy();
    var param = "";
    var nav = document.getElementById("nav").value;
    var CorporateID = document.getElementById("CorporateID").value;
    var CompanyID = document.getElementById("CompanyID").value;
    var BranchID = document.getElementById("BranchID").value;
    var UserType = document.getElementById("UserType").value;
    param = "nav="+nav+"&CorporateID="+CorporateID+"&CompanyID="+CompanyID+"&BranchID="+BranchID+"&UserType="+UserType;
    var cityObject = document.getElementById("cityName");
    if(cityObject !== null)
    {
        var cityName = document.getElementById("cityName").value;
        param = param+"&city="+cityName;
    }
    
    var stateObject = document.getElementById("stateName");
    if(stateObject !== null)
    {
        var stateName = document.getElementById("stateName").value;
        param = param+"&stateName="+stateName;
    }
    
    var companyaccountObject = document.getElementById("filter_company_id");
    if(companyaccountObject !== null)
    {
        var filter_company_id = document.getElementById("filter_company_id").value;
        param = param+"&filter_company_id="+filter_company_id;
    }

    var i = 1;
    $('#view-branch').dataTable({
         responsive: true,
        'processing': true,
        'serverSide': true,
        'ordering': false,
        'serverMethod': 'post',
        'ajax': {
            'url': 'include/branch-list-post.php?'+param
        },
        'columnDefs': [{
            "targets": [0],
            "className": "text-center"
        }],
        "order": [
            [1, 'asc']
        ],
        'columns': [{
                "data": "id",
                render: function(data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            {
                data: 'CompanyName'
            },
            {
                data: 'BranchName'
            },
            {
                data: 'Mobile_Alternate'
            },
            {
                data: 'City_State'
            },
            {
                data: 'Branch_Assets'
            },
            // {
            //     data: 'ViewARC'
            // },
            {
                data: 'ViewSparePart'
            },
            {
                data: 'Access'
            },
            {
                data: 'Update'
            },
            {
                data: 'Action'
            },
            {
                data: 'SiteIncharge'
            }


        ]


    });
}
function GetCitiesfromState(selection)
{
      $.ajax({
      url: "../corporate-tickets/ajax/view_cities_filter_div.php",
      type: "POST",
      data: 
      {
        "StateName":selection.value
      },
      success: function (data_response) {
          document.getElementById("branches_cities_div").innerHTML = data_response;
      },
  });
}