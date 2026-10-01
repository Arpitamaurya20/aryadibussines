$(document).ready(function() 
    {
        $("#nav_corporate").addClass("active");
        $("#nav_corporate").addClass("open");
        $("#nav_main_corporate").addClass("active");
        initCorporateDataTable();

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

    function initCorporateDataTable() {
        if ($.fn.dataTable && $.fn.dataTable.isDataTable('#view-corporate')) {
            $('#view-corporate').DataTable().destroy();
        }
        $('#view-corporate').DataTable({
            responsive: true,
            order: [[0, 'asc']],
            stateSave: true,
            stateDuration: -1,
            processing: true,
            ajax: {
                url: 'action/list_corporate.php',
                dataSrc: 'data'
            },
            columns: [
                { data: 0 },
                { data: 1 },
                { data: 2 },
                { data: 3 },
                { data: 4, orderable: false, searchable: false },
                { data: 5, orderable: false, searchable: false },
                { data: 6, orderable: false, searchable: false },
                { data: 7, orderable: false, searchable: false }
            ]
        });
    }

    function reloadCorporateList(options) {
        options = options || {};
        var goToFirstPage = !!options.goToFirstPage;
        var delay = options.delay !== undefined ? options.delay : 0;
        setTimeout(function() {
            if ($.fn.dataTable && $.fn.dataTable.isDataTable('#view-corporate')) {
                var table = $('#view-corporate').DataTable();
                // false = keep current page; true = reset to first page
                table.ajax.reload(null, goToFirstPage);
            } else {
                initCorporateDataTable();
            }
        }, delay);
    }
    function opencorporate_modal() {
        $("#add_update_corporate_form")[0].reset();
        $("#form_action").val("add");
        $("#form_id").val("-1");
        $("#corporate_heading").html("Add Company HQ");
        $("#corporate_password").attr("placeholder", "Enter Company Password");
        $("#company_btn").prop("disabled", false).html("Submit");
        $("#add_edit_corporate_modal").modal();
    }
    function UpdateCorporate_modal(corporate_id)
    {
        $("#corporate_heading").html("Update Company HQ");
        $("#corporate_username").val("");
        $("#corporate_password").val("");
        $("#corporate_password").attr("placeholder", "Leave blank to keep current password");
        $("#company_btn").prop("disabled", false).html("Submit");
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
                var loginUsername = response.data.LoginUsername || "";
                var hasLogin = parseInt(response.data.HasLogin || 0, 10) === 1;
                $("#corporate_name").val(corporate_name);
                $("#corporate_gst").val(corporate_gst);
                $("#corporate_address").val(corporate_address);
                $("#corporate_username").val(loginUsername);
                $("#corporate_password").val("");
                if (hasLogin) {
                    $("#corporate_password").attr("placeholder", "Leave blank to keep current password");
                } else {
                    $("#corporate_password").attr("placeholder", "Enter Company Password");
                }
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
                          reloadCorporateList({ delay: 0 });
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

        var corporate_username = document.getElementById("corporate_username").value.trim();
        var corporate_password = document.getElementById("corporate_password").value;
        var formAction = $("#form_action").val();

        if (formAction === "add") {
            if (corporate_username != "" && corporate_password == "") {
                TechXAlert("Please Enter Company Password");
                return false;
            }
            if (corporate_password != "" && corporate_username == "") {
                TechXAlert("Please Enter Company Username");
                return false;
            }
        } else {
            // Update: password optional if login already exists (username visible).
            var originalHadLogin = $("#corporate_password").attr("placeholder") === "Leave blank to keep current password";
            if (!originalHadLogin && corporate_username != "" && corporate_password == "") {
                TechXAlert("Please Enter Company Password to create login");
                return false;
            }
            if (corporate_password != "" && corporate_username == "") {
                TechXAlert("Please Enter Company Username");
                return false;
            }
        }
        
        document.getElementById("company_btn").innerHTML ="Submitting...";
        document.getElementById("company_btn").disabled = true;
        $.ajax({
            url: "action/add_update_corporate.php",
            type: "POST",
            data: $("#add_update_corporate_form").serialize(),
            success: function(data) {
                var response = JSON.parse(data);
                TechXAlert(response.message);
                document.getElementById("company_btn").disabled = false;
                document.getElementById("company_btn").innerHTML = "Submit";
                if (response.error == false)
                {
                  $("#add_edit_corporate_modal").modal("hide");
                  var isAdd = $("#form_action").val() === "add";
                  reloadCorporateList({ goToFirstPage: isAdd, delay: 0 });
                }
            },
            error: function() {
                document.getElementById("company_btn").disabled = false;
                document.getElementById("company_btn").innerHTML = "Submit";
                TechXAlert("Request failed. Please try again.");
            }
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
    setCorporateCsvClientError('');
    var fileInput = document.getElementById('csvFile');
    var nameEl = document.getElementById('csv_selected_name');
    var uploadBtn = document.getElementById('upload_csv_btn');
    if (fileInput) fileInput.value = '';
    if (nameEl) nameEl.textContent = 'No file selected';
    if (uploadBtn) {
        uploadBtn.disabled = false;
        uploadBtn.textContent = 'Upload';
    }
}

$(document).on('change', '#csvFile', function () {
    var nameEl = document.getElementById('csv_selected_name');
    var file = this.files && this.files[0] ? this.files[0] : null;
    setCorporateCsvClientError('');
    if (!nameEl) return;
    if (!file) {
        nameEl.textContent = 'No file selected';
        return;
    }
    var sizeKb = Math.max(1, Math.round(file.size / 1024));
    nameEl.textContent = file.name + ' (' + sizeKb + ' KB)';
});

$(document).on('dragover dragenter', '#csv_dropzone', function (e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).addClass('is-dragover');
});

$(document).on('dragleave drop', '#csv_dropzone', function (e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).removeClass('is-dragover');
});

$(document).on('drop', '#csv_dropzone', function (e) {
    var dt = e.originalEvent && e.originalEvent.dataTransfer;
    var fileInput = document.getElementById('csvFile');
    if (!dt || !dt.files || !dt.files.length || !fileInput) return;
    fileInput.files = dt.files;
    $(fileInput).trigger('change');
});

function showCorporateCsvUploadOverlay(message) {
    var existing = document.getElementById('corporate_csv_upload_overlay');
    if (existing) {
        existing.remove();
    }
    var overlay = document.createElement('div');
    overlay.id = 'corporate_csv_upload_overlay';
    overlay.innerHTML =
        '<div style="position:fixed;inset:0;background:rgba(15,23,42,.45);z-index:20000;display:flex;align-items:center;justify-content:center;">' +
        '  <div style="width:min(420px,92vw);background:#fff;border-radius:12px;box-shadow:0 20px 50px rgba(0,0,0,.25);padding:22px;font-family:Poppins,Segoe UI,sans-serif;text-align:center;">' +
        '    <div id="corporate_csv_upload_spinner" style="width:38px;height:38px;border-radius:50%;border:3px solid #dbeafe;border-top-color:#027dc1;animation:corporateCsvSpin .9s linear infinite;margin:0 auto 14px;"></div>' +
        '    <div id="corporate_csv_upload_title" style="font-weight:600;font-size:16px;color:#0f172a;margin-bottom:6px;">Uploading CSV</div>' +
        '    <div id="corporate_csv_upload_msg" style="font-size:13px;color:#64748b;">' + (message || 'Please wait...') + '</div>' +
        '  </div>' +
        '</div>' +
        '<style>@keyframes corporateCsvSpin{to{transform:rotate(360deg)}}</style>';
    document.body.appendChild(overlay);
}

function updateCorporateCsvUploadOverlay(title, message, isSuccess) {
    var titleEl = document.getElementById('corporate_csv_upload_title');
    var msgEl = document.getElementById('corporate_csv_upload_msg');
    var spinner = document.getElementById('corporate_csv_upload_spinner');
    if (titleEl) titleEl.textContent = title;
    if (msgEl) msgEl.textContent = message || '';
    if (isSuccess && spinner) {
        spinner.style.border = '3px solid #bbf7d0';
        spinner.style.borderTopColor = '#16a34a';
        spinner.style.animation = 'none';
        spinner.innerHTML = '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#16a34a;font-size:20px;font-weight:700;">✓</div>';
    }
}

function hideCorporateCsvUploadOverlay() {
    var existing = document.getElementById('corporate_csv_upload_overlay');
    if (existing) existing.remove();
}

function setCorporateCsvClientError(message) {
    var el = document.getElementById('csv_upload_client_error');
    if (!el) return;
    if (!message) {
        el.style.display = 'none';
        el.textContent = '';
        return;
    }
    el.style.display = 'block';
    el.textContent = message;
}

function UploadCompanyARC_CSV() {
    var MAX_SIZE_BYTES = 2 * 1024 * 1024; // 2 MB
    var myForm = document.getElementById('upload_company_arc_csv');
    var fileInput = document.getElementById('csvFile');
    var uploadBtn = document.getElementById('upload_csv_btn');

    setCorporateCsvClientError('');

    if (!myForm || !fileInput) {
        TechXAlert('Upload form not found. Please refresh the page.');
        return false;
    }

    if (!fileInput.files || !fileInput.files.length) {
        setCorporateCsvClientError('Please select a CSV file to upload.');
        TechXAlert('Please select a CSV file to upload.');
        return false;
    }

    var file = fileInput.files[0];
    var fileName = (file.name || '').toLowerCase();
    if (!fileName.endsWith('.csv')) {
        setCorporateCsvClientError('Only CSV files are allowed (.csv).');
        TechXAlert('Only CSV files are allowed (.csv).');
        return false;
    }

    if (file.size <= 0) {
        setCorporateCsvClientError('Selected file is empty.');
        TechXAlert('Selected file is empty.');
        return false;
    }

    if (file.size > MAX_SIZE_BYTES) {
        setCorporateCsvClientError('File is too large. Maximum allowed size is 2 MB.');
        TechXAlert('File is too large. Maximum allowed size is 2 MB.');
        return false;
    }

    var formData = new FormData(myForm);
    if (uploadBtn) {
        uploadBtn.disabled = true;
        uploadBtn.textContent = 'Uploading...';
    }

    $('#upload_company_csv').modal('hide');
    showCorporateCsvUploadOverlay('Validating and uploading your CSV...');

    $.ajax({
        url: 'action/importcsv.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        cache: false,
        contentType: false,
        processData: false,
        xhr: function () {
            var xhr = $.ajaxSettings.xhr();
            if (xhr.upload) {
                xhr.upload.addEventListener('progress', function (e) {
                    if (e.lengthComputable) {
                        var pct = Math.round((e.loaded / e.total) * 100);
                        updateCorporateCsvUploadOverlay('Uploading CSV', 'Upload progress: ' + pct + '%');
                    }
                }, false);
            }
            return xhr;
        },
        success: function (response) {
            if (typeof response === 'string') {
                try { response = JSON.parse(response); } catch (e) { response = { error: true, message: 'Invalid server response' }; }
            }
            if (response && response.error == false) {
                updateCorporateCsvUploadOverlay('Success', response.message || 'Bulk upload complete.', true);
                setTimeout(function () {
                    hideCorporateCsvUploadOverlay();
                    reloadCorporateList({ goToFirstPage: true, delay: 0 });
                }, 1800);
            } else {
                hideCorporateCsvUploadOverlay();
                TechXAlert((response && response.message) ? response.message : 'CSV upload failed.');
                if (uploadBtn) {
                    uploadBtn.disabled = false;
                    uploadBtn.textContent = 'Upload';
                }
                $('#upload_company_csv').modal('show');
            }
        },
        error: function (xhr) {
            hideCorporateCsvUploadOverlay();
            var msg = 'CSV upload failed. Please try again.';
            if (xhr && xhr.responseText) {
                try {
                    var parsed = JSON.parse(xhr.responseText);
                    if (parsed.message) msg = parsed.message;
                } catch (e) {}
            }
            TechXAlert(msg);
            if (uploadBtn) {
                uploadBtn.disabled = false;
                uploadBtn.textContent = 'Upload';
            }
            $('#upload_company_csv').modal('show');
        }
    });
    return false;
}

function DownloadCorporateFilesFormat() {
  var baseUrl = window.location.href.substring(0, window.location.href.lastIndexOf('/') + 1);
  var file_url = baseUrl + 'template/Corporate-format.csv';

  var link = document.createElement('a');
  link.href = file_url;
  link.setAttribute('download', 'Corporate-format.csv');
  link.setAttribute('target', '_blank');
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
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
          reloadCorporateList({ delay: 0 });
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

