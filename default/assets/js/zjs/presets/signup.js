/**
 * Signup Preset. Call it on every try for example on the submit button press
 * @param {string} nameElementId ID of the DOM input for the name/mail
 * @param {string} passwordElementId ID of the DOM input for the password
 * @param {string} passwordConfirmElementId ID of the DOM input for repeating the password
 * @param {string} errorLabelId ID if the DOM elemnt to show errors in
 * @param {string} redirect URL to redirect to after a successfull signup
 */
export default function Signup(nameElementId, passwordElementId, passwordConfirmElementId, errorLabelId, redirect = "", alertErrors = false) {
  var eName = document.getElementById(nameElementId);
  var ePassword = document.getElementById(passwordElementId);
  var ePasswordConfirm = document.getElementById(passwordConfirmElementId);
  var errorLabel = document.getElementById(errorLabelId);

  if (ePassword.value != ePasswordConfirm.value) { 
    if(alertErrors) {
      alert(Z.Lang.error_password_mismatch); 
      return; 
    } else {
      if(errorLabel.innerHTML == Z.Lang.error_password_mismatch) {
        $('#'+errorLabelId).fadeOut(20).fadeIn(100).fadeOut(20).fadeIn(100).show();
      } else {
        errorLabel.innerHTML = Z.Lang.error_password_mismatch;
        $('#'+errorLabelId).slideDown(300);
      }
      return;
    }
  }

  var loader = document.getElementById("loading");
  if (loader) {
    loader.style.display = "";
  }

  Z.Request.root('login/signup', 'signup', {email: eName.value, password: ePassword.value}, (res) => {
    if (loader) {
      loader.style.display = "none";
    }

    if (res.result == "error") {
      let msg = res.message;
      if (res.message == "This email is not allowed!") {
        msg = Z.Lang.error_invalid_email;
      }

      errorLabel.innerHTML = msg;

      if(alertErrors) alert(msg);
    } else if (res.result == "success") {
      if (redirect == "") {
        window.location.reload();
      } else {
        window.location.href = redirect;
      }
    }
  });
}