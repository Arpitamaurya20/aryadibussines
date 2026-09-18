function LoadAccountDashboard()
{
	var filter_date = document.getElementById("filter_date").value;
	var UserType = $("#UserType").val();
	var type = "";
	if($("#type").length)
	{
		type = document.getElementById("type").value;
	}
	if(type == "")
	{
		TechXAlert("Please Select Type");
		return false;
	}
	var state_name = "";
	if($("#type").length)
	{
		state_name = document.getElementById("stateName").value;
	}
	var sql_in_state_string = document.getElementById("sql_in_state_string").value;
	$.post("ajax/get_account_dashboard_html.php", {
          UserType: UserType,
          filter_date: filter_date,
          Type: type,
          StateName: state_name,
          sql_in_state_string:sql_in_state_string
	  },
	  function(data, status) {
	      document.getElementById("daily_tracker_html").innerHTML = data;
	  });
}