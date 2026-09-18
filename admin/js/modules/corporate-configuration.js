function AddFormConfiguration()
{
	$("#field_title").val("");
    $("#field_type").val("");
    $("#field_mandatory").val("Yes");
    $("#form_action").val('add');
    $("#form_id").val(-1);
	$("#add_update_form_configuration").modal();
}
function SaveFormField()
{
	var field_title = $("#field_title").val();
	if(field_title == "")
	{
		TechXAlert("Field Title can't be blank");
		return false;
	}

	var field_type = $("#field_type").val();
	if(field_type == "")
	{
		TechXAlert("Field Type can't be blank");
		return false;
	}

	var field_mandatory = $("#field_mandatory").val();
	if(field_mandatory == "")
	{
		TechXAlert("Please select if the field will be mandatory or not");
		return false;
	}

	$("#form_configuration_field_btn").html("Saving..");
	  $.ajax({
	      url: "action/save-form-field.php",
	      type: "POST",
	      data: $("#add_update_field_form").serialize(),
	      success: function(data) {
	          var response = JSON.parse(data);
	          TechXAlert(response.message);
	          setInterval(function() {
                  location.reload();

              }, 1500);
	      },
	  });
}

function EditFormConfiguration(form_id,CorporateID)
{
	$.post("ajax/get_form_field_details.php",
    {
        ID: form_id
    },
    function (data, status) {
        var response = JSON.parse(data);        
        $("#field_title").val(response.Title);
        $("#field_type").val(response.Type);
        $("#field_mandatory").val(response.Mandatory);
        $("#form_action").val('edit');
        $("#form_id").val(form_id);
       	$("#add_update_form_configuration").modal();
    });
}

function AddCircle()
{
	$("#circle_name").val("");
    $("#form_circle_action").val('add');
    $("#form_circle_id").val(-1);
	$("#add_update_form_circle").modal();
}
function EditCircle(form_id,CircleName)
{     
    $("#circle_name").val(CircleName);
    $("#form_circle_action").val('edit');
    $("#form_circle_id").val(form_id);
   	$("#add_update_form_circle").modal();
}
function SaveCircle()
{
	var circle_name = $("#circle_name").val();
	if(circle_name == "")
	{
		TechXAlert("Circle Name can't be blank");
		return false;
	}
	$("#form_circle_btn").html("Saving..");
	  $.ajax({
	      url: "action/save-circle.php",
	      type: "POST",
	      data: $("#add_update_circle_form").serialize(),
	      success: function(data) {
	          var response = JSON.parse(data);
	          TechXAlert(response.message);
	          setInterval(function() {
                  location.reload();

              }, 1500);
	      },
	  });
}