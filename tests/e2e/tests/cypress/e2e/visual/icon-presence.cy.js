// Belt-and-braces companion to the FA visual-regression spec. The pixel
// diff catches glyph *changes* against a committed baseline, but if a class
// name silently produces no glyph at all (renamed in a major FA bump,
// dropped from the Free tier, never existed under the asserted prefix),
// you'd only notice from the screenshot itself. This spec asserts every
// audit cell renders a non-empty ::before content, so a missing glyph
// fails loudly and points at the offending class.
describe("Icon presence audit", { browser: "electron" }, () => {
    it("every audit cell renders a glyph", () => {
        cy.visit("/visual/icons");
        cy.get("body[data-fonts-ready='1']", { timeout: 15000 }).should("exist");

        cy.document().then((doc) => {
            const win = doc.defaultView;
            const cells = doc.querySelectorAll(".icon-audit-cell");
            const missing = [];
            cells.forEach((cell) => {
                const glyph = cell.querySelector(".icon-audit-glyph");
                const content = win.getComputedStyle(glyph, "::before").content;
                // Browsers report unset ::before content as "none"; an empty
                // string literal as '""' or "''". Either means no glyph.
                if (!content || content === "none" || content === '""' || content === "''" || content === "normal") {
                    missing.push(cell.dataset.icon);
                }
            });
            expect(missing, `Missing glyphs: ${missing.join(", ")}`).to.be.empty;
        });
    });
});
