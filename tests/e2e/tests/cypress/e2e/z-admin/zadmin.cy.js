describe('Z-Admin Panel', () => {
    before(() => {
        cy.dbSeed();
    });

    it('LoginAs', () => {
        cy.visit("/admin/loginas");
        cy.contains("0");

        cy.visit("/admin/loginas");
        cy.contains("1");
    });

});