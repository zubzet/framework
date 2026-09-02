describe('Translation - Locale Resolution', () => {

    beforeEach(() => {
        cy.dbSeed({ exclude: ['environment'], include: ['environment/test/language'] });
    });

    it("should use the default domain when no domain is specified", () => {
        cy.loginAs('locale_en');
        cy.visit('/translation/default-domain');
        cy.contains("Default");

        cy.loginAs('locale_de');
        cy.visit('/translation/default-domain');
        cy.contains("Standard");

    });

});
