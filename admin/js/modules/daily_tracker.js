function LoadDailyTracker(CorporateID)
{
	var wrap = document.getElementById("daily_tracker_status_html");
	if (!wrap) {
		return;
	}
	var filter_date = document.getElementById("filter_date").value;
	var UserType = $("#UserType").val();
	if($("#corporate_name").length)
	{
		CorporateID = document.getElementById("corporate_name").value;
	}
	wrap.classList.add("is-loading");
	$.post("ajax/get_daily_tracker_html.php", {
          UserType: UserType,
          filter_date: filter_date,
          CorporateID: CorporateID
	  },
	  function(data) {
	      wrap.innerHTML = data;
	      wrap.classList.remove("is-loading");
	      var payload = document.getElementById("dt_kpi_payload");
	      if (payload) {
	          var fmt = function(n) { return Number(n || 0).toLocaleString(); };
	          var setText = function(id, value) {
	              var el = document.getElementById(id);
	              if (el) { el.textContent = value; }
	          };
	          setText("dt_kpi_total", fmt(payload.getAttribute("data-total")));
	          setText("dt_kpi_closed", fmt(payload.getAttribute("data-closed")));
	          setText("dt_kpi_open", fmt(payload.getAttribute("data-open")));
	          setText("dt_kpi_hold", fmt(payload.getAttribute("data-hold")));
	          setText("dt_kpi_days", fmt(payload.getAttribute("data-days")) + " days in range");
	          var cancelCount = Number(payload.getAttribute("data-cancel") || 0);
	          setText("dt_kpi_cancel", cancelCount ? fmt(cancelCount) + " cancelled" : "Waiting");
	      }
	  }).fail(function() {
	      wrap.classList.remove("is-loading");
	  });
}
