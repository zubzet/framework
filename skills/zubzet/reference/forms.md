# Forms and client to server communication

Framework docs: `vendor/zubzet/framework/docs/forms/auto-form-validation.md`, `forms/ced-validation.md`, `forms/file-uploads.md`, `frontend-integration/backend-requests.md`.

## The priority order

1. **Z.Forms** (`Z.Forms.create` + `form.createField`) for anything that collects input. Multi field records, single field dialogs, file uploads, settings, filters that persist. This is the default and the first thing to reach for.
2. **`Z.Request.action`** for a button that triggers one operation on an existing record: accept, delete, toggle hidden, set state, link a file.
3. **Custom elements** (hand written inputs, contenteditable, drag and drop, maps) only when Z.Forms cannot express the interaction. Even then, keep a Z.Forms form on the page with a hidden field and mirror the custom value into it, so validation, submit, error display and tests stay standard.

Known agent failure: building everything with `Z.Request.action` and hand written inputs. Do not. If the page saves a record, it is a Z.Forms form.

## Z.Forms, front end

```html
<div class="card shadow-sm mt-4 mb-5">
    <div class="card-body py-5">
        <div id="form" data-test="form"></div>
    </div>
</div>

<script>
    const form = Z.Forms.create({
        dom: "form",
        doReload: true,
    });

    form.createField({
        name: "company",
        value: <?= json_encode($position["company"]) ?>,
        type: "text",
        text: "Unternehmen",
        required: true,
        width: 6,
    });

    form.createField({
        name: "work_model",
        type: "select",
        text: "Arbeitsmodell",
        food: <?= $workModels ?>,
        required: true,
        width: 6,
    });

    form.addSeperator();

    form.createField({
        name: "must_have_skills",
        value: <?= json_encode($position["must_have_skills"]) ?>,
        type: "textarea",
        text: "Must-have Skills",
        required: true,
        attributes: { rows: 4 },
        placeholder: "z.B. SAP FI/CO, Power BI ...",
    });

    form.saveHook = (res) => {
        location.href = "<?= $opt["root"] ?>position/details/" + res.positionUuid;
    };
</script>
```

Field types, options, select `food`, pre-filling, grouping, CED and the live form API are documented in `docs/forms/auto-form-validation.md` and `docs/forms/ced-validation.md`; read them before guessing an option name. The design rules on top:

- The form renders into an empty `<div id="...">` that carries the `data-test`. Select options come from the controller (`makeFood`), never hardcoded in the view when they exist in a table.
- Read only mode disables the form or hides its submit; do not re-implement the form as static markup.
- `doReload: true` when the same page shows the saved data; `saveHook` when the user should move on.
- CED for n child rows of one record (permissions of a role, line items), not a hand built row editor.
- Client only inputs that never post (a search box over rendered cards, a sort toggle) may be plain Bootstrap inputs; `collectOnly` is for a filter set large enough to be a form.
- Toggle fields through the field API (`show`, `hide`, `enable`, `disable`), never through jQuery on the field wrapper: the two fight over the layout.
- An `autocomplete` field with a backend source calls an `action_*` of the stack; that action is the one place a page answers with `$res->success(["data" => ...])` to something other than Z.Forms or `Z.Request.action`.

## Z.Forms, back end

```php
if($req->hasFormData()) {
    $formResult = $req->validateForm([
        (new \FormField("company"))
            ->required()->length(1, 255),
        (new \FormField("location_zip"))
            ->length(3, 6),
        (new \FormField("location_radius_km"))
            ->filter(FILTER_VALIDATE_INT),
        (new \FormField("work_model"))
            ->required()->in(array_keys($workModels)),
        (new \FormField("contact_file"))
            ->file(FILE_SIZE_10MB, ["csv"]),
    ]);

    if($formResult->hasErrors) {
        return $res->formErrors($formResult->errors);
    }

    $briefingId = $res->insertOrUpdateDatabase(
        "position_briefing",
        "id", "i", $position["briefing_id"] ?? null,
        $formResult,
        ["positionId" => $position["id"]],
    );

    return $res->success(["briefingId" => $briefingId]);
}
```

- One `FormField` per input, rules chained on the next line. The field name equals the column name; if it differs, pass the column as the second constructor argument.
- The rules (`required`, `length`, `in`, `exists`, `unique`, `integer`, `range`, `date`, `file`, `regex`, ...) and what each one does are in `docs/forms/auto-form-validation.md`; look a rule up there instead of guessing its name or arguments. Custom checks after `validateForm` go through `$formResult->errors[] = ...` and `formErrors`, not through ad hoc `success(["error" => ...])`.
- Every `select` and `multi-select` carries `->in(...)` or `->exists(...)`: the browser can post any value, the rendered dropdown is not a guard.
- A box that must be ticked uses `checked()`, since a checkbox always posts a value and `required()` cannot tell the states apart. `required()` comes first in a chain.
- Conditional rules (a premium plan needs extra fields) are built by composing arrays of `FormField` objects before `validateForm`.
- Persist with `insertOrUpdateDatabase` / `updateDatabase` / `insertDatabase` plus a `$fixed` array for columns the user did not send (`positionId`, `userId`). When the write needs more than that, call a model method with `$formResult->fields[...]->value` or `$req->getPost()`.
- `return $res->success([...])` ends the branch. `success` exits the request.

Details that cost debugging time:

- **Several Z.Forms on one page**: each gets its own `dom` id (`form-<uuid>`), and each `<script>` wraps its code in a block `{ ... }`, otherwise the second `const form` throws a redeclaration error.
- **A form inside a partial that renders on several URLs** gets its own receiving action and posts there with `customEndpoint`. The validation is then written once, instead of in every page action that happens to show the partial.

## Z.Request.action for buttons

```html
<button class="btn btn-sm btn-primary btn-accept" data-position-uuid="<?= $position["uuid"] ?>" data-test="btn-accept-position">
    <i class="fa fa-fw fa-check"></i>
    Akzeptieren
</button>

<script>
    $(".btn-accept").click((e) => {
        Z.Request.action("accept-position", {
            positionUuid: $(e.currentTarget).data("position-uuid"),
        }, (res) => {
            location.reload();
        });
    });
</script>
```

```php
if($req->isAction("accept-position")) {
    model("Position")->accept($req->getPost("positionUuid"));
    return $res->success();
}
```

- The browser only ever sends uuids. The model method takes the uuid, and its `UPDATE ... WHERE uuid = ? AND userId = ?` is the ownership check ([database.md](database.md)).

- Action names are kebab-case verbs: `accept-position`, `set-state`, `link-files`, `delete-position`.
- Booleans arrive as strings; convert with `filter_var($x, FILTER_VALIDATE_BOOLEAN)`. Trim free text with `trim((string) $x)`.
- The action handles one record and one operation. If it starts taking five fields, it is a form.

## Not allowed for the stack's own UI

- `$res->json()` endpoints, `generateRest`, REST style routes, `fetch()` by hand, hand rolled `XMLHttpRequest`. These exist for external consumers (see Routers in `controllers.md`). A special case in the stack's own UI (a chart that loads a large dataset lazily) can use one, but it is very rarely the right choice; ask first. A separate SPA frontend with a REST backend is a different kind of project, not a template for stacks.
- Native `<form method="post">` submits. There is basically never a good use for them; everything goes through Z.js.
- Query string state for filters is fine (mirror filter selections into the URL with `history.replaceState`), but filtering itself happens either in SQL (model) or in the browser on already rendered cards.

## Errors

- Input problems: `FormField` rules and `$res->formErrors()`. Z.Forms shows them inline.
- Process failures the user can fix: the model throws `\Exception("Die CSV-Datei enthält keine Daten.")`, the controller catches and returns `$res->success(["error" => $e->getMessage()])` or `$res->error($e->getMessage())`; the JS decides how to show it. Both are acceptable. Exceptions are not the common path, use them where the alternative is an `if` ladder.
- Unexpected failures: let them bubble. The framework logs and renders the error page. Do not wrap everything in try/catch.
- Logging: `logger()->warning("csvImportFailed", ["positionId" => $id])` with a short camelCase event name and context array. Not for normal flow.
- Permission failures are handled by `checkPermission` (403 page or `generateRestError`). Never render a custom "forbidden" page.
