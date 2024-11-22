# Auto form validation and database updates
This framework has the ability to create automatic generated forms with user feedback. These forms can be validated automatically on the server and if errors occur the feedback is sent back to the user. If no errors occur the data can be used to update a database table.
## Front-end
To create the form on the frontend, use the Z.js library. An example form can be created with this code:
```javascript
var form = Z.Forms.create({dom: "form"});

var inputFirstName = form.createField({
    name: "first_name",
    type: "text",
    text: "First name",
    required: true
});

var inputLastName = form.createField({
    name: "last_name",
    type: "text",
    text: "Last name",
    required: true
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
| `prepend`| Adds a visual element before the input field. This can be used for prefixes, labels, or other indicators. Example usage: prepend: 'Prefix' creates an input field with a preceding text or symbol. |


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

`form.addSeperator()`. This inserts a simple `<hr>` element at the end of the current builded form.


## Back-end
When the form is submitted, it will send an asynchronous post request to the current action specified by the current users url.
To check in the action if the current request is from a form, [`$req->hasFormData()`](https://zdoc.zierhut-it.de/classes/Request.html#method_hasFormData) can be used. This is example code for handling a form:

### **Backend validation**
```php
if ($req->hasFormData()) {
    $formResult = $req->validateForm([
        (new FormField("first_name"))
            ->required()->length(1, 255),
        (new FormField("last_name"))
            ->required()->length(1, 255)
    ]);

    if ($formResult->hasErrors) {
        return $res->formErrors($formResult->errors);
    }
}
```

### **Validation structure**

`$req->hasFormData()` checks if there is any data in the request.

`$req->validateForm()` validates the values.
As the first parameter it takes an array of fields to validate. To these field, rules can be attached.

`$formResult->hasErrors` returns true or false depended on the validation result of `$req->validateForm()`.
If the validation fails, `$res->formErrors($formResult->errors)` will return the errors to the frontend, where they will be displayed.


### **Saving functions**

Success will exit the current action. So before calling it the data should be processed by a model or `$res->updateDatabase()`.
Update database will take the result object from the form validation to get the names in the database. If the name in the database column and the post differ, the database name can be set by the second parameter of the `constructor of FormField`.

`$res->insertDatabase()` can also be used to process the data. This method will create a dataset in a table given as argument. It works similar to `$res->updateDatabase()`.


### **insertOrUpdateDatabase Example**
`$req->insertOrUpdateDatabase()` Adds a logic to check if the dataset already exists.
```php
public function action_manage(Request $req, Response $res) {
    $req->checkPermission("employee.edit");

    // Get the employeeid from the URL
    $employeeId = $req->getParameters(0, 1);
    $employee = null;

    if(!empty($employeeId)) {
        $employee = $req->getModel("Employee")->getById($employeeId);
    }

    if($req->hasFormData()) {
        $formResult = $req->validateForm([
            (new FormField("first_name"))
                ->required()->length(1, 255),
            (new FormField("last_name"))
                ->required()->length(1, 255),
            (new FormField("contact_email"))
                ->required()->filter(FILTER_VALIDATE_EMAIL)->length(1, 255),
            (new FormField("birthday"))
                ->date(),
            (new FormField("notes")),
            (new FormField("type"))
        ]);

        if ($formResult->hasErrors) {
            return $res->formErrors($formResult->errors);
        }

        // Insert the Employee with their datas or updates them if exist
        $employeeId = $res->insertOrUpdateDatabase(
            "employee",
            "id", "i", $employee["id"] ?? null,
            $formResult,
        );

        // Send a response with the inserted/updated EmployeeId
        return $res->success([
            "employeeId" => $employeeId,
        ]);
    }

    return $res->render("employee/employee_edit.php", [
        "employee" => $employee,
        "types" => $this->makeFood(
            $req->getModel("Employee")->getTypes(),
            "id", "label",
        ),
    ]);
}

```

## Example advanced layout
```js
<div id="form"></div>

<script>
    var form = Z.Forms.create({
        dom: "form"
    });

    form.addCustomHTML("<h2>These Fields are required</h2>");

    form.createField({
        name: "first_name",
        type: "text",
        text: "First name",
        required: true,
        width: 6,
        value: <?= json_encode($opt["employee"]["first_name"] ?? "") ?>,
    });

    form.createField({
        name: "last_name",
        type: "text",
        text: "Last name",
        required: true,
        width: 6,
        value: <?= json_encode($opt["employee"]["last_name"] ?? "") ?>,
    });

    form.createField({
        name: "contact_email",
        type: "email",
        text: "Email Address",
        required: true,
        width: 6,
        value: <?= json_encode($opt["employee"]["contact_email"] ?? "") ?>,
    });

    form.createField({
        name: "type",
        type: "select",
        text: "Type",
        food: "<?= $opt['types'] ?>"
    });

    form.addSeperator();
    form.addCustomHTML("<h2>These Fields are optional</h2>");

    form.createField({
        name: "birthday",
        type: "date",
        text: "Geburtstag",
        width: 6,
        value: <?= json_encode($opt["employee"]["birthday"] ?? "") ?>
    });

    form.createField({
        name: "notes",
        type: "textarea",
        text: "Notizen",
        attributes: {
            rows: 5,
        }
    });

    form.saveHook = (res) => {
        location.href = "<?= $opt["root"] ?>employee/manage/" + res.employeeId;
    };

    $(form.buttonSubmit).html("Send");
</script>
```

## Supported types:
- All default HTML types
- Additionally supported::
    - textarea
    - select
    - autocomplete

### Autocomplete
The `autocomplete` type creates a text input with an additional feature: it displays suggestions based on predefined data as the user types.

#### Example
```js
<div id="form"></div>
<script>
    var form = Z.Forms.create({
        dom: "form"
    });

    form.createField({
        name: 'favoriteFruit',
        type: 'autocomplete',
        autocompleteData: ['Apple', 'Banana', 'Cherry', 'Dragonfruit'],
    });
</script>
```

#### Advanced Example
```php
public function action_favourite(Request $req, Response $res) {
    $value = $req->getPost("value");

    return $res->generateRest([
        "data" => $req->getModel("Fruits")->getByValue($value)
    ]);
}
```

```js
// View
<div id="form"></div>
<script>
    var form = Z.Forms.create({
        dom: "form"
    });

    let placeSearch = form.createField({
        name: "favouriteFruits",
        type: "autocomplete",
        autocompleteData: "fruits/favourite",   // The Endpoint you're trying to get your data from
        autocompleteMinCharacters: 2,           // The number of characters you need to type in to get the first autocomplete data
        autocompleteTextCB: (text, value) => {  // This will be called when autocomplete data is found
            let json = JSON.parse(value);
            return json.text;
        },

        autocompleteCB: (text) => {             // This will be called when you click on the word suggestion
            let json = JSON.parse(text);
            placeSearch.input.value = json.text;
        },
    });
</script>
```

### Select
The `select` type generates a dropdown menu and automatically populates it with options.  
This feature leverages a function called makeFood, which is used in the controllers.

#### Example

```php
// Controller
return $res->render("employee/employee_edit.php", [
    "types" => $this->makeFood(
        $req->getModel("Employee")->getTypes(),
        "id", "label",
    ),
]);
```

```js
// View
<div id="form"></div>
<script>
    var form = Z.Forms.create({
        dom: "form"
    });

    // Manually written food
    form.createField({
        name: 'favoriteFruit',
        type: 'select',
        food: [
            {value: "1", text: "Apple"},
            {value: "2", text: "Banana"},
            {value: "3", text: "Cherry"},
            {value: "4", text: "Dragonfruit"}
        ],
    });

    // Auto generated food
    form.createField({
        name: 'types',
        type: 'select',
        food: <?= $opt["types"] ?>,
    });

    // Advanced Example with optgroups
    form.createField({
        name: 'favoriteFruitVegetables',
        type: 'select',
        food: [
            {type: "optgroup", text: "Vegetables"},
            {value: "1", text: "Carrot"},
            {value: "2", text: "Broccoli"},
            {value: "3", text: "Spinach"},
            {type: "optgroup", text: "Fruits"},
            {value: "4", text: "Apple"},
            {value: "5", text: "Banana"},
            {value: "6", text: "Orange"},
        ],
    });
</script>
```