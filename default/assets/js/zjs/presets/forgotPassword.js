/**
 * Login preset. Can be used to create a user login. Call it on every try for example on tge submit button press
 * @param {string} unameemailElementId ID of the dom element for the name/email input
 * @param {string} errorLabel ID of the dom element to show errors in
 * @param {string} redirect URL to redirect after a successful login
 */
export default function ForgotPassword(unameemailElementId, errorLabel, redirect = "") {
  var eUnameemail = document.getElementById(unameemailElementId);
  var loader = document.getElementById("loading");
  if (loader) {
    loader.style.display = "";
  }
  Z.Request.root('login/forgot_password', 'forgot_password', {unameemail: eUnameemail.value}, (res) => {  
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
      if(document.getElementById(errorLabel).innerHTML == Z.Lang.error_password_reset) {
        $('#'+errorLabel).fadeOut(20).fadeIn(100).fadeOut(20).fadeIn(100).show();
      } else {
        document.getElementById(errorLabel).innerHTML = Z.Lang.error_password_reset;//res.message;
        $('#'+errorLabel).slideDown(300);
      }
    }
  });
}