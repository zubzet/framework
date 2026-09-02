describe('Translation - Locale Resolution', () => {

    beforeEach(() => {
        cy.dbSeed({ exclude: ['environment'], include: ['environment/test/language'] });
    });

    it("should use the default domain when no domain is specified", () => {
        cy.loginAs('locale_en');
        cy.visit('/translation/default-domain');
        cy.contains("Default");
    });

    it("should use the specified domain when a domain is specified", () => {
        cy.loginAs('locale_en');
        cy.visit('/translation/domain');
        cy.contains("Domain");
    });

    it("should use the user's preferred language", () => {
        cy.loginAs('locale_de');
        cy.visit('/translation/preferred');
        cy.contains("Bevorzugt");

        cy.loginAs('locale_en');
        cy.visit('/translation/preferred');
        cy.contains("Preferred");
    });

});
