# Modals
Z.js has the ability to generate one time use modals that can replace the native browser functions `alert()` and `confirm()`. The modals are created by Z.js in frontend. Creating modals with this technique on the backend is not possible. If required, the modals should be build as HTML in the view.
    
Z.js Modals don't block the main thread and are better looking than the native alert/confirm.

All functions in `Z.ModalsBS4` require to have the Bootstrap 4 styles loaded.

## Showing a simple text message
```js
async function() {
  console.log("Modal is opening...");
  await Z.ModalsBS4.showMessage({
    message: "Hello World",
    title: "Titel"            // Title is optional
  });
  console.log("Modal closed!");
}
```

## Ask the user for confirmation
```js
async function() {
  if (await Z.ModalsBS4.confirm({
    message: "Please confirm this action",
    title: "Confirmation",                  // Title is optional
    confirmString: "Type this"              // optional. If given, the user has to type the string into an input before confirming is possible.
  })) {
    doSomethingDangerous();
  }
}
```