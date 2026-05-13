<div class="card border-{{ $variant ?? 'secondary' }} mb-2" data-test="card">
    <div class="card-header" data-test="card-title">{!! $title !!}</div>
    <div class="card-body" data-test="card-body">
        {!! $slot !!}
    </div>
</div>
