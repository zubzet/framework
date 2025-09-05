describe('Configuration', () => {
    before(() => {
        cy.dbSeed();
    });

    it('Configuration', () => {
        cy.visit("/Core/Configuration");
        cy.contains("TestValue");
    });
});