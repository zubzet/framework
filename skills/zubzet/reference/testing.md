# Tests: Cypress and data-test attributes

Every feature ships three things: migration, seed rows covering its states, one Cypress spec for the happy path. `npm run tests` must stay green.

## Contents

- What the tests are for
- Folder structure and commands
- Building a test step by step
- A feature spec
- Selecting elements
- Lists and positions
- Independence and test data
- Logging in
- Running and debugging
- data-test attributes

## What the tests are for

Cypress runs end to end tests. A unit test feeds one function an input and checks the output; an end to end test takes the finished application and clicks through it the way a user would, checking that everything behaves as intended. That is what lets a stack guarantee a feature works.

- Test whole features as a user flow, not single functions.
- 100% coverage does not exist and is not the goal. Plan the tests that matter (every feature's happy path, the states the seed covers); you can always write too many.

## Folder structure and commands

```
tests/
├── cypress.config.js                baseUrl http://localhost:8080
└── cypress/
    ├── e2e/<area>/<feature>.cy.js   specs in sensibly named folders: account/login.cy.js
    ├── fixtures/logins.json         data handed to the specs, e.g. the login tokens
    └── support/
        ├── commands.js              cy.query, cy.queryLike, cy.form, cy.fillForm, cy.validateForm,
        │                            cy.loginAs, cy.areVisible, cy.hasLinks, cy.http, cy.sendRequest
        └── database.js              cy.dbSeed
```

- `npm run cypress` opens the interactive runner; `npm run tests` runs every spec headless and prints the results in the console (CI does that).
- The support commands come with the `zubzet/zubzet` template. Know they exist and use them; change or extend them only with permission, and never duplicate one.
- A spec is one file with one `describe("<Feature>")` around it, and every `it` in it is one test. Name them so the runner reads like English: `describe("Login")` plus `it("requires a password")`.
- `before()` runs once before all tests of the spec (seed the database), `beforeEach()` before every single `it` (log in, visit the start page).

## Building a test step by step

Keep the runner (`npm run cypress`) open next to the editor, ideally on a second screen. It reruns the spec on every save, so you watch each step work before writing the next.

1. **Read the view first.** Find the elements the test touches and check their `data-test` attributes; add the missing ones to the view.
2. **One step at a time.** Write the first action, save, watch it run, then add the next. The login spec's "requires a password" grows like this:

```js
it("requires a password", () => {
    cy.query("username").type("some@email.de");
    cy.query("btn-login").click();
    cy.query("error").should("be.visible");
    cy.query("error").should("contain", "Username or password is wrong");
});
```

3. **Assert inside the element that should show the result.** An unscoped `cy.contains("...")` searches the whole page and passes when the text appears anywhere, even when the error element itself shows something else. Select the element, then check its content.
4. **A click is already an existence check.** Cypress fails when the element is not there, so no `should("exist")` before `.click()`.

Map the user's real flow: think the way the application is actually used instead of clicking around wildly. A test reads top to bottom as: start where the user starts, assert the state before (what must not be there yet), act, assert the state after.

```js
describe("Customer setup", () => {
    before(() => {
        cy.dbSeed();
    });

    beforeEach(() => {
        cy.loginAs("customer_new");
    });

    it("sets up the customer account", () => {
        cy.visit("/");
        cy.query("customer-setup").should("not.exist");
        cy.query("navbar-leads").should("not.exist");

        cy.query("btn-start-setup").click();
        cy.fillForm({
            "company": "Beispielfirma GmbH",
            "country": "DE",
        });
        cy.query("form").contains("Speichern").click();

        cy.query("customer-setup").should("be.visible");
        cy.query("navbar-leads").should("be.visible");
    });
});
```

- A new customer sees neither the dashboard parts nor the extra navigation yet, so the test asserts that first; after the setup both must be there.
- `cy.fillForm({name: value})` fills all Z.Forms fields at once by their `name` (the `name` of `form.createField`) and handles selects itself (a select is chosen with `select`, not `type`). `cy.form("name")` reaches a single field.
- The form container carries `data-test="form"`, and the save button is found inside it. Never use the container's id: when a second form arrives, the first one's id gets renamed (`form-settings`), the `data-test` stays.

## A feature spec

The happy path of a whole feature, across two users:

```js
describe("Position", () => {
    before(() => {
        cy.dbSeed();
    });

    it("creates a gold position and gets it accepted", () => {
        cy.loginAs("customer");
        cy.visit("/");
        cy.query("navbar-position").click();
        cy.query("btn-create-position-empty-state").click();
        cy.query("name").type("Test Position");
        cy.query("product").first().click();
        cy.query("btn-create-position").click();
        cy.url().should("match", /position\/[a-z0-9\-]+/);

        cy.query("heading-position-briefing").should("be.visible");
        cy.fillForm({
            "company": "Beispielfirma",
            "contact_person": "Max Mustermann",
        });
        cy.query("form").contains("Speichern").click();
        cy.query("card-wait-for-accept").should("be.visible");

        cy.loginAs("admin");
        cy.visit("/position/requests");
        cy.query("position-test-position").within(() => {
            cy.query("btn-accept-position").click();
        });
        cy.reload();
        cy.query("position-test-position").within(() => {
            cy.query("accepted-status").should("contain", "✅");
        });
    });
});
```

- `cy.query` is a parent command: chained as `cy.query("a").query("b")` it searches the whole page again. Scope with `.within(() => { ... })`.
- `cy.dbSeed()` rebuilds the database (it runs `npm run seed` and clears the cached logins).
- `it` titles are English sentences.

## Selecting elements

- **Always the `data-test` attribute**, through `cy.query("value")` instead of `cy.get('[data-test=value]')`. Not the id, not a class: tests stay independent of the rest of the code, so renaming an id (`username` to `username2`) or restyling a class never breaks them.
- **`data-test` is for tests only.** No CSS and no JavaScript selects by it, so it never has to change for a reason outside the tests.
- **Values need not be unique**, neither across views nor for repeated items on one page. Keep them short and descriptive: `error`, `form`, `username`.
- **Text only inside a selected element**: to assert content (`cy.query("error").should("contain", ...)`) or to pick the button inside a container (`cy.query("form").contains("Speichern")`). Never CSS classes, never `nth-child`, never a text search across the whole page.

## Lists and positions

Tables, card grids, columns: all of them are lists of things, tested the same way.

- **Every item of a list carries a `data-test` made of the entity and a slug of its name**, set inside the `@foreach`: `position-{{ makeSlug($position["name"]) }}`, `contact-{{ makeSlug($contact["email"]) }}`. The test then names the row it means (`cy.query("position-test-position")`), reads like the scenario, and survives new seed rows, resorting and pagination. Numeric ids are not used in the value: the slug is readable, the id is not.
- **Reach the list once through the UI.** Visit `/`, check the navigation link exists, click it: that is the user's path. Later tests in the spec may `cy.visit("/segmentation")` directly, because the path is already validated.
- **Position only where there is no name**: repeated form fields, rows of a CED, columns of a chart. Then every item shares one `data-test` and the test picks by index, counted from zero: `.eq(1)` is the second item, `.first()` is `.eq(0)`. Lists are sorted (an explicit `ORDER BY` in the model, `models.md`), so rows appended at the end do not move the existing ones. When new seed rows shift a position, adjusting the index is fine.

```js
cy.query("position-zielgruppe-2").click();
cy.query("name").clear().type("Zielgruppe 2 Test").blur();
cy.query("btn-back").click();
cy.query("position-zielgruppe-2-test").should("contain", "Zielgruppe 2 Test");
```

- `.blur()` leaves the field, as a user clicking elsewhere would.
- For several positional items, loop over the indexes instead of repeating the line. This fills the first, second, fifth and sixth field and leaves the third and fourth empty:

```js
[0, 1, 4, 5].forEach((index) => {
    cy.query("email").eq(index).type("test@example.com");
});
```

- **Static seed, static test.** The seed makes the scenario fixed, so the test knows every name and every position in advance. A test that has to search for its row, or that creates the row it later asserts on, is a sign the seed is missing a state.

## Independence and test data

- **Tests inside a spec do not depend on each other.** Any `it` could be moved to the top and still pass. A test that edits what the previous one created makes adding tests harder and the spec harder to follow. Two dependent tests are not a disaster; avoid it whenever possible.
- **A spec never depends on another spec.** Specs run alone (you run just the part you are working on), so there is no "setup spec". A hidden dependency can creep in because most specs seed once or not at all; still avoid it.
- **A long test is fine.** One that is twice as long as the others is better than a split that makes no sense.
- **Test data comes from the seed**, not from previous tests and not from API calls in the test. The application starts empty, `cy.dbSeed()` puts in exactly the state the test needs (several inserts, an update afterwards), and the test finds it there. "can view existing segmentations" uses two seeded segmentations instead of creating them first (`database.md`, "Seeds").

## Logging in

Logging in through the form (type the name and password, click, wait) costs time in every test, about two extra minutes on 60 tests. Feature specs therefore log in with a seeded token: `cy.loginAs("customer_new")`.

- A login trades the password for a token with a limited lifetime (table `z_logintoken`, cookie `z_login_token`). `seed/accounts/1_users.sql` inserts one token per test user, with recognizable values (`aaaa...01`, `aaaa...02`).
- `fixtures/logins.json` maps a profile name to that token. `cy.loginAs` reads the fixture and sets the cookie inside a `cy.session` cached per profile. It lowercases the name, so profile keys are lowercase snake_case: `customer_new`.
- A user that only exists to test a state that cannot log in (not activated) gets no token, just name and password in the fixture.
- Switch users inside a test by calling `cy.loginAs` again (customer creates, admin accepts).
- The login form itself is tested once, in `e2e/account/login.cy.js`.

## Running and debugging

- `it.only(...)` runs just that test while you work on it, so you do not wait for the whole spec. Never commit it. It silently skips the rest of the spec, and once a suite is allowed to drift from fully passing, switching to `it.only` becomes the easy way out and quality degrades from there.
- Cypress fails a test on an uncaught JavaScript error in the page. That is intended: fix the error, do not silence it.
- CI and compose health checks wait for `GET /_zubzet/health` to answer `200`, not for a fixed sleep (`docs/core-features/health-endpoint.md`).
- The e2e stack runs with `execution_type = test`, so a 500 during a spec renders the Whoops page with the stack trace; read it in the Cypress screenshot before guessing (`docs/core-features/error-handling.md`).

## data-test attributes

Mandatory on every interactive element and every status indicator a test could assert on. kebab-case, role prefixed:

| Prefix | Use |
| --- | --- |
| `btn-` | buttons and links that trigger a flow: `btn-create-position`, `btn-accept-position`, `btn-login` |
| `navbar-` | navigation links: `navbar-position`, `navbar-leads` |
| `filter-` | filter inputs and selects |
| `card-` | state cards and empty states: `card-wait-for-accept` |
| `heading-` | page headings a test waits for |
| `<entity>-<slug>` | repeated items with a name: `position-{{ makeSlug($position["name"]) }}`, `contact-{{ makeSlug($contact["email"]) }}` |
| `<entity>` | repeated items without a name, picked by position: `email`, `ced-row` |
| `<field>-status` | badges and indicators: `briefing-status`, `accepted-status` |
| plain name | Z.Forms containers and inputs, messages: `form`, `name`, `product`, `error` |
