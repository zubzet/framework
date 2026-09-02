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

    it("should use the different loaders", () => {
        cy.loginAs('locale_de');

        ["php", "csv", "ini"].forEach((loader) => {
            cy.visit(`/translation/loader/${loader}`);
            cy.contains(`${loader}-Nachricht`);
        });
    });

    describe('with no user language preference', () => {

        const visitWith = (acceptLanguage) => {
            cy.loginAs('locale_undefined');
            cy.visit('/translation/message', {
                headers: {
                    'Accept-Language': acceptLanguage
                }
            });
        };

        it("should use the requested language when it has a catalogue", () => {
            visitWith('de');
            cy.contains("Nachricht");

            visitWith('en');
            cy.contains("Message");
        });

        it("should prefer the entry with the highest quality", () => {
            visitWith('en;q=0.3, de;q=0.9');
            cy.contains("Nachricht");

            visitWith('de;q=0.3, en;q=0.9');
            cy.contains("Message");
        });

        it("should skip requested languages that have no catalogue", () => {
            visitWith('fr, it, de');
            cy.contains("Nachricht");
        });

        it("should match a region against the catalogue's base language", () => {
            visitWith('de-CH');
            cy.contains("Nachricht");
        });

        // Last resort is fallback_locales[0]. "fr" is configured as the second
        // fallback and has no catalogue, so landing on English proves the first
        // entry is taken rather than just any of them.
        it("should use the first fallback locale when no requested language has a catalogue", () => {
            visitWith('fr, it');
            cy.contains("Message");
        });

    });

});
