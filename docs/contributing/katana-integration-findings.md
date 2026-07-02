# Katana integration findings

Working notes from integrating [Katana](https://github.com/soysudhanshu/katana) as ZubZet's
render engine (issue [#145](https://github.com/zubzet/framework/issues/145)).

**Purpose:** the point of this WIP is *discovery* - drive a real integration far enough to
surface exactly which hooks Katana still needs, so the render-engine maintainer can finalise
[katana#53](https://github.com/soysudhanshu/katana/issues/53) before the final implementation.
It is deliberately not a finished renderer.

Tested against **Katana 0.1.0** on **PHP 8.0.30** (the framework's minimum), rendering the real
framework and e2e-app views migrated to `.blade.php`.

## What was validated

- The v1.3 migrator converts every legacy `return[...]` view **and** layout to `.blade.php`.
  An oracle renders each converted document through real Katana and diffs it against the legacy
  closures' own output: **47/47 renderable views byte-identical**; **full view+layout composition
  (external injection): 42 byte-identical, 8 whitespace-only (content identical), 0 content diffs.**
- End-to-end on the dockerised e2e stack (PHP 8.0): pages render through Katana; the
  `core/framework-views` (12/12), `core/layout` (16/16) and `core/blade-compat` (2/2) specs pass.
- A dedicated compatibility probe (`core/blade-compat`) proves the migration keeps literal `{{ }}`,
  `{!! !!}` and `{{-- --}}` verbatim, that CSS `@media` passes through, and that an anonymous
  `<x-component>` renders through the adapter.

### Migration escaping notes (what the converter emits)

- Literal `{{ }}` / `{!! !!}` are escaped with Blade's own `@{{` / `@{!!` forms.
- Literal `{{-- --}}` **cannot** use `@{{--`: Katana strips comments *before* echo-escaping, so the
  converter wraps them in `@verbatim ... @endverbatim` instead. Raw `<?php ?>` still executes inside
  `@verbatim`, so this stays output-preserving.
- `@php ... @endphp` is equivalent to `<?php ... ?>` (it compiles to exactly that); migrated views
  keep raw `<?php ?>` and pass `$opt` as a single datum, so no view-body rewriting is needed.

## Katana change-points (what katana#53 should cover)

Ranked by how much they block a clean integration. "Framework workaround" is what the adapter has
to do *today* to cope; each workaround is the tax we pay for a missing hook.

### 1. `@extends` does not forward render data to the parent layout  — blocker

`TemplateInheritanceRenderer::output()` renders the parent template with **no data**:

```php
// TemplateInheritanceRenderer.php
public function output(): void {
    ...
    echo $this->blade->render($this->template);   // <- no $data
}
```

The child view receives the data (via `renderContents()`'s `extract($data)`), but the layout is
rendered blind, so any `$var` a layout uses (here `$opt` for root/title/essentials) is null and
the layout fatals. `@include` already threads data (`tempContextData`/`withDefault`); `@extends`
does not.

- **Minimal fix (provided):** `patches/katana-extends-forward-data.patch` - store the current
  render data on the inheritance renderer and pass it in `output()`. The data must be **saved and
  restored** around each `renderContents()` include, otherwise a nested render (an `<x-component>`
  or `@include`, which re-enter `renderContents` with their own data) clobbers the shared
  `contextData` before the outer `output()` reads it, and the layout loses `$opt`. This bug bit a
  component-using view and the admin layout until the save/restore was added.
- **#53 mapping:** the "global state / service injection" story only works if the layout shares the
  child's data scope. Tied to finding #2 (state lives on the shared instance).
- **Framework workaround:** none possible without the patch (layouts genuinely need the data).

### 2. Template-inheritance state leaks across top-level renders  — blocker (also a Katana bug)

`@section` content is stored on the (shared) `Blade` instance and never reset between renders, and
`startSection()` skips a section that is already set:

```php
public function startSection(string $section, string $inlineContent = ''): void {
    ...
    if (isset($this->sections[$section])) return;   // keeps the FIRST render's section
    ...
}
```

Rendering view A then view B through the same `Blade` instance makes B reuse A's `body`/`head`.
Observed directly: three different views all rendered the first view's body.

- **Fix:** reset inheritance state per top-level render, or don't hang it off the shared instance.
- **#53 mapping:** any long-lived engine handed to a framework must be safe to render N times.
- **Framework workaround:** a fresh `Blade` per render (the on-disk compile cache is still shared).

### 3. PHP 8.0 incompatibility  — major

The framework supports `php >=8.0 <8.6`; Katana 0.1.0 does not run on 8.0:

- `public readonly` properties (PHP 8.1+) in `Blade.php`, `Slot.php`, `CompileAtRules.php`
  → `ParseError` on 8.0.
- `hash('xxh64', ...)` (algorithm added in PHP 8.1) in `Blade.php::getViewIdentifier()`
  → `ValueError` on 8.0.

Katana declares no `require.php` constraint, so Composer installs it on 8.0 and it fails at runtime.

- **Fix:** drop `readonly` (or gate it), use an 8.0-available hash, **and** declare an accurate
  `require.php`. Provided: `patches/katana-php80-compat.patch`.
- **#53 mapping:** a "standalone Blade for any PHP project" should state and honour its floor.
- **Framework decision:** keep the 8.0 floor; Katana to become 8.0-compatible.

### 4. Single view path, no pluggable view finder  — major

`new Blade($viewPath, $cachePath)` takes exactly one root. The framework resolves views
**userspace-overrides-then-framework-fallback** (two roots), so a single root cannot express the
lookup chain. This is exactly katana#53's `AbstractViewFinder` / `addViewPath`.

- **Framework workaround:** one `Blade` per root, picked by where the layout resolves, with the
  view's source inlined into the synthetic child. **Limitation:** a userspace view that
  `@include`s a framework partial (or vice-versa) will not resolve across roots.

### 5. No render-from-string / no programmatic layout selection  — major

Because the layout is chosen *externally* (per request / `HandlesDefaultLayout`) rather than via an
in-view `@extends`, the adapter must synthesise a child `@extends('<layout>') ...` and it can only
render **files** (`render($name)` / `renderViewFile($path)`) - there is no `renderString()` and no
"render view X into layout Y". So the adapter writes a synthetic child file to disk per (view,layout).

- **Fix:** a `renderString()` and/or a `renderWithLayout($view, $layout, $data)` entry point.
- **#53 mapping:** frameworks that own layout selection need this.

### 6. `Blade` is `final`  — minor

`final class Blade` blocks subclassing, so a framework can't override one method to inject behaviour
and must compose around the class. Consider non-final or documented extension points.

### 7. Cache handling  — minor

Katana writes compiled views with `file_put_contents` and assumes the cache dir exists (no `mkdir`),
keys on `path+filemtime`, and exposes no clear/invalidate API. Issue #145 asks for a PSR file cache;
Katana's cache is not PSR and is not injectable.

- **Framework workaround:** adapter creates the cache dir.

### 8. `e()` helper  — non-issue (recorded so it isn't re-litigated)

Katana namespaces its helper (`Blade\e`) and compiles `{{ }}` to fully-qualified `\Blade\e(...)`, so
there is **no collision** with the framework's global `e()`. The framework's `e()` now delegates to
`\Blade\e()` (keeping its historical `strip_tags()` + null passthrough) so both escape identically.

## How the framework integrates today (the lean adapter)

- `src/Rendering/KatanaRenderer.php` (~60 lines) - the only Katana-specific glue. Everything it does
  beyond `new Blade(...); render(...)` corresponds to a change-point above.
- `src/Rendering/CanRenderView.php` - `resolvePath()` now targets `.blade.php`; `render()` still
  builds the same `$opt` contract and delegates composition to `KatanaRenderer`. No second renderer,
  no closure fallback (per the "Blade-only" decision).
- `src/Support/Helpers.php` - `e()` delegates to `\Blade\e()`.
- `patches/` - the two Katana patches above. Applied locally for now; once upstreamed the framework
  simply requires the fixed Katana version and drops the patches.

## Migrator (version-migrator v1.3)

`LegacyViewConverter` (tokenizer-based) + `ViewMigration` modifier + `V1_3_0` step convert
`return[...]` views/layouts to `.blade.php`:

- body-only view → straight content; head(+body) → `@section('head')` / `@section('body')`.
- layout `$body(...)` / `$head(...)` calls → `@yield('body')` / `@yield('head')`; everything else
  (including `$opt["layout_essentials_*"]`) stays raw PHP.
- literal `{{`, `{!!`, `{{--` are escaped (`@{{` ...) so Katana emits them verbatim.

Run against the e2e app: **36 views/layouts converted, 0 `.php` left**, output byte-identical to the
validated converter.
