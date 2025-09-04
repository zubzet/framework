describe('Form EMail Validation', () => {
    before(() => {
        cy.dbSeed();
    });

    it('Correct Input', () => {
        cy.visit("/Form/validationEMail");

        cy.get('[name="field_email"]').click().type("Test@gmail.com");
        cy.get('[name="field_email_required"]').click().type("Test@gmail.com");
        cy.get('[name="field_email_length"]').click().type("Test@gmail.com");
        cy.get('[name="field_email_unique"]').click().type("Test@gmail.com");
        cy.get('button').click();
        cy.get('#form').contains("Saved!");
    });

    it('Correct Input Ignore Unrequired Fields', () => {
        cy.visit("/Form/validationEMail");

        cy.get('[name="field_email_required"]').click().type("Test@gmail.com");
        cy.get('[name="field_email_length"]').click().type("Test@gmail.com");
        cy.get('[name="field_email_unique"]').click().type("Test@gmail.com");
        cy.get('button').click();
        cy.get('#form').contains("Saved!");
    });

    it('Input Failure Required', () => {
        cy.visit("/Form/validationEMail");
        cy.get('[name="field_email"]').click().type("Test@gmail.com");
        cy.get('[name="field_email_length"]').click().type("Test@gmail.com");
        cy.get('[name="field_email_unique"]').click().type("Test@gmail.com");
        cy.get('button').click();

        cy.get('[name="field_email_required"]').parent().contains("Please fill in this field");
    });

    it('Input Failure Length', () => {
        cy.visit("/Form/validationEMail");

        cy.get('[name="field_email"]').click().type("Test@gmail.com");
        cy.get('[name="field_email_required"]').click().type("Test@gmail.com");
        cy.get('[name="field_email_length"]').click().type("T@ml.de");
        cy.get('[name="field_email_unique"]').click().type("Test@gmail.com");
        cy.get('button').click();

        cy.get('[name="field_email_length"]').parent().contains("Your input is too long or too short.");
    });

    it('Input Failure Type', () => {
        cy.visit("/Form/validationEMail");

        cy.get('[name="field_email"]').click().type("NotAEMail")
        cy.get('[name="field_email_required"]').click().type("Test@gmail.com");
        cy.get('[name="field_email_length"]').click().type("Test@gmail.com");
        cy.get('[name="field_email_unique"]').click().type("Test@gmail.com");
        cy.get('button').click();

        cy.get('[name="field_email"]').parent().contains("Your input does not have the correct pattern!");
    });

    it('Input Failure Type', () => {
        cy.visit("/Form/validationEMail");

        cy.get('[name="field_email"]').click().type("Test@gmail.com")
        cy.get('[name="field_email_required"]').click().type("Test@gmail.com");
        cy.get('[name="field_email_length"]').click().type("Test@gmail.com");
        cy.get('[name="field_email_unique"]').click().type("Unique@gmail.com");
        cy.get('button').click();

        cy.get('[name="field_email_unique"]').parent().contains("This already exists!");
    });

});