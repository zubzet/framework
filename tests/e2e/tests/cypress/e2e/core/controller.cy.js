describe('Controllers', () => {
    before(() => {
        cy.dbSeed();
    });

    it('Controller Index', () => {
        cy.visit("/Core");
        cy.contains("Controller Index");
    });

    it('Controller Fallback', () => {
        cy.visit("/Core/AABBCCDDEEFFGG");
        cy.contains("Controller Fallback");
    });

    it('Controller Action', () => {
        cy.visit("/Core/Action");
        cy.contains("Controller Action");
    });

    it('Controller Parameters', () => {
        cy.visit("/Core/Parameters/Param1/Param2");
        cy.contains("Param1");
        cy.contains("Param2");

        cy.visit("/Core/Parameters1/TestParameter");
        cy.contains("Ja");

        cy.visit("/Core/Parameters1/TestParameterss");
        cy.contains("Nein");

        cy.visit("/Core/Parameters2/ABC/DEF/GHI/JKL");
        cy.contains("ABC");
        cy.contains("DEF");
        cy.contains("GHI").should("not.exist");
    });

    it('Controller Render', () => {
        cy.visit("/Core/Render");
        cy.query("title").contains("Render");
        cy.query("data").contains("Data");
    });
});