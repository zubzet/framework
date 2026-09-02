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

    it("should replace parameters in the translation string", () => {
        cy.loginAs('locale_en');
        cy.visit('/translation/param-message');
        cy.contains("ParamMessage:First:Second");

        cy.loginAs('locale_de');
        cy.visit('/translation/param-message');
        cy.contains("ParamNachricht:First:Second");
    });

    it("should use the defined locale", () => {
        cy.loginAs('locale_en');
        cy.visit('/translation/defined-locale');
        cy.contains("Nachricht");

        cy.loginAs('locale_de');
        cy.visit('/translation/defined-locale');
        cy.contains("Nachricht");
    });

    it("should use the default locale when no user preference is set", () => {
        cy.loginAs('locale_undefined');
        cy.visit('/translation/message');
        cy.contains("Nachricht");
    });

    it("should use the fallback locale when no translation is available in the user's preferred language", () => {
        cy.visit('/translation/fallback-locale');
        cy.contains("Message");
    });

    describe('with a chain of fallback locales', () => {

        before(() => {
            cy.saveConfigBackup();
            // Chain of fallback
            cy.setConfigSetting('fallback_locales', 'fr, it, de');
        });

        after(() => cy.restoreConfigBackup());

        it("should walk the fallback chain until a locale has a catalogue", () => {
            cy.visit('/translation/fallback-locale');
            cy.contains("Nachricht");
        });

    });

});
