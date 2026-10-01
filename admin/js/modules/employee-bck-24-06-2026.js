

function checkDates() 
{
      const dateFrom = document.getElementById('from_date');
      const dateTo = document.getElementById('to_date');
      const halfDayContainer = document.getElementById('half_day_container');
      if (dateFrom.value && dateTo.value) 
      {

          if (dateFrom.value === dateTo.value) {
              halfDayContainer.style.display = 'block';
          } else {
              halfDayContainer.style.display = 'none';
          }
      }
}
function AddEmployee() {
  var radioButton = document.querySelector('input[name="work_type"]:checked');
  if (!radioButton) {
    TechXAlert("Please Select Work Type");
    return false;
  }

  var radioButton = document.querySelector('input[name="gender_type"]:checked');
  if (!radioButton) {
    TechXAlert("Please Select Gender");
    return false;
  }

  var EmployeeName = document.getElementById("employee_name").value;
  if (EmployeeName == "") {
    TechXAlert("Employee Name cannot be blank");
    return false;
  }

  var Designation = document.getElementById("designation").value;
  if (Designation == "") {
    TechXAlert("Designation cannot be blank");
    return false;
  }

  var check_work_type = $('input[name="work_type"]:checked').val();;
  var EmployeeDepartment = document.getElementById("employee_department").value;
  if (check_work_type == "Employee") {
    if (EmployeeDepartment == "") {
      TechXAlert("Please Select Employee Department");
      return false;
    }
  }

 
  var EmployeeContact = document.getElementById("employee_contact").value;
  if (EmployeeContact == "") {
    TechXAlert("Employee Contact Number cannot be blank");
    return false;
  }

  var email = document.getElementById("employee_email").value;
  if (email_check(email) == false) {
    TechXAlert("Please Enter Valid Email");
    return false;
  }

  var phone = document.getElementById("employee_contact").value;
  if (validaphone(phone) == false) {
    TechXAlert("Please Enter Valid Phone Number");
    return false;
  }

  // var pan = document.getElementById("employee_pan_number").value;
  // if (pan != "") {
  //   if (validatePAN(pan) == false) {
  //     TechXAlert("Please Enter Valid PAN number");
  //     return false;
  //   }
  // }

  // var aadhaar = document.getElementById("employee_aadhar").value;
  // if (aadhaar != "") {
  //   if (validateAadhaar(aadhaar) == false) {
  //     TechXAlert("Please Enter Valid Aadhaar number");
  //     return false;
  //   }
  // }


    let myForm = document.getElementById("add_employee_form"); 
    var formData = new FormData(myForm);
    $.ajax({
        url: "action/add_employee_action.php",
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

function openUpdateLeavesModal(EmployeeID)
{
  $("#Leave_EmployeeID").val(EmployeeID);
  $("#employee_leave_modal").modal("show");
}

function UpdateEmployeeLeave()
{
  var EmployeeID = $("#Leave_EmployeeID").val();
  var leaves_allowed = $("#leaves_allowed").val();
  if(leaves_allowed < 0)
  {
    TechXAlert("Leaves Allowed can't be negative");
    return false;
  }
  $.post("action/update_employee_leaves.php",
    {
      EmployeeID: EmployeeID,
      leaves_allowed:leaves_allowed
    },
    function (data, status) 
    {
      var data_response = JSON.parse(data);
      TechXAlert(data_response.message);
      $("#employee_leave_modal").modal("hide");
      if(data_response.error == false)
      {
        document.getElementById("employee_leave_display_text").innerHTML = data_response.employee_allowed_leaves;
      }
    }
  );

}
  // document.getElementById("AddEmployeeButton").innerHTML = "Adding..";
  // let myForm = document.getElementById("add_employee_form");
  // var formData = new FormData(myForm);
  // $.ajax({
  //   url: "action/add_employee_action.php",
  //   type: "POST",
  //   data: formData,
  //   async: false,
  //   success: function (data) {
  //     var response = JSON.parse(data);
  //     TechXAlert(response.message);
  //     if (response.error == false) {
  //       jQuery("#add_employee_form")[0].reset();
  //       location.reload();
      //}
      // else{
      //   document.getElementById("AddEmployeeButton").innerHTML = "Add";
      // }
      
    //},
  //   cache: false,
  //   contentType: false,
  //   processData: false,
  //});
//}

function UpdateEmployee() {
  document.getElementById("update_basic_detail").innerHTML = "Updating..";
  let myForm = document.getElementById("update_employee_form");
  var formData = new FormData(myForm);
  $.ajax({
    url: "action/update_employee_details_action.php",
    type: "POST",
    data: formData,
    async: false,
    success: function (data) {
      var response = JSON.parse(data);
      TechXAlert(response.message);
      $("#editdetails").modal("hide");
      if (response.error == false)
        setInterval(function () {
          location.reload();
        }, 2000);
    },
    cache: false,
    contentType: false,
    processData: false,
  });
}

$(document).ready(function () {

    $('#editdetails').on('shown.bs.modal', function () {

        // City Select2
        $('#citydata').select2({
            dropdownParent: $('#editdetails'),
            width: '100%'
        });

        // State Select2
        $('#statedata').select2({
            dropdownParent: $('#editdetails'),
            width: '100%'
        });

        // Weekly Off Select2
        $('#weekly_off').select2({
            dropdownParent: $('#editdetails'),
            width: '100%'
        });

    });

});

function UpdateEmployeeSalary() {
  
  document.getElementById("update_salary").innerHTML ="Updating....";

  let myForm = document.getElementById("update_employee_salary_form");

  var formData = new FormData(myForm);
  $.ajax({
    url: "action/update_employee_salary_action.php",
    type: "POST",
    data: formData,
    async: false,
    success: function (data) {
      var response = JSON.parse(data);
      TechXAlert(response.message);
      $("#salarydetails").modal("hide");
        setInterval(function () {
          location.reload();
        }, 1000);
    },
    cache: false,
    contentType: false,
    processData: false,
  });
}


function ViewEmployee(EmployeeID) {
  $.post(
    "../controllers/setSession.php",
    {
      EmployeeID: EmployeeID,
    },
    function (data, status) {
      BasicURLRouter("view_employee");
    }
  );
}



function DeleteEmployee(EmployeeID, EmployeeName) {
  alertify.confirm(
    "TechXpert",
    "Are you sure you want to delete Employee " + EmployeeName + "?",
    function (e) {
      if (e) {
        $.post(
          "action/DeleteEmployee.php",
          {
            EmployeeID: EmployeeID,
          },
          function (data, status) {
            var response = JSON.parse(data);
            TechXAlert(response.message);
            if (response.error == false)
              setInterval(function () {
                location.reload();
              }, 2000);
          }
        );
      } else {
        // user clicked "cancel"
      }
    }
  );
}
// Access Page

function ChangePassword() {
  var access = $("#access_input").val();
  var username = $("#username").val();
  if (username == "") {
    TechXAlert("Kindly Enter Username");
    return false;
  }
  if (access == "Set") {
    var old_username = $("#old_username").val();
    if (old_username == username) {
      TechXAlert("Kindly change the username first");
      return false;
    }
  }
  if (access != "Set") {
    var password = $("#password").val();
    if (password == "") {
      TechXAlert("Kindly Enter Password");
      return false;
    }
  }
  document.getElementById("access_input").innerHTML = "Saving..";
  $.ajax({
    url: "action/change_password.php",
    type: "POST",
    data: $("#access_form").serialize(),
    success: function (data) {
      var response = JSON.parse(data);
      TechXAlert(response.message);
      if (response.error == false) location.reload();
    },
  });
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
      $.ajax({
        url: "action/reset_password.php",
        type: "POST",
        data: $("#reset_password_form").serialize(),
        success: function (data) {
          var response = JSON.parse(data);
          alert(response.message);
          $("#resetpassword").modal("hide");
        },
      });
    }
  }
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

// pan number validation

// function validatePAN(pan) {
//   var regex = /^[A-Z]{5}\d{4}[A-Z]{1}$/;
//   if (!regex.test(pan)) {
//     return false;
//   } else {
//     return true;
//   }
// }

// function validateAadhaar(aadhaar) {
//   var regex = /^\d{12}$/;
//   if (!regex.test(aadhaar)) {
//     return false;
//   } else {
//     return true;
//   }
// }
function ChangeRole_Supervisor() {
  document.getElementById("save_roll_btn").innerHTML ="Saving....";
  var selected_roles = $("#role_dropdown").val();
  var selected_divisions = $("#division_dropdown").val();
  var supervisor = $("#supervisor_dropdown").val();
  // Then, get the selected value
  $.ajax({
    url: "action/change_role_supervisor.php",
    type: "POST",
    data: $("#role_supervisor_form").serialize(),
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

//  multiple select js

function openrolemodal() {
  $("#supervisor_dropdown").select2();
  $("#role_dropdown").select2();
  $("#division_dropdown").select2();
  $("#editrole").modal();
}

function RemoveAccountBranchManagerRole(EmployeeID)
{
  $.post("action/removerole.php",
    {
      EmployeeID: EmployeeID,
      Role: "Branch Account Manager"
    },
    function (data, status) 
    {
      var response = JSON.parse(data);
      TechXAlert(response.message);
    }
  );
}

function opendetailmodal() {

  $("#edit_date_of_joining").datepicker({
    format: "yyyy-mm-dd",
    todayBtn: "linked",
    clearBtn: true,
    todayHighlight: true,
    autoclose: true,
  });
  var city_value = $("#city_Select_value").val();
  var mySelect2 = $("#citydata");
  mySelect2.val(city_value).trigger("change");
  var weekly_off = $("#temp_weekly_off").val();
  mySelect2 = $("#weekly_off");
  mySelect2.val(weekly_off).trigger("change");

  $("#editdetails").modal();
  $("#citydata").select2();
  $("#weekly_off").select2();
  var vendorcheck = document.getElementById("division");
  var selectedOption = vendorcheck.options[vendorcheck.selectedIndex];
  var divisionvalue = selectedOption.value;

  if (divisionvalue == "Vendor") {
    document.getElementById("division").disabled = true;
    document.getElementById("employee_department").disabled = true;
    document.getElementById("division").style.backgroundColor = "#dddddd";
  } else {
    document.getElementById("division").disabled = false;
    document.getElementById("employee_department").disabled = false;
  }
}

function opensalarymodal() {
  $("#salarydetails").modal();
}

function EnableDisableDivision(work_type) {
  if (work_type == "Vendor") {
    // document.getElementById("division").disabled = true;
    // document.getElementById("division_textbox").disabled = false;
    // document.getElementById("division").style.display = "none";
    // document.getElementById("division_textbox").style.display = "";

    document.getElementById("employee_department").disabled = true;
    document.getElementById("department_textbox").disabled = false;
    document.getElementById("employee_department").style.display = "none";
    document.getElementById("department_textbox").style.display = "";
  } else {
    // document.getElementById("division").disabled = false;
    // document.getElementById("division_textbox").disabled = true;
    // document.getElementById("division").style.display = "";
    // document.getElementById("division_textbox").style.display = "none";

    document.getElementById("employee_department").disabled = false;
    document.getElementById("department_textbox").disabled = true;
    document.getElementById("employee_department").style.display = "";
    document.getElementById("department_textbox").style.display = "none";
  }
}


function AddEmployeeMonthlySalary() {
  let myForm = document.getElementById("update_employee_monthly_salary_form");
  $.post("action/add_employee_monthly_salary_action.php", {
  ID: $("#monthly_employee_id").val(),
  year: $("#salaryYear").val(),
  month: $("#salaryMonth").val()
});
}

function getEmployeeSalary() {
  $.post("action/get_employee_monthly_salary.php", 
  {
    ID: $("#monthly_employee_id").val(),
    Year: $("#salaryYear").val(),
    Month: $("#salaryMonth").val()
  },
  function(data) 
  {
    if(data=="false")
    {
      document.getElementById("view-salary").style.display = "none";
      document.getElementById("edit_button").style.display = "none";
      document.getElementById("generate_button").style.display = "block";
      document.getElementById("salary_text").style.display = "block";
    }
    else
    {
      document.getElementById("view-salary").style.display = "block";
      document.getElementById("view_month_salary").innerHTML = data;
      document.getElementById("edit_button").style.display = "block";
      document.getElementById("generate_button").style.display = "none";
      document.getElementById("salary_text").style.display = "none";
    }
  });
};

function openMonthlysalarymodal() {
  $("#monthlysalarydetails").modal();
}

function UpdateEmployeeMonthlySalary() {
  let myForm = document.getElementById("update_employee_monthly_salary_form");
  var formData = new FormData(myForm);
  $.ajax({
    url: "action/update_employee_monthly_salary_action.php",
    type: "POST",
    data: formData,
    async: false,
    success: function (data) {
      var response = JSON.parse(data);
    },
    cache: false,
    contentType: false,
    processData: false,
  });
}

function salaryMonth() {
  $.post("action/get_employee_monthly_salary.php", {
    ID: $("#monthly_employee_id").val(),
    Year: $("#salaryYear").val(),
    Month: $("#salaryMonth").val()
},

function(data) {
    document.getElementById("view-salary").style.display = "block";
    document.getElementById("view_month_salary").innerHTML = data;
    if(data){
      $("#generate_button").css("display", "none");
    }
});
};

function validISNumber(basic) {
   const input = event.target;
  let value = input.value;
  
  value = value.replace(/[^0-9.]/g, ''); // Remove non-numeric characters
  
  input.value = value;
}




function ExportEmployeeData() {
  $.ajax({
      url: "action/export_employee.php",
      type: "POST",
      data: $("#import_form").serialize(),
      success: function (data) {
          window.location.href = "report.xls";
      },
  });
  return false;
}

function DownloadEmployeeDataAssetsFileFormat() {
  // Replace 'file_url' with the URL of the file you want to download
  var file_url = 'https://techxpertindia.in/admin/branch/Employee-Template.xlsx';

  
  // Create a new anchor element
  var link = document.createElement('a');
  
  // Set the href attribute to the file URL
  link.href = file_url;
  
  // Set the download attribute to the file name
  link.setAttribute('download', 'Employee-Template.xlsx');
  
  // Simulate a click on the anchor element to initiate the download
  link.click();
}

function ViewEmployeeDetail(EmployeeID) {
  $.post(
    "../controllers/setSession.php",
    {
      EmployeeID: EmployeeID,
    },
    function (data, status) {
      BasicURLRouter("view_employee_detail");
    }
  );
}

// --------Employee Leave start-------------

function OpenLeave_modal() {
    $("#add_update_leave_form")[0].reset();
    $("#form_action").val("add");

    $("#add_edit_leave_modal").modal();
     $("#from_date").datepicker({
            format: "yyyy-mm-dd",
            todayBtn: "linked",
            clearBtn: true,
            todayHighlight: true,
            autoclose: true,
      });
     $("#to_date").datepicker({
            format: "yyyy-mm-dd",
            todayBtn: "linked",
            clearBtn: true,
            todayHighlight: true,
            autoclose: true,
      });

    $("#type_of_leave").select2();
    $("#reason_of_leave").select2();

}

function UpdateLeave_modal(leave_id)
{
    $.post("action/get_employee_leave.php", {
        ID: leave_id
    },
    function(data, status) {
        var response = JSON.parse(data);
        if(response.error == false)
        {
            var TypeOfLeave = response.data.TypeOfLeave;
            var ReasonOfLeave = response.data.ReasonOfLeave;
            var FromDate = response.data.FromDate;
            var ToDate = response.data.ToDate;
            $("#type_of_leave").val(TypeOfLeave);
            $("#reason_of_leave").val(ReasonOfLeave);
            $("#from_date").val(FromDate);
            $("#to_date").val(ToDate);
            $("#form_action").val("Update");
            $("#form_id").val(leave_id);
            $("#type_of_leave").select2();
            $("#reason_of_leave").select2();

        }
    });
    $("#add_edit_leave_modal").modal();
}

 function AddUpdateLeave() {

        var ReasonOfLeave = document.getElementById("leave_reason").value;
        var FromDate = document.getElementById("from_date").value;
        var ToDate = document.getElementById("to_date").value;
        
        if (ReasonOfLeave == "") {
            TechXAlert("Reason cannot be blank");
            return false;
        }
        if (FromDate == "") {
            TechXAlert("From Date cannot be blank");
            return false;
        }
         if (ToDate == "") {
            TechXAlert("To Date cannot be blank");
            return false;
        }
        // document.getElementById("submit").innerHTML ="Submiting....";
        $.ajax({
            url: "action/add_update_employee_leave.php",
            type: "POST",
            data: $("#add_update_leave_form").serialize(),
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

function DeleteLeave(leave_id,Status) 
{
    if(Status == "Approved" || Status == "Rejected" || Status == "SupervisorApproved")
    {
      TechXAlert("Approved, rejected, or supervisor-approved leave can't be deleted");
      return false;
    }
    alertify.confirm('TechXpert ', 'Do you really want to delete Leave', function() {
            $.post("action/delete_employee_leave.php", {
                    ID: leave_id
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

function ApproveLeave(leave_id) {
    alertify.confirm('TechXpert ', 'Do you really want to Approve Leave', function() {
            $.post("action/approve_employee_leave.php", {
                  ID: leave_id
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
            alertify.error('Approve Cancelled')
        });
}

function GenerateEmployeeAttendanceDetails(EmployeeID)
{
  var current_year = $("#attendance_select_year").val();
  var current_month = $("#attendance_select_month").val();
  $.post("ajax/employee-attendance-view.php", 
  {
      EmployeeID: EmployeeID,
      s_year: current_year,
      s_month: current_month
  },
  function(data, status) 
  {
      $("#employee_attendance_table").html(data);
  });
}

function ViewAttendanceImage(imageUrl)
{
  document.getElementById('modalImage').src = "../media/employee_attendance/"+imageUrl;
    
    // Show the modal
    var imageModal = new bootstrap.Modal(document.getElementById('imageModal'), {
      keyboard: true
    });
    imageModal.show();
}


