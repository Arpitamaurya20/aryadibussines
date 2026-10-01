
function buildViewCorporateTicketsColumns(UserType)
{
    var columns = [];

    if (typeof rmvCanBulk !== 'undefined' && rmvCanBulk) {
        columns.push({
            data: 'RM_Select',
            orderable: false,
            searchable: false
        });
    }

    columns.push({
        data: 'id',
        render: function(data, type, row, meta) {
            return meta.row + meta.settings._iDisplayStart + 1;
        }
    }, {
        data: 'TicketID'
    }, {
        data: 'ClientTicketID'
    }, {
        data: 'Corporate_Branch'
    }, {
        data: 'Type'
    }, {
        data: 'Message'
    }, {
        data: 'Date_Time'
    }, {
        data: 'Status'
    });

    if (typeof bamCanAccess !== 'undefined' && bamCanAccess) {
        columns.push({
            data: 'BAM_Verification'
        });
    } else if (typeof smvCanAccess !== 'undefined' && smvCanAccess && typeof stateCanAccess !== 'undefined' && !stateCanAccess) {
        columns.push({
            data: 'BAM_Verification'
        });
    }

    if (typeof stateCanAccess !== 'undefined' && stateCanAccess) {
        columns.push({
            data: 'State_Verification'
        });
    }

    if (typeof rmvCanAccess !== 'undefined' && rmvCanAccess) {
        columns.push({
            data: 'RM_Verification'
        });
    }

    columns.push({
        data: 'View_Details'
    });

    if (UserType == "Admin" || UserType == "Ticket Manager") {
        columns.push({
            data: 'Delete'
        });
    }

    return columns;
}

function getViewCorporateTicketsColumnDefs()
{
    var centerTargets = [0];
    if (typeof rmvCanBulk !== 'undefined' && rmvCanBulk) {
        centerTargets = [0, 1];
    }
    return [{
        targets: centerTargets,
        className: 'text-center'
    }];
}

function initViewCorporateTicketsDataTable(UserType, ajaxUrl)
{
    $('#view-corporate-tickets').dataTable({
        responsive: true,
        'processing': true,
        'serverSide': true,
        'ordering': false,
        'serverMethod': 'post',
        'language': {
            'infoFiltered': ''
        },
        'ajax': {
            'url': ajaxUrl
        },
        'columnDefs': getViewCorporateTicketsColumnDefs(),
        'columns': buildViewCorporateTicketsColumns(UserType)
    });
}

function FilterTickets(UserType)
{

    var table = $('#view-corporate-tickets').DataTable();
    table.destroy();
    var param = "";
    var EmployeeID = document.getElementById("EmployeeID").value;
    var filter_date = document.getElementById("filter_date").value;
    param = "?filter_date=" + encodeURIComponent(filter_date) + "&EmployeeID=" + encodeURIComponent(EmployeeID);
    if (typeof cityLead !== 'undefined') {
        param += "&CityLead=" + encodeURIComponent(cityLead);
    }
    var cityObject = document.getElementById("cityName");
    if(cityObject !== null)
    {
        var cityName = document.getElementById("cityName").value;
        param = param+"&city="+cityName;
    }
    var branchObject = document.getElementById("branch_name");
    if(branchObject !== null)
    {
        var branchName = document.getElementById("branch_name").value;
        param = param+"&branchName="+branchName;
    }
    var stateObject = document.getElementById("stateName");
    if(stateObject !== null)
    {
        var stateName = document.getElementById("stateName").value;
        param = param+"&stateName="+stateName;
    }
    var statusObject = document.getElementById("ticket_status");
    if(statusObject !== null)
    {
        var status = document.getElementById("ticket_status").value;
        param = param+"&status="+encodeURIComponent(status);
    }
    var companyaccountObject = document.getElementById("filter_company_id");
    if(companyaccountObject !== null)
    {
        var filter_company_id = document.getElementById("filter_company_id").value;
        param = param+"&filter_company_id="+filter_company_id;
    }

    if (document.getElementById("finance_not_placed"))
    {
        var filter_cost_not_placed = document.getElementById("finance_not_placed").checked;
        if(filter_cost_not_placed)
        {
            param = param+"&finance_not_placed=yes"; 
        }
    }


    var categoryObject = document.getElementById("categories");
    if(categoryObject !== null)
    {
        var category_id = document.getElementById("categories").value;
        param = param + "&category_id=" + category_id;
    }

    initViewCorporateTicketsDataTable(UserType, 'ajax/view-corporate-tickets-post.php' + param);
}
function FilterAccountTickets()
{

    var table = $('#view-corporate-tickets').DataTable();
    table.destroy();
    var param = "";
    var EmployeeID = document.getElementById("EmployeeID").value;
    var filter_date = document.getElementById("filter_date").value;
    param = "?filter_date=" + encodeURIComponent(filter_date) + "&EmployeeID=" + encodeURIComponent(EmployeeID);
    var branchObject = document.getElementById("branch_name");
    if(branchObject !== null)
    {
        var branchName = document.getElementById("branch_name").value;
        param = param+"&branchName="+branchName;
    }
    var statusObject = document.getElementById("ticket_status");
    if(statusObject !== null)
    {
        var status = document.getElementById("ticket_status").value;
        param = param+"&status="+encodeURIComponent(status);
    }
    var companyaccountObject = document.getElementById("filter_company_id");
    if(companyaccountObject !== null)
    {
        var filter_company_id = document.getElementById("filter_company_id").value;
        param = param+"&filter_company_id="+filter_company_id;
    }

    $('#view-corporate-tickets').dataTable({
        responsive: true,
        'processing': true,
        'serverSide': true,
        'ordering': false,
        'serverMethod': 'post',
        'ajax': {
            'url': 'ajax/view-account-tickets-post.php'+param
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
                data: 'Corporate_Branch'
            },
            {
                data: 'Branch_City'
            },
            {
                data: 'Type'
            },
            {
                data: 'Message'
            },
            {
                data: 'Date_Time'
            },
            {
                data: 'Status'
            },
            {
                data: 'View_Details'
            }
        ]


    });
}
function getAccountBranchTicketColumns()
{
    var columns = [{
            data: 'id',
            render: function(data, type, row, meta) {
                return meta.row + meta.settings._iDisplayStart + 1;
            }
        },
        { data: 'TicketID' },
        { data: 'Corporate_Branch' },
        { data: 'Branch_City' },
        { data: 'Type' },
        { data: 'Message' },
        { data: 'Date_Time' },
        { data: 'Status' }
    ];
    if (typeof smvCanAccess !== 'undefined' && smvCanAccess) {
        columns.push({ data: 'SM_Verification' });
    }
    columns.push({ data: 'View_Details' });
    return columns;
}

function FilterAccountBranchTickets()
{
    var param = '';
    var EmployeeID = document.getElementById('EmployeeID').value;
    var filter_date = document.getElementById('filter_date').value;
    param = '?filter_date=' + encodeURIComponent(filter_date) + '&EmployeeID=' + encodeURIComponent(EmployeeID);
    var branchObject = document.getElementById('branch_name');
    if (branchObject !== null) {
        param += '&branchName=' + encodeURIComponent(branchObject.value);
    }
    var statusObject = document.getElementById('ticket_status');
    if (statusObject !== null) {
        param += '&status=' + encodeURIComponent(statusObject.value);
    }
    var companyaccountObject = document.getElementById('filter_company_id');
    if (companyaccountObject !== null) {
        param += '&filter_company_id=' + encodeURIComponent(companyaccountObject.value);
    }

    var ajaxUrl = 'ajax/view-account-branch-tickets-post.php' + param;
    var tableSelector = '#view-corporate-tickets';

    // Reload the existing table. Destroying a responsive DataTable throws
    // "Cannot read properties of null (reading 'parentNode')".
    if ($.fn.DataTable.isDataTable(tableSelector)) {
        $(tableSelector).DataTable().ajax.url(ajaxUrl).load();
        return;
    }

    $(tableSelector).dataTable({
        responsive: true,
        processing: true,
        serverSide: true,
        ordering: false,
        serverMethod: 'post',
        ajax: {
            url: ajaxUrl
        },
        columnDefs: [{
            targets: [0],
            className: 'text-center'
        }],
        columns: getAccountBranchTicketColumns()
    });
}
function FilterFinanceTickets()
{
    
    var table = $('#view-corporate-tickets-finance').DataTable();
    table.destroy();
    var param = "";
    var EmployeeID = document.getElementById("EmployeeID").value;
    var filter_date = document.getElementById("filter_date").value;
    param = "?filter_date=" + encodeURIComponent(filter_date) + "&EmployeeID=" + encodeURIComponent(EmployeeID);
    var cityObject = document.getElementById("cityName");
    if(cityObject !== null)
    {
        var cityName = document.getElementById("cityName").value;
        param = param+"&city="+cityName;
    }
    var branchObject = document.getElementById("branch_name");
    if(branchObject !== null)
    {
        var branchName = document.getElementById("branch_name").value;
        param = param+"&branchName="+branchName;
    }
    var stateObject = document.getElementById("stateName");
    if(stateObject !== null)
    {
        var stateName = document.getElementById("stateName").value;
        param = param+"&stateName="+stateName;
    }
    var statusObject = document.getElementById("ticket_status");
    if(statusObject !== null)
    {
        var status = document.getElementById("ticket_status").value;
        param = param+"&status="+encodeURIComponent(status);
    }
    var companyaccountObject = document.getElementById("filter_company_id");
    if(companyaccountObject !== null)
    {
        var filter_company_id = document.getElementById("filter_company_id").value;
        param = param+"&filter_company_id="+filter_company_id;
    }
    var techx_admin = $("#techx_admin").val();
    param = param+"&techx_admin="+techx_admin;

    var i = 1;
        var columns = [{
        "data": "id",
        render: function(data, type, row, meta) {
            return meta.row + meta.settings._iDisplayStart + 1;
        }
        },
        {
            data: 'TicketID'
        },
        {
            data: 'Corporate_Branch'
        },
        {
            data: 'Branch_City'
        },
        {
            data: 'Type'
        },
        {
            data: 'Message'
        },
        {
            data: 'Date_Time'
        },
        {
            data: 'Status'
        },
        {
            data: 'Prices'
        },
        {
            data: 'View_Details'
        }
    ];

    // Conditionally add the FinanceStatus column if techx_admin is true
    if (techx_admin) {
        columns.splice(8, 0, { // Insert FinanceStatus at the 9th position
            data: 'FinanceStatus'
        });
    }

    $('#view-corporate-tickets-finance').dataTable({
        responsive: true,
        'processing': true,
        'serverSide': true,
        'ordering': false,
        'serverMethod': 'post',
        'ajax': {
            'url': 'ajax/view-corporate-tickets-finance-post.php'+param
        },
        'columnDefs': [{
            "targets": [0],
            "className": "text-center"
        }],
        'columns': columns
    });
    if(techx_admin == true)
    {
        GenerateTicketFinanceDashboard();
    }
}
function DeleteCustomerDetail(deleteid) {

    //alert(deleteid);
    alertify.confirm('TechXpert ', 'Do you really want to delete Corporate Ticket', function() {
            $.post("action/delete-corporate-tickets.php", {
                    ID: deleteid
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

function DeleteTicket(deleteid) {

    //alert(deleteid);
    alertify.confirm('TechXpert ', 'Do you really want to delete Corporate Ticket', function() {
            $.post("action/delete-corporate-tickets.php", {
                    ID: deleteid
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

  function SelectService() {

        $.post("action/get_subservices.php", {
                ServiveID: $("#service_name").val()
            },
            function(data, status) {
                document.getElementById("sub_services_div").style.display = "block";
                document.getElementById("sub_service_name").innerHTML = data;
            });
    }
    function GetSubCategories() 
    {
        var selectElement = document.getElementById('service_name');
        var selectedOption = selectElement.options[selectElement.selectedIndex];
        var serviceId = selectedOption.getAttribute('data-id');
          $.post("action/get_subcategories.php", {
                  CategoryID: serviceId
              },
              function(data, status) {
                  document.getElementById("sub_services_div").style.display = "block";
                  document.getElementById("sub_service_name").innerHTML = data;
                  $("#sub_service_name").select2();
              });
    }

function ExportCorporateTicketData() 
{
    document.getElementById("filter_date_export").value = document.getElementById("filter_date").value;
    if (document.getElementById("cityName")) 
    {
        document.getElementById("city_export").value = document.getElementById("cityName").value;
    }
    if (document.getElementById("branch_name")) 
    {
      document.getElementById("branch_export").value = document.getElementById("branch_name").value;
    }
    if (document.getElementById("filter_company_id")) 
    {
      document.getElementById("company_account_export").value = document.getElementById("filter_company_id").value;
    }
    if (document.getElementById("stateName")) 
    {
      document.getElementById("state_export").value = document.getElementById("stateName").value;
    }
    if (document.getElementById("ticket_status")) 
    {
      document.getElementById("status_export").value = document.getElementById("ticket_status").value;
    }
    if (document.getElementById("finance_not_placed")) {
        var finance_not_placed = document.getElementById('finance_not_placed');
        if(finance_not_placed.checked)
        {
            document.getElementById("export_finance_not_placed").value = 1;
        }
    }

    if (document.getElementById("categories")) 
        {
            document.getElementById("category_export").value =
                document.getElementById("categories").value;
        }
    var exportButton = document.getElementById('export_button');

        // Disable the button and change the text
    exportButton.disabled = true;
    exportButton.textContent = 'Exporting...';
    $.ajax({
      url: "action/export_corporate_tickets.php",
      type: "POST",
      data: $("#export_form").serialize(),
      success: function (data) {
          window.location.href = "report.xls";
          exportButton.disabled = false;
          exportButton.textContent = 'Export Data';
      },
    });
    return false;
}
function ExportCorporateTicketsFinanceData() 
{
  // Check and assign value for filter_date_export
    if (document.getElementById("filter_date") && document.getElementById("filter_date").value) {
        document.getElementById("filter_date_export").value = document.getElementById("filter_date").value;
    }

    // Check and assign value for city_export
    if (document.getElementById("cityName") && document.getElementById("cityName").value) {
        document.getElementById("city_export").value = document.getElementById("cityName").value;
    }

    // Check and assign value for branch_export
    if (document.getElementById("branch_name") && document.getElementById("branch_name").value) {
        document.getElementById("branch_export").value = document.getElementById("branch_name").value;
    }

    // Check and assign value for company_account_export
    if (document.getElementById("filter_company_id") && document.getElementById("filter_company_id").value) {
        document.getElementById("company_account_export").value = document.getElementById("filter_company_id").value;
    }

    // Check and assign value for state_export
    if (document.getElementById("stateName") && document.getElementById("stateName").value) {
        document.getElementById("state_export").value = document.getElementById("stateName").value;
    }

    // Check and assign value for status_export
    if (document.getElementById("ticket_status") && document.getElementById("ticket_status").value) {
        document.getElementById("status_export").value = document.getElementById("ticket_status").value;
    }

  document.getElementById("export_data_button").innerHTML = "Exporting..";
  $.ajax({
      url: "action/export_corporate_tickets_finance.php",
      type: "POST",
      data: $("#export_form").serialize(),
      success: function (data) {
          window.location.href = "report.xls";
          document.getElementById("export_data_button").innerHTML = "Export Data";
      },
  });
  return false;
}
function ExportFinanceDataByQuotation() {
  // Assign filter_date_export if filter_date exists and has a value
    if (document.getElementById("filter_date") && document.getElementById("filter_date").value) {
        document.getElementById("filter_date_export").value = document.getElementById("filter_date").value;
    }

    // Assign city_export if cityName exists and has a value
    if (document.getElementById("cityName") && document.getElementById("cityName").value) {
        document.getElementById("city_export").value = document.getElementById("cityName").value;
    }

    // Assign branch_export if branch_name exists and has a value
    if (document.getElementById("branch_name") && document.getElementById("branch_name").value) {
        document.getElementById("branch_export").value = document.getElementById("branch_name").value;
    }

    // Assign company_account_export if filter_company_id exists and has a value
    if (document.getElementById("filter_company_id") && document.getElementById("filter_company_id").value) {
        document.getElementById("company_account_export").value = document.getElementById("filter_company_id").value;
    }

    // Assign state_export if stateName exists and has a value
    if (document.getElementById("stateName") && document.getElementById("stateName").value) {
        document.getElementById("state_export").value = document.getElementById("stateName").value;
    }

    // Assign status_export if ticket_status exists and has a value
    if (document.getElementById("ticket_status") && document.getElementById("ticket_status").value) {
        document.getElementById("status_export").value = document.getElementById("ticket_status").value;
    }
  document.getElementById("export_data_button").innerHTML = "Exporting..";
  $.ajax({
      url: "action/export_corporate_tickets_finance_by_quotation.php",
      type: "POST",
      data: $("#export_form").serialize(),
      success: function (data) {
          var epoch_time = document.getElementById("epoch_time").value;
          window.location.href = "report_finance"+epoch_time+".xls";
          document.getElementById("export_data_button").innerHTML = "Export Data";
      },
  });
  return false;
}
function ExportAccountCorporateTicketData()
{
    document.getElementById("filter_date_export").value = document.getElementById("filter_date").value;
    document.getElementById("branch_export").value = document.getElementById("branch_name").value;
    document.getElementById("company_account_export").value = document.getElementById("filter_company_id").value;
    document.getElementById("status_export").value = document.getElementById("ticket_status").value;
    
    document.getElementById("export_data_button").style.display = "none";
    document.getElementById("exporting_button").style.display = "";
      $.ajax({
          url: "action/export_account_corporate_tickets.php",
          type: "POST",
          data: $("#export_form").serialize(),
          success: function (data) {
              window.location.href = "account_tickets_report.xls";
               document.getElementById("export_data_button").style.display = "";
                document.getElementById("exporting_button").style.display = "none";
          },
      });
      return false;
}
function ExportAccountBranchCorporateTicketData()
{
    document.getElementById("filter_date_export").value = document.getElementById("filter_date").value;
    document.getElementById("branch_export").value = document.getElementById("branch_name").value;
    //document.getElementById("company_account_export").value = document.getElementById("filter_company_id").value;
    document.getElementById("status_export").value = document.getElementById("ticket_status").value;
      $.ajax({
          url: "action/export_account_branch_corporate_tickets.php",
          type: "POST",
          data: $("#export_form").serialize(),
          success: function (data) {
              window.location.href = "account_branch_tickets_report.xls";
          },
      });
      return false;
}

function GetBranchesFromCorporateID(selection)
{
    $.ajax({
      url: "ajax/view_branches_filter_div.php",
      type: "POST",
      data: 
      {
        "CompanyID":selection.value
      },
      success: function (data_response) {
          document.getElementById("branches_filter_div").innerHTML = data_response;
      },
  });
}

function GetCitiesfromState(selection)
{
    var city_in_sql = document.getElementById("city_in_sql").value;
    $.ajax({
      url: "ajax/view_cities_filter_div.php",
      type: "POST",
      data: 
      {
        "StateName":selection.value,
        "City_In_SQL":city_in_sql
      },
      success: function (data_response) {
          document.getElementById("branches_cities_div").innerHTML = data_response;
      },
  });
}

function GenerateTicketFinanceDashboard()
{
  document.getElementById("filter_date_export").value = document.getElementById("filter_date").value;
  document.getElementById("city_export").value = document.getElementById("cityName").value;
  document.getElementById("branch_export").value = document.getElementById("branch_name").value;
  document.getElementById("company_account_export").value = document.getElementById("filter_company_id").value;
  document.getElementById("state_export").value = document.getElementById("stateName").value;
  document.getElementById("status_export").value = document.getElementById("ticket_status").value;
  $.ajax({
          url: "ajax/generate_finance_dashboard.php",
          type: "POST",
          data: $("#export_form").serialize(),
          success: function (data) {
              //window.location.href = "account_tickets_report.xls";
              // document.getElementById("export_data_button").style.display = "";
             document.getElementById("status_buttons_div").innerHTML = data;
             barChart();
          },
      });
      return false;
}
function DownloadCorporateTicketsFileFormat() 
{
  const fullUrl = window.location.href;  // Get the full URL
  const baseUrl = fullUrl.substring(0, fullUrl.lastIndexOf('/') + 1); 
  // Replace 'file_url' with the URL of the file you want to download
  var file_url = baseUrl+'/template/Corporate-Tickets-Bulk-Upload-Format.csv';
  
  // Create a new anchor element
  var link = document.createElement('a');
  
  // Set the href attribute to the file URL
  link.href = file_url;
  
  // Set the download attribute to the file name
  link.setAttribute('download', 'Corporate-Tickets-Bulk-Upload-Format.csv');
  link.setAttribute('target', '_blank');
  
  // Simulate a click on the anchor element to initiate the download
  link.click();
}

function OpenTicketImportmodal()
{
    $("#upload_import_tickets_csv").modal();
}
function UploadCorporateTickets_CSV() {
  let myForm = document.getElementById("uplaod_corporate_tickets_csv"); 
    var formData = new FormData(myForm);
    $.ajax({
        url: "action/upload_corporate_tickets_csv.php",
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