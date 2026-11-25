describe('Controllers', () => {
    before(() => {
        cy.dbSeed();
    });

    it('Controller Index', () => {
        cy.visit("/Core");
        cy.contains("Controller Index");
    });

    it('Controller Action', () => {
        cy.visit("/Core/Action");
        cy.contains("Controller Action");
    });

    // The fallback action and the parameters of an action are unit tested in
    // tests/unit/php/Routing/RequestLifecycleTest.php

    it('Controller Render', () => {
        cy.visit("/Core/Render");
        cy.query("title").contains("Render");
        cy.query("data").contains("Data");
    });

    it("framework's default IndexController renders the placeholder index view", () => {
        // Hits IncludedComponents/controllers/IndexController::action_index, which
        // renders views/index.php inside layout/default_layout.php. Test apps
        // normally override defaultIndex; this test exercises the framework default.
        cy.visit("/index/index");
        cy.contains("Replace this title");
    });
});