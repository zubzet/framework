# Auto form validation and database updates
This framework has the ability to create automatic generated forms with user feedback. These forms can be validated automatically on the server and if errors occur the feedback is sent back to the user. If no errors occur the data can be used to update a database table.
## Front-end
To create the form on the frontend, use Z.js, the framework's bundled frontend script. It is served through the [asset proxy](../core-features/asset-proxy.md) and included automatically by the framework's default head component, so the global `Z` object is available in every properly set up [layout](../core-features/layouts.md). An example form can be created with this code:
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

Further options of `Z.Forms.create`:

- `doReload`: reloads the page after a successful submit, default `false` (`true` when `Z.Forms.create()` gets no options object at all), forced to `true` once a [CED](ced-validation.md) is added.
- `saveHook(response)`: called with the parsed response after a `success` only. `formErrorHook(response)` is called after the fields of a `formErrors` response were marked.
- `hidehints`: suppresses the sticky banner above the form that shows the unsaved, saved and error hints. `form.hint(alertClass, content)` shows it manually with a Bootstrap alert class and HTML content, `form.unhint()` hides it.
- `sendOnSubmitClick`: default `true`; with `false` the submit button no longer calls `form.send()`, so you must call it yourself.
- `customEndpoint`: URL the submit button posts to instead of the current action. `form.send(customUrl)` takes the URL directly.

`form.send()` clears the field errors and posts the form data as `multipart/form-data` (a call is ignored while a request runs or within 300 ms of the previous one; `form.debug = true` logs the FormData and the response to the console) and handles the JSON `result` of the response: `success` runs `saveHook`, reloads if `doReload` is set and shows the saved hint, `formErrors` marks the fields (names without a field are ignored, a field with several errors shows the last one) and runs `formErrorHook`, `error` (or a response that is not valid JSON) shows the generic `Z.Lang.saveError` hint, never the `message` sent by `$res->error()`.

`form.createActionButton(text, style, action)` adds a button after the submit button and returns it. `style` has to be a single class such as `btn-secondary`.

### **Creating fields**

| Attribute   | Description                                                                                                                                                                                                                                                                                                    |
|-------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `name`      | Corresponds to the name in the post request.                                                                                                                                                                                                                                                                   |
| `type`      | The input type. Any valid [HTML standard type](https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Elements/input) is accepted, as well as `textarea`, `select`, `multi-select`, `autocomplete`, `button` and `hidden`. Without a type the input gets an invalid type attribute. This attribute does not affect server-side value parsing.                                                                                                                       |
| `text`      | Label HTML, default a non-breaking space.                                                                                                                                                                                                                                                                               |
| `value`     | Initial value, used over `default` when truthy (`0` or an empty string falls back to it), and the label of a `button` (full width, class from `style`, default `btn-primary`). For `multi-select`, pass an array (e.g. `["1","4"]`) or `<?= json_encode([...]) ?>`.                                                                                                                                                                                       |
| `food`      | Required for `select` and `multi-select` types. It defines the options available and is formatted as an array: `[{value: 1, text: "one"}, {value: 2, text: "two"}, ...]`, where `value` falls back to `text` and an entry with `type: "optgroup"` starts a group. This array can be generated via [`$controller->makeFood`](../api/classes/ZubZet-Framework-Core-Controller.html#method_makeFood).                                                                              |
| `required`  | Marks the field as required in the UI only: a red asterisk and the class `input-required` are added to the label, and a `select` drops its empty `---` option. No client-side validation is performed and the browser's `required` attribute is not set. Enforce it on the server with `->required()`. |
| `width`     | Defines the width of the form element in 1/12 units of the total width (`col-md-N`, default 12, `0` for `hidden`); a field that no longer fits into 12 units starts a new row. Effective on medium or larger devices. On small devices, the width is always 100%.                                                                                                                                                      |
| `attributes`| Allows adding additional attributes for the generated input element (e.g., `min`, `max` for number inputs), set after the defaults so they can override them. Example usage: `attributes: {'min': 1, 'max': 10}`.                                                                                                                                                  |
| `prepend`| Adds HTML in an `input-group-prepend` before the input field. This can be used for prefixes, labels, or other indicators. Example usage: prepend: 'Prefix' creates an input field with a preceding text or symbol. |
| `hint`| Muted help text as HTML under the input. |
| `placeholder`| Input placeholder; for `multi-select` the text of the first option, for `file` the text before a file is chosen is `customFileInputText` (default `Z.Lang.choose_file`). |
| `default`| Value restored by `field.reset()`, also the initial value when `value` is empty. |
| `autofill`| When false (default) every input gets `autocomplete="new-password"` to suppress browser autofill. |
| `disabled`| Starts the field disabled. The input is greyed out and cannot be edited until `field.enable()` is called. |
| `hidden`| Starts the field hidden. It is left out of the layout until `field.show()` is called. The field still exists and submits its value. |


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

The return value if `form.createField` is the created field. It has the attributes `input` (the dom input element), `label` and `dom` (the wrapper around label, input and error text). It also has `on` which is an alias for addEventListener on the input dom element. The value can be read and set with `value`: a `multi-select` returns a copy of its array, a `checkbox` a boolean, and the setter of a `file` field only changes the label text. `field.markInvalid(error)` and `field.markValid()` show or clear the `Z.Lang.error_<type>` message and the `is-invalid` class, and `markInvalid` scrolls the field into view.

`form.addCustomHTML()`. With this you can add Html inside of the form, in its own `div`; the next field starts a new row.

#### Escaping untrusted strings

Several strings are inserted into the page as HTML (`innerHTML`), not as plain text. Escape anything that is not hard-coded, such as user input or database content, before you pass it in, otherwise it is an XSS hole. In PHP use `htmlspecialchars()` or `e()` before dumping the value into the script.

Inserted as HTML, escape these:

- `text` (the label) and `hint` of a field
- `prepend`
- the `value` of a `button` field and the `text` of `createActionButton()`
- `form.hint(alertClass, content)`
- `form.addCustomHTML(html)`
- the suggestions of an `autocomplete` field (`autocompleteData` entries and the return value of `autocompleteTextCB`)

Inserted as plain text, no escaping needed: the `text` of `select` and `multi-select` options and the text of `multi-select` badges.

`form.addSeperator()`. This inserts a simple `<hr>` element at the end of the current builded form and starts a new row. `form.createEmpty(size = 12)` adds an empty spacer of that width the same way.

### **Disabling fields**

A field can be disabled and re-enabled at runtime. A disabled field is greyed out and cannot be edited, but still submits its value.

```js
field.disable();        // disable a single field
field.enable();         // re-enable it
field.isDisabled();     // true while disabled, also while its form is

form.disable();         // disable the whole form (all fields + submit button)
form.enable();          // re-enable it
```

`form.disable()` and a field's own `field.disable()` are independent: re-enabling the form does not re-enable a field that was disabled on its own. While a form is submitting it disables itself automatically (`form.isDisabled()` is true meanwhile) and restores the previous state when the request finishes.

### **Showing and hiding fields**

Fields can be removed from and added back to the layout at runtime. A hidden field still exists and submits its value — it is just not rendered.

```js
field.hide();           // remove from the layout
field.show();           // add it back in its original position
field.isHidden();       // true while hidden
```

Custom HTML (`addCustomHTML`) and separators (`addSeperator`) keep their place when fields are shown or hidden. Do not mix `show()` / `hide()` with manual DOM manipulation of a field's wrapper (e.g. `$(field.dom).parent().hide()`) — the two will fight over the layout.

The built-in submit button can be hidden too — useful for live forms that have no submit:

```js
form.hideSubmit();
form.showSubmit();
```

### **Reading and writing all values**

```js
form.getValues();                       // { fieldName: value, ... }
form.setValues({ first_name: "Ada" });  // set the named fields
form.setValues(data, { resetUnknown: true }); // reset the whole form first
```

[CED](ced-validation.md) fields are skipped by `getValues` / `setValues`; their data round-trips through the normal submit instead.

`form.reset()` calls `field.reset()` on every field: back to `default` or empty (`false` for a checkbox, `[]` for a `multi-select`), select options stay, CEDs are not reset.

Keys passed to `setValues` that don't match any field are kept on `form.meta` and returned by `getValues`, but are never submitted to the backend. This lets a value like an id ride along with the form data:

```js
form.setValues({ first_name: "Ada", id: 42 }); // id has no field -> stored as meta
form.getValues();                               // { first_name: "Ada", id: 42 }
form.meta;                                      // { id: 42 }   (inspectable)
```

### **Live / client-only forms**

With `collectOnly: true` the form never posts to the backend. Clicking the submit button hands the collected values (`getValues()`, meta included) straight to `saveHook` instead. Combined with `inputHook` — called with `getValues()` on every change — the form becomes a live value source you can wire into the page without a round-trip.

```js
var form = Z.Forms.create({
    dom: "form",
    collectOnly: true,                  // submit -> saveHook(getValues()), no POST
    saveHook: (values) => { /* values incl. meta */ },
    inputHook: (values) => { /* fires on every keystroke / select change */ },
});
```


## Back-end
When the form is submitted, it will send an asynchronous post request to the current action specified by the current users url.
To check in the action if the current request is from a form, [`$req->hasFormData()`](../api/classes/ZubZet-Framework-Message-Request.html#method_hasFormData) can be used. This is example code for handling a form:

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

`$req->hasFormData()` checks if the POST data contains the `isFormData` marker, which Z.js forms always send.

[`$req->validateForm()`](../api/classes/ZubZet-Framework-Form-Validation-CanValidateForm.html#method_validateForm) validates the values.
As the first parameter it takes an array of fields to validate. To these field, rules can be attached. `FormField` and `FormResult` are global aliases of `Form\Validation\Field` and `Form\Validation\Result`, `z_upload` of `Form\Upload`.

Without the optional second parameter `$data` (an empty array counts as without) the input is `array_merge(getPost(), getFiles())`, so uploaded files override same-named POST keys. Every rule except `required` only runs for a set, non-empty value or the string `0`; otherwise the field value becomes `null` (a field without any rule keeps its raw value, so an empty string stays one). Rules run in the order they are chained and every failing rule adds its own error, so one field can report several.

- `->required()` fails with `required` when the input is unset or an empty string and no uploaded file exists under that name.
- `->length($min, $max)` has inclusive bounds and counts bytes (`strlen`); the error carries `info` `[min, max]`, like `->range()`.
- `->filter($filter)` fails when `filter_var($value, $filter)` is falsy, so only the filter id works (no options or flags) and a valid value that filters to a falsy result, such as `0` with `FILTER_VALIDATE_INT`, fails too.
- `->integer()` fails unless `filter_var($value, FILTER_VALIDATE_INT)` is not `false` (`0` passes) and sets the bind type to `i`. It also replaces the field value by `intval($value)`, even when validation failed, and only when it is the last rule. It does not accept arrays.
- `->range($min, $max)` fails when `$value < $min || $value > $max`; it does not check that the value is numeric.
- `->unique($table, $field, $ignoreField, $ignoreValue)` fails with `unique` when the value is already in `$table.$field`; the names are only backtick-wrapped and the value is bound as a string, so never take `$table` or `$field` from user input. On edit forms pass the primary key column and the current id as the ignore pair, so the edited row does not collide with itself.

`$formResult->hasErrors` returns true or false depended on the validation result of `$req->validateForm()`.
If the validation fails, [`$res->formErrors($formResult->errors)`](../api/classes/ZubZet-Framework-Message-Response.html#method_formErrors) will return the errors to the frontend, where they will be displayed; it merges every array argument and exits. Each error is `["name" => ..., "type" => ...]`, plus `info` for `length` and `range`. `$formResult->addCustomError($name, $type)` appends your own (any `$type`; the client shows `Z.Lang.error_<type>`) and `$formResult->getValue($name)` returns a validated value, or `null` for an unknown field.

### **Rules on array values (multi-select)**

A `multi-select` field's value arrives at the server as a real PHP array. Three existing rules are *list-aware* — they adapt automatically when the field value is an array — and one new rule (`->in()`) covers the in-memory allow-list case:

- `->length($min, $max)` — `strlen()` for scalars; **`count()`** for arrays. So `->length(1, 3)` on a multi-select means "between 1 and 3 selections."
- `->regex($pattern, $exceptions)` — the `$exceptions` strings and then every match of `$pattern` are removed from the value, and the field fails when text remains. So the pattern describes the allowed characters (`/[A-Za-z ]/`), and a full-match pattern like `/^[a-z]+$/` works too. Runs against each item; the field fails as soon as any item fails. A value of `0`, or one that is empty after the exceptions are removed, always fails.
- `->exists("table", "field")` — applied per item: every picked entry must exist as a row's `field` in `table`. A miss reports the error type `exist` (not `exists`).
- `->in($allowedValues)` — the value (or each item, for arrays) must appear in the given in-memory allow-list (non-strict `in_array`). No DB query. Use this to guard `select` / `multi-select` fields against tampered POST payloads where the client sent an option that wasn't in the rendered dropdown. Works on plain `select` (single-value) and `multi-select` (per-item).

```php
$formResult = $req->validateForm([
    (new FormField("skills"))
        ->required()
        ->length(1, 5)
        ->in($availableSkillIds),   // every picked id must be in the rendered list
]);
```

`filter`, `unique`, `integer`, `range` and `date` are scalar-only and do not support array values; use `in()` instead.

### **The date rule**

`->date($format = "Y-m-d")` fails with the error type `date` only when `strtotime($value)` is falsy. The `$format` argument is stored with the rule but never applied, so it does not restrict the input. Any string `strtotime()` can parse passes, for example `31.12.2024`, `2024-02-31` or `tomorrow`, while `banana` or `2024-13-45` fail. Because the check is a loose comparison, a value that parses to the Unix timestamp `0` (`1970-01-01 00:00:00 UTC`) fails as well. The rule does not accept array values.

### **Saving functions**

Success will exit the current action. So before calling it the data should be processed by a model or [`$res->updateDatabase()`](../api/classes/ZubZet-Framework-Message-Response.html#method_updateDatabase).
Update database will take the result object from the form validation to get the names in the database; it updates the row where `$pkField` equals `$pkValue` (bound with the type `$pkType`, a field or `$fixed` entry that is the primary key with that same value is not written) and returns nothing. If the name in the database column and the post differ, the database name can be set by the second parameter of the [`constructor of FormField`](../api/classes/ZubZet-Framework-Form-Validation-Field.html#method___construct), which is stored in the public property `dbField` (default: the post name).

[`$res->insertDatabase()`](../api/classes/ZubZet-Framework-Message-Response.html#method_insertDatabase) can also be used to process the data. This method will create a dataset in a table given as argument. It works similar to `$res->updateDatabase()`; both write the other fields to their `dbField` columns using the field `dataType`, skip fields flagged `noSave`, take extra columns from the optional `$fixed` array (bound as strings), and `insertDatabase()` returns the insert id. Table and column names are only backtick-wrapped, so never take them from user input.


### **insertOrUpdateDatabase Example**
[`$res->insertOrUpdateDatabase()`](../api/classes/ZubZet-Framework-Message-Response.html#method_insertOrUpdateDatabase) selects the row by its primary key: it updates it and returns `$pkValue` if found, otherwise it inserts and returns the insert id.
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

    return $res->render("employee/employee_edit", [
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
- Specially rendered:
    - checkbox

### Checkbox
The `checkbox` type renders with a [Bootstrap](https://getbootstrap.com/docs/4.6/components/forms/) `form-check` layout: the box sits to the left of its label, and clicking the label toggles the box. `text` becomes the label.

```js
form.createField({
    name: "send_notifications",
    type: "checkbox",
    text: "Send me notifications",
    default: true,         // optional; start checked
});
```

Value semantics differ from text inputs:

- `field.value` is a **boolean** (the checked state). Assigning accepts `true`/`false`, `1`/`0`, `"1"`/`"0"`, `"true"`/`"false"`, or `"on"`.
- On submit the box **always** sends its state — `1` when ticked, `0` when not. (Unlike a native HTML checkbox it is never omitted.) This means `updateDatabase` / `insertDatabase` write a clean `0`/`1` to a binary column, so toggling a setting **off** persists just like toggling it on.

Because a checkbox always submits a value, `->required()` is effectively a no-op on it. To enforce that a box must be ticked (e.g. accept-terms), use **`->checked()`**:

```php
$formResult = $req->validateForm([
    (new FormField("accept_terms"))
        ->checked(),   // errors unless the box is ticked
]);
```

`->checked()` is sugar over `->in(["1", "true", "on"])` — the box's value must be a ticked representation, otherwise it fails with the error type `in`.

`->checked()` does **not** imply `->required()`. Like every rule except `required`, it only runs when the field is present and not empty, so a request that omits the field entirely (or sends an empty string) passes without an error. The bundled checkbox always submits `0` or `1`, but a hand-made request can leave the field out. To reject that too, chain both: `->required()->checked()`.

### Autocomplete
The `autocomplete` type creates a text input that, from `autocompleteMinCharacters` (default 2) typed characters on, displays the entries of `autocompleteData` as suggestions: an array of strings filtered by case-insensitive substring match, or a backend path.

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

Every key press posts `value` with `action=autocomplete` to the path and renders the returned `data` array as soon as it arrives. `autocompleteTextCB(html, entry)` returns the HTML of a list item from the highlighted text and the raw entry, `autocompleteCB(entry)` runs when a suggestion is picked (which also fills the input), and Escape or a click anywhere closes the list.

The suggestion list carries the term it shows suggestions for in `data-search-term`, set once the endpoint's answer for that term has been rendered and removed when the list is cleared. Tests and scripts can wait on it, e.g. `[data-search-term="Apple"] button`.

### Select
The `select` type generates a dropdown menu and automatically populates it with options.  
This feature leverages a function called makeFood, which is used in the controllers. It turns database rows into `{value, text}` entries and appends `$optionalTextField` to the text after a space. Until the options arrive the select holds a disabled `---` option; `field.feedData(food, clear = true)` replaces (or with `clear = false` appends) the options later and only works on `select` and `multi-select`. A clearing feed leaves a non-required select with an enabled empty `---` option (a required one with none), also drops the selection and option maps of a `multi-select`, and re-applies the initial `value` option (not `default`) afterwards.

`makeFood` does not HTML-escape the text. It does not need to: it returns JSON encoded with `JSON_HEX_TAG | JSON_HEX_AMP`, so the value is safe to print inside a `<script>` block, and Z.js inserts the option text with `textContent`, so markup in it is shown literally and never executed.

#### Example

```php
// Controller
return $res->render("employee/employee_edit", [
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
The `multi-select` type behaves like `select` but lets the user pick more than one value. Picked entries appear as badges under the dropdown; a click removes one (unless the field is disabled) and fires a bubbling `input` event on the select. The picked option is hidden from the dropdown until then, and picking a value twice is ignored. Uses the same `food` shape (and same `makeFood` helper) as `select`.

#### Value semantics
- `field.value` returns the picked entries as a **JavaScript array** of values.
- Assigning `field.value = ["a", "b"]` replaces the selection (items become strings). Non-array input is treated as an empty selection. `field.addSelectedValue(value)` and `field.clearSelectedValues()` change it step by step.
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
- `{value}` alone — value is also used as the label (`multi-select` only; a plain `select` shows `undefined`).
- `{text}` alone — text doubles as the value.
- `{type: "optgroup", text}` — groups the options that follow (until the next optgroup) under an `<optgroup>` label. Optgroups are collapsed automatically when every option below them has been picked, and come back when one is removed.

#### Example

```php
// Controller
return $res->render("project/manage", [
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