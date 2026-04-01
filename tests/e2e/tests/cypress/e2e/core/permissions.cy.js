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

    it("should show the correct permissions for the superuser", () => {
        cy.loginAs("admin");
        cy.visit("/admin/e2e-loginas-exec");

        cy.request("/Core/e2e-SuperPermission").its("body")
            .then((body) => {
                const parsed = JSON.parse(body);

                expect(parsed).to.deep.equal({
                    checkSuperPerm: true,
                    checkPerm: false
                });
            });
    });
});