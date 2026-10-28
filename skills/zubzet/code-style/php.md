# PHP style

General rules (whitespace, casing, names, comments) are in [index.md](index.md); this file is only the PHP specific part. SQL inside a PHP string follows [sql.md](sql.md).

**Views are not covered here.** A view is a PHP file, but it follows [html.md](html.md): no class skeleton, markup with inline PHP, one tag per line. The rules below apply to controllers, models, `App\` classes and PHP migrations. The only PHP rules that carry into a view are quotes, spacing around operators and the one-liner form of inline PHP, and html.md states them again where they matter.

The example is the shape of every action as well (the five numbered steps, explained in `reference/controllers.md`), so it is shown once, here:

```php
<?php

    class PositionController extends z_controller {

        public function action_details(Request $req, Response $res) {
            // 1. Permission, always the first statement
            $req->checkPermission("customer.dashboard");

            // 2. Load what every branch needs; only support sees hidden positions
            $position = model("Position")->get($req->getParameters(0, 1));
            if(empty($position)) return $res->reroute(["position"]);

            $canViewAny = $req->checkPermission("support", true);
            $isHidden = 1 == $position["is_hidden"];
            if($isHidden && !$canViewAny) return $res->reroute(["position"]);

            // 3. Async branches, each one returns; a write checks its own finer permission first
            if($req->isAction("update-contact-workspace")) {
                $req->checkPermission("customer.position.edit");
                model("Position")->updateContactWorkspace(
                    $position["id"],
                    $req->getPost("contactUuid"),
                    $req->getPost("workspace_notes"),
                );
                return $res->success();
            }

            // 4. Form branch (Z.Forms submit)
            if($req->hasFormData()) {
                $formResult = $req->validateForm([
                    (new FormField("name"))->required()->length(1, 255),
                ]);
                if($formResult->hasErrors) return $res->formErrors($formResult->errors);
                $res->updateDatabase("position", "id", "i", $position["id"], $formResult);
                return $res->success();
            }

            // 5. Render, last statement
            return $res->render("customer/position_details", [
                "position" => $position,
                "contacts" => model("Position")->getContacts($position["id"], includeHidden: $canViewAny),
            ]);
        }

    }

?>
```

## File

- **File skeleton:** `<?php` on the first line, a blank line, the class body indented one level, a blank line, `?>` on the last line. One blank line after the class's opening brace and one before its closing brace. One class per file, named like the file.
- **Namespaced classes** (`App\`) put `namespace App\Objects;` indented with the class, followed by a blank line.
- **`use` statements are sorted by length**, shortest first, the longest at the bottom. Project namespaces (`App\...`) and external namespaces (Composer packages) form two blocks with a blank line between them when a file has both:

```php
<?php

    namespace App\Objects;

    use App\Objects\Plan;
    use App\Models\Billing;

    use Symfony\Component\Uid\Uuid;
    use AsyncAws\S3\Input\PutObjectRequest;

    class S3Presign {
```

- **Typed properties** are declared at the top of the class, one per line, before the constructor: `private Client $client;`.

## Statements

- **Braces** open on the same line as the statement. `if(`, `foreach(`, `while(`, `match(`, `catch(` and `function(` without a space before the parenthesis; one space before the opening brace.
- **A single-statement `if` that leaves the method stays on one line without braces**: `if(!$notification) return;`, `if($formResult->hasErrors) return $res->formErrors($formResult->errors);`, `if(!$row) continue;`. Only for `return`, `continue`, `break` and `throw`, and only while the line stays short. Everything else gets braces.
- **Guard clauses first**: check and leave early, so the main path is not indented.
- **The literal goes on the left of a comparison**, always: `"CSV" == $function`, `false === $source`, `0 < count($rows)`. A mistyped `=` then fails loudly instead of silently assigning. Prefer `===`; `==` is acceptable where both types are known and it reads easier, never where the comparison is a security decision.
- **Named arguments for optional and boolean parameters**: `getFeedByUserId($userId, limit: 100, employerView: true)`, `notify("sale", userId: $userId, group: "Kundenbetreuung")`. A bare `true` in an argument list tells the reader nothing.
- **No `else`, never `switch`.** Both can nearly always be replaced: an `if` that leaves the method (early return) instead of `else`, a lookup array or a dynamic method call instead of a chain of cases (`$icons[$state]`, `$this->{"export" . ucfirst($format)}($rows)`), a `match` expression (trailing comma after the last arm) for a closed set of values. `switch` compares loosely, falls through when a `break` is forgotten, and reads worse than all of these. When an `else` really cannot be avoided, it is written `} else {` on one line, never on a line of its own.
- **One blank line between methods**, none between a method's signature and its first statement.
- **Always `return` the response call**: `return $res->render(...)`, `return $res->success()`, `return $res->reroute(...)`. The framework still ends the request inside these calls, but that behaviour will go, and the `return` already shows the reader that the branch ends here.
- Blank lines separate the steps of a method (permission, load, branches, render). No blank line after an opening brace inside a method.

## Expressions

- **Double quotes** for strings, including array keys: `$position["name"]`, `"customer.dashboard"`. Single quotes only for a SQL string literal inside a query (`'exclusion-list'`) or a quote inside a double quoted string.
- **Interpolate, with unquoted keys**: `"$opt[root]position/details/$position[uuid]"`, `"urn:li:person:$profile[id]"`, `"notifications/content/$notification[type]"`. No `{$x["y"]}` braces. When concatenation reads better (a method call, a constant), put spaces around the dot: `$asset["name"] . "." . $asset["extension"]`.
- **Type hint every parameter** (`int $positionId`, `string $uuid`, `bool $hidden`) and declare the return type where there is one (`: ?array`, `: string`, `: void`). Nullable with `?`.
- **`is_null($x)` and `empty($x)`**, always, instead of `null === $x` or `"" == $x`; both read as words and need no Yoda form.
- **Casts with one space**: `(int) $id`, `(string) $value`, `(bool) $flag`.
- **Short array syntax** `[]` only, never `array(...)`. One space around `=>`, and the values of a multi-line array are never column aligned: adding a longer key must not re-indent every line.
- Spaces around binary operators and after commas, no space inside parentheses or brackets (`foo($value)`, not `foo( $value )`); `??` and `?:` with a space on both sides.
- **Structured input is built by a named constructor** that parses and validates, not by `new` plus checks at the call site: `YouTubeUrl::fromUrl($input)`, `Plan::fromName($name)`. The object cannot exist in an invalid state, and the validation lives in one place.
- **Arrow functions** `fn($row) => ...` for one-expression callbacks; `function($row) use ($x) { ... }` once the body needs a statement.
- `list($token, $expiresAt) = ...` destructures a method that returns several values.

## Long lines

There is no fixed column count; the measure is that a line fits in half the screen at a normal zoom. When a line gets long:

- **Argument lists and arrays wrap one item per line**, indented one level, with a trailing comma after the last item and the closing bracket on its own line at the indentation of the opening line. A call wraps as soon as it would get long, even with a single argument:

```php
$company = model("Company")->getByUserId(
    user()->userId,
);
```

- **Always a trailing comma** after the last item of a multi-line array, argument list, parameter list or `match` arm list. Adding an item then changes one line, not two.
- **Method chains** put one call per line, indented one level under the receiver. A chain of one call may stay on one line; readability decides, not a count:

```php
$formResult = $req->validateForm([
    (new FormField("name"))
        ->required()
        ->length(3, 50),
    (new FormField("email"))
        ->required()
        ->length(3, 50)
        ->filter(FILTER_VALIDATE_EMAIL),
    (new FormField("remarks"))->length(1, 1000),
]);
```

- **A long condition is not wrapped, it is named.** Move its parts into variables with clear names above the `if`, so the condition reads as words:

```php
$hasLexofficeContact = !empty($employer["lexoffice_contact_id"]);
$isTest = "test" == config("execution_type");
if($formResult->hasErrors || (!$hasLexofficeContact && !$isTest)) {
```
