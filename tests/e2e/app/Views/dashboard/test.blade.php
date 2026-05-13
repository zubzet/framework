<div class="container py-4" data-test="blade-page">

    <header class="mb-4">
        <h1 data-test="blade-heading">{{ $heading }}</h1>
        <p class="text-muted" data-test="blade-subtitle">{{ $subtitle }}</p>
    </header>

    <section class="mb-4">
        <h2>Escaping</h2>
        <p>Escaped (safe): <span data-test="blade-escaped">{{ $user_name }}</span></p>
        <p>Raw (unescaped): <span data-test="blade-raw">{!! $user_name !!}</span></p>
    </section>

    <section class="mb-4">
        <h2>Checklist</h2>
        <ul data-test="blade-checklist">
            @foreach($items as $item)
                <li @class(['text-success' => $item['ready'], 'text-danger' => !$item['ready']])>
                    @if($item['ready'])
                        <span data-test="blade-item-ready">[x]</span>
                    @else
                        <span data-test="blade-item-pending">[ ]</span>
                    @endif
                    {{ $item['name'] }}
                </li>
            @endforeach
        </ul>
    </section>

    <section class="mb-4">
        <h2>Status</h2>
        <p data-test="blade-status">
            @switch($status)
                @case('ok')
                    Everything is green.
                    @break
                @case('warn')
                    Heads up.
                    @break
                @default
                    Unknown state.
            @endswitch
        </p>
    </section>

    <section class="mb-4">
        <h2>Conditional class</h2>
        <div
            @class(['p-3', 'border', 'border-success' => $active, 'border-secondary' => !$active])
            data-test="blade-classstyle"
        >
            Class attribute built by Blade.
        </div>
    </section>

    <section class="mb-4">
        <h2>Inline PHP</h2>
        @php
            $now = date('Y-m-d H:i:s');
            $count = count($items);
        @endphp
        <p data-test="blade-phpblock">Rendered at {{ $now }} with {{ $count }} items.</p>
    </section>

    <section class="mb-4">
        <h2>Components</h2>
        @component('components.card', ['variant' => 'success'])
            @slot('title')
                Renderer pipeline
            @endslot
            Pluggable engines via <code>registerRenderer()</code>.
        @endcomponent

        @component('components.card', ['variant' => 'info'])
            @slot('title')
                BladeOne
            @endslot
            <strong>Slots</strong> carry markup, the default slot is <code>$slot</code>.
        @endcomponent
    </section>

    <section class="mb-4">
        <h2>Framework helpers</h2>
        <p data-test="blade-root">root: {{ $root }}</p>
        <p data-test="blade-title">title: {{ $title }}</p>
        <p data-test="blade-escape-helper">e('A &amp; B') &rarr; {{ e('A & B') }}</p>
    </section>

</div>
