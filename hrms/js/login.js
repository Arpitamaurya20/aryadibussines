function isValidEmail(email) {
  var emailRegex = /^\w+([.-]?\w+)*@\w+([.-]?\w+)*(\.\w{2,3})+$/;
  return emailRegex.test(email);
}

function login() {
  var email = $("#email").val();
  if (!isValidEmail(email)) {
    Alert("Please enter valid email address");
    return false;
  }
  var password = $("#password").val();
  if (password == '') {
    Alert("Please enter password");
    return false;
  }
  var data = $("#form-login").serialize();
  $.ajax({
    type: "POST",
    url: "action/login-action",
    data: data,
    success: function (data) {
      var response = JSON.parse(data);
      if (response.error === false) {
        if (response.access_token) {
          localStorage.setItem("auth_token", response.access_token);
        } else {
          Alert("Login error: Token missing.");
          return false;
        }
        if (response.UserType === "System Admin") {
          window.location.href = "../dashboard/admin-dashboard";
          return false;
        }
         else {
          window.location.href = "../dashboard/admin-dashboard";
          return false;
        }

      } else {
        Alert(response.message);
      }
    }
  });

  return false;
}
