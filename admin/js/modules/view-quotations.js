function GenerateQuotationsDashboard_Analytics(CorporateID)
{
  var Category = $('#service_name_qd').val();
  var TicketStatus = $('#ticket_status').val();
  $.post("../corporate-tickets/ajax/generate_quotation_dashboard.php",
  {
    CorporateID: CorporateID,
    Category:Category,
    TicketStatus:TicketStatus
  },
  function (data, status) 
  {
    document.getElementById("status_buttons_div").innerHTML = data;
    $("#service_name_qd").select2(); 

  })
}