describe('Core Features', () => {
    before(() => {
        cy.dbSeed();
    });

    it('Backend Request', () => {
        cy.visit("/advanced/aliases");
        cy.contains("Controller Action");
    });

});