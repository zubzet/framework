describe('Permissions', () => {
    before(() => {
        cy.dbSeed();
    });

    it('Permissions', () => {
        cy.loginAs("admin");
        cy.visit("/Core/Permission");
        cy.contains("Permissions");
        cy.visit("/Core/Permission1");
        cy.contains("Administrator");

        cy.loginAs("customer");
        cy.sendRequest("/Core/Permission", 403);
        cy.visit("/Core/Permission1");
        cy.contains("Administrator").should("not.exist");
    });
});