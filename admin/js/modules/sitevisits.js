function SearchSiteVisits()
{
	var table = $('#view-site-visits').DataTable();
    table.destroy();
    var param = "";
    
    var filter_date = document.getElementById("filter_date").value;
    param = "?filter_date=" + filter_date;
   	var i = 1;
    $('#view-site-visits').dataTable({
        responsive: true,
        'processing': true,
        'serverSide': true,
        'ordering': false,
        'serverMethod': 'post',
        'ajax': {
            'url': 'action/view-all-site-visits-post.php'+param
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
                data: 'Corporate'
            },
            {
                data: 'Branch'
            },
            {
                data: 'VisitTitle'
            },
            {
                data: 'Status'
            },
            
            {
                data: 'ContactPerson'
            },
            {
                data: 'CreatedOn'
            },
            {
                data: 'CompletedOn'
            },
            {
                data: 'Details'
            }
        ]


	});
}

function ViewSiteVistDetails(ID)
{
	$.post(
            "../controllers/setSession.php", {
                SiteVisitID: ID,
            },
            function(data, status) {
                BasicURLRouter("view-site-visit-details");
            }
        );
}

function DownloadSiteVisitReport(SiteVisitID)
{
    
  $("#download_button").text("Downloading...");
  
  //var url = "action/generate_amc_service_report_pdf.php";
  var url = "action/generate_site_visit_pdf.php";
  $.post(url,
  {
    "SiteVisitID": SiteVisitID,
    "Action":"Download"
  },
  function (data, status) 
  { 
    data_response = JSON.parse(data);
    
      $("#download_button").text("Download");
      window.open("reports/"+data_response.pdfname, '_blank');
    
    

  })
}