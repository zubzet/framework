# Views

Framework docs: `vendor/zubzet/framework/docs/core-features/views.md`, `layouts.md`. House style in Blade: `vendor/zubzet/framework/src/IncludedComponents/views`. Formatting (one tag per line, indentation, attribute splitting, quotes, directive syntax) is in [code-style/blade.md](../code-style/blade.md); this file is about what the markup is made of.

Views are `.blade.php` files under `app/Views`. ZubZet renders them with Katana Blade. Blade is a superset of PHP: directives are preferred, raw PHP is acceptable where it reads simpler. `{{ }}` is preferred for output.

## Skeleton

```blade
@extends($layout)

@section("head")
    <link rel="stylesheet" href="<?php $opt["generateResourceLink"]("assets/css/loadCircle.css") ?>">
@endsection

@section("content")
    <a href="{{ $opt["root"] }}position" class="btn btn-sm btn-outline-primary mb-4 d-print-none">
        <i class="fa fa-arrow-left mr-1"></i>
        Zurück zur Übersicht
    </a>

    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h2 mb-0">
            <i class="fa fa-briefcase mr-2"></i>
            {{ $position["name"] }}
        </h1>
    </div>

    <div class="mb-4">
        Hier finden Sie den Arbeitsbereich Ihres Auftrags.
    </div>

    @forelse($contacts as $contact)
        <div class="card shadow-sm mb-4" data-test="contact">
            <div class="card-body">
                ...
            </div>
        </div>
    @empty
        <div class="card shadow-sm mb-4" data-test="card-empty">
            <div class="card-body py-5 text-center">
                <h2 class="h3 mb-3">
                    Noch keine Kontakte
                </h2>
            </div>
        </div>
    @endforelse

    <script>
        ...
    </script>
@endsection
```

- `@extends($layout)` always, never a hardcoded layout name. The controller picks the layout as the third `render` argument when it is not the default.
- `@section("head")` only when the page needs an extra stylesheet, a `Z.Lang` override, or a library.
- Data arrives as top level variables and as `$opt[...]`. Prefer the top level variable (`$position`), use `$opt["root"]`, `$opt["user"]`, `$opt["generateResourceLink"]` for the framework injected ones.

## Markup rules

Layout of the markup itself (one tag per line, four spaces, long attribute lists, no empty tag without classes) is in [code-style/blade.md](../code-style/blade.md).

- **Bootstrap 4 utility classes only.** Layout with `d-flex`, `row`/`col-*`, spacing with `m*-*`/`p*-*`. Bootstrap is the base and a project only customizes it, so custom CSS is very rarely needed: the handful of classes in `main.css` (`shadow-hover`, `cursor-pointer`, `rounded-xl`, brand badges) is the normal amount. If a design needs more, ask. No `style=""` attributes, except a width or a margin-top the grid cannot express. No per-view `<style>` blocks; a Blade component may carry a small `<style>` so it stays portable, which must not become a habit.
- **Cards with `shadow-sm`** are the building block. Margins between cards (`mb-4`). Avoid nesting cards inside cards as much as possible; it is rarely ever good design. Use `card-header` or headings inside one card instead.
- **Headings in order**, `h1` through `h6`, sized with the classes `h1` to `h6`. The page has exactly one `h1`.
- **No `<p>`.** It carries its own bottom margin and typographic defaults, so spacing becomes inconsistent with the utility class system. `<div class="mb-2">` (or `mb-3`, `mb-0`) reads the same and keeps spacing explicit; long form text loses nothing by it.
- **Key value dumps are a last resort.** Label and value each in their own div (not a `<dl>`, not `<b>Label:</b><br>`). Better, design the data into the page: a status badge, a sentence, a card header. A list of labels and values is what you write when you only want to make sure the data is there at all.
- **Tables are not mobile friendly.** A table that scrolls sideways on a phone is unreadable, so every list of records has a card like appearance on small screens, with the label next to each value, and may become a row layout on large screens. The technique is free: one way is a header row with `d-none d-lg-flex` and a card per record repeating the labels with `d-block d-lg-none`. DataTables is the exception for real data grids.
- **Icons**: Font Awesome 4 class names (`fa fa-briefcase`, `fa fa-fw`), always with `mr-1` or `mr-2` before text. Icons are colored by `main.css`.
- **Mobile first**: every `col-*` starts at `col-12`, flex rows that would overflow get `flex-wrap` or `d-md-flex`. Buttons in a row carry `mb-2 mb-md-0`.
- **Empty states** always say that the section is empty and what could be there. Two sizes: a page dedicated to a list that has no entries yet gets a large card with icon, heading, sentence and the primary call to action (an illustration is fine); a small section of a page that happens to be empty gets the info card below.
- **Two fixed snippets**, identical in every project so the same element looks the same everywhere. The info card for an empty list or section, and the usage hint that tells the user how a feature works:

```blade
<div class="card shadow-sm mb-3" data-test="card-empty">
    <div class="card-body d-md-flex align-items-center py-3">
        <i class="fa fa-info-circle mr-2"></i>
        Noch keine Kontakte vorhanden.
    </div>
</div>

<div class="alert alert-info shadow-sm py-2">
    <i class="fa fa-info-circle text-dark mr-2"></i>
    Laden Sie eine CSV-Datei hoch, um Duplikate bei zukünftigen Anfragen zu vermeiden.
</div>
```
- **Print**: navigation, back buttons and action buttons get `d-print-none`.
- **Fonts, images, libraries** are self hosted and referenced through `$opt["generateResourceLink"]("assets/...")`, which appends the asset version. Never a CDN URL.

## Visual design, as recommendations

These are the defaults a stack starts from, not rules: a project's design brief changes them, and then the brief wins. They exist so an agent without a brief does not invent a design.

- Consistency in colors, fonts, sizes and spacing across all pages; the spacing between sections and between elements is the same everywhere, and button spacing is never changed.
- Only the primary and the secondary color are set (by theming Bootstrap, `code-style/css.md`). Text is black or white, rarely in the accent color. Calls to action stand out and are not placed next to stronger colors that take the attention away.
- One body font size; only the headings `h1` to `h6` differ, and not extremely. Emphasis is bold, not color or size.
- Avoid visual overload; prefer less. No "floating" boxes: every box and image is visibly left aligned, right aligned or centered.
- Layout changes only at Bootstrap's breakpoints, designed on the grid, so a section that maps onto `row` / `col-*` is responsive for free. Check dense content (pricing tables, wide forms) at every breakpoint.
- Contrast is checked with the WebAIM contrast checker (https://webaim.org/resources/contrastchecker/) when a color is chosen.

## PHP and Blade inside the view

- Output: `{{ $contact["name"] }}` (escaped) is the default for every value (see "Escaping user content"). Raw `<?= ?>` is tolerated for a value that obviously cannot carry input, such as `$opt["root"]`, a loop index or a constant, and "obviously" is a high bar: user input creeps into unexpected places (an error branch returning the original string, a column that later becomes editable), so when in doubt it is `{{ }}`. `{!! !!}` only for HTML the backend built on purpose, never for anything a user typed.
- Control flow: `@if` / `@endif`, `@foreach` / `@endforeach`, `@forelse` / `@empty` / `@endforelse`, `@auth("permission")` / `@endauth`. Raw `<?php if(...) { ?> ... <?php } ?>` is acceptable where a directive would read worse. Directive versus raw PHP, `@else` avoidance and the one-liner rule for other PHP: `code-style/blade.md`.
- Attribute toggles: `@checked($x)`, `@selected($x === $y)`, `@disabled($x)`, `@class([...])`.
- No type casts in views unless the value really needs it. No `?? ""` chains for every field. Assume the controller delivered what it promised.
- German date format: `date("d.m.Y", strtotime($row["created"]))`, with time `d.m.Y H:i`. Always through `strtotime`.
- German number format: `number_format($amount, 2, ",", ".")` for money, `number_format($count, 0, ",", ".")` for counts. When a project is clearly not German, or it is unclear, ask which format the project uses instead of assuming.
- Permission dependent markup: `@auth("support") ... @endauth` or `<?php if($opt["user"]->checkPermission("support")) { ?>`.
- Never concatenate markup in PHP (`'<span class="badge">' . $x . '</span>'`). Write it as markup in the view or a component.
- Katana implements a subset of Laravel Blade. Before using a Laravel habit (`@csrf`, `@can`, `@error`, `@lang`, ...), check the "Not available in Katana" section of `docs/core-features/views.md`; the ZubZet way is `@auth("permission")` and plain PHP.
- A project view with the same name as a framework view replaces it, which is how custom error and login pages are done (`docs/core-features/views.md`).

## Escaping user content

**Anything a user typed is printed escaped, always**: `{{ }}` in Blade, `e($value)` in PHP. This is the stack's XSS defence, and it is applied everywhere without deciding case by case, so it is never forgotten where it matters.

- **Escape, do not strip.** `strip_tags` is not escaping. `e()` currently strips tags before escaping; do not rely on that part (`docs/core-features/global-helper-functions.md`).
- **Values that merely look safe are escaped too.** A formatted date can only be a date, until an error branch hands back the original input: `{{ date("d.m.Y", strtotime($row["created"])) }}`. Raw output is for values that cannot be input by construction, not for values that happen to be clean today.
- **Escaping depends on the context.** HTML escaping protects HTML text and quoted attributes, nothing else:
    - In a `<script>` use `<?= json_encode($value) ?>`, never `{{ }}` (it HTML escapes the quotes). An optional value gets a default so a field receives `""` instead of `null`: `const name = <?= json_encode($segmentation["name"] ?? "") ?>;`. Never wrap it in quotes: the JSON brings its own and the outer string would end right away.
    - In an attribute, escaping does not stop a value from adding more than intended (`class="user-{{ $color }}"` with `green other-class`), and in markup assembled in PHP it can add an attribute such as `onload=` without a single angle bracket.
- **Prefer a whitelist** where the values are a closed set: `->in([...])` in the `FormField`, an enum, a `match` to known class names, then print the known string. Escaping is a blacklist and someone always finds a way around it.
- **Before printing user input somewhere new** (a class, a `<script>`, a data attribute), think about which characters can break out of that context.

## Components and includes

Blade gives real reuse. Use it instead of copying markup:

- Repeated markup inside one project (a position card, a status badge) becomes a component under `app/Views/components/<name>.blade.php` with `@props([...])`, used as `<x-position-card :position="$position"/>`. A component brings its own `<script>` along, which is why inline JS stays the default even with reuse.
- A partial that is only split for length is `@include("customer.position_contact", ["contact" => $contact])`.
- Layouts: `app/Views/layout/default_layout.blade.php` contains `<x-zubzet::head :opt="$opt"/>`, `@yield("head")`, the navbar, `@yield("content")` inside the container, the footer, and `<x-zubzet::body :opt="$opt"/>`. Every full page layout keeps both components: the head one loads jQuery, Bootstrap, Font Awesome and Z.js through the asset proxy, the body one carries the session watcher and the debug bar (`docs/core-features/layouts.md`, `docs/core-features/debug-bar.md`). The `Z.Lang.*` strings for Z.Forms in the UI language live in the layout `<head>`. A second layout (`min_layout`, `empty`, `mail_layout`) exists only when the page shell is really different.
- Do not build a parallel "page" concept with `$opt["page"]` flags and body classes. The layout highlights the active navigation item by comparing `$opt["request"]->getUrlParts()` or receives a single `active` key, nothing more.
- `@push("scripts")` into a layout `@stack` is allowed only when the layout already defines the stack; the default stays the inline `<script>` at the end of the content section.
- Custom error pages are views named `403`, `404` and `500` under `app/Views`, which override the framework's (`src/IncludedComponents/views`, rendered by the framework `ErrorController` in `layout/min_layout`). No `ErrorController` in the project. The maintenance page is `app/Views/maintenance.html`, plain HTML without `<?php` (`docs/core-features/maintenance.md`).
- The login, signup, reset and verification views come from the framework and are restyled by overriding them by name; their JavaScript stays `Z.Presets.Login` / `Z.Presets.Signup` with the default `LoginController` (`docs/frontend-integration/presets.md`).

## Pages, not modals

This is server side rendered software. The default unit of UI is a page with its own URL, not a modal.

- Creating or editing an entity gets its own action and view: `/company/new`, `/company/edit/<uuid>`, `/position/details/<uuid>`. A plus button links there. It does not open a modal with a form inside.
- Detail pages beat popups. A list item links to a detail page; the detail page holds the forms and the buttons. Users can bookmark it, reload it, open it in a tab, and a Cypress test can `cy.visit()` it.
- A modal is acceptable for a short confirmation, a single field prompt, or a small secondary form that would be absurd as a page (a CSV upload dialog on a list page). Z.Forms resets correctly inside modals, so when a modal is the right call it works; it is still the exception.
- When the same form or card appears in several places, that is a reason for a Blade component or `@include`, never a reason for a shared modal. Components carry their markup and their `<script>` with them, so reuse costs nothing.
- Known agent failure: SPA habits. Lists with inline editors, plus buttons that open modals, tabs that swap content client side, state kept in JS. Each of those should be a page or a server rendered section. Good UX here means fast, predictable pages with clear navigation, not front end machinery. It is also what stays maintainable in two years.

## JavaScript in views

- JS lives inline, in a `<script>` block at the end of `@section("content")`, or right after the one element it drives (`code-style/blade.md`). jQuery is available (`$`), Z.js is available (`Z.Forms`, `Z.Request`, `Z.Lang`, `Z.Presets`).
- A separate file in `webroot/assets/js` is the rare exception for a large, self contained component that several pages share and that is not a Blade component. Default to inline.
- Uuids travel in `data-*` attributes (`data-position-uuid`), never numeric ids. No frameworks, no bundlers, no modules. Syntax style (`const`/`let`, arrow functions, quotes, selectors): `code-style/javascript.md`.
- After a write that changes what the page shows: `location.reload()` or `location.href = "{{ $opt["root"] }}..."`. Only update the DOM in place for small toggles (badge, checkbox state).
- Confirmations with `confirm()`, quick inputs with `prompt()`, result messages with `alert()` are acceptable in back office pages. Customer facing pages use a Bootstrap modal or inline feedback, because a browser dialog does not look good on a public site.
- No `window.*` globals per page and no JS translation layer. Texts are in the markup.
