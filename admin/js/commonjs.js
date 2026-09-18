// Functions by Prateek
function validateEmail(email) {
	var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
	return re.test(String(email).toLowerCase());
}
function validatePassword(userpassword) {
    var p = userpassword;
    errors = [];
    if (p.length < 8) {
        errors.push("Your password must be at least 8 characters"); 
    }
    if (p.search(/[a-z]/i) < 0) {
        errors.push("Your password must contain at least one letter.");
    }
    if (p.search(/[0-9]/) < 0) {
        errors.push("Your password must contain at least one digit."); 
    }
    if (errors.length > 0) {
        alertify.alert("PlanBook",errors.join("\n"));
        return false;
    }
    return true;
}
function TechXAlert(message)
{
	// Get the subdomain
    var subdomain = window.location.hostname.split('.')[0];

    // Check if the subdomain contains 'innov'
    var alertTitle = subdomain.includes('innov') ? "Innov" : "TechXpert";

    // Show the alert with the correct title
    alertify.alert(alertTitle, message);
}
function URLRouter(url)
{
	var form = $(document.createElement('form'));
    $(form).attr("action", url);
    $(form).attr("method", "POST");
    $(form).css("display", "none");

    var project_param = document.getElementById("project_param");
    if(project_param)
    {
        var project_param_value= project_param.value;
        var project_param_text = $("<input>")
        .attr("type", "text")
        .attr("name", "project_param")
        .val(project_param_value);
        
        $(form).append($(project_param_text));
    }

    var enterprise_param = document.getElementById("enterprise_param");
    if(enterprise_param)
    {
        
        var enterprise_param_value= enterprise_param.value;
        var enterprise_param_text = $("<input>")
        .attr("type", "text")
        .attr("name", "enterprise_param")
        .val(enterprise_param_value);
        
        $(form).append($(enterprise_param_text));
    }
    
    form.appendTo( document.body );	

    $(form).submit();
}

function BasicURLRouter(url)
{
    window.location.href = url;
}


function EnterpriseURLRouter(url,enterprise_param)
{
    var form = $(document.createElement('form'));
    $(form).attr("action", url);
    $(form).attr("method", "POST");
    $(form).css("display", "none");
    
    var enterprise_param_text = $("<input>")
    .attr("type", "text")
    .attr("name", "enterprise_param")
    .val(enterprise_param);
    
    $(form).append($(enterprise_param_text));

    form.appendTo( document.body );    

    $(form).submit();
}

$("#myDIV li ").click(function(){
            
	// remove the class i.e. selectednav from all li
	$('#myDIV li').removeClass("selectednav");
	// apply selectednav class to the current item
				$(this).addClass("selectednav");
});

function validateNumber(event)
{
    var theEvent = event || window.event;
    // Handle paste
    if (theEvent.type === 'paste') 
    {
        key = event.clipboardData.getData('text/plain');
    } 
    else 
    {
         // Handle key press
        var key = theEvent.keyCode || theEvent.which;
        key = String.fromCharCode(key);
    }
    var regex = /[0-9]|\./;
    if( !regex.test(key) ) 
    {
      theEvent.returnValue = false;
      if(theEvent.preventDefault) theEvent.preventDefault();
    }
}

function isNumber(event) {
  // Get the key code of the pressed key
  var keyCode = event.keyCode || event.which;

  // Allow only numbers (0-9) and special keys like backspace and delete
  if ((keyCode >= 48 && keyCode <= 57) || // Numbers
      (keyCode >= 96 && keyCode <= 105) || // Numpad numbers
      keyCode === 8 || // Backspace
      keyCode === 9 || // Tab
      keyCode === 46 || // Delete
      keyCode === 37 || // Left arrow
      keyCode === 39)   // Right arrow
  {
    return true; // Allow the input
  } else {
    return false; // Block the input
  }
}

function valid_email_check(customer_email) {
  var regex =
    /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
  if (!regex.test(customer_email)) {
    return false;
  } else {
    return true;
  }
}

function valid_phone_check(phone) {
  var regex = /^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/im;
  if (!regex.test(phone)) {
    return false;
  } else {
    return true;
  }
}

$(document).ready(function () {
   $('.navdata li span a').click(function() {

        $('.navdata li.active').removeClass('active');

        var $parent = $(this).parent();
        $parent.addClass('active');
        e.preventDefault();
    });
});

