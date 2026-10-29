# Auto form validation using CED
Some forms require the user to input multiple values into one field. Or to create multiple blocks or something. For every case this happens the CED system can be used. CED stands for **C**reate **E**dit **D**elete. It builds on top of the [form system](auto-form-validation.md).

## Front-end
Like the rest of the form system, the frontend is provided by the bundled [Z.js](auto-form-validation.md#front-end) script, which exposes the global `Z` object used below.

This is an example for adding permissions to a group. It is possible to have n permissions per group.
```js
var form = Z.Forms.create({dom: "form"});

form.createCED({
    name: "permissions",
    text: "Permissions",
    fields: [{ 
        name: "name", 
        type: "text", 
        text: "Permission"
    }],
    value: <?php echo $opt["permissions"]; ?>
});
```
`form.createCED` is a method from ZForm. It adds the new CED to the form and returns it. `name` is the POST array name that `validateCED` and `doCED` read, `text` the label (HTML). `fields` is required and takes an array of form field parameters that every item repeats; all form fields are build like in `form.createField`.

`value` takes the initial rows as objects with `dbId` plus values keyed by field name (a key without a matching field throws), as created by [`$controller->makeCEDFood()`](../api/classes/ZubZet-Framework-Core-Controller.html#method_makeCEDFood), which takes `dbId` from the `id` column and returns a JavaScript array literal with a trailing comma, not strict JSON. It does not escape the values unless you pass an `$escape` callable `fn($value, $field)` as third argument.

To create more compact forms, the attribute `compact` can be set to true. On form fields it will hide the label. In CED's it renders the items as bootstrap rows instead of cards and puts the remove button at the right. When using the compact mode, the inputs should have a total combined length of 11 units because the remove button will take exactly one. `smallButton` puts the remove button inside the input area. `deleteHook` is called with the item after the user removes it.

The returned `ZCED` has `createItem()`, `addItem(item)`, `on(type, handler)`, `markInvalid(error)`, `markValid()`, `getFormData(data)` and `getPostString()`; its only event `change` fires without arguments when an item is created or removed or a field input changes. Each item is posted as `name[i][Z]` (`create`, `edit` or `delete`), `name[i][dbId]` for saved rows and `name[i][field]` per value, so `Z` and `dbId` are reserved field names.
## Back-end
The backend code for the example mentioned before:
```php
if ($req->hasFormData()) {

    $formResult = $req->validateCED("permissions", [
        (new FormField("name")) -> required() -> length(3, 100)
    ]);
    
    if ($formResult->hasErrors) {
        $res->formErrors($formResult->errors);
    } else {
        $res->doCED("z_role_permission", $formResult, ["role" => $roleId]);
        $res->success();
    }
}
```
[`$req->validateCED`](../api/classes/ZubZet-Framework-Form-Validation-CanValidateMultiForm.html#method_validateCED) takes the name of the CED field at the frontend as the first parameter. The second parameter is equal to the ruleset in `$req->validateForm`. The difference here is that the rules will be applied for all items in the ced. The return value can also be used like of the normal `validateForm` method for error reporting; each error gets `name` set to the CED name, `subname` to the field name and `index` to the entry key, which no longer matches the item position the frontend marks once an unsaved row was removed.

Instead of `$res->updateDatabase`, CED's use [`$res->doCED`](../api/classes/ZubZet-Framework-Message-Response.html#method_doCED) to update the database. The first parameter is the name of the table. It is important that the table has an `id` primary key and a field named `active`. This will be used to deterime if a dataset was removed. The other fields should have the same names as the subinputs of the form, because `doCED` uses the field `name` as column name (not `dbField`). Each posted entry is handled by its `Z` value: `create` inserts a row, `edit` updates the row with the `dbId`, `delete` sets `active = 0` for it, and a missing `dbId` on `edit` or any other `Z` value ends with the generic `error()`. `doCED` writes the raw posted values, not the validated ones, runs without a transaction (a failure midway leaves the earlier items applied), and does nothing when the CED field was not posted as an array (`doNothing` is set), and table and field names are inserted into the SQL unescaped, so they must never come from user input.

The optional third parameter `$fix` is an array of column/value pairs that is added to every `create` (insert) only, for example the id of the parent record. It is not applied to `edit` or `delete`. Those two operations match rows solely by `WHERE id = ?` with the `dbId` sent by the client; `$fix` is not part of the condition, and neither is the `active` column. There is no ownership check: a client can post any `dbId` and edit or deactivate rows of that table that belong to another parent or user. `doCED` therefore must not be used on its own for tables with tenant or user separation. Verify before calling it that every posted `dbId` belongs to the current user or parent, for example by loading the permitted ids and rejecting the request if one is missing.
