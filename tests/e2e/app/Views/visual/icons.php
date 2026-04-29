<?php return ["body" => function ($opt) { ?>
    <style>
        /* Override anything that introduces non-determinism. The screenshot
         * has to be byte-stable across runs, so we kill animations, force a
         * known background, and lock font rendering hints. */
        html, body { background: #ffffff !important; margin: 0; padding: 0; }
        body { font-family: monospace; }
        *, *::before, *::after { animation: none !important; transition: none !important; }
        /* Debugbar is dev-mode chrome — its query timings, memory readout,
         * and version stamp differ every run. Hide it for the screenshot. */
        .phpdebugbar, div.phpdebugbar-openhandler { display: none !important; }
        /* 7 cols × 160 + 6 × 8 gap + 32 padding = 1200, fits the 1280 viewport
         * with margin to spare so a small zoom doesn't clip the right column. */
        .icon-audit-grid {
            display: grid;
            grid-template-columns: repeat(7, 160px);
            gap: 8px;
            padding: 16px;
        }
        .icon-audit-cell {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
            background: #fafafa;
            min-height: 70px;
        }
        .icon-audit-cell .icon-audit-glyph {
            font-size: 24px;
            line-height: 32px;
            color: #222;
            display: block;
            height: 32px;
        }
        .icon-audit-cell .icon-audit-label {
            font-size: 10px;
            color: #555;
            margin-top: 4px;
            word-break: break-all;
        }
    </style>

    <div data-test="visual-page">
        <div class="icon-audit-grid">
            <?php foreach ($opt["icons"] as [$prefix, $name]) {
                $cls = htmlspecialchars($prefix . " fa-" . $name, ENT_QUOTES);
                $label = htmlspecialchars($prefix . " " . $name, ENT_QUOTES);
            ?>
                <div class="icon-audit-cell" data-icon="<?= $label ?>">
                    <i class="icon-audit-glyph <?= $cls ?>" aria-hidden="true"></i>
                    <div class="icon-audit-label"><?= $label ?></div>
                </div>
            <?php } ?>
        </div>
    </div>

    <script>
        document.fonts.ready.then(function () {
            document.body.setAttribute("data-fonts-ready", "1");
        });
    </script>
<?php }]; ?>
