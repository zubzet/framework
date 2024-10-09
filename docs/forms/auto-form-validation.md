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
### **Creating a form**

The form is created with `Z.Forms.create`. The `dom` attribute takes the id of an html element in which the form in embedded. For this to work the element needs to be loaded so this script should not execute directly at the start of the page. If the element will spawns later the form can be added to it with the dom attribute `element.appendChild(form.dom);`.

### **Creating fields**

| Attribute   | Description                                                                                                                                                                                                                                                                                                    |
|-------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `name`      | Corresponds to the name in the post request.                                                                                                                                                                                                                                                                   |
| `type`      | The input type. Any valid HTML standard type is accepted, as well as `textarea` and `select`. This attribute does not affect server-side value parsing.                                                                                                                                                         |
| `text`      | Label text for the input field.                                                                                                                                                                                                                                                                               |
| `value`     | Default value for the input field.                                                                                                                                                                                                                                                                            |
| `food`      | Required for `select` types. It defines the options available and is formatted as an array: `[{value: 1, text: "one"}, {value: 2, text: "two"}, ...]`. This array can be generated via `$controller->makeFood`.                                                                                                  |
| `required`  | Specifies whether the field is required. When set to `true`, the input must be filled before form submission.                                                                                                                                                                                                  |
| `width`     | Defines the width of the form element in 1/12 units of the total width. Effective on medium or larger devices. On small devices, the width is always 100%.                                                                                                                                                      |
| `attributes`| Allows adding additional attributes for the generated input element (e.g., `min`, `max` for number inputs). Example usage: `attributes: {'min': 1, 'max': 10}`.                                                                                                                                                  |



### **Simple input example**
```js
var inputAmount = $form.createElement({
    name: "amount",
    type: "number",
    attributes: {
        min: 0,
        max: 100,
        step: 10
    }
});
```
### **Manipulating fields**

The return value if `form.createField` is the created field. It has an attribute called `input` which can be used to access the dom input element directly. It also has `on` which is an alias for addEventListener on the input dom element. The value can be read and set with `value`.

`form.addCustomHTML()`. With this you can add Html inside of the form.

`form.createSeperator()`. This inserts a simple `<hr>` element at the end of the current builded form.


## Back-end
When the form is submitted, it will send an asynchronous post request to the current action specified by the current users url.
To check in the action if the current request is from a form, [`$req->hasFormData()`](https://zdoc.zierhut-it.de/classes/Request.html#method_hasFormData) can be used. This is example code for handling a form:

### **Backend validation**
```php
if ($req->hasFormData()) {
    $formResult = $req->validateForm([
        (new FormField("email"))
            ->required()->filter(FILTER_VALIDATE_EMAIL)->unique("z_user", "email", "id", $userId),
        (new FormField("languageId"))
            ->required()->exists("z_language", "id")
    ]);

    if ($formResult->hasErrors) {
        return $res->formErrors($formResult->errors);
    }

    $res->updateDatabase(
        "z_user", "id", "i",
        $userId,
        $formResult,
        ["fixed" => $fixedValue]
    );

    return $res->success();
}
```

### **Validation structure**

`$req->hasFormData()` checks if there is any data in the request.

`$req->validateForm()` validates the values.
As the first parameter it takes an array of fields to validate. To these field, rules can be attached.

`$formResult->hasErrors` returns true or false depended on the validation result of `$req->validateForm()`.
If the validation fails, `$res->formErrors($formResult->errors)` will return the errors to the frontend, where they will be displayed.

`$res->success()` will indicate that the saving was successful and a confirmation message will be displayed on the frontend.


### **Saving functions**

Success will exit the current action. So before calling it the data should be processed by a model or `$res->updateDatabase()`.
Update database will take the result object from the form validation to get the names in the database. If the name in the database column and the post differ, the database name can be set by the second parameter of the `constructor of FormField`.

`$res->insertDatabase()` can also be used to process the data. This method will create a dataset in a table given as argument. It works similar to `$res->updateDatabase()`.


### **insertOrUpdateDatabase Example**
`$req->insertOrUpdateDatabase()` Adds a logic to check if the dataset already exists.
```php
$res->insertOrUpdateDatabase(
    "table",
    "PrimaryKeyColumn",
    "i", // pk type
    $primaryKey ?? null,
    $formResult,
);

```

## Example advanced layout
### **Variation of inputs**
```html
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

    var button = form.createField({
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
            attributes: {
            rows: 5,
        }
        value: "Hello o/"
    });

    form.createField({
        name: "file",
        type: "file",
        text: "File upload"
    });

    button.on('click', () => {
        num.value = 4;
    });
</script>
```