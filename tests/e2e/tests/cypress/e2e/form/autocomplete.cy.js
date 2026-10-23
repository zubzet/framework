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

        cy.query("form").find(".list-group button")
            .should("have.length", 1)
            .and("contain.text", "Aachen");
    });
});
