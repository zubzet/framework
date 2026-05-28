// Visual-regression checks for the bundled frontend libraries (Font Awesome,
// Bootstrap). Each test renders a deterministic kitchen-sink page, takes a
// screenshot, and diffs it against a committed baseline.
//
// Re-run after bumping a library in composer.json: a real glyph swap or
// component restyle moves thousands of pixels and trips the diff. Sub-pixel
// anti-aliasing jitter is absorbed by the threshold in visualRegression.js.
//
// Pinned to Electron because other engines anti-alias type differently and
// the baseline will not match.
//
// The icon spec uses per-cell tiling: each `.icon-audit-cell` is diffed in
// isolation, so a single redrawn glyph saturates its tile (~hundreds of
// pixels in a 160×70 cell) while AA jitter spread across 200 cells doesn't
// force the global threshold up.
describe("Visual regression", { browser: "electron" }, () => {

    function takeScreenshot(name, then) {
        const captured = {};
        cy.screenshot(name, {
            capture: "fullPage",
            overwrite: true,
            onAfterScreenshot: (_el, props) => { captured.path = props.path; },
        });
        cy.then(() => then(captured.path));
    }

    function assertResult(name, baseline, result) {
        const diff = baseline.replace("/fixtures/visual-baselines/", "/screenshots/visual.cy.js/").replace(/\.png$/, ".diff.png");
        if (result.reason === "no-baseline") {
            throw new Error(`No baseline existed at ${baseline}. The actual screenshot was copied there; inspect it, then commit it and rerun.`);
        }
        if (result.reason === "size-mismatch") {
            throw new Error(`Screenshot size changed for ${name} (actual ${result.actualSize}, baseline ${result.baselineSize}). If intentional, delete the baseline and rerun.`);
        }
        if (result.reason === "region-diff") {
            const summary = result.failures
                .sort((a, b) => b.ratio - a.ratio)
                .slice(0, 10)
                .map(f => `  ${f.name}: ${(f.ratio * 100).toFixed(1)}% (${f.diffPixels}/${f.regionPixels} px)`)
                .join("\n");
            const more = result.failures.length > 10 ? `\n  …and ${result.failures.length - 10} more` : "";
            throw new Error(`${result.failures.length} region(s) exceed ${(result.regionThreshold * 100).toFixed(0)}% diff threshold for ${name}:\n${summary}${more}\nDiff: ${diff}`);
        }
        expect(result.match, `${name}: ${result.diffPixels}/${result.totalPixels} px differ (tolerated ${result.tolerated}); see ${diff}`).to.be.true;
    }

    it("Font Awesome icons match the committed baseline", () => {
        const name = "font-awesome-audit";
        const baseline = `cypress/fixtures/visual-baselines/${name}.png`;

        cy.visit("/visual/icons");
        cy.query("visual-page").should("be.visible");
        cy.get("body[data-fonts-ready='1']", { timeout: 15000 }).should("exist");

        // Capture *glyph* rects (32×32 fixed box), not cell rects. The cell
        // is mostly whitespace + label, which dilutes a wrong-glyph diff
        // below threshold; cropping to the glyph element makes the diff
        // signal-dominant. Coordinates are page-relative, which matches
        // pixel coordinates in the full-page screenshot 1:1 (Cypress runs
        // headless Electron at devicePixelRatio=1).
        cy.get(".icon-audit-glyph").then(($glyphs) => {
            const regions = $glyphs.toArray().map((el) => {
                const r = el.getBoundingClientRect();
                return {
                    name: el.closest(".icon-audit-cell").dataset.icon,
                    x: Math.floor(r.left),
                    y: Math.floor(r.top),
                    width: Math.ceil(r.width),
                    height: Math.ceil(r.height),
                };
            });

            takeScreenshot(name, (actualPath) => {
                cy.task("compareScreenshot", {
                    actualPath,
                    baselinePath: baseline,
                    diffPath: actualPath.replace(/\.png$/, ".diff.png"),
                    regions,
                    // Per-tile threshold. Tuned against the FA 5→6 transition:
                    // observed real-world drift on refined-but-not-redrawn
                    // glyphs maxed out at 2.4%. 5% leaves comfortable headroom
                    // over that while still catching outright glyph swaps,
                    // missing-glyph fallback boxes (which saturate the cell),
                    // or geometry shifts > a few pixels.
                    regionThreshold: 0.05,
                }).then((result) => assertResult(name, baseline, result));
            });
        });
    });

    it("Bootstrap components match the committed baseline", () => {
        const name = "bootstrap-audit";
        const baseline = `cypress/fixtures/visual-baselines/${name}.png`;

        cy.visit("/visual/bootstrap");
        cy.query("visual-page").should("be.visible");
        cy.get("body[data-fonts-ready='1']", { timeout: 15000 }).should("exist");

        takeScreenshot(name, (actualPath) => {
            cy.task("compareScreenshot", {
                actualPath,
                baselinePath: baseline,
                diffPath: actualPath.replace(/\.png$/, ".diff.png"),
            }).then((result) => assertResult(name, baseline, result));
        });
    });
});
