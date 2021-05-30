export default function Login(nameElementId, passwordElementId, errorLabelId, redirect = "") {
  var eName = document.getElementById(nameElementId);
  var ePassword = document.getElementById(passwordElementId);
  var errorLabel = document.getElementById(errorLabelId);

  var loader = document.getElementById("loading");
  if (loader) {
    loader.style.display = "";
  }
  errorLabel.style.display = "none";
  Z.Request.root('login', 'login', {name: eName.value, password: ePassword.value}, (res) => {  
    if (loader) {
      loader.style.display = "none";
    }

    if (res.result == "success") {
      if (redirect == "") {
        window.location.reload();
      } else {
        window.location.href = redirect;
      }
    } else {
      errorLabel.style.display = "";
      var msg = res.message;
      if (msg == "Username or password is wrong") {
        msg = Z.Lang.error_login;
      }
      if (msg == "Too many login tries. Try again later.") {
        msg = Z.Lang.error_too_many_login_tries;
      }

      if(errorLabel.innerHTML == msg) {
        $(errorLabel).fadeOut(20).fadeIn(100).fadeOut(20).fadeIn(100).show();
      } else {
        errorLabel.innerHTML = msg;
        $(errorLabel).slideDown(300);
      }
    }
  });
};