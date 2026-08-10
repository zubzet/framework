# Getting Started: Views

## What does a view do?
A view contains the HTML the user should see, along with the CSS, images and JavaScript that page needs. Views do usually **not** contain a footer, navigation, header or other elements that belong to the overall layout of the page. For this, layouts should be used, as without a layout a view can't be rendered. Read more about layouts [here](layouts.md).

Database access and heavy logic should not be used in the view, as that belongs into other parts of the application.

## The render engine
Since v1.3.0 ZubZet renders views with [Katana](https://github.com/katanaphp/blade), a standalone implementation of the [Blade](https://laravel.com/docs/13.x/blade) templating language. A view is a `.blade.php` file placed in your project's `z_views` directory, otherwise it will not be found when called from the [render method](../api/classes/ZubZet-Framework-Rendering-CanRenderView.html#method_render).

Blade gives you a concise syntax for the things you do in every template: escaping output, loops, conditionals, reusable components and page inheritance. This page covers the parts you reach for day to day, which is most of what you need. For the complete directive reference, the [Laravel Blade documentation](https://laravel.com/docs/13.x/blade) is the final backstop (Katana implements the common Blade set, see [Not available in Katana](#not-available-in-katana)).

!!! tip "Blade is a superset of PHP"
    Every `.blade.php` file is still a PHP file. Plain PHP works verbatim: `<?php ... ?>`, `<?= $x ?>` and any function call run exactly as they would in a normal template. Blade directives like `@if` or `@foreach` are just shorthand that compiles down to that same PHP, so you never have to give up raw PHP, Blade only adds to it. If you prefer to stay inside Blade's syntax, `@php ... @endphp` is identical to `<?php ... ?>`.

## A first view
A view extends a layout and fills in its content:
```blade
@extends($layout)

@section("content")
    <h1>Hello World</h1>
@endsection
```
`@extends($layout)` plugs the view into the layout the [controller](controllers-and-actions.md) chose. The framework passes the layout name in as `$layout`, so you never hardcode it. `@section("content")` is the main body, and the layout decides where it lands. An optional `@section("head")` adds page specific tags to the document `<head>` (a stylesheet, a meta tag). Read more about how the two fit together in [Layouts](layouts.md).

## Passing data
Whatever you pass to `render` is available in the view, both as a top level variable and inside the `$opt` array:
```php
// Controller
public function action_index(Request $req, Response $res) {
    return $res->render("employee/index", [
        "name" => "Ada",
    ]);
}
```
```blade
{{-- View: z_views/employee/index.blade.php --}}
@extends($layout)

@section("content")
    <h1>Hello {{ $name }}</h1>   {{-- or $opt["name"] --}}
@endsection
```
The framework also injects a set of helpers into `$opt` for every render: `$opt["root"]` (the application's root path), `$opt["host"]`, `$opt["user"]`, `$opt["request"]`, `$opt["response"]`, `$opt["title"]` and `$opt["generateResourceLink"]`. For a view to communicate back to a controller, asynchronous methods must be used (see [Backend Requests](../frontend-integration/backend-requests.md)).

!!! note "View names and the file extension"
    The view above is addressed as `"employee/index"` while the file on disk is `z_views/employee/index.blade.php`. The extension is optional and dot notation works too, so `"employee/index"`, `"employee/index.blade.php"` and `"employee.index"` all resolve to the same file. A view in your project overrides a framework view of the same name.

## Echoing data
```blade
{{ $name }}              {{-- escaped output, HTML safe --}}
{!! $trustedHtml !!}     {{-- raw, unescaped output --}}
{{-- this is a comment and is not rendered --}}
{{ $title ?? "Untitled" }}   {{-- any PHP expression works inside the braces --}}
```
`{{ }}` escapes its output the same way the framework's [`e()` helper](global-helper-functions.md#e) does, so it is safe for user input by default. Use `{!! !!}` only for HTML you trust.

## Conditionals
```blade
@if($user->isLoggedIn)
    <span>Welcome back</span>
@elseif($showGuestBanner)
    <span>Hello, guest</span>
@else
    <a href="{{ $opt["root"] }}login">Log in</a>
@endif

@unless($employees)
    <p>No employees yet.</p>
@endunless

@isset($employee)
    <p>{{ $employee["name"] }}</p>
@endisset
```

## Loops
```blade
@foreach($employees as $employee)
    <li>{{ $employee["name"] }}</li>
@endforeach

{{-- @forelse handles the empty case in a single construct --}}
@forelse($employees as $employee)
    <li>{{ $employee["name"] }}</li>
@empty
    <li>No employees found.</li>
@endforelse

@for($i = 0; $i < 3; $i++)
    <span>{{ $i }}</span>
@endfor
```
Inside any loop the `$loop` variable is available, exposing `$loop->index`, `$loop->iteration`, `$loop->first`, `$loop->last`, `$loop->count` and `$loop->remaining`:
```blade
@foreach($employees as $employee)
    <li class="{{ $loop->first ? 'is-first' : '' }}">
        {{ $loop->iteration }}. {{ $employee["name"] }}
    </li>
@endforeach
```

## Switch
```blade
@switch($status)
    @case("active")
        <span class="badge">Active</span>
        @break
    @case("pending")
        <span class="badge">Pending</span>
        @break
    @default
        <span class="badge">Unknown</span>
@endswitch
```

## Raw PHP
Because Blade is a superset of PHP, these two are equivalent. Use whichever reads better:
```blade
<?php $total = array_sum($amounts); ?>

@php
    $total = array_sum($amounts);
@endphp

<p>Total: {{ $total }}</p>
```

## Including partials
Split shared markup into its own view and pull it in wherever you need it:
```blade
@include("partials.employee_card", ["employee" => $employee])

@includeIf("partials.banner")             {{-- only if the view exists --}}
@includeWhen($showTips, "partials.tips")  {{-- only when the condition is true --}}
```
An included partial receives the current view's data plus anything you pass in the second argument. Because the render engine resolves names across your project and the framework, a partial can live in either.

## Auth directives
ZubZet binds Blade's `@auth` and `@guest` to its own permission system. With no argument they test whether a user is logged in, and with an argument they test a permission (dotted and wildcard aware). `@guest` is the negation of `@auth`:
```blade
@auth
    <a href="{{ $opt["root"] }}account">My account</a>
@endauth

@auth("employee.edit")
    <a href="{{ $opt["root"] }}employee/edit">Edit</a>
@endauth

@guest
    <a href="{{ $opt["root"] }}login">Log in</a>
@endguest
```
See the [Permission System](permission-system.md) for how permissions are defined.

## Conditional HTML attributes
Blade has small helpers for the attributes you toggle most often, so you do not have to write the `<?php echo $x ? "checked" : "" ?>` dance:
```blade
<div @class(["card", "card-active" => $isActive, "card-muted" => !$isActive])>...</div>

<input type="checkbox" @checked($subscribed)>
<option value="de" @selected($lang === "de")>German</option>
<button @disabled($isSaving)>Save</button>
```

## Components
Components are reusable pieces of UI you drop in with an `<x-...>` tag. Create one as a `.blade.php` file under `z_views/components`, then use it by file name:
```blade
{{-- z_views/components/alert.blade.php --}}
@props(["type" => "info"])
<div class="alert alert-{{ $type }}">
    {{ $slot }}
</div>
```
```blade
{{-- in any view --}}
<x-alert type="warning">
    Your session is about to expire.
</x-alert>
```
Attributes on the tag (`type="warning"`) become variables inside the component (`$type`), and the content between the open and close tags is available as `{{ $slot }}`. `@props` declares the inputs a component expects and their defaults. A component in your project's `components` directory overrides a framework component of the same name.

### Framework components
The framework ships its own components under the `zubzet` namespace. The two you see in every layout are the page essentials:
```blade
<x-zubzet::head :opt="$opt"/>   {{-- jQuery, Bootstrap, Font Awesome, Z.js, the debug bar head --}}
<x-zubzet::body :opt="$opt"/>   {{-- the session watcher and the debug bar body --}}
```
The `zubzet::` namespace keeps these separate from your own components, so an app component named `head` never collides with `<x-zubzet::head/>`. You normally only place them in layouts, see [Layouts](layouts.md).

## Pushing to the layout with stacks
A view can push markup into a named stack that the layout renders elsewhere, which is handy for adding a page specific script without a dedicated section:
```blade
{{-- layout --}}
<head>
    ...
    @stack("scripts")
</head>
```
```blade
{{-- view --}}
@push("scripts")
    <script src="<?php $opt["generateResourceLink"]('js/chart.js'); ?>"></script>
@endpush
```

## What changed from the previous engine
Before v1.3.0 a view was a PHP file that returned an array of `head` and `body` closures, and the layout was chosen from the outside. Now a view is a Blade template that extends a layout. The migrator rewrites this for you (see [Migrating to 1.3.0](../setup/upgrade/1.2.0-to-1.3.0.md)), and the mapping is:

| Before (closure array) | Now (Blade) |
| ---------------------- | ----------- |
| `return ["body" => function($opt) { ... }]` | `@section("content") ... @endsection` |
| `return ["head" => function($opt) { ... }]` | `@section("head") ... @endsection` |
| the layout picked externally | `@extends($layout)` inside the view |
| copy and pasting shared markup | `@include` and components (`<x-...>`) |
| hand written `foreach` loops only | `@foreach`, `@forelse`, `$loop` and more (raw PHP still works) |

Beyond a cleaner syntax, the engine unlocks real template inheritance, reusable components and shared partials that the old closure format could not express.

## Not available in Katana
Katana implements the common Blade directives, which is everything on this page. A handful of Laravel specific directives are **not** available, because they depend on parts of Laravel the framework does not use: `@csrf`, `@method`, `@error`, `@can` / `@cannot`, `@lang` / `@choice`, `@vite`, `@dump` / `@dd` and `@inject`. Use the framework's own equivalents (for example `@auth("permission")` in place of `@can`) or plain PHP.

## Learn more
- [Katana](https://github.com/katanaphp/blade), the render engine ZubZet uses.
- [Laravel Blade documentation](https://laravel.com/docs/13.x/blade), the full reference for the Blade language.
- [Layouts](layouts.md), how views and layouts fit together.

More examples for views can be found in the framework's `src/IncludedComponents/views` directory.
