function LoadDailyTracker(CorporateID)
{
	var filter_date = document.getElementById("filter_date").value;
	var UserType = $("#UserType").val();
	if($("#corporate_name").length)
	{
		CorporateID = document.getElementById("corporate_name").value;
	}
	$.post("ajax/get_daily_tracker_html.php", {
          UserType: UserType,
          filter_date: filter_date,
          CorporateID: CorporateID
	  },
	  function(data, status) {
	      document.getElementById("daily_tracker_status_html").innerHTML = data;
	  });
}