# Auto form validation using CED
Some forms require the user to input multiple values into one field. Or to create multiple blocks or something. For every case this happens the CED system can be used. CED stands for **C**reate **E**dit **D**elete. It builds on top of the form system.

## Front-end
This is an example for adding permissions to a group. It is possible to have n permissions per group.
```js
var form = Z.Forms.create({dom: "form"});

form.createCED({
    name: "permissions",
    text: "<?php $opt["lang"]("permissions"); ?>",
    fields: [{ 
        name: "name", 
        type: "text", 
        text: "<?php $opt["lang"]("permission"); ?>"
    }],
    value: <?php echo $opt["permissions"]; ?>
});
```
`form.createCED` is a method from ZForm. It takes the same parameters as `form.createField`. The only special one is `fields`. `fields` takes an array of form field parameters. All form fields are build like in `form.createField`.

`value` takes an value created by `$controller->makeCEDFood()`.

To create more compact forms, the attribute `compact` can be set to true. On form fields it will hide the label. In CED's it allows to have the remove button at the right. When using the compact mode, the inputs should have a total combined length of 11 units because the remove button will take exactly one.
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
`$req->validateCED` takes the name of the CED field at the frontend as the first parameter. The second parameter is equal to the ruleset in `$req->validateForm`. The difference here is that the rules will be applied for all items in the ced. The return value can also be used like of the normal `validateForm` method for error reporting.

Instead of `$res->updateDatabase`, CED's use `$res->doCED` to update the database. The first parameter is the name of the table. It is important that the table has a field named `active`. This will be used to deterime if a dataset was removed. The other fields should have the same names as the subinputs of the form.