describe('Form Url Validation', () => {
    before(() => {
        cy.dbSeed();
    });

    it('Correct Input', () => {
        cy.visit("/Form/validationUrl");

        cy.get('[name="field_url"]').click().type("https://www.test.de");
        cy.get('[name="field_url_required"]').click().type("https://www.test.de");
        cy.get('[name="field_url_length"]').click().type("https://www.test.de");
        cy.get('[name="field_url_unique"]').click().type("https://www.test.de");
        cy.get('button').click();
        cy.get('#form').contains("Saved!");
    });

    it('Correct Input Ignore Unrequired Fields', () => {
        cy.visit("/Form/validationUrl");

        cy.get('[name="field_url_required"]').click().type("https://www.test.de");
        cy.get('[name="field_url_length"]').click().type("https://www.test.de");
        cy.get('[name="field_url_unique"]').click().type("https://www.test.de");
        cy.get('button').click();
        cy.get('#form').contains("Saved!");
    });

    it('Input Failure Required', () => {
        cy.visit("/Form/validationUrl");
        cy.get('[name="field_url"]').click().type("https://www.test.de");
        cy.get('[name="field_url_length"]').click().type("https://www.test.de");
        cy.get('[name="field_url_unique"]').click().type("https://www.test.de");
        cy.get('button').click();

        cy.get('[name="field_url_required"]').parent().contains("Please fill in this field");
    });

    it('Input Failure Length', () => {
        cy.visit("/Form/validationUrl");

        cy.get('[name="field_url"]').click().type("https://www.test.de");
        cy.get('[name="field_url_required"]').click().type("https://www.test.de");
        cy.get('[name="field_url_length"]').click().type("http://test.de");
        cy.get('[name="field_url_unique"]').click().type("https://www.test.de");
        cy.get('button').click();

        cy.get('[name="field_url_length"]').parent().contains("Your input is too long or too short.");
    });

    it('Input Failure Type', () => {
        cy.visit("/Form/validationUrl");

        cy.get('[name="field_url"]').click().type("NotAUrl")
        cy.get('[name="field_url_required"]').click().type("https://www.test.de");
        cy.get('[name="field_url_length"]').click().type("https://www.test.de");
        cy.get('[name="field_url_unique"]').click().type("https://www.test.de");
        cy.get('button').click();

        cy.get('[name="field_url"]').parent().contains("Your input does not have the correct pattern!");
    });

    it('Input Failure Unique', () => {
        cy.visit("/Form/validationUrl");

        cy.get('[name="field_url"]').click().type("https://www.test.de")
        cy.get('[name="field_url_required"]').click().type("https://www.test.de");
        cy.get('[name="field_url_length"]').click().type("https://www.test.de");
        cy.get('[name="field_url_unique"]').click().type("https://www.unique.de");
        cy.get('button').click();

        cy.get('[name="field_url_unique"]').parent().contains("This already exists!");
    });

});