<h2 data-test="title">Blade compatibility probe</h2>

<!-- Literal template markers that must survive migration verbatim (not be
     interpreted by Blade). These are the kinds of tokens real apps embed in
     JS/HTML: handlebars/Vue mustaches, "raw" markers, and comment markers. -->
<div data-test="literal-echo">@{{ notBladeEcho }}</div>
<div data-test="literal-raw">@{!! notBladeRaw !!}</div>
<div data-test="literal-comment">@verbatim{{-- notBladeComment --}}@endverbatim</div>

<!-- Real PHP still runs and reads $opt as before. -->
<div data-test="opt-data"><?php echo $opt["compatData"]; ?></div>

<!-- CSS at-rules must pass through untouched (not be seen as Blade directives). -->
<style>
    @media (max-width: 600px) {
        .compat-probe { color: red; }
    }
</style>

<!-- A JS mustache template embedded in a script tag. -->
<script>
    var compatTpl = "@{{ vueStyleBinding }}";
    document.querySelector('[data-test="js-literal"]').textContent = compatTpl;
</script>
<div data-test="js-literal"></div>

