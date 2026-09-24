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

    it("should prefer the user's language over the Accept-Language header", () => {
        cy.loginAs('locale_de');
        cy.visit('/translation/message', { headers: { 'Accept-Language': 'en' } });
        cy.contains("Nachricht");

        cy.loginAs('locale_en');
        cy.visit('/translation/message', { headers: { 'Accept-Language': 'de' } });
        cy.contains("Message");
    });

    it("should fall back when the user's language has no catalogue", () => {
        cy.loginAs('locale_uncovered');
        cy.visit('/translation/message', { headers: { 'Accept-Language': 'de' } });
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

        // de_CH has a catalogue of its own, de_AT has not.
        it("should match a region against the catalogue's base language", () => {
            visitWith('de-AT');
            cy.contains("Nachricht");
        });

        it("should prefer the catalogue that spells the region exactly", () => {
            visitWith('de-CH');
            cy.contains("CH-Meldung");
        });

        // Both "de" and "de_CH" are prefixes of the request; the longer one wins, and
        // which of them the catalogue scan happens to reach first must not matter.
        it("should prefer the most specific catalogue when several could match", () => {
            visitWith('de-CH-1901');
            cy.contains("CH-Meldung");
        });

        // Last resort is fallback_locales[0]. "fr" is configured as the second
        // fallback and has no catalogue, so landing on English proves the first
        // entry is taken rather than just any of them.
        it("should use the first fallback locale when no requested language has a catalogue", () => {
            visitWith('fr, it');
            cy.contains("Message");
        });

    });

    // app/Translations/messages.de.json redefines form.unsaved, a key the framework
    // catalogue (src/IncludedComponents/translations/messages.de.json) also carries.
    describe('when the app redefines a key of a framework catalogue', () => {

        it("should replace only the redefined key", () => {
            cy.loginAs('locale_de');
            cy.visit('/translation/catalogue-override');

            cy.contains("AppUngespeichert");
            cy.get('body').should('not.contain.text', "Es gibt ungespeicherte Änderungen");
        });

        it("should keep the rest of the framework catalogue", () => {
            cy.loginAs('locale_de');
            cy.visit('/translation/catalogue-override');

            cy.contains("Gespeichert!");
            cy.contains("Bitte füllen Sie dieses Feld aus");
        });

        it("should keep the app's own keys in the same domain", () => {
            cy.loginAs('locale_de');
            cy.visit('/translation/catalogue-override');

            cy.contains("Nachricht");
        });

        // Only the German app catalogue redefines the key, so English has to stay
        // entirely with the framework.
        it("should leave the locales the app does not redefine untouched", () => {
            cy.loginAs('locale_en');
            cy.visit('/translation/catalogue-override');

            cy.contains("There are unsaved changes");
            cy.contains("Saved!");
            cy.contains("Please fill in this field");
        });
    });

    describe('when a catalogue value carries javascript syntax', () => {

        const hostile = 'Schon vergeben: " \' & </script>';

        beforeEach(() => {
            cy.loginAs('locale_de');
            cy.visit('/translation/lang');
        });

        it("should hand the value to Z.Lang unchanged", () => {
            cy.window().its('Z.Lang.error_unique').should('equal', hostile);
        });
    });

});
