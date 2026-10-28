# JavaScript and Cypress style

General rules are in [index.md](index.md). Where JavaScript lives and what it may do is in `reference/views.md`, "JavaScript in views"; how specs are built is in `reference/testing.md`.

```js
const root = <?= json_encode($opt["root"]) ?>;

$(".btn-accept").on("click", (event) => {
    const uuid = $(event.currentTarget).data("position-uuid");
    Z.Request.action("accept-position", { uuid }).then(() => {
        location.reload();
    });
});
```

- `const` and `let`, never `var`. Arrow functions for callbacks. Semicolons after every statement.
- **Double quotes** for strings, template literals only when interpolating.
- jQuery selectors by id or by a purpose class (`.btn-accept`, `.contact-card`), never by `data-test`. Uuids travel in `data-*` attributes (`data-position-uuid`), never numeric ids.
- **All PHP at the top of the script, as JSON.** Every value the script needs is handed over once in its first lines (`const root = <?= json_encode($opt["root"]) ?>;`, `const isPublic = <?= json_encode($isPublic) ?>;`, `const offers = <?= json_encode($offers) ?>;`), without surrounding quotes. Below that line the script is plain JavaScript: no `<?php if(...) { ?>` around a block of JS, no `<?= ?>` inside an expression. It reads as one language, and `json_encode` escapes what a hand written interpolation would get wrong.
- Multi-line calls and object literals: one item per line, four spaces, always a trailing comma, closing bracket on its own line, same as PHP.
- No `switch` here either. A lookup object (`const icons = { gold: "star", pro: "rocket" };`) or `if` with early returns.
- Cypress specs follow the same quotes, commas, step comments (`// Delete the order`) and index loops as above; `describe` names the feature, `it` titles are English sentences (`it("can view existing orders", ...)`). How a spec is built (`cy.query`, `cy.fillForm`, lists and positions, login) is in `reference/testing.md`, which holds far more than this line.
