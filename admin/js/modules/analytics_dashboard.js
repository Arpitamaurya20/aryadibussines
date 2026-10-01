function doesElementExist(id) {
    return !!document.getElementById(id);
}
function getAnalyticsCorporateID()
{
  var corporateIdEl = document.getElementById('AnalyticsCorporateID');
  if(corporateIdEl && corporateIdEl.value != "" && corporateIdEl.value != "-1")
  {
    return corporateIdEl.value;
  }
  if($("#corporate_name").length)
  {
    return $("#corporate_name").val();
  }
  return -1;
}
function getAnalyticsDashboardFilters()
{
  var state_filter = "";
  if(doesElementExist("ticket_state_filter"))
  {
    state_filter = $("#ticket_state_filter").val();
  }
  var region_filter = "";
  if(doesElementExist("ticket_region_filter"))
  {
    region_filter = $("#ticket_region_filter").val();
  }
  var filter_date = "";
  if(doesElementExist("filter_date"))
  {
    filter_date = $("#filter_date").val();
  }
  return {
    state_filter: state_filter,
    region_filter: region_filter,
    filter_date: filter_date,
    sql_in_state_string: $("#sql_in_state_string").val(),
    sql_in_branch_account_string: $("#sql_in_branch_account_string").val()
  };
}
function bindAnalyticsStatusTicketLinks()
{
  $("#status_buttons_div").off("click", ".analytics-status-ticket-link");
  $("#status_buttons_div").on("click", ".analytics-status-ticket-link", function(e) {
    e.preventDefault();
    var el = $(this);
    OpenAnalyticsStatusTicketsModal(
      el.attr("data-ticket-scope"),
      el.attr("data-ticket-type"),
      el.attr("data-ticket-status"),
      el.attr("data-status-label"),
      el.attr("data-section-label")
    );
  });
}
function OpenAnalyticsStatusTicketsModal(ticketScope, ticketType, ticketStatus, statusLabel, sectionLabel)
{
  var filters = getAnalyticsDashboardFilters();
  var corporateId = getAnalyticsCorporateID();
  var modalTitle = "Tickets - " + statusLabel + " (" + sectionLabel + ")";
  $("#analyticsStatusTicketsModalTitle").text(modalTitle);
  $("#analyticsStatusTicketsSummary").text("Loading tickets...");
  $("#analyticsStatusTicketsLoader").show();
  $("#analyticsStatusTicketsError").hide().text("");
  $("#analyticsStatusTicketsEmpty").hide();
  $("#analyticsStatusTicketsTableWrap").hide();
  $("#analyticsStatusTicketsTableBody").empty();
  $("#analyticsStatusTicketsModal").modal("show");

  $.post("ajax/get_tickets_by_status.php",
  {
    CorporateID: corporateId,
    ticket_scope: ticketScope,
    ticket_type: ticketType,
    ticket_status: ticketStatus,
    state_filter: filters.state_filter,
    region_filter: filters.region_filter,
    filter_date: filters.filter_date,
    sql_in_state_string: filters.sql_in_state_string,
    sql_in_branch_account_string: filters.sql_in_branch_account_string,
    limit: 200,
    offset: 0
  },
  function(data) {
    $("#analyticsStatusTicketsLoader").hide();
    var response = data;
    if(typeof data === "string")
    {
      try {
        response = JSON.parse(data);
      } catch (err) {
        $("#analyticsStatusTicketsError").text("Unable to read ticket list response.").show();
        $("#analyticsStatusTicketsSummary").text("");
        return;
      }
    }
    if(!response.ok)
    {
      $("#analyticsStatusTicketsError").text(response.msg || "Unable to load tickets.").show();
      $("#analyticsStatusTicketsSummary").text("");
      return;
    }
    var tickets = response.tickets || [];
    var totalCount = response.total_count || 0;
    var showingCount = tickets.length;
    $("#analyticsStatusTicketsSummary").text("Total: " + totalCount + " • Showing: " + showingCount + " (limit " + (response.limit || 200) + ")");
    if(showingCount === 0)
    {
      $("#analyticsStatusTicketsEmpty").show();
      return;
    }
    var rowsHtml = "";
    tickets.forEach(function(ticket) {
      var branchSite = ticket.branch_site || "";
      if(ticket.branch_code)
      {
        branchSite = branchSite ? branchSite + " (" + ticket.branch_code + ")" : ticket.branch_code;
      }
      rowsHtml += "<tr><td>" + (ticket.ticket_id || "") + "</td><td>" + branchSite + "</td></tr>";
    });
    $("#analyticsStatusTicketsTableBody").html(rowsHtml);
    $("#analyticsStatusTicketsTableWrap").show();
  }).fail(function() {
    $("#analyticsStatusTicketsLoader").hide();
    $("#analyticsStatusTicketsError").text("Failed to load tickets. Please try again.").show();
    $("#analyticsStatusTicketsSummary").text("");
  });
}
function GenerateDashboard(CorporateID)
{
  if(CorporateID == -1)
  {
    CorporateID = $("#corporate_name").val();
  }
  RefreshBranchAnalytics(CorporateID);
}
function RefreshBranchAnalytics(CorporateID)
{
  $("#panel-rnm").show();
  /*$("#ticket_state_filter").val("");
  $("#ticket_region_filter").val("");
  $("#ticket_type_filter").val("");
  $("#ticket_status_filter").val("");*/
  GenerateBranchAnalytics(CorporateID);
  GenerateQuotationsDashboard_Analytics(CorporateID);
}
function GenerateBranchAnalytics(CorporateID)
{
  var state_filter = "";
  if(doesElementExist("ticket_state_filter"))
  {
    state_filter = $("#ticket_state_filter").val();
  }
  var region_filter = "";
  if(doesElementExist("ticket_region_filter"))
  {
    region_filter = $("#ticket_region_filter").val();
  }
  var ticket_type_filter = "";
  if(doesElementExist("ticket_type_filter"))
  {
    ticket_type_filter = $("#ticket_type_filter").val();
  }
  var ticket_status_filter = "";
  if(doesElementExist("ticket_status_filter"))
  {
    ticket_status_filter = $("#ticket_status_filter").val();
  }
  var filter_date = "";
  if(doesElementExist("filter_date"))
  {
    filter_date = $("#filter_date").val();
  }
	GenerateCorporateBranchStats(CorporateID,state_filter,region_filter,ticket_type_filter,ticket_status_filter,filter_date);
  GenerateCorporateNameandTicketCount(CorporateID,state_filter,region_filter,ticket_type_filter,ticket_status_filter,filter_date);
  GenerateStateandRegionofCorporate(CorporateID,state_filter,region_filter,ticket_type_filter,ticket_status_filter);
  GenerateTypeStatus(CorporateID,state_filter,region_filter,ticket_type_filter,ticket_status_filter);
  GenerateTicketStatusBarGraph(CorporateID,state_filter,region_filter,ticket_type_filter,ticket_status_filter,filter_date);
  GenerateRegionDoughnutGraph(CorporateID,state_filter,region_filter,ticket_type_filter,ticket_status_filter,filter_date);
  GenerateStateWiseTicketBarGraph(CorporateID,state_filter,region_filter,ticket_type_filter,ticket_status_filter,filter_date);
  GenerateStatusButtons(CorporateID,state_filter,region_filter,filter_date);
  GenerateQuotationsDashboard_Analytics(CorporateID);
}
function GenerateStatusButtons(CorporateID,state_filter,region_filter,filter_date)
{
  var sql_in_state_string = $("#sql_in_state_string").val();
  var sql_in_branch_account_string = $("#sql_in_branch_account_string").val();
  document.getElementById("status_buttons_div_loader").style.display = "block";
  document.getElementById("status_buttons_div").style.display = "none";
  $.post("ajax/get_status_buttons.php",
  {
    filter_date:filter_date,
    sql_in_state_string:sql_in_state_string,
    sql_in_branch_account_string:sql_in_branch_account_string,
    CorporateID: CorporateID,
    state_filter:state_filter,
    region_filter:region_filter
  },
  function (data, status) 
  {
    document.getElementById("status_buttons_div_loader").style.display = "none";
    document.getElementById("status_buttons_div").innerHTML = data;        
    document.getElementById("status_buttons_div").style.display = "";
    $('.js-easy-pie-chart').each(function() 
    {
        // Get the color from the badge
        let badgeColor = $(this).closest('.d-flex').find('.badge').css('background-color');
        
        // Initialize the pie chart with the badge color as the bar color
        $(this).easyPieChart({
            size: 50,
            barColor: badgeColor,     // Dynamic color based on badge
            trackColor: '#e9ecef',     // Background track color
            scaleColor: '#adb5bd',     // Scale color for ticks
            scaleLength: 2,            // Length of scale ticks
            lineWidth: 5,
            lineCap: 'butt'
        });
    });
    bindAnalyticsStatusTicketLinks();
  });
}
function GenerateCorporateBranchStats(CorporateID,state_filter,region_filter,ticket_type_filter,ticket_status_filter,filter_date)
{
  var sql_in_state_string = $("#sql_in_state_string").val();
  var sql_in_branch_account_string = $("#sql_in_branch_account_string").val();
  var corporateBranchPanel = document.getElementById('corporate_branch_panel');
  var corporateRestPanel = document.getElementById('corporate_branch_rest_panel');
  if(CorporateID != -1)
  {
  	$.post("ajax/get_corporate_branch_stats.php",
    {
      CorporateID: CorporateID,
      state_filter:state_filter,
      region_filter:region_filter,
      ticket_type_filter:ticket_type_filter,
      ticket_status_filter:ticket_status_filter,
      filter_date:filter_date,
      sql_in_state_string:sql_in_state_string,
      sql_in_branch_account_string:sql_in_branch_account_string
    },
    function (data, status) 
    {
      corporateRestPanel.classList.remove('col-lg-12', 'col-xl-12');
      corporateRestPanel.classList.add('col-lg-8', 'col-xl-8');
      document.getElementById("corporate_branch_panel").style.display = ""; 
    	document.getElementById("corporate_branch_panel").innerHTML = data;      
      $("#ad_branch_name").select2();
      var branchIdEl = document.getElementById('BranchID');
      if(branchIdEl && branchIdEl.value != "" && branchIdEl.value != "-1")
      {
        $("#ad_branch_name").val(branchIdEl.value).trigger('change');
      }
  	});
  }
  if(CorporateID == -1)
  {
    corporateBranchPanel.style.display = 'none';
    corporateRestPanel.classList.remove('col-lg-8', 'col-xl-8');
    corporateRestPanel.classList.add('col-lg-12', 'col-xl-12');
  }
}
function AD_RefreshBranchAnalytics(CorporateID,BranchID)
{
  var filter_date = "";
  if(doesElementExist("filter_date"))
  {
    filter_date = $("#filter_date").val();
  }
  $.post("ajax/get-branch-wise-status.php",
  {
    CorporateID: CorporateID,
    BranchID: BranchID,
    filter_date: filter_date
  },
  function (data, status) 
  {
    document.getElementById("branch_wise_status_div").innerHTML = data;        
  });
}
function GenerateCorporateNameandTicketCount(CorporateID,state_filter,region_filter,ticket_type_filter,ticket_status_filter,filter_date)
{
  var sql_in_state_string = $("#sql_in_state_string").val();
  var sql_in_branch_account_string = $("#sql_in_branch_account_string").val();
  $.post("ajax/get_corporate_name_ticket_count.php",
  {
    CorporateID: CorporateID,
    state_filter:state_filter,
    region_filter:region_filter,
    ticket_type_filter:ticket_type_filter,
    ticket_status_filter:ticket_status_filter,
    filter_date:filter_date,
    sql_in_state_string:sql_in_state_string,
    sql_in_branch_account_string:sql_in_branch_account_string
  },
  function (data, status) 
  {
    document.getElementById("corporate_name_ticket_count_fetch").innerHTML = data;        
  });
}
function GenerateStateandRegionofCorporate(CorporateID,state_filter,region_filter,ticket_type_filter,ticket_status_filter)
{
  var sql_in_state_string = $("#sql_in_state_string").val();
  var sql_in_branch_account_string = $("#sql_in_branch_account_string").val();
  $.post("ajax/get_state_region_corporate.php",
  {
    CorporateID: CorporateID,
    state_filter: state_filter,
    region_filter:region_filter,
    ticket_type_filter:ticket_type_filter,
    ticket_status_filter:ticket_status_filter,
    sql_in_state_string:sql_in_state_string,
    sql_in_branch_account_string:sql_in_branch_account_string
  },
  function (data, status) 
  {
    document.getElementById("state_region_view").innerHTML = data; 
    if(state_filter != "")
    {
      $("#ticket_state_filter").val(state_filter);
    } 
    if(region_filter != "")
    {
      $("#ticket_region_filter").val(region_filter);
    }       
  });
}
function GenerateTypeStatus(CorporateID,state_filter,region_filter,ticket_type_filter,ticket_status_filter)
{
  $.post("ajax/get_type_status.php",
  {
    CorporateID: CorporateID,
    state_filter: state_filter,
    region_filter:region_filter,
    ticket_type_filter:ticket_type_filter,
    ticket_status_filter:ticket_status_filter
  },
  function (data, status) 
  {
    document.getElementById("type_status_view").innerHTML = data;  
    if(ticket_type_filter != "")
    {
      $("#ticket_type_filter").val(ticket_type_filter);
    }   
    if(ticket_status_filter != "")
    {
      $("#ticket_status_filter").val(ticket_status_filter);
    }     
  });
}
var ticketStatusBarChart;
function GenerateTicketStatusBarGraph(CorporateID,state_filter,region_filter,ticket_type_filter,ticket_status_filter,filter_date)
{
  if (ticketStatusBarChart) {
    ticketStatusBarChart.destroy();
  }
  var sql_in_state_string = $("#sql_in_state_string").val();
  var sql_in_branch_account_string = $("#sql_in_branch_account_string").val();
  $.post("ajax/get_ticket_status_bar_graph.php",
  {
    CorporateID: CorporateID,
    state_filter: state_filter,
    region_filter:region_filter,
    ticket_type_filter:ticket_type_filter,
    ticket_status_filter:ticket_status_filter,
    filter_date:filter_date,
    sql_in_state_string:sql_in_state_string,
    sql_in_branch_account_string:sql_in_branch_account_string
  },
  function (data, status) 
  {
      response_data = JSON.parse(data);
      document.getElementById("ticket_status_bar_graph").style.display = "";
      const ctx = document.getElementById('ticket_status_graph_id');
      console.log(response_data.chart_data.data_labels);
      ticketStatusBarChart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: response_data.chart_data.data_labels,
          datasets: [{
            label: 'Status Count',
            data: response_data.chart_data.data,
            borderWidth: 1
          }]
        },
        options: {
            indexAxis: 'y',
          }
      });
           
  });
}
var RegionDoughnutChart;
function GenerateRegionDoughnutGraph(CorporateID,state_filter,region_filter,ticket_type_filter,ticket_status_filter,filter_date)
{
  if (RegionDoughnutChart) {
    RegionDoughnutChart.destroy();
  }
  var sql_in_state_string = $("#sql_in_state_string").val();
  var sql_in_branch_account_string = $("#sql_in_branch_account_string").val();
  //destroyChart('region_status_graph_id');
  $.post("ajax/get_ticket_status_by_region_doughnut_graph.php",
  {
    CorporateID: CorporateID,
    state_filter:state_filter,
    region_filter:region_filter,
    ticket_type_filter:ticket_type_filter,
    ticket_status_filter:ticket_status_filter,
    filter_date:filter_date,
    sql_in_state_string:sql_in_state_string,
    sql_in_branch_account_string:sql_in_branch_account_string
  },
  function (data, status) 
  {
      response_data = JSON.parse(data);
      document.getElementById("type_status_pie_region_graph").style.display = "";
      const ctx = document.getElementById('region_status_graph_id');
      //console.log(response_data.chart_data.data_labels);
      RegionDoughnutChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: response_data.chart_data.data_labels,
          datasets: [{
            label: 'Status Count',
            data: response_data.chart_data.data,
            backgroundColor: response_data.chart_data.data_bg
          }]
        }
      });
           
  });
}

var StateWiseBarGraph;
function GenerateStateWiseTicketBarGraph(CorporateID,state_filter,region_filter,ticket_type_filter,ticket_status_filter,filter_date)
{
  if (StateWiseBarGraph) {
    StateWiseBarGraph.destroy();
  }
  //destroyChart('region_status_graph_id');
  var sql_in_state_string = $("#sql_in_state_string").val();
  var sql_in_branch_account_string = $("#sql_in_branch_account_string").val();
  $.post("ajax/get_ticket_status_by_state_bar_graph.php",
  {
    CorporateID: CorporateID,
    state_filter:state_filter,
    region_filter:region_filter,
    ticket_type_filter:ticket_type_filter,
    ticket_status_filter:ticket_status_filter,
    filter_date:filter_date,
    sql_in_state_string:sql_in_state_string,
    sql_in_branch_account_string:sql_in_branch_account_string
  },
  function (data, status) 
  {
      response_data = JSON.parse(data);
      document.getElementById("state_wise_ticket_count").style.display = "";
      const ctx_state = document.getElementById('state_wise_ticket_graph_id');
      //console.log(response_data.chart_data.data_labels);
      response_data = JSON.parse(data);
      //console.log(response_data.chart_data.data_labels);
      StateWiseBarGraph = new Chart(ctx_state, {
        type: 'bar',
        data: {
          labels: response_data.chart_data.data_labels,
          datasets: [{
            label: 'States',
            data: response_data.chart_data.data,
            borderWidth: 1
          }]
        },
        options: {
            indexAxis: 'x',
          }
      });
           
  });
}

function BranchWiseTicketsModal()
{
  $("#branchTicketsModal").modal("show");
}

function GenerateQuotationsDashboard_Analytics(CorporateID)
{
  var state_filter = "";
  if(doesElementExist("ticket_state_filter"))
  {
    state_filter = $("#ticket_state_filter").val();
  }
  var region_filter = "";
  if(doesElementExist("ticket_region_filter"))
  {
    region_filter = $("#ticket_region_filter").val();
  }
  var Category = $('#service_name_qd').val();
  var TicketStatus = $('#ticket_status').val();
  var sql_in_state_string = $("#sql_in_state_string").val();
  var sql_in_branch_account_string = $("#sql_in_branch_account_string").val();
  var filter_date = "";
  if(doesElementExist("filter_date"))
  {
    filter_date = $("#filter_date").val();
  }
  $.post("../corporate-tickets/ajax/generate_quotation_dashboard.php",
  {
    CorporateID: CorporateID,
    Category:Category,
    TicketStatus:TicketStatus,
    filter_date:filter_date,
    sql_in_state_string:sql_in_state_string,
    sql_in_branch_account_string:sql_in_branch_account_string,
    state_filter:state_filter,
    region_filter:region_filter
  },
  function (data, status) 
  {
    document.getElementById("quotation_dashboard_div").innerHTML = data;
    $("#service_name_qd").select2(); 

  })
}