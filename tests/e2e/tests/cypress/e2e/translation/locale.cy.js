describe('Translation - Locale Resolution', () => {

    beforeEach(() => {
        cy.dbSeed({ exclude: ['environment'], include: ['environment/test/language'] });
    });

    it("should translate messages correctly (without domain specified)", () => {
        cy.loginAs('locale_en');
        cy.visit('/translation/message');
        cy.contains("Message");
    });

    it("should use the user's preferred language", () => {
        cy.loginAs('locale_de');
        cy.visit('/translation/message');
        cy.contains("Nachricht");

        cy.loginAs('locale_en');
        cy.visit('/translation/message');
        cy.contains("Message");
    });

    it("should use the specified domain when a domain is specified", () => {
        cy.loginAs('locale_en');
        cy.visit('/translation/domain-message');
        cy.contains("TestMessage");

        cy.loginAs('locale_de');
        cy.visit('/translation/domain-message');
        cy.contains("TestNachricht");
    });

});
