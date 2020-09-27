# Auto form validation and database updates
This framework has the ability to create automatic generated forms with user feedback. These forms can be validated automatically on the server and if errors occur the feedback is sent back to the user. If no errors occur the data can be used to update a database table.
## Front-end
To create the form on the frontend, use the Z.js library. An example form can be created with this code:
```javascript
var form = Z.Forms.create({dom: "form"});

var inputEmail = form.createField({
    name: "email", 
    type: "email", 
    text: "<?php $opt["lang"]("email"); ?>", 
    value: "<?php echo $opt["email"]; ?>"
});

var inputLanguage = form.createField({
    name: "languageId", 
    type: "select", 
    text: "<?php $opt["lang"]("language"); ?>", 
    value: "<?php echo $opt["language"]; ?>", 
    food: <?php echo $opt["languages"]; ?>
});
```
The form is created with `Z.Forms.create`. The `dom` attribute takes the id of an html element in which the form in embedded. For this to work the element needs to be loaded so this script should not execute directly at the start of the page. If the element will spawns later the form can be added to it with the dom attribute `element.appendChild(form.dom);`.

`form.createField()` creates a new input field. `name` corresponds to the name in the post request. `type` is the input type. Any valid html standard type, textarea and select are possible values. This will not have any effect on how the values are parsed at the server. `text` is the label text. `value` is the default value. `food` is only required for selects. It describes what options are available. It needs to be an array formatted like this: `[{value: 1, text: "one"}, {value: 2, text: "two"}, ...]`. This array can be created at the backend with [`$controller->makeFood`](https://zdoc.zierhut-it.de/classes/z_controller.html#method_makeFood).

If the type of a form element is `button` it can be styled with the `style` attribute. The values for this attribute are the default bootstrap button classes like `btn-primary` or `btn-secondary`.

The `width` attribute can be used to set the width of form element. It is in 1/12 units of the full width and equivalent to the bootstrap column classes. This width value is only used on medium devices or larger. On small devices, the width is always 100% / 12.

With the `attributes` attribute it is possible to define attributes for the generated input element. Attributes like `min` or `max` for a number input. Example: 
```
form.createElement({name: "zahl", type: "number", attributes: {min: 0, max: 100, step: 10}});
```

The return value if `form.createField` is the created field. It has an attribute called `input` which can be used to access the dom input element directly. It also has `on` which is an alias for addEventListener on the input dom element. The value can be read and set with `value`.

A separator between form inputs can be added with `form.createSeperator();`. This will insert a simple `<hr>` element at the end of the current builded form.
## Back-end
When the form is submitted, it will send an asynchronous post request to the current action specified by the current users url. To check in the action if the current request is from a form, [`$req->hasFormData()`](https://zdoc.zierhut-it.de/classes/Request.html#method_hasFormData) can be used. This is example code for handling a form:
```php
if ($req->hasFormData()) {
    $formResult = $req->validateForm([
        (new FormField("email"))        -> required() -> filter(FILTER_VALIDATE_EMAIL) -> unique("z_user", "email", "id", $userId),
        (new FormField("languageId"))   -> required() -> exists("z_language", "id")
    ]);

    if ($formResult->hasErrors) {
        $res->formErrors($formResult->errors);
    } else {
        $res->updateDatabase("z_user", "id", "i", $userId, $formResult);
        $res->success();
    }
}
```
[`$req->validateForm()`](https://zdoc.zierhut-it.de/classes/Request.html#method_validateForm) validates incoming data. As the first parameter it takes an array of fields to validate. To these field, rules can be attached. For more information see [`FormField`](https://zdoc.zierhut-it.de/classes/FormField.html). If no form error occurs, [`$formResult->hasErrors`](https://zdoc.zierhut-it.de/classes/FormResult.html#property_hasErrors) will be false. Else the errors should be send back to the client with [`$res->formErrors()`](https://zdoc.zierhut-it.de/classes/Response.html#method_formErrors). When everything works fine [`$res->success()`](https://zdoc.zierhut-it.de/classes/Response.html#method_success) should be called to signalize the client a success. Else it will show an save error. 

Success will exit the current action. So before calling it the data should be processed by a model or [`$res->updateDatabase()`](https://zdoc.zierhut-it.de/classes/Response.html#method_updateDatabase). Update database will take the result object from the form validation to get the names in the database. If the name in the database column and the post differ, the database name can be set by the second parameter of the [constructor of FormField](https://zdoc.zierhut-it.de/classes/FormField.html#method___construct).

`$res->insertDatabase()` can also be used to process the data. This method will create a dataset in a table given as argument. It works similar to `$res->updateDatabase()`.

## Example advanced layout
Code:
```html
<h2>Testpage</h2>

<div id="form"></div>

<script>
  var form = Z.Forms.create({dom: "form"});
  
  form.createField({
      name: "input_email", 
      type: "email",    
      text: "Some mail address", 
      required: true,      
      width: 6, 
      placeholder: "email@blubs.de"
  });
  
  form.createField({
      name: "input_date",  
      type: "date",     
      text: "Some random date",                       
      width: 6
  });
  
  var but = form.createField({
      name: "button",      
      type: "button", 
      value: "Set number = 4", 
      style: "btn-secondary",  
      width: 2
  });
  
  var num = form.createField({
      name: "number",      
      type: "number",   
      text: "Give me a number", 
      required: true,       
      width: 10,
      attributes: {
          min: 1,
          max: 10,
          step: 2
      }
  });
  
  form.createField({
      name: "range",       
      type: "textarea", 
      text: "Textarea", 
      value: "Hello o/"
  });
  
  form.createField({
      name: "file",        
      type: "file",     
      text: "File upload"
  });

  but.on('click', () => {
    num.value = 4;
  });
</script>
```
Result:
![image](/attachments/ea84c19f-fe6a-4954-b2f9-9da51b3cb863)