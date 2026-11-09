# Blade and HTML style

General rules are in [index.md](index.md). What the markup is made of (Bootstrap, cards, headings, no `<p>`, escaping) is in `reference/views.md`; this file is only how it is laid out. The inline `<script>` follows [javascript.md](javascript.md).

```blade
@section("content")
    <a href="{{ $opt["root"] }}position/new" class="btn btn-sm btn-primary" data-test="btn-create-position">
        <i class="fa fa-plus mr-1"></i>
        Neue Position
    </a>

    @forelse($contacts as $contact)
        <div class="card shadow-sm mb-4" data-test="contact-{{ makeSlug($contact["email"]) }}">
            <div class="card-body">
                {{ $contact["name"] }}
            </div>
        </div>
    @empty
        <div class="card shadow-sm mb-4" data-test="card-empty">
            <div class="card-body py-5 text-center">
                Noch keine Kontakte
            </div>
        </div>
    @endforelse
@endsection
```

- **One tag per line. Always spread tags open.** Even `<i class="fa fa-plus mr-1"></i>` may stay on one line only because it is empty; anything with content gets its own open and close lines, with the content indented one level.
- **Four spaces** per nesting level, counting Blade directives (`@if`, `@foreach`, `@section`) as a level like any tag.
- **Long attribute lists** may be split one attribute per line, indented one level, with the closing `>` or `/>` on its own line at the tag's indentation:

```blade
<input
    type="text"
    id="filterName"
    class="form-control"
    placeholder="Name der Position"
    value="{{ $filterSelections["name"] }}"
    data-test="filter-name"
/>
```

- **Never an empty tag without classes.** At least `mb-0`, so the reader sees the spacing was decided, not forgotten.
- **Double quotes** for attributes and inside Blade expressions alike: `{{ $contact["name"] }}`, `@section("content")`, `@include("customer.position_contact", ["contact" => $contact])`.
- **Interpolate inside one string** instead of concatenating: `href="{{ $opt["root"] }}position/details/{{ $position["uuid"] }}"`.
- **Directives over raw PHP**: `@if` / `@endif`, `@foreach` / `@endforeach`, `@forelse` / `@empty` / `@endforelse`, `@auth`. Raw `<?php if(...) { ?> ... <?php } ?>` with braces is acceptable where a directive would read worse. Never the `endif;` / `endforeach;` alternative syntax.
- **Avoid `@else`.** Prefer `@forelse` with `@empty`, two `@if` blocks, or `??` / `?:` with a space on both sides.
- **PHP that is not output or control flow is a one-liner**: `<?php $plan = App\Objects\Plan::fromName($position["product"]); ?>`. Only introduce a variable when the expression is used several times; otherwise use the array item directly.
- Output is `{{ }}` (what may be raw, and why, is in `views.md`, "Escaping user content").
- Inline `<script>` goes at the end of `@section("content")` for the page's logic, indented like the markup around it. A small script that drives one element may sit right after that element, inside the same `@if`, so that what belongs together stays together; a component carries its own script the same way.
