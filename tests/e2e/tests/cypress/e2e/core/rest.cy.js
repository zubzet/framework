describe('Rest', () => {
    before(() => {
        cy.dbSeed();
    });

    it('REST API', () => {
        cy.visit("/Core/Rest");
        cy.contains("Response");
        cy.contains("Test");

        cy.visit("/Core/RestErr");
        cy.contains("400");
        cy.contains("TestErr");
    });
});