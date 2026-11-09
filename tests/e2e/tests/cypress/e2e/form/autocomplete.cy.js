describe("Form Autocomplete", () => {
    before(() => {
        cy.dbSeed();
    });

    it("Renders the suggestions when the answer arrives after the last keystroke", () => {
        // Holds every answer back until the typing is over
        cy.intercept("POST", "/Form/autocomplete", (req) => {
            req.continue((res) => res.setDelay(300));
        });
        cy.visit("/Form/autocomplete");

        cy.form("city").type("Aachen");

        cy.query("form").find('[data-search-term="Aachen"] button')
            .should("have.length", 1)
            .and("contain.text", "Aachen");
    });

    it("Renders suggestions as text instead of markup", () => {
        cy.visit("/Form/autocomplete");

        cy.form("city").type("Xa");

        cy.query("form").find('[data-search-term="Xa"] button')
            .should("have.length", 1)
            .and("contain.text", '<img src=x onerror="window.__xss=1">')
            .find("img").should("not.exist");
        cy.window().its("__xss").should("be.undefined");
    });
});
