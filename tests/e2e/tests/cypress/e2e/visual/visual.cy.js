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
describe("Visual regression", { browser: "electron" }, () => {

    // Helper: capture the audit page and diff it against the committed
    // baseline. Throws with a clear message on first run so the user knows
    // to inspect & commit the seeded baseline.
    function check(name) {
        const baseline = `cypress/fixtures/visual-baselines/${name}.png`;

        cy.query("visual-page").should("be.visible");
        cy.get("body[data-fonts-ready='1']", { timeout: 15000 }).should("exist");

        const captured = {};
        cy.screenshot(name, {
            capture: "fullPage",
            overwrite: true,
            onAfterScreenshot: (_el, props) => { captured.path = props.path; },
        });

        cy.then(() => cy.task("compareScreenshot", {
            actualPath: captured.path,
            baselinePath: baseline,
            diffPath: captured.path.replace(/\.png$/, ".diff.png"),
        })).then((result) => {
            const diff = captured.path.replace(/\.png$/, ".diff.png");
            if (result.reason === "no-baseline") {
                throw new Error(`No baseline existed at ${baseline}. The actual screenshot was copied there; inspect it, then commit it and rerun.`);
            }
            if (result.reason === "size-mismatch") {
                throw new Error(`Screenshot size changed for ${name}. If intentional, delete the baseline and rerun.`);
            }
            expect(result.match, `${name}: ${result.diffPixels}/${result.totalPixels} px differ (tolerated ${result.tolerated}); see ${diff}`).to.be.true;
        });
    }

    it("Font Awesome icons match the committed baseline", () => {
        cy.visit("/visual/icons");
        check("font-awesome-audit");
    });

    it("Bootstrap components match the committed baseline", () => {
        cy.visit("/visual/bootstrap");
        check("bootstrap-audit");
    });
});
