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
| `type`      | The input type. Any valid HTML standard type is accepted, as well as `textarea`, `select`, `multi-select`, and `autocomplete`. This attribute does not affect server-side value parsing.                                                                                                                       |
| `text`      | Label text for the input field.                                                                                                                                                                                                                                                                               |
| `value`     | Default value for the input field. For `multi-select`, pass an array (e.g. `["1","4"]`) or `<?= json_encode([...]) ?>`.                                                                                                                                                                                       |
| `food`      | Required for `select` and `multi-select` types. It defines the options available and is formatted as an array: `[{value: 1, text: "one"}, {value: 2, text: "two"}, ...]`. This array can be generated via `$controller->makeFood`.                                                                              |
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

### **Rules on array values (multi-select)**

A `multi-select` field's value arrives at the server as a real PHP array. Three existing rules are *list-aware* — they adapt automatically when the field value is an array — and one new rule (`->in()`) covers the in-memory allow-list case:

- `->length($min, $max)` — `strlen()` for scalars; **`count()`** for arrays. So `->length(1, 3)` on a multi-select means "between 1 and 3 selections."
- `->regex($pattern, $exceptions)` — runs the regex against each item; the field fails as soon as any item fails.
- `->exists("table", "field")` — applied per item: every picked entry must exist as a row's `field` in `table`.
- `->in($allowedValues)` — the value (or each item, for arrays) must appear in the given in-memory allow-list. No DB query. Use this to guard `select` / `multi-select` fields against tampered POST payloads where the client sent an option that wasn't in the rendered dropdown. Works on plain `select` (single-value) and `multi-select` (per-item).

```php
$formResult = $req->validateForm([
    (new FormField("skills"))
        ->required()
        ->length(1, 5)
        ->in($availableSkillIds),   // every picked id must be in the rendered list
]);
```

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
    - multi-select
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

### Multi Select
The `multi-select` type behaves like `select` but lets the user pick more than one value. Picked entries appear as removable badges under the dropdown and the picked option is hidden from the dropdown until the badge is removed. Uses the same `food` shape (and same `makeFood` helper) as `select`.

#### Value semantics
- `field.value` returns the picked entries as a **JavaScript array** of values.
- Assigning `field.value = ["a", "b"]` replaces the selection. Non-array input is treated as an empty selection.
- On submit, each pick is sent as a native PHP array entry (`name[]=a&name[]=b`), so `$_POST[name]` arrives as a real **PHP array** — no `json_decode` needed in user code. An **empty** multi-select is omitted from the POST entirely — just like an unchecked checkbox — so the existing `->required()` rule (`isset($_POST[$name])`) catches it without any special-casing.
- `insertDatabase` / `updateDatabase` automatically `json_encode` array field values before binding, so a `JSON` (or `TEXT`/`VARCHAR`) column receives a real JSON string. User code never has to encode manually.
- Pre-fill from PHP with either an inline JS literal or `json_encode` dumped directly (no surrounding quotes):

```php
form.createField({
    name: "skills",
    type: "multi-select",
    food: <?= $opt["skillsFood"] ?>,
    value: <?= json_encode($opt["employee"]["skills"] ?? []) ?>,
});
```

#### Food shapes
- `{value, text}` — value is what gets posted, text is what's shown in the dropdown and the badge.
- `{value}` alone — value is also used as the label.
- `{text}` alone — text doubles as the value.
- `{type: "optgroup", text}` — groups the options that follow (until the next optgroup) under an `<optgroup>` label. Optgroups are collapsed automatically when every option below them has been picked, and come back when one is removed.

#### Example

```php
// Controller
return $res->render("project/manage.php", [
    "skills" => $this->makeFood(
        $req->getModel("Skill")->getAll(),
        "id", "name",
    ),
]);
```

```js
// View
<div id="form"></div>
<script>
    var form = Z.Forms.create({ dom: "form" });

    form.createField({
        name: "skills",
        type: "multi-select",
        text: "Skills",
        placeholder: "Add a skill...",   // replaces the default "---"
        food: <?= $opt["skills"] ?>,
        required: true,                  // empty selection fails ->required()
        default: ["1"],                  // pre-fill; reset() returns to this
    });
</script>
```

#### Backend
```php
$formResult = $req->validateForm([
    (new FormField("skills", "skills_json"))
        ->required(),
]);

if ($formResult->hasErrors) {
    return $res->formErrors($formResult->errors);
}

$res->insertDatabase("project", $formResult);
```

`$req->getPost("skills")` and `$formResult->fields[0]->value` both come back as real PHP arrays (e.g. `["1", "4"]`). `insertDatabase` then auto-`json_encode`s the array before binding, so the column stores a JSON string (`["1","4"]`) — a `JSON`, `TEXT`, or `VARCHAR` column all work. Nothing in user code needs to special-case the multi-select.